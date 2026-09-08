<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="card card-custom p-4" id="orders">
    <h5 class="fw-bold mb-3"><i class="fa-solid fa-list-check text-primary me-2"></i>ประวัติคำสั่งซื้อ</h5>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
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
                $transactions = [];
                $total_sum = 0;

                if($ref_code) {
                    [ $transactions, $total_sum] = getTransactionOrderInfo(getTransaction($user_id, ""));
                } else {
                    $total_sum = 0;
                }
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
                                <button class="btn btn-outline-primary btn-sm" onclick="window.location.href='/affiliate/dashboard/?order_id=<?=$item->order_id?>'">รายละเอียดคำสั่งซื้อ</button>
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
                    <td class="fw-bold text-center"><?= number_format($total_sum, 2); ?> บาท</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>