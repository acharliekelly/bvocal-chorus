<?php
/**
 * Dynamic menu block render callback.
 *
 * @package BVOCAL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo bvocal_render_menu_block( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
