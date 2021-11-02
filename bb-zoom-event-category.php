<?php
/**
 * Plugin Name: BB Zoom Event Category
 * Description: Sync copy buddyboss zoom meeting to events category.
 * Version: 1.2.6
 * Author: John Albert Catama
 * Author URI: https://github.com/jcatama
 * Text Domain: bb-zoom-event-category
 * Domain Path: /languages/
 *
 * @package BB_Zoom_Event_Category
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'BBZEC_PLUGIN_FILE' ) ) {
	define( 'BBZEC_PLUGIN_FILE', __FILE__ );
}

if ( ! defined('BBZEC_PLUGIN_DIR' ) ) {
	define( 'BBZEC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined('BBZEC_VERSION' ) ) {
	define( 'BBZEC_VERSION', 'v1.2.6' );
}

if ( ! class_exists( 'BB_Zoom_Event_Category' ) ) :

	/**
	 * BB Zoom Event Category Class.
	 *
	 * @class BB_Zoom_Event_Category
	 */
	final class BB_Zoom_Event_Category {

		/**
		 * The single instance of the class.
		 *
		 * @var BB_Zoom_Event_Category
		 */
		private static $instance;

		/**
		 * BB_Zoom_Event_Category Constructor.
		 */
		public function __construct() {

			// Load local translations.
			load_plugin_textdomain( 'bb-zoom-event-category', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

			add_action( 'wp_enqueue_scripts', [ $this, 'scripts_styles' ], 9999 );
			add_filter( 'bp_nouveau_get_group_meta',  [ $this, 'render_custom_group_meta' ], 10, 3 );
			add_action( 'bp_zoom_meeting_after_save', [ $this, 'mutate_sync_event_data' ], 11 );
			add_action( 'bp_zoom_meeting_deleted_meetings', [ $this, 'delete_sync_event_data' ], 11 );

		}

		/**
		 * Include action scripts
		 */
		public function scripts_styles() {

			wp_enqueue_style( 'bbzec-css', plugins_url( '/assets/css/index.css', __FILE__ ), array(), BBZEC_VERSION );
			wp_enqueue_script( 'bbzec-init-js', plugins_url( '/assets/js/index.js', __FILE__ ), array( 'jquery' ), BBZEC_VERSION );

			wp_localize_script( 'jquery', 'bbzec',
				[
					'ajaxurl'  => admin_url( 'admin-ajax.php' ),
					'home_url' => get_home_url(),
					'nonce'    => wp_create_nonce( 'ajax-nonce' )
				]
			);

			if ( 'calendar-group-event' == bp_get_group_current_admin_tab() ) {
				wp_enqueue_style( 'jquery-datetimepicker' );
				wp_enqueue_script( 'jquery-datetimepicker' );
				wp_enqueue_script( 'bbzec-zoom-js', plugins_url( '/assets/js/calendar.js', __FILE__ ), array( 'jquery' ), BBZEC_VERSION, true );
			}

		}

		/**
		 * Use to render event category meta.
		 *
		 * @param Array $meta array of status and description
		 * @param BB_GROUP $group buddyboss group object
		 * @paraa Boolean $is_group weather the object is a group or not
		 */
		public function render_custom_group_meta( $meta, $group, $is_group ) {

			$group_event_category = absint( groups_get_groupmeta( $group->id, 'group_meta_event_id' ) );
			$term                 = get_term( $group_event_category );
			if ( $group_event_category && ! is_wp_error( $term ) ) {
				$meta['status'] = $meta['status'] .
					' <span class="type-separator">/</span><span class="group-type zoom-group-calendar '.$term->slug.'">
						' . __( 'Calendar', 'bb-zoom-event-category' ) . '
					</span>';
			}

			return $meta;

		}

		/**
		 * Add/Set Event Category on Calendar meeting mutate.
		 *
		 * @param BP_Zoom_Meeting $meeting Current instance of meeting item being saved. Passed by reference.
		 */
		public function mutate_sync_event_data( $meeting ) {

			if ( ! isset( $_POST['action'] ) || ( 'zoom_meeting_add' !== $_POST['action'] && 'zoom_meeting_occurrence_edit' !== $_POST['action'] ) ) {

				return;

			}

			$post_args = $_POST;

			if( isset( $post_args['bp-zoom-meeting-recurring'] ) && 'meeting' === $meeting->zoom_type ) {

				return;

			} else if ( ! isset( $post_args['bp-zoom-meeting-recurring'] ) && 'meeting' === $meeting->zoom_type ) {

				remove_action( 'bp_zoom_meeting_after_save', [ $this, 'mutate_sync_event_data' ], 11 );

			}

			if ( isset( $post_args['bp-zoom-meeting-id'] ) ) {

				$this->update_event( $meeting, $post_args );

			} else {

				$this->create_event( $meeting, $post_args );

			}

		}

		/**
		 * Create new event
		 *
		 * @param BP_Zoom_Meeting $meeting Current instance of meeting item being saved. Passed by reference.
		 * @param $_POST $post_args Current post payload
		 */
		private function create_event( $meeting, $post_args ) {

			if ( $meeting->parent ) {
				$parent_meeting = BP_Zoom_Meeting::get_meeting_by_meeting_id( $meeting->parent );

				if ( $parent_meeting ) {

					$args = array(
						'post_type'		 =>	'tribe_events',
						'meta_query'	 =>	[
							[
								'key'    => 'bp_zoom_meeting_id',
								'value'	 =>	$parent_meeting->id
							]
						],
						'posts_per_page' => 1,
						'update_post_term_cache' => false
					);

					$event = new WP_Query( $args );

					if( $event->have_posts() ) {
						while( $event->have_posts() ) {
						  $event->the_post();
						  wp_delete_post( get_the_ID(), true );
						}
					}

					wp_reset_postdata();

				}
			}

			$is_event_created = tribe_create_event( $this->populate_zoom_args( $meeting, $post_args ) );

			if ( $is_event_created ) {

				bp_zoom_meeting_add_meta( $meeting->id, 'event_id', $is_event_created );
				add_post_meta( $is_event_created, 'bp_zoom_meeting_id', $meeting->id );

			}

		}

		/**
		 * Update event
		 *
		 * @param BP_Zoom_Meeting $meeting Current instance of meeting item being saved. Passed by reference.
		 * @param $_POST $post_args Current post payload
		 */
		private function update_event( $meeting, $post_args ) {

			$event_id = bp_zoom_meeting_get_meta( $meeting->id, 'event_id' );

			if ( $event_id ) {

				tribe_update_event( $event_id, $this->populate_zoom_args( $meeting, $post_args ) );

			} else {

				$this->create_event( $meeting, $post_args );

			}

		}

		/**
		 * Populate post_args to array
		 *
		 * @param $_POST $post_args Current post payload
		 */
		private function populate_zoom_args( $meeting, $post_args ) {

			$zoom_url         = '';
			$zoom_description = '';

			if ( $meeting->parent ) {
				$parent_meeting = BP_Zoom_Meeting::get_meeting_by_meeting_id( $meeting->parent );

				if ( $parent_meeting ) {

					$zoom_url         = bp_zoom_meeting_get_meta( $parent_meeting->id, 'zoom_join_url', true );
					$zoom_description = $parent_meeting->description;

				} else {

					$zoom_url         = bp_zoom_meeting_get_meta( $meeting->id, 'zoom_join_url', true );
					$zoom_description = $meeting->description;

				}

			} else {

				$zoom_url         = bp_zoom_meeting_get_meta( $meeting->id, 'zoom_join_url', true );
				$zoom_description = $meeting->description;

			}

			$event_content    = sprintf(
				/* translators: %s: duration in minutes, %s: description, %s: zoom link, %s: zoom id, %s: zoom password */
				__(
					'
					Duration: %1$s minutes<br>
					%2$s<br>
					<p></p>
					Join Zoom Meeting: %3$s<br>
					Meeting ID: %4$s<br>
					Passcode: %5$s<br>
					',
					'bb-zoom-event-category'
				),
				$meeting->duration,
				$zoom_description,
				$zoom_url,
				$meeting->meeting_id,
				$meeting->password
			);

			$start_date_time  = explode( 'T', $meeting->start_date );

			if ( 'meeting_occurrence' === $meeting->zoom_type ) {
				$datetime            = new DateTime( $meeting->start_date );
				$t_time              = new DateTimeZone( $meeting->timezone );
				$datetime->setTimezone( $t_time );
				$start_date_time_tmp = $datetime->format( 'Y-m-d H:i:s' );
				$start_date_time     = explode( ' ', $start_date_time_tmp );
			}

			$start_date       = $start_date_time[0];
			$start_hr_mm_ss   = explode( ':', $start_date_time[1] );
			$start_hour       = absint($start_hr_mm_ss[0]);
			$start_minute     = absint($start_hr_mm_ss[1]);
			$duration         = absint($meeting->duration);
			$duration_hr      = floor( $duration / 60 );
			$duration_min     = $duration % 60;
			$end_hour         = $start_hour + $duration_hr;
			$end_minute       = $start_minute + $duration_min;

			if( 60 === $end_minute ) {
				$end_hour  = $end_hour  + 1;
				$end_minute = 0;
			}

			$group_name       = groups_get_group( $meeting->group_id )->name;
			$term_exist       = term_exists( $group_name, 'tribe_events_cat' );
			$term_ids         = [];

			if( null === $term_exist ) {

				$term = wp_insert_term( $group_name, 'tribe_events_cat' );

				if ( ! is_wp_error( $term ) ) {
					$term_ids[] = $term['term_id'];
				}

			} else {

				$term_ids[] = $term_exist['term_id'];

			}

            $group_event_category = absint( groups_get_groupmeta( $meeting->group_id, 'group_meta_event_id' ) );
			$group_term           = get_term( $group_event_category );
			if ( $group_event_category && ! is_wp_error( $group_term ) ) {
				$term_ids[] = $group_term->term_id;
            }

			$eventendmeridian = $post_args['bp-zoom-meeting-start-time-meridian'];
			if (
				$eventendmeridian == 'am' && $end_hour > 12
			) {
				$end_hour         = $end_hour - 12;
				$eventendmeridian = 'pm';
			}

			return [
				'post_title'         => $meeting->title,
				'post_content'       => $event_content,
				'post_status'        => 'publish',
				'EventStartDate'     => $start_date,
				'EventEndDate'       => $start_date,
				'EventStartHour'     => $start_hour,
				'EventStartMinute'   => $start_minute,
				'EventStartMeridian' => $post_args['bp-zoom-meeting-start-time-meridian'],
				'EventEndHour'       => $end_hour,
				'EventEndMinute'     => $end_minute,
				'EventTimezone'      => $meeting->timezone,
				'EventEndMeridian'   => $eventendmeridian,
				'EventURL'           => $zoom_url,
				'Organizer'          => $group_name,
				'tax_input'          => [ 'tribe_events_cat' => $term_ids ]
			];

		}

		/**
		 * Delete create event data.
		 *
		 * @param array $meeting_ids Meeting ids deleted.
		 */
		public function delete_sync_event_data( $meeting_ids ) {

			if ( ! empty( $meeting_ids ) ) {

				foreach ( $meeting_ids as $meeting_id ) {

					$args = array(
						'post_type'		 =>	'tribe_events',
						'meta_query'	 =>	[
							[
								'key'    => 'bp_zoom_meeting_id',
								'value'	 =>	$meeting_id
							]
						],
						'posts_per_page' => 1,
						'update_post_term_cache' => false
					);

					$event = new WP_Query( $args );

					if( $event->have_posts() ) {
						while( $event->have_posts() ) {
						  $event->the_post();
						  wp_delete_post( get_the_ID(), true );
						}
					}

					wp_reset_postdata();
				}

			}

		}

		/**
		 * Main BB_Zoom_Event_Category Instance.
		 *
		 * Ensures only one instance of BB_Zoom_Event_Category is loaded or can be loaded.
		 *
		 * @since 1.0
		 * @static
		 * @return BB_Zoom_Event_Category - Main instance.
		 */
		public static function get_instance() {
			if (null === self::$instance) {
				self::$instance = new self();
			}

			return self::$instance;
		}
	}

	/**
	 * Include caledar group admin sub menu.
	 */
	add_action( 'bp_init', function () {
		include_once BBZEC_PLUGIN_DIR . 'includes/class-calendar.php';
	} );

	include_once BBZEC_PLUGIN_DIR . 'includes/class-calendar-functions.php';

	/**
	 * Initiates plugin class.
	 */
	add_action( 'plugins_loaded', function () {
		BB_Zoom_Event_Category::get_instance();
		BB_Calendar_Group_Functions::get_instance();
	} );

endif;

/**
 * Check for dependencies.
 *
 * BuddyBoss Platform Pro
 * The Events Calendar
 *
 */
register_activation_hook(__FILE__, function() {
	if(
		( ! is_plugin_active( 'buddyboss-platform-pro/buddyboss-platform-pro.php' ) || ! is_plugin_active( 'the-events-calendar/the-events-calendar.php' ) ) &&
		is_admin()
	):

		wp_die(
			sprintf(
				/* translators: %s: admin link */
				__(
					'Sorry, but this plugin requires the BuddyBoss Platform Pro & The Events Calendar to be installed and active.<br><a href="%1$s">&laquo; Return to Plugins</a>', 'bb-zoom-event-category'
				),
				admin_url( 'plugins.php' )
			)
		);
		deactivate_plugins( plugin_basename( __FILE__ ) );

	endif;
} );
