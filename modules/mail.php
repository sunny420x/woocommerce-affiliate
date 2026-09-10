<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Send an HTML email through the Gmail API.
 *
 * Required options: GMAIL_access_token, GMAIL_sender_email.
 * Optional options for automatic access-token renewal:
 * GMAIL_refresh_token, GMAIL_client_id, GMAIL_client_secret,
 * GMAIL_access_token_expires_at.
 *
 * @param string $to          Recipient email address.
 * @param string $subject     Email subject.
 * @param string $html        HTML email body.
 * @param string $from_email  Optional sender override.
 * @return true|WP_Error
 */
function sendTemplateMail($option, $to, $data = null) {
    $html = '';
    $subject = '';

    if($data) {
        if($option == "affiliate_approved") {
            $subject = 'ผลการพิจารณาคำขอเข้าร่วม Affiliate Program';
            $template_html = get_option('html_affiliate_approved');
    
            $html = str_replace('[full_name]', $data['full_name'], $template_html);
        }
    
        if($option == "affiliate_disapproved") {
            $subject = 'ผลการพิจารณาคำขอเข้าร่วม Affiliate Program';
            $template_html = get_option('html_affiliate_disapproved');
    
            $html = str_replace('[full_name]', $data['full_name'], $template_html);
    
        }
    
        if($option == "affiliate_suspended") {
            $subject = 'บัญชี Affiliate ของคุณถูก ระงับการใช้งานชั่วคราว';
            $template_html = get_option('html_affiliate_suspended');
            
            $html = str_replace('[full_name]', $data['full_name'], $template_html);
        }

        if($option == "affiliate_unsuspended") {
            $subject = 'บัญชี Affiliate ของคุณถูกสามารถกลับมาใช้งานได้ตามปกติ';
            $template_html = get_option('affiliate_unsuspended');
            
            $html = str_replace('[full_name]', $data['full_name'], $template_html);
        }
    
        if($option == "affiliate_payments") {
            $subject = 'ผลการแจ้งถอนเงิน Affiliate Program';
            $template_html = get_option('html_affiliate_payments');
            
            $html = str_replace('[full_name]', $data['full_name'], $template_html);
            $html = str_replace('[amount]', $data['amount'], $template_html);
            $html = str_replace('[date]', $data['date'], $template_html);
        }
    
        sendGmailHtmlEmail( $to, $subject, $html);
    } else {
        return new WP_Error('email_template_missing_data', 'sendTemplateMail() function $data attribute can not be empty or null.');
    }
}

function sendGmailHtmlEmail( $to, $subject, $html) {
	$to          = sanitize_email( $to );
	$subject     = trim( (string) $subject );
	$html        = trim( (string) $html );
	$sender      = sanitize_email( get_option( 'GMAIL_sender_email', '' ) );

	if ( ! is_email( $to ) ) {
		return new WP_Error( 'gmail_invalid_recipient', 'A valid recipient email address is required.' );
	}

	if ( $subject === '' ) {
		return new WP_Error( 'gmail_missing_subject', 'The email subject cannot be empty.' );
	}

	if ( $html === '' ) {
		return new WP_Error( 'gmail_missing_message', 'The HTML email body cannot be empty.' );
	}

	if ( ! is_email( $sender ) ) {
		return new WP_Error( 'gmail_missing_sender', 'A valid Gmail sender email is not configured.' );
	}

	$access_token = affiliate_get_gmail_access_token();
	if ( is_wp_error( $access_token ) ) {
		return $access_token;
	}

	$subject_header = '=?UTF-8?B?' . base64_encode( $subject ) . '?=';
	$mime_message   = implode(
		"\r\n",
		array(
			'MIME-Version: 1.0',
			'To: ' . $to,
			'From: ' . $sender,
			'Subject: ' . $subject_header,
			'Content-Type: text/html; charset=UTF-8',
			'Content-Transfer-Encoding: 8bit',
			'',
			$html,
		)
	);

	$response = wp_remote_post(
		'https://gmail.googleapis.com/gmail/v1/users/me/messages/send',
		array(
			'timeout' => 15,
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
				'Content-Type'  => 'application/json',
			),
			'body' => wp_json_encode(
				array(
					'raw' => affiliate_gmail_base64url_encode( $mime_message ),
				)
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$status_code = wp_remote_retrieve_response_code( $response );
	if ( $status_code < 200 || $status_code >= 300 ) {
		return new WP_Error(
			'gmail_api_error',
			'Gmail API returned an error.',
			array(
				'status_code' => $status_code,
				'body'        => wp_remote_retrieve_body( $response ),
			)
		);
	}

	return true;
}

/**
 * Get a usable Gmail access token and renew it when refresh credentials exist.
 *
 * @return string|WP_Error
 */
function affiliate_get_gmail_access_token() {
	$access_token  = trim( (string) get_option( 'GMAIL_access_token', '' ) );
	$expires_at    = (int) get_option( 'GMAIL_access_token_expires_at', 0 );
	$refresh_token = trim( (string) get_option( 'GMAIL_refresh_token', '' ) );

	if ( $access_token !== '' && ( $expires_at === 0 || $expires_at > ( time() + 60 ) ) ) {
		return $access_token;
	}

	$client_id     = trim( (string) get_option( 'GMAIL_client_id', '' ) );
	$client_secret = trim( (string) get_option( 'GMAIL_client_secret', '' ) );
	if ( $refresh_token === '' || $client_id === '' || $client_secret === '' ) {
		return new WP_Error(
			'gmail_missing_credentials',
			'Gmail access token is not configured or has expired.'
		);
	}

	$response = wp_remote_post(
		'https://oauth2.googleapis.com/token',
		array(
			'timeout' => 15,
			'body'    => array(
				'client_id'     => $client_id,
				'client_secret' => $client_secret,
				'refresh_token' => $refresh_token,
				'grant_type'    => 'refresh_token',
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$status_code = wp_remote_retrieve_response_code( $response );
	$body        = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( $status_code < 200 || $status_code >= 300 || empty( $body['access_token'] ) ) {
		return new WP_Error(
			'gmail_token_error',
			'Unable to refresh the Gmail access token.',
			array(
				'status_code' => $status_code,
				'body'        => $body,
			)
		);
	}

	$new_access_token = sanitize_text_field( $body['access_token'] );
	update_option( 'GMAIL_access_token', $new_access_token, false );
	update_option(
		'GMAIL_access_token_expires_at',
		time() + max( 0, (int) ( $body['expires_in'] ?? 3600 ) ),
		false
	);

	return $new_access_token;
}

/**
 * Gmail expects base64url without padding for the raw MIME message.
 *
 * @param string $value MIME message.
 * @return string
 */
function affiliate_gmail_base64url_encode( $value ) {
	return rtrim( strtr( base64_encode( $value ), '+/', '-_' ), '=' );
}
