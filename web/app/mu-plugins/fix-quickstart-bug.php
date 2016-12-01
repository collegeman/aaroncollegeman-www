<?php
/*
Plugin Name: Fix Quickstart Bug
Description: This plugin works around a <a href="https://github.com/Automattic/vip-quickstart/issues/512#issue-165799484">known bug</a> in the core Quick Start featureset.
Version: 1.0.0
Author: Fat Panda
Author URI: http://github.com/withfatpanda
*/
add_filter('wp_mail_from', function() {
	return 'wordpress@vip.local';
});