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

			add_action( 'wp_ajax_bbzec_create_event', [ $this, 'create_event' ] );
			add_action( 'wp_ajax_nopriv_bbzec_create_event', [ $this, 'no_priv' ] );

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

			if ( ! ( bp_is_groups_component() && bp_is_current_action( 'calendar-group-event' ) ) ) {
				$response['error_message'] = 'Invalid access.';
				exit( json_encode( $response ) );
			}

			if ( ! wp_verify_nonce( $_POST['nonce'], 'ajax-nonce') ) {
				$response['error_message'] = 'Cannot verify nonce.';
				exit( json_encode( $response ) );
			}

            $group_meta_event                 = 'group_meta_event_id';
			$bp_group_zoom_event_category     = isset($_POST['zoom_group_cat']) ? sanitize_text_field($_POST['zoom_group_cat']) : null;
			$bp_group_zoom_event_category     = trim( $bp_group_zoom_event_category );
			$bp_group_zoom_event_category_slug = sanitize_title( $bp_group_zoom_event_category );

            if ( empty( trim( $bp_group_zoom_event_category ) ) && empty( groups_get_groupmeta( $group_id, $group_meta_event, true) ) ) {
                exit(json_encode($response));
            } else {

                $group_event_category = absint( groups_get_groupmeta( $group_id, $group_meta_event ) );

				$term_other = term_exists( $bp_group_zoom_event_category, 'tribe_events_cat' );
				if (
					$term_other['term_id'] != 0 &&
					$term_other['term_id'] != null &&
					$term_other['term_id'] != $group_event_category
				) {
					$bp_group_zoom_event_category_slug = $bp_group_zoom_event_category_slug . '-1';
				}

                if ( $group_event_category ) {

					$term = term_exists( $group_event_category, 'tribe_events_cat' );
                    if ( $term ) {

						$term_now = get_term( $group_event_category, 'tribe_events_cat' );
						if ( $term_now ) {
							if ( strtolower($term_now->name) == strtolower($bp_group_zoom_event_category) ) {
								$response['error']           = true;
								$response['success_message'] = 'No changes needed.';
								exit(json_encode($response));
							}
						}

                        wp_update_term(
                            $term['term_id'],
                            'tribe_events_cat',
                            [
                                'name' => $bp_group_zoom_event_category,
                                'slug' => $bp_group_zoom_event_category_slug
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

			if ( ! ( bp_is_groups_component() && bp_is_current_action( 'calendar-group-event' ) ) ) {
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
		 * Handles the main save action.
		 *
		 * @return void
		 */
		public function create_event() {

			$response = [ 'error' => true ];
			$group_id = bp_get_current_group_id();

			if ( ! $group_id ) {
				$response['error_message'] = 'Group is missing.';
				exit( json_encode( $response ) );
			}

			if ( ! ( bp_is_groups_component() && bp_is_current_action( 'calendar-group-event' ) ) ) {
				$response['error_message'] = 'Invalid access.';
				exit( json_encode( $response ) );
			}

			if ( ! isset( $_POST['bp-group-calendar-title'] ) ) {
				$response['error_message'] = 'Missing required fields.';
				exit( json_encode( $response ) );
			}

			if (
				! isset( $_POST['bbzec_create_event_field'] ) ||
				! wp_verify_nonce( $_POST['bbzec_create_event_field'], 'bbzec_create_event' )
			) {
				$response['error_message'] = 'Invalid nonce.';
				exit( json_encode( $response ) );
			}

			$post_args            = $_POST;
			$event_title          = sanitize_text_field( $post_args['bp-group-calendar-title'] );
			$start_date           = $post_args['bp-group-calendar-start-date'];
			$start_hr_mm_ss       = explode( ':', $post_args['bp-group-calendar-start-time'] );
			$start_hour           = absint( $start_hr_mm_ss[0] );
			$start_minute         = absint( $start_hr_mm_ss[1] );
			$duration_hr          = absint( $post_args['bp-group-calendar-duration-hr'] );
			$duration_min         = absint( $post_args['bp-group-calendar-duration-min'] );
			$end_hour             = $start_hour + $duration_hr;
			$end_minute           = $start_minute + $duration_min;

			if( 60 === $end_minute ) {
				$end_hour  = $end_hour  + 1;
				$end_minute = 0;
			}

			$group_name           = groups_get_group( $group_id )->name;
			$term_ids             = [];
            $group_event_category = absint( groups_get_groupmeta( $group_id, 'group_meta_event_id' ) );
			$group_term           = get_term( $group_event_category );
			if ( $group_event_category && ! is_wp_error( $group_term ) ) {
				$term_ids[] = $group_term->term_id;
            }

			$eventendmeridian = $post_args['bp-group-calendar-medridian'];
			if (
				$eventendmeridian == 'am' && $end_hour > 12
			) {
				$end_hour         = $end_hour - 12;
				$eventendmeridian = 'pm';
			}

			$args = [
				'post_title'         => $event_title,
				'post_content'       => sanitize_text_field( $post_args['bp-group-calendar-description'] ),
				'post_status'        => 'publish',
				'EventStartDate'     => $start_date,
				'EventEndDate'       => $start_date,
				'EventStartHour'     => $start_hour,
				'EventStartMinute'   => $start_minute,
				'EventStartMeridian' => $post_args['bp-group-calendar-medridian'],
				'EventEndHour'       => $end_hour,
				'EventEndMinute'     => $end_minute,
				'EventTimezone'      => $post_args['bp-group-calendar-timezone'],
				'EventEndMeridian'   => $eventendmeridian,
				'Organizer'          => $group_name,
				'tax_input'          => [ 'tribe_events_cat' => $term_ids ]
			];

			$event_id = tribe_create_event( $args );

			if ( $event_id ) {
				$response['error']           = false;
				$response['success_message'] = 'Event has been created.';
			} else {
				$response['error_message'] = 'Event was not created.';
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
