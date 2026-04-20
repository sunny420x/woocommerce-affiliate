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

// วางไว้ตอนเริ่มต้นฟังก์ชันแสดงหน้า Admin
if ( isset($_GET['action']) && $_GET['action'] == 'mark_paid' && isset($_GET['refCode']) ) {
    
    // เช็คความปลอดภัย (สำคัญมาก)
    if ( !isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'wcc_mark_paid_nonce') ) {
        wp_die('Security check failed!');
    }

    $ref_to_pay = sanitize_text_field($_GET['refCode']);
    
    // อัปเดตทุก Transaction ของคนนี้ที่ยังไม่เคยจ่าย (is_paid = 0) ให้เป็นจ่ายแล้ว (is_paid = 1)
    $wpdb->update(
        "{$wpdb->prefix}affiliate_transactions",
        array(
            'paid' => 1, 
            'paid_at' => current_time('mysql')
        ),
        array(
            'refCode' => $ref_to_pay,
            'paid' => 0 // อัปเดตเฉพาะอันที่ยังไม่เคยจ่าย
        ),
        array('%d', '%s'),
        array('%s', '%d')
    );

    echo '<div class="updated"><p>จ่ายเงินให้รหัส ' . esc_html($ref_to_pay) . ' เรียบร้อยแล้ว!</p></div>';
    
    wp_redirect(admin_url('admin.php?page=affiliate')); 
}

function get_all_users_table()
{
    global $wpdb;

    $affiliate_users = $wpdb->prefix . 'users';
    $affiliate_transactions = $wpdb->prefix . 'affiliate_transactions';
    $order_stats_table = $wpdb->prefix . 'wc_order_stats'; 

    $results = $wpdb->get_results(
        "
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
        WHERE u.refCode IS NOT NULL AND u.refCode != '' AND t.paid = 0 
        GROUP BY u.ID, u.display_name, u.user_email, u.refCode
        ORDER BY u.ID DESC
        "
    );

    // WHERE MONTH(t.created_at) = MONTH(CURDATE())
    // AND YEAR(t.created_at) = YEAR(CURDATE())
    ?>

<div class='card-admin'>
    <h1>ระบบการตลาดแบบพันธมิตรสำหรับ WooCommerce | Affiliate Program</h1>
    <p>ระบบ Affiliate กระตุ้นการขายบนเว็บไซต์ โดยการให้เปอร์เซ็น Affiliate Partner เป็นจำนวน <?=get_option('affiliate_commission', 10);?>% ของยอดขายสินค้า</p>
    <form action="options.php" method="post">
        <?php
        settings_fields('membership_settings_group');
        ?>
        <label for="affiliate_commission">% Commission: </label><input type="number" name="affiliate_commission" value="<?=esc_attr(get_option('affiliate_commission', 10)); ?>" />
        <?php submit_button('บันทึกการเปลี่ยนแปลง'); ?>
    </form>
    <hr>
    <h2>รายชื่อสมาชิกและสรุป</h2>
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
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>

        <?php
        if (!empty($results)) {
            foreach ($results as $row) {

            $mark_as_paid_action_url = wp_nonce_url( 
            admin_url('admin.php?page=affiliate&action=mark_paid&refCode=' . $row->refCode), 
            'wcc_mark_paid_nonce' 
            );
        ?>
            <tr>
                <td><?=esc_html($row->display_name)?></td>
                <td><?=esc_html($row->user_email)?></td>
                <td><?=esc_html($row->refCode)?></td>
                <td><?=esc_html($row->total_views)?></td>
                <td><?=esc_html($row->total_sales_count)?></td>
                <td><?=esc_html($row->total_revenue)?> บาท </td>
                <td><?=esc_html(floor($row->total_earns))?> บาท </td>
                <td>
                    <button type='button' class='button' onclick="window.location.href='<?=admin_url('admin.php?page=affiliate_report&id=' . absint($row->id));?>'">ออกรายงาน</button>
                    <button type='button' class='button' onclick="window.location.href='<?=$mark_as_paid_action_url?>'">ทำสถานะว่าจ่ายแล้ว</button>
                </td>
            </tr>
        <?php
            }
        } else {
        ?>
            <tr><td colspan="7">ไม่พบข้อมูลผู้ใช้งาน</td></tr>
        <?php
        }

        if (!empty($wpdb->last_error)) {
            echo '<div style="color:red;">SQL Error: ' . esc_html($wpdb->last_error).'</div>';
        }
        ?>
        </tbody>
    </table>

    <p>Github Repository: <a href="https://github.com/sunny420x/woocommerce-affiliate" target="_blank">github.com/sunny420x/woocommerce-affiliate</a></p>
</div>
<?php
}

add_action('admin_init', 'affiliate_settings_init');

function affiliate_settings_init() {
    register_setting('affiliate_settings_group', 'affiliate_commission');
}

function addTransaction($ref, $type, $product_id, $order_id = null)
{
    global $wpdb;

    $affiliate_transactions = $wpdb->prefix . 'affiliate_transactions';

    $wpdb->insert(
        $affiliate_transactions,
        [
            'refCode'    => $ref,
            'type'       => $type,
            'product_id' => $product_id,
            'order_id'   => $order_id,
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

function wcc_handle_global_ref_cookie() {
    if (isset($_GET['ref'])) {
        $ref = sanitize_text_field(wp_unslash($_GET['ref']));
        
        if (!isset( $_COOKIE['aff_global_ref'] ) ) {
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

add_action( 'woocommerce_checkout_order_processed', 'affiliate_track_conversion', 10, 3 );

function affiliate_track_conversion( $order_id, $posted_data, $order ) {
    // วนลูปสินค้าใน Order ทั้งหมด
    foreach ( $order->get_items() as $item_id => $item ) {
        $product_id = $item->get_product_id();
        $cookie_name = 'aff_global_ref';

        // เช็คว่าคนซื้อมี Cookie 'aff_view_[ID]' ของสินค้าชิ้นนี้ไหม
        if ( isset( $_COOKIE[$cookie_name] ) ) {
            $ref = sanitize_text_field($_COOKIE[$cookie_name]);

            // บันทึกธุรกรรมลงตาราง (เปลี่ยน Type เป็น 'sale' หรือ 'conversion')
            // เราส่ง $order_id ไปด้วยเพื่อให้ตรวจสอบย้อนหลังได้
            addTransaction($ref, 'sale', $product_id, $order_id);

            // (Optional) ลบ Cookie ทิ้งทันทีที่ขายได้ เพื่อป้องกันการนับยอดซ้ำหากเขากดสั่งอีกรอบโดยไม่ผ่านลิงก์เดิม
            setcookie($cookie_name, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
        }
    }
}

function wcc_track_product_view() {
    $ref = '';

    if (isset( $_COOKIE['aff_global_ref'] ) ) {
        $ref = $_COOKIE['aff_global_ref'];
    } else {
        if (isset($_GET['ref'])) {
            $ref = sanitize_text_field(wp_unslash($_GET['ref']));
        }
    }


    if (empty($ref)) return;

    $product_id = get_the_ID();
    $cookie_name = 'aff_view_' . $product_id;

    // ถ้ายังไม่มี Cookie ของสินค้าชิ้นนี้ (กันนับซ้ำใน 1 ชม.)
    if (!isset($_COOKIE[$cookie_name])) {
        
        // เรียกฟังก์ชันบันทึกลง DB ของพี่
        addTransaction($ref, 'view', $product_id);

        // ฝัง Cookie สินค้าไว้ (เก็บค่า $ref ไว้ข้างในด้วยเพื่อใช้ตอน Sale)
        setcookie($cookie_name, $ref, time() + 3600, COOKIEPATH, COOKIE_DOMAIN);
    }
}

// ลงทะเบียน Endpoint ใหม่
add_action('init', 'wcc_affiliate_endpoint');
function wcc_affiliate_endpoint() {
    add_rewrite_endpoint('affiliate-program', EP_PAGES);
}

// เพิ่มเมนูเข้าไปในรายการ My Account (เรียงต่อจาก Dashboard)
add_filter('woocommerce_account_menu_items', 'wcc_affiliate_menu_item');
function wcc_affiliate_menu_item($items) {
    // แทรกเมนูใหม่เข้าไป
    $new_items = array('affiliate-program' => 'โปรแกรมพันธมิตร (Affiliate)');
    return array_slice($items, 0, 1, true) + $new_items + array_slice($items, 1, count($items), true);
}

// เนื้อหาภายในหน้า Affiliate
add_action('woocommerce_account_affiliate-program_endpoint', 'wcc_affiliate_content');
function wcc_affiliate_content() {
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
            array('refCode' => $new_ref), // Field ที่พี่สร้างรอไว้
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
    echo '<h1 style="font-size: 24px;">ระบบพันธมิตร World Chemical</h1>';

    if ($ref_code) {
    ?>
        <p>รหัสแนะนำของคุณคือ: <strong><?=$esc_ref = esc_html($ref_code)?></strong></p>
        <p>ลิงก์สำหรับแนะนำ (ส่งลิงก์นี้ให้เพื่อน):<br>
        <code style="display:block; padding:10px; background:#f0f0f0;"><?=home_url('/?ref=' . $esc_ref)?></code></p>
        <p><small>*เมื่อมีคนเข้าชมผ่านลิงก์นี้และซื้อสินค้า คุณจะได้รับค่า Commission ทันที</small></p>
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

            foreach ( $transactions as $tx ) {
            ?>
            <tr>
                <td><?=$tx->total_sales_count; ?> รายการ</td>
                <td><?=number_format($tx->total_revenue, 2); ?> บาท</td>
                <td><?=number_format($tx->total_earns, 2); ?> บาท</td>
            </tr>
            <?php
            }
            ?>
        </table>
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