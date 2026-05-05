<?php

namespace SimpleAwsSes\Admin;

use SimpleAwsSes\Credentials;

class SettingsPage {

	private $options;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'addPluginPage' ) );
		add_action( 'admin_init', array( $this, 'initSettings' ) );
		add_action( 'wp_ajax_simple_aws_ses_test_email', array( $this, 'handleTestEmail' ) );
	}

	public function addPluginPage() {
		add_options_page(
			'Simple AWS SES Settings',
			'Simple AWS SES',
			'manage_options',
			'simple-aws-ses',
			array( $this, 'createAdminPage' )
		);
	}

	public function createAdminPage() {
		$this->options = get_option( 'simple_aws_ses_settings' );
		?>
		<div class="wrap">
			<h1>Simple AWS SES Settings</h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'simple_aws_ses_group' );
				do_settings_sections( 'simple-aws-ses-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function initSettings() {
		register_setting(
			'simple_aws_ses_group',
			'simple_aws_ses_settings',
			array( $this, 'sanitize' )
		);

		add_settings_section(
			'simple_aws_ses_credentials',
			'AWS Credentials',
			array( $this, 'printSectionInfo' ),
			'simple-aws-ses-settings'
		);

		add_settings_field(
			'aws_access_key',
			'AWS Access Key ID',
			array( $this, 'awsAccessKeyCallback' ),
			'simple-aws-ses-settings',
			'simple_aws_ses_credentials'
		);

		add_settings_field(
			'aws_secret_key',
			'AWS Secret Access Key',
			array( $this, 'awsSecretKeyCallback' ),
			'simple-aws-ses-settings',
			'simple_aws_ses_credentials'
		);

		add_settings_field(
			'aws_region',
			'AWS Region',
			array( $this, 'awsRegionCallback' ),
			'simple-aws-ses-settings',
			'simple_aws_ses_credentials'
		);

		add_settings_section(
			'simple_aws_ses_sender',
			'Sender Settings',
			array( $this, 'printSenderSectionInfo' ),
			'simple-aws-ses-settings'
		);

		add_settings_field(
			'from_email',
			'From Email',
			array( $this, 'fromEmailCallback' ),
			'simple-aws-ses-settings',
			'simple_aws_ses_sender'
		);

		add_settings_field(
			'from_name',
			'From Name',
			array( $this, 'fromNameCallback' ),
			'simple-aws-ses-settings',
			'simple_aws_ses_sender'
		);

		add_settings_section(
			'simple_aws_ses_test',
			'Test Email',
			array( $this, 'printTestSectionInfo' ),
			'simple-aws-ses-settings'
		);

		add_settings_field(
			'test_email',
			'Send Test Email',
			array( $this, 'testEmailCallback' ),
			'simple-aws-ses-settings',
			'simple_aws_ses_test'
		);
	}

	public function sanitize( $input ) {
		$new_input = array();
		$existing  = get_option( 'simple_aws_ses_settings' );
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
		echo 'Enter your AWS credentials below. You can find these in your AWS Console under IAM.';
		echo '<p class="description">Credentials can also be defined as PHP constants in <code>wp-config.php</code> (<code>SIMPLE_AWS_SES_ACCESS_KEY_ID</code>, <code>SIMPLE_AWS_SES_SECRET_ACCESS_KEY</code>, <code>SIMPLE_AWS_SES_REGION</code>). When defined, the matching field below is locked and the constant value is used.</p>';
	}

	private function definedNotice() {
		return ' <span class="description"><em>Defined in <code>wp-config.php</code>.</em></span>';
	}

	public function printSenderSectionInfo() {
		echo 'Configure the default sender information for emails.';
	}

	public function awsAccessKeyCallback() {
		$defined  = Credentials::isAccessKeyDefined();
		$value    = $defined ? Credentials::accessKey() : ( $this->options['aws_access_key'] ?? '' );
		$disabled = $defined ? ' disabled="disabled"' : '';
		printf(
			'<input type="text" id="aws_access_key" name="simple_aws_ses_settings[aws_access_key]" value="%s" class="regular-text"%s />',
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
			'<input type="%s" id="aws_secret_key" name="simple_aws_ses_settings[aws_secret_key]" value="%s" class="regular-text"%s />',
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
			'us-east-1'      => 'US East (N. Virginia)',
			'us-east-2'      => 'US East (Ohio)',
			'us-west-1'      => 'US West (N. California)',
			'us-west-2'      => 'US West (Oregon)',
			'eu-west-1'      => 'EU (Ireland)',
			'eu-west-2'      => 'EU (London)',
			'eu-west-3'      => 'EU (Paris)',
			'eu-central-1'   => 'EU (Frankfurt)',
			'ap-southeast-1' => 'Asia Pacific (Singapore)',
			'ap-southeast-2' => 'Asia Pacific (Sydney)',
			'ap-northeast-1' => 'Asia Pacific (Tokyo)',
			'ap-northeast-2' => 'Asia Pacific (Seoul)',
			'ap-south-1'     => 'Asia Pacific (Mumbai)',
			'sa-east-1'      => 'South America (São Paulo)',
		);

		$defined        = Credentials::isRegionDefined();
		$current_region = $defined ? Credentials::region() : ( $this->options['aws_region'] ?? 'us-east-1' );
		$disabled       = $defined ? ' disabled="disabled"' : '';

		printf(
			'<select id="aws_region" name="simple_aws_ses_settings[aws_region]"%s>',
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
			'<input type="email" id="from_email" name="simple_aws_ses_settings[from_email]" value="%s" class="regular-text" />',
			isset( $this->options['from_email'] ) ? esc_attr( $this->options['from_email'] ) : ''
		);
		echo '<p class="description">This email must be verified in AWS SES.</p>';
	}

	public function fromNameCallback() {
		printf(
			'<input type="text" id="from_name" name="simple_aws_ses_settings[from_name]" value="%s" class="regular-text" />',
			isset( $this->options['from_name'] ) ? esc_attr( $this->options['from_name'] ) : ''
		);
	}

	public function printTestSectionInfo() {
		echo 'Send a test email to verify your configuration is working correctly.';
	}

	public function testEmailCallback() {
		?>
		<input type="email" id="test_email_address" placeholder="your-email@example.com" class="regular-text" />
		<button type="button" class="button" id="send_test_email">Send Test Email</button>
		<span id="test_email_result"></span>
		
		<script>
		jQuery(document).ready(function($) {
			$('#send_test_email').click(function() {
				var email = $('#test_email_address').val();
				if (!email) {
					alert('Please enter an email address');
					return;
				}
				
				$('#test_email_result').html('Sending...');
				
				$.post(ajaxurl, {
					action: 'simple_aws_ses_test_email',
					email: email,
					nonce: '<?php echo esc_js( wp_create_nonce( 'simple_aws_ses_test' ) ); ?>'
				}, function(response) {
					if (response.success) {
						$('#test_email_result').html('<span style="color: green;">✓ Test email sent successfully!</span>');
					} else {
						$('#test_email_result').html('<span style="color: red;">✗ Failed: ' + response.data + '</span>');
					}
				});
			});
		});
		</script>
		<?php
	}

	public function handleTestEmail() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'simple_aws_ses_test' ) ) {
			wp_send_json_error( 'Invalid security token' );
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		if ( ! isset( $_POST['email'] ) ) {
			wp_send_json_error( 'Email address required' );
		}

		$to = sanitize_email( wp_unslash( $_POST['email'] ) );
		if ( ! is_email( $to ) ) {
			wp_send_json_error( 'Invalid email address' );
		}

		$subject  = 'Simple AWS SES Test Email';
		$message  = 'This is a test email from your WordPress site using Simple AWS SES plugin.';
		$message .= "\n\n";
		$message .= 'If you received this email, your AWS SES configuration is working correctly!';
		$message .= "\n\n";
		$message .= 'Site: ' . get_bloginfo( 'name' );
		$message .= "\n";
		$message .= 'URL: ' . get_bloginfo( 'url' );

		$result = wp_mail( $to, $subject, $message );

		if ( $result ) {
			wp_send_json_success( 'Test email sent successfully' );
		} else {
			wp_send_json_error( 'Failed to send test email. Please check your settings and error logs.' );
		}
	}
}