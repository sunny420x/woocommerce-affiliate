<?php
if (!defined('ABSPATH')) {
    exit;
}

function affiliate_create_video_link_table() {
    global $wpdb;
    $charset_collate = "DEFAULT CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci";

    $create_video_link_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}affiliate_video_links (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        video_link VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $create_video_link_table );
}

// สร้างตารางให้ปลั๊กอินที่ติดตั้งไว้ก่อนหน้านี้ (activation hook ไม่ทำงานซ้ำตอนอัปเดต)
add_action('init', 'affiliate_maybe_create_video_table');
function affiliate_maybe_create_video_table() {
    if (get_option('affiliate_video_db_version') !== '1') {
        affiliate_create_video_link_table();
        update_option('affiliate_video_db_version', '1');
    }
}

/**
 * ตรวจสอบและปรับ URL วิดีโอให้เป็นรูปแบบมาตรฐาน
 *
 * @return array|WP_Error ['url' => ลิงก์มาตรฐาน, 'platform' => ชื่อแพลตฟอร์ม]
 */
function affiliate_validate_video_url($raw_url) {
    $raw_url = trim((string) $raw_url);

    if ($raw_url === '') {
        return new WP_Error('empty', 'กรุณาวางลิงก์วิดีโอ');
    }
    if (strlen($raw_url) > 500) {
        return new WP_Error('too_long', 'ลิงก์ยาวเกินไป');
    }

    $parts = wp_parse_url($raw_url);
    if (!$parts || empty($parts['host']) || empty($parts['scheme'])) {
        return new WP_Error('invalid', 'รูปแบบลิงก์ไม่ถูกต้อง');
    }
    if (strtolower($parts['scheme']) !== 'https') {
        return new WP_Error('not_https', 'ลิงก์ต้องขึ้นต้นด้วย https://');
    }

    $host = strtolower($parts['host']);
    $host = preg_replace('/^(www|m|mobile|web)\./', '', $host);
    $path = $parts['path'] ?? '';
    parse_str($parts['query'] ?? '', $query);

    // [แพลตฟอร์ม => [host ที่รองรับ, รูปแบบ path ของลิงก์วิดีโอ]]
    $platforms = [
        'YouTube' => [
            'hosts' => ['youtube.com', 'youtu.be'],
            'path'  => '#^/(watch|shorts/[\w-]{6,}|live/[\w-]{6,}|[\w-]{11})/?$#',
        ],
        'TikTok' => [
            'hosts' => ['tiktok.com', 'vt.tiktok.com', 'vm.tiktok.com'],
            'path'  => '#^/(@[\w.\-]+/video/\d+|[\w-]{6,})/?$#',
        ],
        'Facebook' => [
            'hosts' => ['facebook.com', 'fb.watch'],
            'path'  => '#^/(watch|reel/\d+|[\w.\-]+/videos/\d+|share/[rv]/[\w-]+|[\w-]{6,})/?$#',
        ],
        'Instagram' => [
            'hosts' => ['instagram.com'],
            'path'  => '#^/(reel|reels|p|tv)/[\w-]+/?$#',
        ],
    ];

    foreach ($platforms as $name => $rule) {
        if (!in_array($host, $rule['hosts'], true)) {
            continue;
        }
        if (!preg_match($rule['path'], $path)) {
            return new WP_Error('not_video', "ลิงก์นี้ไม่ใช่ลิงก์วิดีโอของ {$name}");
        }

        $path = rtrim($path, '/');

        // YouTube /watch ต้องมีรหัสวิดีโอ
        if ($name === 'YouTube' && $path === '/watch' && empty($query['v'])) {
            return new WP_Error('not_video', 'ลิงก์ YouTube ไม่มีรหัสวิดีโอ (v=...)');
        }

        // ตัด query/fragment ที่ใช้ติดตามออก เก็บเฉพาะ v ของ YouTube เพื่อใช้ตรวจลิงก์ซ้ำ
        $canonical = 'https://' . $host . $path;
        if ($name === 'YouTube' && $path === '/watch') {
            $canonical .= '?v=' . rawurlencode($query['v']);
        } elseif ($name === 'Facebook' && $path === '/watch' && !empty($query['v'])) {
            $canonical .= '?v=' . rawurlencode($query['v']);
        }

        return ['url' => $canonical, 'platform' => $name];
    }

    return new WP_Error('unsupported', 'รองรับเฉพาะลิงก์จาก YouTube, TikTok, Facebook และ Instagram');
}

function affiliate_detect_video_platform($url) {
    $result = affiliate_validate_video_url($url);
    return is_wp_error($result) ? '-' : $result['platform'];
}

/**
 * บันทึกลิงก์วิดีโอของ affiliate
 *
 * @return true|WP_Error
 */
function affiliate_submit_video_link($user_id, $raw_url) {
    global $wpdb;
    $table = $wpdb->prefix . 'affiliate_video_links';

    $result = affiliate_validate_video_url($raw_url);
    if (is_wp_error($result)) {
        return $result;
    }

    if (strlen($result['url']) > 255) {
        return new WP_Error('too_long', 'ลิงก์ยาวเกินไป');
    }

    $duplicate = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM $table WHERE video_link = %s LIMIT 1",
        $result['url']
    ));
    if ($duplicate !== null) {
        return new WP_Error(
            'duplicate',
            (int) $duplicate === (int) $user_id ? 'คุณเคยส่งลิงก์นี้แล้ว' : 'ลิงก์นี้ถูกส่งเข้าระบบแล้ว'
        );
    }

    $inserted = $wpdb->insert(
        $table,
        [
            'user_id'    => (int) $user_id,
            'video_link' => $result['url'],
            'created_at' => current_time('mysql'),
        ],
        ['%d', '%s', '%s']
    );

    if ($inserted === false) {
        return new WP_Error('db', 'เกิดข้อผิดพลาดในการบันทึกข้อมูล');
    }

    return true;
}
