<?php

namespace SimpleAwsSes\Email;

defined( 'ABSPATH' ) || exit;

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;
use SimpleAwsSes\Credentials;

class SesSender {

	private $client;
	private $options;
	private $lastError = '';

	public function __construct() {
		$this->options = get_option( 'simple_aws_ses_settings' );
		$this->initializeClient();
	}

	public function getLastError() {
		return $this->lastError;
	}

	private function initializeClient() {
		if ( ! Credentials::isConfigured() ) {
			$this->lastError = 'AWS credentials are not configured.';
			return;
		}

		try {
			$this->client = new SesClient(
				array(
					'version'     => 'latest',
					'region'      => Credentials::region(),
					'credentials' => array(
						'key'    => Credentials::accessKey(),
						'secret' => Credentials::secretKey(),
					),
				)
			);
		} catch ( \Exception $e ) {
			$this->lastError = 'SES client init failed: ' . $e->getMessage();
			$this->logDebug( $this->lastError );
		}
	}

	public function send( $to, $subject, $message, $headers = '', $attachments = array() ) {
		if ( ! $this->client ) {
			if ( '' === $this->lastError ) {
				$this->lastError = 'SES client not initialized.';
			}
			return false;
		}

		// Parse headers
		$parsed_headers = $this->parseHeaders( $headers );

		// Prepare recipients
		$recipients = is_array( $to ) ? $to : array( $to );

		// Get from email and name
		$from_email = $parsed_headers['from_email'] ?? $this->options['from_email'] ?? get_option( 'admin_email' );
		$from_name  = $parsed_headers['from_name'] ?? $this->options['from_name'] ?? get_bloginfo( 'name' );

		// Build raw email
		$boundary    = uniqid( 'boundary_' );
		$raw_message = $this->buildRawMessage( $from_email, $from_name, $recipients, $subject, $message, $parsed_headers, $attachments, $boundary );

		try {
			$result = $this->client->sendRawEmail(
				array(
					'RawMessage' => array(
						'Data' => $raw_message,
					),
				)
			);

			$this->lastError = '';
			$this->logDebug( 'sendRawEmail succeeded. MessageId=' . $result->get( 'MessageId' ) );
			return true;
		} catch ( AwsException $e ) {
			$this->lastError = sprintf(
				'[%s] %s (request id: %s)',
				$e->getAwsErrorCode() ?: 'unknown',
				$e->getAwsErrorMessage() ?: $e->getMessage(),
				$e->getAwsRequestId() ?: 'n/a'
			);
			$this->logDebug( 'sendRawEmail failed: ' . $this->lastError );
			return false;
		} catch ( \Exception $e ) {
			$this->lastError = $e->getMessage();
			$this->logDebug( 'sendRawEmail error: ' . $this->lastError );
			return false;
		}
	}

	private function logDebug( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[Simple AWS SES] ' . $message );
		}
	}

	private function parseHeaders( $headers ) {
		$parsed = array();

		if ( empty( $headers ) ) {
			return $parsed;
		}

		// Convert headers to array if string
		if ( is_string( $headers ) ) {
			$headers = explode( "\n", $headers );
		}

		foreach ( $headers as $header ) {
			if ( strpos( $header, ':' ) === false ) {
				continue;
			}

			list($name, $value) = explode( ':', $header, 2 );
			$name               = strtolower( trim( $name ) );
			$value              = trim( $value );

			switch ( $name ) {
				case 'from':
					if ( preg_match( '/(.+?)\s*<(.+?)>/', $value, $matches ) ) {
						$parsed['from_name']  = trim( $matches[1], '"\'' );
						$parsed['from_email'] = $matches[2];
					} else {
						$parsed['from_email'] = $value;
					}
					break;

				case 'reply-to':
					$parsed['reply_to'] = array( $value );
					break;

				case 'cc':
					$parsed['cc'] = array_map( 'trim', explode( ',', $value ) );
					break;

				case 'bcc':
					$parsed['bcc'] = array_map( 'trim', explode( ',', $value ) );
					break;

				case 'content-type':
					$parsed['content_type'] = $value;
					break;
			}
		}

		return $parsed;
	}

	private function buildRawMessage( $from_email, $from_name, $recipients, $subject, $message, $headers, $attachments, $boundary ) {
		$raw_message = '';

		// Headers
		$raw_message .= 'From: ' . sprintf( '%s <%s>', $from_name, $from_email ) . "\r\n";
		$raw_message .= 'To: ' . implode( ', ', $recipients ) . "\r\n";
		$raw_message .= 'Subject: ' . $subject . "\r\n";

		// Add CC if present
		if ( ! empty( $headers['cc'] ) ) {
			$raw_message .= 'Cc: ' . implode( ', ', $headers['cc'] ) . "\r\n";
		}

		// Add Reply-To if present
		if ( ! empty( $headers['reply_to'] ) ) {
			$raw_message .= 'Reply-To: ' . implode( ', ', $headers['reply_to'] ) . "\r\n";
		}

		// MIME headers
		$raw_message .= 'MIME-Version: 1.0' . "\r\n";

		// Determine content type
		$content_type = $headers['content_type'] ?? 'text/plain';
		$is_html      = stripos( $content_type, 'text/html' ) !== false || $this->looksLikeHtml( $message );

		if ( ! empty( $attachments ) ) {
			// Multipart message with attachments
			$raw_message .= 'Content-Type: multipart/mixed; boundary="' . $boundary . '"' . "\r\n";
			$raw_message .= "\r\n";

			// Message body
			$raw_message .= '--' . $boundary . "\r\n";
			$raw_message .= 'Content-Type: ' . ( $is_html ? 'text/html' : 'text/plain' ) . '; charset=UTF-8' . "\r\n";
			$raw_message .= 'Content-Transfer-Encoding: base64' . "\r\n";
			$raw_message .= "\r\n";
			$raw_message .= chunk_split( base64_encode( $message ) ) . "\r\n";

			// Add attachments
			foreach ( $attachments as $attachment ) {
				if ( file_exists( $attachment ) ) {
					$filename  = basename( $attachment );
					$file_data = file_get_contents( $attachment );
					$mime_type = mime_content_type( $attachment ) ?: 'application/octet-stream';

					$raw_message .= '--' . $boundary . "\r\n";
					$raw_message .= 'Content-Type: ' . $mime_type . '; name="' . $filename . '"' . "\r\n";
					$raw_message .= 'Content-Transfer-Encoding: base64' . "\r\n";
					$raw_message .= 'Content-Disposition: attachment; filename="' . $filename . '"' . "\r\n";
					$raw_message .= "\r\n";
					$raw_message .= chunk_split( base64_encode( $file_data ) ) . "\r\n";
				}
			}

			$raw_message .= '--' . $boundary . '--' . "\r\n";
		} else {
			// Simple message without attachments
			$raw_message .= 'Content-Type: ' . ( $is_html ? 'text/html' : 'text/plain' ) . '; charset=UTF-8' . "\r\n";
			$raw_message .= 'Content-Transfer-Encoding: base64' . "\r\n";
			$raw_message .= "\r\n";
			$raw_message .= chunk_split( base64_encode( $message ) );
		}

		return $raw_message;
	}

	/**
	 * Check if message content looks like HTML.
	 *
	 * @param string $message The message content to check.
	 * @return bool True if the message appears to contain HTML.
	 */
	private function looksLikeHtml( $message ) {
		// Check for common HTML tags (case-insensitive)
		$html_pattern = '/<(html|body|div|p|br|span|a|strong|em|b|i|u|h[1-6]|ul|ol|li|table|tr|td|th|img|head|style|script)[>\s\/]/i';
		return (bool) preg_match( $html_pattern, $message );
	}
}
