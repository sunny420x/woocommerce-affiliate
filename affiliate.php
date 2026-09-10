<?php
/**
 * Plugin Name: WooCommerce Affiliate Marketing
 * Description: ระบบ Affiliate Marketing สำหรับ World Chemical
 * Author: Jirakit Pawnsakunrungrot
 * Author URI: https://www.linkedin.com/in/sunny-jirakit
 * Plugin URI: https://github.com/sunny420x/woocommerce-affiliate
 * GitHub Plugin URI: https://github.com/sunny420x/woocommerce-affiliate
 * Primary Branch: master
 * Version: 1.0.0
*/

//Deny access from URL.
if (!defined('ABSPATH'))
    exit;

require_once( plugin_dir_path( __FILE__ ) . 'modules/line.php' );
require_once( plugin_dir_path( __FILE__ ) . 'modules/mail.php' );
require_once( plugin_dir_path( __FILE__ ) . 'modules/reports.php' );

function afiliate_enqueue_assets()
{
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

// Function to add the menu page
function affiliate_admin_menu()
{
    add_menu_page(
        'ระบบ Affiliate',    // Page title
        'Affiliate Program',                     // Menu title
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
    echo get_all_users_table();
}

register_activation_hook( __FILE__, 'my_plugin_install' );

function my_plugin_install() {
    global $wpdb;
    // $charset_collate = $wpdb->get_charset_collate();
    $charset_collate = "DEFAULT CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci";
    
    $users_affiliate_info = $wpdb->prefix . 'users_affiliate_info';
    $affiliate_transactions = $wpdb->prefix . 'affiliate_transactions';
    $user_table = $wpdb->prefix . 'users';

    $create_info_table_query = "CREATE TABLE IF NOT EXISTS $users_affiliate_info (
        id int(11) NOT NULL AUTO_INCREMENT,
        user_id int(11) NOT NULL,
        bank_account_number varchar(20) NOT NULL,
        bank_name varchar(50) NOT NULL,
        verified INT(1) NOT NULL DEFAULT 0,
        suspended INT(1) NOT NULL DEFAULT 0,
        social_media_01 varchar(200) DEFAULT NULL,
        social_media_02 varchar(200) DEFAULT NULL,
        social_media_03 varchar(200) DEFAULT NULL,
        social_media_04 varchar(200) DEFAULT NULL,
        social_media_01_type varchar(50) DEFAULT NULL,
        social_media_02_type varchar(50) DEFAULT NULL,
        social_media_03_type varchar(50) DEFAULT NULL,
        social_media_04_type varchar(50) DEFAULT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    $create_transaction_table_query = "CREATE TABLE IF NOT EXISTS $affiliate_transactions (
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

    $create_request_payments_table_query = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}affiliate_request_payments (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        amount INT(11) NOT NULL,
        order_id varchar(200) NOT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $create_info_table_query );
    dbDelta( $create_transaction_table_query );
    dbDelta( $create_request_payments_table_query );

    // เช็คว่ามีคอลัมน์ refCode หรือยัง เพื่อป้องกัน Error ตอนรันซ้ำ
    $row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = '".DB_NAME."' 
        AND TABLE_NAME = '$user_table' 
        AND COLUMN_NAME = 'refCode'");

    if (empty($row)) {
        $wpdb->query("ALTER TABLE $user_table ADD `refCode` VARCHAR(50) DEFAULT NULL");
    }
}

add_action('admin_init', 'handle_mark_as_paid');

function handle_mark_as_paid()
{
    if (isset($_GET['action']) && $_GET['action'] == 'mark_paid') {
        global $wpdb;

        // เช็คความปลอดภัย (ถ้าไม่ผ่านมันจะแค่เด้งออก ไม่ Critical Error)
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'mark_paid_nonce')) {
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
        $query = "SELECT u.ID, t.product_id, t.commission_percentage, t.paid, t.order_id, t.refCode, os.status, u.user_email, u.display_name 
        FROM {$this->tables['transactions']} as t 
        JOIN {$this->tables['users']} as u ON u.refCode = t.refCode 
        LEFT JOIN {$this->tables['order_stats']} AS os 
        ON t.order_id = os.order_id AND (os.status = 'completed' OR os.status = 'wc-completed')
        {$query_option}
        GROUP BY t.product_id, t.commission_percentage 
        ORDER BY t.id DESC";

        $results = $this->wpdb->get_results($query);

        $transactions = [];
        foreach ($results as $item) {
            $product = wc_get_product($item->product_id);
            $order = wc_get_order($item->order_id);
            $quantity = 0;

            if ($order) {
                foreach ($order->get_items() as $item_id => $order_item) {
                    if ($order_item->get_product_id() == $item->product_id || $order_item->get_variation_id() == $item->product_id) {
                        $quantity += $order_item->get_quantity();
                    }
                }
            }

            $product_price = (float) $product->get_price() * $quantity;
            $commission_value = ((float) $item->commission_percentage / 100) * $product_price;

            if (!isset($transactions[$item->order_id])) {
                $transactions[$item->order_id] = (object) [
                    "ID" => $item->ID,
                    "display_name" => $item->display_name,
                    "user_email" => $item->user_email,
                    "order_id" => $item->order_id,
                    "quantity" => 0,
                    "status" => $item->status,
                    "total_sold_sum" => 0,
                    "total_earns_sum" => 0,
                    "commission_percentage" => $item->commission_percentage,
                    "paid" => $item->paid,
                ];
            }

            $transactions[$item->order_id]->quantity += $quantity;
            $transactions[$item->order_id]->total_sold_sum += $product_price;
            $transactions[$item->order_id]->total_earns_sum += $commission_value;
        }
        return $transactions;
    }

    public function getAffiliateByRefCode($refCode) {
        $query = $this->wpdb->prepare("SELECT 
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
                ON t.order_id = os.order_id AND (os.status = 'completed' OR os.status = 'wc-completed') 
            WHERE u.refCode = %s 
            ORDER BY u.ID DESC
        ", $refCode);

        return $this->wpdb->get_results($query);
    }

    public function getAffiliateChart($query_option = '') {
        $query = "SELECT 
                t.created_at,
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
                ON t.order_id = os.order_id AND (os.status = 'completed' OR os.status = 'wc-completed') 
            {$query_option} 
            GROUP BY DATE(t.created_at)
            ORDER BY t.id
        ";

        return $this->wpdb->get_results($query);
    }
}


function get_all_users_table() {
    global $wpdb;
    $affiliate = new Affiliate();

    if(isset($_GET['from']) && !empty($_GET['from']) && isset($_GET['to']) && !empty($_GET['to'])) {
        $from = sanitize_text_field($_GET['from']);
        $to = sanitize_text_field($_GET['to']);

        $waiting_for_payments = $affiliate->getAffiliate("WHERE u.refCode IS NOT NULL AND u.refCode != '' AND t.paid = 0 AND t.created_at BETWEEN '{$from}' AND '{$to}' ");
        $success_payments = $affiliate->getAffiliate("WHERE u.refCode IS NOT NULL AND u.refCode != '' AND t.paid = 1 AND t.created_at BETWEEN '{$from}' AND '{$to}' ");
        $affiliate_chart = $affiliate->getAffiliate("WHERE t.created_at BETWEEN '{$from}' AND '{$to}' ");
    } else {
        $waiting_for_payments = $affiliate->getAffiliate("WHERE u.refCode IS NOT NULL AND u.refCode != '' AND t.paid = 0 ");
        $success_payments = $affiliate->getAffiliate("WHERE u.refCode IS NOT NULL AND u.refCode != '' AND t.paid = 1 ");
        $affiliate_chart = $affiliate->getAffiliate();
    }

    $labels = [];
    $revenue_data = [];

    foreach ($affiliate_chart as $row) {
        $date_label = date('d M', strtotime($row->created_at));
        
        $labels[] = $date_label;
        $revenue_data[] = (float)$row->total_sold_sum;
    }
    ?>
    <style>
        ul.popup_profile_list {
            margin: 0;
        }
        ul.popup_profile_list li {
            padding: 10px 20px;
            font-size: 14px;
            background: #f8f8f8;
            color: #111;
            transition: .2s ease-in-out;
            margin: 0;
        }
        ul.popup_profile_list li:hover {
            background: #fff;
            cursor: pointer;
        }
        .leftside {
            width: 350px;
            background: #f8f8f8;
            height: max-content;
        }
        .leftside h1 {
            background: #009FE3;
            color: #fff;
            font-size: 16px;
            padding: 10px 20px;
            margin: 0;
        }
        .leftside a {
            padding: 10px 20px;
            font-size: 14px;
            background: #f8f8f8;
            color: #000;
            transition: .2s ease-in-out;
            display: block;
            width: 100%;
            text-decoration: none;
        }
        .leftside a.active {
            background: #fff;
        }
        .leftside a:hover {
            background: #fff;
            cursor: pointer;
        }
        .container {
            width: 1200px;
            background: #fff;
        }
        .container h1 {
            background: #555;
            color: #fff;
            font-size: 16px;
            padding: 10px 20px;
            margin: 0;
        }
        .white-label-zone {
            width: calc(100% + 20px);
            height: auto;
            background: #fff;
            display: flex;
            margin: 0 0 0 -20px;
        }
        .white-label-zone {
            h1 {
                padding: 0 20px;
            }
            p {
                padding: 0 20px;
            }
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
    <div class="white-label-zone no-print">
        <!-- <span style="padding: 40px 10px 40px 40px;float: left;font-size: 60px;">🤝</span> -->
        <img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'plugin-logo.jpg' ); ?>" " alt="Plugin Logo" style="width: 125px; height: auto; float: left; margin: 40px 10px 40px 20px; border-radius: 20px;">
        <div style="padding: 20px 0;">
            <h1>WooCommerce Affiliate System</h1>
            <p>ระบบพันธมิตรสำหรับ WooCommerce รองรับการสร้างลิงค์พันธมิตร เงื่อนไขการให้ยอด Commission หลากหลาย การติดตามยอดขาย สร้างรายงาน ยืนยันตัวตน และการจ่ายค่าตอบแทนให้กับพันธมิตร</p>
            <p>
            <strong>Github Repository:</strong> <a href="https://github.com/sunny420x/woocommerce-affiliate" target="_blank">https://github.com/sunny420x/woocommerce-affiliate</a><br>
            <!-- <strong>Documentation:</strong> <a href="https://github.com/sunny420x/woocommerce-affiliate/wiki" target="_blank">https://github.com/sunny420x/woocommerce-affiliate/wiki</a><br> -->
            <strong>Support:</strong> <a href="https://github.com/sunny420x/woocommerce-affiliate/issues" target="_blank">https://github.com/sunny420x/woocommerce-affiliate/issues</a><br>
            <strong>Developer:</strong> <a href="https://sunny420x.com" target="_blank">https://sunny420x.com</a>
            </p>
        </div>
    </div>
    <div class="wrap">
        <?php
        if(isset($_GET['status'])) {
        ?>
        <div class="notice notice-<?=$_GET['status']?> is-dismissible">
            <?php
            if($_GET['status'] == 'success') {
            ?>
                <p>บันทึกข้อมูลแล้ว</p>
            <?php
            } else {
            ?>
                <p>เกิดข้อผิดพลาด</p>
            <?php
            }
            ?>
        </div>
        <?php
        }
        ?>
        <div style="display: flex;">
            <div class="leftside no-print">
                <h1>WooCommerce Affiliate System</h1>
                <a href="admin.php?page=affiliate&option=affiliate_users" <?php if(isset($_GET['option']) && $_GET['option'] == "affiliate_users") { echo "class='active'"; } ?>>🤝 พันธมิตรในระบบ</a>
                <a href="admin.php?page=affiliate&option=affiliate_withdrawals" <?php if(isset($_GET['option']) && $_GET['option'] == "affiliate_withdrawals") { echo "class='active'"; } ?>>💸 คำขอถอนเงิน</a>
                <h1>รายงาน</h1>
                <a href="admin.php?page=affiliate&option=reports" <?php if(isset($_GET['option']) && $_GET['option'] == "reports") { echo "class='active'"; } ?>>📋 ออกรายงานสรุป</a>
                <a href="admin.php?page=affiliate&option=statistic" <?php if(isset($_GET['option']) && $_GET['option'] == "statistic") { echo "class='active'"; } ?>>📊 สถิติการใช้งาน</a>
                <h1>ตั้งค่าระบบ</h1>
                <a href="admin.php?page=affiliate&option=affiliate_commission_settings" <?php if(isset($_GET['option']) && $_GET['option'] == "affiliate_commission_settings") { echo "class='active'"; } ?>>📦 Commission ตามประเภทสินค้า</a>
                <a href="admin.php?page=affiliate&option=affiliate_tiers_commission_settings" <?php if(isset($_GET['option']) && $_GET['option'] == "affiliate_tiers_commission_settings") { echo "class='active'"; } ?>>🪜 Commission แบบขั้นบันใด</a>
                <a href="admin.php?page=affiliate&option=pages_content" <?php if(isset($_GET['option']) && $_GET['option'] == "pages_content") { echo "class='active'"; } ?>>📝 เนื้อหาที่แสดงในระบบ</a>
                <a href="admin.php?page=affiliate&option=emails_content" <?php if(isset($_GET['option']) && $_GET['option'] == "emails_content") { echo "class='active'"; } ?>>📝 อีเมล์</a>
                <a href="admin.php?page=affiliate&option=affiliate_settings" <?php if(isset($_GET['option']) && $_GET['option'] == "affiliate_settings") { echo "class='active'"; } ?>>⚙️ ตั้งค่าระบบ</a>
            </div>
            <div class="container">
                <?php
                if(isset($_GET['option']) && $_GET['option'] == "statistic") {
                    require_once __DIR__ . '/pages/statistic.php';
                } elseif(isset($_GET['option']) && $_GET['option'] == "reports") {
                    require_once __DIR__ . '/pages/reports.php';
                } elseif(isset($_GET['option']) && $_GET['option'] == "pages_content") {
                    require_once __DIR__ . '/pages/pages_content.php';
                } elseif(isset($_GET['option']) && $_GET['option'] == "emails_content") {
                    require_once __DIR__ . '/pages/emails_content.php';
                } elseif(isset($_GET['option']) && $_GET['option'] == "affiliate_settings") {
                    require_once __DIR__ . '/pages/affiliate_settings.php';
                } elseif(isset($_GET['option']) && $_GET['option'] == "affiliate_commission_settings") {
                    require_once __DIR__ . '/pages/affiliate_commission_settings.php';
                } elseif(isset($_GET['option']) && $_GET['option'] == "affiliate_tiers_commission_settings") {
                    require_once __DIR__ . '/pages/affiliate_tiers_commission_settings.php';
                } elseif(isset($_GET['option']) && $_GET['option'] == "affiliate_users") {
                    require_once __DIR__ . '/pages/affiliate_users.php';
                } elseif(isset($_GET['option']) && $_GET['option'] == "affiliate_withdrawals") {
                    require_once __DIR__ . '/pages/affiliate_withdrawals.php';
                } else {
                ?>
                <h1>WooCommerce Affiliate</h1>
                <div style="padding: 0 25px 25px 25px;">
                    <h2>ระบบนี้คืออะไร ?</h2>
                    <p>ระบบ WooCommerce Affiliate
                        คือระบบที่ออกแบบมาสำหรับรองรับการทำการตลาดแบบพันธมิตร หรือที่เรียกว่าระบบ Affiliate ที่ปลั้กอินนี้สามารถกำหนด % Commission เริ่มต้น และจากประเภทสินค้าได้ สามารถออกรายงานได้
                        ในฝั่งผู้ใช้งานที่หน้า /my-account/ จะมีเมนูใหม่ให้สามารถสมัครเป็นพันธมิตรกับร้านค้าได้ โดยภายในจะแสดงยอด Commission ที่ทำได้และสามารถกรอกช่องทางการรับเงินได้
                    </p>
                    <h2>วิธีการติดตั้ง</h2>
                    <p>
                        สามารถติดตั้งปลั้กอินนี้ได้โดยการดาวน์โหลดไฟล์นี้จาก Github หน้านี้ และอัพโหลดลงในหน้า /wp-admin/plugin-install.php หลังจากอัพโหลด 
                        และเปิดใช้งาน (Activate) ระบบจะทำการสร้างตารางและคอลัมน์ใหม่จากตารางเดิมโดยอัตโนมัติ
                    </p>
                </div>
                <?php
                }
                ?>
            </div>
        </div>
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
            <div style="display: grid; grid-template-columns: 100px 1fr;">
                <img src="<?=get_option('affiliate_logo', '')?>" alt="Logo" width="100%" style="margin: 20px;">
                <div style="margin: 10px 40px; float: right;">
                    <h1>รายงานรายได้จากระบบพันธมิตร - Affiliate Program Report</h1>
                    <h4 style="margin: 5px 0;">คุณ <?=$affliate_records[0]->display_name;?> รหัสพันธมิตร <?=$refCode?></h4>
                    <p style="padding: 0;">วันที่ออกรายงาน: <?=date('d-m-Y')?></p>
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
                        <th style="width: 10%;">#</th>
                        <th>รายการสินค้า</th>
                        <th>ยอดขายรวม</th>
                        <th>% Commission</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($affliate_records as $row) {
                        $order = wc_get_order($row->order_id);
                        $product_display = "";

                        if($row->total_sales == null) continue;

                        if ($order) {
                            foreach ($order->get_items() as $item_id => $item) {
                                $product = $item->get_product();
                                if ($product) {
                                    $image = $product->get_image(array(40, 40));
                                    $name  = $item->get_name();
                                    
                                    $product_display .= '<div style="display:flex; align-items:center; margin-bottom:5px;">';
                                    $product_display .= '<div style="margin-right:10px;">' . $image . '</div>';
                                    $product_display .= '<div style="font-size:12px; line-height:1.2;">' . $name . ' (x' . $item->get_quantity() . ')</div>';
                                    $product_display .= '</div>';
                                }
                            }
                        } else {
                            $product_display = "ไม่พบข้อมูลออเดอร์";
                        }
                    ?>
                    <tr>
                        <td><a href="/wp-admin/post.php?post=<?=$row->order_id?>&action=edit">#<?=$row->order_id?></a></td>
                        <td><?=$product_display?></td>
                        <td><?=number_format($row->total_sales, 2)?> บาท</td>
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
                        <th colspan="4"><strong>รวมยอดขายทั้งหมด</strong></th>
                        <th><strong><?=number_format($total_sales_sum, 2)?> บาท</strong></th>
                    </tr>
                    <tr>
                        <th colspan="4"><strong>รวมยอด Commission ทั้งหมด</strong></th>
                        <th><strong><?=number_format($total_earns_sum, 2)?> บาท</strong></th>
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
    register_setting('affiliate_settings_group', 'LINE_recipient_id');
    register_setting('affiliate_settings_group', 'LINE_channel_secret');
    register_setting('affiliate_settings_group', 'LINE_channel_access_token');

    register_setting('affiliate_settings_group', 'GMAIL_sender_email');
    register_setting('affiliate_settings_group', 'GMAIL_access_token');
    register_setting('affiliate_settings_group', 'GMAIL_refresh_token');
    register_setting('affiliate_settings_group', 'GMAIL_client_id');
    register_setting('affiliate_settings_group', 'GMAIL_client_secret');
    register_setting('affiliate_settings_group', 'GMAIL_access_token_expires_at');

    register_setting('affiliate_content_settings_group', 'affiliate_condition');
    register_setting('affiliate_content_settings_group', 'affiliate_support_page');
    register_setting('affiliate_content_settings_group', 'affiliate_requirements_and_conditions' );

    register_setting( 'affiliate_email_content_settings_group', 'html_affiliate_approved');
    register_setting( 'affiliate_email_content_settings_group', 'html_affiliate_disapproved');
    register_setting( 'affiliate_email_content_settings_group', 'html_affiliate_suspended');
    register_setting( 'affiliate_email_content_settings_group', 'html_affiliate_payments');

    // Tiered commission settings (thresholds and extra %)
    register_setting('affiliate_tiers_settings_group', 'affiliate_tier_threshold_1');
    register_setting('affiliate_tiers_settings_group', 'affiliate_tier_threshold_2');
    register_setting('affiliate_tiers_settings_group', 'affiliate_tier_threshold_3');
    register_setting('affiliate_tiers_settings_group', 'affiliate_tier_bonus_1');
    register_setting('affiliate_tiers_settings_group', 'affiliate_tier_bonus_2');
    register_setting('affiliate_tiers_settings_group', 'affiliate_tier_bonus_3');

    $args = array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
    );

    $product_categories = get_terms($args);

    if ( ! empty($product_categories) && ! is_wp_error($product_categories) ) {
        foreach ( $product_categories as $category ) {
            register_setting('affiliate_commission_settings_group', 'commission_by_slug_'.str_replace(" ", "_", $category->name));
        }
    }
}

function addTransaction($ref, $type, $product_id, $order_id = null)
{
    global $wpdb;
    $affiliate_transactions = $wpdb->prefix . 'affiliate_transactions';

    if ($order_id) {
        $transaction_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$affiliate_transactions}
            WHERE refCode = %s AND type = %s AND product_id = %d AND order_id = %d
            LIMIT 1",
            $ref,
            $type,
            $product_id,
            $order_id
        ));

        if ($transaction_exists) {
            return false;
        }
    }

    $term_product_id = $product_id;
    $transaction_product = wc_get_product($product_id);
    if ($transaction_product && $transaction_product->is_type('variation')) {
        $term_product_id = $transaction_product->get_parent_id();
    }

    $terms = get_the_terms($term_product_id, 'product_cat');
    
    $default_commission = (float) get_option('affiliate_commission', 10);
    $max_commission = 0;

    if ($terms && !is_wp_error($terms)) {
        foreach ($terms as $term) {
            // ปั้นชื่อ Option ตามหมวดหมู่ที่วนลูปเจอ
            $option_name = 'commission_by_slug_' . str_replace(" ", "_", $term->name);
            
            // ดึงค่าคอมมิชชันของหมวดนี้ (ถ้าไม่มีให้เป็น 0 หรือค่า Default)
            $current_term_commission = (float) get_option($option_name, 0);
            
            // เปรียบเทียบ: ถ้าค่าของหมวดนี้ "มากกว่า" ค่าที่เก็บไว้เดิม ให้เปลี่ยนไปใช้ค่านี้
            if ($current_term_commission > $max_commission) {
                $max_commission = $current_term_commission;
            }
        }
    }

    if ($max_commission <= 0) {
        $max_commission = $default_commission;
    }

    // 3. คำนวณ Tier Bonus (ถ้ามีการส่ง order_id)
    $final_commission = $max_commission;
    if ($order_id && function_exists('wc_get_order')) {
        $order_obj = wc_get_order($order_id);
        if ($order_obj) {
            $order_total = (float) $order_obj->get_total();

            $t1 = (float) get_option('affiliate_tier_threshold_1', 10000);
            $t2 = (float) get_option('affiliate_tier_threshold_2', 30000);
            $t3 = (float) get_option('affiliate_tier_threshold_3', 60000);

            $b1 = (float) get_option('affiliate_tier_bonus_1', 1);
            $b2 = (float) get_option('affiliate_tier_bonus_2', 2);
            $b3 = (float) get_option('affiliate_tier_bonus_3', 3);

            $tier_bonus = 0;
            if ($order_total >= $t3) {
                $tier_bonus = $b3;
            } elseif ($order_total >= $t2) {
                $tier_bonus = $b2;
            } elseif ($order_total >= $t1) {
                $tier_bonus = $b1;
            }

            $final_commission = $max_commission + $tier_bonus;
        }
    }

    // 4. บันทึกลง Database
    return $wpdb->insert(
        $affiliate_transactions,
        [
            'refCode'               => $ref,
            'type'                  => $type,
            'product_id'            => $product_id,
            'order_id'              => $order_id,
            'commission_percentage' => $final_commission, // ค่าที่คำนวณแล้ว (รวม Tier Bonus)
            'created_at'            => current_time('mysql'),
        ],
        [
            '%s', '%s', '%d', '%d', '%f', '%s'
        ]
    );
}

add_action('init', 'handle_global_ref_cookie');

function handle_global_ref_cookie()
{
    if (isset($_GET['ref'])) {
        $ref = sanitize_text_field(wp_unslash($_GET['ref']));

        setcookie(
            'aff_global_ref',
            $ref,
            time() + (30 * DAY_IN_SECONDS), 
            '/',
            COOKIE_DOMAIN,
            true,
            false
        );

        $_COOKIE['aff_global_ref'] = $ref;
    }

    if (is_product() && isset($_COOKIE['aff_global_ref'])) {
        track_product_view();
    }
}

add_action('woocommerce_checkout_order_processed', 'affiliate_track_conversion', 10, 3);

function affiliate_track_conversion($order_id, $posted_data, $order)
{
    global $wpdb;

    // วนลูปสินค้าใน Order ทั้งหมด
    foreach ($order->get_items() as $item_id => $item) {
        $product_id = absint($item->get_variation_id() ?: $item->get_product_id());
        if (!$product_id) {
            continue;
        }
        $cookie_name = 'aff_global_ref';

        // เช็คว่าคนซื้อมี Cookie 'aff_global_ref' ของสินค้าชิ้นนี้ไหม
        if (isset($_COOKIE[$cookie_name])) {
            $ref = sanitize_text_field($_COOKIE[$cookie_name]);

            // ถ้าเจ้าของ refCode เป็นคนสั่งซื้อเอง ให้ข้ามการให้คอมมิชชั่น
            $ref_owner_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->prefix}users WHERE refCode = %s LIMIT 1", $ref));
            $buyer_id = 0;
            if (is_object($order) && method_exists($order, 'get_user_id')) {
                $buyer_id = (int) $order->get_user_id();
            }

            if ($ref_owner_id && $ref_owner_id === $buyer_id) {
                // บันทึก Note ว่าเป็นการสั่งซื้อของเจ้าของ refCode เอง และจะไม่ให้คอมมิชชั่น
                if (is_object($order) && method_exists($order, 'add_order_note')) {
                    $order->add_order_note(sprintf('ตรวจพบ Affiliate refCode "%s" แต่ว่าผู้สั่งซื้อเป็นเจ้าของโค้ด — ข้ามการให้ค่า commission', esc_html($ref)));
                }
                // เก็บลง order meta เผื่อใช้อ้างอิงภายหลัง
                if ($order_id) {
                    update_post_meta($order_id, '_affiliate_refcode', $ref);
                    update_post_meta($order_id, '_affiliate_ref_self_purchase', 1);
                }

                // ข้ามการให้คอมมิชชั่น
                continue;
            }

            // บันทึก Note ลงในออเดอร์ว่าอ้างอิงมาจาก refCode ใด
            if (is_object($order) && method_exists($order, 'add_order_note')) {
                $order->add_order_note(sprintf('Affiliate refCode คือ "%s"', esc_html($ref)));
            }

            // เก็บ refCode ลงใน order meta เพื่อให้ง่ายต่อการค้นหา/รายงาน
            if ($order_id) {
                update_post_meta($order_id, '_affiliate_refcode', $ref);
            }

            // บันทึกธุรกรรมลงตาราง (เปลี่ยน Type เป็น 'sale' หรือ 'conversion')
            // เราส่ง $order_id ไปด้วยเพื่อให้ตรวจสอบย้อนหลังได้
            addTransaction($ref, 'sale', $product_id, $order_id);

        }
    }
}

function track_product_view()
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

// My Account Menu
add_filter('woocommerce_account_menu_items', 'affiliate_menu_item');
function affiliate_menu_item($items)
{
    if (esc_attr(get_option('affiliate_enable', 'yes')) === 'yes') {        
        $new_items = array('affiliate-program' => 'โปรแกรมพันธมิตร (Affiliate)');
        return array_slice($items, 0, 1, true) + $new_items + array_slice($items, 1, count($items), true);
    }
    return $items;
}

add_filter('woocommerce_get_endpoint_url', 'custom_affiliate_menu_endpoint_url', 10, 4);
function custom_affiliate_menu_endpoint_url($url, $endpoint, $value, $permalink)
{
    if ($endpoint === 'affiliate-program') {
        // ชี้ไปที่ URL /affiliate/dashboard/ โดยตรง
        return site_url('/affiliate/dashboard/');
    }
    return $url;
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

    if($ref_code != '' || $ref_code != null) {
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
    
                <!-- Line -->
                <a href="https://social-plugins.line.me/lineit/share?url=<?php echo $encoded_url; ?>" title="Line" class="share-line" target="_blank" style="margin-right: 10px; background: #38C702;">
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
            margin-top: 10px;
        }
    </style>
    <?php
    }
}

// เพิ่ม Rewrite Rule สำหรับ /affiliate/dashboard
add_action('init', 'affiliate_dashboard_rewrite_rule');
function affiliate_dashboard_rewrite_rule() {
    add_rewrite_rule('^affiliate/dashboard/?$', 'index.php?is_affiliate_dashboard=1', 'top');
}

add_filter('query_vars', 'affiliate_dashboard_query_vars');
function affiliate_dashboard_query_vars($vars) {
    $vars[] = 'is_affiliate_dashboard';
    return $vars;
}

add_action('template_redirect', 'affiliate_dashboard_template_redirect');
function affiliate_dashboard_template_redirect() {
    if (get_query_var('is_affiliate_dashboard')) {
        status_header(200);

        $template_path = plugin_dir_path(__FILE__) . '/pages/member/dashboard.php';

        if (file_exists($template_path)) {
            require_once $template_path;
            exit;
        }
    }
}