<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-4">
        <div class="card card-custom p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-medium">Commission สะสมทั้งหมด</span>
                    <h4 class="fw-bold my-1 text-success">฿ <?= number_format($total_earns_sum, 2); ?></h4>
                </div>
                <div class="icon-shape bg-success-subtle text-success">
                    <i class="fa-solid fa-wallet"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-4">
        <div class="card card-custom p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-medium">ยอดขายรวมจากลิงก์ของคุณ</span>
                    <h4 class="fw-bold my-1 text-primary">฿ <?= number_format($total_revenue_sum, 2); ?></h4>
                </div>
                <div class="icon-shape bg-primary-subtle text-primary">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-4">
        <div class="card card-custom p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-medium">จำนวนคำสั่งซื้อ</span>
                    <h4 class="fw-bold my-1 text-dark"><?= number_format($total_sales_cnt); ?> รายการ</h4>
                </div>
                <div class="icon-shape bg-info-subtle text-info">
                    <i class="fa-solid fa-bag-shopping"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<?php
if (file_exists(__DIR__ . '/inc/chart.php')) {
    include __DIR__ . '/inc/chart.php';
}
?>

<!-- Affiliate Link Box -->
<?php $esc_ref = esc_html($ref_code); ?>
<div class="card card-custom p-4 mb-4">
    <h5 class="fw-bold mb-2"><i class="fa-solid fa-share-nodes text-primary me-2"></i>ลิงก์สำหรับแนะนำ</h5>
    <p class="text-muted small mb-2">รหัสแนะนำของคุณคือ: <span class="badge bg-secondary"><?= $esc_ref ?></span></p>
    <div class="input-group mb-2">
        <input type="text" class="form-control" id="affLink" value="<?= home_url('/?ref=' . $esc_ref) ?>" readonly>
        <button class="btn btn-primary px-4" type="button" onclick="copyLink()">
            <i class="fa-regular fa-copy me-1"></i> คัดลอกลิงก์
        </button>
    </div>
    <small class="text-muted">* เมื่อมีผู้ซื้อสินค้าผ่านลิงก์นี้ คุณจะได้รับ Commission ทันที (สามารถต่อท้าย
        <code>?ref=<?= $esc_ref ?></code> บน URL อื่น ๆ ในเว็บได้)</small>
</div>

<!-- Transactions Table -->
<div class="card card-custom p-4 mb-4">
    <h5 class="fw-bold mb-3"><i class="fa-solid fa-list-check text-primary me-2"></i>รายการคำสั่งซื้อล่าสุด</h5>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>หมายเลขคำสั่งซื้อ</th>
                    <th class="text-center">สถานะออเดอร์</th>
                    <th>ยอดขายทั้งหมด</th>
                    <th>ยอด Commission</th>
                    <th class="text-center">สถานะการจ่ายเงิน</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $transactions_latest = getTransaction($user_id, "LIMIT 5");

                if (!empty($transactions_latest)) {
                    $transactions = [];
                    $current_order_id = null;

                    //Loop data and push to transactions array.
                    $transactions = [];

                    foreach ($transactions_latest as $item) {
                        $product = wc_get_product($item->product_id);
                        $quantity = 0;
                        $order = wc_get_order($item->order_id);

                        if ($order) {
                            foreach ($order->get_items() as $item_id => $order_item) {
                                if ($order_item->get_product_id() == $item->product_id || $order_item->get_variation_id() == $item->product_id) {
                                    $quantity += $order_item->get_quantity();
                                }
                            }
                        }

                        // คำนวณราคาและคอมมิชชันของรายการนี้ (คูณด้วยจำนวนชิ้นที่ซื้อจริง)
                        $product_price = (float) $product->get_price() * $quantity;
                        $commission_value = ((float) $item->commission_percentage / 100) * $product_price;

                        // ตรวจสอบว่ามี Order ID นี้ในระบบหรือยัง ถ้ายังให้ตั้งค่าเริ่มต้น
                        if (!isset($transactions[$item->order_id])) {
                            $transactions[$item->order_id] = (object) [
                                "order_id" => $item->order_id,
                                "quantity" => 0,
                                "status" => $item->status,
                                "total_sold_sum" => 0,
                                "total_earns_sum" => 0,
                                "commission_percentage" => $item->commission_percentage,
                                "paid" => $item->paid,
                            ];
                        }

                        // บวกสะสมยอดขาย จำนวนชิ้น และคอมมิชชันเข้าไปใน Order ID นั้นๆ
                        $transactions[$item->order_id]->quantity += $quantity;
                        $transactions[$item->order_id]->total_sold_sum += $product_price;
                        $transactions[$item->order_id]->total_earns_sum += $commission_value;
                    }

                    // Rendering table.
                    if (!empty($transactions)) {
                        foreach ($transactions as $item) {
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
                                <!-- แสดงผลยอดเงินสุทธิของออเดอร์นั้นๆ ได้ทันที ไม่ต้องคำนวณซ้ำในตาราง -->
                                <td><?= number_format($item->total_sold_sum) ?> บาท</td>
                                <td>
                                    <strong class="text-success"><?= number_format($item->total_earns_sum, 2); ?> บาท</strong>
                                    <span class="small text-muted">(<?= $item->commission_percentage ?>%)</span>
                                </td>
                                <td class="text-center">
                                    <?php if ($item->paid == 0) { ?>
                                        <span
                                            class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3">รอชำระค่าตอบแทน</span>
                                    <?php } else { ?>
                                        <span
                                            class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3">ชำระค่าตอบแทนแล้ว</span>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">ยังไม่มีรายการสั่งซื้อในขณะนี้</td>
                        </tr>
                    <?php
                    }
                }
                ?>

            </tbody>
        </table>
    </div>
</div>