<?php
/*
Plugin Name: Flush Rewrite Rules in Debug Mode
Description: If WP_DEBUG is set to <code>true</code>, flush permalinks on every request. To reverse this effect, create a constant named <code>WP_DONT_FLUSH_PERMALINKS</code> and set it to <code>true</code>.
Version: 1.0.0
Author: Fat Panda
Author URI: http://github.com/withfatpanda
*/
add_action('init', function() {
	if (is_blog_installed()) {
		if (defined('WP_DEBUG') && WP_DEBUG) {
			if (!defined('WP_DONT_FLUSH_PERMALINKS') || !WP_DONT_FLUSH_PERMALINKS) {
				flush_rewrite_rules();
			}
		}
	}
});
