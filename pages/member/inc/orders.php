<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="card card-custom p-4" id="orders">
    <h5 class="fw-bold mb-3"><i class="fa-solid fa-list-check text-primary me-2"></i>ประวัติคำสั่งซื้อ</h5>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>หมายเลขคำสั่งซื้อ</th>
                    <th class="text-center">สถานะออเดอร์</th>
                    <th>ยอดขายทั้งหมด</th>
                    <th>ยอด Commission</th>
                    <th class="text-center">สถานะการจ่ายเงิน</th>
                    <th class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $transactions_orders_full = getTransaction($user_id, "");

                if (!empty($transactions_orders_full)) {
                    $transactions = [];
                    $current_order_id = null;

                    //Loop data and push to transactions array.
                    $transactions = [];

                    $total_sum = 0;

                    foreach ($transactions_orders_full as $item) {
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
                        $total_sum += $item->total_earns_sum;
                    }
                    ?>
                    <tr>
                        <td colspan="5">รวมยอด Commission</td>
                        <td><?= number_format($total_sum, 2); ?> บาท</td>
                    </tr>
                    <?php
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
                                <td class="text-center">
                                    <button class="btn btn-outline-primary btn-sm" onclick="window.location.href='/affiliate/dashboard/?order_id=<?=$item->order_id?>'">รายละเอียดคำสั่งซื้อ</button>
                                </td>
                            </tr>
                        <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">ยังไม่มีรายการสั่งซื้อในขณะนี้</td>
                        </tr>
                    <?php
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
</div>