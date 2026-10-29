<?php
/**
 * Run inside WordPress:
 * wp eval-file plugins/bvocal-repertoire/tests/publish-validation-test.php
 */

function repertoire_publish_expect( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

$_POST = array();
$data  = BVOCAL_Repertoire::keep_incomplete_item_as_draft(
	array(
		'post_type'   => 'repertoire_item',
		'post_status' => 'publish',
	),
	array()
);

repertoire_publish_expect( 'publish' === $data['post_status'], 'Programmatic publishing is not blocked when no editor form was submitted.' );
fwrite( STDOUT, "PASS: repertoire publication validation\n" );
