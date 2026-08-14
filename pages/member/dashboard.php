<?php
/**
 * Affiliate Member Dashboard Page
 * File: pages/member/dashboard.php
 */

// ตรวจสอบการ Login
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

global $wpdb;
$user_id      = get_current_user_id();
$current_user = wp_get_current_user();
$table_info   = $wpdb->prefix . 'users_affiliate_info';

// ดึง refCode ของ User
$ref_code = $wpdb->get_var($wpdb->prepare(
    "SELECT refCode FROM {$wpdb->prefix}users WHERE ID = %d",
    $user_id
));

// ตัวแปรสำหรับแจ้งเตือน
$notice_message = '';
$notice_type    = 'success';

// ==========================================
// 1. ประมวลผล: การสมัครเป็นตัวแทน (Affiliate Registration)
// ==========================================
if (isset($_POST['register_affiliate'])) {
    if (check_admin_referer('aff_reg')) {
        
        // 1. ตรวจสอบ User ID (รับค่ากรณีผู้ใช้ล็อกอินอยู่)
        $user_id = get_current_user_id();
        if (!$user_id) {
            $notice_message = 'กรุณาเข้าสู่ระบบก่อนทำการสมัคร';
            $notice_type    = 'danger';
            return;
        }

        // 2. เช็คว่ามีการเลือกไฟล์มาอย่างน้อย 1 ไฟล์หรือไม่
        if (!empty($_FILES['aff_identity_doc']['name'][0])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            
            $uploaded_urls    = array();
            $files            = $_FILES['aff_identity_doc'];
            $has_upload_error = false;
            $error_msg        = '';

            // กำหนดเฉพาะ MIME Types ที่อนุญาต (เพื่อความปลอดภัย)
            $mimes = array(
                'jpg|jpeg|jpe' => 'image/jpeg',
                'png'          => 'image/png',
                'pdf'          => 'application/pdf'
            );

            // วนลูปประมวลผลไฟล์ทีละไฟล์
            foreach ($files['name'] as $key => $value) {
                if (!empty($files['name'][$key])) {
                    
                    $file = array(
                        'name'     => $files['name'][$key],
                        'type'     => $files['type'][$key],
                        'tmp_name' => $files['tmp_name'][$key],
                        'error'    => $files['error'][$key],
                        'size'     => $files['size'][$key]
                    );

                    $upload_overrides = array(
                        'test_form' => false,
                        'mimes'     => $mimes // กรองประเภทไฟล์
                    );

                    $movefile = wp_handle_upload($file, $upload_overrides);

                    if ($movefile && !isset($movefile['error'])) {
                        $uploaded_urls[] = $movefile['url'];
                    } else {
                        $has_upload_error = true;
                        $error_msg        = $movefile['error'];
                        break; // หากพบไฟล์มีปัญหาให้หยุดลูปทันที
                    }
                }
            }

            // 3. ถ้าอัปโหลดสำเร็จครบถ้วน
            if (!$has_upload_error && !empty($uploaded_urls)) {
                // บันทึก URL เอกสารลงใน usermeta
                update_user_meta($user_id, 'affiliate_identity_doc', $uploaded_urls);

                $social_data = array(
                    'user_id'              => $user_id,
                    'full_name'      => sanitize_text_field($_POST['full_name'] ?? ''),
                    'phone_number'      => sanitize_text_field($_POST['phone_number'] ?? ''),
                    'social_media_01'      => sanitize_text_field($_POST['social_media_01'] ?? ''),
                    'social_media_01_type' => sanitize_text_field($_POST['social_media_01_type'] ?? ''),
                    'social_media_02'      => sanitize_text_field($_POST['social_media_02'] ?? ''),
                    'social_media_02_type' => sanitize_text_field($_POST['social_media_02_type'] ?? ''),
                    'social_media_03'      => sanitize_text_field($_POST['social_media_03'] ?? ''),
                    'social_media_03_type' => sanitize_text_field($_POST['social_media_03_type'] ?? ''),
                    'social_media_04'      => sanitize_text_field($_POST['social_media_04'] ?? ''),
                    'social_media_04_type' => sanitize_text_field($_POST['social_media_04_type'] ?? ''),
                );

                $inserted = $wpdb->insert(
                    $wpdb->prefix . 'users_affiliate_info',
                    $social_data,
                    array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
                );

                if ($inserted !== false) {
                    // สร้าง refCode และอัปเดตลงตาราง users (ทำเพียงครั้งเดียว)
                    $new_ref = strtoupper(substr(md5($user_id . time() . wp_rand()), 0, 8));
                    
                    $updated = $wpdb->update(
                        "{$wpdb->prefix}users",
                        array('refCode' => $new_ref),
                        array('ID' => $user_id),
                        array('%s'),
                        array('%d')
                    );

                    if ($updated !== false) {
                        $ref_code       = $new_ref;
                        $notice_message = 'ยินดีด้วย! ส่งหลักฐานและสมัครเป็นตัวแทนพันธมิตรสำเร็จแล้ว';
                        $notice_type    = 'success';
                    } else {
                        $notice_message = 'บันทึกข้อมูลเรียบร้อย แต่ไม่สามารถสร้าง Ref Code ได้';
                        $notice_type    = 'warning';
                    }
                } else {
                    $notice_message = 'เกิดข้อผิดพลาดในการบันทึกข้อมูลลงฐานข้อมูล';
                    $notice_type    = 'danger';
                }

            } else {
                $notice_message = 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์: ' . esc_html($error_msg);
                $notice_type    = 'danger';
            }
        } else {
            $notice_message = 'กรุณาแนบไฟล์รูปหลักฐานยืนยันตัวตนอย่างน้อย 1 ไฟล์';
            $notice_type    = 'danger';
        }
    }
}

// ==========================================
// 2. ประมวลผล: การบันทึกข้อมูลธนาคาร
// ==========================================
if (isset($_POST['save_affiliate_info'])) {
    $account_number = sanitize_text_field($_POST['aff_account_number']);
    $bank_name      = sanitize_text_field($_POST['aff_bank_name']);

    $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_info WHERE user_id = %d", $user_id));

    if ($exists) {
        $wpdb->update(
            $table_info,
            array(
                'bank_account_number' => $account_number,
                'bank_name'           => $bank_name,
                'updated_at'          => current_time('mysql')
            ),
            array('user_id' => $user_id),
            array('%s', '%s', '%s'),
            array('%d')
        );
    } else {
        $wpdb->insert(
            $table_info,
            array(
                'user_id'             => $user_id,
                'bank_account_number' => $account_number,
                'bank_name'           => $bank_name,
                'updated_at'          => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s')
        );
    }
    $notice_message = 'บันทึกข้อมูลบัญชีรับเงินเรียบร้อยแล้ว';
}

$user_affiliate_info = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_info WHERE user_id = %d", $user_id));
$verified = $user_affiliate_info->verified;

// ==========================================
// 3. Query ข้อมูลรายงานและสถิติ (เฉพาะเมื่อมี refCode)
// ==========================================
$chart_labels       = [];
$chart_data         = [];
$full_chart_labels  = [];
$full_chart_data    = [];
$total_earns_sum    = 0;
$total_revenue_sum  = 0;
$total_sales_cnt    = 0;
$transactions       = [];

if ($ref_code) {
    $affiliate_users        = $wpdb->prefix . 'users';
    $affiliate_transactions = $wpdb->prefix . 'affiliate_transactions';
    $order_stats_table      = $wpdb->prefix . 'wc_order_stats';

    $transactions = $wpdb->get_results($wpdb->prepare("
        SELECT 
            t.paid,
            os.status,
            t.order_id,
            t.commission_percentage,
            COUNT(CASE WHEN t.type = 'view' THEN 1 END) AS total_views,
            COUNT(CASE WHEN t.type = 'sale' THEN 1 END) AS total_sales_count,
            SUM(CASE 
                WHEN t.type = 'sale' AND os.total_sales IS NOT NULL AND (os.status = 'completed' OR os.status = 'wc-completed')
                THEN os.total_sales 
                ELSE 0 
            END) AS total_revenue,
            SUM(CASE 
                WHEN t.type = 'sale' AND os.total_sales IS NOT NULL AND (os.status = 'completed' OR os.status = 'wc-completed')
                THEN (os.total_sales - os.shipping_total) * (t.commission_percentage / 100)
                ELSE 0 
            END) AS total_earns 
        FROM {$affiliate_users} AS u
        LEFT JOIN {$affiliate_transactions} AS t
            ON u.refCode = t.refCode
        LEFT JOIN {$order_stats_table} AS os 
            ON t.order_id = os.order_id
        WHERE u.ID = %d 
        GROUP BY u.ID, u.display_name, u.user_email, u.refCode, t.paid, t.order_id, os.status
        ORDER BY t.order_id DESC", $user_id));

    $transactions_full = $wpdb->get_results($wpdb->prepare("
        SELECT 
            t.created_at,
            os.total_sales,
            os.status
        FROM {$affiliate_users} AS u
        LEFT JOIN {$affiliate_transactions} AS t
            ON u.refCode = t.refCode
        LEFT JOIN {$order_stats_table} AS os 
            ON t.order_id = os.order_id AND (os.status = 'completed' OR os.status = 'wc-completed')
        WHERE u.ID = %d 
        ORDER BY t.order_id DESC", $user_id));

    foreach ($transactions as $tx) {
        $chart_labels[]    = ($tx->paid == 1) ? 'จ่ายแล้ว' : 'รอชำระ';
        $chart_data[]      = (float)$tx->total_earns;
        $total_earns_sum   += (float)$tx->total_earns;
        $total_revenue_sum += (float)$tx->total_revenue;
        $total_sales_cnt   += (int)$tx->total_sales_count;
    }

    foreach ($transactions_full as $tx) {
        if (!empty($tx->created_at)) {
            $full_chart_labels[] = date('d/m/Y', strtotime($tx->created_at));
            $full_chart_data[]   = (float)$tx->total_sales;
        }
    }
}


// Helper
function getOrderStatusInThai($status) {
    if($status == "wc-processing") {
        return "<div class='badge bg-primary'>กำลังดำเนินการ</div>";
    }
    if($status == "wc-completed") {
        return "<div class='badge bg-success'>เสร็จสมบูรณ์</div>";
    }
    if($status == "wc-cancelled") {
        return "<div class='badge bg-danger'>ถูกยกเลิก</div>";
    }
    if($status == "wc-refunded") {
        return "<div class='badge bg-danger'>ถูกคืนเงิน</div>";
    }
    if($status == "wc-failed") {
        return "<div class='badge bg-danger'>ชำระเงินไม่สำเร็จ</div>";
    }
    if($status == "wc-on-hold") {
        return "<div class='badge bg-warning'>รอชำระเงิน</div>";
    }
    if($status == "wc-pending") {
        return "<div class='badge bg-primary'>รอดำเนินการ</div>";
    }
}

$is_affiliate_enabled = (esc_attr(get_option('affiliate_enable', 'yes')) === 'yes');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบพันธมิตร - Affiliate Dashboard</title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- Google Fonts (Prompt) -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f1f5f9;
            color: #334155;
        }
        .sidebar {
            min-height: 100vh;
            background: #0f172a;
            color: #fff;
        }
        .sidebar .nav-link {
            color: #94a3b8;
            padding: 12px 18px;
            border-radius: 8px;
            margin-bottom: 4px;
            transition: all 0.2s ease;
        }
        .sidebar .nav-link:hover, 
        .sidebar .nav-link.active {
            color: #fff;
            background: #1e293b;
        }
        .sidebar .nav-link i {
            width: 24px;
        }
        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            background: #ffffff;
        }
        .icon-shape {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        @media screen and (max-width: 1024px) {
            .sidebar {
                min-height: max-content;
            }
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">

        <!-- Sidebar Navigation -->
        <?php 
        $nav_path = __DIR__ . '/inc/nav.php';
        if (file_exists($nav_path)) {
            include $nav_path;
        }
        ?>

        <!-- Main Content Area -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            
            <!-- Top Bar -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <button class="btn btn-dark d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <h3 class="fw-bold mb-1">ระบบตัวแทนแนะนำสินค้า</h3>
                    <p class="text-muted small mb-0">สวัสดีคุณ <?= esc_html($current_user->display_name); ?></p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <img src="<?php echo get_avatar_url($user_id); ?>" class="rounded-circle border" width="42" height="42" alt="Avatar">
                </div>
            </div>

            <!-- Global Alert Notice -->
            <?php if (!empty($notice_message)) : ?>
                <div class="alert alert-success alert-dismissible fade show mb-4 card-custom" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i><?= esc_html($notice_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!$is_affiliate_enabled) : ?>
                <!-- CASE 1: ระบบปิดใช้งาน -->
                <div class="card card-custom p-5 text-center my-5">
                    <div class="text-warning mb-3">
                        <i class="fa-solid fa-triangle-exclamation fa-4x"></i>
                    </div>
                    <h3 class="fw-bold">ปิดใช้งานระบบพันธมิตรชั่วคราว</h3>
                    <p class="text-muted mb-0">ขณะนี้ระบบตัวแทนจำหน่ายกำลังปิดปรับปรุงชั่วคราว กรุณากลับมาใหม่อีกครั้งในภายหลัง</p>
                </div>

                
                <?php elseif (!$ref_code) : ?>
                    <!-- CASE 2: ยังไม่ได้สมัคร Affiliate -->
                    <div class="card card-custom p-4 p-md-5 my-4">
                        <div class="text-center mb-4">
                        <div class="text-primary mb-3">
                            <i class="fa-solid fa-id-card fa-3x"></i>
                        </div>
                        <h3 class="fw-bold mb-2">สมัครเป็นตัวแทนแนะนำสินค้า</h3>
                        <p class="text-muted col-lg-8 mx-auto mb-0">
                            ร่วมเป็นส่วนหนึ่งกับเรา รับลิงก์พิเศษสำหรับนำไปแชร์ และรับค่าคอมมิชชั่นทันทีเมื่อมีการสั่งซื้อผ่านลิงก์ของคุณ!
                        </p>
                    </div>
                    
                    <form method="post" enctype="multipart/form-data" class="col-lg-8 mx-auto">
                        <?php wp_nonce_field('aff_reg'); ?>
                        
                        <!-- ส่วนอัปโหลดหลักฐาน -->
                        <div class="mb-4 text-start">
                            <label for="aff_identity_doc" class="form-label fw-bold">
                                <i class="fa-solid fa-file text-primary me-1"></i> ข้อมูลเกี่ยวกับผู้สมัคร</span>
                            </label>

                            <div class="form-group mb-4">
                                <label for="full_name">ชื่อ-นามสกุล:</label>
                                <input type="text" name="full_name" id="full_name" class="form-control">
                            </div>

                            <div class="form-group mb-4">
                                <label for="phone_number">เบอร์โทรศัพท์:</label>
                                <input type="text" name="phone_number" id="phone_number" class="form-control">
                            </div>

                            <div class="form-group mb-4">
                                <label for="social_media">ช่องทางที่ใช้เผยแพร่:</label>
                                <p class="text-muted small">วางลิงค์โปรไฟล์ Social Media ของท่าน ที่จะใช้เป็นช่องทางในการเผยแพร่สินค้า</p>
                                <div class="d-flex gap-2">
                                    <select name="social_media_01_type" id="social_media_01_type" class="form-select" style="width: 250px;">
                                        <option value="facebook/ig">Facebook / Instagram</option>
                                        <option value="tiktok">TikTok</option>
                                        <option value="youtube">YouTube</option>
                                        <option value="other">อื่น ๆ </option>
                                    </select>
                                    <input type="text" name="social_media_01" id="social_media_01" class="form-control">
                                </div>
                                <div class="d-flex gap-2">
                                    <select name="social_media_02_type" id="social_media_02_type" class="form-select" style="width: 250px;">
                                       <option value="facebook/ig">Facebook / Instagram</option>
                                        <option value="tiktok">TikTok</option>
                                        <option value="youtube">YouTube</option>
                                        <option value="other">อื่น ๆ </option>
                                    </select>
                                    <input type="text" name="social_media_02" id="social_media_02" class="form-control">
                                </div>
                                <div class="d-flex gap-2">
                                    <select name="social_media_03_type" id="social_media_03_type" class="form-select" style="width: 250px;">
                                       <option value="facebook/ig">Facebook / Instagram</option>
                                        <option value="tiktok">TikTok</option>
                                        <option value="youtube">YouTube</option>
                                        <option value="other">อื่น ๆ </option>
                                    </select>
                                    <input type="text" name="social_media_03" id="social_media_03" class="form-control">
                                </div>
                                <div class="d-flex gap-2">
                                    <select name="social_media_04_type" id="social_media_04_type" class="form-select" style="width: 250px;">
                                        <option value="facebook/ig">Facebook / Instagram</option>
                                        <option value="tiktok">TikTok</option>
                                        <option value="youtube">YouTube</option>
                                        <option value="other">อื่น ๆ </option>
                                    </select>
                                    <input type="text" name="social_media_04" id="social_media_04" class="form-control">
                                </div>
                            </div>

                            <label for="aff_identity_doc" class="form-label fw-bold">
                                <i class="fa-solid fa-file-arrow-up text-primary me-1"></i> อัปโหลดเอกสารยืนยันตัวตน (สำเนาบัตรประชาชน และ รูปถ่ายคู่บัตรประชาชน) <span class="text-danger">*</span>
                            </label>
                            <!-- รูปที่ 1: บัตรประชาชน -->
                            <label for="aff_identity_doc_card">บัตรประชาชน:</label>
                            <input type="file" name="aff_identity_doc[]" class="form-control mb-2" accept="image/*" required>
                            <!-- รูปที่ 2: รูปถ่ายคู่กับบัตร -->
                            <label for="aff_identity_doc_selfie">รูปถ่ายคู่บัตรประชาชน:</label>
                            <input type="file" name="aff_identity_doc[]" class="form-control" accept="image/*" required>
                            <div class="form-text small text-muted">
                                รองรับไฟล์รูปภาพ (JPG, PNG) หรือ PDF ขนาดไม่เกิน 5MB
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" name="register_affiliate" class="btn btn-primary btn-lg px-5 shadow-sm w-100 w-sm-auto">
                                <i class="fa-solid fa-user-plus me-2"></i> สมัครเป็นพันธมิตรตอนนี้
                            </button>
                        </div>
                    </form>
                </div>
            
            <?php elseif (!$verified) : ?>
                <!-- CASE 1: ระบบปิดใช้งาน -->
                <div class="card card-custom p-5 text-center my-5">
                    <div class="text-success mb-3">
                        <i class="fa-solid fa-square-check fa-4x"></i>
                    </div>
                    <h3 class="fw-bold">คุณได้สมัครและส่งเอกสารแล้ว !</h3>
                    <p class="text-muted mb-0">ทางเราได้รับเอกสารแล้ว และจะดำเนินการตรวจสอบและยืนยันตัวตนของท่านโดยเร็วที่สุด</p>
                </div>

            <?php else : ?>
                <!-- CASE 3: เป็นสมาชิกแล้ว แสดง Dashboard รายงานผล -->

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="dashboard" role="tabpanel">
                        <?php
                        if (file_exists(__DIR__ . '/inc/dashboard.php')) {
                            include __DIR__ . '/inc/dashboard.php';
                        }
                        ?>
                    </div>
                    <div class="tab-pane fade" id="commission" role="tabpanel">
                        <?php
                        if (file_exists(__DIR__ . '/inc/commission.php')) {
                            include __DIR__ . '/inc/commission.php';
                        }
                        ?>
                    </div>
                    <div class="tab-pane fade" id="settings" role="tabpanel">
                        <?php
                        if (file_exists(__DIR__ . '/inc/bank_setting.php')) {
                            include __DIR__ . '/inc/bank_setting.php';
                        }
                        ?>
                    </div>
                    <div class="tab-pane fade" id="policy" role="tabpanel">
                        <?php
                        if (file_exists(__DIR__ . '/inc/policy.php')) {
                            include __DIR__ . '/inc/policy.php';
                        }
                        ?>
                    </div>

                </div>

            <?php endif; ?>

        </main>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Bootstrap 5.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php if ($ref_code && $is_affiliate_enabled): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Render Pie Chart
    const ctx = document.getElementById('commissionChart');
    if (ctx) {
        new Chart(ctx.getContext('2d'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'ยอดเงิน (บาท)',
                    data: <?php echo !empty($chart_data) ? json_encode($chart_data) : '[]'; ?>,
                    backgroundColor: ['#22c55e', '#f59e0b', '#ef4444', '#3b82f6'],
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    // 2. Render Line Chart
    const ctx_full = document.getElementById('commissionFullChart');
    if (ctx_full) {
        new Chart(ctx_full.getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($full_chart_labels); ?>,
                datasets: [{
                    label: 'ยอดขาย (บาท)',
                    data: <?php echo !empty($full_chart_data) ? json_encode($full_chart_data) : '[]'; ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderColor: '#3b82f6',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { 
                    y: { beginAtZero: true } 
                }
            }
        });
    }
});

function copyLink() {
    var copyText = document.getElementById("affLink");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value);
    alert("คัดลอกลิงก์แนะนำเรียบร้อยแล้ว!");
}
</script>
<?php endif; ?>

</body>
</html>