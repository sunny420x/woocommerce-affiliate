<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<h1>ระดับ Commission ตามยอดขาย (Tiered)</h1>
<div style="padding: 0 25px 25px 25px;">
    <form action="options.php" method="post">
        <?php
        settings_fields('affiliate_tiers_settings_group');
        ?>
        <p>กำหนดเกณฑ์และโบนัส % เพิ่มเติมจาก % Commission เริ่มต้น</p>
        <table class="widefat fixed striped">
            <thead>
                <th>เกณฑ์</th>
                <th>โบนัส</th>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <label>เกณฑ์ ขั้นที่ 1 (บาท):</label><br>
                        <input type="number" name="affiliate_tier_threshold_1" value="<?= esc_attr(get_option('affiliate_tier_threshold_1', 10000)); ?>" />
                    </td>
                    <td>
                        <label>โบนัส % ขั้นที่ 1:</label><br>
                        <input type="number" step="0.1" name="affiliate_tier_bonus_1" value="<?= esc_attr(get_option('affiliate_tier_bonus_1', 1)); ?>" />
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>เกณฑ์ ขั้นที่ 2 (บาท):</label><br>
                        <input type="number" name="affiliate_tier_threshold_2" value="<?= esc_attr(get_option('affiliate_tier_threshold_2', 30000)); ?>" />
                    </td>
                    <td>
                        <label>โบนัส % ขั้นที่ 2:</label><br>
                        <input type="number" step="0.1" name="affiliate_tier_bonus_2" value="<?= esc_attr(get_option('affiliate_tier_bonus_2', 2)); ?>" />
                    </td>
                </tr>
                <tr>
                    <td>
                        <label>เกณฑ์ ขั้นที่ 3 (บาท):</label><br>
                        <input type="number" name="affiliate_tier_threshold_3" value="<?= esc_attr(get_option('affiliate_tier_threshold_3', 60000)); ?>" />
                    </td>
                    <td>
                        <label>โบนัส % ขั้นที่ 3:</label><br>
                        <input type="number" step="0.1" name="affiliate_tier_bonus_3" value="<?= esc_attr(get_option('affiliate_tier_bonus_3', 3)); ?>" />
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
    <?php submit_button('บันทึกการเปลี่ยนแปลง'); ?>
</div>