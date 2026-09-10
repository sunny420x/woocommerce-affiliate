<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<h1>🤝🏻 WooCommerce | Affiliate Program</h1>
<div style="padding: 25px 25px 25px 25px;">
    <span>ระบบ Affiliate กระตุ้นการขายบนเว็บไซต์ โดยการให้เปอร์เซ็น Affiliate Partner เป็นจำนวน <?= get_option('affiliate_commission', 10); ?>% ของยอดขายสินค้า</span>
    <form action="options.php" method="post">
        <?php
        settings_fields('affiliate_settings_group');
        ?>
        <label for="affiliate_enable"><strong>เปิดใช้งานระบบพันธมิตร:</strong> </label>
        <select name="affiliate_enable">
            <option value="yes" <?php if(esc_attr(get_option('affiliate_enable', 'yes')) == 'yes') { echo "selected"; } ?>>เปิดใช้งาน</option>
            <option value="no" <?php if(esc_attr(get_option('affiliate_enable', 'yes')) == 'no') { echo "selected"; } ?>>ปิดใช้งาน</option>
        </select>
        <br>
        <h2>เอกสาร (Documents)</h2>
        <label for="affiliate_logo"><strong>ลิงค์รูปภาพ Logo บริษัท (สำหรับออกรายงาน):</strong></label><br>
        <div class="image-upload-wrapper">
            <input type="text" name="affiliate_logo" id="affiliate_logo" style="width: 400px;" value="<?php echo esc_attr(get_option('affiliate_logo', ''))?>"/>

            <button type="button" class="button" id="upload_image_button">เลือกรูปภาพ...</button>

            <div id="image_preview" style="margin-top: 10px;">
                <?php 
                $affiliate_logo = get_option('affiliate_logo');
                if ($affiliate_logo): ?>
                    <img src="<?php echo esc_url($affiliate_logo); ?>" style="max-width: 300px; border: 1px solid #ccc;" />
                <?php endif; ?>
            </div>
        </div>
        <br>
        <h2>ค่าเริ่มต้น (Default Variables)</h2>
        <label for="affiliate_commission"><strong>% Commission เริ่มต้น:</strong> </label><input type="number" name="affiliate_commission"
            value="<?= esc_attr(get_option('affiliate_commission', 10)); ?>" /> %
        <p>* การอัพเดท % Commission จะไม่มีผลย้อนหลังกับข้อมูลการขายเดิมในระบบ แต่จะมีผลกับข้อมูลการขายใหม่ที่จะถูกเพิ่มเข้ามาหลังจากอัพเดท</p>

        <h2>LINE API Settings</h2>
        <label for="LINE_recipient_id">LINE Recipient ID (User/Group/Room):</label>
        <input type="text" name="LINE_recipient_id" value="<?= esc_attr(get_option('LINE_recipient_id')); ?>" />
        <br>
        <br>
        <label for="LINE_channel_access_token">LINE Channel Access Token:</label>
        <input type="text" name="LINE_channel_access_token" value="<?= esc_attr(get_option('LINE_channel_access_token')); ?>" />
        <br>
        <br>
        <label for="LINE_channel_secret">LINE Channel Secret:</label>
        <input type="text" name="LINE_channel_secret" value="<?= esc_attr(get_option('LINE_channel_secret')); ?>" />

        <h2>Gmail API Settings</h2>
        <label for="GMAIL_sender_email">Gmail Sender Email:</label>
        <input type="text" name="GMAIL_sender_email" value="<?= esc_attr(get_option('GMAIL_sender_email')); ?>" />
        <br>
        <br>
        <label for="GMAIL_access_token">Gmail Access Token:</label>
        <input type="text" name="GMAIL_access_token" value="<?= esc_attr(get_option('GMAIL_access_token')); ?>" />
        <br>
        <br>
        <label for="GMAIL_refresh_token">Gmail Refresh Token:</label>
        <input type="text" name="GMAIL_refresh_token" value="<?= esc_attr(get_option('GMAIL_refresh_token')); ?>" />
        <br>
        <br>
        <label for="GMAIL_client_id">Gmail Client ID:</label>
        <input type="text" name="GMAIL_client_id" value="<?= esc_attr(get_option('GMAIL_client_id')); ?>" />
        <br>
        <br>
        <label for="GMAIL_client_secret">Gmail Client Secret:</label>
        <input type="text" name="GMAIL_client_secret" value="<?= esc_attr(get_option('GMAIL_client_secret')); ?>" />
        <br>
        <br>
        <label for="GMAIL_access_token_expires_at">Gmail Access Token Expires At</label>
        <input type="text" name="GMAIL_access_token_expires_at" value="<?= esc_attr(get_option('GMAIL_access_token_expires_at')); ?>" />

        <?php submit_button('บันทึกการเปลี่ยนแปลง'); ?>
    </form>
    <script type="text/javascript">
    jQuery(document).ready(function($){
        $('#upload_image_button').click(function(e) {
            e.preventDefault();
            
            // สร้าง Media Frame
            var image_frame = wp.media({
                title: 'เลือกรูปภาพ Logo',
                multiple: false,
                library: { type: 'image' }
            });

            // เมื่อเลือกรูปภาพเสร็จแล้ว
            image_frame.on('select', function() {
                var selection = image_frame.state().get('selection').first().toJSON();
                var image_url = selection.url;

                // 1. เอา URL ไปใส่ใน Input
                $('#affiliate_logo').val(image_url);
                
                // 2. แสดงตัวอย่างรูปภาพ (Preview)
                $('#image_preview').html('<img src="'+image_url+'" style="max-width: 300px; border: 1px solid #ccc;" />');
            });

            image_frame.open();
        });
    });
    </script>
</div>