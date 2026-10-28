<?php
/**
 * Regression checks for publication-safe RSVP provisioning.
 *
 * Run with: php plugins/bvocal-gig-rsvp/tests/publication-safety-test.php
 */

$source = file_get_contents( dirname( __DIR__ ) . '/bvocal-gig-rsvp.php' );

foreach (
	array(
		"add_action( 'wp_after_insert_post', array( \$this, 'provision_after_event_save' )",
		"const META_ERROR",
		'public function provision_after_event_save',
		'update_post_meta( $event_id, self::META_ERROR',
	) as $expected
) {
	if ( false === strpos( $source, $expected ) ) {
		fwrite( STDERR, "Publication-safe RSVP provisioning is missing: $expected\n" );
		exit( 1 );
	}
}

foreach (
	array(
		'RSVP needs attention.',
		'Create a Google Form for this Gig',
		'paste its responder link into the Event description',
		'Retry MailerLite RSVP',
	) as $expected
) {
	if ( false === strpos( $source, $expected ) ) {
		fwrite( STDERR, "Dashboard fallback guidance is missing: $expected\n" );
		exit( 1 );
	}
}

echo "Publication-safety structure checks passed.\n";
