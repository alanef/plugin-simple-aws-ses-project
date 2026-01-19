<?php
/**
 * Plugin Name: Simple AWS SES
 * Plugin URI: https://fw9.uk/simple-aws-ses
 * Description: Send WordPress emails through Amazon SES
 * Version: 1.1.0
 * Author: Alan Fuller
 * License: GPL v2 or later
 * Text Domain: simple-aws-ses
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'SIMPLE_AWS_SES_VERSION', '1.1.0' );
define( 'SIMPLE_AWS_SES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SIMPLE_AWS_SES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load Composer autoloader
if ( file_exists( SIMPLE_AWS_SES_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once SIMPLE_AWS_SES_PLUGIN_DIR . 'vendor/autoload.php';
}

// Initialize the plugin
add_action(
	'plugins_loaded',
	function () {
		\SimpleAwsSes\Plugin::getInstance();
	}
);

// Activation hook
register_activation_hook(
	__FILE__,
	function () {
		// Add default options
		add_option(
			'simple_aws_ses_settings',
			array(
				'aws_access_key' => '',
				'aws_secret_key' => '',
				'aws_region'     => 'us-east-1',
				'from_email'     => get_option( 'admin_email' ),
				'from_name'      => get_bloginfo( 'name' ),
			)
		);
	}
);

// Deactivation hook
register_deactivation_hook(
	__FILE__,
	function () {
		// Clean up if necessary
	}
);
