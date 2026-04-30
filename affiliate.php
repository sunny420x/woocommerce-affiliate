<?php
/**
 * Plugin Name: World Chemical Affiliate Marketing
 * Description: ระบบ Affiliate Marketing สำหรับ World Chemical
 * Version: 1.0
 * Author: Jirakit Pawnsakunrungrot
 * Author URI: https://www.linkedin.com/in/sunny-jirakit
 * Plugin URI: https://github.com/sunny420x/woocommerce-affiliate
 */

//Deny access from URL.
if (!defined('ABSPATH'))
    exit;

function afiliate_enqueue_assets()
{
    //Load CSS
    wp_enqueue_style(
        'style',
        plugins_url('/css/style.css', __FILE__),
        array(),
        time()
    );

    wp_enqueue_style(
        'style',
        plugins_url('/css/admin.css', __FILE__),
        array(),
        time()
    );

    //Load JS
    wp_enqueue_script(
        'sds',
        plugins_url('/js/affiliate-client.js', __FILE__),
        array(),
        time(),
        true
    );
}

//Load Afiliate Assets
add_action('wp_enqueue_scripts', 'afiliate_enqueue_assets');

function dashboard_styling()
{
    wp_enqueue_style(
        'affiliate-admin-style',
        plugins_url('/css/admin.css', __FILE__),
        array(),
        '1.0.0'
    );
}
// Change 'admin_head' to 'admin_enqueue_scripts'
add_action('admin_enqueue_scripts', 'dashboard_styling');

// Function to add the menu page
function affiliate_admin_menu()
{
    add_menu_page(
        'ระบบ Affiliate | World Chemical',    // Page title
        'ระบบ Affiliate',                     // Menu title
        'manage_options',                        // Capability required
        'affiliate',                             // Menu slug
        'affiliate_admin_management',            // Callback function to display page content
        'dashicons-star-filled',                 // Icon URL or Dashicon class
        80                                       // Position in the menu (optional)
    );
}

//Add Menu to Wordpress Admin
add_action('admin_menu', 'affiliate_admin_menu');

// Function to display the content of the custom page
function affiliate_admin_management()
{
    echo '<div class="wrapper">';
    echo get_all_users_table();
    echo '</div>';
}

function affiliate_admin_management_users()
{
    $id = isset($_GET['id']) ? absint($_GET['id']) : 0;

    echo '<div class="wrapper">';
    echo get_user_editor($id);
    echo '</div>';
}

register_activation_hook( __FILE__, 'my_plugin_install' );

function my_plugin_install() {
    global $wpdb;
    // $charset_collate = $wpdb->get_charset_collate();
    $charset_collate = "DEFAULT CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci";
    
    $new_table = $wpdb->prefix . 'users_affiliate_info';
    $new_table_transaction = $wpdb->prefix . 'affiliate_transactions';
    $user_table = $wpdb->prefix . 'users';

    $create_table_query = "CREATE TABLE IF NOT EXISTS $new_table (
        id int(11) NOT NULL AUTO_INCREMENT,
        user_id int(11) NOT NULL,
        bank_account_number varchar(20) NOT NULL,
        bank_name varchar(50) NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    $create__transaction_table_query = "CREATE TABLE IF NOT EXISTS $new_table_transaction (
        id int(12) NOT NULL AUTO_INCREMENT,
        refCode varchar(50) NOT NULL,
        product_id int(12) NOT NULL,
        type varchar(10) NOT NULL,
        order_id int(11) NOT NULL,
        commission_percentage int(2) NOT NULL DEFAULT 10,
        created_at datetime NOT NULL,
        paid int(1) NOT NULL DEFAULT 0,
        paid_at datetime DEFAULT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $create_table_query );
    dbDelta( $create__transaction_table_query );

    // เช็คว่ามีคอลัมน์ refCode หรือยัง เพื่อป้องกัน Error ตอนรันซ้ำ
    $row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = '".DB_NAME."' 
        AND TABLE_NAME = '$user_table' 
        AND COLUMN_NAME = 'refCode'");

    if (empty($row)) {
        $wpdb->query("ALTER TABLE $user_table ADD `refCode` VARCHAR(50) DEFAULT NULL");
    }
}

add_action('admin_init', 'wcc_handle_mark_as_paid');

function wcc_handle_mark_as_paid()
{
    if (isset($_GET['action']) && $_GET['action'] == 'mark_paid') {
        global $wpdb;

        // เช็คความปลอดภัย (ถ้าไม่ผ่านมันจะแค่เด้งออก ไม่ Critical Error)
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'wcc_mark_paid_nonce')) {
            return;
        }

        $ref_to_pay = sanitize_text_field($_GET['refCode'] ?? '');
        $table_name = $wpdb->prefix . 'affiliate_transactions';

        $wpdb->update(
            $table_name,
            array('paid' => 1, 'paid_at' => current_time('mysql')),
            array('refCode' => $ref_to_pay, 'paid' => 0),
            array('%d', '%s'),
            array('%s', '%d')
        );

        // หลังจาก Update เสร็จ ให้ Redirect กลับหน้าเดิมแบบคลีนๆ
        wp_redirect(admin_url('admin.php?page=affiliate&status=paid_success'));
        exit;
    }
}


class Affiliate {
    
    public $wpdb;
    public $tables = [];

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->tables = [
            'users'        => $wpdb->prefix . 'users',
            'transactions' => $wpdb->prefix . 'affiliate_transactions',
            'order_stats'  => $wpdb->prefix . 'wc_order_stats',
        ];
    }

    public function getAffiliate($query_option = '') {
        $query = "
            SELECT 
                u.ID,
                u.display_name,
                u.user_email,
                u.refCode,
                COUNT(CASE WHEN t.type = 'view' THEN 1 END) AS total_views,
                COUNT(CASE WHEN t.type = 'sale' THEN 1 END) AS total_sales_count,
                SUM(CASE 
                    WHEN t.type = 'sale' AND os.total_sales IS NOT NULL 
                    THEN os.total_sales 
                    ELSE 0 
                END) AS total_revenue,
                SUM(CASE 
                    WHEN t.type = 'sale' AND os.total_sales IS NOT NULL 
                    THEN os.total_sales * (t.commission_percentage / 100)
                    ELSE 0 
                END) AS total_earns
            FROM {$this->tables['users']} AS u
            LEFT JOIN {$this->tables['transactions']} AS t
                ON u.refCode = t.refCode
            LEFT JOIN {$this->tables['order_stats']} AS os 
                ON t.order_id = os.order_id 
            {$query_option} 
            GROUP BY u.ID, u.display_name, u.user_email, u.refCode
            ORDER BY u.ID DESC
        ";

        return $this->wpdb->get_results($query);
    }

    public function getAffiliateByRefCode($refCode) {
        $query = $this->wpdb->prepare("
            SELECT 
                u.ID,
                u.display_name,
                u.user_email,
                u.refCode, 
                os.order_id,
                os.total_sales,
                os.total_sales * (t.commission_percentage / 100) as total_earns,
                t.paid
            FROM {$this->tables['users']} AS u
            LEFT JOIN {$this->tables['transactions']} AS t
                ON u.refCode = t.refCode 
            LEFT JOIN {$this->tables['order_stats']} AS os 
                ON t.order_id = os.order_id 
            WHERE u.refCode = %s 
            ORDER BY u.ID DESC
        ", $refCode);

        return $this->wpdb->get_results($query);
    }
}


function get_all_users_table() {
    global $wpdb;
    $affiliate = new Affiliate();
    $waiting_for_payments = $affiliate->getAffiliate("WHERE u.refCode IS NOT NULL AND u.refCode != '' AND t.paid = 0 ");
    $success_payments = $affiliate->getAffiliate("WHERE u.refCode IS NOT NULL AND u.refCode != '' AND t.paid = 1 ");
    ?>

    <div class='card-admin'>
        <h1>🤝🏻 ระบบการตลาดแบบพันธมิตรสำหรับ WooCommerce | Affiliate Program</h1>
        <p>ระบบ Affiliate กระตุ้นการขายบนเว็บไซต์ โดยการให้เปอร์เซ็น Affiliate Partner เป็นจำนวน
            <?= get_option('affiliate_commission', 10); ?>% ของยอดขายสินค้า</p>
        <form action="options.php" method="post">
            <?php
            settings_fields('affiliate_settings_group');
            ?>
            <label for="affiliate_enable">เปิดใช้งานระบบพันธมิตร: </label>
            <select name="affiliate_enable">
                <option value="yes" <?php if(esc_attr(get_option('affiliate_enable', 'yes')) == 'yes') { echo "selected"; } ?>>เปิดใช้งาน</option>
                <option value="no" <?php if(esc_attr(get_option('affiliate_enable', 'yes')) == 'no') { echo "selected"; } ?>>ปิดใช้งาน</option>
            </select>
            <br>
            <br>
            <label for="affiliate_logo">ลิงค์รูปภาพ Logo บริษัท (สำหรับออกรายงาน):</label>
            <input name="affiliate_logo" type="text" value="<?=esc_attr(get_option('affiliate_logo', ''))?>" style="width: 500px;">
            <br>
            <br>
            <label for="affiliate_commission">% Commission: </label><input type="number" name="affiliate_commission"
                value="<?= esc_attr(get_option('affiliate_commission', 10)); ?>" /> %
            <p>* การอัพเดท % Commission จะไม่มีผลย้อนหลังกับข้อมูลการขายเดิมในระบบ แต่จะมีผลกับข้อมูลการขายใหม่ที่จะถูกเพิ่มเข้ามาหลังจากอัพเดท</p>
            <input type="submit" class="button button-primary" value="บันทึกการเปลี่ยนแปลง">
        </form>
    </div>
    <br>
    <div class='card-admin'>
        <h1>📊สรุปยอดของพันธมิตร</h1>
        <hr>
        <h2>💸 ยอด Commission ของสมาชิกที่รอจ่าย</h2>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th>ชื่อในระบบ</th>
                    <th>Email</th>
                    <th>หมายเลข Ref</th>
                    <th>ยอดเข้าชม</th>
                    <th>ขายได้ (รายการ)</th>
                    <th>ยอดขาย</th>
                    <th>ยอด Commission ทั้งหมด</th>
                    <th>บัญชีปลายทาง</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($waiting_for_payments)) {
                    foreach ($waiting_for_payments as $row) {

                        $user_bank_info =  $wpdb->get_results($wpdb->prepare("SELECT bank_account_number, bank_name FROM {$wpdb->prefix}users_affiliate_info WHERE user_id = %d LIMIT 1", $row->ID));

                        $mark_as_paid_action_url = wp_nonce_url(
                            admin_url('admin.php?page=affiliate&action=mark_paid&refCode=' . $row->refCode),
                            'wcc_mark_paid_nonce'
                        );
                        ?>
                        <tr>
                            <td><?= esc_html($row->display_name) ?></td>
                            <td><?= esc_html($row->user_email) ?></td>
                            <td><?= esc_html($row->refCode) ?></td>
                            <td><?= esc_html($row->total_views) ?></td>
                            <td><?= esc_html($row->total_sales_count) ?></td>
                            <td><?= esc_html($row->total_revenue) ?> บาท </td>
                            <td><strong><?= esc_html(number_format($row->total_earns, 2)) ?> บาท</strong></td>
                            <td><?=esc_html($user_bank_info[0]->bank_account_number)?> <?=esc_html($user_bank_info[0]->bank_name)?></td>
                            <td>
                                <button type='button' class='button'
                                    onclick="window.location.href='<?= $mark_as_paid_action_url ?>'">ทำสถานะว่าจ่ายแล้ว</button>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    ?>
                    <tr>
                        <td colspan="9">ไม่พบข้อมูล</td>
                    </tr>
                    <?php
                }

                if (!empty($wpdb->last_error)) {
                    echo '<div style="color:red;">SQL Error: ' . esc_html($wpdb->last_error) . '</div>';
                }
                ?>
            </tbody>
        </table>

        <h2>⭐ ยอด Commission ของสมาชิกที่จ่ายแล้ว</h2>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th>ชื่อในระบบ</th>
                    <th>Email</th>
                    <th>หมายเลข Ref</th>
                    <th>ยอดเข้าชม</th>
                    <th>ขายได้ (รายการ)</th>
                    <th>ยอดขาย</th>
                    <th>ยอด Commission ทั้งหมด</th>
                    <th>บัญชีปลายทาง</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($success_payments)) {
                    foreach ($success_payments as $row) {

                        $user_bank_info =  $wpdb->get_results($wpdb->prepare("SELECT bank_account_number, bank_name FROM {$wpdb->prefix}users_affiliate_info WHERE user_id = %d LIMIT 1", $row->ID));
                        ?>
                        <tr>
                            <td><?= esc_html($row->display_name) ?></td>
                            <td><?= esc_html($row->user_email) ?></td>
                            <td><?= esc_html($row->refCode) ?></td>
                            <td><?= esc_html($row->total_views) ?></td>
                            <td><?= esc_html($row->total_sales_count) ?></td>
                            <td><?= esc_html($row->total_revenue) ?> บาท </td>
                            <td><strong><?= esc_html(number_format($row->total_earns, 2)) ?> บาท</strong></td>
                            <td><?=esc_html($user_bank_info[0]->bank_account_number)?> <?=esc_html($user_bank_info[0]->bank_name)?></td>
                            <td>
                                <button type='button' class='button button-primary'
                                    onclick="window.location.href='<?= admin_url('admin.php?page=affiliate_report&refCode='.$row->refCode); ?>'">ออกรายงาน</button>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    ?>
                    <tr>
                        <td colspan="9">ไม่พบข้อมูล</td>
                    </tr>
                    <?php
                }

                if (!empty($wpdb->last_error)) {
                    echo '<div style="color:red;">SQL Error: ' . esc_html($wpdb->last_error) . '</div>';
                }
                ?>
            </tbody>
        </table>

        <p>Github Repository: <a href="https://github.com/sunny420x/woocommerce-affiliate"
                target="_blank">github.com/sunny420x/woocommerce-affiliate</a></p>
    </div>
    <?php
}

add_action('admin_menu', function() {
    add_submenu_page(
        null,
        'ออกรายงาน Affiliate',
        'Affiliate Report',
        'manage_options',
        'affiliate_report',
        'affiliate_report_page'
    );
});

function affiliate_report_page() {
    if(!isset($_GET['refCode'])) {
        wp_safe_redirect(admin_url("/wp-admin/admin.php?page=affiliate&error=refcode_not_found"));
        exit;
    }

    $refCode = sanitize_text_field($_GET['refCode']);
    $affiliate = new Affiliate();
    $affliate_records = $affiliate->getAffiliateByRefCode($refCode);

    if ( empty($affliate_records) ) {
        echo '<div class="wrap"><div class="notice notice-error"><p>ไม่พบข้อมูลสำหรับรหัสอ้างอิงนี้</p></div></div>';
        return;
    }
    ?>
    <div class="wrap" style="background: white; padding: 10px 30px 30px 30px;">
        <style>
            @media print {
                .no-print {
                    display: none !important;
                }
            }
        </style>
        <div class="card-admin">
            <div style="display: flex;">
                <img src="<?=get_option('affiliate_logo', '')?>" height="100%" alt="" style="margin: 20px;">
                <div style="margin: 10px 20px; float: right;">
                    <h1>รายงานรายได้จากระบบพันธมิตร - Affiliate Program Report</h1>
                    <h4>คุณ <?=$affliate_records[0]->display_name;?> รหัสพันธมิตร <?=$refCode?></h4>
                    <p>วันที่ออกรายงาน: <?=date('d-m-Y')?></p>
                </div>
            </div>
            <?php
            $total_sales_sum = 0;
            $total_earns_sum = 0;

            $user_bank_info =  $affiliate->wpdb->get_results($affiliate->wpdb->prepare("SELECT bank_account_number, bank_name FROM {$affiliate->wpdb->prefix}users_affiliate_info WHERE user_id = %d LIMIT 1", get_current_user_id()));
            ?>
            <table class="widefat fixed">
                <thead>
                    <tr>
                        <th>หมายเลขคำสั่งซื้อ</th>
                        <th>ยอดขายรวม</th>
                        <th>% Commission</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        foreach ($affliate_records as $row) {
                    ?>
                    <tr>
                        <td>#<?=$row->order_id?></td>
                        <td><?=$row->total_sales?> บาท</td>
                        <td><?=number_format($row->total_earns, 2)?> บาท</td>
                        <td><?php if($row->paid == 1) { echo "<span style='color: green;'>ชำระแล้ว</span>"; } else { echo "<span style='color: red;'>รอชำระ</span>"; }?></td>
                    </tr>
                    <?php
                            $total_sales_sum += $row->total_sales;
                            if($row->paid == 1) {
                                $total_earns_sum += $row->total_earns;
                            }
                        }
                    ?>
                    <tr>
                        <td colspan="3"><strong>รวมยอดขายทั้งหมด</strong></td>
                        <td><strong><?=$total_sales_sum?> บาท</strong></td>
                    </tr>
                    <tr>
                        <td colspan="3"><strong>รวมยอด Commission ทั้งหมด</strong></td>
                        <td><strong><?=$total_earns_sum?> บาท</strong></td>
                    </tr>
                </tbody>
            </table>
            <br>
            <h3>ทางบริษัทจะจ่ายค่าตอบแทน (Commission) ไปที่:</h3>
            <pre><?=$user_bank_info[0]->bank_name?> - <?=$user_bank_info[0]->bank_account_number?></pre>
            <br>
            <button class="button no-print" onclick="window.print()">พิมพ์รายงาน</button>
        </div>
    </div>
    <?php
}

add_filter('admin_title', function($admin_title, $title) {
    if (isset($_GET['page']) && $_GET['page'] === 'affiliate_report') {
        
        $ref_code = isset($_GET['refCode']) ? sanitize_text_field($_GET['refCode']) : 'ไม่ระบุ';
        
        $current_date = wp_date('d/m/Y');

        return "รายงานรายได้จากระบบพันธมิตรของรหัส {$ref_code} วันที่ {$current_date}";
    }

    return $admin_title;
}, 999, 2);

//Admin Setting
add_action('admin_init', 'affiliate_settings_init');

function affiliate_settings_init()
{
    register_setting('affiliate_settings_group', 'affiliate_commission');
    register_setting('affiliate_settings_group', 'affiliate_enable');
    register_setting('affiliate_settings_group', 'affiliate_logo');
}

function addTransaction($ref, $type, $product_id, $order_id = null)
{
    global $wpdb;

    $affiliate_transactions = $wpdb->prefix . 'affiliate_transactions';

    $wpdb->insert(
        $affiliate_transactions,
        [
            'refCode' => $ref,
            'type' => $type,
            'product_id' => $product_id,
            'order_id' => $order_id,
            'commission_percentage' => (float) get_option('affiliate_commission', 10),
            'created_at' => current_time('mysql'),
        ],
        [
            '%s', // refCode
            '%s', // type
            '%d', // product_id
            '%d', // order_id (ใช้ %d เพราะเป็นตัวเลข ID)
            '%s'  // created_at
        ]
    );
}

add_action('init', 'handle_global_ref_cookie');

function handle_global_ref_cookie()
{
    if (isset($_GET['ref'])) {
        $ref = sanitize_text_field(wp_unslash($_GET['ref']));

        if (!isset($_COOKIE['aff_global_ref'])) {
            setcookie(
                'aff_global_ref',
                $ref,
                time() + 86400,
                '/',
                $_SERVER['HTTP_HOST'],
                true,
                false
            );
            // บังคับให้ PHP มองเห็นทันทีในรอบการโหลดนี้
            $_COOKIE['aff_global_ref'] = $ref;
        } else {
            // ถ้าเป็นหน้าสินค้า ค่อยรัน Logic บันทึกการเข้าชม
            if (is_product()) {
                wcc_track_product_view();
            }
        }

    }

}

add_action('woocommerce_checkout_order_processed', 'affiliate_track_conversion', 10, 3);

function affiliate_track_conversion($order_id, $posted_data, $order)
{
    // วนลูปสินค้าใน Order ทั้งหมด
    foreach ($order->get_items() as $item_id => $item) {
        $product_id = $item->get_product_id();
        $cookie_name = 'aff_global_ref';

        // เช็คว่าคนซื้อมี Cookie 'aff_view_[ID]' ของสินค้าชิ้นนี้ไหม
        if (isset($_COOKIE[$cookie_name])) {
            $ref = sanitize_text_field($_COOKIE[$cookie_name]);

            // บันทึกธุรกรรมลงตาราง (เปลี่ยน Type เป็น 'sale' หรือ 'conversion')
            // เราส่ง $order_id ไปด้วยเพื่อให้ตรวจสอบย้อนหลังได้
            addTransaction($ref, 'sale', $product_id, $order_id);

            // (Optional) ลบ Cookie ทิ้งทันทีที่ขายได้ เพื่อป้องกันการนับยอดซ้ำหากเขากดสั่งอีกรอบโดยไม่ผ่านลิงก์เดิม
            setcookie($cookie_name, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
        }
    }
}

function wcc_track_product_view()
{
    $ref = '';

    if (isset($_COOKIE['aff_global_ref'])) {
        $ref = $_COOKIE['aff_global_ref'];
    } else {
        if (isset($_GET['ref'])) {
            $ref = sanitize_text_field(wp_unslash($_GET['ref']));
        }
    }


    if (empty($ref))
        return;

    $product_id = get_the_ID();
    $cookie_name = 'aff_view_' . $product_id;

    // ถ้ายังไม่มี Cookie ของสินค้าชิ้นนี้ (กันนับซ้ำใน 1 ชม.)
    if (!isset($_COOKIE[$cookie_name])) {

        addTransaction($ref, 'view', $product_id);

        // ฝัง Cookie สินค้าไว้ (เก็บค่า $ref ไว้ข้างในด้วยเพื่อใช้ตอน Sale)
        setcookie($cookie_name, $ref, time() + 3600, COOKIEPATH, COOKIE_DOMAIN);
    }
}

// ลงทะเบียน Endpoint ใหม่
add_action('init', 'affiliate_endpoint');
function affiliate_endpoint()
{
    add_rewrite_endpoint('affiliate-program', EP_PAGES);
}

// เพิ่มเมนูเข้าไปในรายการ My Account (เรียงต่อจาก Dashboard)
add_filter('woocommerce_account_menu_items', 'affiliate_menu_item');
function affiliate_menu_item($items)
{
    // แทรกเมนูใหม่เข้าไป
    if(esc_attr(get_option('affiliate_enable', 'yes')) == 'yes') {        
        $new_items = array('affiliate-program' => 'โปรแกรมพันธมิตร (Affiliate)');
        return array_slice($items, 0, 1, true) + $new_items + array_slice($items, 1, count($items), true);
    } else {
        return $items;
    }
}

// เนื้อหาภายในหน้า Affiliate
add_action('woocommerce_account_affiliate-program_endpoint', 'wcc_affiliate_content');
function wcc_affiliate_content()
{
    global $wpdb;
    $user_id = get_current_user_id();

    $ref_code = $wpdb->get_var($wpdb->prepare(
        "SELECT refCode FROM {$wpdb->prefix}users WHERE ID = %d",
        $user_id
    ));

    if (isset($_POST['register_affiliate'])) {
        check_admin_referer('wcc_aff_reg');

        // เจนรหัสสุ่ม 8 หลัก
        $new_ref = strtoupper(substr(md5($user_id . time()), 0, 8));

        // 2. อัปเดตลงตาราง users ใน field refCode
        $updated = $wpdb->update(
            "{$wpdb->prefix}users",
            array('refCode' => $new_ref), // Field
            array('ID' => $user_id),      // เงื่อนไข
            array('%s'),                  // Format ของค่าที่ใส่
            array('%d')                   // Format ของเงื่อนไข
        );

        if ($updated !== false) {
            $ref_code = $new_ref; // อัปเดตตัวแปรไว้โชว์ในหน้าเว็บทันที
            echo '<div class="woocommerce-message">ยินดีด้วย! คุณสมัครเป็นตัวแทนสำเร็จแล้ว</div>';
        }
    }

    // --- ส่วนแสดงผล UI ---
    echo '<h1 style="font-size: 24px;">🤝🏻 ระบบพันธมิตร | Affliate Program</h1>';

    if(esc_attr(get_option('affiliate_enable', 'yes')) == 'yes') {        
        if ($ref_code) {
            ?>
            <p>รหัสแนะนำของคุณคือ: <strong><?= $esc_ref = esc_html($ref_code) ?></strong></p>
            <p>ลิงก์สำหรับแนะนำ:<br>
                <code style="display:block; padding:10px; background:#f0f0f0;"><?= home_url('/?ref=' . $esc_ref) ?></code>
            </p>
            <p>*เมื่อมีคนเข้าชมผ่านลิงก์นี้และซื้อสินค้า ระบบจะคิดค่า Commission ทันที</p>

            <h1 style="font-size: 24px;">💵 สรุปยอด</h1>
            <table>
                <tr>
                    <th>ขายได้ทั้งหมด (ชิ้น)</th>
                    <th>ยอดขายทั้งหมด</th>
                    <th>ยอด Commission</th>
                </tr>
                <?php
                global $wpdb;

                $affiliate_users = $wpdb->prefix . 'users';
                $affiliate_transactions = $wpdb->prefix . 'affiliate_transactions';
                $order_stats_table = $wpdb->prefix . 'wc_order_stats';

                $transactions = $wpdb->get_results($wpdb->prepare("
                SELECT 
                    COUNT(CASE WHEN t.type = 'view' THEN 1 END) AS total_views,
                    COUNT(CASE WHEN t.type = 'sale' THEN 1 END) AS total_sales_count,
                    SUM(CASE 
                        WHEN t.type = 'sale' AND os.total_sales IS NOT NULL 
                        THEN os.total_sales 
                        ELSE 0 
                    END) AS total_revenue,
                    SUM(CASE 
                        WHEN t.type = 'sale' AND os.total_sales IS NOT NULL 
                        THEN os.total_sales * (t.commission_percentage / 100)
                        ELSE 0 
                    END) AS total_earns
                FROM {$affiliate_users} AS u
                LEFT JOIN {$affiliate_transactions} AS t
                    ON u.refCode = t.refCode
                LEFT JOIN {$order_stats_table} AS os 
                    ON t.order_id = os.order_id
                WHERE u.ID = %d AND t.paid = 0 
                GROUP BY u.ID, u.display_name, u.user_email, u.refCode
                ORDER BY u.ID DESC", $user_id));

                foreach ($transactions as $tx) {
                    ?>
                    <tr>
                        <td><?= $tx->total_sales_count; ?> รายการ</td>
                        <td><?= number_format($tx->total_revenue, 2); ?> บาท</td>
                        <td><strong><?= number_format($tx->total_earns, 2); ?> บาท</strong></td>
                    </tr>
                    <?php
                }
                ?>
            </table>
            <h1 style="font-size: 24px;">ตั้งค่าระบบพันธมิตร</h1>
            <?php
            global $wpdb;
            $user_id = get_current_user_id();
            $table_info = $wpdb->prefix . 'users_affiliate_info';

            // --- ส่วนประมวลผลการบันทึก ---
            if (isset($_POST['save_affiliate_info'])) {
                $account_number = sanitize_text_field($_POST['aff_account_number']);
                $bank_name = sanitize_text_field($_POST['aff_bank_name']);

                // เช็คก่อนว่ามีข้อมูลของ User คนนี้ในตารางหรือยัง
                $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_info WHERE user_id = %d", $user_id));

                if ($exists) {
                    // มีอยู่แล้วให้ Update
                    $wpdb->update(
                        $table_info,
                        array(
                            'bank_account_number' => $account_number,
                            'bank_name' => $bank_name,
                            'updated_at' => current_time('mysql')
                        ),
                        array('user_id' => $user_id),
                        array('%s', '%s', '%s'),
                        array('%d')
                    );
                } else {
                    // ยังไม่มีให้ Insert
                    $wpdb->insert(
                        $table_info,
                        array(
                            'user_id' => $user_id,
                            'bank_account_number' => $account_number,
                            'bank_name' => $bank_name,
                            'updated_at' => current_time('mysql')
                        ),
                        array('%d', '%s', '%s', '%s')
                    );
                }
                echo '<div class="woocommerce-message">บันทึกข้อมูลบัญชีรับเงินเรียบร้อยแล้ว</div>';
            }

            // --- ดึงข้อมูลปัจจุบันมาแสดงในฟอร์ม ---
            $user_info = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_info WHERE user_id = %d", $user_id));
            ?>

            <form action="" method="post" style="margin-top: 20px;">
                <div class="form-row" style="margin-bottom: 15px;">
                    <label>หมายเลขบัญชีธนาคาร:</label>
                    <input type="text" name="aff_account_number" value="<?= esc_attr($user_info->bank_account_number ?? ''); ?>"
                        placeholder="ระบุเลขบัญชี" required style="width: 100%;" />
                </div>

                <div class="form-row" style="margin-bottom: 15px;">
                    <label>ธนาคาร:</label>
                    <select name="aff_bank_name" style="width: 100%;">
                        <?php
                        $banks = ["ธนาคารกรุงเทพ", "ธนาคารกสิกรไทย", "ธนาคารไทยพาณิชย์", "ธนาคารกรุงไทย", "ธนาคารกรุงศรีอยุธยา", "ธนาคารทหารไทยธนชาต", "ธนาคารยูโอบี", "ธนาคารออมสิน"];
                        foreach ($banks as $bank):
                            ?>
                            <option value="<?= $bank ?>" <?php selected($user_info->bank_name ?? '', $bank); ?>><?= $bank ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" name="save_affiliate_info" class="button">บันทึกข้อมูลบัญชี</button>
            </form>
            <?php
        } else {
            ?>
            <h2>คุณยังไม่ได้สมัครเป็นตัวแทนแนะนำสินค้า สมัครเพื่อรับลิงก์พิเศษและเริ่มสะสมยอดขายได้ทันที!</h2>
            <form method="post">
                <?php wp_nonce_field('wcc_aff_reg'); ?>
                <button type="submit" name="register_affiliate" class="button">สมัครเป็นพันธมิตรตอนนี้</button>
            </form>
            <?php
        }
    } else {
    ?>
    <h2>ปิดใช้งานชั่วคราว</h2>
    <?php
    }
}

//User Setting
add_action('admin_init', 'user_affiliate_settings_init');

function user_affiliate_settings_init()
{
    register_setting('user_affiliate_settings_group', 'affiliate_payment_account_number');
    register_setting('user_affiliate_settings_group', 'affiliate_payment_account_bank');
}

add_action( 'woocommerce_single_product_summary', 'inject_affliate_share_buttons', 35 );

function inject_affliate_share_buttons() {
    global $product, $wpdb;
    
    // 1. ถ้าไม่ Login ไม่ต้องโชว์ (หรือจะโชว์แบบไม่มี refCode ก็ได้แล้วแต่เพื่อน)
    if ( ! is_user_logged_in() ) return;

    $user_id = get_current_user_id();

    // 2. ดึง refCode มาตรงๆ (get_var คืนค่าเป็น string)
    $ref_code = $wpdb->get_var($wpdb->prepare(
        "SELECT refCode FROM {$wpdb->prefix}users WHERE ID = %d",
        $user_id
    ));

    // ถ้าไม่มี refCode ให้เป็นค่าว่าง
    $affiliate_param = $ref_code ? "?ref=" . $ref_code : "";

    // 3. ประกอบ URL ก่อนแล้วค่อย urlencode ทีเดียว
    $full_url      = get_permalink() . $affiliate_param;
    $encoded_url   = urlencode( $full_url );
    
    $product_title = urlencode( get_the_title() );
    $product_img   = urlencode( wp_get_attachment_url( get_post_thumbnail_id() ) );
    ?>
    <div class="affiliate_element">
        <strong>⭐ แชร์สินค้าชิ้นนี้เพื่อรับ Commission <?=get_option('affiliate_commission');?>% เมื่อมีการซื้อสินค้าจากการแชร์</strong>
        <div class="social-icon">
            <label style="font-weight: bold; margin-right: 10px;">Share : </label>
            <div class="social-share" style="display: inline-block;">
                <!-- Facebook -->
                <a href="https://www.facebook.com/sharer.php?u=<?php echo $encoded_url; ?>" title="Facebook" class="share-facebook" target="_blank" style="margin-right: 10px;">
                    <i class="fa fa-facebook"></i>
                </a>
    
                <!-- Twitter -->
                <a href="https://twitter.com/intent/tweet?url=<?php echo $encoded_url; ?>&text=<?php echo $product_title; ?>" title="Twitter" class="share-twitter" target="_blank" style="margin-right: 10px;">
                    <i class="fa fa-twitter"></i>
                </a>
    
                <!-- Line -->
                <a href="https://social-plugins.line.me/lineit/share?url=<?php echo $encoded_url; ?>" title="Line" class="share-line" target="_blank" style="margin-right: 10px;">
                    <i class="fa fa-comment"></i>
                </a>

                <a href="javascript:void(0);" 
                class="share-clipboard" 
                title="Copy Link" 
                id="copy-affiliate-link"
                data-url="<?php echo esc_url($full_url); ?>" 
                style="margin: 0 10px 0 0; cursor: pointer; position: relative; top: 12px;">
                    <i style="padding: 0px 3px; margin-top: 10px;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 800" fill="#fff" style="margin-top: 2px;"><path d="M360 160L280 160C266.7 160 256 149.3 256 136C256 122.7 266.7 112 280 112L360 112C373.3 112 384 122.7 384 136C384 149.3 373.3 160 360 160zM360 208C397.1 208 427.6 180 431.6 144L448 144C456.8 144 464 151.2 464 160L464 512C464 520.8 456.8 528 448 528L192 528C183.2 528 176 520.8 176 512L176 160C176 151.2 183.2 144 192 144L208.4 144C212.4 180 242.9 208 280 208L360 208zM419.9 96C407 76.7 385 64 360 64L280 64C255 64 233 76.7 220.1 96L192 96C156.7 96 128 124.7 128 160L128 512C128 547.3 156.7 576 192 576L448 576C483.3 576 512 547.3 512 512L512 160C512 124.7 483.3 96 448 96L419.9 96z"/></svg>
                    </i>
                    <span id="copy-status" style="width: max-content; display:none; position:absolute; top:-30px; left:0; background:#000; color:#fff; padding:2px 5px; font-size:10px; border-radius:3px;">คัดลอกลิงค์แล้ว !</span>
                </a>
            </div>
        </div>
        <script>
        document.getElementById('copy-affiliate-link').addEventListener('click', function() {
            var copyText = this.getAttribute('data-url');
            
            navigator.clipboard.writeText(copyText).then(function() {
                // โชว์ป้ายว่า Copied!
                var status = document.getElementById('copy-status');
                status.style.display = 'block';
                
                setTimeout(function() {
                    status.style.display = 'none';
                }, 2000);
                
            }).catch(function(err) {
                console.error('ไม่สามารถคัดลอกลิงก์ได้: ', err);
            });
        });
        </script>
    </div>

    <style>
        .affiliate_element {
            background: #f8f8f8;
            border-radius: 10px;
            padding: 20px;
        }
    </style>
    <?php
}