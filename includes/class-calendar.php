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
		 * Here you can see more customization of the config options.
		 */
		function __construct() {

			$args = [
				'slug'                => 'calendar-group-event',
				'name'                => 'Calendar',
				'nav_item_position'   => 100,
				'enable_nav_item'     => true,
				'access'              => [ 'admin', 'mod' ],
				'show_tab'            => [ 'admin', 'mod' ],
			];

			parent::init( $args );

		}

		/**
		 * Handles the default screen settings.
		 *
		 * @return void
		 */
		public function display( $group_id = NULL ) {

			$group_event_category = absint( groups_get_groupmeta( bp_get_current_group_id(), 'group_meta_event_id' ) );
			$term                 = get_term( $group_event_category );
			if ( $group_event_category && ! is_wp_error( $term ) ) {
				$this->zoom_event_category = $term->name;
			}

			include BBZEC_PLUGIN_DIR . 'includes/templates/template-calendar.php';

		}

	}

endif;

bp_register_group_extension( 'BB_Calendar_Group_Setting' );
