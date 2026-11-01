<?php
/** Run: php plugins/bvocal-gig-rsvp/tests/dashboard-maintenance-test.php */

$source = file_get_contents( dirname( __DIR__ ) . '/bvocal-gig-rsvp.php' );
foreach ( array( 'dashboard_events', 'sort_events', 'tribe_events_cat', 'rehearsal', 'enqueue_admin_styles', 'admin.css', 'view=actions', 'render_actions_view', 'More actions', 'No one has RSVP’d yet', 'people have RSVP’d', 'sort-indicator', 'Action' ) as $required ) {
	if ( false === strpos( $source, $required ) ) { fwrite( STDERR, "FAIL: Missing dashboard maintenance behavior: {$required}\n" ); exit( 1 ); }
}
echo "PASS: Gig RSVP dashboard maintenance structure\n";
