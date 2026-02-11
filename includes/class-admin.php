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
			__( 'Anik Smart TOC Settings', 'anik-smart-table-of-contents' ),
			__( 'Anik Smart TOC', 'anik-smart-table-of-contents' ),
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
			<h1><?php esc_html_e( 'Anik Smart Table of Contents', 'anik-smart-table-of-contents' ); ?></h1>
			
			<!-- Pro Banner -->
			<div class="smart-toc-pro-banner">
				<div class="pro-banner-content">
					<h3>🚀 <?php esc_html_e( 'Upgrade to Smart TOC Pro', 'anik-smart-table-of-contents' ); ?></h3>
					<p><?php esc_html_e( 'Get advanced features like Sticky TOC, Reading Progress Bar, Gutenberg Block, Theme Presets, and more!', 'anik-smart-table-of-contents' ); ?></p>
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
						</table>
					</div>

					<!-- Pro Features Preview -->
					<div class="smart-toc-card smart-toc-pro-features">
						<h2>✨ <?php esc_html_e( 'Pro Features', 'anik-smart-table-of-contents' ); ?></h2>
						<ul class="pro-features-list">
							<li>🔒 <?php esc_html_e( 'Sticky/Floating TOC', 'anik-smart-table-of-contents' ); ?></li>
							<li>🔒 <?php esc_html_e( 'Reading Progress Bar', 'anik-smart-table-of-contents' ); ?></li>
							<li>🔒 <?php esc_html_e( 'Estimated Reading Time', 'anik-smart-table-of-contents' ); ?></li>
							<li>🔒 <?php esc_html_e( 'Back to Top Button', 'anik-smart-table-of-contents' ); ?></li>
							<li>🔒 <?php esc_html_e( 'Keyboard Navigation', 'anik-smart-table-of-contents' ); ?></li>
							<li>🔒 <?php esc_html_e( 'Multiple Theme Presets', 'anik-smart-table-of-contents' ); ?></li>
							<li>🔒 <?php esc_html_e( 'Custom CSS Support', 'anik-smart-table-of-contents' ); ?></li>
							<li>🔒 <?php esc_html_e( 'Mobile-specific Options', 'anik-smart-table-of-contents' ); ?></li>
							<li>🔒 <?php esc_html_e( 'Gutenberg Block', 'anik-smart-table-of-contents' ); ?></li>
							<li>🔒 <?php esc_html_e( 'Sidebar Widget', 'anik-smart-table-of-contents' ); ?></li>
						</ul>
						<a href="https://smallseoengine.com/plugins/smart-table-of-contents/" target="_blank" class="button button-primary"><?php esc_html_e( 'Unlock All Features', 'anik-smart-table-of-contents' ); ?></a>
					</div>
				</div>

				<?php submit_button(); ?>
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
					<p><?php esc_html_e( 'Disable TOC on individual posts/pages:', 'anik-smart-table-of-contents' ); ?></p>
					<ol class="smart-toc-docs-list">
						<li><?php esc_html_e( 'Edit the post/page in WordPress admin', 'anik-smart-table-of-contents' ); ?></li>
						<li><?php esc_html_e( 'Find the "Smart TOC" meta box in the sidebar', 'anik-smart-table-of-contents' ); ?></li>
						<li><?php esc_html_e( 'Check "Disable TOC for this post"', 'anik-smart-table-of-contents' ); ?></li>
						<li><?php esc_html_e( 'Save/Update the post', 'anik-smart-table-of-contents' ); ?></li>
					</ol>
					<p><em><?php esc_html_e( 'Useful for landing pages, short posts, or custom layouts.', 'anik-smart-table-of-contents' ); ?></em></p>
				</div>

				<!-- Excluding Headings -->
				<div class="smart-toc-card smart-toc-docs-card">
					<h2><span class="dashicons dashicons-hidden"></span> <?php esc_html_e( 'Excluding Headings', 'anik-smart-table-of-contents' ); ?></h2>
					<p><?php esc_html_e( 'Add the "no-toc" CSS class to exclude specific headings:', 'anik-smart-table-of-contents' ); ?></p>
					
					<h3><?php esc_html_e( 'Block Editor (Gutenberg)', 'anik-smart-table-of-contents' ); ?></h3>
					<ol class="smart-toc-docs-list">
						<li><?php esc_html_e( 'Select the heading block', 'anik-smart-table-of-contents' ); ?></li>
						<li><?php esc_html_e( 'Open Block Settings (right sidebar)', 'anik-smart-table-of-contents' ); ?></li>
						<li><?php esc_html_e( 'Expand "Advanced" section', 'anik-smart-table-of-contents' ); ?></li>
						<li><?php esc_html_e( 'Add "no-toc" to Additional CSS class(es)', 'anik-smart-table-of-contents' ); ?></li>
					</ol>
					
					<h3><?php esc_html_e( 'Classic Editor (HTML)', 'anik-smart-table-of-contents' ); ?></h3>
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
					<p><?php esc_html_e( 'Yes! Uses semantic HTML with proper ARIA labels. Search engines can index TOC anchor links.', 'anik-smart-table-of-contents' ); ?></p>
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
					<div class="support-icon"></div>
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
						<h3><?php esc_html_e( 'Enjoying Anik Smart Table of Contents?', 'anik-smart-table-of-contents' ); ?></h3>
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
					<li>📌 <?php esc_html_e( 'Sticky/Floating TOC', 'anik-smart-table-of-contents' ); ?></li>
					<li>📊 <?php esc_html_e( 'Reading Progress Bar', 'anik-smart-table-of-contents' ); ?></li>
					<li>⏱️ <?php esc_html_e( 'Estimated Reading Time', 'anik-smart-table-of-contents' ); ?></li>
					<li>🧱 <?php esc_html_e( 'Gutenberg Block', 'anik-smart-table-of-contents' ); ?></li>
					<li>🎨 <?php esc_html_e( 'Multiple Theme Presets', 'anik-smart-table-of-contents' ); ?></li>
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

		$disabled = get_post_meta( $post->ID, '_aniksmta_disable', true );
		?>
		<label>
			<input type="checkbox" name="aniksmta_disable" value="1" <?php checked( $disabled ); ?>>
			<?php esc_html_e( 'Disable TOC for this post', 'anik-smart-table-of-contents' ); ?>
		</label>
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

		$disabled = ! empty( $_POST['aniksmta_disable'] );
		if ( $disabled ) {
			update_post_meta( $post_id, '_aniksmta_disable', '1' );
		} else {
			delete_post_meta( $post_id, '_aniksmta_disable' );
		}
	}

	/**
	 * Add dashboard widget
	 */
	public function add_dashboard_widget() {
		wp_add_dashboard_widget(
			'aniksmta_dashboard_widget',
			__( '📑 Anik Smart Table of Contents', 'anik-smart-table-of-contents' ),
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
					<li>📌 <?php esc_html_e( 'Sticky/Floating TOC', 'anik-smart-table-of-contents' ); ?></li>
					<li>📊 <?php esc_html_e( 'Reading Progress Bar', 'anik-smart-table-of-contents' ); ?></li>
					<li>⏱️ <?php esc_html_e( 'Reading Time Display', 'anik-smart-table-of-contents' ); ?></li>
					<li>🎨 <?php esc_html_e( '5+ Theme Presets', 'anik-smart-table-of-contents' ); ?></li>
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
						<strong><?php esc_html_e( 'Enjoying Anik Smart Table of Contents?', 'anik-smart-table-of-contents' ); ?></strong>
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

		$action = isset( $_POST['review_action'] ) ? sanitize_key( $_POST['review_action'] ) : 'dismiss';

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
