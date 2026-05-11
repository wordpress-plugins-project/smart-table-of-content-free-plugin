<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Settings Page
 *
 * @package Anik_Smart_TOC
 */

class Aniksmta_Admin {

	/**
	 * Settings instance
	 *
	 * @var Aniksmta_Settings
	 */
	private $settings;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->settings = new Aniksmta_Settings();

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
		add_filter( 'plugin_action_links_' . ANIKSMTA_BASENAME, array( $this, 'plugin_action_links' ) );

		// Meta box for per-post settings
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_meta_box' ) );

		// Dashboard widget
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );

		// Review request notice
		add_action( 'admin_notices', array( $this, 'review_request_notice' ) );
		add_action( 'wp_ajax_aniksmta_dismiss_review', array( $this, 'dismiss_review_notice' ) );

		// Track installation date
		$this->maybe_set_install_date();
	}

	/**
	 * Set installation date if not already set
	 */
	private function maybe_set_install_date() {
		if ( ! get_option( 'aniksmta_install_date' ) ) {
			update_option( 'aniksmta_install_date', time() );
		}
	}

	/**
	 * Add settings link to plugins page
	 */
	public function plugin_action_links( $links ) {
		$settings_link = '<a href="' . admin_url( 'options-general.php?page=aniksmta-settings' ) . '">' . __( 'Settings', 'anik-smart-table-of-contents' ) . '</a>';
		$pro_link      = '<a href="https://smallseoengine.com/plugins/smart-table-of-contents/" target="_blank" style="color:#00a32a;font-weight:600;">' . __( 'Get Pro', 'anik-smart-table-of-contents' ) . '</a>';
		array_unshift( $links, $settings_link );
		$links[] = $pro_link;
		return $links;
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'Smart TOC Settings', 'anik-smart-table-of-contents' ),
			__( 'Smart TOC', 'anik-smart-table-of-contents' ),
			'manage_options',
			'aniksmta-settings',
			array( $this, 'settings_page' )
		);
	}

	/**
	 * Enqueue admin assets
	 */
	public function admin_assets( $hook ) {
		$settings_hook = 'settings_page_aniksmta-settings';

		// Pages that need our admin CSS and JS.
		$allowed_hooks = array( $settings_hook, 'post.php', 'post-new.php', 'index.php', 'plugins.php' );
		if ( ! in_array( $hook, $allowed_hooks, true ) ) {
			return;
		}

		wp_enqueue_style(
			'aniksmta-admin',
			ANIKSMTA_URL . 'assets/css/admin.css',
			array(),
			ANIKSMTA_VERSION
		);

		wp_enqueue_script(
			'aniksmta-admin-js',
			ANIKSMTA_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			ANIKSMTA_VERSION,
			true
		);

		wp_localize_script(
			'aniksmta-admin-js',
			'aniksmtaAdmin',
			array(
				'copiedMsg' => __( 'System info copied to clipboard!', 'anik-smart-table-of-contents' ),
			)
		);

		// Color picker only on settings page.
		if ( $settings_hook === $hook ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );
			wp_add_inline_script(
				'wp-color-picker',
				'jQuery(document).ready(function($){ $(".smart-toc-color-picker").wpColorPicker(); });'
			);
		}
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting(
			'aniksmta_settings_group',
			'aniksmta_settings',
			array( $this, 'sanitize_settings' )
		);
	}

	/**
	 * Sanitize settings
	 */
	public function sanitize_settings( $input ): array {
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		// submit_button() posts the button label as value, so check key presence instead of literal value.
		if ( array_key_exists( 'reset_defaults', $input ) ) {
			return $this->settings->get_defaults();
		}

		$sanitized = array();

		$sanitized['enabled'] = ! empty( $input['enabled'] );

		$sanitized['post_types'] = isset( $input['post_types'] ) && is_array( $input['post_types'] )
			? array_map( 'sanitize_key', $input['post_types'] )
			: array( 'post', 'page' );

		$sanitized['min_headings'] = isset( $input['min_headings'] )
			? absint( $input['min_headings'] )
			: 2;

		$sanitized['heading_levels'] = isset( $input['heading_levels'] ) && is_array( $input['heading_levels'] )
			? array_map( 'absint', $input['heading_levels'] )
			: array( 2, 3, 4, 5, 6 );

		$sanitized['default_collapsed'] = ! empty( $input['default_collapsed'] );

		$sanitized['position'] = isset( $input['position'] )
			? sanitize_key( $input['position'] )
			: 'before_content';

		$sanitized['smooth_scroll']    = ! empty( $input['smooth_scroll'] );
		$sanitized['highlight_active'] = ! empty( $input['highlight_active'] );

		$sanitized['title'] = isset( $input['title'] )
			? sanitize_text_field( $input['title'] )
			: __( 'Table of Contents', 'anik-smart-table-of-contents' );

		$sanitized['theme_color'] = isset( $input['theme_color'] )
			? sanitize_hex_color( $input['theme_color'] )
			: '#0073aa';

		$sanitized['scroll_offset'] = isset( $input['scroll_offset'] )
			? absint( $input['scroll_offset'] )
			: 80;

		$sanitized['show_numbers'] = ! empty( $input['show_numbers'] );

		// Counter format.
		$allowed_formats             = array( 'decimal', 'roman', 'hierarchical', 'none' );
		$sanitized['counter_format'] = isset( $input['counter_format'] ) && in_array( $input['counter_format'], $allowed_formats, true )
			? $input['counter_format']
			: 'decimal';

		// TOC theme.
		$allowed_themes         = array( 'default', 'light', 'dark', 'minimal' );
		$sanitized['toc_theme'] = isset( $input['toc_theme'] ) && in_array( $input['toc_theme'], $allowed_themes, true )
			? $input['toc_theme']
			: 'dark';

		// Toggle icon style.
		$allowed_icons                  = array( 'chevron', 'plus_minus' );
		$sanitized['toggle_icon_style'] = isset( $input['toggle_icon_style'] ) && in_array( $input['toggle_icon_style'], $allowed_icons, true )
			? $input['toggle_icon_style']
			: 'chevron';

		// Exclude headings by text (comma-separated).
		$sanitized['exclude_headings'] = isset( $input['exclude_headings'] )
			? sanitize_textarea_field( $input['exclude_headings'] )
			: '';

		// Schema enabled.
		$sanitized['schema_enabled'] = ! empty( $input['schema_enabled'] );

		// Floating Desktop TOC.
		$sanitized['floating_desktop'] = ! empty( $input['floating_desktop'] );

		// Freemium settings added.
		$sanitized['sticky_toc']      = ! empty( $input['sticky_toc'] );
		$sanitized['sticky_position'] = isset( $input['sticky_position'] ) ? sanitize_key( $input['sticky_position'] ) : 'inline';
		if ( ! in_array( $sanitized['sticky_position'], array( 'inline', 'left', 'right' ), true ) ) {
			$sanitized['sticky_position'] = 'inline';
		}
		$sanitized['sticky_width'] = isset( $input['sticky_width'] ) ? absint( $input['sticky_width'] ) : 280;
		if ( $sanitized['sticky_width'] < 200 || $sanitized['sticky_width'] > 500 ) {
			$sanitized['sticky_width'] = 280;
		}
		$sanitized['sticky_offset']        = isset( $input['sticky_offset'] )
			? absint( $input['sticky_offset'] )
			: 20;
		$sanitized['copy_link']            = ! empty( $input['copy_link'] );
		$sanitized['reading_progress']     = ! empty( $input['reading_progress'] );
		$sanitized['dynamic_content']      = ! empty( $input['dynamic_content'] );
		$sanitized['lazy_load_toc']        = ! empty( $input['lazy_load_toc'] );
		$sanitized['mobile_toc_modal']     = ! empty( $input['mobile_toc_modal'] );
		$sanitized['collapsible_sections'] = ! empty( $input['collapsible_sections'] );
		$sanitized['sections_collapsed']   = ! empty( $input['sections_collapsed'] );
		$sanitized['reading_time']         = ! empty( $input['reading_time'] );
		$sanitized['back_to_top']          = ! empty( $input['back_to_top'] );
		$sanitized['back_to_top_icon']     = isset( $input['back_to_top_icon'] ) ? sanitize_key( $input['back_to_top_icon'] ) : 'arrow';
		if ( ! in_array( $sanitized['back_to_top_icon'], array( 'arrow', 'chevron', 'double', 'rocket' ), true ) ) {
			$sanitized['back_to_top_icon'] = 'arrow';
		}
		$sanitized['back_to_top_style'] = isset( $input['back_to_top_style'] ) ? sanitize_key( $input['back_to_top_style'] ) : 'circle';
		if ( ! in_array( $sanitized['back_to_top_style'], array( 'circle', 'rounded', 'pill' ), true ) ) {
			$sanitized['back_to_top_style'] = 'circle';
		}
		$sanitized['back_to_top_bg_color']     = ! empty( $input['back_to_top_bg_color'] ) ? sanitize_hex_color( $input['back_to_top_bg_color'] ) : '';
		$sanitized['back_to_top_icon_color']   = ! empty( $input['back_to_top_icon_color'] ) ? sanitize_hex_color( $input['back_to_top_icon_color'] ) : '#ffffff';
		$sanitized['back_to_top_show_desktop'] = ! empty( $input['back_to_top_show_desktop'] );
		$sanitized['back_to_top_show_tablet']  = ! empty( $input['back_to_top_show_tablet'] );
		$sanitized['back_to_top_show_mobile']  = ! empty( $input['back_to_top_show_mobile'] );

		$sanitized['floating_toc_position'] = isset( $input['floating_toc_position'] ) ? sanitize_key( $input['floating_toc_position'] ) : 'right';
		if ( ! in_array( $sanitized['floating_toc_position'], array( 'left', 'right' ), true ) ) {
			$sanitized['floating_toc_position'] = 'right';
		}
		$sanitized['floating_toc_style'] = isset( $input['floating_toc_style'] ) ? sanitize_key( $input['floating_toc_style'] ) : 'icon_text';
		if ( ! in_array( $sanitized['floating_toc_style'], array( 'icon_only', 'icon_text', 'icon_counter' ), true ) ) {
			$sanitized['floating_toc_style'] = 'icon_text';
		}
		$sanitized['floating_toc_theme'] = isset( $input['floating_toc_theme'] ) ? sanitize_key( $input['floating_toc_theme'] ) : 'dark';
		if ( ! in_array( $sanitized['floating_toc_theme'], array( 'default', 'dark' ), true ) ) {
			$sanitized['floating_toc_theme'] = 'dark';
		}
		$sanitized['floating_toc_panel_width'] = isset( $input['floating_toc_panel_width'] ) ? absint( $input['floating_toc_panel_width'] ) : 320;
		if ( $sanitized['floating_toc_panel_width'] < 280 || $sanitized['floating_toc_panel_width'] > 450 ) {
			$sanitized['floating_toc_panel_width'] = 320;
		}
		$sanitized['floating_toc_auto_close']       = ! empty( $input['floating_toc_auto_close'] );
		$sanitized['floating_toc_show_progress']    = ! empty( $input['floating_toc_show_progress'] );
		$sanitized['floating_toc_default_expanded'] = ! empty( $input['floating_toc_default_expanded'] );

		$sanitized['exclude_home']    = ! empty( $input['exclude_home'] );
		$sanitized['exclude_archive'] = ! empty( $input['exclude_archive'] );
		$sanitized['exclude_search']  = ! empty( $input['exclude_search'] );
		$sanitized['exclude_404']     = ! empty( $input['exclude_404'] );
		$sanitized['auto_dark_mode']  = ! empty( $input['auto_dark_mode'] );

		// Preserve exclude_class (not in the form, but used by the renderer).
		$sanitized['exclude_class'] = isset( $input['exclude_class'] )
			? sanitize_html_class( $input['exclude_class'] )
			: 'no-toc';

		return $sanitized;
	}

	/**
	 * Render settings page
	 */
	public function settings_page() {
		$settings = $this->settings->get_all();
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Tab navigation only, no data processing.
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'settings';
		?>
		<div class="wrap smart-toc-admin">
			<h1><?php esc_html_e( 'Smart Table of Contents', 'anik-smart-table-of-contents' ); ?></h1>
			
			<!-- Pro Banner -->
			<div class="smart-toc-pro-banner">
				<div class="pro-banner-content">
					<h3>🚀 <?php esc_html_e( 'Upgrade to Smart TOC Pro', 'anik-smart-table-of-contents' ); ?></h3>
					<p><?php esc_html_e( 'Get advanced features like Custom CSS, Advanced Mobile Controls, Estimated Reading Time, Premium Themes, and more!', 'anik-smart-table-of-contents' ); ?></p>
					<a href="https://smallseoengine.com/plugins/smart-table-of-contents/" target="_blank" class="button button-primary"><?php esc_html_e( 'Get Pro Version', 'anik-smart-table-of-contents' ); ?></a>
				</div>
			</div>

			<!-- Navigation Tabs -->
			<nav class="nav-tab-wrapper smart-toc-tabs">
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=aniksmta-settings&tab=settings' ) ); ?>" class="nav-tab <?php echo 'settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Settings', 'anik-smart-table-of-contents' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=aniksmta-settings&tab=documentation' ) ); ?>" class="nav-tab <?php echo 'documentation' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-book"></span> <?php esc_html_e( 'Documentation', 'anik-smart-table-of-contents' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=aniksmta-settings&tab=support' ) ); ?>" class="nav-tab <?php echo 'support' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-sos"></span> <?php esc_html_e( 'Help & Support', 'anik-smart-table-of-contents' ); ?>
				</a>
			</nav>

			<?php if ( 'settings' === $active_tab ) : ?>
			<form method="post" action="options.php">
				<?php settings_fields( 'aniksmta_settings_group' ); ?>
				
				<div class="smart-toc-settings-grid">
					<!-- General Settings -->
					<div class="smart-toc-card">
						<h2><?php esc_html_e( 'General Settings', 'anik-smart-table-of-contents' ); ?></h2>
						
						<table class="form-table">
							<tr>
								<th scope="row"><?php esc_html_e( 'Enable TOC', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="aniksmta_settings[enabled]" value="1" <?php checked( $settings['enabled'] ); ?>>
										<?php esc_html_e( 'Enable Table of Contents globally', 'anik-smart-table-of-contents' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Post Types', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<?php
									$post_types = get_post_types( array( 'public' => true ), 'objects' );
									foreach ( $post_types as $pt_slug => $post_type ) :
										if ( ! ( $post_type instanceof \WP_Post_Type ) ) {
											continue;
										}
										if ( 'attachment' === $post_type->name ) {
											continue;
										}
										?>
										<label style="display: block; margin-bottom: 5px;">
											<input type="checkbox" 
													name="aniksmta_settings[post_types][]" 
													value="<?php echo esc_attr( $post_type->name ); ?>"
													<?php checked( in_array( $post_type->name, $settings['post_types'], true ) ); ?>>
											<?php echo esc_html( $post_type->label ); ?>
										</label>
									<?php endforeach; ?>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Minimum Headings', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<input type="number" 
											name="aniksmta_settings[min_headings]" 
											value="<?php echo esc_attr( $settings['min_headings'] ); ?>" 
											min="1" 
											max="10"
											class="small-text">
									<p class="description"><?php esc_html_e( 'Minimum number of headings required to display TOC', 'anik-smart-table-of-contents' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Heading Levels', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<?php for ( $i = 2; $i <= 6; $i++ ) : ?>
										<label style="margin-right: 15px;">
											<input type="checkbox" 
													name="aniksmta_settings[heading_levels][]" 
													value="<?php echo esc_attr( $i ); ?>"
													<?php checked( in_array( $i, $settings['heading_levels'], true ) ); ?>>
											H<?php echo esc_html( $i ); ?>
										</label>
									<?php endfor; ?>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Exclude Headings', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<textarea name="aniksmta_settings[exclude_headings]" rows="3" class="large-text" placeholder="<?php esc_attr_e( 'Introduction, References, Comments', 'anik-smart-table-of-contents' ); ?>"><?php echo esc_textarea( $settings['exclude_headings'] ); ?></textarea>
									<p class="description"><?php esc_html_e( 'Enter heading texts to exclude from TOC, separated by commas. Partial matches are supported.', 'anik-smart-table-of-contents' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Display Conditions', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<label style="display: block; margin-bottom: 5px;">
										<input type="checkbox" name="aniksmta_settings[exclude_home]" value="1" <?php checked( ! empty( $settings['exclude_home'] ) ); ?>>
										<?php esc_html_e( 'Exclude on Homepage', 'anik-smart-table-of-contents' ); ?>
									</label>
									<label style="display: block; margin-bottom: 5px;">
										<input type="checkbox" name="aniksmta_settings[exclude_archive]" value="1" <?php checked( ! empty( $settings['exclude_archive'] ) ); ?>>
										<?php esc_html_e( 'Exclude on Archive Pages', 'anik-smart-table-of-contents' ); ?>
									</label>
									<label style="display: block; margin-bottom: 5px;">
										<input type="checkbox" name="aniksmta_settings[exclude_search]" value="1" <?php checked( ! empty( $settings['exclude_search'] ) ); ?>>
										<?php esc_html_e( 'Exclude on Search Pages', 'anik-smart-table-of-contents' ); ?>
									</label>
									<label style="display: block; margin-bottom: 5px;">
										<input type="checkbox" name="aniksmta_settings[exclude_404]" value="1" <?php checked( ! empty( $settings['exclude_404'] ) ); ?>>
										<?php esc_html_e( 'Exclude on 404 Pages', 'anik-smart-table-of-contents' ); ?>
									</label>
									<p class="description"><?php esc_html_e( 'Checked by default to prevent TOC from appearing on non-singular views.', 'anik-smart-table-of-contents' ); ?></p>
								</td>
							</tr>
						</table>
					</div>

					<!-- Display Settings -->
					<div class="smart-toc-card">
						<h2><?php esc_html_e( 'Display Settings', 'anik-smart-table-of-contents' ); ?></h2>
						
						<table class="form-table">
							<tr>
								<th scope="row"><?php esc_html_e( 'TOC Title', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<input type="text" 
											name="aniksmta_settings[title]" 
											value="<?php echo esc_attr( $settings['title'] ); ?>" 
											class="regular-text">
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Reading Time', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="aniksmta_settings[reading_time]" value="1" <?php checked( ! empty( $settings['reading_time'] ) ); ?>>
										<?php esc_html_e( 'Display estimated reading time next to the TOC title', 'anik-smart-table-of-contents' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Position', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<select name="aniksmta_settings[position]">
										<option value="before_content" <?php selected( $settings['position'], 'before_content' ); ?>>
											<?php esc_html_e( 'Before Content', 'anik-smart-table-of-contents' ); ?>
										</option>
										<option value="after_first_paragraph" <?php selected( $settings['position'], 'after_first_paragraph' ); ?>>
											<?php esc_html_e( 'After First Paragraph', 'anik-smart-table-of-contents' ); ?>
										</option>
										<option value="manual" <?php selected( $settings['position'], 'manual' ); ?>>
											<?php esc_html_e( 'Manual (Shortcode Only)', 'anik-smart-table-of-contents' ); ?>
										</option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Default State', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="aniksmta_settings[default_collapsed]" value="1" <?php checked( $settings['default_collapsed'] ); ?>>
										<?php esc_html_e( 'Collapsed by default', 'anik-smart-table-of-contents' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Show Numbers', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="aniksmta_settings[show_numbers]" value="1" <?php checked( $settings['show_numbers'] ); ?>>
										<?php esc_html_e( 'Display numbers before TOC items (1, 2, 3...)', 'anik-smart-table-of-contents' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Counter Format', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<select name="aniksmta_settings[counter_format]">
										<option value="decimal" <?php selected( $settings['counter_format'], 'decimal' ); ?>>
											<?php esc_html_e( 'Decimal (1, 2, 3...)', 'anik-smart-table-of-contents' ); ?>
										</option>
										<option value="roman" <?php selected( $settings['counter_format'], 'roman' ); ?>>
											<?php esc_html_e( 'Roman (I, II, III...)', 'anik-smart-table-of-contents' ); ?>
										</option>
										<option value="hierarchical" <?php selected( $settings['counter_format'], 'hierarchical' ); ?>>
											<?php esc_html_e( 'Hierarchical (1, 1.1, 1.1.1)', 'anik-smart-table-of-contents' ); ?>
										</option>
										<option value="none" <?php selected( $settings['counter_format'], 'none' ); ?>>
											<?php esc_html_e( 'No Numbers', 'anik-smart-table-of-contents' ); ?>
										</option>
									</select>
									<p class="description"><?php esc_html_e( 'Format for TOC item numbering (only applies when Show Numbers is enabled)', 'anik-smart-table-of-contents' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'TOC Theme', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<select name="aniksmta_settings[toc_theme]">
										<option value="default" <?php selected( $settings['toc_theme'], 'default' ); ?>>
											<?php esc_html_e( 'Default (Gray)', 'anik-smart-table-of-contents' ); ?>
										</option>
										<option value="light" <?php selected( $settings['toc_theme'], 'light' ); ?>>
											<?php esc_html_e( 'Light (White)', 'anik-smart-table-of-contents' ); ?>
										</option>
										<option value="dark" <?php selected( $settings['toc_theme'], 'dark' ); ?>>
											<?php esc_html_e( 'Dark', 'anik-smart-table-of-contents' ); ?>
										</option>
										<option value="minimal" <?php selected( $settings['toc_theme'], 'minimal' ); ?>>
											<?php esc_html_e( 'Minimal (Borderless)', 'anik-smart-table-of-contents' ); ?>
										</option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Toggle Icon Style', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<select name="aniksmta_settings[toggle_icon_style]">
										<option value="chevron" <?php selected( $settings['toggle_icon_style'], 'chevron' ); ?>>
											<?php esc_html_e( 'Chevron (Default)', 'anik-smart-table-of-contents' ); ?>
										</option>
										<option value="plus_minus" <?php selected( $settings['toggle_icon_style'], 'plus_minus' ); ?>>
											<?php esc_html_e( 'Plus / Minus', 'anik-smart-table-of-contents' ); ?>
										</option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Auto Dark Mode', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="aniksmta_settings[auto_dark_mode]" value="1" <?php checked( ! empty( $settings['auto_dark_mode'] ) ); ?>>
										<?php esc_html_e( 'Automatically switch to dark theme if the user\'s device is in dark mode', 'anik-smart-table-of-contents' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Copy Anchor Links', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="aniksmta_settings[copy_link]" value="1" <?php checked( ! empty( $settings['copy_link'] ) ); ?>>
										<?php esc_html_e( 'Show a copy link button next to TOC items', 'anik-smart-table-of-contents' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Theme Color', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<input type="text" 
											name="aniksmta_settings[theme_color]" 
											value="<?php echo esc_attr( $settings['theme_color'] ); ?>" 
											class="smart-toc-color-picker">
								</td>
							</tr>
						</table>
					</div>

					<!-- Behavior Settings -->
					<div class="smart-toc-card">
						<h2><?php esc_html_e( 'Behavior Settings', 'anik-smart-table-of-contents' ); ?></h2>
						
						<table class="form-table">
							<tr>
								<th scope="row"><?php esc_html_e( 'Smooth Scroll', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="aniksmta_settings[smooth_scroll]" value="1" <?php checked( $settings['smooth_scroll'] ); ?>>
										<?php esc_html_e( 'Enable smooth scrolling to headings', 'anik-smart-table-of-contents' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Highlight Active', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="aniksmta_settings[highlight_active]" value="1" <?php checked( $settings['highlight_active'] ); ?>>
										<?php esc_html_e( 'Highlight current section in TOC', 'anik-smart-table-of-contents' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Scroll Offset', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<input type="number" 
											name="aniksmta_settings[scroll_offset]" 
											value="<?php echo esc_attr( $settings['scroll_offset'] ); ?>" 
											min="0" 
											max="200"
											class="small-text"> px
									<p class="description"><?php esc_html_e( 'Offset from top when scrolling (useful for fixed headers)', 'anik-smart-table-of-contents' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Basic Sticky TOC', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="aniksmta_settings[sticky_toc]" value="1" <?php checked( ! empty( $settings['sticky_toc'] ) ); ?>>
										<?php esc_html_e( 'Keep the TOC visible while scrolling (basic mode)', 'anik-smart-table-of-contents' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Sticky Position', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<select name="aniksmta_settings[sticky_position]">
										<option value="inline" <?php selected( isset( $settings['sticky_position'] ) ? $settings['sticky_position'] : 'inline', 'inline' ); ?>>
											<?php esc_html_e( 'Inline (in content column)', 'anik-smart-table-of-contents' ); ?>
										</option>
										<option value="left" <?php selected( isset( $settings['sticky_position'] ) ? $settings['sticky_position'] : 'inline', 'left' ); ?>>
											<?php esc_html_e( 'Left side (desktop)', 'anik-smart-table-of-contents' ); ?>
										</option>
										<option value="right" <?php selected( isset( $settings['sticky_position'] ) ? $settings['sticky_position'] : 'inline', 'right' ); ?>>
											<?php esc_html_e( 'Right side (desktop)', 'anik-smart-table-of-contents' ); ?>
										</option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Sticky Width', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<input type="number"
											name="aniksmta_settings[sticky_width]"
											value="<?php echo esc_attr( isset( $settings['sticky_width'] ) ? $settings['sticky_width'] : 280 ); ?>"
											min="200"
											max="500"
											class="small-text"> px
									<p class="description"><?php esc_html_e( 'Applies to Left/Right sticky mode.', 'anik-smart-table-of-contents' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Sticky Offset', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<input type="number" 
											name="aniksmta_settings[sticky_offset]" 
											value="<?php echo esc_attr( isset( $settings['sticky_offset'] ) ? $settings['sticky_offset'] : 20 ); ?>" 
											min="0" 
											max="500"
											class="small-text"> px
									<p class="description"><?php esc_html_e( 'Space between the top of the screen and the sticky TOC (in pixels)', 'anik-smart-table-of-contents' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Collapsible Sections', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="aniksmta_settings[collapsible_sections]" value="1" <?php checked( ! empty( $settings['collapsible_sections'] ) ); ?>>
										<?php esc_html_e( 'Add toggle buttons to collapse/expand nested headings', 'anik-smart-table-of-contents' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Default Collapsed Sections', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="aniksmta_settings[sections_collapsed]" value="1" <?php checked( ! empty( $settings['sections_collapsed'] ) ); ?>>
										<?php esc_html_e( 'Start nested section groups in collapsed state', 'anik-smart-table-of-contents' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Reading Progress Bar', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="aniksmta_settings[reading_progress]" value="1" <?php checked( ! empty( $settings['reading_progress'] ) ); ?>>
										<?php esc_html_e( 'Display a basic reading progress bar at the top of the screen', 'anik-smart-table-of-contents' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Back to Top Button', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="aniksmta_settings[back_to_top]" value="1" <?php checked( ! empty( $settings['back_to_top'] ) ); ?>>
										<?php esc_html_e( 'Display a back-to-top button when scrolling down', 'anik-smart-table-of-contents' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Back to Top Icon', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<select name="aniksmta_settings[back_to_top_icon]">
										<option value="arrow" <?php selected( isset( $settings['back_to_top_icon'] ) ? $settings['back_to_top_icon'] : 'arrow', 'arrow' ); ?>><?php esc_html_e( 'Arrow', 'anik-smart-table-of-contents' ); ?></option>
										<option value="chevron" <?php selected( isset( $settings['back_to_top_icon'] ) ? $settings['back_to_top_icon'] : 'arrow', 'chevron' ); ?>><?php esc_html_e( 'Chevron', 'anik-smart-table-of-contents' ); ?></option>
										<option value="double" <?php selected( isset( $settings['back_to_top_icon'] ) ? $settings['back_to_top_icon'] : 'arrow', 'double' ); ?>><?php esc_html_e( 'Double Arrow', 'anik-smart-table-of-contents' ); ?></option>
										<option value="rocket" <?php selected( isset( $settings['back_to_top_icon'] ) ? $settings['back_to_top_icon'] : 'arrow', 'rocket' ); ?>><?php esc_html_e( 'Rocket', 'anik-smart-table-of-contents' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Back to Top Shape', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<select name="aniksmta_settings[back_to_top_style]">
										<option value="circle" <?php selected( isset( $settings['back_to_top_style'] ) ? $settings['back_to_top_style'] : 'circle', 'circle' ); ?>><?php esc_html_e( 'Circle', 'anik-smart-table-of-contents' ); ?></option>
										<option value="rounded" <?php selected( isset( $settings['back_to_top_style'] ) ? $settings['back_to_top_style'] : 'circle', 'rounded' ); ?>><?php esc_html_e( 'Rounded', 'anik-smart-table-of-contents' ); ?></option>
										<option value="pill" <?php selected( isset( $settings['back_to_top_style'] ) ? $settings['back_to_top_style'] : 'circle', 'pill' ); ?>><?php esc_html_e( 'Pill', 'anik-smart-table-of-contents' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Back to Top Colors', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<label style="display: inline-block; margin-right: 16px;">
										<?php esc_html_e( 'Background', 'anik-smart-table-of-contents' ); ?>
										<input type="text" name="aniksmta_settings[back_to_top_bg_color]" value="<?php echo esc_attr( isset( $settings['back_to_top_bg_color'] ) ? $settings['back_to_top_bg_color'] : '' ); ?>" class="smart-toc-color-picker">
									</label>
									<label style="display: inline-block;">
										<?php esc_html_e( 'Icon', 'anik-smart-table-of-contents' ); ?>
										<input type="text" name="aniksmta_settings[back_to_top_icon_color]" value="<?php echo esc_attr( isset( $settings['back_to_top_icon_color'] ) ? $settings['back_to_top_icon_color'] : '#ffffff' ); ?>" class="smart-toc-color-picker">
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Back to Top Device Visibility', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<label style="display: block; margin-bottom: 5px;">
										<input type="checkbox" name="aniksmta_settings[back_to_top_show_desktop]" value="1" <?php checked( ! isset( $settings['back_to_top_show_desktop'] ) || ! empty( $settings['back_to_top_show_desktop'] ) ); ?>>
										<?php esc_html_e( 'Show on Desktop', 'anik-smart-table-of-contents' ); ?>
									</label>
									<label style="display: block; margin-bottom: 5px;">
										<input type="checkbox" name="aniksmta_settings[back_to_top_show_tablet]" value="1" <?php checked( ! isset( $settings['back_to_top_show_tablet'] ) || ! empty( $settings['back_to_top_show_tablet'] ) ); ?>>
										<?php esc_html_e( 'Show on Tablet', 'anik-smart-table-of-contents' ); ?>
									</label>
									<label style="display: block;">
										<input type="checkbox" name="aniksmta_settings[back_to_top_show_mobile]" value="1" <?php checked( ! isset( $settings['back_to_top_show_mobile'] ) || ! empty( $settings['back_to_top_show_mobile'] ) ); ?>>
										<?php esc_html_e( 'Show on Mobile', 'anik-smart-table-of-contents' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Mobile TOC Modal', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="aniksmta_settings[mobile_toc_modal]" value="1" <?php checked( ! empty( $settings['mobile_toc_modal'] ) ); ?>>
										<?php esc_html_e( 'Show a floating TOC button and compact modal on mobile devices', 'anik-smart-table-of-contents' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'SEO Schema', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="aniksmta_settings[schema_enabled]" value="1" <?php checked( $settings['schema_enabled'] ); ?>>
										<?php esc_html_e( 'Output SiteNavigationElement JSON-LD schema for search engines', 'anik-smart-table-of-contents' ); ?>
									</label>
									<p class="description"><?php esc_html_e( 'Helps search engines understand your page structure and may enable "Jump to" links in results.', 'anik-smart-table-of-contents' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Floating Desktop TOC', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="aniksmta_settings[floating_desktop]" value="1" <?php checked( ! empty( $settings['floating_desktop'] ) ); ?>>
										<?php esc_html_e( 'Enable basic floating button on desktop', 'anik-smart-table-of-contents' ); ?>
									</label>
									<p class="description"><?php esc_html_e( 'Adds a persistent TOC button on the side of the screen for desktop users.', 'anik-smart-table-of-contents' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Floating Button Position', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<select name="aniksmta_settings[floating_toc_position]">
										<option value="left" <?php selected( isset( $settings['floating_toc_position'] ) ? $settings['floating_toc_position'] : 'right', 'left' ); ?>>
											<?php esc_html_e( 'Left', 'anik-smart-table-of-contents' ); ?>
										</option>
										<option value="right" <?php selected( isset( $settings['floating_toc_position'] ) ? $settings['floating_toc_position'] : 'right', 'right' ); ?>>
											<?php esc_html_e( 'Right', 'anik-smart-table-of-contents' ); ?>
										</option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Floating Button Style', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<select name="aniksmta_settings[floating_toc_style]">
										<option value="icon_only" <?php selected( isset( $settings['floating_toc_style'] ) ? $settings['floating_toc_style'] : 'icon_text', 'icon_only' ); ?>>
											<?php esc_html_e( 'Icon Only', 'anik-smart-table-of-contents' ); ?>
										</option>
										<option value="icon_text" <?php selected( isset( $settings['floating_toc_style'] ) ? $settings['floating_toc_style'] : 'icon_text', 'icon_text' ); ?>>
											<?php esc_html_e( 'Icon + Text', 'anik-smart-table-of-contents' ); ?>
										</option>
										<option value="icon_counter" <?php selected( isset( $settings['floating_toc_style'] ) ? $settings['floating_toc_style'] : 'icon_text', 'icon_counter' ); ?>>
											<?php esc_html_e( 'Icon + Counter', 'anik-smart-table-of-contents' ); ?>
										</option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Floating Theme', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<select name="aniksmta_settings[floating_toc_theme]">
										<option value="default" <?php selected( isset( $settings['floating_toc_theme'] ) ? $settings['floating_toc_theme'] : 'default', 'default' ); ?>>
											<?php esc_html_e( 'Default', 'anik-smart-table-of-contents' ); ?>
										</option>
										<option value="dark" <?php selected( isset( $settings['floating_toc_theme'] ) ? $settings['floating_toc_theme'] : 'default', 'dark' ); ?>>
											<?php esc_html_e( 'Dark', 'anik-smart-table-of-contents' ); ?>
										</option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Floating Panel Width', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<input type="number"
											name="aniksmta_settings[floating_toc_panel_width]"
											value="<?php echo esc_attr( isset( $settings['floating_toc_panel_width'] ) ? $settings['floating_toc_panel_width'] : 320 ); ?>"
											min="280"
											max="450"
											step="10"
											class="small-text"> px
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Floating Auto-close', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="aniksmta_settings[floating_toc_auto_close]" value="1" <?php checked( ! isset( $settings['floating_toc_auto_close'] ) || ! empty( $settings['floating_toc_auto_close'] ) ); ?>>
										<?php esc_html_e( 'Automatically close the floating panel after clicking a TOC link', 'anik-smart-table-of-contents' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Floating Progress Bar', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="aniksmta_settings[floating_toc_show_progress]" value="1" <?php checked( ! isset( $settings['floating_toc_show_progress'] ) || ! empty( $settings['floating_toc_show_progress'] ) ); ?>>
										<?php esc_html_e( 'Show scroll progress bar in the floating panel header', 'anik-smart-table-of-contents' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Floating Default Expanded', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="aniksmta_settings[floating_toc_default_expanded]" value="1" <?php checked( ! isset( $settings['floating_toc_default_expanded'] ) || ! empty( $settings['floating_toc_default_expanded'] ) ); ?>>
										<?php esc_html_e( 'Auto-open floating panel the first time it appears while scrolling', 'anik-smart-table-of-contents' ); ?>
									</label>
								</td>
							</tr>
						</table>
					</div>

					<!-- Advanced Settings -->
					<div class="smart-toc-card">
						<h2><?php esc_html_e( 'Advanced Settings', 'anik-smart-table-of-contents' ); ?></h2>
						
						<table class="form-table">
							<tr>
								<th scope="row"><?php esc_html_e( 'Dynamic Content Support', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="aniksmta_settings[dynamic_content]" value="1" <?php checked( ! empty( $settings['dynamic_content'] ) ); ?>>
										<?php esc_html_e( 'Auto-detect content changes (useful for AJAX, Infinite Scroll, and Page Builders)', 'anik-smart-table-of-contents' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Lazy Load Initialization', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="aniksmta_settings[lazy_load_toc]" value="1" <?php checked( ! empty( $settings['lazy_load_toc'] ) ); ?>>
										<?php esc_html_e( 'Delay TOC scripts until it enters the viewport (Performance boost)', 'anik-smart-table-of-contents' ); ?>
									</label>
								</td>
							</tr>
						</table>
					</div>

					<!-- Pro Features Preview -->
					<div class="smart-toc-card smart-toc-pro-features">
						<h2><span aria-hidden="true">&#10024;</span> <?php esc_html_e( 'Pro Features', 'anik-smart-table-of-contents' ); ?></h2>
						<ul class="pro-features-list">
							<li><span aria-hidden="true">&#128274;</span> <?php esc_html_e( 'Elementor Widget Integration (Pro Only)', 'anik-smart-table-of-contents' ); ?></li>
							<li><span aria-hidden="true">&#128274;</span> <?php esc_html_e( 'Floating TOC Automation (Auto-close, Panel Progress, Dismiss)', 'anik-smart-table-of-contents' ); ?></li>
							<li><span aria-hidden="true">&#128274;</span> <?php esc_html_e( 'Advanced Mobile UX (Scroll Trigger, Swipe Close, Safe Areas)', 'anik-smart-table-of-contents' ); ?></li>
							<li><span aria-hidden="true">&#128274;</span> <?php esc_html_e( 'Advanced Reading Progress and Reading Time Controls', 'anik-smart-table-of-contents' ); ?></li>
							<li><span aria-hidden="true">&#128274;</span> <?php esc_html_e( 'Advanced Display Conditions and Page Rules', 'anik-smart-table-of-contents' ); ?></li>
							<li><span aria-hidden="true">&#128274;</span> <?php esc_html_e( '6 Premium Theme Presets (Glass and Gradient Included)', 'anik-smart-table-of-contents' ); ?></li>
							<li><span aria-hidden="true">&#128274;</span> <?php esc_html_e( 'Settings Export and Import', 'anik-smart-table-of-contents' ); ?></li>
							<li><span aria-hidden="true">&#128274;</span> <?php esc_html_e( 'Advanced SEO and Accessibility Controls', 'anik-smart-table-of-contents' ); ?></li>
							<li><span aria-hidden="true">&#128274;</span> <?php esc_html_e( 'Performance Optimization Suite (Lazy Load, Defer, Minified Assets)', 'anik-smart-table-of-contents' ); ?></li>
							<li><span aria-hidden="true">&#128274;</span> <?php esc_html_e( 'Universal Compatibility (Elementor, Gutenberg, RTL, Multisite)', 'anik-smart-table-of-contents' ); ?></li>
							<li><span aria-hidden="true">&#128274;</span> <?php esc_html_e( 'Custom CSS and Deep Style Controls', 'anik-smart-table-of-contents' ); ?></li>
							<li><span aria-hidden="true">&#128274;</span> <?php esc_html_e( 'Priority Support', 'anik-smart-table-of-contents' ); ?></li>
						</ul>
						<a href="https://smallseoengine.com/plugins/smart-table-of-contents/" target="_blank" class="button button-primary"><?php esc_html_e( 'Unlock All Features', 'anik-smart-table-of-contents' ); ?></a>
					</div>
				</div>

					<div class="smart-toc-settings-actions">
						<?php
						submit_button();

						$reset_confirm = esc_js( __( 'Are you sure you want to reset all settings to default values?', 'anik-smart-table-of-contents' ) );
						submit_button(
							__( 'Reset to Defaults', 'anik-smart-table-of-contents' ),
							'secondary',
							'aniksmta_settings[reset_defaults]',
							false,
							array(
								'id'      => 'aniksmta-reset-settings',
								'onclick' => "return confirm('{$reset_confirm}');",
							)
						);
						?>
						<p class="description"><?php esc_html_e( 'This will restore all plugin settings to default values.', 'anik-smart-table-of-contents' ); ?></p>
					</div>
				</form>

			<!-- Shortcode Info -->
			<div class="smart-toc-card">
				<h2><?php esc_html_e( 'Shortcode Usage', 'anik-smart-table-of-contents' ); ?></h2>
				<p><?php esc_html_e( 'Use the following shortcode to manually place the TOC:', 'anik-smart-table-of-contents' ); ?></p>
				<code>[aniksmta_toc]</code>
				<p style="margin-top: 10px;"><?php esc_html_e( 'With custom title:', 'anik-smart-table-of-contents' ); ?></p>
				<code>[aniksmta_toc title="In This Article"]</code>
				<p style="margin-top: 10px;"><?php esc_html_e( 'Collapsed by default:', 'anik-smart-table-of-contents' ); ?></p>
				<code>[aniksmta_toc collapsed="true"]</code>
			</div>

			<?php elseif ( 'documentation' === $active_tab ) : ?>
			<!-- Documentation Tab -->
			<div class="smart-toc-documentation">
				<?php $this->render_documentation(); ?>
			</div>

			<?php elseif ( 'support' === $active_tab ) : ?>
			<!-- Help & Support Tab -->
			<div class="smart-toc-support">
				<?php $this->render_support(); ?>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render documentation content
	 */
	private function render_documentation() {
		?>
		<div class="smart-toc-docs-container">
			<!-- Quick Start -->
			<div class="smart-toc-card smart-toc-docs-card">
				<h2><span class="dashicons dashicons-controls-play"></span> <?php esc_html_e( 'Quick Start', 'anik-smart-table-of-contents' ); ?></h2>
				<ol class="smart-toc-docs-list">
					<li><?php esc_html_e( 'Activate the plugin - TOC is enabled by default', 'anik-smart-table-of-contents' ); ?></li>
					<li><?php esc_html_e( 'Create or edit a post/page with at least 2 headings (H2-H6)', 'anik-smart-table-of-contents' ); ?></li>
					<li><?php esc_html_e( 'View your post - The TOC appears automatically before your content', 'anik-smart-table-of-contents' ); ?></li>
					<li><?php esc_html_e( 'Customize via Settings tab as needed', 'anik-smart-table-of-contents' ); ?></li>
				</ol>
			</div>

			<div class="smart-toc-docs-grid">
				<!-- Settings Reference -->
				<div class="smart-toc-card smart-toc-docs-card">
					<h2><span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Settings Reference', 'anik-smart-table-of-contents' ); ?></h2>
					
					<h3><?php esc_html_e( 'General Settings', 'anik-smart-table-of-contents' ); ?></h3>
					<table class="smart-toc-docs-table">
						<tr>
							<td><strong><?php esc_html_e( 'Enable TOC', 'anik-smart-table-of-contents' ); ?></strong></td>
							<td><?php esc_html_e( 'Master switch to enable/disable TOC globally', 'anik-smart-table-of-contents' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Post Types', 'anik-smart-table-of-contents' ); ?></strong></td>
							<td><?php esc_html_e( 'Select which post types display the TOC', 'anik-smart-table-of-contents' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Minimum Headings', 'anik-smart-table-of-contents' ); ?></strong></td>
							<td><?php esc_html_e( 'Minimum headings required for TOC to appear (default: 2)', 'anik-smart-table-of-contents' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Heading Levels', 'anik-smart-table-of-contents' ); ?></strong></td>
							<td><?php esc_html_e( 'Choose which heading levels (H2-H6) to include', 'anik-smart-table-of-contents' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Exclude Headings', 'anik-smart-table-of-contents' ); ?></strong></td>
							<td><?php esc_html_e( 'Comma-separated heading texts to exclude globally (partial match)', 'anik-smart-table-of-contents' ); ?></td>
						</tr>
					</table>

					<h3><?php esc_html_e( 'Display Settings', 'anik-smart-table-of-contents' ); ?></h3>
					<table class="smart-toc-docs-table">
						<tr>
							<td><strong><?php esc_html_e( 'TOC Title', 'anik-smart-table-of-contents' ); ?></strong></td>
							<td><?php esc_html_e( 'The heading text displayed at the top of the TOC', 'anik-smart-table-of-contents' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Position', 'anik-smart-table-of-contents' ); ?></strong></td>
							<td><?php esc_html_e( 'Before Content, After First Paragraph, or Manual (Shortcode)', 'anik-smart-table-of-contents' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Default State', 'anik-smart-table-of-contents' ); ?></strong></td>
							<td><?php esc_html_e( 'Whether TOC starts expanded or collapsed', 'anik-smart-table-of-contents' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Show Numbers', 'anik-smart-table-of-contents' ); ?></strong></td>
							<td><?php esc_html_e( 'Display sequential numbers before each item', 'anik-smart-table-of-contents' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Counter Format', 'anik-smart-table-of-contents' ); ?></strong></td>
							<td><?php esc_html_e( 'Decimal, Roman, Hierarchical (1, 1.1, 1.1.1), or None', 'anik-smart-table-of-contents' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'TOC Theme', 'anik-smart-table-of-contents' ); ?></strong></td>
							<td><?php esc_html_e( 'Choose Default (Gray), Light (White), Dark, or Minimal (Borderless)', 'anik-smart-table-of-contents' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Theme Color', 'anik-smart-table-of-contents' ); ?></strong></td>
							<td><?php esc_html_e( 'Primary color for links and active states', 'anik-smart-table-of-contents' ); ?></td>
						</tr>
					</table>

					<h3><?php esc_html_e( 'Behavior Settings', 'anik-smart-table-of-contents' ); ?></h3>
					<table class="smart-toc-docs-table">
						<tr>
							<td><strong><?php esc_html_e( 'Smooth Scroll', 'anik-smart-table-of-contents' ); ?></strong></td>
							<td><?php esc_html_e( 'Animated scrolling when clicking TOC links', 'anik-smart-table-of-contents' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Highlight Active', 'anik-smart-table-of-contents' ); ?></strong></td>
							<td><?php esc_html_e( 'Highlights current section as users scroll', 'anik-smart-table-of-contents' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Scroll Offset', 'anik-smart-table-of-contents' ); ?></strong></td>
							<td><?php esc_html_e( 'Offset for fixed headers (0-200px)', 'anik-smart-table-of-contents' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'SEO Schema', 'anik-smart-table-of-contents' ); ?></strong></td>
							<td><?php esc_html_e( 'Output SiteNavigationElement JSON-LD schema for search engines', 'anik-smart-table-of-contents' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Copy Heading Link', 'anik-smart-table-of-contents' ); ?></strong></td>
							<td><?php esc_html_e( 'Shows a copy icon on TOC items to copy direct anchor links', 'anik-smart-table-of-contents' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Sticky TOC', 'anik-smart-table-of-contents' ); ?></strong></td>
							<td><?php esc_html_e( 'Make TOC sticky with Left/Right/Inline position, width, and top offset controls', 'anik-smart-table-of-contents' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Collapsible Sections', 'anik-smart-table-of-contents' ); ?></strong></td>
							<td><?php esc_html_e( 'Enable nested heading section toggles and optionally start collapsed', 'anik-smart-table-of-contents' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Reading Progress', 'anik-smart-table-of-contents' ); ?></strong></td>
							<td><?php esc_html_e( 'Shows a progress indicator while reading the content', 'anik-smart-table-of-contents' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Back to Top', 'anik-smart-table-of-contents' ); ?></strong></td>
							<td><?php esc_html_e( 'Adds a customizable back-to-top button (icon, shape, colors, and device visibility)', 'anik-smart-table-of-contents' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Floating TOC (Desktop)', 'anik-smart-table-of-contents' ); ?></strong></td>
							<td><?php esc_html_e( 'Floating trigger + panel with position, style, theme, width, auto-close, progress bar, and default expanded options', 'anik-smart-table-of-contents' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Mobile TOC Modal', 'anik-smart-table-of-contents' ); ?></strong></td>
							<td><?php esc_html_e( 'Display TOC as a modal drawer on mobile devices', 'anik-smart-table-of-contents' ); ?></td>
						</tr>
						<tr>
							<td><strong><?php esc_html_e( 'Performance', 'anik-smart-table-of-contents' ); ?></strong></td>
							<td><?php esc_html_e( 'Dynamic content observer and lazy TOC initialization for better compatibility and speed', 'anik-smart-table-of-contents' ); ?></td>
						</tr>
					</table>
				</div>

				<!-- Shortcode Usage -->
				<div class="smart-toc-card smart-toc-docs-card">
					<h2><span class="dashicons dashicons-shortcode"></span> <?php esc_html_e( 'Shortcode Usage', 'anik-smart-table-of-contents' ); ?></h2>
					
					<p><?php esc_html_e( 'Use the shortcode for manual TOC placement:', 'anik-smart-table-of-contents' ); ?></p>
					
					<h3><?php esc_html_e( 'Basic Usage', 'anik-smart-table-of-contents' ); ?></h3>
					<div class="smart-toc-code-block">
						<code>[aniksmta_toc]</code>
					</div>
					
					<h3><?php esc_html_e( 'Custom Title', 'anik-smart-table-of-contents' ); ?></h3>
					<div class="smart-toc-code-block">
						<code>[aniksmta_toc title="In This Article"]</code>
					</div>
					
					<h3><?php esc_html_e( 'Collapsed by Default', 'anik-smart-table-of-contents' ); ?></h3>
					<div class="smart-toc-code-block">
						<code>[aniksmta_toc collapsed="true"]</code>
					</div>
					
					<h3><?php esc_html_e( 'Combined Attributes', 'anik-smart-table-of-contents' ); ?></h3>
					<div class="smart-toc-code-block">
						<code>[aniksmta_toc title="Quick Navigation" collapsed="false"]</code>
					</div>

					<div class="smart-toc-docs-tip">
						<strong>💡 <?php esc_html_e( 'Tip:', 'anik-smart-table-of-contents' ); ?></strong>
						<?php esc_html_e( 'Set Position to "Manual (Shortcode Only)" in settings to prevent automatic insertion.', 'anik-smart-table-of-contents' ); ?>
					</div>
				</div>
			</div>

			<div class="smart-toc-docs-grid">
				<!-- Per-Post Controls -->
				<div class="smart-toc-card smart-toc-docs-card">
					<h2><span class="dashicons dashicons-admin-post"></span> <?php esc_html_e( 'Per-Post Controls', 'anik-smart-table-of-contents' ); ?></h2>
					<p><?php esc_html_e( 'Control TOC on individual posts/pages via the "Smart TOC" meta box:', 'anik-smart-table-of-contents' ); ?></p>
					<ol class="smart-toc-docs-list">
						<li><?php esc_html_e( 'Edit the post/page in WordPress admin', 'anik-smart-table-of-contents' ); ?></li>
						<li><?php esc_html_e( 'Find the "Smart TOC" meta box in the sidebar', 'anik-smart-table-of-contents' ); ?></li>
						<li><?php esc_html_e( 'Check "Disable TOC" to hide the TOC on this post', 'anik-smart-table-of-contents' ); ?></li>
						<li><?php esc_html_e( 'Use "Heading Levels Override" to choose which headings appear in the TOC for this post only', 'anik-smart-table-of-contents' ); ?></li>
						<li><?php esc_html_e( 'Save/Update the post', 'anik-smart-table-of-contents' ); ?></li>
					</ol>
					<p><em><?php esc_html_e( 'Useful for landing pages, short posts, or custom layouts. Leave heading checkboxes unchecked to use global settings.', 'anik-smart-table-of-contents' ); ?></em></p>
				</div>

				<!-- Excluding Headings -->
				<div class="smart-toc-card smart-toc-docs-card">
					<h2><span class="dashicons dashicons-hidden"></span> <?php esc_html_e( 'Excluding Headings', 'anik-smart-table-of-contents' ); ?></h2>

					<h3><?php esc_html_e( 'Method 1: Global Exclusion by Text', 'anik-smart-table-of-contents' ); ?></h3>
					<p><?php esc_html_e( 'Go to Settings → Smart TOC → General Settings → Exclude Headings. Enter a comma-separated list of heading texts to exclude. Partial matches are supported (e.g., "Related" will exclude "Related Posts" and "Related Articles").', 'anik-smart-table-of-contents' ); ?></p>

					<h3><?php esc_html_e( 'Method 2: CSS Class per Heading', 'anik-smart-table-of-contents' ); ?></h3>
					<p><?php esc_html_e( 'Add the "no-toc" CSS class to exclude specific headings:', 'anik-smart-table-of-contents' ); ?></p>

					<h4><?php esc_html_e( 'Block Editor (Gutenberg)', 'anik-smart-table-of-contents' ); ?></h4>
					<ol class="smart-toc-docs-list">
						<li><?php esc_html_e( 'Select the heading block', 'anik-smart-table-of-contents' ); ?></li>
						<li><?php esc_html_e( 'Open Block Settings (right sidebar)', 'anik-smart-table-of-contents' ); ?></li>
						<li><?php esc_html_e( 'Expand "Advanced" section', 'anik-smart-table-of-contents' ); ?></li>
						<li><?php esc_html_e( 'Add "no-toc" to Additional CSS class(es)', 'anik-smart-table-of-contents' ); ?></li>
					</ol>

					<h4><?php esc_html_e( 'Classic Editor (HTML)', 'anik-smart-table-of-contents' ); ?></h4>
					<div class="smart-toc-code-block">
						<code>&lt;h2 class="no-toc"&gt;<?php esc_html_e( 'Hidden Heading', 'anik-smart-table-of-contents' ); ?>&lt;/h2&gt;</code>
					</div>
				</div>
			</div>

			<!-- Troubleshooting -->
			<div class="smart-toc-card smart-toc-docs-card">
				<h2><span class="dashicons dashicons-sos"></span> <?php esc_html_e( 'Troubleshooting', 'anik-smart-table-of-contents' ); ?></h2>
				
				<div class="smart-toc-docs-grid smart-toc-troubleshoot-grid">
					<div class="smart-toc-troubleshoot-item">
						<h3><?php esc_html_e( 'TOC Not Appearing', 'anik-smart-table-of-contents' ); ?></h3>
						<ul>
							<li><?php esc_html_e( 'Check minimum headings requirement', 'anik-smart-table-of-contents' ); ?></li>
							<li><?php esc_html_e( 'Verify post type is enabled', 'anik-smart-table-of-contents' ); ?></li>
							<li><?php esc_html_e( 'Check per-post disable setting', 'anik-smart-table-of-contents' ); ?></li>
							<li><?php esc_html_e( 'Confirm global toggle is enabled', 'anik-smart-table-of-contents' ); ?></li>
							<li><?php esc_html_e( 'TOC is disabled on front/home pages', 'anik-smart-table-of-contents' ); ?></li>
						</ul>
					</div>
					<div class="smart-toc-troubleshoot-item">
						<h3><?php esc_html_e( 'Headings Hidden Behind Header', 'anik-smart-table-of-contents' ); ?></h3>
						<p><?php esc_html_e( 'Increase the Scroll Offset value in Behavior Settings to match your fixed header height.', 'anik-smart-table-of-contents' ); ?></p>
					</div>
					<div class="smart-toc-troubleshoot-item">
						<h3><?php esc_html_e( 'Smooth Scroll Not Working', 'anik-smart-table-of-contents' ); ?></h3>
						<ul>
							<li><?php esc_html_e( 'Check for plugin/theme conflicts', 'anik-smart-table-of-contents' ); ?></li>
							<li><?php esc_html_e( 'Verify Smooth Scroll is enabled', 'anik-smart-table-of-contents' ); ?></li>
							<li><?php esc_html_e( 'Check browser console for errors', 'anik-smart-table-of-contents' ); ?></li>
						</ul>
					</div>
					<div class="smart-toc-troubleshoot-item">
						<h3><?php esc_html_e( 'Active Highlight Not Updating', 'anik-smart-table-of-contents' ); ?></h3>
						<ul>
							<li><?php esc_html_e( 'Ensure Highlight Active is enabled', 'anik-smart-table-of-contents' ); ?></li>
							<li><?php esc_html_e( 'Check for JavaScript conflicts', 'anik-smart-table-of-contents' ); ?></li>
							<li><?php esc_html_e( 'Try scrolling slowly through content', 'anik-smart-table-of-contents' ); ?></li>
						</ul>
					</div>
				</div>
			</div>

			<!-- CSS Customization -->
			<div class="smart-toc-card smart-toc-docs-card">
				<h2><span class="dashicons dashicons-art"></span> <?php esc_html_e( 'CSS Customization', 'anik-smart-table-of-contents' ); ?></h2>
				<p><?php esc_html_e( 'Add custom CSS via Appearance → Customize → Additional CSS:', 'anik-smart-table-of-contents' ); ?></p>
				
				<h3><?php esc_html_e( 'CSS Classes Reference', 'anik-smart-table-of-contents' ); ?></h3>
				<table class="smart-toc-docs-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Class', 'anik-smart-table-of-contents' ); ?></th>
							<th><?php esc_html_e( 'Element', 'anik-smart-table-of-contents' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr><td><code>.smart-toc</code></td><td><?php esc_html_e( 'Main container', 'anik-smart-table-of-contents' ); ?></td></tr>
						<tr><td><code>.smart-toc-header</code></td><td><?php esc_html_e( 'Header with title and toggle', 'anik-smart-table-of-contents' ); ?></td></tr>
						<tr><td><code>.smart-toc-title</code></td><td><?php esc_html_e( 'Title text', 'anik-smart-table-of-contents' ); ?></td></tr>
						<tr><td><code>.smart-toc-toggle</code></td><td><?php esc_html_e( 'Collapse/expand button', 'anik-smart-table-of-contents' ); ?></td></tr>
						<tr><td><code>.smart-toc-body</code></td><td><?php esc_html_e( 'Content area', 'anik-smart-table-of-contents' ); ?></td></tr>
						<tr><td><code>.smart-toc-list</code></td><td><?php esc_html_e( 'The &lt;ul&gt; list', 'anik-smart-table-of-contents' ); ?></td></tr>
						<tr><td><code>.toc-item</code></td><td><?php esc_html_e( 'Each &lt;li&gt; item', 'anik-smart-table-of-contents' ); ?></td></tr>
						<tr><td><code>.toc-level-2</code> to <code>.toc-level-6</code></td><td><?php esc_html_e( 'Heading level classes', 'anik-smart-table-of-contents' ); ?></td></tr>
						<tr><td><code>.collapsed</code></td><td><?php esc_html_e( 'Applied when TOC is collapsed', 'anik-smart-table-of-contents' ); ?></td></tr>
						<tr><td><code>.active</code></td><td><?php esc_html_e( 'Applied to current section link', 'anik-smart-table-of-contents' ); ?></td></tr>
						<tr><td><code>.toc-theme-default</code></td><td><?php esc_html_e( 'Default gray theme preset', 'anik-smart-table-of-contents' ); ?></td></tr>
						<tr><td><code>.toc-theme-light</code></td><td><?php esc_html_e( 'Light theme preset', 'anik-smart-table-of-contents' ); ?></td></tr>
						<tr><td><code>.toc-theme-dark</code></td><td><?php esc_html_e( 'Dark theme preset', 'anik-smart-table-of-contents' ); ?></td></tr>
						<tr><td><code>.toc-theme-minimal</code></td><td><?php esc_html_e( 'Minimal theme preset', 'anik-smart-table-of-contents' ); ?></td></tr>
						<tr><td><code>.toc-item-inner</code></td><td><?php esc_html_e( 'Inline row wrapper for toggle/link/copy button', 'anik-smart-table-of-contents' ); ?></td></tr>
						<tr><td><code>.smart-toc-copy-link</code></td><td><?php esc_html_e( 'Copy anchor icon button', 'anik-smart-table-of-contents' ); ?></td></tr>
						<tr><td><code>.smart-toc-floating</code></td><td><?php esc_html_e( 'Floating TOC desktop container', 'anik-smart-table-of-contents' ); ?></td></tr>
						<tr><td><code>.smart-toc-back-to-top</code></td><td><?php esc_html_e( 'Back-to-top button container', 'anik-smart-table-of-contents' ); ?></td></tr>
					</tbody>
				</table>
			</div>

			<!-- FAQ -->
			<div class="smart-toc-card smart-toc-docs-card">
				<h2><span class="dashicons dashicons-editor-help"></span> <?php esc_html_e( 'Frequently Asked Questions', 'anik-smart-table-of-contents' ); ?></h2>
				
				<div class="smart-toc-faq-item">
					<h3><?php esc_html_e( 'Does it work with page builders?', 'anik-smart-table-of-contents' ); ?></h3>
					<p><?php esc_html_e( 'Yes! The TOC parses final rendered content, so it works with Elementor, Divi, Beaver Builder, and most page builders.', 'anik-smart-table-of-contents' ); ?></p>
				</div>
				
				<div class="smart-toc-faq-item">
					<h3><?php esc_html_e( 'Is it SEO-friendly?', 'anik-smart-table-of-contents' ); ?></h3>
					<p><?php esc_html_e( 'Yes! Uses semantic HTML with proper ARIA labels, and optionally outputs SiteNavigationElement JSON-LD schema. Enable SEO Schema in Behavior Settings to help search engines understand your page structure.', 'anik-smart-table-of-contents' ); ?></p>
				</div>
				
				<div class="smart-toc-faq-item">
					<h3><?php esc_html_e( 'Can I translate it?', 'anik-smart-table-of-contents' ); ?></h3>
					<p><?php esc_html_e( 'Yes, fully translatable. Use Loco Translate, WPML, or add translations to /languages/ folder. Text domain: anik-smart-table-of-contents', 'anik-smart-table-of-contents' ); ?></p>
				</div>
				
				<div class="smart-toc-faq-item">
					<h3><?php esc_html_e( 'Can I have multiple TOCs on one page?', 'anik-smart-table-of-contents' ); ?></h3>
					<p><?php esc_html_e( 'Yes, using the shortcode. However, this is generally not recommended for user experience.', 'anik-smart-table-of-contents' ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render help & support content
	 */
	private function render_support() {
		global $wp_version;
		?>
		<div class="smart-toc-support-container">
			<!-- Support Links -->
			<div class="smart-toc-docs-grid">
				<div class="smart-toc-card smart-toc-support-card">
					<div class="support-icon">💬</div>
					<h3><?php esc_html_e( 'Support Forum', 'anik-smart-table-of-contents' ); ?></h3>
					<p><?php esc_html_e( 'Get help from the community and our support team on WordPress.org.', 'anik-smart-table-of-contents' ); ?></p>
					<a href="https://wordpress.org/support/plugin/anik-smart-table-of-contents/" target="_blank" class="button">
						<?php esc_html_e( 'Visit Support Forum', 'anik-smart-table-of-contents' ); ?>
					</a>
				</div>

					<div class="smart-toc-card smart-toc-support-card">
						<div class="support-icon">🐛</div>
					<h3><?php esc_html_e( 'Report a Bug', 'anik-smart-table-of-contents' ); ?></h3>
					<p><?php esc_html_e( 'Found a bug? Report it on GitHub and help us improve the plugin.', 'anik-smart-table-of-contents' ); ?></p>
					<a href="https://github.com/wordpress-plugins-project/smart-table-of-content-free-plugin/issues" target="_blank" class="button">
						<?php esc_html_e( 'Report Bug', 'anik-smart-table-of-contents' ); ?>
					</a>
				</div>

				<div class="smart-toc-card smart-toc-support-card">
					<div class="support-icon">✉️</div>
					<h3><?php esc_html_e( 'Contact Us', 'anik-smart-table-of-contents' ); ?></h3>
					<p><?php esc_html_e( 'Have questions or suggestions? We would love to hear from you.', 'anik-smart-table-of-contents' ); ?></p>
					<a href="https://smallseoengine.com/contact/" target="_blank" class="button">
						<?php esc_html_e( 'Contact Us', 'anik-smart-table-of-contents' ); ?>
					</a>
				</div>
			</div>

			<!-- Rate Us -->
			<div class="smart-toc-card smart-toc-rate-card">
				<div class="rate-content">
					<div class="rate-icon">⭐</div>
					<div class="rate-text">
						<h3><?php esc_html_e( 'Enjoying Smart Table of Contents?', 'anik-smart-table-of-contents' ); ?></h3>
						<p><?php esc_html_e( 'Please consider leaving a 5-star review. Your feedback helps us improve and motivates us to add more features!', 'anik-smart-table-of-contents' ); ?></p>
					</div>
					<a href="https://wordpress.org/support/plugin/anik-smart-table-of-contents/reviews/#new-post" target="_blank" class="button button-primary">
						⭐ <?php esc_html_e( 'Leave a Review', 'anik-smart-table-of-contents' ); ?>
					</a>
				</div>
			</div>

			<!-- Pro Upgrade -->
			<div class="smart-toc-card smart-toc-upgrade-card">
				<h3>🚀 <?php esc_html_e( 'Need More Features?', 'anik-smart-table-of-contents' ); ?></h3>
				<p><?php esc_html_e( 'Upgrade to Smart TOC Pro for advanced features and priority support:', 'anik-smart-table-of-contents' ); ?></p>
				<ul class="pro-features-list">
					<li>🔒 <?php esc_html_e( 'Elementor Widget Integration (Pro Only)', 'anik-smart-table-of-contents' ); ?></li>
					<li>📌 <?php esc_html_e( 'Floating TOC Automation + Advanced Sticky Controls', 'anik-smart-table-of-contents' ); ?></li>
					<li>📱 <?php esc_html_e( 'Advanced Mobile UX (Scroll Trigger, Swipe Close)', 'anik-smart-table-of-contents' ); ?></li>
					<li>🎨 <?php esc_html_e( 'Premium Theme Presets (Including Glass and Gradient)', 'anik-smart-table-of-contents' ); ?></li>
					<li>⚙️ <?php esc_html_e( 'Export/Import, SEO, Accessibility, and Performance Controls', 'anik-smart-table-of-contents' ); ?></li>
					<li>🔒 <?php esc_html_e( 'Priority Support', 'anik-smart-table-of-contents' ); ?></li>
				</ul>
				<a href="https://smallseoengine.com/plugins/smart-table-of-contents/" target="_blank" class="button button-primary button-hero">
					<?php esc_html_e( 'Upgrade to Pro', 'anik-smart-table-of-contents' ); ?>
				</a>
			</div>

			<!-- System Info -->
			<div class="smart-toc-card smart-toc-system-info">
				<h3><span class="dashicons dashicons-info"></span> <?php esc_html_e( 'System Information', 'anik-smart-table-of-contents' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Copy this information when requesting support.', 'anik-smart-table-of-contents' ); ?></p>
				<table class="smart-toc-system-table">
					<tr>
						<td><strong><?php esc_html_e( 'Plugin Version', 'anik-smart-table-of-contents' ); ?></strong></td>
						<td><?php echo esc_html( ANIKSMTA_VERSION ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'WordPress Version', 'anik-smart-table-of-contents' ); ?></strong></td>
						<td><?php echo esc_html( $wp_version ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'PHP Version', 'anik-smart-table-of-contents' ); ?></strong></td>
						<td><?php echo esc_html( PHP_VERSION ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Active Theme', 'anik-smart-table-of-contents' ); ?></strong></td>
						<td><?php echo esc_html( wp_get_theme()->get( 'Name' ) . ' (' . wp_get_theme()->get( 'Version' ) . ')' ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'TOC Status', 'anik-smart-table-of-contents' ); ?></strong></td>
						<td><?php echo $this->settings->get( 'enabled' ) ? esc_html__( 'Enabled', 'anik-smart-table-of-contents' ) : esc_html__( 'Disabled', 'anik-smart-table-of-contents' ); ?></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Enabled Post Types', 'anik-smart-table-of-contents' ); ?></strong></td>
						<td><?php echo esc_html( implode( ', ', $this->settings->get( 'post_types' ) ) ); ?></td>
					</tr>
				</table>
				<button type="button" class="button smart-toc-copy-info">
					📋 <?php esc_html_e( 'Copy System Info', 'anik-smart-table-of-contents' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Add meta box
	 */
	public function add_meta_box() {
		$post_types = $this->settings->get( 'post_types' );

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'aniksmta_meta_box',
				__( 'Smart TOC', 'anik-smart-table-of-contents' ),
				array( $this, 'render_meta_box' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render meta box
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'aniksmta_meta_box', 'aniksmta_meta_box_nonce' );

		$disabled       = get_post_meta( $post->ID, '_aniksmta_disable', true );
		$heading_levels = get_post_meta( $post->ID, '_aniksmta_heading_levels', true );
		?>
		<label>
			<input type="checkbox" name="aniksmta_disable" value="1" <?php checked( $disabled ); ?>>
			<?php esc_html_e( 'Disable TOC for this post', 'anik-smart-table-of-contents' ); ?>
		</label>

		<hr style="margin: 12px 0;">
		<p style="margin-bottom: 6px;"><strong><?php esc_html_e( 'Heading Levels Override', 'anik-smart-table-of-contents' ); ?></strong></p>
		<p class="description" style="margin-bottom: 8px;"><?php esc_html_e( 'Leave unchecked to use global settings.', 'anik-smart-table-of-contents' ); ?></p>
		<?php for ( $i = 2; $i <= 6; $i++ ) : ?>
			<label style="display: inline-block; margin-right: 10px;">
				<input type="checkbox"
						name="aniksmta_heading_levels[]"
						value="<?php echo esc_attr( $i ); ?>"
						<?php checked( is_array( $heading_levels ) && in_array( $i, $heading_levels, true ) ); ?>>
				H<?php echo esc_html( $i ); ?>
			</label>
		<?php endfor; ?>
		<?php
	}

	/**
	 * Save meta box
	 */
	public function save_meta_box( $post_id ) {
		if ( ! isset( $_POST['aniksmta_meta_box_nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['aniksmta_meta_box_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'aniksmta_meta_box' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$disabled = isset( $_POST['aniksmta_disable'] )
			? sanitize_text_field( wp_unslash( $_POST['aniksmta_disable'] ) )
			: '';
		$disabled = '1' === $disabled;
		if ( $disabled ) {
			update_post_meta( $post_id, '_aniksmta_disable', '1' );
		} else {
			delete_post_meta( $post_id, '_aniksmta_disable' );
		}

		// Save per-post heading levels.
		$heading_levels = ( isset( $_POST['aniksmta_heading_levels'] ) && is_array( $_POST['aniksmta_heading_levels'] ) )
			? array_map( 'absint', wp_unslash( $_POST['aniksmta_heading_levels'] ) )
			: array();
		if ( ! empty( $heading_levels ) && is_array( $heading_levels ) ) {
			$heading_levels = array_filter(
				$heading_levels,
				function ( $level ) {
					return $level >= 2 && $level <= 6;
				}
			);
			update_post_meta( $post_id, '_aniksmta_heading_levels', array_values( $heading_levels ) );
		} else {
			delete_post_meta( $post_id, '_aniksmta_heading_levels' );
		}
	}

	/**
	 * Add dashboard widget
	 */
	public function add_dashboard_widget() {
		wp_add_dashboard_widget(
			'aniksmta_dashboard_widget',
			__( '📑 Smart Table of Contents', 'anik-smart-table-of-contents' ),
			array( $this, 'render_dashboard_widget' )
		);
	}

	/**
	 * Render dashboard widget content
	 */
	public function render_dashboard_widget() {
		$settings   = $this->settings->get_all();
		$post_types = $settings['post_types'];

		// Get stats using a lightweight approach.
		$total_posts    = 0;
		$posts_with_toc = 0;

		foreach ( $post_types as $post_type ) {
			$count        = wp_count_posts( $post_type );
			$total_posts += isset( $count->publish ) ? $count->publish : 0;
		}

		// Sample a limited number of recent posts to estimate TOC usage.
		$sample_args  = array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'orderby'        => 'date',
			'order'          => 'DESC',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Limited to 100 posts.
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => '_aniksmta_disable',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'   => '_aniksmta_disable',
					'value' => '',
				),
			),
		);
		$sample_posts = get_posts( $sample_args );

		foreach ( $sample_posts as $post_id ) {
			$content       = get_post_field( 'post_content', $post_id );
			$heading_count = preg_match_all( '/<h[2-6][^>]*>/i', $content );
			if ( $heading_count >= $settings['min_headings'] ) {
				++$posts_with_toc;
			}
		}

		$toc_enabled  = $settings['enabled'] ? __( 'Active', 'anik-smart-table-of-contents' ) : __( 'Inactive', 'anik-smart-table-of-contents' );
		$status_class = $settings['enabled'] ? 'active' : 'inactive';
		?>
		<div class="smart-toc-widget">
			<div class="smart-toc-widget-stats">
				<div class="stat-item">
					<span class="stat-number"><?php echo esc_html( $posts_with_toc ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Posts with TOC', 'anik-smart-table-of-contents' ); ?></span>
				</div>
				<div class="stat-item">
					<span class="stat-number"><?php echo esc_html( $total_posts ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Total Posts', 'anik-smart-table-of-contents' ); ?></span>
				</div>
				<div class="stat-item">
					<span class="stat-number stat-status <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $toc_enabled ); ?></span>
					<span class="stat-label"><?php esc_html_e( 'Status', 'anik-smart-table-of-contents' ); ?></span>
				</div>
			</div>

			<div class="smart-toc-widget-actions">
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=aniksmta-settings' ) ); ?>" class="button">
					<?php esc_html_e( 'Settings', 'anik-smart-table-of-contents' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=aniksmta-settings&tab=documentation' ) ); ?>" class="button">
					<?php esc_html_e( 'Documentation', 'anik-smart-table-of-contents' ); ?>
				</a>
			</div>

			<div class="smart-toc-widget-pro">
				<h4>🚀 <?php esc_html_e( 'Unlock Pro Features', 'anik-smart-table-of-contents' ); ?></h4>
				<ul>
					<li>🔒 <?php esc_html_e( 'Elementor Widget Integration (Pro Only)', 'anik-smart-table-of-contents' ); ?></li>
					<li>📌 <?php esc_html_e( 'Advanced Floating + Sticky TOC Controls', 'anik-smart-table-of-contents' ); ?></li>
					<li>📱 <?php esc_html_e( 'Advanced Mobile TOC Experience', 'anik-smart-table-of-contents' ); ?></li>
					<li>🎨 <?php esc_html_e( 'Premium Theme Presets (Glass and Gradient)', 'anik-smart-table-of-contents' ); ?></li>
					<li>⚙️ <?php esc_html_e( 'Export/Import, SEO, and Performance Toolset', 'anik-smart-table-of-contents' ); ?></li>
				</ul>
				<a href="https://smallseoengine.com/plugins/smart-table-of-contents/" target="_blank" class="button button-primary">
					<?php esc_html_e( 'Get Pro Version', 'anik-smart-table-of-contents' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Display review request notice
	 */
	public function review_request_notice() {
		// Only show to admins
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Check if already dismissed
		if ( get_option( 'aniksmta_review_dismissed' ) ) {
			return;
		}

		// Check if already reviewed
		if ( get_option( 'aniksmta_review_done' ) ) {
			return;
		}

		// Check install date (show after 7 days)
		$install_date = get_option( 'aniksmta_install_date' );
		if ( ! $install_date || ( time() - $install_date ) < ( 7 * 86400 ) ) {
			return;
		}

		// Only show on certain admin pages
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'plugins', 'settings_page_aniksmta-settings' ), true ) ) {
			return;
		}
		?>
		<div class="notice notice-info smart-toc-review-notice is-dismissible" data-nonce="<?php echo esc_attr( wp_create_nonce( 'aniksmta_dismiss_review' ) ); ?>">
			<div class="smart-toc-review-content">
				<div class="smart-toc-review-icon">⭐</div>
				<div class="smart-toc-review-text">
					<p>
						<strong><?php esc_html_e( 'Enjoying Smart Table of Contents?', 'anik-smart-table-of-contents' ); ?></strong>
						<?php esc_html_e( "We'd love to hear your feedback! Please take a moment to leave a review on WordPress.org. Your support helps us improve and reach more users.", 'anik-smart-table-of-contents' ); ?>
					</p>
					<p class="smart-toc-review-actions">
						<a href="https://wordpress.org/support/plugin/anik-smart-table-of-contents/reviews/#new-post" target="_blank" class="button button-primary smart-toc-review-btn" data-action="reviewed">
							⭐ <?php esc_html_e( 'Leave a Review', 'anik-smart-table-of-contents' ); ?>
						</a>
						<a href="#" class="button smart-toc-review-btn" data-action="later">
							🕐 <?php esc_html_e( 'Maybe Later', 'anik-smart-table-of-contents' ); ?>
						</a>
						<a href="#" class="button smart-toc-review-btn" data-action="dismiss">
							✕ <?php esc_html_e( 'Already Did', 'anik-smart-table-of-contents' ); ?>
						</a>
					</p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle review notice dismissal
	 */
	public function dismiss_review_notice() {
		check_ajax_referer( 'aniksmta_dismiss_review', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die();
		}

		$action = isset( $_POST['review_action'] ) ? sanitize_key( wp_unslash( $_POST['review_action'] ) ) : 'dismiss';

		if ( 'reviewed' === $action || 'dismiss' === $action ) {
			update_option( 'aniksmta_review_done', true );
			update_option( 'aniksmta_review_dismissed', true );
		} elseif ( 'later' === $action ) {
			// Reset install date to show again in 7 days.
			update_option( 'aniksmta_install_date', time() );
		}

		wp_die();
	}
}
