<?php
/**
 * Tests Drive sharing-link conversion for public repertoire embeds.
 *
 * Run: php plugins/bvocal-repertoire/tests/drive-media-urls-test.php
 */

define( 'ABSPATH', __DIR__ . '/' );
require_once dirname( __DIR__ ) . '/includes/class-repertoire.php';

function repertoire_drive_expect( $actual, $expected, $message ) {
	if ( $actual !== $expected ) {
		fwrite( STDERR, "FAIL: {$message}\nExpected: {$expected}\nActual: {$actual}\n" );
		exit( 1 );
	}
}

$file_id  = '1mEd6EBQwVR97BO_HryG_OdgPgym6LHgX';
$share_url = 'https://drive.google.com/file/d/' . $file_id . '/view?usp=drive_link';

repertoire_drive_expect(
	BVOCAL_Repertoire::drive_preview_url( $share_url ),
	'https://drive.google.com/file/d/' . $file_id . '/preview',
	'A Drive sharing link becomes an embeddable preview URL.'
);
repertoire_drive_expect(
	BVOCAL_Repertoire::drive_audio_url( $share_url ),
	'https://drive.google.com/uc?export=download&id=' . $file_id,
	'A Drive sharing link becomes a browser-playable audio URL.'
);
repertoire_drive_expect(
	BVOCAL_Repertoire::drive_audio_url( 'https://example.org/not-drive.mp3' ),
	'https://example.org/not-drive.mp3',
	'Non-Drive audio URLs remain unchanged.'
);

fwrite( STDOUT, "PASS: Drive media URLs\n" );
