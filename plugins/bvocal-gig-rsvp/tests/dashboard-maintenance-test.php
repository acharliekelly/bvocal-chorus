<?php
/** Run: php plugins/bvocal-gig-rsvp/tests/dashboard-maintenance-test.php */

$source = file_get_contents( dirname( __DIR__ ) . '/bvocal-gig-rsvp.php' );
foreach ( array( 'dashboard_events', 'sort_events', 'bvocal_rsvp_confirm_delete_group', 'confirm_upcoming_group_deletion', 'Delete group…', 'Delete RSVP group' ) as $required ) {
	if ( false === strpos( $source, $required ) ) { fwrite( STDERR, "FAIL: Missing dashboard maintenance behavior: {$required}\n" ); exit( 1 ); }
}
echo "PASS: Gig RSVP dashboard maintenance structure\n";
