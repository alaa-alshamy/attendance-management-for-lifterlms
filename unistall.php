<?php
/**
 * Uninstall file, which would delete all user metadata and configuration settings
 *
 * @since 1.0
 */
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
    exit();

global $wpdb;

$llmsat_options = get_option(LLMS_AT_OPTIONS_OPTION_KEY, []);

if($llmsat_options['llmsat_delete_attendance'] === 'on') {
	// delete attendance course settings
	$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '\_at\_llms\_%';");

	// delete users attendance data
	$wpdb->query("DELETE FROM {$wpdb->prefix}lifterlms_user_postmeta WHERE meta_key LIKE '\_at\_llms\_%';" );

	// delete attendance options
	$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'at\_llms\_%';");
}
