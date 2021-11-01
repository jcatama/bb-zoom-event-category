<?php
/**
 * Calendar Groups Template.
 *
 * @package    BBZoomEventCategory
 * @subpackage includes/templates
 */

?>

<div class="bp-meeting-fields-wrap bp-calendar-group-fields-wrap">

	<h4 class="bb-section-title"><?php esc_html_e('Calendar Name', 'bb-zoom-event-category'); ?></h4>
	<div class="bb-field-wrapper">
		<div class="">
			<div class="bb-field-wrap">
				<div class="bp-input-wrap">
					<input type="text" name="bp-group-calendar-event-category" id="bp-group-calendar-event-category" class="zoom-group-instructions-main-input" value="<?php echo esc_attr($this->zoom_event_category); ?>" />
				</div>
				<button type="button" class="btn" id="save_calendar_group"><?php esc_html_e('Save', 'bb-zoom-event-category'); ?></button>
			</div>
		</div>
	</div>

	<hr class="bb-sep-line" />

	<h4 class="bb-section-title"><?php esc_html_e('Delete Event', 'bb-zoom-event-category'); ?></h4>
	<div class="bb-field-wrapper">
		<div class="">
			<div class="bb-field-wrap">
				<div class="bp-input-wrap">
					<select name="bp-group-calendar-event-category-delete" id="bp-group-calendar-event-category-delete">
						<?php
							$args = [
								'post_type'              => 'tribe_events',
								'post_status'            => 'publish',
								'orderby'                => 'date',
								'order'                  => 'DESC',
								'meta_query'             => [
									[
										'key'     => 'bp_zoom_meeting_id',
										'compare' => 'NOT EXISTS'
									]
								],
								'posts_per_page'         => -1,
								'update_post_term_cache' => false
							];

							$calendar_events = new WP_Query( $args );

							if ( ! is_wp_error( $calendar_events ) ) :

								while ( $calendar_events->have_posts() ) :

									$calendar_events->the_post();

									echo '<option value="' . get_the_ID() . '">' . get_the_title() . '</option>';

								endwhile;

							endif;
							wp_reset_postdata();
						?>
					</select>
				</div>
				<br>
				<button type="button" class="btn" id="save_calendar_group_delete"><?php esc_html_e('Delete', 'bb-zoom-event-category'); ?></button>
			</div>
		</div>
	</div>

	<hr class="bb-sep-line" />

	<h4 class="bb-section-title"><?php esc_html_e('Create Event', 'bb-zoom-event-category'); ?></h4>
	<div class="bb-field-wrapper">
		<div class="bb-field-wrapper-inner">
			<div class="bb-field-wrap">
				<label for="bp-group-calendar-title"><?php esc_html_e('Event Title *', 'bb-zoom-event-category'); ?></label>
				<div class="bb-meeting-input-wrap">
					<input autocomplete="off" type="text" id="bp-group-calendar-title" value="" name="bp-group-calendar-title" required>
				</div>
			</div>

			<div class="bb-field-wrap">
				<label for="bp-group-calendar-description"><?php esc_html_e('Description (optional)', 'bb-zoom-event-category'); ?></label>
				<div class="bb-meeting-input-wrap">
					<textarea id="bp-group-calendar-description" name="bp-group-calendar-description"></textarea>
				</div>
			</div>
		</div>

		<div class="bb-field-wrapper-inner">
			<div class="bb-field-wrap">
				<label for="bp-group-calendar-start-date"><?php esc_html_e('When *', 'bb-zoom-event-category'); ?></label>
				<div class="bp-wrap-duration bb-meeting-input-wrap">
					<div class="bb-field-wrap start-date-picker">
						<input type="text" id="bp-group-calendar-start-date" value="<?php echo esc_attr( wp_date( 'Y-m-d', strtotime( 'now' ) ) ); ?>" name="bp-group-calendar-start-date" placeholder="yyyy-mm-dd" autocomplete="off">
					</div>
					<div class="bb-field-wrap start-time-picker">
						<?php
						$pending_minutes = 60 - wp_date( 'i', strtotime( 'now' ) );
						$current_minutes = strtotime( '+ ' . $pending_minutes . ' minutes' );
						?>
						<input type="text" id="bp-group-calendar-start-time" name="bp-group-calendar-start-time" autocomplete="off" placeholder="hh:mm" value="<?php echo esc_attr( wp_date( 'h:i', $current_minutes ) ); ?>" maxlength="5">
					</div>
				</div>
				<div class="bb-field-wrap start-time-picker">
					<select id="bp-group-calendar-medridian" name="bp-group-calendar-medridian">
						<option value="am" <?php selected( 'AM', wp_date( 'A', $current_minutes ) ); ?>>AM</option>
						<option value="pm" <?php selected( 'PM', wp_date( 'A', $current_minutes ) ); ?>>PM</option>
					</select>
				</div>
			</div>

			<div class="bb-field-wrap">
				<label for="bp-group-calendar-duration"><?php esc_html_e('Duration *', 'bb-zoom-event-category'); ?></label>
				<div class="bp-wrap-duration bb-meeting-input-wrap">
					<div class="bb-field-wrap">
						<select id="bp-group-calendar-duration-hr" name="bp-group-calendar-duration-hr">
							<?php
								for ( $hr = 0; $hr <= 24; $hr ++ ) {
									echo '<option value="' . esc_attr( $hr ) . '">' . esc_attr( $hr ) . '</option>';
								}
							?>
						</select>
						<label for="bp-group-calendar-duration-hr"><?php esc_html_e('hr', 'bb-zoom-event-category'); ?></label>
					</div>
					<div class="bb-field-wrap">
						<select id="bp-group-calendar-duration-min" name="bp-group-calendar-duration-min">
							<?php
								$min = 0;
								while ( $min <= 45 ) {
									$selected = ( 30 === $min ) ? 'selected="selected"' : '';
									echo '<option value="' . esc_attr( $min ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $min ) . '</option>';
									$min = $min + 15;
								}
							?>
						</select>
						<label for="bp-group-calendar-duration-min"><?php esc_html_e('min', 'bb-zoom-event-category'); ?></label>
					</div>
				</div>
			</div>

			<div class="bb-field-wrap">
				<label for="bp-group-calendar-timezone"><?php esc_html_e('Timezone *', 'bb-zoom-event-category'); ?></label>
				<div class="bb-meeting-input-wrap">
					<select id="bp-group-calendar-timezone" name="bp-group-calendar-timezone" tabindex="-1">
					<?php
						$timezones          = bp_zoom_get_timezone_options();
						$wp_timezone_str    = get_option( 'timezone_string' );
						$selected_time_zone = '';

						if ( empty( $wp_timezone_str ) ) {
							$wp_timezone_str_offset = get_option( 'gmt_offset' );
						} else {
							$time                   = new DateTime( 'now', new DateTimeZone( $wp_timezone_str ) );
							$wp_timezone_str_offset = $time->getOffset() / 60 / 60;
						}

						if ( ! empty( $timezones ) ) {
							foreach ( $timezones as $key => $time_zone ) {
								if ( $key === $wp_timezone_str ) {
									$selected_time_zone = $key;
									break;
								}

								$date            = new DateTime( 'now', new DateTimeZone( $key ) );
								$offset_in_hours = $date->getOffset() / 60 / 60;

								if ( (float) $wp_timezone_str_offset === (float) $offset_in_hours ) {
									$selected_time_zone = $key;
								}
							}
						}
					?>
					<?php foreach ( $timezones as $k => $timezone ) { ?>
						<option value="<?php echo esc_attr( $k ); ?>" <?php echo $k === $selected_time_zone ? 'selected="selected"' : ''; ?>><?php echo esc_html( $timezone ); ?></option>
					<?php } ?>
					</select>
				</div>
			</div>

		</div>

	</div>
</div>
