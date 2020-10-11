<?php
/**
 * Attendance Management For LifterLMS Core
 *
 * @author   Muhammad Faizan Haidar
 * @package  Attendance Management For LifterLMS Core
 * @version  1.0
 */

if ( ! defined( "ABSPATH" ) ) {
    exit;
}

/**
 * LLMS_AT_Core Class
 */
class LLMS_AT_Core {

    /**
     * Constructor
     */
    public function __construct () {

        $this->hooks();
    }

    private function hooks() {
        add_action(
            'lifterlms_single_course_before_summary',
            [ $this,'add_content_before_course_summary' ],
            10,
            0
        );

        add_action(
            'wp_ajax_llmsat_attendance_btn_ajax_action',
            [ $this, 'llmsat_attendance_btn_ajax_action' ],
            10
        );

        add_action( 'wp_ajax_nopriv_llmsat_attendance_btn_ajax_action',
            [ $this, 'llmsat_attendance_btn_ajax_action' ],
            10
        );
    }

    /**
     * Add Content after course summary
     * @return void/string
     */
    public function add_content_before_course_summary() {

        if(
        	!is_singular( 'course' )
        	|| !is_user_logged_in()
		) {
            return;
        }

        $course_id = get_the_ID();
        $user_id   = get_current_user_id();
        if( ! $course_id || get_post_type( $course_id ) != 'course' ) {
            return;
        }

        $course = new LLMS_Course( $course_id );
        if( $course->has_date_passed( 'end_date' ) ) {
            return;
        }

        if (
			llmsat_is_enabled($course_id)
			&& llmsat_is_enabled_for_students($course_id)
			&& llms_is_user_enrolled($user_id, $course_id)
		) {
			$attendance_button_text = __( "Mark Present", LLMS_At_TEXT_DOMAIN );
			$attendance_button_text = apply_filters( "llms_attendance_button_text", $attendance_button_text );

			$output = '<div class="llmsat-button-container">';
			$output .= '<input type="submit" value="'.$attendance_button_text.'" href="javascript:;" onclick="llmsat_attendance_btn_ajax('.$course_id.', '.$user_id.')" class="llmsat-attendance-btn llmsat-btn"/>';
            $output .= '<div id="llmsat-ajax-response-id" class="llmsat-ajax-response"><span></span></div>';
			$output .= '</div>';

			echo $output;
        }
    }

    /**
     * Ajax action to mark attendance
     *
     * @return void
     */
    public function llmsat_attendance_btn_ajax_action() {

		$user_ids  = explode(',', sanitize_text_field( $_POST['uids'] ) );
        $course_id = sanitize_text_field( $_POST['pid'] );

        if (!empty($user_ids) && $course_id) {

			$nowTimeStamp = strtotime(current_time('mysql'));

			$isSuccess = false;
			foreach ($user_ids as $user_id) {
				$user_id = intval($user_id);

				if (llms_is_user_enrolled($user_id, $course_id)) {
					$latestUpdatedDateTime = llms_get_user_postmeta($user_id, $course_id, LLMS_AT_COUNTER_META_KEY, true, 'updated_date');
					if ($latestUpdatedDateTime) {
						// 1 attendance per day
						$date = new DateTime(explode(' ', $latestUpdatedDateTime)[0]);
						$date->add(new DateInterval('P1D'));

						$allowToMakeAttendance = ($nowTimeStamp > $date->getTimestamp());
					}
					else {
						$allowToMakeAttendance = true;
					}

					if ($allowToMakeAttendance) {
						$counter = llms_get_user_postmeta($user_id, $course_id, LLMS_AT_COUNTER_META_KEY, true);
						if ($counter) {
							$counter += 1;
						} else {
							$counter = 1;
						}

						llms_update_user_postmeta($user_id, $course_id, LLMS_AT_COUNTER_META_KEY, $counter);
						$isSuccess = true;
					}
				}
			}

			if ($isSuccess) {
				$success_message = __("Attendance marked successfully", LLMS_At_TEXT_DOMAIN);

				echo apply_filters("llms_attendance_success_message", $success_message) . "1";
			}
			else
			{
				$already_marked = __("Already marked present", LLMS_At_TEXT_DOMAIN);

				echo apply_filters("llms_attendance_already_marked_message", $already_marked) . "3";
			}
		} else {
			$failed_message = __("Attendance was not marked successfully", LLMS_At_TEXT_DOMAIN);

			echo apply_filters("llms_attendance_failed_message", $failed_message) . "2";
		}
		exit;
    }
}
return new LLMS_AT_Core();
