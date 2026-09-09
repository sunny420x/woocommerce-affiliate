<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function sendLineNotification($msg) {
    $access_token = trim((string) get_option('LINE_channel_access_token', ''));
    $to = trim((string) get_option('LINE_recipient_id', ''));
    $msg = trim((string) $msg);

    if ($access_token === '') {
        return new WP_Error('line_missing_access_token', 'LINE Channel Access Token is not configured.');
    }

    if ($msg === '') {
        return new WP_Error('line_missing_message', 'The LINE notification message cannot be empty.');
    }

    $endpoint = $to === ''
        ? 'https://api.line.me/v2/bot/message/broadcast'
        : 'https://api.line.me/v2/bot/message/push';

    $payload = array(
        'messages' => array(
            array(
                'type' => 'text',
                'text' => $msg,
            ),
        ),
    );

    if ($to !== '') {
        $payload['to'] = $to;
    }

    $response = wp_remote_post(
        $endpoint,
        array(
            'timeout' => 15,
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type'  => 'application/json',
            ),
            'body' => wp_json_encode($payload),
        )
    );

    if (is_wp_error($response)) {
        return $response;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code < 200 || $status_code >= 300) {
        return new WP_Error(
            'line_api_error',
            'LINE API returned an error.',
            array(
                'status_code' => $status_code,
                'body' => wp_remote_retrieve_body($response),
            )
        );
    }

    return true;
}