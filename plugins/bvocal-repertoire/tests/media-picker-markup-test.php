<?php
/**
 * Run inside WordPress:
 * wp eval-file wp-content/plugins/bvocal-repertoire/tests/media-picker-markup-test.php
 */

function repertoire_picker_expect( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

$markup = BVOCAL_Repertoire::media_picker_button( 'bvocal-repertoire-score', 'application/pdf' );

repertoire_picker_expect( false !== strpos( $markup, 'bvocal-media-picker' ), 'The picker button has the media-picker hook.' );
repertoire_picker_expect( false !== strpos( $markup, 'data-target="bvocal-repertoire-score"' ), 'The picker button identifies the URL field it fills.' );
repertoire_picker_expect( false !== strpos( $markup, 'data-media-type="application/pdf"' ), 'The score picker requests PDF media.' );

fwrite( STDOUT, "PASS: media picker markup\n" );
