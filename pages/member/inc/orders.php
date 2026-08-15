<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
$transactions_orders_full = getTransaction($user_id, "");
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
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($transactions_orders_full)) : ?>
                    <?php foreach ($transactions_orders_full as $tx) : ?>
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