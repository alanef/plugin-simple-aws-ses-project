<?php

namespace SimpleAwsSes;

class Plugin {

	private static $instance = null;

	public static function getInstance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->init();
	}

	private function init() {
		// Initialize admin
		if ( is_admin() ) {
			new Admin\SettingsPage();
		}

		// Initialize email handler
		new Email\MailHandler();
	}
}
