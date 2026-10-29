<?php
/**
 * Run inside WordPress:
 * wp eval-file wp-content/plugins/bvocal-repertoire/tests/recording-preview-test.php
 */

function repertoire_preview_expect( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

$markup = BVOCAL_Repertoire::recording_preview_markup(
	'All parts once through',
	'https://drive.google.com/file/d/1mEd6EBQwVR97BO_HryG_OdgPgym6LHgX/view?usp=drive_link'
);

repertoire_preview_expect( false !== strpos( $markup, '<iframe' ), 'A recording uses a Google Drive preview frame.' );
repertoire_preview_expect( false !== strpos( $markup, '/preview' ), 'The frame uses the Drive preview URL.' );
repertoire_preview_expect( false === strpos( $markup, '<audio' ), 'A recording does not use a direct-download audio element.' );
repertoire_preview_expect( false !== strpos( $markup, 'Open in Google Drive' ), 'The Drive fallback link remains available.' );

fwrite( STDOUT, "PASS: recording preview markup\n" );
