<?php

namespace Fullworks\SimpleSetupForAmazonSes;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves AWS connection settings from PHP constants first, then DB options.
 *
 * Constants are intended to be defined in wp-config.php, typically bridged from
 * environment variables, e.g.:
 *
 *     define( 'FSSFAS_ACCESS_KEY_ID', getenv( 'FSSFAS_ACCESS_KEY_ID' ) ?: '' );
 *     define( 'FSSFAS_SECRET_ACCESS_KEY', getenv( 'FSSFAS_SECRET_ACCESS_KEY' ) ?: '' );
 *     define( 'FSSFAS_REGION', getenv( 'FSSFAS_REGION' ) ?: 'us-east-1' );
 */
class Credentials {

	const CONST_ACCESS_KEY = 'FSSFAS_ACCESS_KEY_ID';
	const CONST_SECRET_KEY = 'FSSFAS_SECRET_ACCESS_KEY';
	const CONST_REGION     = 'FSSFAS_REGION';

	public static function accessKey() {
		return self::resolve( self::CONST_ACCESS_KEY, 'aws_access_key', '' );
	}

	public static function secretKey() {
		return self::resolve( self::CONST_SECRET_KEY, 'aws_secret_key', '' );
	}

	public static function region() {
		return self::resolve( self::CONST_REGION, 'aws_region', 'us-east-1' );
	}

	public static function isAccessKeyDefined() {
		return self::isDefined( self::CONST_ACCESS_KEY );
	}

	public static function isSecretKeyDefined() {
		return self::isDefined( self::CONST_SECRET_KEY );
	}

	public static function isRegionDefined() {
		return self::isDefined( self::CONST_REGION );
	}

	public static function isConfigured() {
		return self::accessKey() !== '' && self::secretKey() !== '';
	}

	private static function resolve( $constant, $option_key, $default ) {
		if ( self::isDefined( $constant ) ) {
			return (string) constant( $constant );
		}

		$options = get_option( 'fssfas_settings' );
		if ( is_array( $options ) && isset( $options[ $option_key ] ) && $options[ $option_key ] !== '' ) {
			return $options[ $option_key ];
		}

		return $default;
	}

	private static function isDefined( $constant ) {
		return defined( $constant ) && constant( $constant ) !== '';
	}
}
