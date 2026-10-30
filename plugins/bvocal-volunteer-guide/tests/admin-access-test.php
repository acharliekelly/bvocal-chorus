<?php
/** Run with: php plugins/bvocal-volunteer-guide/tests/admin-access-test.php */

$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-guide-admin.php' );
foreach ( array( "'edit_others_pages'", "'manage_options'", "admin_post_bvocal_guide_sync", "wp_verify_nonce" ) as $required ) {
	if ( false === strpos( $source, $required ) ) { fwrite( STDERR, "FAIL: Missing guide access control: {$required}\n" ); exit( 1 ); }
}
echo "PASS: guide admin access controls\n";
