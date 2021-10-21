<?php

// Exit if accessed directly.
defined('ABSPATH') || exit;

if ( ! class_exists( 'BBZoomEventCategorySetting' ) ) :

    /**
	 * BB Zoom Event Category Setting Class.
	 *
	 * @class BBZoomEventCategorySetting
	 */
	class BBZoomEventCategorySetting {

        /**
		 * The single instance of the class.
		 *
		 * @var BBZoomEventCategorySetting
		 */
		private static $instance;

        /**
		 * BBZoomEventCategory Constructor.
		 */
		public function __construct() {

            add_action( 'bp_screens', [ $this, 'zoom_event_admin_page' ] );

		}

        /**
         * Function:Renders additional content in the zoom setting page
         * 
         * @param None
         */
        public function zoom_event_admin_page() {
            if ( 'zoom' !== bp_get_group_current_admin_tab() ) {
                return false;
            }
    
            if ( ! bp_is_item_admin() && ! bp_current_user_can( 'bp_moderate' ) ) {
                return false;
            }

            add_action( 'groups_custom_edit_steps', array( $this, 'zoom_event_screen' ), 1 );
            bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'groups/single/home' ) );
        }
    
        /**
         * Template: Renders additional content in the zoom setting page
         * 
         * @param None
         */
        public function zoom_event_screen() {
            $zoom_event_category  = '';
            $group_event_category = absint( groups_get_groupmeta( bp_get_current_group_id(), 'group_meta_event_id' ) );
			$term                 = get_term( $group_event_category );
			if ( $group_event_category && ! is_wp_error( $term ) ) {
                $zoom_event_category = $term->name;
            }
            ?>
			<h4 class="bb-section-title"><?php esc_html_e( 'Calendar Name', 'bb-zoom-event-category' ); ?></h4>
            <div class="bb-field-wrap">
                <div class="bp-input-wrap">
                    <input type="text" name="bp-group-zoom-event-category" id="bp-group-zoom-event-category" class="zoom-group-instructions-main-input" value="<?php echo esc_attr( $zoom_event_category ); ?>"/>
                </div>
                <button type="button" class="btn" id="save_zoom_calendar_group"><?php esc_html_e( 'Save', 'bb-zoom-event-category' ); ?></button>
            </div>
            <hr class="bb-sep-line" />
            <?php
        }

        /**
		 * Main BBZoomEventCategorySetting Instance.
		 *
		 * Ensures only one instance of BBZoomEventCategorySetting is loaded or can be loaded.
		 *
		 * @since 1.0
		 * @static
		 * @return BBZoomEventCategorySetting - Main instance.
		 */
		public static function get_instance() {
			if (null === self::$instance) {
				self::$instance = new self();
			}
	
			return self::$instance;
		}

    }

endif;