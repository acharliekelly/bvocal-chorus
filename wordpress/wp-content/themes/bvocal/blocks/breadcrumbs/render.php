<?php
/**
 * Dynamic breadcrumbs block render callback.
 *
 * @package BVOCAL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo bvocal_render_breadcrumbs_block(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
