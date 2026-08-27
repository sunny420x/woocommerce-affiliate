<?php
if (!defined('ABSPATH')) {
    exit;
}

function getTransactionByOrderId($order_id) {
    global $wpdb;
    
    $affiliate_transactions = $wpdb->prefix . 'affiliate_transactions';

    $transactions = $wpdb->get_results($wpdb->prepare("
    SELECT product_id, commission_percentage FROM {$affiliate_transactions} WHERE order_id = %d
    ", $order_id));

    return $transactions;
}

$order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
$order = getOrderById($order_id);

if (!$order) {
    echo '<div class="card card-custom p-4"><h5 class="fw-bold mb-0 text-danger"><i class="fa-solid fa-circle-exclamation me-2"></i>ไม่พบข้อมูลคำสั่งซื้อ</h5></div>';
    return;
}

$customer_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
if (empty($customer_name)) {
    $customer_name = $order->get_formatted_billing_full_name() ?: '—';
}

$customer_email = $order->get_billing_email() ?: $order->get_customer_email();
if (empty($customer_email)) {
    $customer_email = '—';
}

$affiliate_rows = getTransactionByOrderId($order_id);
?>
<div class="card card-custom p-4 mb-4" id="order-detail">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
        <div>
            <h5 class="fw-bold mb-1"><i class="fa-solid fa-receipt text-primary me-2"></i>รายละเอียดคำสั่งซื้อ #<?= esc_html($order->get_id()); ?></h5>
            <div class="text-muted small">
                วันที่สั่งซื้อ: <?= esc_html(date_i18n('d/m/Y H:i', strtotime($order->get_date_created() ?? "-"))); ?><br>
                ชื่อลูกค้า: <?= esc_html($customer_name); ?><br>
                อีเมล์: <?= esc_html($customer_email); ?>
            </div>
        </div>
        <div>
            <?php if (function_exists('getOrderStatusInThai')) { echo getOrderStatusInThai($order->get_status()); } else { echo esc_html($order->get_status()); } ?>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-12 rounded overflow-hidden">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px;">รูป</th>
                            <th>สินค้า</th>
                            <th class="text-start">จำนวน</th>
                            <th class="text-center">ราคาสินค้า</th>
                            <th class="text-center">Commission</th>
                            <th class="text-end">ค่าคอมมิชชั่น</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($affiliate_rows)) {
                            $total_earns_sum = 0;
                            foreach ($affiliate_rows as $item) {
                                $product = wc_get_product($item->product_id);
    
                                $quantity = 0;
    
                                $order = wc_get_order( $order_id );
    
                                if ( $order ) {
                                    foreach ( $order->get_items() as $item_id => $order_item ) {
                                        if ($order_item->get_product_id() == $item->product_id || $order_item->get_variation_id() == $item->product_id ) {
                                            $quantity += $order_item->get_quantity();
                                        }
                                    }
                                }
    
                                $commission_value = ((float) $item->commission_percentage / 100) * (float) $product->get_price();
                                $total_sold_sum += (float) $product->get_price();
                                $total_earns_sum += $commission_value;
                            ?>
                                <tr>
                                    <td>
                                        <?=$product->get_image(array( 48, 48 )) ?? "" ?>
                                    </td>
                                    <td>
                                        <?=$product->get_title() ?? "" ?>
                                    </td>
                                    <td class="text-start"><?=number_format($quantity)?></td>
                                    <td class="text-center"><?=$product->get_price()?> บาท</td>
                                    <td class="text-center"><?= esc_html($item->commission_percentage); ?>%</td>
                                    <td class="text-end text-success fw-bold"><?= number_format($commission_value, 2); ?> บาท</td>
                                </tr>
                            <?php } ?>
                            <tr>
                                <th colspan="4"></th>
                                <th class="text-end"><strong>รวมยอดคำสั่งซื้อ:</strong> <?=number_format($total_sold_sum, 2)?> บาท</th>
                                <th class="text-end text-success fw-bold"><?=number_format($total_earns_sum, 2)?> บาท</th>
                            </tr>
                        <?php } else { ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">ไม่มีสินค้าในคำสั่งซื้อนี้</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>