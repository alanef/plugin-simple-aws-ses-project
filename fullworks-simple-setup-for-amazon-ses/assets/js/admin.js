( function ( $, settings ) {
	'use strict';

	$( function () {
		var $button = $( '#fssfas-send-test-email' );
		var $input = $( '#fssfas-test-email-address' );
		var $result = $( '#fssfas-test-email-result' );

		if ( ! $button.length ) {
			return;
		}

		$button.on( 'click', function () {
			var email = $.trim( $input.val() );

			if ( ! email ) {
				window.alert( settings.promptEmail );
				return;
			}

			$result.text( settings.sending );

			$.post( settings.ajaxUrl, {
				action: 'fssfas_test_email',
				email: email,
				nonce: settings.nonce
			} ).done( function ( response ) {
				$result.empty();

				if ( response && response.success ) {
					$result.append( $( '<span/>', {
						'class': 'fssfas-result-ok',
						text: settings.success
					} ) );
				} else {
					var detail = response && typeof response.data !== 'undefined' ? String( response.data ) : '';
					$result.append( $( '<span/>', {
						'class': 'fssfas-result-error',
						text: settings.failedFmt.replace( '%s', detail )
					} ) );
				}
			} ).fail( function () {
				$result.empty().append( $( '<span/>', {
					'class': 'fssfas-result-error',
					text: settings.failedFmt.replace( '%s', settings.requestFailed )
				} ) );
			} );
		} );
	} );
}( jQuery, window.fssfasAdmin || {} ) );
