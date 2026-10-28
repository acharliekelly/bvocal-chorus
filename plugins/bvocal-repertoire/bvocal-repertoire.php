<?php
/**
 * Plugin Name: BVOCAL Repertoire
 * Description: A clean repertoire-item content model for BVOCAL performance materials.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: bvocal-repertoire
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-repertoire.php';

BVOCAL_Repertoire::init();

register_activation_hook(
	__FILE__,
	function () {
		BVOCAL_Repertoire::register_post_type();
		flush_rewrite_rules();
	}
);

register_deactivation_hook(
	__FILE__, 'flush_rewrite_rules' );
