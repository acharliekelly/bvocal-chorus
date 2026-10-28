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
	register_block_type( __DIR__ . '/blocks/breadcrumbs' );
	register_block_type( __DIR__ . '/blocks/social-links' );
	register_block_type( __DIR__ . '/blocks/cta-rail' );
}
add_action( 'init', 'bvocal_register_blocks' );

function bvocal_get_theme_options() {
	return wp_parse_args( get_option( 'bvocal_theme_options', array() ), array( 'youtube_url' => '', 'facebook_url' => '', 'instagram_url' => '' ) );
}

function bvocal_sanitize_theme_options( $options ) {
	return array(
		'youtube_url'   => isset( $options['youtube_url'] ) ? esc_url_raw( $options['youtube_url'] ) : '',
		'facebook_url'  => isset( $options['facebook_url'] ) ? esc_url_raw( $options['facebook_url'] ) : '',
		'instagram_url' => isset( $options['instagram_url'] ) ? esc_url_raw( $options['instagram_url'] ) : '',
	);
}

function bvocal_register_theme_settings() {
	register_setting( 'bvocal_theme_options', 'bvocal_theme_options', array( 'sanitize_callback' => 'bvocal_sanitize_theme_options' ) );
	add_settings_section( 'bvocal_social_links', __( 'Social media links', 'bvocal' ), '__return_false', 'bvocal-theme-settings' );
	foreach ( array( 'youtube_url' => 'YouTube', 'facebook_url' => 'Facebook', 'instagram_url' => 'Instagram' ) as $key => $label ) {
		add_settings_field( $key, $label, 'bvocal_render_social_setting', 'bvocal-theme-settings', 'bvocal_social_links', array( 'key' => $key, 'label' => $label ) );
	}
}
add_action( 'admin_init', 'bvocal_register_theme_settings' );

function bvocal_render_social_setting( $args ) {
	$options = bvocal_get_theme_options();
	printf( '<input class="regular-text" type="url" name="bvocal_theme_options[%1$s]" value="%2$s" placeholder="https://..." aria-label="%3$s">', esc_attr( $args['key'] ), esc_attr( $options[ $args['key'] ] ), esc_attr( $args['label'] ) );
}

function bvocal_add_theme_settings_page() {
	add_theme_page( __( 'BVOCAL Theme Settings', 'bvocal' ), __( 'BVOCAL Theme Settings', 'bvocal' ), 'manage_options', 'bvocal-theme-settings', 'bvocal_render_theme_settings_page' );
}
add_action( 'admin_menu', 'bvocal_add_theme_settings_page' );

function bvocal_render_theme_settings_page() {
	?>
	<div class="wrap"><h1><?php esc_html_e( 'BVOCAL Theme Settings', 'bvocal' ); ?></h1><form action="options.php" method="post"><?php settings_fields( 'bvocal_theme_options' ); do_settings_sections( 'bvocal-theme-settings' ); submit_button(); ?></form></div>
	<?php
}

function bvocal_render_social_links() {
	$options = bvocal_get_theme_options();
	$icons   = array(
		'youtube_url' => array( 'YouTube', '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.5 12 3.5 12 3.5s-7.5 0-9.4.6A3 3 0 0 0 .5 6.2 31 31 0 0 0 0 12a31 31 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.6 9.4.6 9.4.6s7.5 0 9.4-.6a3 3 0 0 0 2.1-2.1A31 31 0 0 0 24 12a31 31 0 0 0-.5-5.8ZM9.6 15.6V8.4l6.3 3.6-6.3 3.6Z"/></svg>' ),
		'facebook_url' => array( 'Facebook', '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.8 21v-8h2.7l.4-3h-3.1V8.1c0-.9.3-1.5 1.6-1.5H17V3.9c-.3 0-1.3-.1-2.5-.1-2.5 0-4.2 1.5-4.2 4.3V10H7.5v3h2.8v8h3.5Z"/></svg>' ),
		'instagram_url' => array( 'Instagram', '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5Zm0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3H7Zm5 3.5A4.5 4.5 0 1 1 7.5 12 4.5 4.5 0 0 1 12 7.5Zm0 2A2.5 2.5 0 1 0 14.5 12 2.5 2.5 0 0 0 12 9.5ZM17.8 6.3a1.1 1.1 0 1 1-1.1 1.1 1.1 1.1 0 0 1 1.1-1.1Z"/></svg>' ),
	);
	$output = '';
	foreach ( $icons as $key => $icon ) {
		if ( ! empty( $options[ $key ] ) ) {
			$output .= sprintf( '<a href="%1$s" class="bvocal-social-link" target="_blank" rel="noopener noreferrer"><span class="screen-reader-text">%2$s</span>%3$s</a>', esc_url( $options[ $key ] ), esc_html( $icon[0] ), $icon[1] );
		}
	}
	return $output ? '<div class="bvocal-social-links" aria-label="Social media">' . $output . '</div>' : '';
}

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

function bvocal_render_breadcrumbs_block() {
	if ( is_front_page() ) {
		return '';
	}

	$items = array(
		array(
			'label' => __( 'Home', 'bvocal' ),
			'url'   => home_url( '/' ),
		),
	);

	if ( is_page() ) {
		$page      = get_queried_object();
		$ancestors = array_reverse( get_post_ancestors( $page ) );

		foreach ( $ancestors as $ancestor_id ) {
			$items[] = array(
				'label' => get_the_title( $ancestor_id ),
				'url'   => get_permalink( $ancestor_id ),
			);
		}

		$items[] = array(
			'label' => get_the_title( $page ),
			'url'   => '',
		);
	} elseif ( is_singular() ) {
		$post_type = get_post_type();
		$type_obj  = get_post_type_object( $post_type );

		if ( $type_obj && ! empty( $type_obj->has_archive ) ) {
			$items[] = array(
				'label' => $type_obj->labels->name,
				'url'   => get_post_type_archive_link( $post_type ),
			);
		}

		$items[] = array(
			'label' => get_the_title(),
			'url'   => '',
		);
	} elseif ( is_search() ) {
		$items[] = array(
			'label' => sprintf(
				/* translators: %s: Search query. */
				__( 'Search: %s', 'bvocal' ),
				get_search_query()
			),
			'url'   => '',
		);
	} elseif ( is_404() ) {
		$items[] = array(
			'label' => __( 'Page not found', 'bvocal' ),
			'url'   => '',
		);
	} elseif ( is_archive() ) {
		$items[] = array(
			'label' => get_the_archive_title(),
			'url'   => '',
		);
	}

	if ( count( $items ) < 2 ) {
		return '';
	}

	$output = '<nav class="bvocal-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumbs', 'bvocal' ) . '"><ol>';
	foreach ( $items as $index => $item ) {
		$is_last = count( $items ) - 1 === $index;
		$output .= '<li>';
		if ( ! $is_last && ! empty( $item['url'] ) ) {
			$output .= '<a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a>';
		} else {
			$output .= '<span aria-current="page">' . esc_html( $item['label'] ) . '</span>';
		}
		$output .= '</li>';
	}
	$output .= '</ol></nav>';

	return $output;
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
