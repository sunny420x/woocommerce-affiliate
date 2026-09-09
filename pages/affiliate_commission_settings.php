<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<h1>💵 กำหนด % Commission ตามประเภทสินค้า</h1>
<div style="padding: 0 25px 25px 25px;">
    <p>หากสินค้ามีหลายหมวดหมู่ในชิ้นเดียว ระบบจะเลือก % Commission ที่สูงที่สุดจากหมวดหมู่ที่กำหนดในสินค้านั้น ๆ</p>
    <form action="options.php" method="post">
    <?php
    settings_fields('affiliate_commission_settings_group');
    ?>
    <div style="height: 650px; overflow: auto;">
        <table class="widefat fixed striped">
            <thead>
                <th>ประเภทสินค้า</th>
                <th>% Commission</th>
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
                ?>
                <tr>
                    <td><?=$category->name?></td>
                    <td><input type="number" name="commission_by_slug_<?=str_replace(" ", "_", $category->name)?>" value="<?=get_option('commission_by_slug_'.str_replace(" ", "_", $category->name), 10)?>"> %</td>
                </tr>
                <?php
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
    <br>
    <input type="submit" class="button button-primary" name="setCommissionByProductCategory" value="บันทึกการเปลี่ยนแปลง">
</div>