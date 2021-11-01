<?php
/**
 * Calendar Groups Functions.
 *
 * @package    BBZoomEventCategory
 * @subpackage includes
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

if ( ! class_exists( 'BB_Calendar_Group_Functions' ) ) :

	/**
	 * BB Zoom Event Category Setting Class.
	 *
	 * @class BB_Calendar_Group_Functions
	 */
	class BB_Calendar_Group_Functions {

		/**
		 * The single instance of the class.
		 *
		 * @var BB_Calendar_Group_Functions
		 */
		private static $instance;

		/**
		 * BB_Calendar_Group_Functions Constructor.
		 */
		public function __construct() {

			add_action( 'wp_ajax_bbzec_save_calendar_group_cat', [ $this, 'save_group_calendar' ] );
			add_action( 'wp_ajax_nopriv_bbzec_save_calendar_group_cat', [ $this, 'no_priv' ] );

			add_action( 'wp_ajax_bbzec_delete_calendar_group_cat', [ $this, 'delete_group_event' ] );
			add_action( 'wp_ajax_nopriv_bbzec_delete_calendar_group_cat', [ $this, 'no_priv' ] );

		}

		/**
		 * Main BB_Calendar_Group_Functions Instance.
		 *
		 * Ensures only one instance of BB_Calendar_Group_Functions is loaded or can be loaded.
		 *
		 * @since 1.0
		 * @static
		 * @return BB_Calendar_Group_Functions - Main instance.
		 */
		public static function get_instance() {
			if (null === self::$instance) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Create/Update custom event category for BB_GROUP
		 *
		 * @return void
		 */
		public function save_group_calendar() {

			$response = [ 'error' => true ];
            $group_id = bp_get_current_group_id();

			if ( ! $group_id ) {
				$response['error_message'] = 'Group is missing.';
				exit( json_encode( $response ) );
			}

			if ( 'calendar-group-event' !== bp_get_group_current_admin_tab() ) {
				$response['error_message'] = 'Invalid access.';
				exit( json_encode( $response ) );
			}

			if ( ! wp_verify_nonce( $_POST['nonce'], 'ajax-nonce' ) ) {
				$response['error_message'] = 'Cannot verify nonce.';
				exit( json_encode( $response ) );
			}

            $group_meta_event             = 'group_meta_event_id';
			$bp_group_zoom_event_category = isset($_POST['zoom_group_cat']) ? sanitize_text_field($_POST['zoom_group_cat']) : null;

            if ( empty( trim( $bp_group_zoom_event_category ) ) && empty( groups_get_groupmeta( $group_id, $group_meta_event, true) ) ) {
                exit(json_encode($response));
            } else {

                $group_event_category = absint( groups_get_groupmeta( $group_id, $group_meta_event ) );

                if ( $group_event_category ) {

                    $term = term_exists( $group_event_category, 'tribe_events_cat' );
                    if ( $term ) {
                        wp_update_term(
                            $term['term_id'],
                            'tribe_events_cat',
                            [
                                'name' => $bp_group_zoom_event_category,
                                'slug' => sanitize_title( $bp_group_zoom_event_category )
                            ]
                        );
						$response['error']           = false;
						$response['success_message'] = 'Zoom Group Category Saved';
                    }

                } else {

                    $term = wp_insert_term( $bp_group_zoom_event_category, 'tribe_events_cat' );
                    if ( ! is_wp_error( $term ) ) {
                        groups_add_groupmeta( $group_id, $group_meta_event, $term['term_id'] );
						$response['error']           = false;
						$response['success_message'] = 'Zoom Group Category Saved';
                    }

                }

            }

			exit( json_encode( $response ) );

		}

		/**
		 * Delete event.
		 *
		 * @return void
		 */
		public function delete_group_event() {

			$response = [ 'error' => true ];
            $group_id = bp_get_current_group_id();

			if ( ! $group_id ) {
				$response['error_message'] = 'Group is missing.';
				exit( json_encode( $response ) );
			}

			if ( 'calendar-group-event' !== bp_get_group_current_admin_tab() ) {
				$response['error_message'] = 'Invalid access.';
				exit( json_encode( $response ) );
			}

			if ( ! wp_verify_nonce( $_POST['nonce'], 'ajax-nonce' ) ) {
				$response['error_message'] = 'Cannot verify nonce.';
				exit( json_encode( $response ) );
			}

			if ( ! isset( $_POST['event_id'] ) ) {
				$response['error_message'] = 'Missing event id.';
				exit( json_encode( $response ) );
			}

			$event_id = absint( $_POST['event_id'] );
			if ( $event_id ) {
				$delete_success = wp_delete_post( $event_id, true );

				if ( $delete_success ) {
					$response['error']           = false;
					$response['success_message'] = 'Event has been deleted.';
				}

			}

			exit( json_encode( $response ) );
		}

		/**
		 * No priv for BB_GROUP
		 *
		 * @return void
		 */
		public function no_priv() {
			wp_die( 'Permissin denied.' );
			die();
		}

	}

endif;
