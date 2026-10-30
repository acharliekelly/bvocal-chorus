<?php
/**
 * Plugin Name: BVOCAL Volunteer Guide
 * Description: Read-only dashboard guidance generated from BVOCAL custom-code documentation.
 * Version: 0.1.0
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-guide-source.php';
require_once __DIR__ . '/includes/class-guide-markdown.php';
require_once __DIR__ . '/includes/class-guide-sync.php';
require_once __DIR__ . '/includes/class-guide-admin.php';

add_action( 'init', array( 'BVOCAL_Guide_Sync', 'register_post_type' ) );
( new BVOCAL_Guide_Admin() )->init();
