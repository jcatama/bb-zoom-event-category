<?php
/**
 * Calendar Admin Menu for Groups.
 *
 * @package	BBZoomEventCategory
 * @subpackage includes
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

if (!class_exists('BB_Calendar_Group_Setting')) :

	/**
	 * BB Calendar Category Setting Class.
	 *
	 * @class BB_Calendar_Group_Setting
	 */
	class BB_Calendar_Group_Setting extends BP_Group_Extension {

		/**
		 * Zoom event category.
		 *
		 * @var string
		 */
		private $zoom_event_category = '';

		/**
		 * Post tribe_events
		 *
		 * @var WP_Post
		 */
		private $calendar_events;

		/**
		 * Here you can see more customization of the config options.
		 */
		function __construct() {
			$args = [
				'slug'                => 'calendar-group-event',
				'name'                => 'Calendar',
				'nav_item_position'   => 100,
				'enable_nav_item'     => false,
				'screens'             => [
					'edit'            => [
						'name'        => 'Calendar',
						'submit_text' => 'Create',
					]
				]
			];
			parent::init( $args );
		}

		/**
		 * Handles the default screen settings.
		 *
		 * @return void
		 */
		function settings_screen( $group_id = null ) {
			$group_event_category = absint( groups_get_groupmeta( bp_get_current_group_id(), 'group_meta_event_id' ) );
			$term                 = get_term( $group_event_category );
			if ( $group_event_category && ! is_wp_error( $term ) ) {
				$this->zoom_event_category = $term->name;
			}

			include BBZEC_PLUGIN_DIR . 'includes/templates/template-calendar.php';
		}

		/**
		 * Handles the main save action.
		 *
		 * @return void
		 */
		function settings_screen_save( $group_id = null ) {

			if ( ! $group_id ) {
				return;
			}

			if ( 'calendar-group-event' !== bp_get_group_current_admin_tab() ) {
				return;
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
				'Organizer'          => $group_name,
				'tax_input'          => [ 'tribe_events_cat' => $term_ids ]
			];

			$event_id = tribe_create_event( $args );

			if ( $event_id ) {
				bp_core_add_message(
					sprintf(
						/* translators: %s: event name */
						__( 'Group Calendar: %1$s was successfully created.', 'bb-zoom-event-category' ),
						$event_title
					),
					'success'
				);
			} else {
				bp_core_add_message(
					sprintf(
						/* translators: %s: event name */
						__( 'Group Calendar: Unable to create %1$s.', 'bb-zoom-event-category' ),
						$event_title
					),
					'error'
				);
			}

		}

	}

endif;

bp_register_group_extension( 'BB_Calendar_Group_Setting' );
