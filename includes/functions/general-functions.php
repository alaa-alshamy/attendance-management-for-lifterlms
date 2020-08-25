<?php

defined( 'ABSPATH' ) || exit;

function llmsat_is_enabled($course_id) {
	$enableAttendanceValue = get_post_meta($course_id, LLMS_AT_ENABLE_META_KEY, true);

	return (
		'yes' === $enableAttendanceValue
		|| (
			!$enableAttendanceValue
			&& 'yes' === get_option( LLMS_AT_GLOBAL_ENABLE_OPTION_KEY, 'yes' )
		)
	);
}

function llmsat_is_enabled_for_students($course_id) {
	$enableAttendanceForStudentsValue = get_post_meta($course_id, LLMS_AT_ENABLE_FOR_STUDENTS_META_KEY, true);

	return (
		'yes' === $enableAttendanceForStudentsValue
		|| (
			!$enableAttendanceForStudentsValue
			&& 'yes' === get_option( LLMS_AT_GLOBAL_ENABLE_FOR_STUDENTS_OPTION_KEY, 'yes' )
		)
	);
}
