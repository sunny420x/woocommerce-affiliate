<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$affiliate_withdrawals = $wpdb->get_results("SELECT w.id, u.display_name, u.user_email, w.amount, w.order_id, w.created_at
FROM {$wpdb->prefix}affiliate_request_payments as w
LEFT JOIN {$wpdb->prefix}users as u ON u.ID = w.user_id");

if(isset($_GET['id']) && is_numeric($_GET['id'])) {
    getReport($_GET['id'], 'manage');
    return;
}

//ออก Reports ประจำเดือน ตามเดือนต่าง ๆ
if(isset($_GET['action']) && $_GET['action'] == "get_reports") {
    $start = $_GET['start'];
    $start = $_GET['end'];
    $affiliate_withdrawals = $wpdb->get_results($wpdb->prepare("SELECT w.id, w.order_id, w.created_at 
    FROM {$wpdb->prefix}affiliate_request_payments as w WHERE w.created_at BETWEEN %s AND %s", $start, $end));

    foreach($affiliate_withdrawals as $row) {
        getReport($row->id, 'view');
    }

    return;
}
?>
<h1>คำขอถอนเงิน</h1>
<div style="padding: 25px 25px 25px 25px;">
<button class="button button-primary" 
    onclick="window.location.href='admin.php?page=affiliate&option=affiliate_withdrawals&action=get_reports&start=<?=date('Y-m-01');?>&end=<?=date('Y-m-t');?>'">📋 ออกรายงานค่า Commission ของเดือนนี้
</button>
<br><br>
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