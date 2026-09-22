<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<h1>📝 เนื้อหา (Contents)</h1>
<div style="padding: 0 25px 25px 25px;">
    <div>
        <button onclick="togglePage('affiliate_condition_content');">เงื่อนไขการจ่ายค่าตอบแทน</button>
        <button onclick="togglePage('affiliate_support_page_content');">หน้าช่วยเหลือของพันธมิตร</button>
        <button onclick="togglePage('affiliate_requirements_and_conditions_content');">คุณสมบัติและเงื่อนไขของผู้สมัคร</button>
        <button onclick="togglePage('affiliate_policy_content');">นโยบายของพันธมิตร</button>
        <button onclick="togglePage('affiliate_instructions_content');">คำแนะนำสำหรับพันธมิตร</button>
    </div>
    <form action="options.php" method="post" id="affiliate_content_form ">
        <?php
        settings_fields('affiliate_content_settings_group');
        ?>
        <div id="affiliate_condition_content">
            <h3 for="affiliate_condition"><strong>เงื่อนไขการจ่ายค่าตอบแทน:</strong></h3>
            <p>เงื่อนไขการจ่ายค่าตอบแทน เช่น จ่ายค่าตอบแทนเมื่อยอดรวม 500 บาท หรือ จ่ายค่าตอบแทนทุก ๆ วันที่ 5 ของเดือน เป็นต้น</p>
            <?php
            wp_editor( get_option('affiliate_condition', ''), 'affiliate_condition', array(
                'textarea_name' => 'affiliate_condition', // The 'name' attribute for the form submission
                'textarea_rows' => 15,                      // Number of visible rows
                'media_buttons' => true,                   // Show "Add Media" buttons
            ));
            ?>
        </div>
        <div id="affiliate_support_page_content">
            <h3 for="affiliate_support_page"><strong>เนื้อหาในหน้าช่วยเหลือของพันธมิตร:</strong></h3>
            <p>เนื้อหา HTML นี้จะแสดงในหน้าช่วยเหลือของพันธมิตร ควรประกอบด้วย คำถามที่พบบ่อย ช่องทางการติดต่อ และอื่น ๆ</p>
            <?php
            wp_editor( get_option('affiliate_support_page', ''), 'affiliate_support_page', array(
                'textarea_name' => 'affiliate_support_page', // The 'name' attribute for the form submission
                'textarea_rows' => 15,                      // Number of visible rows
                'media_buttons' => true,                   // Show "Add Media" buttons
            ));
            ?>
        </div>
        <div id="affiliate_requirements_and_conditions_content">
            <h3 for="affiliate_requirements_and_conditions"><strong>คุณสมบัติและเงื่อนไขของผู้สมัคร:</strong></h3>
            <p>เนื้อหา HTML นี้จะแสดงในหน้าคุณสมบัติและเงื่อนไขของผู้สมัคร ควรประกอบด้วย ข้อกำหนดและเงื่อนไขต่าง ๆ</p>
            <?php
            wp_editor( get_option('affiliate_requirements_and_conditions', ''), 'affiliate_requirements_and_conditions', array(
                'textarea_name' => 'affiliate_requirements_and_conditions', // The 'name' attribute for the form submission
                'textarea_rows' => 15,                      // Number of visible rows
                'media_buttons' => true,                   // Show "Add Media" buttons
            ));
            ?>
        </div>
        <div id="affiliate_policy_content">
            <h3 for="affiliate_policy"><strong>นโยบายของพันธมิตร:</strong></h3>
            <p>เนื้อหา HTML นี้จะแสดงในหน้านโยบายของพันธมิตร ควรประกอบด้วย ข้อกำหนดและเงื่อนไขต่าง ๆ</p>
            <?php
            wp_editor( get_option('affiliate_policy', ''), 'affiliate_policy', array(
                'textarea_name' => 'affiliate_policy', // The 'name' attribute for the form submission
                'textarea_rows' => 15,                      // Number of visible rows
                'media_buttons' => true,                   // Show "Add Media" buttons
            ));
            ?>
        </div>
        <div id="affiliate_instructions_content">
            <h3 for="affiliate_instructions"><strong>คำแนะนำสำหรับพันธมิตร:</strong></h3>
            <p>เนื้อหา HTML นี้จะแสดงในหน้าคำแนะนำสำหรับพันธมิตร ควรประกอบด้วย คำแนะนำและวิธีการต่าง ๆ</p>
            <?php
            wp_editor( get_option('affiliate_instructions', ''), 'affiliate_instructions', array(
                'textarea_name' => 'affiliate_instructions', // The 'name' attribute for the form submission
                'textarea_rows' => 15,                      // Number of visible rows
                'media_buttons' => true,                   // Show "Add Media" buttons
            ));
            ?>
        </div>
        <br>
        <?php submit_button('บันทึกการเปลี่ยนแปลง'); ?>
    </form>
    <script>
        function togglePage(id) {
            var affiliate_content_form = document.getElementById('affiliate_content_form');
            Array.from(affiliate_content_form.elements).forEach(element => {
                element.style.display = "none"; // Hide all elements within the form initially
            });

            if (document.getElementById(id).style.display === "none") {
                document.getElementById(id).style.display = "block";
            }
        }
    </script>
</div>