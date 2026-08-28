/**
 * Simple LMS - admin helpers.
 */
( function () {
	'use strict';

	// Toggle the uninstall-data warning styling when the checkbox changes.
	document.addEventListener( 'change', function ( event ) {
		var input = event.target;
		if (
			input &&
			input.name === 'slms_settings[delete_data_on_uninstall]' &&
			input.checked
		) {
			input.closest( 'label' ).classList.add( 'slms-danger' );
		} else if (
			input &&
			input.name === 'slms_settings[delete_data_on_uninstall]'
		) {
			input.closest( 'label' ).classList.remove( 'slms-danger' );
		}
	} );
} )();
