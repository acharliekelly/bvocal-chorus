<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<aside class="bvocal-cta-sidebar" aria-label="Site actions">
	<?php
	$actions = array(
		'new-members'    => 'Join Us',
		'contact-us'     => 'Contact Us',
		'booking-bvocal' => 'Book Us',
		'support-us'     => 'Support Us',
	);
	$is_action_page = false;
	foreach ( array_keys( $actions ) as $slug ) {
		if ( is_page( $slug ) ) {
			$is_action_page = true;
			break;
		}
	}
	foreach ( $actions as $slug => $label ) {
		$is_current     = is_page( $slug );
		$class          = 'bvocal-cta' . ( $is_current || ( ! $is_action_page && 'new-members' === $slug ) ? ' is-current' : '' );
		printf( '<a class="%1$s" href="%2$s">%3$s</a>', esc_attr( $class ), esc_url( home_url( '/' . $slug . '/' ) ), esc_html( $label ) );
	}
	?>
	<?php get_search_form(); ?>
	<?php echo bvocal_render_social_links(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</aside>
