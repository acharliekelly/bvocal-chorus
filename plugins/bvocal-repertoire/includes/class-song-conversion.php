<?php
/** Draft creation from the read-only Song mapping. Loaded only by WP-CLI. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class BVOCAL_Song_Conversion {
	const SOURCE_META = '_bvocal_repertoire_source_song_id';
	const HASH_META = '_bvocal_repertoire_source_song_fingerprint';
	const RUN_META = '_bvocal_repertoire_conversion_run';
	const LOCK_OPTION = 'bvocal_repertoire_conversion_lock';

	private static function normalized_text( $text ) {
		return strtolower( trim( preg_replace( '/\s+/u', ' ', str_replace( "\xc2\xa0", ' ', $text ) ) ) );
	}

	/** Fingerprint includes all mapped fields, title and media identities. */
	private static function fingerprint( $report ) {
		return hash( 'sha256', wp_json_encode( array( $report['source'], $report['proposed'], $report['media'], isset( $report['legacy_fields_for_review'] ) ? $report['legacy_fields_for_review'] : array() ) ) );
	}

	private static function all_statuses() {
		return array_keys( get_post_stati() );
	}

	/** User-confirmed distinct Songs with intentionally identical lyrics. */
	public static function shared_lyrics_allowed( $first_slug, $second_slug ) {
		$pair = array( $first_slug, $second_slug );
		sort( $pair, SORT_STRING );
		return array( 'first-amendment-2', 'hostile-government-take-over' ) === $pair;
	}

	/** Detect existing manual items; a match is a review flag, never an automatic merge. */
	private static function conflicts( $song, $report ) {
		$matches = array();
		$fields = $report['proposed'];
		$urls = array_column( $fields['recordings'], 'url' );
		if ( $fields['score_url'] ) { $urls[] = $fields['score_url']; }
		$drive_ids = array_filter( array_map( array( 'BVOCAL_Repertoire', 'drive_file_id' ), $urls ) );
		foreach ( get_posts( array( 'post_type' => BVOCAL_Repertoire::POST_TYPE, 'post_status' => self::all_statuses(), 'posts_per_page' => -1 ) ) as $item ) {
			$reasons = array();
			if ( self::normalized_text( $song->post_title ) === self::normalized_text( $item->post_title ) || ( $song->post_name && $song->post_name === $item->post_name ) ) { $reasons[] = 'title or slug'; }
			$lyrics = get_post_meta( $item->ID, BVOCAL_Repertoire::META_PREFIX . 'lyrics', true );
			$other_source_id = (int) get_post_meta( $item->ID, self::SOURCE_META, true );
			$other_source = $other_source_id ? get_post( $other_source_id ) : null;
			$other_slug = $other_source && 'song' === $other_source->post_type ? $other_source->post_name : '';
			if ( ! self::shared_lyrics_allowed( $song->post_name, $other_slug ) && $fields['lyrics'] && is_string( $lyrics ) && self::normalized_text( $fields['lyrics'] ) === self::normalized_text( $lyrics ) ) { $reasons[] = 'lyrics'; }
			$recordings = get_post_meta( $item->ID, BVOCAL_Repertoire::META_PREFIX . 'recordings', true );
			$existing_urls = is_array( $recordings ) ? array_column( $recordings, 'url' ) : array();
			$score = get_post_meta( $item->ID, BVOCAL_Repertoire::META_PREFIX . 'score_url', true );
			if ( is_string( $score ) && $score ) { $existing_urls[] = $score; }
			$existing_drive_ids = array_filter( array_map( array( 'BVOCAL_Repertoire', 'drive_file_id' ), $existing_urls ) );
			if ( array_intersect( $drive_ids, $existing_drive_ids ) || array_intersect( $urls, $existing_urls ) ) { $reasons[] = 'shared score or recording'; }
			if ( $reasons ) { $matches[] = array( 'id' => $item->ID, 'title' => $item->post_title, 'status' => $item->post_status, 'matches' => $reasons ); }
		}
		return $matches;
	}

	/** Plan or create one draft; existing items and original Songs are never updated. */
	public static function convert_song( $song_id, $apply = false, $run_id = '' ) {
		$row = array( 'song_id' => (int) $song_id, 'title' => '', 'status' => '', 'repertoire_ids' => array(), 'reason' => '', 'warnings' => array() );
		if ( $apply && ! current_user_can( 'manage_options' ) ) {
			$row['status'] = 'blocked_permission'; $row['reason'] = 'Use an administrator account for draft creation.'; return $row;
		}
		$song = get_post( $song_id );
		if ( ! $song || 'song' !== $song->post_type || 'publish' !== $song->post_status ) {
			$row['status'] = 'blocked_source'; $row['reason'] = 'Only published Songs are eligible; private/test items are excluded.'; return $row;
		}
		$row['title'] = $song->post_title;
		if ( '' !== $song->post_password ) {
			$row['status'] = 'blocked_protected'; $row['reason'] = 'Password-protected source requires manual review.'; return $row;
		}
		$report = BVOCAL_Song_Preview::preview( $song );
		$row['warnings'] = $report['warnings'];
		$fingerprint = self::fingerprint( $report );
		$existing = get_posts( array( 'post_type' => BVOCAL_Repertoire::POST_TYPE, 'post_status' => self::all_statuses(), 'posts_per_page' => -1, 'meta_key' => self::SOURCE_META, 'meta_value' => (string) $song->ID ) );
		if ( $existing ) {
			$row['repertoire_ids'] = array_map( 'intval', wp_list_pluck( $existing, 'ID' ) );
			if ( count( $existing ) > 1 ) {
				$row['status'] = 'blocked_conflict'; $row['reason'] = 'Multiple items track this source; reconcile manually.';
			} elseif ( 'trash' === $existing[0]->post_status ) {
				$row['status'] = 'skipped_trashed'; $row['reason'] = 'A converted item is in Trash; restore or review it instead of recreating it.';
			} elseif ( get_post_meta( $existing[0]->ID, self::HASH_META, true ) !== $fingerprint ) {
				$row['status'] = 'skipped_source_changed'; $row['reason'] = 'Source or mapped media changed since conversion; existing item preserved.';
			} else { $row['status'] = 'skipped_existing'; $row['reason'] = 'Existing conversion preserved, including manual edits.'; }
			return $row;
		}
		$conflicts = self::conflicts( $song, $report );
		if ( $conflicts ) {
			$row['status'] = 'blocked_conflict'; $row['reason'] = 'Existing Repertoire items may represent this Song; no automatic merge.';
			$row['conflicts'] = $conflicts; $row['repertoire_ids'] = wp_list_pluck( $conflicts, 'id' ); return $row;
		}
		if ( $report['unmapped'] ) {
			$row['status'] = 'blocked_unmapped'; $row['reason'] = 'Resolve unmapped components before creating a draft.'; $row['unmapped'] = $report['unmapped']; return $row;
		}
		if ( ! $apply ) {
			$row['status'] = 'would_create'; $row['reason'] = 'Would create a draft; warnings still require editorial review.'; return $row;
		}
		$meta = array(
			self::SOURCE_META => $song->ID,
			self::HASH_META => $fingerprint,
			self::RUN_META => $run_id,
			'_bvocal_repertoire_conversion_warnings' => $report['warnings'],
		);
		foreach ( array( 'author', 'arranger', 'lyricist', 'language', 'notes', 'lyrics', 'score_url', 'recordings', 'links' ) as $field ) {
			$meta[BVOCAL_Repertoire::META_PREFIX . $field] = $report['proposed'][$field];
		}
		$id = wp_insert_post( wp_slash( array( 'post_type' => BVOCAL_Repertoire::POST_TYPE, 'post_status' => 'draft', 'post_title' => $song->post_title, 'post_name' => $song->post_name, 'meta_input' => $meta ) ), true );
		if ( is_wp_error( $id ) ) { $row['status'] = 'error'; $row['reason'] = $id->get_error_message(); return $row; }
		$row['status'] = 'created'; $row['reason'] = 'Draft created; review credits, labels and media before publishing.'; $row['repertoire_ids'] = array( $id );
		return $row;
	}

	/**
	 * Plan Songs conversion, or explicitly create new Repertoire drafts.
	 *
	 * ## OPTIONS
	 *
	 * [--song=<id>]
	 * : Select one published Song on this site.
	 *
	 * [--apply]
	 * : Create eligible drafts. Requires --user=<administrator>.
	 *
	 * [--format=<format>]
	 * : json (default) or table.
	 *
	 * ## EXAMPLES
	 *
	 *     wp bvocal repertoire convert-songs --format=table
	 *     wp bvocal repertoire convert-songs --apply --user=admin
	 */
	public static function command( $args, $assoc_args ) {
		if ( $args || array_diff( array_keys( $assoc_args ), array( 'song', 'apply', 'format' ) ) ) { WP_CLI::error( 'Supported options are --song, --apply and --format.' ); }
		$apply = isset( $assoc_args['apply'] );
		if ( $apply && true !== $assoc_args['apply'] ) { WP_CLI::error( '--apply is a flag and takes no value.' ); }
		if ( $apply && ! current_user_can( 'manage_options' ) ) { WP_CLI::error( 'Draft creation requires --user=<administrator>.' ); }
		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'json';
		if ( ! in_array( $format, array( 'json', 'table' ), true ) ) { WP_CLI::error( 'Use --format=json or --format=table.' ); }
		$query = array( 'post_type' => 'song', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' );
		if ( isset( $assoc_args['song'] ) ) {
			if ( ! ctype_digit( (string) $assoc_args['song'] ) || (int) $assoc_args['song'] < 1 ) { WP_CLI::error( '--song must be a positive local Song ID.' ); }
			$query['post__in'] = array( (int) $assoc_args['song'] );
		}
		$songs = get_posts( $query );
		if ( isset( $assoc_args['song'] ) && ! $songs ) { WP_CLI::error( 'No published Song found for that ID.' ); }
		$run_id = wp_generate_uuid4();
		if ( $apply && ! add_option( self::LOCK_OPTION, array( 'run_id' => $run_id, 'started_utc' => gmdate( 'c' ) ), '', false ) ) {
			WP_CLI::error( 'Another conversion holds the lock. Inspect that run before removing a stale lock.' );
		}
		$rows = array();
		try {
			foreach ( $songs as $song ) { $rows[] = self::convert_song( $song->ID, $apply, $run_id ); }
		} finally { if ( $apply ) { delete_option( self::LOCK_OPTION ); } }
		$counts = array_count_values( wp_list_pluck( $rows, 'status' ) );
		if ( 'json' === $format ) {
			WP_CLI::line( wp_json_encode( array( 'mode' => $apply ? 'create-drafts' : 'dry-run', 'site' => home_url(), 'run_id' => $apply ? $run_id : null, 'counts' => $counts, 'songs' => $rows ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
		} else {
			if ( $apply ) { WP_CLI::line( 'Conversion run: ' . $run_id ); }
			$summary = array();
			foreach ( $rows as $row ) { $summary[] = array( 'Song' => $row['song_id'], 'Title' => $row['title'], 'Status' => $row['status'], 'Repertoire' => implode( ',', $row['repertoire_ids'] ), 'Warnings' => count( $row['warnings'] ), 'Reason' => $row['reason'] ); }
			\WP_CLI\Utils\format_items( 'table', $summary, array( 'Song', 'Title', 'Status', 'Repertoire', 'Warnings', 'Reason' ) );
		}
	}
}
