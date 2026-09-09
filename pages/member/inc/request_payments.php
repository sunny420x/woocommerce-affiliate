<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
$transactions = [];
$total_unpaid_sum = 0;
$order_ids = [];

if($ref_code) {
    [ $transactions, $total_paid_sum, $total_unpaid_sum] = getTransactionOrderInfo(getTransaction($user_id, ""));
} else {
    $total_unpaid_sum = 0;
}

foreach ($transactions as $item) {
    if (($item->status == "wc-completed" || $item->status == "completed") && $item->paid == 0) {
        $order_ids[] = $item->order_id;
    }
}

//Request payment confirmation and insert into the database.
if(isset($_GET['action']) && $_GET['action'] === 'confirm') {
    global $wpdb;
    $affiliate_request_payments_table = $wpdb->prefix . 'affiliate_request_payments';

    if($total_unpaid_sum <= 0) {
        echo "<div class='alert alert-warning'>คุณไม่มียอด Commission ที่สามารถถอนเงินได้</div>";
        return;
    }

    $created_at = current_time('mysql');

    $check_existing_request = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT order_id FROM $affiliate_request_payments_table WHERE user_id = %d AND order_id = %s",
            $user_id, implode(',', $order_ids)
        )
    );

    if(!$check_existing_request) {
        $wpdb->insert(
            $affiliate_request_payments_table,
            [
                'user_id' => $user_id,
                'amount' => $total_unpaid_sum,
                'order_id' => implode(',', $order_ids),
                'created_at' => $created_at,
            ]
        );
        echo "<div class='alert alert-success'>คำขอถอนเงินของคุณถูกส่งเรียบร้อยแล้ว</div>";

        $line_notification = sendLineNotification(
            "มีคำขอถอนเงินใหม่จากผู้ใช้ ID: $user_id จำนวนเงิน: $total_unpaid_sum บาท สำหรับคำสั่งซื้อ: " . implode(', ', $order_ids) ?? "-"
        );

        if (is_wp_error($line_notification)) {
            error_log($line_notification->get_error_message());
        }
    } else {
        echo "<div class='alert alert-warning'>คุณได้ส่งคำขอถอนเงินสำหรับคำสั่งซื้อนี้แล้ว</div>";
    }
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