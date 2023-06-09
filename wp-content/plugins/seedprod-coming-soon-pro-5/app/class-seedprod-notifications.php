<?php
/**
 * Notifications.
 *
 * @since 7.10.5
 */
if ( ! class_exists( 'SeedProd_Notifications' ) ) {

	/**
	 * Notifications Class
	 *
	 */
	class SeedProd_Notifications {
		/**
		 * Get Instance
		 *
		 * @var null
		 */
		protected static $instance = null;

		/**
		 * Source of notifications content.
		 *
		 * @var string
		 */
		const SOURCE_URL = 'https://seedprod-notifications.s3.amazonaws.com/sp-notifications.json';

		/**
		 * Option value.
		 *
		 * @var boolean|array
		 */
		public $option = false;

		/**
		 * The name of the option used to store the data.
		 *
		 * @var string
		 */
		public $option_name = 'seedprod_notifications';

		/**
		 * Return an instance of this class.
		 */
		public static function get_instance() {

			// If the single instance hasn't been set, set it now.
			if ( null == self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * SeedProd_Notifications constructor.
		 *
		 */
		public function __construct() {
			$this->init();
		}

		/**
		 * Initialize class.
		 *
		 * @since {VERSION}
		 */
		public function init() {
			$this->hooks();
		}

		/**
		 * Register hooks.
		 *
		 * @since {VERSION}
		 */
		public function hooks() {
			add_action( 'wp_ajax_seedprod_pro_notification_dismiss', array( $this, 'dismiss' ) );
		}

		/**
		 * Check if user has access and is enabled.
		 *
		 * @return boolean
		 * @since {VERSION}
		 *
		 */
		public function has_access() {
			$access = true;

			if ( current_user_can( 'install_plugins' ) ) {
				$access = true;
			}

			return apply_filters( 'seedprod_admin_notifications_has_access', $access );
		}

		/**
		 * Get option value.
		 *
		 * @param boolean $cache Reference property cache if available.
		 *
		 * @return array
		 * @since {VERSION}
		 *
		 */
		public function get_option( $cache = true ) {
			if ( $this->option && $cache ) {
				return $this->option;
			}

			$option = get_option( $this->option_name, array() );

			$this->option = array(
				'update'    => ! empty( $option['update'] ) ? $option['update'] : 0,
				'events'    => ! empty( $option['events'] ) ? $option['events'] : array(),
				'feed'      => ! empty( $option['feed'] ) ? $option['feed'] : array(),
				'dismissed' => ! empty( $option['dismissed'] ) ? $option['dismissed'] : array(),
			);

			return $this->option;
		}

		/**
		 * Fetch notifications from feed.
		 *
		 * @return array
		 * @since {VERSION}
		 *
		 */
		public function fetch_feed() {
			$res = wp_remote_get( self::SOURCE_URL );

			if ( is_wp_error( $res ) ) {
				return array();
			}

			$body = wp_remote_retrieve_body( $res );

			if ( empty( $body ) ) {
				return array();
			}

			return $this->verify( json_decode( $body, true ) );
		}

		/**
		 * Verify notification data before it is saved.
		 *
		 * @param array $notifications Array of notifications items to verify.
		 *
		 * @return array
		 * @since {VERSION}
		 *
		 */
		public function verify( $notifications ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh,Squiz.Commenting.FunctionComment.TypeHintMissing

			$data = array();

			if ( ! is_array( $notifications ) || empty( $notifications ) ) {
				return $data;
			}

			$option = $this->get_option();

			foreach ( $notifications as $notification ) {

				// The message and license should never be empty, if they are, ignore.
				if ( empty( $notification['content'] ) || empty( $notification['type'] ) ) {
					continue;
				}

				// Ignore if license type does not match.
				$license_name = get_option( 'seedprod_license_name' );
				if ( empty( $license_name ) ) {
					$license_name = 'unlicensed';
				}
				if ( ! in_array( 'any', $notification['type'] ) ) {
					if ( ! in_array( $license_name, $notification['type'] ) ) {
						continue;
					}
				}

				// Ignore if expired.
				if ( ! empty( $notification['end'] ) && time() > strtotime( $notification['end'] ) ) {
					continue;
				}

				// Ignore if notification has already been dismissed.
				if ( ! empty( $option['dismissed'] ) && in_array( $notification['id'], $option['dismissed'] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
					continue;
				}

				// Ignore if notification existed before installing SeedProd.
				// Prevents bombarding the user with notifications after activation.
				$over_time = get_option( 'seedprod_over_time', array() );

				// phpcs:disable
				// if (! empty( $over_time['installed_date'] ) && ! empty( $notification['start'] ) && $over_time['installed_date'] > strtotime( $notification['start'] )) {
				// 	//continue;
				// }
				// phpcs:enable

				$data[] = $notification;
			}

			return $data;
		}

		/**
		 * Verify saved notification data for active notifications.
		 *
		 * @param array $notifications Array of notifications items to verify.
		 *
		 * @return array
		 * @since {VERSION}
		 *
		 */
		public function verify_active( $notifications ) { // phpcs:ignore Squiz.Commenting.FunctionComment.TypeHintMissing
			if ( ! is_array( $notifications ) || empty( $notifications ) ) {
				return array();
			}

			// Remove notifications that are not active.
			foreach ( $notifications as $key => $notification ) {
				if (
				( ! empty( $notification['start'] ) && time() < strtotime( $notification['start'] ) ) ||
				( ! empty( $notification['end'] ) && time() > strtotime( $notification['end'] ) )
				) {
					unset( $notifications[ $key ] );
				}
			}

			return $notifications;
		}

		/**
		 * Get notification data.
		 *
		 * @return array
		 * @since {VERSION}
		 *
		 */
		public function get() {
			if ( ! $this->has_access() ) {
				return array();
			}

			$option = $this->get_option();

			$events = ! empty( $option['events'] ) ? $this->verify_active( $option['events'] ) : array();
			$feed   = ! empty( $option['feed'] ) ? $this->verify_active( $option['feed'] ) : array();

			return array_merge( $events, $feed );
		}

		/**
		 * Get notification count.
		 *
		 * @return integer
		 * @since {VERSION}
		 *
		 */
		public function get_count() {
			$count = count( $this->get() );

			return $count;
		}

		/**
		 * Add a manual notification event.
		 *
		 * @param array $notification Notification data.
		 *
		 * @since {VERSION}
		 *
		 */
		public function add( $notification ) { // phpcs:ignore Squiz.Commenting.FunctionComment.TypeHintMissing
			if ( empty( $notification['id'] ) ) {
				return;
			}

			$option = $this->get_option();

			if ( in_array( $notification['id'], $option['dismissed'] ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				return;
			}

			foreach ( $option['events'] as $item ) {
				if ( $item['id'] === $notification['id'] ) {
					return;
				}
			}

			$notification = $this->verify( array( $notification ) );

			update_option(
				$this->option_name,
				array(
					'update'    => $option['update'],
					'feed'      => $option['feed'],
					'events'    => array_merge( $notification, $option['events'] ),
					'dismissed' => $option['dismissed'],
				)
			);
		}

		/**
		 * Update notification data from feed.
		 *
		 * @since {VERSION}
		 */
		public function update() {
			$feed   = $this->fetch_feed();
			$option = $this->get_option();

			update_option(
				$this->option_name,
				array(
					'update'    => time(),
					'feed'      => $feed,
					'events'    => $option['events'],
					'dismissed' => $option['dismissed'],
				)
			);
		}

		/**
		 * Dismiss notification via AJAX.
		 *
		 * @since {VERSION}
		 */
		public function dismiss() {

			// Run a security check.
			check_ajax_referer( 'seedprod_pro_notification_dismiss', '_wpnonce' );

			// Check for access and required param.
			if ( ! $this->has_access() || empty( $_POST['id'] ) ) {
				wp_send_json_error();
			}

			$id     = sanitize_text_field( wp_unslash( $_POST['id'] ) );
			$option = $this->get_option();
			$type   = is_numeric( $id ) ? 'feed' : 'events';

			$option['dismissed'][] = $id;
			$option['dismissed']   = array_unique( $option['dismissed'] );

			// Remove notification.
			if ( is_array( $option[ $type ] ) && ! empty( $option[ $type ] ) ) {
				foreach ( $option[ $type ] as $key => $notification ) {
					if ( $notification['id'] == $id ) { // phpcs:ignore WordPress.PHP.StrictComparisons
						unset( $option[ $type ][ $key ] );
						break;
					}
				}
			}

			update_option( $this->option_name, $option );

			wp_send_json_success();
		}

		/**
		 * This generates the markup for the notifications indicator if needed.
		 *
		 * @return string
		 */
		public function get_menu_count() {
			if ( $this->get_count() > 0 ) {
				return '<span class="seedprod-menu-notification-indicator"></span>';
			}

			return '';
		}

		/**
		 * Retrieve the notifications via an ajax call.
		 */
		public function ajax_get_notifications() {

			// Run a security check.
			check_ajax_referer( 'sp-admin-nonce', 'nonce' );

			$notifications_data = array(
				'notifications' => $this->get(),
				'view_url'      => $this->get_view_url(),
			);

			wp_send_json_success( $notifications_data );
		}

		/**
		 * Get the URL for the page where users can see/read notifications.
		 *
		 * @return string
		 */
		public function get_view_url() {
			$disabled = get_option( 'dashboards_disabled', false );

			$url = add_query_arg( 'page', 'seedprod_reports', admin_url( 'admin.php' ) );

			if ( false !== $disabled ) {
				$url = is_multisite() ? network_admin_url( 'admin.php?page=seedprod_network' ) : admin_url( 'admin.php?page=seedprod_settings' );
			}

			return $url;
		}
	}
}

if ( ! function_exists( 'seedprod_pro_do_notifications' ) ) {

	add_action( 'seedprod_notifications', 'seedprod_pro_do_notifications' );
	/**
	 * Do SeedProd Notifications
	 *
	 * @return void
	 */
	function seedprod_pro_do_notifications() {
		$notifications = new SeedProd_Notifications();
		$notifications->update();

	}
}
