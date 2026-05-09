<?php

namespace Fullworks\SimpleSetupForAmazonSes\Admin;

defined( 'ABSPATH' ) || exit;

use Fullworks\SimpleSetupForAmazonSes\Credentials;
use Fullworks\SimpleSetupForAmazonSes\Email\SesSender;

class SettingsPage {

	private $options;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'addPluginPage' ) );
		add_action( 'admin_init', array( $this, 'initSettings' ) );
		add_action( 'wp_ajax_fssfas_test_email', array( $this, 'handleTestEmail' ) );
	}

	public function addPluginPage() {
		add_options_page(
			esc_html__( 'Fullworks Simple Setup for Amazon SES', 'fullworks-simple-setup-for-amazon-ses' ),
			esc_html__( 'Fullworks SES', 'fullworks-simple-setup-for-amazon-ses' ),
			'manage_options',
			'fullworks-simple-setup-for-amazon-ses',
			array( $this, 'createAdminPage' )
		);
	}

	public function createAdminPage() {
		$this->options = get_option( 'fssfas_settings' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Fullworks Simple Setup for Amazon SES', 'fullworks-simple-setup-for-amazon-ses' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'fssfas_group' );
				do_settings_sections( 'fssfas-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function initSettings() {
		register_setting(
			'fssfas_group',
			'fssfas_settings',
			array( $this, 'sanitize' )
		);

		add_settings_section(
			'fssfas_credentials',
			esc_html__( 'AWS Credentials', 'fullworks-simple-setup-for-amazon-ses' ),
			array( $this, 'printSectionInfo' ),
			'fssfas-settings'
		);

		add_settings_field(
			'aws_access_key',
			esc_html__( 'AWS Access Key ID', 'fullworks-simple-setup-for-amazon-ses' ),
			array( $this, 'awsAccessKeyCallback' ),
			'fssfas-settings',
			'fssfas_credentials'
		);

		add_settings_field(
			'aws_secret_key',
			esc_html__( 'AWS Secret Access Key', 'fullworks-simple-setup-for-amazon-ses' ),
			array( $this, 'awsSecretKeyCallback' ),
			'fssfas-settings',
			'fssfas_credentials'
		);

		add_settings_field(
			'aws_region',
			esc_html__( 'AWS Region', 'fullworks-simple-setup-for-amazon-ses' ),
			array( $this, 'awsRegionCallback' ),
			'fssfas-settings',
			'fssfas_credentials'
		);

		add_settings_section(
			'fssfas_sender',
			esc_html__( 'Sender Settings', 'fullworks-simple-setup-for-amazon-ses' ),
			array( $this, 'printSenderSectionInfo' ),
			'fssfas-settings'
		);

		add_settings_field(
			'from_email',
			esc_html__( 'From Email', 'fullworks-simple-setup-for-amazon-ses' ),
			array( $this, 'fromEmailCallback' ),
			'fssfas-settings',
			'fssfas_sender'
		);

		add_settings_field(
			'from_name',
			esc_html__( 'From Name', 'fullworks-simple-setup-for-amazon-ses' ),
			array( $this, 'fromNameCallback' ),
			'fssfas-settings',
			'fssfas_sender'
		);

		add_settings_section(
			'fssfas_test',
			esc_html__( 'Test Email', 'fullworks-simple-setup-for-amazon-ses' ),
			array( $this, 'printTestSectionInfo' ),
			'fssfas-settings'
		);

		add_settings_field(
			'test_email',
			esc_html__( 'Send Test Email', 'fullworks-simple-setup-for-amazon-ses' ),
			array( $this, 'testEmailCallback' ),
			'fssfas-settings',
			'fssfas_test'
		);
	}

	public function sanitize( $input ) {
		$new_input = array();
		$existing  = get_option( 'fssfas_settings' );
		if ( ! is_array( $existing ) ) {
			$existing = array();
		}

		// When a credential is defined via constant, the input field is disabled
		// and not submitted — preserve whatever is in the DB rather than blanking it.
		if ( Credentials::isAccessKeyDefined() ) {
			$new_input['aws_access_key'] = $existing['aws_access_key'] ?? '';
		} elseif ( isset( $input['aws_access_key'] ) ) {
			$new_input['aws_access_key'] = sanitize_text_field( $input['aws_access_key'] );
		}

		if ( Credentials::isSecretKeyDefined() ) {
			$new_input['aws_secret_key'] = $existing['aws_secret_key'] ?? '';
		} elseif ( isset( $input['aws_secret_key'] ) ) {
			$new_input['aws_secret_key'] = sanitize_text_field( $input['aws_secret_key'] );
		}

		if ( Credentials::isRegionDefined() ) {
			$new_input['aws_region'] = $existing['aws_region'] ?? '';
		} elseif ( isset( $input['aws_region'] ) ) {
			$new_input['aws_region'] = sanitize_text_field( $input['aws_region'] );
		}

		if ( isset( $input['from_email'] ) ) {
			$new_input['from_email'] = sanitize_email( $input['from_email'] );
		}

		if ( isset( $input['from_name'] ) ) {
			$new_input['from_name'] = sanitize_text_field( $input['from_name'] );
		}

		return $new_input;
	}

	public function printSectionInfo() {
		echo esc_html__( 'Enter your AWS credentials below. You can find these in your AWS Console under IAM.', 'fullworks-simple-setup-for-amazon-ses' );
		echo '<p class="description">';
		echo wp_kses(
			__( 'Credentials can also be defined as PHP constants in <code>wp-config.php</code> (<code>FSSFAS_ACCESS_KEY_ID</code>, <code>FSSFAS_SECRET_ACCESS_KEY</code>, <code>FSSFAS_REGION</code>). When defined, the matching field below is locked and the constant value is used.', 'fullworks-simple-setup-for-amazon-ses' ),
			array( 'code' => array() )
		);
		echo '</p>';
	}

	private function definedNotice() {
		return ' <span class="description"><em>'
			. wp_kses(
				__( 'Defined in <code>wp-config.php</code>.', 'fullworks-simple-setup-for-amazon-ses' ),
				array( 'code' => array() )
			)
			. '</em></span>';
	}

	public function printSenderSectionInfo() {
		echo esc_html__( 'Configure the default sender information for emails.', 'fullworks-simple-setup-for-amazon-ses' );
	}

	public function awsAccessKeyCallback() {
		$defined  = Credentials::isAccessKeyDefined();
		$value    = $defined ? Credentials::accessKey() : ( $this->options['aws_access_key'] ?? '' );
		$disabled = $defined ? ' disabled="disabled"' : '';
		printf(
			'<input type="text" id="aws_access_key" name="fssfas_settings[aws_access_key]" value="%s" class="regular-text"%s />',
			esc_attr( $value ),
			$disabled // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
		if ( $defined ) {
			echo $this->definedNotice(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	public function awsSecretKeyCallback() {
		$defined  = Credentials::isSecretKeyDefined();
		// Never echo the actual secret value back into the page when locked.
		$value    = $defined ? '••••••••' : ( $this->options['aws_secret_key'] ?? '' );
		$type     = $defined ? 'text' : 'password';
		$disabled = $defined ? ' disabled="disabled"' : '';
		printf(
			'<input type="%s" id="aws_secret_key" name="fssfas_settings[aws_secret_key]" value="%s" class="regular-text"%s />',
			esc_attr( $type ),
			esc_attr( $value ),
			$disabled // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
		if ( $defined ) {
			echo $this->definedNotice(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	public function awsRegionCallback() {
		$regions = array(
			'us-east-1'      => __( 'US East (N. Virginia)', 'fullworks-simple-setup-for-amazon-ses' ),
			'us-east-2'      => __( 'US East (Ohio)', 'fullworks-simple-setup-for-amazon-ses' ),
			'us-west-1'      => __( 'US West (N. California)', 'fullworks-simple-setup-for-amazon-ses' ),
			'us-west-2'      => __( 'US West (Oregon)', 'fullworks-simple-setup-for-amazon-ses' ),
			'eu-west-1'      => __( 'EU (Ireland)', 'fullworks-simple-setup-for-amazon-ses' ),
			'eu-west-2'      => __( 'EU (London)', 'fullworks-simple-setup-for-amazon-ses' ),
			'eu-west-3'      => __( 'EU (Paris)', 'fullworks-simple-setup-for-amazon-ses' ),
			'eu-central-1'   => __( 'EU (Frankfurt)', 'fullworks-simple-setup-for-amazon-ses' ),
			'ap-southeast-1' => __( 'Asia Pacific (Singapore)', 'fullworks-simple-setup-for-amazon-ses' ),
			'ap-southeast-2' => __( 'Asia Pacific (Sydney)', 'fullworks-simple-setup-for-amazon-ses' ),
			'ap-northeast-1' => __( 'Asia Pacific (Tokyo)', 'fullworks-simple-setup-for-amazon-ses' ),
			'ap-northeast-2' => __( 'Asia Pacific (Seoul)', 'fullworks-simple-setup-for-amazon-ses' ),
			'ap-south-1'     => __( 'Asia Pacific (Mumbai)', 'fullworks-simple-setup-for-amazon-ses' ),
			'sa-east-1'      => __( 'South America (São Paulo)', 'fullworks-simple-setup-for-amazon-ses' ),
		);

		$defined        = Credentials::isRegionDefined();
		$current_region = $defined ? Credentials::region() : ( $this->options['aws_region'] ?? 'us-east-1' );
		$disabled       = $defined ? ' disabled="disabled"' : '';

		printf(
			'<select id="aws_region" name="fssfas_settings[aws_region]"%s>',
			$disabled // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
		foreach ( $regions as $key => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $key ),
				selected( $current_region, $key, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
		if ( $defined ) {
			echo $this->definedNotice(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	public function fromEmailCallback() {
		printf(
			'<input type="email" id="from_email" name="fssfas_settings[from_email]" value="%s" class="regular-text" />',
			isset( $this->options['from_email'] ) ? esc_attr( $this->options['from_email'] ) : ''
		);
		echo '<p class="description">' . esc_html__( 'This email must be verified in AWS SES.', 'fullworks-simple-setup-for-amazon-ses' ) . '</p>';
	}

	public function fromNameCallback() {
		printf(
			'<input type="text" id="from_name" name="fssfas_settings[from_name]" value="%s" class="regular-text" />',
			isset( $this->options['from_name'] ) ? esc_attr( $this->options['from_name'] ) : ''
		);
	}

	public function printTestSectionInfo() {
		echo esc_html__( 'Send a test email to verify your configuration is working correctly.', 'fullworks-simple-setup-for-amazon-ses' );
	}

	public function testEmailCallback() {
		?>
		<input type="email" id="test_email_address" placeholder="<?php echo esc_attr__( 'your-email@example.com', 'fullworks-simple-setup-for-amazon-ses' ); ?>" class="regular-text" />
		<button type="button" class="button" id="send_test_email"><?php esc_html_e( 'Send Test Email', 'fullworks-simple-setup-for-amazon-ses' ); ?></button>
		<span id="test_email_result"></span>

		<?php
		$fssfas_l10n = array(
			'promptEmail' => __( 'Please enter an email address', 'fullworks-simple-setup-for-amazon-ses' ),
			'sending'     => __( 'Sending…', 'fullworks-simple-setup-for-amazon-ses' ),
			'success'     => __( '✓ Test email sent successfully!', 'fullworks-simple-setup-for-amazon-ses' ),
			/* translators: %s: error message returned from AWS SES. */
			'failedFmt'   => __( '✗ Failed: %s', 'fullworks-simple-setup-for-amazon-ses' ),
		);
		?>
		<script>
		jQuery(document).ready(function($) {
			var fssfasL10n = <?php echo wp_json_encode( $fssfas_l10n ); ?>;

			$('#send_test_email').click(function() {
				var email = $('#test_email_address').val();
				if (!email) {
					alert(fssfasL10n.promptEmail);
					return;
				}

				$('#test_email_result').text(fssfasL10n.sending);

				$.post(ajaxurl, {
					action: 'fssfas_test_email',
					email: email,
					nonce: <?php echo wp_json_encode( wp_create_nonce( 'fssfas_test' ) ); ?>
				}, function(response) {
					var $result = $('#test_email_result').empty();
					if (response.success) {
						$result.append($('<span/>', { css: { color: 'green' }, text: fssfasL10n.success }));
					} else {
						$result.append($('<span/>', {
							css: { color: 'red' },
							text: fssfasL10n.failedFmt.replace('%s', String(response.data))
						}));
					}
				});
			});
		});
		</script>
		<?php
	}

	public function handleTestEmail() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'fssfas_test' ) ) {
			wp_send_json_error( __( 'Invalid security token.', 'fullworks-simple-setup-for-amazon-ses' ) );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', 'fullworks-simple-setup-for-amazon-ses' ) );
		}

		if ( ! isset( $_POST['email'] ) ) {
			wp_send_json_error( __( 'Email address required.', 'fullworks-simple-setup-for-amazon-ses' ) );
		}

		$to = sanitize_email( wp_unslash( $_POST['email'] ) );
		if ( ! is_email( $to ) ) {
			wp_send_json_error( __( 'Invalid email address.', 'fullworks-simple-setup-for-amazon-ses' ) );
		}

		if ( ! Credentials::isConfigured() ) {
			wp_send_json_error( __( 'AWS credentials are not configured.', 'fullworks-simple-setup-for-amazon-ses' ) );
		}

		$subject  = __( 'Fullworks Simple Setup for Amazon SES Test Email', 'fullworks-simple-setup-for-amazon-ses' );
		$message  = __( 'This is a test email from your WordPress site using the Fullworks Simple Setup for Amazon SES plugin.', 'fullworks-simple-setup-for-amazon-ses' );
		$message .= "\n\n";
		$message .= __( 'If you received this email, your AWS SES configuration is working correctly!', 'fullworks-simple-setup-for-amazon-ses' );
		$message .= "\n\n";
		/* translators: %s: site name. */
		$message .= sprintf( __( 'Site: %s', 'fullworks-simple-setup-for-amazon-ses' ), get_bloginfo( 'name' ) );
		$message .= "\n";
		/* translators: %s: site URL. */
		$message .= sprintf( __( 'URL: %s', 'fullworks-simple-setup-for-amazon-ses' ), get_bloginfo( 'url' ) );

		// Bypass wp_mail() so we exercise SES directly. wp_mail() would happily
		// fall back to the default mailer if SES failed and report success,
		// which is the exact misleading behaviour this test is meant to detect.
		$sender = new SesSender();
		$sent   = $sender->send( $to, $subject, $message );

		if ( $sent ) {
			wp_send_json_success( __( 'Test email sent via AWS SES.', 'fullworks-simple-setup-for-amazon-ses' ) );
		}

		$err = $sender->getLastError();
		wp_send_json_error(
			sprintf(
				/* translators: %s: SES error detail. */
				__( 'SES send failed: %s', 'fullworks-simple-setup-for-amazon-ses' ),
				'' !== $err ? $err : __( 'unknown error (enable WP_DEBUG for details)', 'fullworks-simple-setup-for-amazon-ses' )
			)
		);
	}
}
