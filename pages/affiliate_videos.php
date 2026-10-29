<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$videos = $wpdb->get_results("SELECT v.id, v.video_link, v.created_at, u.display_name, u.user_email
FROM {$wpdb->prefix}affiliate_video_links as v
LEFT JOIN {$wpdb->prefix}users as u ON u.ID = v.user_id
ORDER BY v.id DESC");
?>
<h1>งานที่ส่งโดยพันธมิตร</h1>
<div style="padding: 25px;">
<table class="widefat fixed striped">
    <thead>
        <tr>
            <th>ส่งเมื่อ</th>
            <th>ชื่อผู้ใช้งาน</th>
            <th>แพลตฟอร์ม</th>
            <th>ลิงก์วิดีโอ</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($videos)) { foreach ($videos as $video) { ?>
        <tr>
            <td><?= esc_html($video->created_at) ?></td>
            <td><?= esc_html($video->display_name) ?><br><small><?= esc_html($video->user_email) ?></small></td>
            <td><?= esc_html(affiliate_detect_video_platform($video->video_link)) ?></td>
            <td><a href="<?= esc_url($video->video_link) ?>" target="_blank" rel="noopener noreferrer"><?= esc_html($video->video_link) ?></a></td>
        </tr>
        <?php } } else { ?>
        <tr><td colspan="4">ยังไม่มีการส่งงาน</td></tr>
        <?php } ?>
    </tbody>
</table>
</div>
