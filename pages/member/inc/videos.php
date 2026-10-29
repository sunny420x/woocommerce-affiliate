<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

global $wpdb;
$video_notice = '';
$video_notice_type = 'success';

if (isset($_POST['submit_video_link'])) {
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'aff_submit_video')) {
        $video_notice = 'เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง';
        $video_notice_type = 'danger';
    } else {
        $result = affiliate_submit_video_link($user_id, wp_unslash($_POST['video_link'] ?? ''));
        if (is_wp_error($result)) {
            $video_notice = $result->get_error_message();
            $video_notice_type = 'danger';
        } else {
            $video_notice = 'ส่งลิงก์วิดีโอเรียบร้อยแล้ว';
        }
    }
}

$videos = $wpdb->get_results($wpdb->prepare(
    "SELECT id, video_link, created_at FROM {$wpdb->prefix}affiliate_video_links WHERE user_id = %d ORDER BY id DESC",
    $user_id
));
?>
<div class="content-header">
    <p class="text-white mb-0"><a class="text-white me-2" href="/affiliate"><i class="fa-solid fa-chevron-left me-3"></i></a>ส่งงาน (Video Submission)</p>
</div>
<div class="bg-white p-4" id="videos">
    <h5 class="fw-bold mb-3"><i class="fa-solid fa-video text-primary me-2"></i>ส่งลิงก์วิดีโอ</h5>

    <?php if ($video_notice): ?>
        <div class="alert alert-<?= esc_attr($video_notice_type); ?>"><?= esc_html($video_notice); ?></div>
    <?php endif; ?>

    <form method="post" class="mb-4">
        <?php wp_nonce_field('aff_submit_video'); ?>
        <label for="video_link" class="form-label">วางลิงก์วิดีโอจาก Social Media</label>
        <div class="input-group">
            <input type="url" class="form-control" id="video_link" name="video_link" maxlength="500"
                placeholder="https://www.youtube.com/watch?v=..." required>
            <button type="submit" name="submit_video_link" class="btn btn-primary">
                <i class="fa-solid fa-paper-plane me-1"></i>ส่งงาน
            </button>
        </div>
        <div class="form-text">รองรับลิงก์วิดีโอจาก YouTube, TikTok, Facebook และ Instagram (ต้องขึ้นต้นด้วย https://)</div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>ส่งเมื่อ</th>
                    <th>แพลตฟอร์ม</th>
                    <th>ลิงก์วิดีโอ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($videos)): ?>
                    <?php foreach ($videos as $video): ?>
                        <tr>
                            <td><?= esc_html(date('d/m/Y H:i', strtotime($video->created_at))); ?></td>
                            <td><?= esc_html(affiliate_detect_video_platform($video->video_link)); ?></td>
                            <td><a href="<?= esc_url($video->video_link); ?>" target="_blank" rel="noopener noreferrer"><?= esc_html($video->video_link); ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">ยังไม่มีการส่งงาน</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
