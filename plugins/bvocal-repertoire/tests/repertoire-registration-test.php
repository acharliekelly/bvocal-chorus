<?php
/**
 * Smoke test for the repertoire content model.
 *
 * Run: php plugins/bvocal-repertoire/tests/repertoire-registration-test.php
 */

define( 'ABSPATH', __DIR__ . '/' );

require_once dirname( __DIR__ ) . '/includes/class-repertoire.php';

function repertoire_expect( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

$definition = BVOCAL_Repertoire::post_type_definition();

repertoire_expect( 'repertoire_item' === $definition['post_type'], 'Repertoire uses a dedicated post type.' );
repertoire_expect( 'Repertoire' === $definition['labels']['name'], 'The admin menu is named Repertoire.' );
repertoire_expect( 'Repertoire Item' === $definition['labels']['singular_name'], 'The singular label is Repertoire Item.' );
repertoire_expect( true === $definition['has_archive'], 'Repertoire has a public index.' );
repertoire_expect( 'repertoire' === $definition['rewrite']['slug'], 'Repertoire uses the /repertoire/ URL.' );

fwrite( STDOUT, "PASS: repertoire registration\n" );
