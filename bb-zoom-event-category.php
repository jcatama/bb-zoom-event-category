<?php
/**
 * Plugin Name: BB Zoom Event Category
 * Description: Sync copy buddyboss zoom meeting to events category.
 * Version: 1.0.0
 * Author: John Albert Catama
 * Author URI: https://github.com/jcatama
 * Text Domain: bb-zoom-event-category
 *
 * @package BBZoomEventCategory
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'BBZEC_PLUGIN_FILE' ) ) {
	define( 'BBZEC_PLUGIN_FILE', __FILE__ );
}

if ( ! class_exists( 'BBZoomEventCategory' ) ) :

	/**
	 * BB Zoom Event Category Class.
	 *
	 * @class BBZoomEventCategory
	 */
	class BBZoomEventCategory {

		/**
		 * The single instance of the class.
		 *
		 * @var BBZoomEventCategory
		 */
		private static $instance;

		/**
		 * BBZoomEventCategory Constructor.
		 */
		public function __construct() {

			add_action( 'bp_zoom_meeting_after_save', [ $this, 'mutate_sync_event_data' ], 11 );
			add_action( 'bp_zoom_meeting_deleted_meetings', [ $this, 'delete_sync_event_data' ], 11 );

		}

		/**
		 * Add/Set Event Category on Zoom meeting mutate.
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

			$zoom_url      = bp_zoom_meeting_get_meta( $meeting->id, 'zoom_join_url', true );
			$event_content = sprintf(
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
				$meeting->description,
				$zoom_url,
				$meeting->meeting_id,
				$meeting->password
			);

			$start_date_time  = explode( 'T', $meeting->start_date );
			$start_date       = $start_date_time[0];
			$start_hr_mm_ss   = explode( ':', $start_date_time[1] );
			$start_hour       = absint($start_hr_mm_ss[0]);
			$start_minute     = absint($start_hr_mm_ss[1]);
			$duration         = absint($meeting->duration);
			$duration_hr      = floor( $duration / 60 );
			$duration_min     = $duration % 60;
			$end_hour         = $start_hour + $duration_hr;
			$end_minute       = $start_minute + $duration_min;
			$group_name       = groups_get_group( $meeting->group_id )->name;
			$term_exist       = term_exists( $group_name, 'tribe_events_cat' );
			$term_id          = 0;
			
			if( null === $term_exist ) {

				$term = wp_insert_term( $group_name, 'tribe_events_cat' );

				if ( ! is_wp_error( $term ) ) {
					$term_id = $term['term_id'];
				}

			} else {

				$term_id = $term_exist['term_id'];

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
				'EventEndMeridian'   => $post_args['bp-zoom-meeting-start-time-meridian'],
				'EventTimezone'      => $meeting->timezone,
				'EventURL'           => $zoom_url,
				'Organizer'          => $group_name,
				'tax_input'          => [ 'tribe_events_cat' => $term_id ]
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
						'posts_per_page' => 1
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
		 * Main BBZoomEventCategory Instance.
		 *
		 * Ensures only one instance of BBZoomEventCategory is loaded or can be loaded.
		 *
		 * @since 1.0
		 * @static
		 * @return BBZoomEventCategory - Main instance.
		 */
		public static function get_instance() {
			if (null === self::$instance) {
				self::$instance = new self();
			}
	
			return self::$instance;
		}
	}

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
				__(
					'Sorry, but this plugin requires the BuddyBoss Platform Pro & The Events Calendar to be installed and active.<br><a href="%1$s">&laquo; Return to Plugins</a>', 'bb-zoom-event-category'
				),
				admin_url( 'plugins.php' ) 
			)
		);
		deactivate_plugins( plugin_basename( __FILE__ ) );

	endif;
} );

/**
 * Initiates the class.
 */
add_action( 'plugins_loaded', function () {
	BBZoomEventCategory::get_instance();
} );