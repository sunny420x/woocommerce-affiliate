<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$affiliate_withdrawals = $wpdb->get_results("SELECT w.id, u.display_name, u.user_email, w.amount, w.order_id, w.created_at
FROM {$wpdb->prefix}affiliate_request_payments as w
LEFT JOIN {$wpdb->prefix}users as u ON u.ID = w.user_id");

if(isset($_GET['id']) && is_numeric($_GET['id'])) {

    //Approve Withdrawal
    if(isset($_GET['action']) && $_GET['action'] == "approve") {
        $withdrawal_id = intval($_GET['id']);
        $order_ids = $wpdb->get_var($wpdb->prepare("SELECT order_id FROM {$wpdb->prefix}affiliate_request_payments WHERE id = %d", $withdrawal_id));
        $order_ids_array = explode(',', $order_ids);

        foreach ($order_ids_array as $order_id) {
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}affiliate_transactions SET paid = 1 WHERE order_id = %d", $order_id));
        }

        $line_notification = sendLineNotification(
            'ยืนยันการถอนเงินใหม่แล้ว จำนวน ' . number_format($wpdb->get_var($wpdb->prepare("SELECT amount FROM {$wpdb->prefix}affiliate_request_payments WHERE id = %d", $withdrawal_id)), 2) . ' บาท จากบัญชีของ: ' . $wpdb->get_var($wpdb->prepare("SELECT display_name FROM {$wpdb->prefix}users WHERE ID = (SELECT user_id FROM {$wpdb->prefix}affiliate_request_payments WHERE id = %d)", $withdrawal_id))
        );

        if (is_wp_error($line_notification)) {
            error_log($line_notification->get_error_message());
        }

        wp_redirect( "/wp-admin/admin.php?page=affiliate&option=affiliate_withdrawals&id=$withdrawal_id&status=success" );
    }
    //Disapprove Withdrawal
    if(isset($_GET['action']) && $_GET['action'] == "reject") {
        $withdrawal_id = intval($_GET['id']);
        $order_ids = $wpdb->get_var($wpdb->prepare("SELECT order_id FROM {$wpdb->prefix}affiliate_request_payments WHERE id = %d", $withdrawal_id));
        $order_ids_array = explode(',', $order_ids);

        foreach ($order_ids_array as $order_id) {
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}affiliate_transactions SET paid = 0 WHERE order_id = %d", $order_id));
        }

        $line_notification = sendLineNotification(
            'ปฎิเสธการถอนเงิน จำนวน ' . number_format($wpdb->get_var($wpdb->prepare("SELECT amount FROM {$wpdb->prefix}affiliate_request_payments WHERE id = %d", $withdrawal_id)), 2) . ' บาท จากบัญชีของ: ' . $wpdb->get_var($wpdb->prepare("SELECT display_name FROM {$wpdb->prefix}users WHERE ID = (SELECT user_id FROM {$wpdb->prefix}affiliate_request_payments WHERE id = %d)", $withdrawal_id))
        );

        if (is_wp_error($line_notification)) {
            error_log($line_notification->get_error_message());
        }

        wp_redirect( "/wp-admin/admin.php?page=affiliate&option=affiliate_withdrawals&id=$withdrawal_id&status=success" );
    }

    $withdrawal_id = intval($_GET['id']);
    $withdrawal = $wpdb->get_row($wpdb->prepare("SELECT 
    w.id, u.display_name, u.user_email, u.ID as user_id, w.amount, w.order_id, w.created_at, a.bank_account_number, a.bank_name
    FROM {$wpdb->prefix}affiliate_request_payments as w
    LEFT JOIN {$wpdb->prefix}users as u ON u.ID = w.user_id
    LEFT JOIN {$wpdb->prefix}users_affiliate_info as a ON a.user_id = u.ID
    WHERE w.id = %d", $withdrawal_id));

    if(!$withdrawal) {
        echo '<div class="wrap"><div class="notice notice-error"><p>ไม่พบคำขอถอนเงินนี้</p></div></div>';
        return;
    }
?>
<h1>รายละเอียดคำขอถอนเงิน หมายเลข #<?= $withdrawal->id ?></h1>
<div style="padding: 0px 25px 25px 25px;">
    <p><strong>ชื่อผู้ใช้งาน:</strong> <a href="admin.php?page=affiliate&option=affiliate_users&action=profile&user_id=<?=$withdrawal->user_id?>" target="_blank"><?= $withdrawal->display_name ?></a></p>
    <p><strong>อีเมล:</strong> <?= $withdrawal->user_email ?></p>
    <p><strong>หมายเลขบัญชี:</strong> <?= $withdrawal->bank_account_number ?> - <?= $withdrawal->bank_name ?></p>
    <p><strong>จำนวนเงิน:</strong> <strong><?= $withdrawal->amount ?> บาท</strong></p>
    <p><strong>ส่งคำขอเมื่อ:</strong> <?= $withdrawal->created_at ?></p>
    <h2>หมายเลขคำสั่งซื้อ:</h2>
    <table class="widefat fixed striped">
        <thead>
            <tr>
                <th>หมายเลขคำสั่งซื้อ</th>
                <th>รายการสินค้า</th>
                <th>ยอดคำสั่งซื้อรวม</th>
                <th>เปอร์เซ็นต์ Commission</th>
                <th>ยอด Commission</th>
                <th>สถานะ Commission</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $all_order_ids = explode(',', $withdrawal->order_id);
            $sum_commission = 0;
            foreach ($all_order_ids as $order_id) {
                $commission_info = $wpdb->get_results($wpdb->prepare("SELECT paid, commission_percentage, paid_at, product_id FROM {$wpdb->prefix}affiliate_transactions WHERE order_id = %d", $order_id));
                $order_detail = wc_get_order($order_id);
                if (!$order_detail) continue;
                $total_subtotal = 0;
                $total_commission = 0;
                foreach ( $order_detail->get_items() as $item_id => $item ) {
                    $product_name = $item->get_name();
                    $product_id   = $item->get_product_id();
                    $variation_id = $item->get_variation_id();
                    $quantity     = $item->get_quantity();
                    $subtotal     = $item->get_subtotal();
                    $total_subtotal +=  $subtotal * $quantity;
                    $commission_percentage = 0;
                    foreach($commission_info as $commission) {
                        if($commission->product_id == $product_id || $commission->product_id == $variation_id) {
                            $commission_percentage = $commission->commission_percentage;
                            $total_commission += $subtotal * ($commission->commission_percentage / 100);
                        }
                    }
            ?>
            <tr>
                <td><a href="/wp-admin/post.php?post=<?= $order_id ?>&action=edit" target="_blank">#<?= $order_id ?></a></td>
                <td><?=$product_name; ?></td>
                <td><?=number_format($subtotal, 2)?> บาท</td>
                <td><?= $commission_percentage ?>%</td>
                <td><?= number_format($subtotal * ($commission_percentage / 100), 2) ?> บาท</td>
                <td><?php
                if($commission_info[0]->paid == 0) {
                    echo '<span class="badge pending">ยังไม่ได้จ่าย Commission</span>';
                } elseif($commission_info[0]->paid == 1) {
                    echo '<span class="badge success">จ่ายแล้ว</span>';
                }
                ?></td></td>
            </tr>
            <?php
                }
            ?>
            <tr>
                <td colspan="5"><strong>ยอดรวมทั้งหมด</strong></td>
                <td><?=number_format($total_subtotal, 2)?> บาท</td>
            </tr>
            <tr>
                <td colspan="5"><strong>ยอด Commission รวมของคำสั่งซื้อ #<?=$order_id?></strong></td>
                <td><?=number_format($total_commission, 2)?> บาท</td>
            </tr>
            <?php
                $sum_commission += $total_commission;
            }
            ?>
            <tr>
                <th colspan="5"><strong>รวมยอด Commission ทุกคำสั่งซื้อ</strong></th>
                <th><strong><?=number_format($sum_commission, 2)?> บาท</strong></th>
            </tr>
        </tbody>
    </table>
    
    <h2>จัดการคำขอ</h2>
    <button class="button button-primary" onclick="window.location.href='admin.php?page=affiliate&option=affiliate_withdrawals&id=<?= $withdrawal_id ?>&action=approve'">อนุมัติ</button>
    <button class="button button-primary" onclick="window.location.href='admin.php?page=affiliate&option=affiliate_withdrawals&id=<?= $withdrawal_id ?>&action=reject'">ปฏิเสธ</button>
</div>
<?php
    return;
}
?>
<h1>คำขอถอนเงิน</h1>
<div style="padding: 25px 25px 25px 25px;">
<table class="widefat fixed striped">
    <thead>
        <tr>
            <th>ส่งคำขอเมื่อ</th>
            <th>ชื่อผู้ใช้งาน</th>
            <th>จำนวนเงิน</th>
            <th>หมายเลขคำสั่งซื้อ</th>
            <th>จัดการ</th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($affiliate_withdrawals as $withdrawal) {
            ?>
            <tr>
                <td><?= $withdrawal->created_at ?></td>
                <td><?= $withdrawal->display_name ?></td>
                <td><?= $withdrawal->amount ?> บาท</td>
                <td><?= $withdrawal->order_id ?></td>
                <td>
                    <a href="admin.php?page=affiliate&option=affiliate_withdrawals&id=<?= $withdrawal->id ?>" class="button button-primary button-small">ดำเนินการ</a>
                </td>
            </tr>
            <?php
        }
        ?>
    </tbody>
</table>
</div>