<?php
if (!defined('ABSPATH')) {
    exit;
}

$dataChart = getTransaction($user_id, "", null, null);

$orderInfo = getTransactionOrderInfo($dataChart);

$data_full_chart = $orderInfo[0];
$total_paid_sum = $orderInfo[1];
$total_unpaid_sum = $orderInfo[2];

$data_full_chart_json = wp_json_encode(array_values($data_full_chart));
$dataChartjson = wp_json_encode($dataChart);
?>

<div class="row g-3 mb-4">

    <div class="col-lg-4">
        <div class="card card-custom p-3 h-100">
            <h6 class="fw-bold mb-3">
                <i class="fa-solid fa-chart-pie text-warning me-2"></i>
                สัดส่วนสถานะชำระเงิน
            </h6>

            <div style="position: relative; height:230px;">
                <canvas id="commissionStatusChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card card-custom p-3 h-100">
            <h6 class="fw-bold mb-3">
                <i class="fa-solid fa-chart-line text-success me-2"></i>
                แนวโน้มยอดขาย (บาท)
            </h6>

            <div style="position: relative; height:230px;">
                <canvas id="commissionFullChart"></canvas>
            </div>
        </div>
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {

    const dataFullChart = <?= $data_full_chart_json ?: '[]' ?>;
    const dataStatusChart = <?= $dataChartjson ?: '[]' ?>;

    console.log('dataFullChart:', dataFullChart);

    const statusCanvas = document.getElementById('commissionStatusChart');

    if (statusCanvas) {
        const existingStatusChart = Chart.getChart(statusCanvas);
        if (existingStatusChart) {
            existingStatusChart.destroy();
        }
        const paidTransactions = dataStatusChart.filter(
            item => String(item.paid) === '1'
        );
        const unpaidTransactions = dataStatusChart.filter(
            item => String(item.paid) !== '1'
        );
        new Chart(statusCanvas.getContext('2d'), {
            type: 'pie',
            data: {
                labels: [
                    'Paid',
                    'Unpaid'
                ],
                datasets: [{
                    data: [
                        paidTransactions.length,
                        unpaidTransactions.length
                    ],
                    backgroundColor: [
                        '#ffc107',
                        '#6c757d'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    const salesCanvas =
        document.getElementById('commissionFullChart');

    if (salesCanvas) {

        const existingSalesChart = Chart.getChart(salesCanvas);

        if (existingSalesChart) {
            existingSalesChart.destroy();
        }
        const salesLabels = dataFullChart.map(item => {
            if (!item.created_at) {
                return '';
            }
            const datePart =
                String(item.created_at).split(' ')[0];
            const parts = datePart.split('-');
            if (parts.length !== 3) {
                return datePart;
            }
            const year = parts[0];
            const month = Number(parts[1]);
            const day = Number(parts[2]);

            return `${day}/${month}/${year}`;
        });

        const salesData = dataFullChart.map(item => {
            return Number(item.total_sold_sum) || 0;
        });

        console.log('salesLabels:', salesLabels);
        console.log('salesData:', salesData);
        console.log(
            'length:',
            salesLabels.length,
            salesData.length
        );

        new Chart(salesCanvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: salesLabels,
                datasets: [{
                    label: 'ยอดขาย (บาท)',
                    data: salesData,
                    borderColor: '#198754',
                    backgroundColor:
                        'rgba(25, 135, 84, 0.2)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return Number(value)
                                    .toLocaleString() + ' บาท';
                            }
                        }
                    }
                }
            }
        });
    }
})();
</script>