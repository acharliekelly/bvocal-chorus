<?php

if ( ! defined( 'ABSPATH' ) ) { exit; }

class BVOCAL_Guide_Sync {
	const POST_TYPE = 'bvocal_guide';
	public static function register_post_type() {
		register_post_type( self::POST_TYPE, array( 'public' => false, 'show_ui' => false, 'show_in_menu' => false, 'supports' => array( 'title', 'editor' ) ) );
	}
	public static function sync( $sources ) {
		$result = array( 'created' => 0, 'updated' => 0, 'unchanged' => 0 );
		$identities = array();
		foreach ( $sources as $source ) {
			$identities[] = $source['identity'];
			$posts = get_posts( array( 'post_type' => self::POST_TYPE, 'post_status' => 'any', 'meta_key' => '_bvocal_guide_source', 'meta_value' => $source['identity'], 'numberposts' => 1 ) );
			$post = $posts ? $posts[0] : null;
			if ( $post && get_post_meta( $post->ID, '_bvocal_guide_hash', true ) === $source['hash'] ) { update_post_meta( $post->ID, '_bvocal_guide_active', '1' ); $result['unchanged']++; continue; }
			$data = array( 'post_type' => self::POST_TYPE, 'post_status' => 'private', 'post_title' => $source['title'], 'post_content' => BVOCAL_Guide_Markdown::render( $source['markdown'] ) );
			$id = $post ? wp_update_post( $data + array( 'ID' => $post->ID ) ) : wp_insert_post( $data );
			update_post_meta( $id, '_bvocal_guide_source', $source['identity'] );
			update_post_meta( $id, '_bvocal_guide_hash', $source['hash'] );
			update_post_meta( $id, '_bvocal_guide_active', '1' );
			$result[ $post ? 'updated' : 'created' ]++;
		}
		foreach ( get_posts( array( 'post_type' => self::POST_TYPE, 'post_status' => 'any', 'numberposts' => -1 ) ) as $post ) {
			if ( ! in_array( get_post_meta( $post->ID, '_bvocal_guide_source', true ), $identities, true ) ) { update_post_meta( $post->ID, '_bvocal_guide_active', '0' ); }
		}
		return $result;
	}
}
