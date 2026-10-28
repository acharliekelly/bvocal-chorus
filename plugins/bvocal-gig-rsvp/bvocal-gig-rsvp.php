<?php
/**
 * Plugin Name: BVOCAL Gig RSVP
 * Description: Per-Gig RSVPs that keep each action's MailerLite recipients separate.
 * Version: 0.1.0
 * Author: BVOCAL
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-group-naming.php';

class BVOCAL_Gig_RSVP {
	const META_ENABLED       = '_bvocal_rsvp_enabled';
	const META_GROUP_ID      = '_bvocal_rsvp_mailerlite_group_id';
	const META_GROUP_NAME    = '_bvocal_rsvp_mailerlite_group_name';
	const META_GROUP_DELETED = '_bvocal_rsvp_mailerlite_group_deleted_at';
	const META_REQUESTED     = '_bvocal_rsvp_requested';
	const META_ERROR         = '_bvocal_rsvp_error';
	const TABLE_SUFFIX       = 'bvocal_gig_rsvps';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'add_meta_boxes_tribe_events', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_tribe_events', array( $this, 'save_rsvp_request' ), 10, 3 );
		add_action( 'wp_after_insert_post', array( $this, 'provision_after_event_save' ), 20, 4 );
		add_action( 'admin_post_bvocal_rsvp_enable', array( $this, 'enable_rsvp' ) );
		add_action( 'admin_post_bvocal_rsvp_delete_group', array( $this, 'delete_group' ) );
		add_action( 'admin_post_bvocal_rsvp_submit', array( $this, 'submit_rsvp' ) );
		add_action( 'admin_post_nopriv_bvocal_rsvp_submit', array( $this, 'submit_rsvp' ) );
		add_filter( 'the_content', array( $this, 'append_form_to_event' ) );
	}

	public static function activate() {
		global $wpdb;
		$table_name      = $wpdb->prefix . self::TABLE_SUFFIX;
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta(
			"CREATE TABLE $table_name (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				event_id bigint(20) unsigned NOT NULL,
				email varchar(100) NOT NULL,
				name varchar(190) NOT NULL,
				attendance varchar(20) NOT NULL,
				ride_help varchar(20) NOT NULL DEFAULT '',
				comments text NOT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY event_email (event_id,email),
				KEY event_id (event_id)
			) $charset_collate;"
		);
	}

	private function can_manage() {
		return current_user_can( 'manage_options' );
	}

	public function add_menu_page() {
		add_menu_page( 'Gig RSVP', 'Gig RSVP', 'manage_options', 'bvocal-gig-rsvp', array( $this, 'render_dashboard' ), 'dashicons-groups', 58 );
	}

	public function add_meta_box() {
		add_meta_box( 'bvocal-gig-rsvp', 'Gig RSVP', array( $this, 'render_meta_box' ), 'tribe_events', 'side' );
	}

	public function render_meta_box( $post ) {
		$group_id = get_post_meta( $post->ID, self::META_GROUP_ID, true );
		if ( $group_id ) {
			echo '<p><strong>RSVP is enabled.</strong></p><p>MailerLite group: ' . esc_html( get_post_meta( $post->ID, self::META_GROUP_NAME, true ) ) . '</p>';
			return;
		}

		$requested = (bool) get_post_meta( $post->ID, self::META_REQUESTED, true );
		wp_nonce_field( 'bvocal_rsvp_request_' . $post->ID, 'bvocal_rsvp_request_nonce' );
		?>
		<p><label><input type="checkbox" name="bvocal_rsvp_requested" value="1" <?php checked( $requested ); ?>> Enable RSVP for this Gig</label></p>
		<p class="description">Saving a draft only records this choice. The MailerLite group will be created after this future Event is published.</p>
		<?php
	}

	public function save_rsvp_request( $post_id, $post, $update ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$nonce = isset( $_POST['bvocal_rsvp_request_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['bvocal_rsvp_request_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'bvocal_rsvp_request_' . $post_id ) ) {
			return;
		}

		if ( isset( $_POST['bvocal_rsvp_requested'] ) ) {
			update_post_meta( $post_id, self::META_REQUESTED, '1' );
		} else {
			delete_post_meta( $post_id, self::META_REQUESTED );
		}
	}

	public function provision_after_event_save( $event_id, $post, $update, $post_before ) {
		if ( 'tribe_events' !== $post->post_type || 'publish' !== $post->post_status || ( $post_before && 'publish' === $post_before->post_status ) ) {
			return;
		}
		if ( ! get_post_meta( $event_id, self::META_REQUESTED, true ) || get_post_meta( $event_id, self::META_GROUP_ID, true ) || ! $this->is_future_event( $event_id ) ) {
			return;
		}

		$this->provision_group( $event_id );
	}

	private function render_enable_button( $event_id, $label = 'Enable RSVP for this Gig' ) {
		if ( ! $this->has_mailerlite_token() ) {
			echo '<p>MailerLite is not connected yet. Add its API token before enabling an RSVP.</p>';
			return;
		}
		$url = wp_nonce_url(
			add_query_arg(
				array(
					'action'   => 'bvocal_rsvp_enable',
					'event_id' => absint( $event_id ),
				),
				admin_url( 'admin-post.php' )
			),
			'bvocal_rsvp_enable_' . $event_id
		);
		?>
		<p>Create a separate MailerLite group for this Gig. RSVPs will appear here automatically.</p>
		<p><a class="button button-secondary" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a></p>
		<?php
	}

	public function render_dashboard() {
		if ( ! $this->can_manage() ) {
			return;
		}
		$events = get_posts(
			array(
				'post_type'      => 'tribe_events',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'meta_key'       => '_EventStartDate',
				'orderby'        => 'meta_value',
				'order'          => 'ASC',
			)
		);
		?>
		<div class="wrap">
			<h1>Gig RSVP</h1>
			<p>Each Gig has its own MailerLite group, so updates go only to people who signed up for that action.</p>
			<?php $this->render_admin_notice(); ?>
			<?php if ( ! $this->has_mailerlite_token() ) : ?>
				<div class="notice notice-warning"><p>MailerLite is not connected. Add the API token in the MailerLite plugin before enabling an RSVP.</p></div>
			<?php endif; ?>
			<?php if ( empty( $events ) ) : ?>
				<p>No Gig events are available yet. Create an Event in the calendar, then return here to enable its RSVP.</p>
			<?php else : ?>
				<table class="widefat striped">
					<thead><tr><th>Gig</th><th>Date</th><th>MailerLite group</th><th>RSVPs</th><th>Action</th></tr></thead>
					<tbody>
					<?php foreach ( $events as $event ) : ?>
						<?php $this->render_event_row( $event ); ?>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
			<p><em>Future direction: this dashboard can later help turn a completed Gig into a Past Event and collect media. That conversion is not part of this first release.</em></p>
		</div>
		<?php
	}

	private function render_event_row( $event ) {
		$event_id    = $event->ID;
		$date        = $this->event_date( $event_id );
		$group_id    = get_post_meta( $event_id, self::META_GROUP_ID, true );
		$group_name  = get_post_meta( $event_id, self::META_GROUP_NAME, true );
		$deleted_at  = get_post_meta( $event_id, self::META_GROUP_DELETED, true );
		$error       = get_post_meta( $event_id, self::META_ERROR, true );
		$is_past     = $date && strtotime( $date . ' 23:59:59' ) < current_time( 'timestamp' );
		$is_ready    = $this->is_published_future_event( $event_id );
		$rsvp_count  = $this->rsvp_count( $event_id );
		?>
		<tr>
			<td><a href="<?php echo esc_url( get_edit_post_link( $event_id ) ); ?>"><?php echo esc_html( get_the_title( $event_id ) ); ?></a></td>
			<td><?php echo esc_html( $date ? $date : 'Date TBD' ); ?></td>
			<td>
				<?php if ( $group_name ) : ?>
					<?php echo esc_html( $group_name ); ?>
				<?php elseif ( $deleted_at ) : ?>
					<?php echo esc_html( 'Deleted ' . $deleted_at ); ?>
				<?php elseif ( $is_ready && $error ) : ?>
					<div class="notice notice-error inline"><p><strong>RSVP needs attention.</strong> <?php echo esc_html( $error ); ?></p><p><strong>Temporary fallback:</strong> Create a Google Form for this Gig, then paste its responder link into the Event description so visitors have a way to sign up.</p></div>
				<?php else : ?>
					<?php echo esc_html( $is_ready ? 'Not enabled' : 'Unavailable until this Event is published and future-dated' ); ?>
				<?php endif; ?>
			</td>
			<td><?php echo esc_html( (string) $rsvp_count ); ?></td>
			<td>
				<?php if ( $group_id && $is_past ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Delete this MailerLite group? This will not delete subscribers, but it cannot be undone.');">
						<input type="hidden" name="action" value="bvocal_rsvp_delete_group">
						<input type="hidden" name="event_id" value="<?php echo esc_attr( $event_id ); ?>">
						<?php wp_nonce_field( 'bvocal_rsvp_delete_group_' . $event_id ); ?>
						<?php submit_button( 'Delete RSVP group', 'delete', 'submit', false ); ?>
					</form>
				<?php elseif ( $group_id ) : ?>
					<span>Group ready</span>
				<?php elseif ( $is_ready && $error ) : ?>
					<?php $this->render_enable_button( $event_id, 'Retry MailerLite RSVP' ); ?>
				<?php elseif ( $is_ready && ! $deleted_at ) : ?>
					<?php $this->render_enable_button( $event_id ); ?>
				<?php else : ?>
					<span><?php echo esc_html( $deleted_at ? 'Group deleted' : 'No action available' ); ?></span>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	public function enable_rsvp() {
		$event_id = isset( $_REQUEST['event_id'] ) ? absint( wp_unslash( $_REQUEST['event_id'] ) ) : 0;
		$this->assert_manage_request( 'bvocal_rsvp_enable_' . $event_id );

		if ( ! $this->is_published_future_event( $event_id ) || get_post_meta( $event_id, self::META_GROUP_ID, true ) ) {
			$this->redirect_with_notice( 'invalid-event', 'error' );
		}

		update_post_meta( $event_id, self::META_ENABLED, '1' );
		update_post_meta( $event_id, self::META_REQUESTED, '1' );
		$result = $this->provision_group( $event_id );
		if ( is_wp_error( $result ) ) {
			$this->redirect_with_notice( $result->get_error_message(), 'error' );
		}

		$this->redirect_with_notice( 'RSVP enabled for ' . get_the_title( $event_id ) . '.', 'success' );
	}

	private function provision_group( $event_id ) {
		$group_name = BVOCAL_Gig_RSVP_Group_Naming::make( get_the_title( $event_id ), $this->event_date( $event_id ) );
		$result     = $this->mailerlite_request( 'POST', '/groups', array( 'name' => $group_name ) );
		if ( is_wp_error( $result ) || empty( $result['data']['id'] ) ) {
			$error = is_wp_error( $result ) ? $result : new WP_Error( 'bvocal_mailerlite_missing_group', 'MailerLite did not return a group ID.' );
			update_post_meta( $event_id, self::META_ERROR, $error->get_error_message() );
			return $error;
		}

		update_post_meta( $event_id, self::META_ENABLED, '1' );
		update_post_meta( $event_id, self::META_GROUP_ID, (string) $result['data']['id'] );
		update_post_meta( $event_id, self::META_GROUP_NAME, $group_name );
		delete_post_meta( $event_id, self::META_GROUP_DELETED );
		delete_post_meta( $event_id, self::META_ERROR );
		return true;
	}

	public function delete_group() {
		$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
		$this->assert_manage_request( 'bvocal_rsvp_delete_group_' . $event_id );
		$group_id = get_post_meta( $event_id, self::META_GROUP_ID, true );
		$date     = $this->event_date( $event_id );

		if ( ! $group_id || ! $date || strtotime( $date . ' 23:59:59' ) >= current_time( 'timestamp' ) ) {
			$this->redirect_with_notice( 'RSVP groups can only be deleted manually after the Gig has passed.', 'error' );
		}

		$result = $this->mailerlite_request( 'DELETE', '/groups/' . rawurlencode( $group_id ) );
		if ( is_wp_error( $result ) ) {
			$this->redirect_with_notice( $result->get_error_message(), 'error' );
		}

		delete_post_meta( $event_id, self::META_GROUP_ID );
		delete_post_meta( $event_id, self::META_ENABLED );
		update_post_meta( $event_id, self::META_GROUP_DELETED, current_time( 'Y-m-d H:i:s' ) );
		$this->redirect_with_notice( 'MailerLite group deleted. RSVP records remain available locally.', 'success' );
	}

	public function append_form_to_event( $content ) {
		$event_id = get_the_ID();
		$date     = $this->event_date( $event_id );
		$is_past  = $date && strtotime( $date . ' 23:59:59' ) < current_time( 'timestamp' );
		if ( ! is_singular( 'tribe_events' ) || ! in_the_loop() || ! is_main_query() || ! get_post_meta( $event_id, self::META_GROUP_ID, true ) || $is_past ) {
			return $content;
		}

		return $content . $this->form_markup( $event_id );
	}

	private function form_markup( $event_id ) {
		$notice = isset( $_GET['bvocal_rsvp'] ) ? sanitize_key( wp_unslash( $_GET['bvocal_rsvp'] ) ) : '';
		ob_start();
		?>
		<section class="bvocal-gig-rsvp" aria-labelledby="bvocal-gig-rsvp-title">
			<h2 id="bvocal-gig-rsvp-title">RSVP for this action</h2>
			<?php if ( 'thanks' === $notice ) : ?><p role="status"><strong>Thank you. Your RSVP has been recorded.</strong></p><?php endif; ?>
			<?php if ( 'error' === $notice ) : ?><p role="alert"><strong>We could not save that RSVP. Please check the form and try again.</strong></p><?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="bvocal_rsvp_submit">
				<input type="hidden" name="event_id" value="<?php echo esc_attr( $event_id ); ?>">
				<?php wp_nonce_field( 'bvocal_rsvp_submit_' . $event_id ); ?>
				<p><label for="bvocal-rsvp-name">Name <span aria-hidden="true">*</span></label><br><input id="bvocal-rsvp-name" name="name" type="text" required></p>
				<p><label for="bvocal-rsvp-email">Email <span aria-hidden="true">*</span></label><br><input id="bvocal-rsvp-email" name="email" type="email" required></p>
				<fieldset><legend>Will you attend? <span aria-hidden="true">*</span></legend>
					<label><input name="attendance" type="radio" value="yes" required> Yes</label>
					<label><input name="attendance" type="radio" value="maybe"> Maybe</label>
					<label><input name="attendance" type="radio" value="no"> No</label>
				</fieldset>
				<fieldset><legend>Would you like help coordinating a ride?</legend>
					<label><input name="ride_help" type="radio" value="yes"> Yes</label>
					<label><input name="ride_help" type="radio" value="no"> No</label>
				</fieldset>
				<p><label for="bvocal-rsvp-comments">Anything else we should know?</label><br><textarea id="bvocal-rsvp-comments" name="comments" rows="4"></textarea></p>
				<p><button type="submit">Send RSVP</button></p>
			</form>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	public function submit_rsvp() {
		$event_id   = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
		$redirect   = $event_id ? get_permalink( $event_id ) : home_url( '/' );
		$nonce      = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		$name       = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$email      = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$attendance = isset( $_POST['attendance'] ) ? sanitize_key( wp_unslash( $_POST['attendance'] ) ) : '';
		$ride_help  = isset( $_POST['ride_help'] ) ? sanitize_key( wp_unslash( $_POST['ride_help'] ) ) : '';
		$comments   = isset( $_POST['comments'] ) ? sanitize_textarea_field( wp_unslash( $_POST['comments'] ) ) : '';
		$group_id   = get_post_meta( $event_id, self::META_GROUP_ID, true );

		if ( ! wp_verify_nonce( $nonce, 'bvocal_rsvp_submit_' . $event_id ) || ! $event_id || ! $group_id || ! $name || ! is_email( $email ) || ! in_array( $attendance, array( 'yes', 'maybe', 'no' ), true ) || ! in_array( $ride_help, array( '', 'yes', 'no' ), true ) ) {
			wp_safe_redirect( add_query_arg( 'bvocal_rsvp', 'error', $redirect ) );
			exit;
		}

		$result = $this->mailerlite_request(
			'POST',
			'/subscribers',
			array(
				'email'  => $email,
				'fields' => array( 'name' => $name ),
				'groups' => array( $group_id ),
			)
		);
		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg( 'bvocal_rsvp', 'error', $redirect ) );
			exit;
		}

		$this->save_rsvp( $event_id, $email, $name, $attendance, $ride_help, $comments );
		wp_safe_redirect( add_query_arg( 'bvocal_rsvp', 'thanks', $redirect ) );
		exit;
	}

	private function save_rsvp( $event_id, $email, $name, $attendance, $ride_help, $comments ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_SUFFIX;
		$now   = current_time( 'mysql' );
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO $table (event_id, email, name, attendance, ride_help, comments, created_at, updated_at)
				VALUES (%d, %s, %s, %s, %s, %s, %s, %s)
				ON DUPLICATE KEY UPDATE name = VALUES(name), attendance = VALUES(attendance), ride_help = VALUES(ride_help), comments = VALUES(comments), updated_at = VALUES(updated_at)",
				$event_id, $email, $name, $attendance, $ride_help, $comments, $now, $now
			)
		);
	}

	private function rsvp_count( $event_id ) {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_SUFFIX;
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE event_id = %d", $event_id ) );
	}

	private function event_date( $event_id ) {
		$date = get_post_meta( $event_id, '_EventStartDate', true );
		return $date ? wp_date( 'Y-m-d', strtotime( $date ) ) : '';
	}

	private function is_future_event( $event_id ) {
		$start = get_post_meta( $event_id, '_EventStartDate', true );
		return $start && strtotime( $start ) > current_time( 'timestamp' );
	}

	private function is_published_future_event( $event_id ) {
		return 'tribe_events' === get_post_type( $event_id ) && 'publish' === get_post_status( $event_id ) && $this->is_future_event( $event_id );
	}

	private function has_mailerlite_token() {
		return (bool) get_option( 'mailerlite_api_key' );
	}

	private function mailerlite_request( $method, $path, $body = null ) {
		$token = get_option( 'mailerlite_api_key' );
		if ( ! $token ) {
			return new WP_Error( 'bvocal_mailerlite_missing_token', 'MailerLite is not connected.' );
		}
		$args = array(
			'method'  => $method,
			'timeout' => 20,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
				'X-Version'     => '2022-11-21',
			),
		);
		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}
		$response = wp_remote_request( 'https://connect.mailerlite.com/api' . $path, $args );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$status = wp_remote_retrieve_response_code( $response );
		$decoded = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $status < 200 || $status >= 300 ) {
			$message = ! empty( $decoded['message'] ) ? $decoded['message'] : 'MailerLite request failed (' . $status . ').';
			return new WP_Error( 'bvocal_mailerlite_request_failed', $message );
		}

		return is_array( $decoded ) ? $decoded : array();
	}

	private function assert_manage_request( $action ) {
		if ( ! $this->can_manage() ) {
			wp_die( 'You do not have permission to manage Gig RSVPs.' );
		}
		check_admin_referer( $action );
	}

	private function redirect_with_notice( $message, $type ) {
		wp_safe_redirect( add_query_arg( array( 'page' => 'bvocal-gig-rsvp', 'bvocal_rsvp_notice' => $message, 'bvocal_rsvp_type' => $type ), admin_url( 'admin.php' ) ) );
		exit;
	}

	private function render_admin_notice() {
		if ( empty( $_GET['bvocal_rsvp_notice'] ) ) {
			return;
		}
		$type    = isset( $_GET['bvocal_rsvp_type'] ) && 'error' === $_GET['bvocal_rsvp_type'] ? 'error' : 'success';
		$message = sanitize_text_field( wp_unslash( $_GET['bvocal_rsvp_notice'] ) );
		echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
	}
}

register_activation_hook( __FILE__, array( 'BVOCAL_Gig_RSVP', 'activate' ) );
new BVOCAL_Gig_RSVP();
