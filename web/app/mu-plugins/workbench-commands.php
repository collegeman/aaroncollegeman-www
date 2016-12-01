<?php
/*
Plugin Name: Workbench WP-CLI Commands
Description: All of the WP-CLI commands that are part of this workbench. 
Plugin URI: https://github.com/withfatpanda/workbench-wordpress
Author: Fat Panda
Author URI: https://github.com/withfatpanda
*/
namespace FatPanda\WordPress;
use Illuminate\Filesystem\Filesystem;

class WorkbenchCommand {

	/**
	 * Make something on the workbench
	 */
	function __invoke( $args )
	{
		global $root_dir;

		$workbench = new Workbench(new Filesystem, $root_dir);

		$commands = [ 'make', 'whip', 'cleanup' ];

		$make = [ 'plugin', 'mu-plugin', 'theme' ];

		if (empty($args[0]) || !in_array($args[0], $commands)) {
			\WP_CLI::error("Missing argument: wp workbench {command}; valid workbench commands are: " . implode(", ", $commands));
		}	

		if ('make' === $args[0]) {
			if (empty($args[1]) || !in_array($args[1], $make)) {
				\WP_CLI::error("Missing argument: wp workbench make {what}; things you can make are: " . implode(", ", $make));
			}	

			if (empty($args[2])) {
				\WP_CLI::error("Missing argument: wp workbench make ".$args[1]." {name}");
			}

			\WP_CLI::log("Building {$args[1]} \"{$args[2]}\"...");

			if ('plugin' === $args[1]) {
				$workbench->createPlugin($args[2], 'plugin', $args);
			
			} else if ('mu-plugin' === $args[1]) {
				$workbench->createPlugin($args[2], 'mu-plugin', $args);

			}	else if ('theme' === $args[1]) {
				$workbench->createTheme($args[2]);

			}

			return \WP_CLI::success("Done!");
		}

		if ('cleanup' === $args[0]) {
			if (empty($args[1]) || !in_array($args[1], $make)) {
				\WP_CLI::error("Missing argument: wp workbench cleanup {what}; things you can cleanup are: " . implode(", ", $make));
			}	

			if (empty($args[2])) {
				\WP_CLI::error("Missing argument: wp workbench make ".$args[1]." {name}");
			}

			if ('plugin' === $args[1]) {
				$workbench->cleanupPlugin($args[2], $args);
			
			} else if ('mu-plugin' === $args[1]) {
				$workbench->cleanupMuPlugin($args[2], $args);

			}	else if ('theme' === $args[1]) {
				$workbench->cleanupTheme($args[2]);

			}

			return \WP_CLI::success("Done!");

		}



	}

}

if (class_exists('WP_CLI')) {
	\WP_CLI::add_command('workbench', 'FatPanda\WordPress\WorkbenchCommand');
}