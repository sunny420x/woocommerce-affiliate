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
            if(!$verified) {
            ?>
                <a class="nav-link active" data-bs-toggle="tab" href="#register" role="tab">
                    <i class="fa-solid fa-right-to-bracket me-2"></i> สมัครสมาชิก
                </a>
                <a class="nav-link" data-bs-toggle="tab" href="#requirements" role="tab">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> คุณสมบัติและเงื่อนไข
                </a>
            <?php
            } else {
            ?>
                <a class="nav-link active" data-bs-toggle="tab" href="#dashboard" role="tab">
                    <i class="fa-solid fa-chart-pie me-2"></i>แผงควบคุม
                </a>

                <a class="nav-link" data-bs-toggle="tab" href="#orders" role="tab">
                    <i class="fa-solid fa-list-check me-2"></i>ประวัติการสั่งซื้อ
                </a>

                <a class="nav-link" data-bs-toggle="tab" href="#commission" role="tab">
                    <i class="fa-solid fa-table me-2"></i>อัตราคอมมิชชั่น
                </a>

                <a class="nav-link" data-bs-toggle="tab" href="#settings" role="tab">
                    <i class="fa-solid fa-building-columns me-2"></i>ตั้งค่าบัญชี
                </a>

                <a class="nav-link" data-bs-toggle="tab" href="#policy" role="tab">
                    <i class="fa-solid fa-file-contract me-2"></i>นโยบายและเงื่อนไข
                </a>

                <a class="nav-link" data-bs-toggle="tab" href="#help" role="tab">
                    <i class="fa-regular fa-circle-question me-2"></i>ช่วยเหลือ
                </a>
                <hr>
                <a class="nav-link" style="cursor: pointer;" href="/">
                    <i class="fa-solid fa-store me-2"></i>กลับไปหน้าร้านค้า
                </a>
            <?php
            }
            ?>
        </div>
    </div>
</div>