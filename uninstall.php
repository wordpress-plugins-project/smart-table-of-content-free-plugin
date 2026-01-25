<?php
/**
 * Uninstall Smart TOC
 *
 * @package Smart_TOC
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options
delete_option( 'smart_toc_settings' );
delete_option( 'smart_toc_install_date' );
delete_option( 'smart_toc_review_dismissed' );
delete_option( 'smart_toc_review_done' );

// Delete post meta
delete_post_meta_by_key( '_smart_toc_disable' );
delete_post_meta_by_key( '_smart_toc_settings' );

// Clear any cached data
delete_transient( 'smart_toc_cache' );
