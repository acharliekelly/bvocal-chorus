<?php
/**
 * Repertoire content type and its editorial fields.
 *
 * @package BVOCAL_Repertoire
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BVOCAL_Repertoire {
	const POST_TYPE = 'repertoire_item';
	const META_PREFIX = '_bvocal_repertoire_';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'add_meta_boxes_' . self::POST_TYPE, array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_meta_boxes' ) );
		add_filter( 'wp_insert_post_data', array( __CLASS__, 'keep_incomplete_item_as_draft' ), 10, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'render_author_required_notice' ) );
		add_filter( 'the_content', array( __CLASS__, 'append_public_details' ) );
		add_action( 'admin_footer-post.php', array( __CLASS__, 'render_recording_repeater_script' ) );
		add_action( 'admin_footer-post-new.php', array( __CLASS__, 'render_recording_repeater_script' ) );
	}

	public static function post_type_definition() {
		return array(
			'post_type' => self::POST_TYPE,
			'labels'    => array(
				'name'               => 'Repertoire',
				'singular_name'      => 'Repertoire Item',
				'add_new_item'       => 'Add Repertoire Item',
				'edit_item'          => 'Edit Repertoire Item',
				'new_item'           => 'New Repertoire Item',
				'view_item'          => 'View Repertoire Item',
				'search_items'       => 'Search Repertoire',
				'not_found'          => 'No repertoire items found.',
				'not_found_in_trash' => 'No repertoire items found in Trash.',
				'menu_name'          => 'Repertoire',
			),
			'public'    => true,
			'show_in_rest' => true,
			'has_archive' => true,
			'rewrite'     => array( 'slug' => 'repertoire' ),
			'menu_icon'   => 'dashicons-format-audio',
			'supports'    => array( 'title' ),
		);
	}

	public static function register_post_type() {
		$definition = self::post_type_definition();
		register_post_type( $definition['post_type'], $definition );
	}

	public static function add_meta_boxes() {
		add_meta_box( 'bvocal-repertoire-info', 'Repertoire information', array( __CLASS__, 'render_information_box' ), self::POST_TYPE, 'normal', 'high' );
		add_meta_box( 'bvocal-repertoire-lyrics', 'Lyrics', array( __CLASS__, 'render_lyrics_box' ), self::POST_TYPE, 'normal', 'default' );
		add_meta_box( 'bvocal-repertoire-materials', 'Score and recordings', array( __CLASS__, 'render_materials_box' ), self::POST_TYPE, 'normal', 'default' );
	}

	private static function value( $post_id, $key ) {
		return get_post_meta( $post_id, self::META_PREFIX . $key, true );
	}

	public static function drive_file_id( $url ) {
		if ( preg_match( '#drive\.google\.com/file/d/([^/?]+)#', $url, $matches ) ) {
			return $matches[1];
		}
		if ( preg_match( '#(?:[?&])id=([^&]+)#', $url, $matches ) ) {
			return $matches[1];
		}
		return '';
	}

	public static function drive_preview_url( $url ) {
		$file_id = self::drive_file_id( $url );
		return $file_id ? 'https://drive.google.com/file/d/' . rawurlencode( $file_id ) . '/preview' : $url;
	}

	public static function drive_audio_url( $url ) {
		$file_id = self::drive_file_id( $url );
		return $file_id ? 'https://drive.google.com/uc?export=download&id=' . rawurlencode( $file_id ) : $url;
	}

	public static function recording_preview_markup( $label, $url ) {
		return '<li class="bvocal-repertoire__recording"><strong>' . esc_html( $label ) . '</strong><div class="bvocal-repertoire__recording-preview"><iframe title="' . esc_attr( $label . ' recording' ) . '" src="' . esc_url( self::drive_preview_url( $url ) ) . '" loading="lazy"></iframe></div><a class="bvocal-repertoire__recording-link" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">Open in Google Drive</a></li>';
	}

	public static function render_information_box( $post ) {
		wp_nonce_field( 'bvocal_save_repertoire', 'bvocal_repertoire_nonce' );
		$fields = array(
			'author'    => array( 'Author', true ),
			'lyricist'  => array( 'Lyricist', false ),
			'arranger'  => array( 'Arranger', false ),
			'language'  => array( 'Language', false ),
			'notes'     => array( 'Source or notes', false ),
		);
		echo '<p>The title and author identify this repertoire item. Other details are optional.</p>';
		foreach ( $fields as $key => $field ) {
			printf( '<p><label for="bvocal-repertoire-%1$s"><strong>%2$s%3$s</strong></label><br>', esc_attr( $key ), esc_html( $field[0] ), $field[1] ? ' <span aria-hidden="true">*</span>' : '' );
			if ( 'notes' === $key ) {
				printf( '<textarea class="large-text" rows="3" id="bvocal-repertoire-%1$s" name="bvocal_repertoire[%1$s]">%2$s</textarea></p>', esc_attr( $key ), esc_textarea( self::value( $post->ID, $key ) ) );
			} else {
				printf( '<input class="regular-text" type="text" id="bvocal-repertoire-%1$s" name="bvocal_repertoire[%1$s]" value="%2$s"%3$s></p>', esc_attr( $key ), esc_attr( self::value( $post->ID, $key ) ), $field[1] ? ' required' : '' );
			}
		}
	}

	public static function render_lyrics_box( $post ) {
		echo '<p>Paste the lyrics here. Line breaks are preserved on the public page.</p>';
		printf( '<textarea class="large-text code" rows="18" name="bvocal_repertoire[lyrics]">%s</textarea>', esc_textarea( self::value( $post->ID, 'lyrics' ) ) );
	}

	public static function render_materials_box( $post ) {
		$score_url  = self::value( $post->ID, 'score_url' );
		$recordings = self::value( $post->ID, 'recordings' );
		$recordings = is_array( $recordings ) ? $recordings : array();
		echo '<p>Use Google Drive links. Files remain in Drive; WordPress only links to them.</p>';
		printf( '<p><label for="bvocal-repertoire-score"><strong>Score PDF link</strong></label><br><input class="large-text" type="url" id="bvocal-repertoire-score" name="bvocal_repertoire[score_url]" value="%s" placeholder="https://drive.google.com/..."></p>', esc_attr( $score_url ) );
		echo '<p><strong>Practice recordings</strong></p><div id="bvocal-repertoire-recordings">';
		foreach ( $recordings as $recording ) {
			self::render_recording_row( $recording );
		}
		echo '</div><p><button type="button" class="button" id="bvocal-add-recording">Add recording</button></p>';
		echo '<template id="bvocal-recording-template">';
		self::render_recording_row( array() );
		echo '</template>';
	}

	private static function render_recording_row( $recording ) {
		$label = isset( $recording['label'] ) ? $recording['label'] : '';
		$url   = isset( $recording['url'] ) ? $recording['url'] : '';
		printf( '<p class="bvocal-recording-row"><label>Label <input type="text" name="bvocal_repertoire[recording_label][]" value="%1$s" placeholder="e.g. Alto practice track"></label> <label>Google Drive MP3 link <input class="regular-text" type="url" name="bvocal_repertoire[recording_url][]" value="%2$s" placeholder="https://drive.google.com/..."></label> <button type="button" class="button-link-delete bvocal-remove-recording">Remove</button></p>', esc_attr( $label ), esc_attr( $url ) );
	}

	public static function keep_incomplete_item_as_draft( $data, $postarr ) {
		if ( self::POST_TYPE !== ( $data['post_type'] ?? '' ) || 'publish' !== ( $data['post_status'] ?? '' ) ) {
			return $data;
		}
		if ( ! is_admin() || ! isset( $_POST['bvocal_repertoire_nonce'] ) ) {
			return $data;
		}
		$author = isset( $_POST['bvocal_repertoire']['author'] ) ? sanitize_text_field( wp_unslash( $_POST['bvocal_repertoire']['author'] ) ) : '';
		if ( '' === $author ) {
			$data['post_status'] = 'draft';
			add_filter( 'redirect_post_location', array( __CLASS__, 'add_author_required_notice' ) );
		}
		return $data;
	}

	public static function add_author_required_notice( $location ) {
		return add_query_arg( 'bvocal_repertoire_error', 'author_required', $location );
	}

	public static function render_author_required_notice() {
		if ( ! isset( $_GET['bvocal_repertoire_error'] ) || 'author_required' !== $_GET['bvocal_repertoire_error'] ) {
			return;
		}
		echo '<div class="notice notice-error"><p>A Repertoire Item needs an author before it can be published. It has been kept as a draft.</p></div>';
	}

	public static function save_meta_boxes( $post_id ) {
		if ( ! isset( $_POST['bvocal_repertoire_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bvocal_repertoire_nonce'] ) ), 'bvocal_save_repertoire' ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$submitted = isset( $_POST['bvocal_repertoire'] ) && is_array( $_POST['bvocal_repertoire'] ) ? wp_unslash( $_POST['bvocal_repertoire'] ) : array();
		foreach ( array( 'author', 'lyricist', 'arranger', 'language', 'notes', 'lyrics' ) as $key ) {
			update_post_meta( $post_id, self::META_PREFIX . $key, isset( $submitted[ $key ] ) ? sanitize_textarea_field( $submitted[ $key ] ) : '' );
		}
		update_post_meta( $post_id, self::META_PREFIX . 'score_url', isset( $submitted['score_url'] ) ? esc_url_raw( $submitted['score_url'] ) : '' );
		$recordings = array();
		$labels     = isset( $submitted['recording_label'] ) && is_array( $submitted['recording_label'] ) ? $submitted['recording_label'] : array();
		$urls       = isset( $submitted['recording_url'] ) && is_array( $submitted['recording_url'] ) ? $submitted['recording_url'] : array();
		foreach ( $urls as $index => $url ) {
			$url   = esc_url_raw( $url );
			$label = isset( $labels[ $index ] ) ? sanitize_text_field( $labels[ $index ] ) : '';
			if ( '' !== $url ) {
				$recordings[] = array( 'label' => $label, 'url' => $url );
			}
		}
		update_post_meta( $post_id, self::META_PREFIX . 'recordings', $recordings );
	}

	public static function append_public_details( $content ) {
		if ( ! is_singular( self::POST_TYPE ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$post_id = get_the_ID();
		$details = array_filter(
			array(
				'Author'   => self::value( $post_id, 'author' ),
				'Lyricist' => self::value( $post_id, 'lyricist' ),
				'Arranger' => self::value( $post_id, 'arranger' ),
				'Language' => self::value( $post_id, 'language' ),
			)
		);
		$output = '<div class="bvocal-repertoire">';
		if ( $details ) {
			$output .= '<dl class="bvocal-repertoire__details">';
			foreach ( $details as $label => $value ) {
				$output .= '<dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $value ) . '</dd>';
			}
			$output .= '</dl>';
		}
		$notes = self::value( $post_id, 'notes' );
		if ( $notes ) {
			$output .= '<p class="bvocal-repertoire__notes">' . nl2br( esc_html( $notes ) ) . '</p>';
		}
		$lyrics = self::value( $post_id, 'lyrics' );
		if ( $lyrics ) {
			$output .= '<section><h2>Lyrics</h2><div class="bvocal-repertoire__lyrics">' . nl2br( esc_html( $lyrics ) ) . '</div></section>';
		}
		$score_url = self::value( $post_id, 'score_url' );
		if ( $score_url ) {
			$output .= '<section><h2>Score</h2><div class="bvocal-repertoire__score"><iframe title="' . esc_attr( get_the_title( $post_id ) . ' score' ) . '" src="' . esc_url( self::drive_preview_url( $score_url ) ) . '" loading="lazy"></iframe></div><p><a href="' . esc_url( $score_url ) . '" target="_blank" rel="noopener noreferrer">Open score PDF in Google Drive</a></p></section>';
		}
		$recordings = self::value( $post_id, 'recordings' );
		if ( is_array( $recordings ) && $recordings ) {
			$output .= '<section><h2>Practice recordings</h2><ul>';
			foreach ( $recordings as $recording ) {
				$label = ! empty( $recording['label'] ) ? $recording['label'] : 'Open recording';
				$output .= self::recording_preview_markup( $label, $recording['url'] );
			}
			$output .= '</ul></section>';
		}
		return $content . $output . '</div>';
	}

	public static function render_recording_repeater_script() {
		$screen = get_current_screen();
		if ( ! $screen || self::POST_TYPE !== $screen->post_type ) {
			return;
		}
		?>
		<script>
			document.addEventListener('click', function (event) {
				if (event.target.id === 'bvocal-add-recording') {
					document.querySelector('#bvocal-repertoire-recordings').insertAdjacentHTML('beforeend', document.querySelector('#bvocal-recording-template').innerHTML);
				}
				if (event.target.classList.contains('bvocal-remove-recording')) {
					event.target.closest('.bvocal-recording-row').remove();
				}
			});
		</script>
		<?php
	}
}
