<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
$transactions_latest = getTransaction($user_id, "LIMIT 5");
?>
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-4">
        <div class="card card-custom p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-medium">Commission สะสมทั้งหมด</span>
                    <h4 class="fw-bold my-1 text-success">฿ <?= number_format($total_earns_sum, 2); ?></h4>
                </div>
                <div class="icon-shape bg-success-subtle text-success">
                    <i class="fa-solid fa-wallet"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-4">
        <div class="card card-custom p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-medium">ยอดขายรวมจากลิงก์ของคุณ</span>
                    <h4 class="fw-bold my-1 text-primary">฿ <?= number_format($total_revenue_sum, 2); ?></h4>
                </div>
                <div class="icon-shape bg-primary-subtle text-primary">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-4">
        <div class="card card-custom p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-medium">จำนวนคำสั่งซื้อ</span>
                    <h4 class="fw-bold my-1 text-dark"><?= number_format($total_sales_cnt); ?> รายการ</h4>
                </div>
                <div class="icon-shape bg-info-subtle text-info">
                    <i class="fa-solid fa-bag-shopping"></i>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Charts Row -->
    <?php
    if (file_exists(__DIR__ . '/inc/chart.php')) {
    include __DIR__ . '/inc/chart.php';
    }
    ?>

    <!-- Affiliate Link Box -->
    <?php $esc_ref = esc_html($ref_code); ?>
    <div class="card card-custom p-4 mb-4">
    <h5 class="fw-bold mb-2"><i class="fa-solid fa-share-nodes text-primary me-2"></i>ลิงก์สำหรับแนะนำ</h5>
    <p class="text-muted small mb-2">รหัสแนะนำของคุณคือ: <span class="badge bg-secondary"><?= $esc_ref ?></span></p>
    <div class="input-group mb-2">
        <input type="text" class="form-control" id="affLink" value="<?= home_url('/?ref=' . $esc_ref) ?>" readonly>
        <button class="btn btn-primary px-4" type="button" onclick="copyLink()">
            <i class="fa-regular fa-copy me-1"></i> คัดลอกลิงก์
        </button>
    </div>
    <small class="text-muted">* เมื่อมีผู้ซื้อสินค้าผ่านลิงก์นี้ คุณจะได้รับ Commission ทันที (สามารถต่อท้าย <code>?ref=<?= $esc_ref ?></code> บน URL อื่น ๆ ในเว็บได้)</small>
    </div>

    <!-- Transactions Table -->
    <div class="card card-custom p-4 mb-4">
    <h5 class="fw-bold mb-3"><i class="fa-solid fa-list-check text-primary me-2"></i>รายการคำสั่งซื้อล่าสุด</h5>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>หมายเลขคำสั่งซื้อ</th>
                    <th class="text-center">สถานะออเดอร์</th>
                    <th>ยอดขายทั้งหมด</th>
                    <th>ยอด Commission</th>
                    <th class="text-center">สถานะการจ่ายเงิน</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($transactions_latest)) : ?>
                    <?php foreach ($transactions_latest as $tx) : ?>
                        <tr>
                            <td>
                                <strong>#<?= esc_html($tx->order_id); ?></strong> 
                                <span class="text-muted small">(<?= esc_html($tx->total_sales_count); ?> รายการ)</span>
                            </td>
                            <td class="text-center">
                                <?php 
                                if (function_exists('getOrderStatusInThai')) {
                                    echo getOrderStatusInThai($tx->status);
                                } else {
                                    echo esc_html($tx->status);
                                }
                                ?>
                            </td>
                            <td><?= number_format($tx->total_revenue, 2); ?> บาท</td>
                            <td><strong class="text-success"><?= number_format($tx->total_earns, 2); ?> บาท</strong> <span class="small text-muted">(<?=$tx->commission_percentage?>%)</span></td>
                            <td class="text-center">
                                <?php if ($tx->paid == 0) : ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3">รอชำระค่าตอบแทน</span>
                                <?php else : ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3">ชำระค่าตอบแทนแล้ว</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">ยังไม่มีรายการสั่งซื้อในขณะนี้</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>