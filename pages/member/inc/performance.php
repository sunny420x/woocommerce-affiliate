<?php
if (!defined('ABSPATH')) {
    exit;
}

// ทุกแท็บถูก include อยู่ใน scope เดียวกัน จึงคำนวณในฟังก์ชันเพื่อไม่ให้ทับตัวแปรของแท็บอื่น
if (!function_exists('affiliate_get_paid_performance')) {
    function affiliate_get_paid_performance($user_id)
    {
        // เฉพาะรายการที่จ่ายค่าตอบแทนแล้ว (paid = 1)
        $paid_transactions = getTransaction($user_id, "", null, 1);

        $category_stats = [];
        $monthly_sales = [];

        foreach ($paid_transactions as $item) {
            $product = wc_get_product($item->product_id);
            $order = wc_get_order($item->order_id);
            if (!$product || !$order) {
                continue;
            }

            $quantity = 0;
            foreach ($order->get_items() as $order_item) {
                if ($order_item->get_product_id() == $item->product_id || $order_item->get_variation_id() == $item->product_id) {
                    $quantity += $order_item->get_quantity();
                }
            }
            $sold = (float) $product->get_price() * $quantity;

            // variation ใช้หมวดหมู่ของสินค้าแม่
            $parent_id = $product->get_parent_id() ?: $product->get_id();
            $terms = wp_get_post_terms($parent_id, 'product_cat');
            if (is_wp_error($terms) || empty($terms)) {
                $terms = [(object) ['term_id' => 0, 'name' => 'ไม่มีหมวดหมู่']];
            }
            foreach ($terms as $term) {
                if (!isset($category_stats[$term->term_id])) {
                    $category_stats[$term->term_id] = ['name' => $term->name, 'quantity' => 0, 'sales' => 0];
                }
                $category_stats[$term->term_id]['quantity'] += $quantity;
                $category_stats[$term->term_id]['sales'] += $sold;
            }

            $month = substr((string) $item->created_at, 0, 7);
            $monthly_sales[$month] = ($monthly_sales[$month] ?? 0) + $sold;
        }

        uasort($category_stats, function ($a, $b) {
            return $b["quantity"] - $a["quantity"];
        });
        $category_stats = array_slice(array_values($category_stats), 0, 5);
        ksort($monthly_sales);

        return [$category_stats, $monthly_sales];
    }
}

$category_stats = [];
$monthly_sales = [];
if ($affiliate_tab === 'dashboard') {
    try {
        [$category_stats, $monthly_sales] = affiliate_get_paid_performance($user_id);
    } catch (Throwable $e) {
        error_log('affiliate performance: ' . $e->getMessage());
    }
}

$category_json = wp_json_encode($category_stats);
$monthly_json = wp_json_encode($monthly_sales);
?>
<div class="card card-custom p-4 mb-4">
    <h5 class="fw-bold mb-1"><i class="fa-solid fa-trophy text-warning me-2"></i>สถิติผลประกอบการ</h5>
    <p class="text-muted small mb-3">นับเฉพาะยอดขายที่จ่ายค่าตอบแทนแล้ว</p>
    <?php if (empty($category_stats)) { ?>
        <p class="text-muted small mb-0">ยังไม่มียอดขายที่จ่ายค่าตอบแทนแล้ว</p>
    <?php } else { ?>
        <div class="row g-3">
            <div class="col-lg-5">
                <h6 class="fw-bold mb-3">หมวดหมู่สินค้าขายดี</h6>
                <div style="position: relative; height:260px;">
                    <canvas id="topCategoryChart"></canvas>
                </div>
            </div>
            <div class="col-lg-7">
                <h6 class="fw-bold mb-3">ยอดขายรายเดือน (บาท)</h6>
                <div style="position: relative; height:260px;">
                    <canvas id="paidMonthlySalesChart"></canvas>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        (function () {
            const categories = <?= $category_json ?: '[]' ?>;
            const monthly = <?= $monthly_json ?: '{}' ?>;

            const catCanvas = document.getElementById('topCategoryChart');
            if (catCanvas) {
                const old = Chart.getChart(catCanvas);
                if (old) old.destroy();
                new Chart(catCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: categories.map(c => c.name),
                        datasets: [{
                            label: 'จำนวนชิ้นที่ขายได้',
                            data: categories.map(c => c.quantity),
                            backgroundColor: '#ffc107'
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
                    }
                });
            }

            const monthCanvas = document.getElementById('paidMonthlySalesChart');
            if (monthCanvas) {
                const old = Chart.getChart(monthCanvas);
                if (old) old.destroy();
                new Chart(monthCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: Object.keys(monthly),
                        datasets: [{
                            label: 'ยอดขาย (บาท)',
                            data: Object.values(monthly),
                            backgroundColor: '#198754'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { callback: v => Number(v).toLocaleString() + ' บาท' }
                            }
                        }
                    }
                });
            }
        })();
        </script>
    <?php } ?>
</div>
