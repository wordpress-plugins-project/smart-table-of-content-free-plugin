<?php
/**
 * Plugin Name: Smart Table of Contents
 * Plugin URI: https://wordpress.org/plugins/anik-smart-table-of-contents/
 * Description: A lightweight, SEO-friendly Table of Contents plugin that automatically generates TOC from your headings with smooth scroll and collapsible features.
 * Version: 1.2.0
 * Author: Anik Chowdhury
 * Author URI: https://smallseoengine.com
 * Text Domain: anik-smart-table-of-contents
 * Domain Path: /languages
 * License: GPLv2 or later
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
define( 'ANIKSMTA_VERSION', '1.2.0' );
define( 'ANIKSMTA_PATH', plugin_dir_path( __FILE__ ) );
define( 'ANIKSMTA_URL', plugin_dir_url( __FILE__ ) );
define( 'ANIKSMTA_BASENAME', plugin_basename( __FILE__ ) );

// Activation hook
register_activation_hook( __FILE__, 'aniksmta_activate' );
function aniksmta_activate() {
	// Set default options on activation
	if ( ! get_option( 'aniksmta_settings' ) ) {
		$defaults = array(
			'enabled'                       => true,
			'post_types'                    => array( 'post', 'page' ),
			'min_headings'                  => 2,
			'heading_levels'                => array( 2, 3, 4, 5, 6 ),
			'default_collapsed'             => true,
			'position'                      => 'before_content',
			'smooth_scroll'                 => true,
			'highlight_active'              => true,
			'title'                         => __( 'Table of Contents', 'anik-smart-table-of-contents' ),
			'theme_color'                   => '#0073aa',
			'exclude_class'                 => 'no-toc',
			'scroll_offset'                 => 80,
			'show_numbers'                  => true,
			'counter_format'                => 'decimal',
			'toc_theme'                     => 'dark',
			'exclude_headings'              => '',
			'schema_enabled'                => true,
			'sticky_toc'                    => false,
			'sticky_position'               => 'inline',
			'sticky_width'                  => 280,
			'sticky_offset'                 => 20,
			'copy_link'                     => true,
			'reading_progress'              => true,
			'dynamic_content'               => true,
			'lazy_load_toc'                 => true,
			'mobile_toc_modal'              => false,
			'floating_desktop'              => true,
			'floating_toc_position'         => 'right',
			'floating_toc_style'            => 'icon_text',
			'floating_toc_theme'            => 'dark',
			'floating_toc_panel_width'      => 320,
			'floating_toc_auto_close'       => true,
			'floating_toc_show_progress'    => true,
			'floating_toc_default_expanded' => true,
			'collapsible_sections'          => true,
			'sections_collapsed'            => false,
			'reading_time'                  => true,
			'back_to_top'                   => false,
			'back_to_top_icon'              => 'arrow',
			'back_to_top_style'             => 'circle',
			'back_to_top_bg_color'          => '',
			'back_to_top_icon_color'        => '#ffffff',
			'back_to_top_show_desktop'      => true,
			'back_to_top_show_tablet'       => true,
			'back_to_top_show_mobile'       => true,
			'exclude_home'                  => true,
			'exclude_archive'               => true,
			'exclude_search'                => true,
			'exclude_404'                   => true,
			'auto_dark_mode'                => false,
			'toggle_icon_style'             => 'chevron',
		);
		update_option( 'aniksmta_settings', $defaults );
	}
}

// Deactivation hook
register_deactivation_hook( __FILE__, 'aniksmta_deactivate' );
function aniksmta_deactivate() {
	// Cleanup transients if any
	delete_transient( 'aniksmta_cache' );
}

// Initialize plugin
require_once ANIKSMTA_PATH . 'includes/class-core.php';
Aniksmta_Core::instance();
