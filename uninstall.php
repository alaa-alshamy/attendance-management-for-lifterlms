<?php
/**
 * Uninstall file, which would delete all user metadata and configuration settings
 *
 * @since 1.0
 */

// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
	die;
}

if('yes' === get_option(LLMS_AT_ENABLE_DELETE_DATA_OPTION_KEY, 'no')) {
	global $wpdb;

	// delete attendance course settings
	$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '\_at\_llms\_%';");

	// delete users attendance data
	$wpdb->query("DELETE FROM {$wpdb->prefix}lifterlms_user_postmeta WHERE meta_key LIKE '\_at\_llms\_%';" );

	// delete attendance options
	$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'at\_llms\_%';");
}
