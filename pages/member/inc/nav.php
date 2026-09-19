<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="col-md-3 col-lg-2 d-md-block sidebar collapse p-3" id="sidebarMenu">
    <div class="d-flex align-items-center mb-4 px-2">
        <i class="fa-solid fa-handshake text-primary fs-3 me-2"></i>
        <span class="fs-5 fw-bold text-white">Affiliate Hub</span>
    </div>
    
    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist">
        <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist">
            <?php
            $affiliate_tab = $affiliate_tab ?? '';
            $is_guest = !is_user_logged_in();

            if ($is_guest) {
            ?>
                <a class="nav-link <?= $affiliate_tab === 'policy' ? 'active' : ''; ?>" href="/affiliate/policy">
                    <i class="fa-solid fa-file-contract me-2"></i>นโยบายและเงื่อนไข
                </a>
                <a class="nav-link <?= $affiliate_tab === 'requirements' ? 'active' : ''; ?>" href="/affiliate/requirements">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> คุณสมบัติและเงื่อนไข
                </a>
                <hr>
                <a class="nav-link" style="cursor: pointer;" href="/">
                    <i class="fa-solid fa-store me-2"></i>กลับไปหน้าร้านค้า
                </a>
            <?php
            } elseif(!$verified) {
            ?>
                <a class="nav-link <?= $affiliate_tab === 'register' ? 'active' : ''; ?>" href="/affiliate/register">
                    <i class="fa-solid fa-right-to-bracket me-2"></i> สมัครสมาชิก
                </a>
                <a class="nav-link <?= $affiliate_tab === 'policy' ? 'active' : ''; ?>" href="/affiliate/policy">
                    <i class="fa-solid fa-file-contract me-2"></i>นโยบายและเงื่อนไข
                </a>
                <a class="nav-link <?= $affiliate_tab === 'requirements' ? 'active' : ''; ?>" href="/affiliate/requirements">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> คุณสมบัติและเงื่อนไข
                </a>
            <?php
            } else {
            ?>
                <?php
                if(isset($_GET['order_id']) || isset($_GET['request_payments'])) {
                ?>
                <a class="nav-link" href="/affiliate/">
                    <i class="fa-solid fa-arrow-left me-2"></i>กลับไปหน้าแรก
                </a>
                <?php
                } else {
                ?>
                <a class="nav-link <?= $affiliate_tab === 'dashboard' ? 'active' : ''; ?>" href="/affiliate/dashboard">
                    <i class="fa-solid fa-chart-pie me-2"></i>แผงควบคุม
                </a>

                <a class="nav-link <?= $affiliate_tab === 'orders' ? 'active' : ''; ?>" href="/affiliate/orders">
                    <i class="fa-solid fa-list-check me-2"></i>ประวัติการสั่งซื้อ
                </a>

                <a class="nav-link <?= $affiliate_tab === 'commission' ? 'active' : ''; ?>" href="/affiliate/commission">
                    <i class="fa-solid fa-table me-2"></i>อัตราคอมมิชชั่น
                </a>

                <a class="nav-link <?= $affiliate_tab === 'settings' ? 'active' : ''; ?>" href="/affiliate/settings">
                    <i class="fa-solid fa-building-columns me-2"></i>ตั้งค่าบัญชี
                </a>

                <a class="nav-link <?= $affiliate_tab === 'policy' ? 'active' : ''; ?>" href="/affiliate/policy">
                    <i class="fa-solid fa-file-contract me-2"></i>นโยบายและเงื่อนไข
                </a>

                <a class="nav-link <?= $affiliate_tab === 'requirements' ? 'active' : ''; ?>" href="/affiliate/requirements">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> คุณสมบัติและเงื่อนไข
                </a>

                <a class="nav-link <?= $affiliate_tab === 'help' ? 'active' : ''; ?>" href="/affiliate/help">
                    <i class="fa-regular fa-circle-question me-2"></i>ช่วยเหลือ
                </a>
                <hr>
                <a class="nav-link" style="cursor: pointer;" href="/">
                    <i class="fa-solid fa-store me-2"></i>กลับไปหน้าร้านค้า
                </a>
            <?php
                }
            }
            ?>
        </div>
    </div>
</div>