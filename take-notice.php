<?php
/**
 * Plugin Name: Take Notice
 * Plugin URI:  http://wordpress.org/plugins/take-notice/
 * Description: Add notices to all of your posts & pages at once. Customize where and how they appear.
 * Version:     2.0
 * Author:      Alison Barrett
 * Author URI:  http://alisothegeek.com
 * License:     GPLv2+
 * Text Domain: takenotice
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2014 Alison Barrett (email : alison@barre.tt)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Built using grunt-wp-plugin
 * Copyright (c) 2013 10up, LLC
 * https://github.com/10up/grunt-wp-plugin
 */

// Useful global constants
define( 'TAKENOTICE_VERSION', '2.0' );
define( 'TAKENOTICE_URL',     plugin_dir_url( __FILE__ ) );
define( 'TAKENOTICE_PATH',    dirname( __FILE__ ) . '/' );

/**
 * Default initialization for the plugin:
 * - Registers the default textdomain.
 */
function takenotice_init() {
	$locale = apply_filters( 'plugin_locale', get_locale(), 'takenotice' );
	load_textdomain( 'takenotice', WP_LANG_DIR . '/takenotice/takenotice-' . $locale . '.mo' );
	load_plugin_textdomain( 'takenotice', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

/**
 * Activate the plugin
 */
function takenotice_activate() {
	// First load the init scripts in case any rewrite functionality is being loaded
	takenotice_init();

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'takenotice_activate' );

/**
 * Deactivate the plugin
 * Uninstall routines should be in uninstall.php
 */
function takenotice_deactivate() {

}
register_deactivation_hook( __FILE__, 'takenotice_deactivate' );

// Wireup actions
add_action( 'init', 'takenotice_init' );

require_once TAKENOTICE_PATH . 'includes/class-take-notice-plugin.php';
	$Take_Notice = new Take_Notice_Plugin();
