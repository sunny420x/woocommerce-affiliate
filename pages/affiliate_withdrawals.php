<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$affiliate_withdrawals = $wpdb->get_results("SELECT w.id, u.display_name, u.user_email, w.amount, w.order_id, w.created_at
FROM {$wpdb->prefix}affiliate_request_payments as w
LEFT JOIN {$wpdb->prefix}users as u ON u.ID = w.user_id");

if(isset($_GET['id']) && is_numeric($_GET['id'])) {
?>
<h1>รายละเอียดคำขอถอนเงิน หมายเลข #<?= $_GET['id'] ?></h1>
<div style="padding: 0px 25px 25px 25px;">
<?php
    getReport($_GET['id'], 'manage');
?>
</div>
<?php
    return;
}

//ออก Reports ประจำเดือน ตามเดือนต่าง ๆ
if (isset($_GET['action'], $_GET['start'], $_GET['end']) && $_GET['action'] === 'get_reports') {
    $start = sanitize_text_field(wp_unslash($_GET['start']));
    $end = sanitize_text_field(wp_unslash($_GET['end']));
    $affiliate_withdrawals = $wpdb->get_results($wpdb->prepare("SELECT w.id, w.order_id, w.created_at 
    FROM {$wpdb->prefix}affiliate_request_payments as w WHERE DATE(w.created_at) BETWEEN %s AND %s", $start, $end));
    ?>
    <h1 class="no-print">รายงานยอด Commission และคำขอถอนเงิน</h1>
    <div style="padding: 0px 25px 25px 25px;">
    <h2>ยอดคำขอถอนเงินทั้งหมดในช่วง <?=$start?> ถึง <?=$end?> <span class="no-print"><button class="button button-primary button-small" onclick="window.print()">พิมพ์รายงาน</button></span></h2>
    <?php
    $total_income = 0;
    $total_commission_outcome = 0;
    foreach($affiliate_withdrawals as $row) {
        [$income, $outcome] = getReport($row->id, 'range');
        $total_commission_outcome += $outcome;
        $total_income += $income;
    }
    ?>
    <h2>สรุปยอดรวม Commission ตั้งแต่วันที่ <?=$start?> ถึง <?=$end?></h2>
    <p>ออกรายงานเมื่อเวลา <?=date('d-m-Y H:i:s')?></p>
    <table class="widefat fixed striped" style="margin-top: 20px;">
        <tbody>
            <tr>
                <th><strong>รวมยอดขายทั้งหมด</strong></th>
                <td style="text-align: right;"><strong><?=number_format($total_income)?> บาท</strong></td>
            </tr>
            <tr>
                <th><strong>รวมยอด Commission ที่จ่ายไป</strong></th>
                <td style="text-align: right;"><strong><?=number_format($total_commission_outcome)?> บาท</strong></td>
            </tr>
        </tbody>
    </table>
    </div>
    <?php
    return;
}
?>
<h1>คำขอถอนเงิน</h1>
<div style="padding: 25px 25px 25px 25px;">
<label for="dateStart">วันที่:</label><input type="date" id="dateStart"> <label for="dateEnd">ถึง</label><input type="date" id="dateEnd">
ิ<button class="button button-primary" onclick="applyReportFilter()">ออกรายงานตามวันที่ที่เลือก</button>
<button class="button button-outline-primary" 
    onclick="window.location.href='<?= esc_url(admin_url('admin.php?page=affiliate&option=affiliate_withdrawals&action=get_reports&start=' . date('Y-m-01') . '&end=' . date('Y-m-t'))) ?>'">ออกรายงานค่า Commission ของเดือนนี้
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
<script>
    function applyReportFilter() {
        window.location.href = `admin.php?page=affiliate&option=affiliate_withdrawals&action=get_reports&start=${document.getElementById('dateStart').value}&end=${document.getElementById('dateEnd').value}`
    }
</script>
</div>