<?php
/**
 * Run inside WordPress:
 * wp eval-file wp-content/plugins/bvocal-repertoire/tests/media-validation-test.php
 */

function repertoire_validation_expect( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

$pdf_url = 'https://drive.google.com/file/d/10p03St2Zah_FfFOr2AXeh8HozgCglviQ/preview?rm=minimal';
$audio_url = 'https://drive.google.com/file/d/1mEd6EBQwVR97BO_HryG_OdgPgym6LHgX/view?usp=drive_link';

repertoire_validation_expect( true === BVOCAL_Repertoire::validate_media_url( $pdf_url, 'application/pdf' ), 'A catalogued PDF is valid as a score.' );
repertoire_validation_expect( true === BVOCAL_Repertoire::validate_media_url( $audio_url, 'audio' ), 'A catalogued Drive recording is valid across its share-link variants.' );
repertoire_validation_expect( is_wp_error( BVOCAL_Repertoire::validate_media_url( $pdf_url, 'audio' ) ), 'A PDF cannot be used as a recording.' );
repertoire_validation_expect( is_wp_error( BVOCAL_Repertoire::validate_media_url( 'https://drive.google.com/drive/folders/abc123', 'audio' ) ), 'A Drive folder cannot be used as a recording.' );

fwrite( STDOUT, "PASS: media validation\n" );
