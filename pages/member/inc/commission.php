<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="card card-custom p-4" id="commission">
    <h5 class="fw-bold mb-3"><i class="fa-solid fa-table me-2"></i>ตารางอัตราคอมมิชชั่น (Commission Table)</h5>
    <table class="table">
        <thead>
            <th>หมวดหมู่สินค้า</th>
            <th>อัตราคอมมิชชั่น (%)</th>
        </thead>
        <tbody>
            <?php
            $args = array(
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
            );

            $product_categories = get_terms($args);

            if ( ! empty($product_categories) && ! is_wp_error($product_categories) ) {
                foreach ( $product_categories as $category ) {
                    $commission = get_option('commission_by_slug_'.str_replace(" ", "_", $category->name), 10);
                    if($commission != 0) {
            ?>
            <tr>
                <td><a href="/product-category/<?=strtolower(str_replace(' ', '-',$category->name))?>" target="_blank"><?=$category->name?></a></td>
                <td><?=$commission?> %</td>
            </tr>
            <?php
                    }
                }
            }
            ?>
        </tbody>
    </table>
</div>