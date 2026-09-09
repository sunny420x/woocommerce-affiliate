<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<h1>📊 สถิติการขายสินค้าจากระบบ Affiliate</h1>
<div style="padding: 25px 25px 25px 25px;">
    จากวันที่: <input type="date" name="from_filter" id="from_filter" value="<?=$_GET['from'] ?? '' ?>">
    ถึงวันที่: <input type="date" name="to_filter" id="to_filter" value="<?=$_GET['to'] ?? '' ?>">
    <button class="button" onclick="applyFilter(document.getElementById('from_filter').value, document.getElementById('to_filter').value)">กรอง</button>
    <script>
        function applyFilter(from, to) {
            window.location.href=`admin.php?page=affiliate&option=statistic&from=${from}&to=${to}`;
        }
    </script>
    <br>
    <br>
    <span>สถิติการขายสินค้าโดยหัก % Commission แล้ว</span>
    <canvas id="myAffiliateChart"></canvas>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    const ctx = document.getElementById('myAffiliateChart').getContext('2d');
    const myChart = new Chart(ctx, {
        type: 'line', 
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [
                {
                    label: 'ยอดขาย (บาท)',
                    data: <?php echo json_encode($revenue_data); ?>,
                    borderColor: '#27ae60',
                    backgroundColor: 'rgba(39, 174, 96, 0.1)',
                    yAxisID: 'y', // ใช้แกน Y ด้านขวา
                    fill: true,
                    tension: 0.3
                },
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: { // แกนขวา สำหรับ Revenue
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: { display: true, text: 'ยอดเงิน (บาท)' }
                }
            }
        }
    });
    </script>
</div>