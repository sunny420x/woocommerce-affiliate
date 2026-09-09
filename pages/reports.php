<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$affiliate_report_table = $wpdb->prefix . 'affiliate_transactions';
$users_table = $wpdb->prefix . 'users';

$query = "SELECT u.ID as user_id, t.created_at, u.display_name, SUM(os.total_sales * (t.commission_percentage / 100)) AS total_amount, t.paid 
    FROM $affiliate_report_table AS t
    JOIN $users_table AS u ON u.refCode = t.refCode 
    LEFT JOIN {$wpdb->prefix}wc_order_stats AS os ON t.order_id = os.order_id AND (os.status = 'completed' OR os.status = 'wc-completed') ";

if (isset($_GET['start']) && isset($_GET['end'])) {
    $start = sanitize_text_field($_GET['start']);
    $end = sanitize_text_field($_GET['end']);
    $query .= "WHERE t.created_at BETWEEN '$start' AND '$end'";
}

$affiliate_report = $wpdb->query($query);
?>
<h1>📋 ออกรายงานสรุป</h1>
<div style="padding: 25px 25px 25px 25px;">
    <form action="admin.php?page=affiliate&option=reports" method="get">
        เริ่ม: <input type="date" name="start" id="start" value="<?=$_GET['from'] ?? '' ?>">
        ถึง: <input type="date" name="end" id="end" value="<?=$_GET['to'] ?? '' ?>">
        <button type="submit" class="button button-primary">กรอง</button>
    </form>
    <br>
    <table class="widefat fixed striped">
        <thead>
            <tr>
                <th>วันที่</th>
                <th>ชื่อผู้ใช้งาน</th>
                <th class="text-end">ยอดเงินรวม</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($affiliate_report as $row) {
            ?>
            <tr>
                <td><?= $row->created_at ?></td>
                <td><a href="admin.php?page=affiliate&option=affiliate_users&action=profile&user_id=<?= $row->user_id ?>"><?= $row->display_name ?></a></td>
                <td class="text-end"><?= number_format($row->total_amount, 2) ?> บาท</td>
            </tr>
            <?php
            }
            ?>
        </tbody>
    </table>
</div>