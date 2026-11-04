<?php
/** Bounded, draft-only replay of a reviewed correction manifest. WP-CLI only. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class BVOCAL_Correction_Replay {
	const FIELDS = array( 'title', 'slug', 'author', 'arranger', 'lyricist', 'language', 'notes', 'lyrics', 'score_url', 'recordings', 'links' );

	private static function text( $text ) {
		$lines = explode( "\n", str_replace( array( "\r\n", "\r" ), "\n", $text ) );
		return rtrim( implode( "\n", array_map( function ( $line ) { return rtrim( $line, " \t" ); }, $lines ) ), "\n" );
	}

	public static function url_reference( $url ) {
		if ( ! $url ) { return null; }
		$url = html_entity_decode( $url, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$p = wp_parse_url( $url );
		if ( ! is_array( $p ) ) { return array( 'external_url' => $url ); }
		if ( isset( $p['host'] ) && 'drive.google.com' === strtolower( $p['host'] ) ) {
			if ( preg_match( '#/file/d/([^/]+)#', $p['path'] ?? '', $m ) ) { return array( 'drive_file_id' => $m[1] ); }
			parse_str( $p['query'] ?? '', $query );
			if ( ! empty( $query['id'] ) && is_string( $query['id'] ) ) { return array( 'drive_file_id' => $query['id'] ); }
		}
		if ( isset( $p['host'] ) && $p['host'] === wp_parse_url( home_url(), PHP_URL_HOST ) ) {
			return array( 'site_path' => ( $p['path'] ?? '/' ) . ( isset( $p['query'] ) ? '?' . $p['query'] : '' ) . ( isset( $p['fragment'] ) ? '#' . $p['fragment'] : '' ) );
		}
		return array( 'external_url' => $url );
	}

	private static function canonical( $raw ) {
		$out = array();
		foreach ( self::FIELDS as $key ) {
			$v = $raw[$key];
			if ( 'score_url' === $key ) { $out[$key] = self::url_reference( $v ); }
			elseif ( in_array( $key, array( 'recordings', 'links' ), true ) ) {
				$out[$key] = array();
				foreach ( is_array( $v ) ? $v : array() as $link ) { $out[$key][] = array( 'label' => self::text( $link['label'] ?? '' ), 'url' => self::url_reference( $link['url'] ?? '' ) ); }
			} else { $out[$key] = self::text( $v ); }
		}
		return $out;
	}

	/** Approved spelling normalization is for comparison, not source mutation. */
	private static function credit_spelling( $fields ) {
		foreach ( array( 'title', 'author', 'arranger', 'lyricist', 'notes', 'lyrics' ) as $key ) { $fields[$key] = preg_replace( '/\bGallitan\b/iu', 'Gallatin', $fields[$key] ); }
		return $fields;
	}

	public static function preview_fields( $song ) {
		$preview = BVOCAL_Song_Preview::preview( $song );
		return self::canonical( array_merge( $preview['proposed'], array( 'slug' => $song->post_name ) ) );
	}

	private static function raw_fields( $item ) {
		$out = array( 'title' => $item->post_title, 'slug' => $item->post_name );
		foreach ( self::FIELDS as $key ) {
			if ( isset( $out[$key] ) ) { continue; }
			$value = get_post_meta( $item->ID, BVOCAL_Repertoire::META_PREFIX . $key, true );
			$out[$key] = in_array( $key, array( 'recordings', 'links' ), true ) ? ( is_array( $value ) ? $value : array() ) : $value;
		}
		return $out;
	}

	private static function valid_reference( $ref ) {
		if ( ! is_array( $ref ) || 1 !== count( $ref ) ) { return false; }
		if ( isset( $ref['drive_file_id'] ) && is_string( $ref['drive_file_id'] ) ) { return (bool) preg_match( '/^[a-zA-Z0-9_-]+$/D', $ref['drive_file_id'] ); }
		if ( isset( $ref['site_path'] ) && is_string( $ref['site_path'] ) ) { return 0 === strpos( $ref['site_path'], '/wp-content/uploads/' ) && false === strpos( $ref['site_path'], '..' ) && ! preg_match( '/[\r\n?#]/', $ref['site_path'] ); }
		return isset( $ref['external_url'] ) && is_string( $ref['external_url'] ) && ! is_wp_error( BVOCAL_Repertoire::normalize_link( array( 'url' => $ref['external_url'] ) ) );
	}

	public static function validate_entry( $entry ) {
		if ( ! is_array( $entry ) || ! isset( $entry['source_song_slug'] ) || ! is_string( $entry['source_song_slug'] ) || '' === $entry['source_song_slug'] || sanitize_title( $entry['source_song_slug'] ) !== $entry['source_song_slug'] ) { return new WP_Error( 'manifest', 'Invalid source Song slug.' ); }
		foreach ( array( 'baseline_fields', 'reviewed_fields' ) as $state ) {
			$values = $entry[$state] ?? null;
			if ( ! is_array( $values ) || count( $values ) !== count( self::FIELDS ) || array_diff( self::FIELDS, array_keys( $values ) ) ) { return new WP_Error( 'manifest', 'Manifest must contain only the supported fields.' ); }
			foreach ( self::FIELDS as $key ) {
				$v = $values[$key];
				if ( 'score_url' === $key ) { if ( null !== $v && ! self::valid_reference( $v ) ) { return new WP_Error( 'manifest', 'Invalid score reference.' ); } }
				elseif ( in_array( $key, array( 'recordings', 'links' ), true ) ) {
					if ( ! is_array( $v ) || ( $v && array_keys( $v ) !== range( 0, count( $v ) - 1 ) ) ) { return new WP_Error( 'manifest', 'Invalid link list.' ); }
					foreach ( $v as $link ) { if ( ! is_array( $link ) || count( $link ) !== 2 || ! isset( $link['label'], $link['url'] ) || ! is_string( $link['label'] ) || ! self::valid_reference( $link['url'] ) ) { return new WP_Error( 'manifest', 'Invalid link entry.' ); } }
				} elseif ( ! is_string( $v ) ) { return new WP_Error( 'manifest', 'Text fields must be strings.' ); }
			}
			if ( '' === trim( $values['title'] ) || '' === $values['slug'] || sanitize_title( $values['slug'] ) !== $values['slug'] ) { return new WP_Error( 'manifest', 'Invalid title or URL slug.' ); }
		}
		return true;
	}

	private static function resolve_reference( $ref ) {
		if ( null === $ref ) { return ''; }
		if ( isset( $ref['drive_file_id'] ) ) { return 'https://drive.google.com/file/d/' . rawurlencode( $ref['drive_file_id'] ) . '/view'; }
		return isset( $ref['site_path'] ) ? home_url( $ref['site_path'] ) : $ref['external_url'];
	}

	private static function resolve_field( $key, $value ) {
		if ( 'score_url' === $key ) {
			$url = self::resolve_reference( $value );
			$valid = $url ? BVOCAL_Repertoire::validate_media_url( $url, 'application/pdf' ) : true;
			return is_wp_error( $valid ) ? $valid : $url;
		}
		if ( in_array( $key, array( 'recordings', 'links' ), true ) ) {
			$result = array();
			foreach ( $value as $link ) {
				$url = self::resolve_reference( $link['url'] );
				$valid = 'recordings' === $key ? BVOCAL_Repertoire::validate_media_url( $url, 'audio' ) : BVOCAL_Repertoire::normalize_link( array( 'url' => $url ) );
				if ( is_wp_error( $valid ) ) { return $valid; }
				$result[] = array( 'label' => $link['label'], 'url' => $url );
			}
			return $result;
		}
		return $value;
	}

	private static function fingerprint( $item ) { return hash( 'sha256', serialize( array( $item->to_array(), get_post_meta( $item->ID ) ) ) ); }

	public static function plan( $entry ) {
		$row = array( 'source_song_slug' => is_array( $entry ) ? ( $entry['source_song_slug'] ?? '' ) : '', 'target_id' => null, 'status' => '', 'reason' => '', 'changes' => array() );
		$valid = self::validate_entry( $entry );
		if ( is_wp_error( $valid ) ) { $row['status'] = 'blocked_manifest'; $row['reason'] = $valid->get_error_message(); return $row; }
		// JSON object key order is not part of the field values.
		foreach ( array( 'baseline_fields', 'reviewed_fields' ) as $state ) {
			$ordered = array();
			foreach ( self::FIELDS as $key ) {
				$value = $entry[$state][$key];
				if ( in_array( $key, array( 'recordings', 'links' ), true ) ) { $value = array_map( function ( $link ) { return array( 'label' => $link['label'], 'url' => $link['url'] ); }, $value ); }
				$ordered[$key] = $value;
			}
			$entry[$state] = $ordered;
		}
		$sources = get_posts( array( 'post_type' => 'song', 'name' => $entry['source_song_slug'], 'post_status' => 'publish', 'posts_per_page' => -1 ) );
		if ( count( $sources ) !== 1 || $sources[0]->post_password ) { $row['status'] = 'blocked_source'; $row['reason'] = 'A single published, unprotected source Song is required.'; return $row; }
		$source = $sources[0]; $preview = BVOCAL_Song_Preview::preview( $source );
		if ( $preview['unmapped'] || self::credit_spelling( self::preview_fields( $source ) ) !== self::credit_spelling( $entry['baseline_fields'] ) ) { $row['status'] = 'blocked_source_drift'; $row['reason'] = 'Source extraction differs from the audited baseline.'; return $row; }
		$targets = get_posts( array( 'post_type' => BVOCAL_Repertoire::POST_TYPE, 'post_status' => array_keys( get_post_stati() ), 'posts_per_page' => -1, 'meta_key' => BVOCAL_Song_Conversion::SOURCE_META, 'meta_value' => (string) $source->ID ) );
		$manual = false;
		if ( ! $targets && 'user-confirmed manual entry' === ( $entry['pairing'] ?? '' ) ) {
			$item = get_page_by_path( $entry['reviewed_fields']['slug'], OBJECT, BVOCAL_Repertoire::POST_TYPE );
			$targets = $item ? array( $item ) : array(); $manual = true;
		}
		if ( count( $targets ) !== 1 ) { $row['status'] = 'blocked_target'; $row['reason'] = 'Expected one converted target; create drafts or reconcile source tracking first.'; return $row; }
		$item = $targets[0]; $row['target_id'] = $item->ID;
		if ( ! in_array( $item->post_status, array( 'draft', 'publish' ), true ) || $item->post_password || $item->post_content || $item->post_excerpt ) { $row['status'] = 'blocked_target_state'; $row['reason'] = 'Unexpected target status, protection, body content or excerpt.'; return $row; }
		$raw = self::raw_fields( $item ); $current = self::canonical( $raw );
		if ( $current === $entry['reviewed_fields'] ) { $row['status'] = $manual ? 'already_correct_manual' : 'already_correct'; return $row; }
		if ( $manual ) { $row['status'] = 'blocked_untracked'; $row['reason'] = 'Untracked manual entry differs from reviewed state; it will not be edited or adopted.'; return $row; }
		$spelling = self::credit_spelling( $current );
		foreach ( self::FIELDS as $key ) {
			if ( $current[$key] !== $entry['baseline_fields'][$key] && $current[$key] !== $entry['reviewed_fields'][$key] && $spelling[$key] !== $entry['baseline_fields'][$key] && $spelling[$key] !== $entry['reviewed_fields'][$key] ) { $row['status'] = 'blocked_target_drift'; $row['reason'] = 'Unexpected target value: ' . $key; return $row; }
			if ( $current[$key] !== $entry['reviewed_fields'][$key] ) { $row['changes'][] = array( 'field' => $key, 'before' => $current[$key], 'after' => $entry['reviewed_fields'][$key] ); }
		}
		if ( 'draft' !== $item->post_status ) { $row['status'] = 'blocked_published'; $row['reason'] = 'Only drafts may be corrected; published items are never changed.'; return $row; }
		if ( wp_unique_post_slug( $entry['reviewed_fields']['slug'], $item->ID, 'publish', BVOCAL_Repertoire::POST_TYPE, 0 ) !== $entry['reviewed_fields']['slug'] ) { $row['status'] = 'blocked_slug'; $row['reason'] = 'Reviewed target slug is already occupied.'; return $row; }
		$row['_resolved'] = array();
		foreach ( $row['changes'] as $change ) {
			$value = self::resolve_field( $change['field'], $change['after'] );
			if ( is_wp_error( $value ) ) { $row['status'] = 'blocked_media'; $row['reason'] = $value->get_error_message(); return $row; }
			$row['_resolved'][$change['field']] = $value;
		}
		$row['status'] = 'would_update'; $row['_entry'] = $entry; $row['_fingerprint'] = self::fingerprint( $item ); $row['_before'] = $raw;
		$row['_meta_exists'] = array();
		foreach ( self::FIELDS as $key ) { if ( ! in_array( $key, array( 'title', 'slug' ), true ) ) { $row['_meta_exists'][$key] = metadata_exists( 'post', $item->ID, BVOCAL_Repertoire::META_PREFIX . $key ); } }
		return $row;
	}

	public static function apply_row( $row ) {
		if ( ! current_user_can( 'manage_options' ) ) { throw new RuntimeException( 'Administrator context is required.' ); }
		$fresh = self::plan( $row['_entry'] );
		if ( 'would_update' !== $fresh['status'] || $row['_fingerprint'] !== $fresh['_fingerprint'] ) { throw new RuntimeException( 'Source or target changed after preflight; no further edits applied.' ); }
		$post = array( 'ID' => $row['target_id'] );
		foreach ( array( 'title' => 'post_title', 'slug' => 'post_name' ) as $field => $column ) { if ( array_key_exists( $field, $row['_resolved'] ) ) { $post[$column] = $row['_resolved'][$field]; } }
		if ( count( $post ) > 1 ) { $result = wp_update_post( wp_slash( $post ), true ); if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); } }
		foreach ( $row['_resolved'] as $key => $value ) { if ( ! in_array( $key, array( 'title', 'slug' ), true ) ) { update_post_meta( $row['target_id'], BVOCAL_Repertoire::META_PREFIX . $key, wp_slash( $value ) ); } }
		if ( self::canonical( self::raw_fields( get_post( $row['target_id'] ) ) ) !== $row['_entry']['reviewed_fields'] || 'draft' !== get_post_status( $row['target_id'] ) ) { throw new RuntimeException( 'Saved state differs from the plan; use the rollback file for review.' ); }
		$row['status'] = 'updated'; return $row;
	}

	private static function public_rows( $rows ) {
		foreach ( $rows as &$row ) { foreach ( array_keys( $row ) as $key ) { if ( 0 === strpos( $key, '_' ) ) { unset( $row[$key] ); } } }
		return $rows;
	}

	/**
	 * Preview or replay reviewed corrections on converted drafts.
	 *
	 * ## OPTIONS
	 *
	 * <manifest>
	 * : Path to final-corrections.json from the correction audit.
	 *
	 * [--song=<slug>]
	 * : Restrict to one source Song slug.
	 *
	 * [--apply]
	 * : Update drafts only, after a successful whole-selection preflight.
	 *
	 * [--backup=<path>]
	 * : Required with --apply; new absolute file path outside the WordPress root.
	 *
	 * [--format=<format>]
	 * : json (default) or table.
	 */
	public static function command( $args, $assoc_args ) {
		if ( count( $args ) !== 1 || array_diff( array_keys( $assoc_args ), array( 'song', 'apply', 'backup', 'format' ) ) ) { WP_CLI::error( 'Supply one manifest path; supported options are --song, --apply, --backup and --format.' ); }
		$apply = isset( $assoc_args['apply'] ); $format = $assoc_args['format'] ?? 'json';
		if ( ! in_array( $format, array( 'json', 'table' ), true ) || ( $apply && true !== $assoc_args['apply'] ) ) { WP_CLI::error( 'Use --format=json or table; --apply takes no value.' ); }
		if ( $apply && ! current_user_can( 'manage_options' ) ) { WP_CLI::error( 'Apply requires --user=<administrator>.' ); }
		if ( ! is_file( $args[0] ) || filesize( $args[0] ) > 5 * 1024 * 1024 ) { WP_CLI::error( 'Manifest must be a readable JSON file smaller than 5 MB.' ); }
		$manifest_text = file_get_contents( $args[0] );
		$manifest_sha256 = hash( 'sha256', $manifest_text );
		$data = json_decode( $manifest_text, true );
		if ( ! is_array( $data ) || 'bvocal-repertoire-correction-audit-v1' !== ( $data['format'] ?? '' ) || empty( $data['entries'] ) || ! is_array( $data['entries'] ) ) { WP_CLI::error( 'Invalid correction-audit manifest.' ); }
		$entries = $data['entries']; $seen = array();
		foreach ( $entries as $entry ) { $valid = self::validate_entry( $entry ); if ( is_wp_error( $valid ) ) { WP_CLI::error( $valid->get_error_message() ); } if ( isset( $seen[$entry['source_song_slug']] ) ) { WP_CLI::error( 'Duplicate source slug in manifest.' ); } $seen[$entry['source_song_slug']] = true; }
		if ( isset( $assoc_args['song'] ) ) { $entries = array_values( array_filter( $entries, function ( $e ) use ( $assoc_args ) { return $e['source_song_slug'] === $assoc_args['song']; } ) ); if ( ! $entries ) { WP_CLI::error( 'Source slug is not in this manifest.' ); } }
		$backup = $assoc_args['backup'] ?? '';
		if ( $apply ) {
			$dir = $backup ? realpath( dirname( $backup ) ) : false;
			if ( ! $dir || '/' !== substr( $backup, 0, 1 ) || 0 === strpos( trailingslashit( $dir ), trailingslashit( realpath( ABSPATH ) ) ) || file_exists( $backup ) ) { WP_CLI::error( 'Apply requires a new absolute --backup path outside the WordPress root.' ); }
			if ( ! add_option( BVOCAL_Song_Conversion::LOCK_OPTION, array( 'operation' => 'correction-replay', 'started_utc' => gmdate( 'c' ) ), '', false ) ) { WP_CLI::error( 'A conversion/replay holds the lock; inspect it before clearing a stale lock.' ); }
		}
		$error = false; $rows = array(); $active_index = null; $writes_started = false;
		try {
			$rows = array_map( array( __CLASS__, 'plan' ), $entries );
			$blocked = array_filter( $rows, function ( $r ) { return 0 === strpos( $r['status'], 'blocked_' ); } );
			if ( $apply && $blocked ) { $error = true; }
			elseif ( $apply ) {
				$changes = array_values( array_filter( $rows, function ( $r ) { return 'would_update' === $r['status']; } ) );
				if ( $changes ) {
					$contents = wp_json_encode( array( 'site' => home_url(), 'manifest_sha256' => $manifest_sha256, 'created_utc' => gmdate( 'c' ), 'items' => array_map( function ( $r ) { return array( 'target_id' => $r['target_id'], 'source_song_slug' => $r['source_song_slug'], 'before_fields' => $r['_before'], 'meta_existed' => $r['_meta_exists'], 'changes' => $r['changes'] ); }, $changes ) ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
					$handle = @fopen( $backup, 'x' );
					if ( ! $handle ) { throw new RuntimeException( 'Could not exclusively create the rollback file.' ); }
					chmod( $backup, 0600 ); $written = fwrite( $handle, $contents ); $flushed = fflush( $handle ); fclose( $handle );
					if ( false === $contents || $written !== strlen( $contents ) || ! $flushed ) { throw new RuntimeException( 'Rollback file could not be fully written; no edits applied.' ); }
					foreach ( $rows as $index => $row ) { if ( 'would_update' === $row['status'] ) { $active_index = $index; $writes_started = true; $rows[$index] = self::apply_row( $row ); $active_index = null; } }
				}
			}
		} catch ( Throwable $exception ) { $error = true; $message = $exception->getMessage(); if ( null !== $active_index ) { $rows[$active_index]['status'] = 'error_unverified'; $rows[$active_index]['reason'] = 'Inspect saved fields and the rollback file before retrying.'; } }
		finally { if ( $apply ) { delete_option( BVOCAL_Song_Conversion::LOCK_OPTION ); } }
		$rows = self::public_rows( $rows );
		$report = array( 'manifest_sha256' => $manifest_sha256, 'mode' => $apply ? 'apply-draft-corrections' : 'preview-only', 'site' => home_url(), 'counts' => array_count_values( wp_list_pluck( $rows, 'status' ) ), 'applied' => count( array_filter( $rows, function ( $r ) { return 'updated' === $r['status']; } ) ), 'rows' => $rows );
		if ( isset( $message ) ) { $report['error'] = $message; $report['partial_run_possible'] = $writes_started; }
		if ( $apply && $error && ! isset( $message ) ) { $report['error'] = 'Preflight blocked; no corrections were applied.'; }
		if ( 'json' === $format ) { WP_CLI::line( wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ); }
		else { $summary = array(); foreach ( $rows as $r ) { $summary[] = array( 'Song' => $r['source_song_slug'], 'Target' => $r['target_id'], 'Status' => $r['status'], 'Fields' => implode( ', ', wp_list_pluck( $r['changes'], 'field' ) ), 'Reason' => $r['reason'] ); } \WP_CLI\Utils\format_items( 'table', $summary, array( 'Song', 'Target', 'Status', 'Fields', 'Reason' ) ); if ( isset( $report['error'] ) ) { WP_CLI::warning( $report['error'] ); } }
		if ( $error ) { WP_CLI::halt( 1 ); }
	}
}
