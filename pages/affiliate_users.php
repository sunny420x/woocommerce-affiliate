<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
if(isset($_GET['action'])) {
    if($_GET['action'] == "verify") {
        if(isset($_GET['user_id'])) {
            $user_id = sanitize_text_field($_GET['user_id']);
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}users_affiliate_info SET verified = 1 WHERE user_id = %d", $user_id));
            wp_redirect( "/wp-admin/admin.php?page=affiliate&option=affiliate_users&action=profile&user_id=$user_id&status=success" );
        }
    }
    if($_GET['action'] == "unverify") {
        if(isset($_GET['user_id'])) {
            $user_id = sanitize_text_field($_GET['user_id']);
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}users_affiliate_info SET verified = 0 WHERE user_id = %d", $user_id));
            wp_redirect( "/wp-admin/admin.php?page=affiliate&option=affiliate_users&action=profile&user_id=$user_id&status=success" );
        }
    }

    if($_GET['action'] == "suspend") {
        if(isset($_GET['user_id'])) {
            $user_id = sanitize_text_field($_GET['user_id']);
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}users_affiliate_info SET suspended = 1 WHERE user_id = %d", $user_id));
            wp_redirect( "/wp-admin/admin.php?page=affiliate&option=affiliate_users&action=profile&user_id=$user_id&status=success" );
        }
    }
    if($_GET['action'] == "unsuspend") {
        if(isset($_GET['user_id'])) {
            $user_id = sanitize_text_field($_GET['user_id']);
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}users_affiliate_info SET suspended = 0 WHERE user_id = %d", $user_id));
            wp_redirect( "/wp-admin/admin.php?page=affiliate&option=affiliate_users&action=profile&user_id=$user_id&status=success" );
        }
    }


    // แสดง Profile ของ User Affiliate
    if($_GET['action'] == "profile") {
        if(isset($_GET['user_id'])) {
            $user_id = sanitize_text_field($_GET['user_id']);
            $profile = $wpdb->get_row(
                $wpdb->prepare("SELECT a.verified, a.suspended, a.bank_name, a.bank_account_number, u.ID, u.display_name, u.user_email, u.refCode, a.full_name, a.phone_number,
                a.social_media_01, a.social_media_02, a.social_media_03, a.social_media_04, 
                a.social_media_01_type, a.social_media_02_type, a.social_media_03_type, a.social_media_04_type
                FROM {$wpdb->prefix}users as u 
                LEFT JOIN {$wpdb->prefix}users_affiliate_info as a ON a.user_id = u.ID 
                WHERE u.ID = %d", $user_id)
            );
            $doc_url = get_user_meta($user_id, 'affiliate_identity_doc', true);
?>
<h1>ข้อมูลพันธมิตรผู้ใช้งาน Affiliate: <?=$profile->display_name?></h1>
<div style="padding: 25px 25px 25px 25px;">
    <table class="widefat fixed striped">
        <tbody>
            <tr>
                <th><strong>ชื่อที่แสดงในระบบ:</strong></th>
                <td><?=$profile->display_name?>
                <?php
                if($profile->suspended) {
                ?>
                <span style="color: red;">(บัญชีถูกระงับการใช้งาน)</span>
                <?php } ?>
                </td>
            </tr>
            <tr>
                <th><strong>ชื่อ-นามสกุล:</strong></th>
                <td><?=$profile->full_name?></td>
            </tr>
            <tr>
                <th><strong>เบอร์โทรศัพท์:</strong></th>
                <td><?=$profile->phone_number?></td>
            </tr>
            <tr>
                <th><strong>สถานะ:</strong> <?php if($profile->verified == 1) {?><span class="badge success">ยืนยันตัวตนแล้ว</span><?php } else {?><span class="badge danger">ยังไม่ได้ยืนยันตัวตน</span><?php } ?></th>
                <td>
                <?php 
                if($profile->verified == 1) {?>
                <button class="button button-outline-primary button-small" onclick="window.location.href='/wp-admin/admin.php?page=affiliate&option=affiliate_users&action=unverify&user_id=<?=$user_id?>'">ยกเลิกการยืนยัน</button>
                <?php } else { ?>
                <button class="button button-outline-primary button-small" onclick="window.location.href='/wp-admin/admin.php?page=affiliate&option=affiliate_users&action=verify&user_id=<?=$user_id?>'">ยืนยันตัวตน</button>
                <?php } ?>

                <?php 
                if($profile->suspended == 1) {?>
                <button class="button button-outline-primary button-small" onclick="window.location.href='/wp-admin/admin.php?page=affiliate&option=affiliate_users&action=unsuspend&user_id=<?=$user_id?>'">ยกเลิกการระงับ</button>
                <?php } else { ?>
                <button class="button button-outline-primary button-small" onclick="window.location.href='/wp-admin/admin.php?page=affiliate&option=affiliate_users&action=suspend&user_id=<?=$user_id?>'">ระงับบัญชี</button>
                <?php } ?>
                </td>
            </tr>
            <tr>
                <th><strong>Email:</strong></th>
                <td><?=$profile->user_email?></td>
            </tr>
            <tr>
                <th><strong>ช่องทางรับค่าคอมมิชชั่น:</strong></th>
                <td><?=$profile->bank_name?> - <?=$profile->bank_account_number?></td>
            </tr>
            <tr>
                <th><strong>ช่องทางการเผยแพร่:</strong></th>
                <td>
                    <?=$profile->social_media_01_type?>: <a href="<?=$profile->social_media_01?>" target="_blank"><?=$profile->social_media_01?></a><br>
                    <?=$profile->social_media_02_type?>: <a href="<?=$profile->social_media_02?>" target="_blank"><?=$profile->social_media_02?></a><br>
                    <?=$profile->social_media_03_type?>: <a href="<?=$profile->social_media_03?>" target="_blank"><?=$profile->social_media_03?></a><br>
                    <?=$profile->social_media_04_type?>: <a href="<?=$profile->social_media_04?>" target="_blank"><?=$profile->social_media_04?></a><br>
                </td>
            </tr>
        </tbody>
    </table>
    <h2>หลักฐานการยืนยันตัวตน</h2>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <?php
    if($doc_url != "") {
        if (is_array($doc_url)) {
            foreach ($doc_url as $index => $url) {
            ?>
            <img src="<?=esc_url($url)?>" alt="รูปภาพยืนยันตัวตนรูปที่ <?=$index + 1?>" width="100%">
            <?php
            }
        } else {
            ?>
            <img src="<?=esc_url($doc_url)?>" alt="รูปภาพยืนยันตัวตน" width="100%">
            <?php
        }
    } else {
    ?>
    <div class="badge pending" style="width: max-content">ยังไม่ได้อัพโหลดเอกสาร</div>
    <?php
    }
    ?>
    </div>
</div>
<?php
            return;
        }
    }
}
?>
<h1>พันธมิตรผู้ใช้งาน Affiliate</h1>
<div style="padding: 25px 25px 25px 25px;">
<table class="widefat fixed striped">
    <thead>
        <th>#</th>
        <th>Username</th>
        <th>สถานะ</th>
        <th>refCode</th>
        <th>จัดการ</th>
    </thead>
    <tbody>
        <?php
        $users = $wpdb->get_results("SELECT u.refCode, u.display_name, u.ID, a.verified 
        FROM {$wpdb->prefix}users as u 
        LEFT JOIN {$wpdb->prefix}users_affiliate_info as a ON a.user_id = u.ID 
        WHERE u.refCode IS NOT NULL ORDER BY u.ID DESC");

        foreach($users as $user) {
        ?>
        <tr>
            <td><?=$user->ID?></td>
            <td><?=$user->display_name?></td>
            <td><?php if($user->verified == 1) {?><span class="badge success">ยืนยันตัวตนแล้ว</span><?php } else {?><span class="badge danger">ยังไม่ได้ยืนยันตัวตน</span><?php } ?></td>
            <td><?=$user->refCode?></td>
            <td>
                <button class="button button-outline-primary" onclick="window.location.href='/wp-admin/admin.php?page=affiliate&option=affiliate_users&action=profile&user_id=<?=$user->ID?>'">ดูโปรไฟล์</button>
            </td>
        </tr>
        <?php
        }
        ?>
    </tbody>
</table>
</div>