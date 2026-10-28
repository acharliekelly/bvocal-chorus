<?php
/**
 * The Event editor is already wrapped in WordPress's post form. The RSVP
 * control must therefore never add another form inside it.
 *
 * Run with: php plugins/bvocal-gig-rsvp/tests/editor-form-safety-test.php
 */

$source = file_get_contents( dirname( __DIR__ ) . '/bvocal-gig-rsvp.php' );
preg_match( '/private function render_enable_button\(.*?\n\t}\n\n\tpublic function render_dashboard/s', $source, $match );

if ( empty( $match[0] ) || false !== strpos( $match[0], '<form' ) ) {
	fwrite( STDERR, "The Event-editor RSVP control must not render a nested form.\n" );
	exit( 1 );
}

if ( false === strpos( $source, 'name="bvocal_rsvp_requested"' ) ) {
	fwrite( STDERR, "The Event-editor RSVP control must let an editor save RSVP intent with the Event.\n" );
	exit( 1 );
}

echo "Event-editor RSVP control is form-safe.\n";
