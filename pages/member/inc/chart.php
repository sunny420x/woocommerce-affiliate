<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="card card-custom p-3 h-100">
            <h6 class="fw-bold mb-3"><i class="fa-solid fa-chart-pie text-warning me-2"></i>สัดส่วนสถานะชำระเงิน</h6>
            <div style="position: relative; height:230px;">
                <canvas id="commissionChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card card-custom p-3 h-100">
            <h6 class="fw-bold mb-3"><i class="fa-solid fa-chart-line text-success me-2"></i>แนวโน้มยอดขาย (บาท)</h6>
            <div style="position: relative; height:230px;">
                <canvas id="commissionFullChart"></canvas>
            </div>
        </div>
    </div>
</div>