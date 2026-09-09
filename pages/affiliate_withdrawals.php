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
    $withdrawal = $wpdb->get_row($wpdb->prepare("SELECT w.id, u.display_name, u.user_email, w.amount, w.order_id, w.created_at
    FROM {$wpdb->prefix}affiliate_request_payments as w
    LEFT JOIN {$wpdb->prefix}users as u ON u.ID = w.user_id
    WHERE w.id = %d", $withdrawal_id));

    if(!$withdrawal) {
        echo '<div class="wrap"><div class="notice notice-error"><p>ไม่พบคำขอถอนเงินนี้</p></div></div>';
        return;
    }
?>
<h1>รายละเอียดคำขอถอนเงิน หมายเลข #<?= $withdrawal->ID ?></h1>
<div style="padding: 0px 25px 25px 25px;">
    <p><strong>ชื่อผู้ใช้งาน:</strong> <?= $withdrawal->display_name ?></p>
    <p><strong>อีเมล:</strong> <?= $withdrawal->user_email ?></p>
    <p><strong>จำนวนเงิน:</strong> <strong><?= $withdrawal->amount ?> บาท</strong></p>
    <h2>หมายเลขคำสั่งซื้อ:</h2>
    <table class="widefat fixed striped">
        <thead>
            <tr>
                <th>หมายเลขคำสั่งซื้อ</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $all_order_ids = explode(',', $withdrawal->order_id);
            foreach ($all_order_ids as $item) {
            ?>
            <tr>
                <td><a href="/wp-admin/post.php?post=<?= $item ?>&action=edit" target="_blank">#<?= $item ?></a></td>
            </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
    <p><strong>ส่งคำขอเมื่อ:</strong> <?= $withdrawal->created_at ?></p>
    
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
            <th>สถานะ</th>
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
                <td><?php 
                if($withdrawal->status == 0) {
                    echo '<span class="badge warning">รอการอนุมัติ</span>';
                } elseif($withdrawal->status == 1) {
                    echo '<span class="badge success">อนุมัติแล้ว</span>';
                }
                ?></td>
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