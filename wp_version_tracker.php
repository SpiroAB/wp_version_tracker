<?php
	/**
	 * Wordpress Version Tracker
	 *
	 * @package WP_Version_trac
	 * @author Puggan Sundragon <puggan@spiro.se>
	 * @version 0.1.0
	 *
	 * @wordpress-plugin
	 * Plugin Name: Wordpress Version Tracker
	 * Plugin URI: https://github.com/SpiroAB/isimo_wp
	 * Description: Fetch automatic-update information by reading other worpdress plugins version.json
	 * Version: 0.1.0
	 * Author: Puggan Sundragon <puggan@spiro.se>
	 * Author URI: https://spiro.se/
	 */

	require_once __DIR__ . '/Wordpress_Version_Tracker.php';

	$wp_version_tracker = new \SpiroAB\Wordpress_Version_Tracker();
