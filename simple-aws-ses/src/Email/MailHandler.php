<?php

namespace SimpleAwsSes\Email;

class MailHandler
{
    private $sesSender;

    public function __construct()
    {
        add_filter('pre_wp_mail', [$this, 'interceptMail'], 10, 2);
    }

    public function interceptMail($null, $args)
    {
        error_log('Simple AWS SES: Intercepting email');
        
        // Get options to check if plugin is configured
        $options = get_option('simple_aws_ses_settings');
        
        // If not configured, let WordPress handle it normally
        if (empty($options['aws_access_key']) || empty($options['aws_secret_key'])) {
            error_log('Simple AWS SES: Not configured, skipping');
            return $null;
        }

        // Initialize SES sender
        if (!$this->sesSender) {
            $this->sesSender = new SesSender();
        }

        // Extract mail arguments
        $to = $args['to'];
        $subject = $args['subject'];
        $message = $args['message'];
        $headers = isset($args['headers']) ? $args['headers'] : '';
        $attachments = isset($args['attachments']) ? $args['attachments'] : array();

        error_log('Simple AWS SES: Sending email to: ' . (is_array($to) ? implode(', ', $to) : $to));
        
        // Send via SES
        $sent = $this->sesSender->send($to, $subject, $message, $headers, $attachments);

        // If sent successfully, prevent WordPress from sending again
        if ($sent) {
            error_log('Simple AWS SES: Email sent successfully via SES');
            // Return true to stop WordPress from processing the email
            return true;
        }

        error_log('Simple AWS SES: Failed to send via SES, falling back to default');
        // If failed, let WordPress try its normal method
        return $null;
    }

}