<?php
/**
 * Plugin Name: Anik Smart Table of Contents
 * Plugin URI:  https://smallseoengine.com/plugins/anik-smart-table-of-contents/
 * Description: A lightweight, SEO-friendly Table of Contents plugin that automatically generates TOC from your headings with smooth scroll and collapsible features.
 * Version:     1.0.8
 * Author:      Anik Chowdhury
 * Author URI:  https://smallseoengine.com
 * Text Domain: anik-smart-table-of-contents
 * Domain Path: /languages
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Tested up to: 6.9
 * Requires PHP: 7.4
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants
define( 'SMART_TOC_VERSION', '1.0.8' );
define( 'SMART_TOC_PATH', plugin_dir_path( __FILE__ ) );
define( 'SMART_TOC_URL', plugin_dir_url( __FILE__ ) );
define( 'SMART_TOC_BASENAME', plugin_basename( __FILE__ ) );

// Activation hook
register_activation_hook( __FILE__, 'smart_toc_activate' );
function smart_toc_activate() {
	// Set default options on activation
	if ( ! get_option( 'smart_toc_settings' ) ) {
		$defaults = array(
			'enabled'           => true,
			'post_types'        => array( 'post', 'page' ),
			'min_headings'      => 2,
			'heading_levels'    => array( 2, 3, 4, 5, 6 ),
			'default_collapsed' => true,
			'position'          => 'before_content',
			'smooth_scroll'     => true,
			'highlight_active'  => true,
			'title'             => __( 'Table of Contents', 'anik-smart-table-of-contents' ),
			'theme_color'       => '#0073aa',
			'scroll_offset'     => 80,
			'show_numbers'      => true,
		);
		update_option( 'smart_toc_settings', $defaults );
	}
}

// Deactivation hook
register_deactivation_hook( __FILE__, 'smart_toc_deactivate' );
function smart_toc_deactivate() {
	// Cleanup transients if any
	delete_transient( 'smart_toc_cache' );
}

// Initialize plugin
require_once SMART_TOC_PATH . 'includes/class-core.php';
Smart_TOC_Core::instance();
