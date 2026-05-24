<?php
/**
 * BVOCAL theme setup.
 *
 * @package BVOCAL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BVOCAL_THEME_VERSION', '1.0.0' );

function bvocal_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/theme.css' );

	add_theme_support(
		'custom-logo',
		array(
			'height'      => 186,
			'width'       => 537,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'bvocal' ),
			'member'  => __( 'Member Menu', 'bvocal' ),
			'footer'  => __( 'Footer Menu', 'bvocal' ),
		)
	);
}
add_action( 'after_setup_theme', 'bvocal_setup' );

function bvocal_enqueue_assets() {
	wp_enqueue_style(
		'bvocal-theme',
		get_theme_file_uri( 'assets/theme.css' ),
		array(),
		BVOCAL_THEME_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'bvocal_enqueue_assets' );
add_action( 'enqueue_block_editor_assets', 'bvocal_enqueue_assets' );

function bvocal_register_blocks() {
	register_block_type( __DIR__ . '/blocks/menu' );
	register_block_type( __DIR__ . '/blocks/logo' );
}
add_action( 'init', 'bvocal_register_blocks' );

function bvocal_render_logo_block() {
	$custom_logo = get_custom_logo();

	if ( $custom_logo ) {
		return '<div class="bvocal-logo">' . $custom_logo . '</div>';
	}

	$logo_url = get_theme_file_uri( 'assets/images/bvocal-logo-stacked.svg' );

	return sprintf(
		'<a class="bvocal-logo" href="%1$s" rel="home"><img src="%2$s" alt="%3$s"></a>',
		esc_url( home_url( '/' ) ),
		esc_url( $logo_url ),
		esc_attr( get_bloginfo( 'name' ) )
	);
}

function bvocal_get_fallback_menu_slug( $location ) {
	$fallbacks = array(
		'primary' => 'public-main',
		'member'  => 'member',
		'footer'  => 'public-main',
	);

	return isset( $fallbacks[ $location ] ) ? $fallbacks[ $location ] : '';
}

function bvocal_is_member_context() {
	if ( is_admin() || ! is_page() ) {
		return false;
	}

	$member_slugs = array(
		'members',
		'member-dashboard',
		'rehearsal-schedule',
		'spring-rehearsal-schedule',
		'gigs',
		'practice-materials',
		'teams',
		'songs',
		'songs-with-musical-scores',
		'song-sheets-by-topic',
		'tips-on-reading-musical-scores',
		'overview',
		'membership-login',
		'membership-profile',
		'membership-registration',
		'password-reset',
	);

	$page = get_queried_object();
	if ( ! $page instanceof WP_Post ) {
		return false;
	}

	if ( in_array( $page->post_name, $member_slugs, true ) ) {
		return true;
	}

	$ancestors = get_post_ancestors( $page );
	foreach ( $ancestors as $ancestor_id ) {
		$ancestor = get_post( $ancestor_id );
		if ( $ancestor instanceof WP_Post && in_array( $ancestor->post_name, $member_slugs, true ) ) {
			return true;
		}
	}

	return false;
}

function bvocal_render_menu_block( $attributes ) {
	$location            = isset( $attributes['location'] ) ? sanitize_key( $attributes['location'] ) : 'primary';
	$class_name          = isset( $attributes['className'] ) ? sanitize_html_class( $attributes['className'] ) : '';
	$member_context_only = ! empty( $attributes['memberContextOnly'] );

	if ( $member_context_only && ! bvocal_is_member_context() ) {
		return '';
	}

	$args = array(
		'theme_location' => $location,
		'container'      => 'nav',
		'container_class'=> trim( 'bvocal-menu bvocal-menu-' . $location . ' ' . $class_name ),
		'menu_class'     => 'bvocal-menu__list',
		'fallback_cb'    => false,
		'echo'           => false,
		'depth'          => 2,
	);

	$menu = wp_nav_menu( $args );

	if ( ! $menu ) {
		$fallback_slug = bvocal_get_fallback_menu_slug( $location );
		if ( $fallback_slug ) {
			$menu = wp_nav_menu(
				array_merge(
					$args,
					array(
						'theme_location' => '',
						'menu'           => $fallback_slug,
					)
				)
			);
		}
	}

	return $menu ? $menu : '';
}
