<?php
/**
 * Lightweight checks for the parts of the RSVP workflow that do not need WordPress.
 *
 * Run with: php plugins/bvocal-gig-rsvp/tests/group-naming-test.php
 */

require_once dirname( __DIR__ ) . '/includes/class-group-naming.php';

function assert_same( $expected, $actual, $message ) {
	if ( $expected !== $actual ) {
		fwrite( STDERR, $message . "\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true ) . "\n" );
		exit( 1 );
	}
}

assert_same(
	'Gig RSVP - Song at City Hall - 2026-11-15',
	BVOCAL_Gig_RSVP_Group_Naming::make( 'Song at City Hall', '2026-11-15' ),
	'Group names should identify both the Gig and its date.'
);

assert_same(
	'Gig RSVP - Untitled Gig - Date TBD',
	BVOCAL_Gig_RSVP_Group_Naming::make( '', '' ),
	'Group names should remain usable while an event is still being drafted.'
);

echo "Group naming checks passed.\n";
