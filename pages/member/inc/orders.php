<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="content-header">
    <p class="text-white mb-0"><a class="text-white me-2" href="/affiliate"><i class="fa-solid fa-chevron-left me-3"></i></a>ประวัติคำสั่งซื้อ (Order History)</p>
</div>
<div class="p-4" id="orders">
    <?php
    $transactions = [];
    $total_paid_sum = 0;
    $total_unpaid_sum = 0;

    $order_status = $_GET['order_status'] ?? "";
    $payment_status = $_GET['payment_status'] ?? "";

    if($ref_code) {
        [ $transactions, $total_paid_sum, $total_unpaid_sum] = getTransactionOrderInfo(getTransaction($user_id, "", null, $order_status ?? "", $payment_status ?? ""));
    } else {
        $total_paid_sum = 0;
        $total_unpaid_sum = 0;
    }
    ?>
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6">
            <div class="card card-custom p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-medium">Commission สะสมทั้งหมด</span>
                        <h4 class="fw-bold my-1 text-success">฿ <?= number_format($total_paid_sum, 2); ?></h4>
                    </div>
                    <div class="icon-shape bg-success-subtle text-success">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6">
            <div class="card card-custom p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-medium">Commission ที่กำลังรอการจ่าย</span>
                        <h4 class="fw-bold my-1 text-primary">฿ <?= number_format($total_unpaid_sum, 2); ?></h4>
                    </div>
                    <div class="icon-shape bg-primary-subtle text-primary">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card card-custom p-4">
        <h5 class="fw-bold mb-3"><i class="fa-solid fa-list-check text-primary me-2"></i>ประวัติคำสั่งซื้อ
            <button class="btn btn-primary btn-sm" onclick="window.location.href='/affiliate/?request_payments=<?=$user_id?>'">ส่งคำขอถอนเงิน</button>
        </h5>
        <form action="" method="get">
            <div class="d-flex justify-content-end mb-3">
                <div class="mb-3">
                    <label for="order_status" class="form-label">สถานะออเดอร์</label>
                    <select name="order_status" id="order_status" class="form-select">
                        <option value="">ทั้งหมด</option>
                        <option value="pending" <?= $order_status === 'wc-pending' ? 'selected' : '' ?>>รอดำเนินการ</option>
                        <option value="processing" <?= $order_status === 'wc-processing' ? 'selected' : '' ?>>กำลังดำเนินการ</option>
                        <option value="completed" <?= $order_status === 'wc-completed' ? 'selected' : '' ?>>เสร็จสิ้น</option>
                        <option value="cancelled" <?= $order_status === 'wc-cancelled' ? 'selected' : '' ?>>ยกเลิก</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="payment_status" class="form-label">สถานะการจ่ายเงิน</label>
                    <select name="payment_status" id="payment_status" class="form-select">
                        <option value="">ทั้งหมด</option>
                        <option value="paid" <?= $payment_status === 'paid' ? 'selected' : '' ?>>จ่ายแล้ว</option>
                        <option value="unpaid" <?= $payment_status === 'unpaid' ? 'selected' : '' ?>>รอการจ่าย</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">กรอง</button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>หมายเลขคำสั่งซื้อ</th>
                        <th class="text-center">สถานะออเดอร์</th>
                        <th>ยอดขายทั้งหมด</th>
                        <th>ยอด Commission</th>
                        <th class="text-center">สถานะการจ่ายเงิน</th>
                        <th class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($transactions)) {
                        foreach ($transactions as $item) {
                            ?>
                            <tr>
                                <td>
                                    <strong>
                                        #<?= esc_html($item->order_id); ?>
                                    </strong>
                                    <span class="text-muted small">(<?= $item->quantity ?> รายการ)</span>
                                </td>
                                <td class="text-center">
                                    <?php
                                    if (function_exists('getOrderStatusInThai')) {
                                        echo getOrderStatusInThai($item->status);
                                    } else {
                                        echo esc_html($item->status);
                                    }
                                    ?>
                                </td>
                                <!-- แสดงผลยอดเงินสุทธิของออเดอร์นั้นๆ ได้ทันที ไม่ต้องคำนวณซ้ำในตาราง -->
                                <td><?= number_format($item->total_sold_sum) ?> บาท</td>
                                <td>
                                    <strong class="text-success"><?= number_format($item->total_earns_sum, 2); ?> บาท</strong>
                                    <span class="small text-muted">(<?= $item->commission_percentage ?>%)</span>
                                </td>
                                <td class="text-center">
                                    <?php if ($item->paid == 0) { ?>
                                        <span
                                            class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3">รอชำระค่าตอบแทน</span>
                                    <?php } else { ?>
                                        <span
                                            class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3">ชำระค่าตอบแทนแล้ว</span>
                                    <?php } ?>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-outline-primary btn-sm" onclick="window.location.href='/affiliate/?order_id=<?=$item->order_id?>'">รายละเอียดคำสั่งซื้อ</button>
                                </td>
                            </tr>
                        <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">ยังไม่มีรายการสั่งซื้อในขณะนี้</td>
                        </tr>
                    <?php
                    }
                    ?>
                    <tr>
                        <td colspan="5" class="fw-bold text-end">รวมยอด Commission</td>
                        <td class="fw-bold text-center"><?= number_format($total_unpaid_sum, 2); ?> บาท</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>