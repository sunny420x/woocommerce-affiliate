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

$referral_origin = $order->get_meta('_wc_order_attribution_referrer', true);
if (!empty($referral_origin)) {
    $referral_url = wp_parse_url($referral_origin);
    $referral_origin = $referral_url['host'] ?? $referral_origin;
    $referral_origin = preg_replace('/^www\./i', '', $referral_origin);
} else {
    $referral_origin = 'Direct';
}

$affiliate_rows = getTransactionByOrderId($order_id);
$order_items = $order->get_items();
?>
<div class="card card-custom p-4 mb-4" id="order-detail">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-2">
        <div>
            <h5 class="fw-bold mb-1"><i class="fa-solid fa-receipt text-primary me-2"></i>รายละเอียดคำสั่งซื้อ #<?= esc_html($order->get_id()); ?></h5>
            <div class="text-muted small">
                วันที่สั่งซื้อ: <?= esc_html(date_i18n('d/m/Y H:i', strtotime($order->get_date_created() ?? "-"))); ?><br>
                ชื่อลูกค้า: <?= esc_html($customer_name); ?><br>
                อีเมล์: <?= esc_html($customer_email); ?><br>
                Origin (Referral): <?= esc_html($referral_origin); ?>
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
                                $order_item = null;
                                foreach ($order_items as $current_order_item) {
                                    if ($current_order_item->get_product_id() == $item->product_id || $current_order_item->get_variation_id() == $item->product_id) {
                                        $order_item = $current_order_item;
                                        break;
                                    }
                                }

                                $quantity = 0;
                                if ($order_item) {
                                    $quantity = $order_item->get_quantity();
                                }

                                $product_name = $product ? $product->get_title() : ($order_item ? $order_item->get_name() : 'สินค้า');
                                $product_price = $product ? (float) $product->get_price() : ($order_item ? (float) $order_item->get_total() / max(1, $quantity) : 0);
                                $product_image = $product ? $product->get_image(array(48, 48)) : '';
                                $commission_value = ((float) $item->commission_percentage / 100) * $product_price;
                                $total_sold_sum += $product_price * $quantity;
                                $total_earns_sum += $commission_value;
                            ?>
                                <tr>
                                    <td>
                                        <?=$product_image ?>
                                    </td>
                                    <td>
                                        <?=esc_html($product_name) ?>
                                    </td>
                                    <td class="text-start"><?=number_format($quantity)?></td>
                                    <td class="text-center"><?=number_format($product_price, 2)?> บาท</td>
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