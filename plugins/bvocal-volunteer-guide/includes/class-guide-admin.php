<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

class BVOCAL_Guide_Admin {
	public function init() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_bvocal_guide_sync', array( $this, 'sync' ) );
		add_action( 'load-toplevel_page_bvocal-volunteer-guide', array( $this, 'maybe_sync' ) );
	}
	public function menu() { add_menu_page( 'Volunteer Guide', 'Volunteer Guide', 'edit_others_pages', 'bvocal-volunteer-guide', array( $this, 'render' ), 'dashicons-welcome-learn-more', 59 ); }
	public function maybe_sync() { if ( current_user_can( 'manage_options' ) ) { BVOCAL_Guide_Sync::sync( BVOCAL_Guide_Source::discover() ); } }
	public function sync() {
		if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'bvocal_guide_sync' ) ) { wp_die( 'You cannot synchronize the Volunteer Guide.' ); }
		BVOCAL_Guide_Sync::sync( BVOCAL_Guide_Source::discover() ); wp_safe_redirect( admin_url( 'admin.php?page=bvocal-volunteer-guide&synced=1' ) ); exit;
	}
	public function render() {
		if ( ! current_user_can( 'edit_others_pages' ) ) { return; }
		$posts = get_posts( array( 'post_type' => BVOCAL_Guide_Sync::POST_TYPE, 'post_status' => 'private', 'meta_key' => '_bvocal_guide_active', 'meta_value' => '1', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
		echo '<div class="wrap"><h1>Volunteer Guide</h1><p>Instructions are maintained with BVOCAL custom code and cannot be edited here.</p>';
		if ( current_user_can( 'manage_options' ) ) { echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="bvocal_guide_sync">' . wp_nonce_field( 'bvocal_guide_sync', '_wpnonce', false, false ) . '<button class="button">Resync guide</button></form>'; }
		echo '<ul>'; foreach ( $posts as $post ) { echo '<li><h2>' . esc_html( $post->post_title ) . '</h2>' . wp_kses_post( $post->post_content ) . '</li>'; } echo '</ul></div>';
	}
}
