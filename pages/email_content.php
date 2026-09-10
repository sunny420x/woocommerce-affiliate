2<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<h1>เนื้อหาอีเมล์ (Email Contents)</h1>
<div style="padding: 0 25px 25px 25px;">
    <form action="options.php" method="post">
        <?php
        settings_fields('affiliate_email_content_settings_group');
        ?>
        <h3 for="html_affiliate_approved"><strong>อีเมล์อนุมัติคำขอเป็น Affiliate:</strong></h3>
        <?php
        wp_editor( get_option('html_affiliate_approved', ''), 'html_affiliate_approved', array(
            'textarea_name' => 'html_affiliate_approved', // The 'name' attribute for the form submission
            'textarea_rows' => 15,                      // Number of visible rows
            'media_buttons' => true,                   // Show "Add Media" buttons
        ));
        ?>
        <br>
        <h3 for="html_affiliate_disapproved"><strong>อีเมล์ปฏิเสธคำขอเป็น Affiliate:</strong></h3>
        <?php
        wp_editor( get_option('html_affiliate_disapproved', ''), 'html_affiliate_disapproved', array(
            'textarea_name' => 'html_affiliate_disapproved', // The 'name' attribute for the form submission
            'textarea_rows' => 15,                      // Number of visible rows
            'media_buttons' => true,                   // Show "Add Media" buttons
        ));
        ?>
        <h3 for="html_affiliate_suspended"><strong>อีเมล์ระงับบัญชี Affiliate:</strong></h3>
        <?php
        wp_editor( get_option('html_affiliate_suspended', ''), 'html_affiliate_suspended', array(
            'textarea_name' => 'html_affiliate_suspended', // The 'name' attribute for the form submission
            'textarea_rows' => 15,                      // Number of visible rows
            'media_buttons' => true,                   // Show "Add Media" buttons
        ));
        ?>
        <h3 for="html_affiliate_payments"><strong>อีเมล์แจ้งเตือนผลการถอนเงิน:</strong></h3>
        <?php
        wp_editor( get_option('html_affiliate_payments', ''), 'html_affiliate_payments', array(
            'textarea_name' => 'html_affiliate_payments', // The 'name' attribute for the form submission
            'textarea_rows' => 15,                      // Number of visible rows
            'media_buttons' => true,                   // Show "Add Media" buttons
        ));
        ?>
        <?php submit_button('บันทึกการเปลี่ยนแปลง'); ?>
    </form>
</div>