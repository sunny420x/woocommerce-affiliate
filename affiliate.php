<?php
/**
 * Plugin Name: World Chemical Affiliate Marketing
 * Description: ระบบ Affiliate Marketing สำหรับ World Chemical
 * Version: 1.0
 * Author: Jirakit Pawnsakunrungrot
 * Author URI: https://www.linkedin.com/in/sunny-jirakit
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

function affiliate_admin_menu_users()
{
    add_submenu_page(
        'affiliate_admin_management',                        // Parent menu slug (from add_menu_page)
        'จัดการผู้ใช้ Affiliate Program | World Chemical',       // Submenu page title
        'จัดการผู้ใช้',                                          // Submenu menu title
        'manage_options',                                    // Capability required
        'affiliate_users',                                   // Submenu slug
        'affiliate_admin_management_users'                   // Function to render the submenu content
    );
}
//Add Menu to Wordpress Admin
add_action('admin_menu', 'affiliate_admin_menu');
add_action('admin_menu', 'affiliate_admin_menu_users');

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
    $charset_collate = $wpdb->get_charset_collate();
    
    $new_table = $wpdb->prefix . 'users_affiliate_info';
    $user_table = $wpdb->prefix . 'users';

    $create_table_query = "CREATE TABLE IF NOT EXISTS $new_table (
        id int(11) NOT NULL AUTO_INCREMENT,
        user_id int(11) NOT NULL,
        bank_account_number varchar(20) NOT NULL,
        bank_name varchar(50) NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $create_table_query );

    // เช็คว่ามีคอลัมน์ refCode หรือยัง เพื่อป้องกัน Error ตอนรันซ้ำ
    $row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = '".DB_NAME."' 
        AND TABLE_NAME = '$user_table' 
        AND COLUMN_NAME = 'refCode'");

    if (empty($row)) {
        $wpdb->query("ALTER TABLE $user_table ADD `refCode` VARCHAR(50) DEFAULT NULL, ADD `score` INT(12) DEFAULT 0;");
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

function get_all_users_table()
{
    global $wpdb;

    $affiliate_users = $wpdb->prefix . 'users';
    $affiliate_transactions = $wpdb->prefix . 'affiliate_transactions';
    $order_stats_table = $wpdb->prefix . 'wc_order_stats';

$affiliate_query = "
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
    FROM {$affiliate_users} AS u
    LEFT JOIN {$affiliate_transactions} AS t
        ON u.refCode = t.refCode
    LEFT JOIN {$order_stats_table} AS os 
        ON t.order_id = os.order_id 
    ";

    $affiliate_query_group_and_order = " GROUP BY u.ID, u.display_name, u.user_email, u.refCode ORDER BY u.ID DESC";

    $waiting_for_payments = $wpdb->get_results(
        $affiliate_query . " WHERE u.refCode IS NOT NULL AND u.refCode != '' AND t.paid = 0 " . $affiliate_query_group_and_order
    );

    $success_payments = $wpdb->get_results(
        $affiliate_query . " WHERE u.refCode IS NOT NULL AND u.refCode != '' AND t.paid = 1 " . $affiliate_query_group_and_order
    );

    // WHERE MONTH(t.created_at) = MONTH(CURDATE())
    // AND YEAR(t.created_at) = YEAR(CURDATE())

    // --- ส่วนการโชว์ข้อความ (วางไว้ก่อนเริ่มวาดตาราง) ---
    // if (isset($_GET['status']) && $_GET['status'] == 'paid_success') {
    //     echo '<div class="updated"><p>จ่ายเงินเรียบร้อยแล้ว!</p></div>';
    // }
    ?>

    <div class='card-admin'>
        <h1>🤝🏻 ระบบการตลาดแบบพันธมิตรสำหรับ WooCommerce | Affiliate Program</h1>
        <p>ระบบ Affiliate กระตุ้นการขายบนเว็บไซต์ โดยการให้เปอร์เซ็น Affiliate Partner เป็นจำนวน
            <?= get_option('affiliate_commission', 10); ?>% ของยอดขายสินค้า</p>
        <form action="options.php" method="post">
            <?php
            settings_fields('affiliate_settings_group');
            ?>
            <label for="affiliate_commission">% Commission: </label><input type="number" name="affiliate_commission"
                value="<?= esc_attr(get_option('affiliate_commission', 10)); ?>" />
            <p>* การอัพเดท % Commission จะไม่มีผลย้อนหลังกับข้อมูลการขายเดิมในระบบ แต่จะมีผลกับข้อมูลการขายใหม่ที่จะถูกเพิ่มเข้ามาหลังจากอัพเดท</p>
            <?php submit_button('บันทึกการเปลี่ยนแปลง'); ?>
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
                                    onclick="window.location.href='<?= admin_url('admin.php?page=affiliate_report&id=' . absint($row->id)); ?>'">ออกรายงาน</button>
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
                            <td><strong><?= esc_html(floor($row->total_earns)) ?> บาท</strong></td>
                            <td><?=esc_html($user_bank_info[0]->bank_account_number)?> <?=esc_html($user_bank_info[0]->bank_name)?></td>
                            <td>
                                <button type='button' class='button'
                                    onclick="window.location.href='<?= admin_url('admin.php?page=affiliate_report&id=' . absint($row->id)); ?>'">ออกรายงาน</button>
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

//Admin Setting
add_action('admin_init', 'affiliate_settings_init');

function affiliate_settings_init()
{
    register_setting('affiliate_settings_group', 'affiliate_commission');
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

add_action('init', 'wcc_handle_global_ref_cookie');

function wcc_handle_global_ref_cookie()
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
add_action('init', 'wcc_affiliate_endpoint');
function wcc_affiliate_endpoint()
{
    add_rewrite_endpoint('affiliate-program', EP_PAGES);
}

// เพิ่มเมนูเข้าไปในรายการ My Account (เรียงต่อจาก Dashboard)
add_filter('woocommerce_account_menu_items', 'wcc_affiliate_menu_item');
function wcc_affiliate_menu_item($items)
{
    // แทรกเมนูใหม่เข้าไป
    $new_items = array('affiliate-program' => 'โปรแกรมพันธมิตร (Affiliate)');
    return array_slice($items, 0, 1, true) + $new_items + array_slice($items, 1, count($items), true);
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
}

//User Setting
add_action('admin_init', 'user_affiliate_settings_init');

function user_affiliate_settings_init()
{
    register_setting('user_affiliate_settings_group', 'affiliate_payment_account_number');
    register_setting('user_affiliate_settings_group', 'affiliate_payment_account_bank');
}