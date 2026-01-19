=== Simple AWS SES ===
Contributors: alanfuller
Tags: email, aws, ses, smtp
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send WordPress emails through Amazon SES (Simple Email Service) with easy configuration.

== Description ==

Simple AWS SES replaces the default WordPress email function with Amazon SES, ensuring reliable email delivery for your WordPress site.

Features:
* Easy configuration through WordPress admin
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

== Frequently Asked Questions ==

= What permissions does my AWS IAM user need? =

Your IAM user needs the `ses:SendEmail` and `ses:SendRawEmail` permissions.

= My emails are not being sent. What should I check? =

1. Verify your AWS credentials are correct
2. Ensure your sender email is verified in AWS SES
3. Check if you're still in the SES sandbox (new accounts have sending limits)
4. Review your WordPress error logs for specific error messages

== Changelog ==

= 1.1.0 =
* Fixed HTML email detection - emails with common HTML tags are now correctly sent as text/html

= 1.0.0 =
* Initial release