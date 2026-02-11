<?php
/**
 * Uninstall Smart TOC
 *
 * @package Anik_Smart_TOC
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options
delete_option( 'aniksmta_settings' );
delete_option( 'aniksmta_install_date' );
delete_option( 'aniksmta_review_dismissed' );
delete_option( 'aniksmta_review_done' );

// Delete post meta
delete_post_meta_by_key( '_aniksmta_disable' );

// Clear any cached data
delete_transient( 'aniksmta_cache' );
