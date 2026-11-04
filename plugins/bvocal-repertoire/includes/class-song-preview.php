<?php
/** Read-only Song-to-Repertoire field mapping. No writes or remote requests. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class BVOCAL_Song_Preview {
	/** Text fields preserve paragraph and stanza boundaries, without executable HTML. */
	private static function text( $html ) {
		$html = preg_replace( '#<br\s*/?>#i', "\n", $html );
		$html = preg_replace( '#</(?:p|li|h[1-6])>#i', "\n\n", $html );
		$text = html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = str_replace( "\xc2\xa0", ' ', $text );
		$text = preg_replace( '/[\t ]+/', ' ', $text );
		return trim( preg_replace( '/\n[\t ]+/', "\n", $text ) );
	}

	private static function url( $block ) {
		if ( ! empty( $block['attrs']['href'] ) ) { return $block['attrs']['href']; }
		if ( ! empty( $block['attrs']['src'] ) ) { return $block['attrs']['src']; }
		return preg_match( '/(?:src|href)="([^"]+)"/', $block['innerHTML'], $m ) ? html_entity_decode( $m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) : '';
	}

	/** Recognize only Drive file links or the legacy site's Drive-stream query. */
	private static function drive_id( $url ) {
		$parts = wp_parse_url( html_entity_decode( $url, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		if ( ! is_array( $parts ) ) { return ''; }
		parse_str( isset( $parts['query'] ) ? $parts['query'] : '', $query );
		if ( isset( $parts['host'] ) && 'drive.google.com' === strtolower( $parts['host'] ) ) {
			if ( preg_match( '#/file/d/([a-zA-Z0-9_-]+)(?:/|$)#', isset( $parts['path'] ) ? $parts['path'] : '', $m ) ) { return $m[1]; }
			return isset( $query['id'] ) && is_string( $query['id'] ) ? $query['id'] : '';
		}
		$home_host = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( isset( $parts['host'], $query['igd_stream'], $query['id'] ) && $parts['host'] === $home_host && is_string( $query['id'] ) ) { return $query['id']; }
		return '';
	}

	/** Use catalogue identities rather than environment-specific uploads URLs. */
	private static function media( $block, $expected ) {
		global $wpdb;
		$url = self::url( $block );
		$source_drive = self::drive_id( $url );
		$id = isset( $block['attrs']['id'] ) ? absint( $block['attrs']['id'] ) : 0;
		if ( ! $id && $source_drive ) {
			$candidates = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND guid LIKE %s", '%' . $wpdb->esc_like( $source_drive ) . '%' ) );
			$matches = array();
			foreach ( $candidates as $candidate ) {
				if ( self::drive_id( get_post( $candidate )->guid ) === $source_drive ) { $matches[] = $candidate; }
			}
			if ( count( $matches ) !== 1 ) { return new WP_Error( 'ambiguous_media', 'Media needs exactly one matching catalogue record.' ); }
			$id = (int) $matches[0];
		}
		$attachment = $id ? get_post( $id ) : null;
		if ( ! $attachment || 'attachment' !== $attachment->post_type ) { return new WP_Error( 'missing_media', 'Media is not resolved to a catalogue record.' ); }
		$drive = self::drive_id( $attachment->guid );
		if ( $source_drive && $drive !== $source_drive ) { return new WP_Error( 'conflicting_media', 'Block and catalogue refer to different Drive files.' ); }
		$resolved = $drive ? 'https://drive.google.com/file/d/' . rawurlencode( $drive ) . '/view' : $attachment->guid;
		$valid = BVOCAL_Repertoire::validate_media_url( $resolved, $expected );
		if ( is_wp_error( $valid ) ) { return $valid; }
		return array( 'attachment_id' => $id, 'url' => $resolved );
	}

	private static function add_link( &$report, $block, $label, $url ) {
		$link = BVOCAL_Repertoire::normalize_link( array( 'label' => $label, 'url' => $url ) );
		if ( is_wp_error( $link ) ) {
			self::unmapped( $report, $block, 'invalid_link', $label, $url );
			$report['warnings'][] = 'Link URL needs review.';
		} else {
			$report['proposed']['links'][] = $link;
			if ( '' === $label ) { $report['warnings'][] = 'Link label needs review.'; }
		}
	}

	private static function unmapped( &$report, $block, $type, $text = '', $url = '' ) {
		$report['unmapped'][] = array( 'type' => $type, 'text' => $text, 'url' => $url, 'source_block' => serialize_block( $block ) );
	}

	/** Flatten layout containers, keeping their content in document order. */
	private static function flatten( $blocks ) {
		$result = array();
		foreach ( $blocks as $block ) {
			if ( in_array( $block['blockName'], array( 'core/group', 'core/columns', 'core/column' ), true ) && $block['innerBlocks'] ) {
				$result = array_merge( $result, self::flatten( $block['innerBlocks'] ) );
			} else { $result[] = $block; }
		}
		return $result;
	}

	/** Map a Song to proposed fields. Unsupported components retain their source. */
	public static function preview( $song ) {
		$report = array(
			'source' => array( 'id' => (int) $song->ID, 'slug' => $song->post_name, 'title' => $song->post_title, 'status' => $song->post_status, 'content_sha256' => hash( 'sha256', $song->post_content ) ),
			'proposed' => array( 'title' => $song->post_title, 'status' => 'draft', 'author' => '', 'arranger' => '', 'lyricist' => '', 'language' => '', 'notes' => '', 'lyrics' => '', 'score_url' => '', 'recordings' => array(), 'links' => array() ),
			'media' => array(), 'warnings' => array(), 'unmapped' => array(),
		);
		$section = 'intro';
		$label = '';
		$notes = array();
		$lyrics = array();
		foreach ( self::flatten( parse_blocks( $song->post_content ) ) as $block ) {
			$type = $block['blockName'];
			$html = 'core/list' === $type ? serialize_block( $block ) : $block['innerHTML'];
			$text = self::text( $html );
			if ( ! $type && '' === trim( $html ) ) { continue; }
			if ( in_array( $type, array( 'core/separator', 'core/spacer' ), true ) ) { continue; }
			$is_heading = 'core/heading' === $type || ( 'core/paragraph' === $type && preg_match( '#^\s*<p[^>]*>\s*<strong>\s*lyrics\s*</strong>\s*</p>\s*$#i', $html ) );
			if ( $is_heading ) {
				$heading = strtolower( trim( $text, " :\t\n\r" ) );
				$sections = array( 'lyrics' => 'lyrics', 'score' => 'score', 'practice recordings' => 'audio', 'practice recording' => 'audio', 'recordings' => 'audio', 'video' => 'links', 'videos' => 'links', 'links' => 'links' );
				if ( '' !== $label ) { $notes[] = $label; $report['warnings'][] = 'Recording section text needs review.'; }
				$section = isset( $sections[$heading] ) ? $sections[$heading] : 'unknown';
				$label = '';
				if ( 'unknown' === $section ) { self::unmapped( $report, $block, 'heading', $text ); }
				continue;
			}
			if ( 'core/file' === $type || 'core/audio' === $type ) {
				$is_score = 'core/file' === $type;
				$media = self::media( $block, $is_score ? 'application/pdf' : 'audio' );
				if ( is_wp_error( $media ) ) {
					$report['warnings'][] = $media->get_error_message();
					self::unmapped( $report, $block, $is_score ? 'score' : 'audio', $text, self::url( $block ) );
				} elseif ( $is_score && $report['proposed']['score_url'] ) {
					self::unmapped( $report, $block, 'additional_score', $text, $media['url'] );
				} else {
					$report['media'][] = $media;
					if ( $is_score ) { $report['proposed']['score_url'] = $media['url']; }
					else {
						$report['proposed']['recordings'][] = array( 'label' => $label, 'url' => $media['url'] );
						if ( '' === $label ) { $report['warnings'][] = 'Recording label needs review (attachment ' . $media['attachment_id'] . ').'; }
					}
				}
				$label = '';
				continue;
			}
			if ( 'core/video' === $type || 'core/embed' === $type ) {
				$caption = preg_match( '#<figcaption[^>]*>(.*?)</figcaption>#s', $html, $m ) ? self::text( $m[1] ) : $text;
				$url = ! empty( $block['attrs']['url'] ) ? $block['attrs']['url'] : self::url( $block );
				self::add_link( $report, $block, $caption, $url );
				continue;
			}
			if ( preg_match_all( '#<a\b[^>]*href="([^"]+)"[^>]*>(.*?)</a>#is', $html, $links, PREG_SET_ORDER ) ) {
				if ( ! in_array( $type, array( 'core/paragraph', 'core/list' ), true ) ) {
					self::unmapped( $report, $block, 'unsupported_block', $text ); continue;
				}
				foreach ( $links as $link ) {
					$link_label = 1 === count( $links ) ? $text : self::text( $link[2] );
					self::add_link( $report, $block, $link_label, html_entity_decode( $link[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
				}
				if ( 'lyrics' === $section ) { $lyrics[] = $text; }
				elseif ( count( $links ) > 1 ) { $notes[] = $text; }
				continue;
			}
			if ( ! in_array( $type, array( 'core/paragraph', 'core/list', 'core/verse', 'core/preformatted' ), true ) ) {
				self::unmapped( $report, $block, 'unsupported_block', $text ); continue;
			}
			if ( '' === $text ) { continue; }
			// Explicitly approved exception, matched by stable slug and exact source text.
			if ( 'these-hands' === $song->post_name && 'core/paragraph' === $type && 'Add a File block below and upload the score PDF.' === $text ) {
				$notes[] = $text;
				$report['warnings'][] = 'Approved source instruction preserved in notes; review before publishing.';
				continue;
			}
			if ( preg_match( '/Add a File block|upload the score PDF/i', $text ) ) {
				self::unmapped( $report, $block, 'possible_placeholder', $text ); continue;
			}
			if ( 'lyrics' === $section ) {
				$lyrics[] = $text;
				if ( preg_match( '#<(?:strong|em|b|i)\b#i', $html ) ) { $report['warnings'][] = 'Lyric formatting becomes plain text; review part labels and emphasis.'; }
			} elseif ( 'audio' === $section ) {
				if ( '' !== $label ) { $notes[] = $label; $report['warnings'][] = 'Text in recordings section needs review.'; }
				$label = trim( $text, " :\n\r\t" );
			} elseif ( 'intro' === $section && preg_match( '/^by\s+(.+?)(?:\s*\/\s*arr\.?\s*(?:by\s+)?(.+))?$/iu', $text, $m ) && ! $report['proposed']['author'] ) {
				// Compound attribution stays in notes until a person resolves it.
				if ( false !== strpos( $text, ';' ) ) { $notes[] = $text; $report['warnings'][] = 'Compound credit needs review.'; }
				else { $report['proposed']['author'] = trim( $m[1] ); $report['proposed']['arranger'] = isset( $m[2] ) ? trim( $m[2] ) : ''; }
			} elseif ( 'intro' === $section && preg_match( '/^Arrangement by\s+(.+)$/iu', $text, $m ) && ! $report['proposed']['arranger'] ) {
				$report['proposed']['arranger'] = trim( $m[1] );
			} elseif ( 'intro' === $section && preg_match( '/^Traditional\s*\/\s*arr\.?\s*(?:by\s+)?(.+)$/iu', $text, $m ) ) {
				$report['proposed']['author'] = 'Traditional'; $report['proposed']['arranger'] = trim( $m[1] );
			} else { $notes[] = $text; $report['warnings'][] = 'Descriptive text or attribution needs review in notes.'; }
		}
		if ( '' !== $label ) { $notes[] = $label; $report['warnings'][] = 'Trailing recording text needs review.'; }
		$report['proposed']['lyrics'] = implode( "\n\n", $lyrics );
		$report['proposed']['notes'] = implode( "\n\n", $notes );
		if ( ! $report['proposed']['author'] ) { $report['warnings'][] = 'Author needs review.'; }
		if ( ! $report['proposed']['lyrics'] ) { $report['warnings'][] = 'No separate lyrics mapped; PDF text was not extracted.'; }
		if ( ! empty( $song->post_password ) ) { $report['warnings'][] = 'Source is password protected; preserve visibility during future conversion.'; }
		if ( $song->ID ) {
			$legacy = array();
			foreach ( array( 'musical_score', 'recording_other', 'recording_melody', 'recording_bass', 'recording_soprano' ) as $key ) {
				$value = get_post_meta( $song->ID, $key, true );
				if ( '' !== $value ) { $legacy[$key] = $value; }
			}
			if ( $legacy ) { $report['legacy_fields_for_review'] = $legacy; $report['warnings'][] = 'Legacy media fields exist; compare with current blocks before conversion.'; }
		}
		if ( $report['unmapped'] ) { $report['warnings'][] = 'Unmapped components remain; review before conversion.'; }
		$report['warnings'] = array_values( array_unique( $report['warnings'] ) );
		return $report;
	}

	/**
	 * Preview published Songs without changing records.
	 *
	 * ## OPTIONS
	 *
	 * [--song=<id>]
	 * : Preview one published Song on this site.
	 *
	 * [--format=<format>]
	 * : json (full report, default) or table (summary).
	 *
	 * ## EXAMPLES
	 *
	 *     wp bvocal repertoire preview-songs --format=table
	 *     wp bvocal repertoire preview-songs --song=1789
	 */
	public static function command( $args, $assoc_args ) {
		if ( $args || array_diff( array_keys( $assoc_args ), array( 'song', 'format' ) ) ) { WP_CLI::error( 'Preview only: supported options are --song and --format. No apply mode exists.' ); }
		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'json';
		if ( ! in_array( $format, array( 'json', 'table' ), true ) ) { WP_CLI::error( 'Use --format=json or --format=table.' ); }
		$query = array( 'post_type' => 'song', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' );
		if ( isset( $assoc_args['song'] ) ) {
			if ( ! ctype_digit( (string) $assoc_args['song'] ) || (int) $assoc_args['song'] < 1 ) { WP_CLI::error( '--song must be a positive local Song ID.' ); }
			$query['post__in'] = array( (int) $assoc_args['song'] );
		}
		$songs = get_posts( $query );
		if ( isset( $assoc_args['song'] ) && ! $songs ) { WP_CLI::error( 'No published Song found for that ID. Private/test items are excluded.' ); }
		$reports = array_map( array( __CLASS__, 'preview' ), $songs );
		if ( 'json' === $format ) {
			WP_CLI::line( wp_json_encode( array( 'mode' => 'preview-only', 'site' => home_url(), 'count' => count( $reports ), 'songs' => $reports ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
		} else {
			$rows = array();
			foreach ( $reports as $r ) { $rows[] = array( 'ID' => $r['source']['id'], 'Title' => $r['source']['title'], 'Author' => $r['proposed']['author'], 'Score' => $r['proposed']['score_url'] ? 'yes' : 'no', 'Audio' => count( $r['proposed']['recordings'] ), 'Links' => count( $r['proposed']['links'] ), 'Unmapped' => count( $r['unmapped'] ), 'Warnings' => count( $r['warnings'] ) ); }
			\WP_CLI\Utils\format_items( 'table', $rows, array( 'ID', 'Title', 'Author', 'Score', 'Audio', 'Links', 'Unmapped', 'Warnings' ) );
		}
	}
}
