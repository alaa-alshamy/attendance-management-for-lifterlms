<?php
/**
 * LifterLMS Attendance Management Shortcode
 *
 * @student   Muhammad Faizan Haidar
 * @category Admin
 * @package  LifterLMS Attendance Management/Admin/shortcode
 * @version  1.0
 */

if ( ! defined( "ABSPATH" ) ) {
    exit;
}

class LLMS_At_Short_Code {
    /**
     * constructor.
     */
    public function __construct () {
        $this->hooks();
    }

    private function hooks() {

        add_shortcode(
            'llmsat_top_attendant',
            [ $this, 'display_top_attendant' ]
        );

        add_shortcode(
            'llmsat_student_attendance',
            [ $this, 'display_student_attendance' ]
        );
    }

    /**
     * short code callback function
     * @param $course_id
     * @return html output
     */
    public function display_top_attendant( $atts ) {
        $atts = shortcode_atts(
        	[
            'course_id' => 0,
            'students_count' => 1,
					],
					$atts,
					'llmsat_top_attendant'
				);

        ob_start();

        $course_id = absint( trim( sanitize_text_field( $atts['course_id'] ) ) );
        $students_count = absint( trim( sanitize_text_field( $atts['students_count'] ) ) ) ?: 1;

				global $wpdb;

        $user_query  = $wpdb->prepare(
		"
						SELECT
							u.ID as id,
							u.display_name as display_name,
							upm.meta_value as at_count
						FROM {$wpdb->users} AS u
						JOIN {$wpdb->prefix}lifterlms_user_postmeta AS upm ON u.ID = upm.user_id
						WHERE
							upm.post_id = %d
							AND upm.meta_key = %s
						ORDER BY
							upm.meta_value DESC
						LIMIT %d
						;
					",
					[$course_id, LLMS_AT_COUNTER_META_KEY, $students_count]
				);
        $students    = $wpdb->get_results($user_query);

        // Check for results
        if ( count($students) ) {
					$maxAttendanceCount = absint(get_post_meta($course_id, LLMS_AT_MAX_COUNT_META_KEY, true));
					$courseTitle = get_the_title( $course_id )
        	?>
            <ul id=""> <?php
							// loop through each student
							foreach ( $students as $key => $student ) {
									$count = intval( $student->at_count );
									?>
									<li>
										<span class="">
											<b><?php echo __( 'Number ' . intval ( $key + 1 ) . ' Attendant for the course "' . $courseTitle . '" :', LLMS_At_TEXT_DOMAIN );?> </b>
										</span>
										<ul class="llmsat-dicey">
											<li><?php echo __( '<b>Student Name</b> : ' . $student->display_name, LLMS_At_TEXT_DOMAIN ); ?></li>
											<li><?php echo __( '<b>Course Name</b> : ' . $courseTitle, LLMS_At_TEXT_DOMAIN ); ?></li>
											<li><?php echo __( '<b>Attendance</b> : ' . round($count/$maxAttendanceCount * 100) . '%', LLMS_At_TEXT_DOMAIN ); ?></li>
										</ul>
									</li>
									<?php
							} ?>
						</ul><?php
        } else {
            ?>
            <ul class="llmsat-dicey">
            	<li> <?php echo __('<b>No student found in this course</b>', LLMS_At_TEXT_DOMAIN ); ?></li>
            </ul>
            <?php
        }?>
        <?php
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }

    /**
     * short code callback function
     * @param $user_id, course_id
     * @return html output
     */
    public function display_student_attendance( $atts ) {
				$user_id = get_current_user_id();
        $atts = shortcode_atts(
        	[
            'user_id'   => $user_id,
            'course_id' => 0,
					],
					$atts,
					'llmsat_student_attendance'
				);

        ob_start();

        $course_id   = absint( trim( sanitize_text_field( $atts['course_id'] ) ) );
        $user_id     = absint( trim( sanitize_text_field( $atts['user_id'] ) ) ) ?: $user_id;

        $user        = get_userdata( $user_id );
        if (
					$user_id != 0
					&& $user
					&& llms_is_user_enrolled($user_id, $course_id)
				) {
						$count = llms_get_user_postmeta($user_id, $course_id, LLMS_AT_COUNTER_META_KEY, true);
            $count = intval( $count ) ?: 0;

						$maxAttendanceCount = absint(get_post_meta($course_id, LLMS_AT_MAX_COUNT_META_KEY, true));
            ?>
            <ul id="">
							<li>
								<span class="">
									<b><?php echo __( 'Attendance of ' . $user->display_name . ' :', LLMS_At_TEXT_DOMAIN );?> </b>
								</span>
								<ul class="llmsat-dicey">
									<li><?php echo __( '<b>Student Name</b> : ' . $user->display_name, LLMS_At_TEXT_DOMAIN ); ?></li>
									<li><?php echo __( '<b>Course Name</b> : ' . get_the_title( $course_id ), LLMS_At_TEXT_DOMAIN ); ?></li>
									<li><?php echo __( '<b>Attendance</b> : ' . round($count/$maxAttendanceCount * 100) . '%', LLMS_At_TEXT_DOMAIN ); ?></li>
								</ul>
							</li>
            </ul>
            <?php
        } else {
            ?>
						<ul class="llmsat-dicey">
							<li> <?php echo __('<b>Invalid student ID or course ID or the student does not enrolled in the course</b>', LLMS_At_TEXT_DOMAIN ); ?></li>
						</ul>
            <?php
        }
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }
}
return new LLMS_At_Short_Code();
