<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$data_full_chart = getTransaction($user_id, "", "", null);
$data_full_chart_json = wp_json_encode($data_full_chart);
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
const commissionStatusChartCtx =
    document.getElementById('commissionStatusChart').getContext('2d');
const commissionFullChartCtx =
    document.getElementById('commissionFullChart').getContext('2d');
const dataFullChart = <?= $data_full_chart_json ?: '[]' ?>;

const paidTransactions = dataFullChart.filter(
    item => String(item.paid) === '1'
);

const unpaidTransactions = dataFullChart.filter(
    item => String(item.paid) !== '1'
);

const commissionStatusChart = new Chart(
    commissionStatusChartCtx,
    {
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
    }
);

const salesByOrder = {};

dataFullChart.forEach(item => {

    const orderId = item.order_id;

    if (!salesByOrder[orderId]) {
        salesByOrder[orderId] = 0;
    }

    salesByOrder[orderId] += parseFloat(
        item.commission_percentage || 0
    );
});

const salesLabels = Object.keys(salesByOrder);
const salesData = Object.values(salesByOrder);

const commissionFullChart = new Chart(
    commissionFullChartCtx,
    {
        type: 'line',
        data: {
            labels: salesLabels,

            datasets: [{
                label: 'ยอดขาย (บาท)',

                data: salesData,

                borderColor: '#198754',

                backgroundColor: 'rgba(25, 135, 84, 0.2)',

                fill: true,

                tension: 0.3
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            scales: {
                y: {
                    beginAtZero: true,

                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString() + ' บาท';
                        }
                    }
                }
            }
        }
    }
);
</script>