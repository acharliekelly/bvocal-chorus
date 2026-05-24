<?php
/**
 * Dynamic logo block render callback.
 *
 * @package BVOCAL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo bvocal_render_logo_block(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
