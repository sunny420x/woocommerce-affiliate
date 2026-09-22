<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
<div class="content-header">
    <p class="text-white mb-0"><a class="text-white me-2" href="/affiliate"><i class="fa-solid fa-chevron-left me-3"></i></a>ตารางอัตราคอมมิชชั่น (Commission Table)</p>
</div>
<div class="bg-white p-4" id="commission">
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