<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<h1>📝 เนื้อหา (Contents)</h1>
<div style="padding: 0 25px 25px 25px;">
    <form action="options.php" method="post">
        <?php
        settings_fields('affiliate_content_settings_group');
        ?>
        <h3 for="affiliate_condition"><strong>เงื่อนไขการจ่ายค่าตอบแทน:</strong></h3>
        <p>เงื่อนไขการจ่ายค่าตอบแทน เช่น จ่ายค่าตอบแทนเมื่อยอดรวม 500 บาท หรือ จ่ายค่าตอบแทนทุก ๆ วันที่ 5 ของเดือน เป็นต้น</p>
        <?php
        wp_editor( get_option('affiliate_condition', ''), 'affiliate_condition', array(
            'textarea_name' => 'affiliate_condition', // The 'name' attribute for the form submission
            'textarea_rows' => 15,                      // Number of visible rows
            'media_buttons' => true,                   // Show "Add Media" buttons
        ));
        ?>
        <br>
        <h3 for="affiliate_support_page"><strong>เนื้อหาในหน้าช่วยเหลือของพันธมิตร:</strong></h3>
        <p>เนื้อหา HTML นี้จะแสดงในหน้าช่วยเหลือของพันธมิตร ควรประกอบด้วย คำถามที่พบบ่อย ช่องทางการติดต่อ และอื่น ๆ</p>
        <?php
        wp_editor( get_option('affiliate_support_page', ''), 'affiliate_support_page', array(
            'textarea_name' => 'affiliate_support_page', // The 'name' attribute for the form submission
            'textarea_rows' => 15,                      // Number of visible rows
            'media_buttons' => true,                   // Show "Add Media" buttons
        ));
        ?>
        <h3 for="affiliate_support_page"><strong>คุณสมบัติและเงื่อนไขของผู้สมัคร:</strong></h3>
        <?php
        wp_editor( get_option('affiliate_requirements_and_conditions', ''), 'affiliate_requirements_and_conditions', array(
            'textarea_name' => 'affiliate_requirements_and_conditions', // The 'name' attribute for the form submission
            'textarea_rows' => 15,                      // Number of visible rows
            'media_buttons' => true,                   // Show "Add Media" buttons
        ));
        ?>
        <?php submit_button('บันทึกการเปลี่ยนแปลง'); ?>
    </form>
</div>