<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="card card-custom p-4" id="orders">
    <h5 class="fw-bold mb-3"><i class="fa-solid fa-list-check text-primary me-2"></i>ส่งคำขอถอนเงิน</h5>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>หมายเลขคำสั่งซื้อ</th>
                    <th class="text-center">สถานะออเดอร์</th>
                    <th>ยอดขายทั้งหมด</th>
                    <th>ยอด Commission</th>
                    <th class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $transactions = [];
                $total_unpaid_sum = 0;
                $order_ids = [];

                if($ref_code) {
                    [ $transactions, $total_paid_sum, $total_unpaid_sum] = getTransactionOrderInfo(getTransaction($user_id, ""));
                } else {
                    $total_unpaid_sum = 0;
                }

                foreach ($transactions as $item) {
                    if ($item->status == "wc-completed" || $item->status == "completed") {
                        $order_ids[] = $item->order_id;
                    }
                }

                if(isset($_GET['action']) && $_GET['action'] === 'confirm') {
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'affiliate_request_payments';
                    $wpdb->insert(
                        $table_name,
                        [
                            'user_id' => $user_id,
                            'amount' => $total_unpaid_sum,
                            'order_id' => implode(',', $order_ids),
                            'status' => 0,
                            'created_at' => current_time('mysql'),
                        ]
                    );

                    $notice_message = 'ส่งคำขอถอนเงินเรียบร้อยแล้ว กรุณารอการตรวจสอบจากผู้ดูแลระบบ';
                    $notice_type    = 'success';
                }

                if (!empty($transactions)) {
                    foreach ($transactions as $item) {
                        if (($item->status == "wc-completed" || $item->status == "completed") && $item->paid == 0) {
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
                            <td><?= number_format($item->total_sold_sum) ?> บาท</td>
                            <td>
                                <strong class="text-success"><?= number_format($item->total_earns_sum, 2); ?> บาท</strong>
                                <span class="small text-muted">(<?= $item->commission_percentage ?>%)</span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-outline-primary btn-sm" onclick="window.location.href='/affiliate/dashboard/?order_id=<?=$item->order_id?>'">รายละเอียดคำสั่งซื้อ</button>
                            </td>
                        </tr>
                    <?php
                        }
                    }
                } else {
                    ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">ยังไม่มีรายการสั่งซื้อในขณะนี้</td>
                    </tr>
                <?php
                }
                ?>
                <tr>
                    <td colspan="4" class="fw-bold text-end">รวมยอด Commission</td>
                    <td class="fw-bold text-center"><?= number_format($total_unpaid_sum, 2); ?> บาท</td>
                </tr>
            </tbody>
        </table>
        <button class="btn btn-primary w-100" onclick="window.location.href='/affiliate/dashboard/?request_payments=<?=$user_id?>&action=confirm'">ยืนยันการส่งคำขอถอนเงิน</button>
    </div>
</div>