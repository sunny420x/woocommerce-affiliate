<?php
/**
 * Plugin Name: World Chemical Affiliate Marketing
 * Description: ระบบ Affiliate Marketing สำหรับ World Chemical
 * Version: 1.0
 * Author: Jirakit Pawnsakunrungrot
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

function dashboard_styling() {
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
        'Affiliate Program | World Chemical',    // Page title
        'Affiliate Program',                     // Menu title
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

function get_all_users_table()
{
    global $wpdb;

    $affiliate_users = $wpdb->prefix . 'affiliate_users';
    $affiliate_transactions = $wpdb->prefix . 'affiliate_transactions';
    $products_table = $wpdb->prefix . 'wc_product_meta_lookup';

    $results = $wpdb->get_results(
        "
        SELECT 
            u.id,
            u.full_name,
            u.refCode,
            COUNT(CASE WHEN t.type = 'view' THEN 1 END) AS total_views,
            COUNT(CASE WHEN t.type = 'sold' THEN 1 END) AS total_sales,
            SUM(CASE 
                WHEN t.type = 'sold' 
                THEN p.min_price * 0.1 
                ELSE 0 
            END) AS total_earns
        FROM {$affiliate_users} AS u
        LEFT JOIN {$affiliate_transactions} AS t
            ON u.refCode = t.refCode
        LEFT JOIN {$products_table} as p ON t.product_id = p.product_id
        WHERE MONTH(t.created_at) = MONTH(CURDATE())
        AND YEAR(t.created_at) = YEAR(CURDATE())
        GROUP BY u.id, u.full_name, u.refCode
        ORDER BY u.id DESC
        "
    );

    $output = "<div class='card-admin'>";
    $output .= '<table class="widefat fixed striped">';
    $output .= '<h1>ยินดีต้อนรับสู่ World Chemical Affiliate Program</h1> <br>';
    $output .= '<thead>';
    $output .= '<tr>';
    $output .= '<th>ชื่อ-นามสกุล</th>';
    $output .= '<th>หมายเลข Ref</th>';
    $output .= '<th>ยอดเข้าชม</th>';
    $output .= '<th>ขายได้ (รายการ)</th>';
    $output .= '<th>ยอด Commission เดือนนี้</th>';
    $output .= '<th>จัดการ</th>';
    $output .= '</tr>';
    $output .= '</thead>';
    $output .= '<tbody>';

    if (!empty($results)) {
        foreach ($results as $row) {
            $output .= '<tr>';
            $output .= '<td>' . esc_html($row->full_name) . '</td>';
            $output .= '<td>' . esc_html($row->refCode) . '</td>';
            $output .= '<td>' . esc_html($row->total_views) . '</td>';
            $output .= '<td>' . esc_html($row->total_sales) . '</td>';
            $output .= '<td>' . esc_html(floor($row->total_earns)) . ' บาท </td>';
            $output .= "<td><button type='button' class='button button-primary' onclick=\"window.location.href='" . admin_url('admin.php?page=affiliate_users&id=' . absint($row->id)) . "'\">จัดการผู้ใช้</button> ";
            $output .= "<button type='button' class='button' onclick=\"window.location.href='" . admin_url('admin.php?page=affiliate_report&id=' . absint($row->id)) . "'\">ออกรายงาน</button></td>";
            $output .= '</tr>';
        }
    } else {
        $output .= '<tr><td colspan="5">ไม่พบข้อมูลผู้ใช้งาน</td></tr>';
    }

    if (!empty($wpdb->last_error)) {
        echo '<div style="color:red;">SQL Error: ' . esc_html($wpdb->last_error) . '</div>';
    }

    $output .= '</tbody>';
    $output .= '</table>';
    $output .= '</div>';

    return $output;
}

function get_user_editor($id)
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'affiliate_users';

    $query = $wpdb->prepare(
        "SELECT full_name, refCode, username FROM $table_name WHERE id = %d",
        $id
    );

    $results = $wpdb->get_results($query);

    $output = "<div class='card-admin'>";
    $output .= '<h2>จัดการผู้ใช้ World Chemical Affiliate Program</h2>';

    if (!empty($results)) {
        foreach ($results as $row) {
            $output .= '<b>Username:</b><br>';
            $output .= '<input type="text" value="' . esc_attr($row->username) . '" name="username" class="regular-text"><br>';
            $output .= '<b>Full Name:</b><br>';
            $output .= '<input type="text" value="' . esc_attr($row->full_name) . '" name="full_name" class="regular-text"><br>';
            $output .= '<b>Ref Code:</b><br>';
            $output .= '<input type="text" value="' . esc_attr($row->refCode) . '" name="refCode" class="regular-text"><br>';
            $output .= '<br><input type="submit" value="แก้ไขข้อมูลผู้ใช้งาน" class="button button-primary"><br>';
            $output .= '<br><hr><br>';
            $output .= '<input type="submit" value="ลบผู้ใช้งาน" class="button button-secondary">';
        }
    } else {
        $output .= '<div>ไม่พบข้อมูลผู้ใช้งาน</div>';
    }

    $output .= "</div>";

    // Error handling (useful for debugging, but hide in production!)
    if (!empty($wpdb->last_error) && current_user_can('manage_options')) {
        $output .= '<div style="color:red;">SQL Error: ' . esc_html($wpdb->last_error) . '</div>';
    }

    return $output;
}

function add_view_ref($ref, $product_id)
{
    global $wpdb;

    $affiliate_transactions = $wpdb->prefix . 'affiliate_transactions';

    $wpdb->insert(
        $affiliate_transactions,
        [
            'refCode' => $ref,
            'type' => 'view',
            'product_id' => $product_id,
            'created_at' => current_time('mysql'),
        ],
        [
            '%s',
            '%s',
            '%d',
            '%s'
        ]
    );
}

function updateUser($full_name, $username, $refCode)
{
    global $wpdb;

    $affiliate_users = $wpdb->prefix . 'affiliate_users';

    $wpdb->update(
        $affiliate_users,
        [
            'full_name' => $full_name,
            'username' => $username,
            'refCode' => $refCode
        ],
        [
            '%s',
            '%s',
            '%s'
        ]
    );
}

function addUser($full_name, $username, $refCode)
{
    global $wpdb;

    $affiliate_users = $wpdb->prefix . 'affiliate_users';

    $wpdb->insert(
        $affiliate_users,
        [
            'full_name' => $full_name,
            'username' => $username,
            'refCode' => $refCode
        ],
        [
            '%s',
            '%s',
            '%s'
        ]
    );
}

function deleteUser($id)
{
    global $wpdb;

    $affiliate_users = $wpdb->prefix . 'affiliate_users';

    $wpdb->delete(
        $affiliate_users,
        array('ID' => $id),
        array('%d') // Format the value as an integer
    );
}

//Check if is product page.
function checkForRef()
{
    if (!function_exists('is_product') || !is_product()) {
        return;
    }

    if (!isset($_GET['ref'])) {
        return;
    }
    $ref = sanitize_text_field(wp_unslash($_GET['ref']));
    $product_id = get_the_ID();
    $cookie_name = 'aff_view_' . $product_id;

    if (!isset($_COOKIE[$cookie_name])) {
        add_view_ref($ref, $product_id);
        setcookie(
            $cookie_name,
            '1',
            time() + 3600, // 1 ชั่วโมง
            COOKIEPATH,
            COOKIE_DOMAIN
        );
    }
}

add_action('template_redirect', 'checkForRef');