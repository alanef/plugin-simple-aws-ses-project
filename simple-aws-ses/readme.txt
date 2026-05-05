=== Simple AWS SES ===
Contributors: alanfuller
Tags: email, aws, ses, smtp
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 8.2
Stable tag: 1.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send WordPress emails through Amazon SES (Simple Email Service) with easy configuration.

== Description ==

Simple AWS SES replaces the default WordPress email function with Amazon SES, ensuring reliable email delivery for your WordPress site.

Features:
* Easy configuration through WordPress admin
* Optional credential configuration via wp-config.php constants (12-factor / env-var friendly)
* Supports all standard WordPress emails
* Test email functionality
* Secure credential storage
* Support for HTML and plain text emails

== Installation ==

1. Upload the `simple-aws-ses` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > Simple AWS SES to configure your AWS credentials
4. Enter your AWS Access Key ID, Secret Access Key, and select your AWS region
5. Configure your sender email address (must be verified in AWS SES)
6. Send a test email to verify everything is working

== Configuration ==

Before using this plugin, you need to:

1. Have an AWS account
2. Set up Amazon SES in your preferred region
3. Verify your sender email address or domain in AWS SES
4. Create an IAM user with SES sending permissions
5. Generate Access Keys for the IAM user

== Defining Credentials in wp-config.php ==

Instead of storing AWS credentials in the database via the settings UI, you can define them as PHP constants in `wp-config.php`. This is the recommended approach for production sites and works well with environment variables on managed hosts.

Add the following before the `/* That's all, stop editing! */` line in `wp-config.php`:

`
define( 'SIMPLE_AWS_SES_ACCESS_KEY_ID',     getenv( 'SIMPLE_AWS_SES_ACCESS_KEY_ID' ) ?: '' );
define( 'SIMPLE_AWS_SES_SECRET_ACCESS_KEY', getenv( 'SIMPLE_AWS_SES_SECRET_ACCESS_KEY' ) ?: '' );
define( 'SIMPLE_AWS_SES_REGION',            getenv( 'SIMPLE_AWS_SES_REGION' ) ?: 'us-east-1' );
`

Each constant is independent — you can define one, two, or all three. Any constant that is defined takes precedence over the value saved in the settings page, and the matching field in the admin UI is locked while the constant is in effect.

== Frequently Asked Questions ==

= What permissions does my AWS IAM user need? =

Your IAM user needs the `ses:SendEmail` and `ses:SendRawEmail` permissions.

= My emails are not being sent. What should I check? =

1. Verify your AWS credentials are correct
2. Ensure your sender email is verified in AWS SES
3. Check if you're still in the SES sandbox (new accounts have sending limits)
4. Review your WordPress error logs for specific error messages

== Changelog ==

= 1.2.1 =
* Test email button now sends via AWS SES directly instead of `wp_mail()`, so it no longer reports success when SES has actually failed and WordPress fell back to the default mailer.
* AWS errors (code, message, request ID) are now surfaced in the test email response and written to `debug.log` when `WP_DEBUG` is enabled. SES failures during normal `wp_mail()` flow are also logged before the WordPress default mailer takes over.

= 1.2.0 =
* Added support for defining AWS credentials via PHP constants in wp-config.php (`SIMPLE_AWS_SES_ACCESS_KEY_ID`, `SIMPLE_AWS_SES_SECRET_ACCESS_KEY`, `SIMPLE_AWS_SES_REGION`). When a constant is defined, the matching field in the settings UI is locked.

= 1.1.0 =
* Fixed HTML email detection - emails with common HTML tags are now correctly sent as text/html

= 1.0.0 =
* Initial release