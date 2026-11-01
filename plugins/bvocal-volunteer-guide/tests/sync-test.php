<?php
/** Run with WP-CLI eval-file. */

require_once dirname( __DIR__ ) . '/includes/class-guide-source.php';
require_once dirname( __DIR__ ) . '/includes/class-guide-markdown.php';
require_once dirname( __DIR__ ) . '/includes/class-guide-sync.php';

BVOCAL_Guide_Sync::register_post_type();
$source = BVOCAL_Guide_Source::parse_document( 'plugins/bvocal-test/KB.md', "# Test guide\n\nFirst version." );
$result = BVOCAL_Guide_Sync::sync( array( $source ) );
if ( 1 !== $result['created'] ) { throw new RuntimeException( 'New guide was not created.' ); }
$post = get_page_by_path( 'test-guide', OBJECT, 'bvocal_guide' );
if ( ! $post || '1' !== get_post_meta( $post->ID, '_bvocal_guide_active', true ) ) { throw new RuntimeException( 'Guide is not active.' ); }
$unchanged = BVOCAL_Guide_Sync::sync( array( $source ) );
if ( 1 !== $unchanged['unchanged'] ) { throw new RuntimeException( 'Unchanged guide was rewritten.' ); }
$changed = BVOCAL_Guide_Source::parse_document( 'plugins/bvocal-test/KB.md', "# Test guide\n\nChanged version." );
if ( 1 !== BVOCAL_Guide_Sync::sync( array( $changed ) )['updated'] ) { throw new RuntimeException( 'Changed guide was not updated.' ); }
BVOCAL_Guide_Sync::sync( array() );
if ( '0' !== get_post_meta( $post->ID, '_bvocal_guide_active', true ) ) { throw new RuntimeException( 'Missing guide was not deactivated.' ); }
wp_delete_post( $post->ID, true );
echo "PASS: guide synchronization\n";
