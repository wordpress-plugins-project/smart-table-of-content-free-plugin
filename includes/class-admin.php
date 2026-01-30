<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Settings Page
 *
 * @package Smart_TOC
 */

class Smart_TOC_Admin {

	/**
	 * Settings instance
	 *
	 * @var Smart_TOC_Settings
	 */
	private $settings;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->settings = new Smart_TOC_Settings();

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
		add_filter( 'plugin_action_links_' . SMART_TOC_BASENAME, array( $this, 'plugin_action_links' ) );

		// Meta box for per-post settings
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_meta_box' ) );

		// Dashboard widget
		add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );

		// Review request notice
		add_action( 'admin_notices', array( $this, 'review_request_notice' ) );
		add_action( 'wp_ajax_smart_toc_dismiss_review', array( $this, 'dismiss_review_notice' ) );

		// Track installation date
		$this->maybe_set_install_date();
	}

	/**
	 * Set installation date if not already set
	 */
	private function maybe_set_install_date() {
		if ( ! get_option( 'smart_toc_install_date' ) ) {
			update_option( 'smart_toc_install_date', time() );
		}
	}

	/**
	 * Add settings link to plugins page
	 */
	public function plugin_action_links( $links ) {
		$settings_link = '<a href="' . admin_url( 'options-general.php?page=smart-toc-free' ) . '">' . __( 'Settings', 'anik-smart-table-of-contents' ) . '</a>';
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
			'smart-toc-free',
			array( $this, 'settings_page' )
		);
	}

	/**
	 * Enqueue admin assets
	 */
	public function admin_assets( $hook ) {
		if ( 'settings_page_smart-toc-free' !== $hook && 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'smart-toc-free-admin',
			SMART_TOC_URL . 'assets/css/admin.css',
			array(),
			SMART_TOC_VERSION
		);

		// Color picker
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		wp_add_inline_script(
			'wp-color-picker',
			'
            jQuery(document).ready(function($) {
                $(".smart-toc-color-picker").wpColorPicker();
            });
        '
		);
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting(
			'smart_toc_settings_group',
			'smart_toc_settings',
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
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=smart-toc-free&tab=settings' ) ); ?>" class="nav-tab <?php echo 'settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Settings', 'anik-smart-table-of-contents' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=smart-toc-free&tab=documentation' ) ); ?>" class="nav-tab <?php echo 'documentation' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-book"></span> <?php esc_html_e( 'Documentation', 'anik-smart-table-of-contents' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=smart-toc-free&tab=support' ) ); ?>" class="nav-tab <?php echo 'support' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-sos"></span> <?php esc_html_e( 'Help & Support', 'anik-smart-table-of-contents' ); ?>
				</a>
			</nav>

			<?php if ( 'settings' === $active_tab ) : ?>
			<form method="post" action="options.php">
				<?php settings_fields( 'smart_toc_settings_group' ); ?>
				
				<div class="smart-toc-settings-grid">
					<!-- General Settings -->
					<div class="smart-toc-card">
						<h2><?php esc_html_e( 'General Settings', 'anik-smart-table-of-contents' ); ?></h2>
						
						<table class="form-table">
							<tr>
								<th scope="row"><?php esc_html_e( 'Enable TOC', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="smart_toc_settings[enabled]" value="1" <?php checked( $settings['enabled'] ); ?>>
										<?php esc_html_e( 'Enable Table of Contents globally', 'anik-smart-table-of-contents' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Post Types', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<?php
									$post_types = get_post_types( array( 'public' => true ), 'objects' );
									foreach ( $post_types as $post_type ) :
										if ( 'attachment' === $post_type->name ) {
											continue;
										}
										?>
										<label style="display: block; margin-bottom: 5px;">
											<input type="checkbox" 
													name="smart_toc_settings[post_types][]" 
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
											name="smart_toc_settings[min_headings]" 
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
													name="smart_toc_settings[heading_levels][]" 
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
											name="smart_toc_settings[title]" 
											value="<?php echo esc_attr( $settings['title'] ); ?>" 
											class="regular-text">
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Position', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<select name="smart_toc_settings[position]">
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
										<input type="checkbox" name="smart_toc_settings[default_collapsed]" value="1" <?php checked( $settings['default_collapsed'] ); ?>>
										<?php esc_html_e( 'Collapsed by default', 'anik-smart-table-of-contents' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Show Numbers', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="smart_toc_settings[show_numbers]" value="1" <?php checked( $settings['show_numbers'] ); ?>>
										<?php esc_html_e( 'Display numbers before TOC items (1, 2, 3...)', 'anik-smart-table-of-contents' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Theme Color', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<input type="text" 
											name="smart_toc_settings[theme_color]" 
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
										<input type="checkbox" name="smart_toc_settings[smooth_scroll]" value="1" <?php checked( $settings['smooth_scroll'] ); ?>>
										<?php esc_html_e( 'Enable smooth scrolling to headings', 'anik-smart-table-of-contents' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Highlight Active', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="smart_toc_settings[highlight_active]" value="1" <?php checked( $settings['highlight_active'] ); ?>>
										<?php esc_html_e( 'Highlight current section in TOC', 'anik-smart-table-of-contents' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Scroll Offset', 'anik-smart-table-of-contents' ); ?></th>
								<td>
									<input type="number" 
											name="smart_toc_settings[scroll_offset]" 
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
				<code>[smart_toc]</code>
				<p style="margin-top: 10px;"><?php esc_html_e( 'With custom title:', 'anik-smart-table-of-contents' ); ?></p>
				<code>[smart_toc title="In This Article"]</code>
				<p style="margin-top: 10px;"><?php esc_html_e( 'Collapsed by default:', 'anik-smart-table-of-contents' ); ?></p>
				<code>[smart_toc collapsed="true"]</code>
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
						<code>[smart_toc]</code>
					</div>
					
					<h3><?php esc_html_e( 'Custom Title', 'anik-smart-table-of-contents' ); ?></h3>
					<div class="smart-toc-code-block">
						<code>[smart_toc title="In This Article"]</code>
					</div>
					
					<h3><?php esc_html_e( 'Collapsed by Default', 'anik-smart-table-of-contents' ); ?></h3>
					<div class="smart-toc-code-block">
						<code>[smart_toc collapsed="true"]</code>
					</div>
					
					<h3><?php esc_html_e( 'Combined Attributes', 'anik-smart-table-of-contents' ); ?></h3>
					<div class="smart-toc-code-block">
						<code>[smart_toc title="Quick Navigation" collapsed="false"]</code>
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
						<td><?php echo esc_html( SMART_TOC_VERSION ); ?></td>
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
				<button type="button" class="button smart-toc-copy-info" onclick="smartTocCopySystemInfo()">
					📋 <?php esc_html_e( 'Copy System Info', 'anik-smart-table-of-contents' ); ?>
				</button>
			</div>
		</div>

		<style>
			.smart-toc-support-container {
				margin-top: 20px;
			}
			.smart-toc-support-card {
				text-align: center;
				padding: 25px 20px;
			}
			.smart-toc-support-card .support-icon {
				font-size: 40px;
				margin-bottom: 10px;
			}
			.smart-toc-support-card h3 {
				margin: 0 0 10px;
			}
			.smart-toc-support-card p {
				color: #666;
				margin-bottom: 15px;
			}
			.smart-toc-rate-card {
				background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
				border: none;
			}
			.smart-toc-rate-card .rate-content {
				display: flex;
				align-items: center;
				gap: 20px;
				flex-wrap: wrap;
			}
			.smart-toc-rate-card .rate-icon {
				font-size: 50px;
			}
			.smart-toc-rate-card .rate-text {
				flex: 1;
				min-width: 200px;
			}
			.smart-toc-rate-card .rate-text h3 {
				margin: 0 0 5px;
				color: #333;
			}
			.smart-toc-rate-card .rate-text p {
				margin: 0;
				color: #555;
			}
			.smart-toc-upgrade-card {
				background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
				color: #fff;
			}
			.smart-toc-upgrade-card h3,
			.smart-toc-upgrade-card p,
			.smart-toc-upgrade-card li {
				color: #fff;
			}
			.smart-toc-upgrade-card .pro-features-list {
				display: grid;
				grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
				gap: 10px;
				list-style: none;
				padding: 0;
				margin: 15px 0 20px;
			}
			.smart-toc-upgrade-card .pro-features-list li {
				background: rgba(255,255,255,0.1);
				padding: 8px 12px;
				border-radius: 4px;
			}
			.smart-toc-upgrade-card .button-hero {
				font-size: 16px;
				padding: 10px 30px;
				height: auto;
			}
			.smart-toc-system-info {
				background: #f8f9fa;
			}
			.smart-toc-system-table {
				width: 100%;
				border-collapse: collapse;
				margin: 15px 0;
			}
			.smart-toc-system-table td {
				padding: 8px 12px;
				border-bottom: 1px solid #e2e4e7;
			}
			.smart-toc-system-table tr:last-child td {
				border-bottom: none;
			}
			.smart-toc-system-table td:first-child {
				width: 40%;
				background: #fff;
			}
			.smart-toc-copy-info {
				margin-top: 10px;
			}
		</style>

		<script>
			function smartTocCopySystemInfo() {
				var info = '';
				var rows = document.querySelectorAll('.smart-toc-system-table tr');
				rows.forEach(function(row) {
					var cells = row.querySelectorAll('td');
					if (cells.length === 2) {
						info += cells[0].innerText + ': ' + cells[1].innerText + '\n';
					}
				});
				navigator.clipboard.writeText(info).then(function() {
					alert('<?php echo esc_js( __( 'System info copied to clipboard!', 'anik-smart-table-of-contents' ) ); ?>');
				});
			}
		</script>
		<?php
	}

	/**
	 * Add meta box
	 */
	public function add_meta_box() {
		$post_types = $this->settings->get( 'post_types' );

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'smart_toc_meta_box',
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
		wp_nonce_field( 'smart_toc_meta_box', 'smart_toc_meta_box_nonce' );

		$disabled = get_post_meta( $post->ID, '_smart_toc_disable', true );
		?>
		<label>
			<input type="checkbox" name="smart_toc_disable" value="1" <?php checked( $disabled ); ?>>
			<?php esc_html_e( 'Disable TOC for this post', 'anik-smart-table-of-contents' ); ?>
		</label>
		<?php
	}

	/**
	 * Save meta box
	 */
	public function save_meta_box( $post_id ) {
		if ( ! isset( $_POST['smart_toc_meta_box_nonce'] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST['smart_toc_meta_box_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'smart_toc_meta_box' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$disabled = ! empty( $_POST['smart_toc_disable'] );
		update_post_meta( $post_id, '_smart_toc_disable', $disabled );
	}

	/**
	 * Add dashboard widget
	 */
	public function add_dashboard_widget() {
		wp_add_dashboard_widget(
			'smart_toc_dashboard_widget',
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

		// Get stats
		$total_posts    = 0;
		$posts_with_toc = 0;

		foreach ( $post_types as $post_type ) {
			$args         = array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			);
			$posts        = get_posts( $args );
			$total_posts += count( $posts );

			// Count posts with enough headings for TOC
			foreach ( $posts as $post_id ) {
				$content  = get_post_field( 'post_content', $post_id );
				$disabled = get_post_meta( $post_id, '_smart_toc_disable', true );

				if ( ! $disabled ) {
					$heading_count = preg_match_all( '/<h[2-6][^>]*>/i', $content );
					if ( $heading_count >= $settings['min_headings'] ) {
						++$posts_with_toc;
					}
				}
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
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=smart-toc-free' ) ); ?>" class="button">
					<?php esc_html_e( 'Settings', 'anik-smart-table-of-contents' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'options-general.php?page=smart-toc-free&tab=documentation' ) ); ?>" class="button">
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

		<style>
			.smart-toc-widget-stats {
				display: flex;
				justify-content: space-between;
				margin-bottom: 15px;
				padding: 15px;
				background: #f8f9fa;
				border-radius: 6px;
			}
			.smart-toc-widget-stats .stat-item {
				text-align: center;
			}
			.smart-toc-widget-stats .stat-number {
				display: block;
				font-size: 24px;
				font-weight: 700;
				color: #1d2327;
			}
			.smart-toc-widget-stats .stat-number.stat-status {
				font-size: 14px;
				padding: 4px 10px;
				border-radius: 4px;
			}
			.smart-toc-widget-stats .stat-number.active {
				background: #d4edda;
				color: #155724;
			}
			.smart-toc-widget-stats .stat-number.inactive {
				background: #f8d7da;
				color: #721c24;
			}
			.smart-toc-widget-stats .stat-label {
				font-size: 12px;
				color: #666;
				margin-top: 4px;
				display: block;
			}
			.smart-toc-widget-actions {
				display: flex;
				gap: 10px;
				margin-bottom: 15px;
			}
			.smart-toc-widget-pro {
				background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
				padding: 15px;
				border-radius: 6px;
				color: #fff;
			}
			.smart-toc-widget-pro h4 {
				margin: 0 0 10px;
				color: #fff;
			}
			.smart-toc-widget-pro ul {
				margin: 0 0 12px;
				padding-left: 5px;
				list-style: none;
			}
			.smart-toc-widget-pro li {
				margin-bottom: 4px;
				font-size: 13px;
			}
			.smart-toc-widget-pro .button-primary {
				background: #fff;
				color: #667eea;
				border: none;
			}
			.smart-toc-widget-pro .button-primary:hover {
				background: #f0f0f0;
				color: #5a67d8;
			}
		</style>
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
		if ( get_option( 'smart_toc_review_dismissed' ) ) {
			return;
		}

		// Check if already reviewed
		if ( get_option( 'smart_toc_review_done' ) ) {
			return;
		}

		// Check install date (show after 7 days)
		$install_date = get_option( 'smart_toc_install_date' );
		if ( ! $install_date || ( time() - $install_date ) < ( 7 * DAY_IN_SECONDS ) ) {
			return;
		}

		// Only show on certain admin pages
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'plugins', 'settings_page_smart-toc-free' ), true ) ) {
			return;
		}
		?>
		<div class="notice notice-info smart-toc-review-notice is-dismissible" data-nonce="<?php echo esc_attr( wp_create_nonce( 'smart_toc_dismiss_review' ) ); ?>">
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

		<style>
			.smart-toc-review-notice {
				padding: 15px;
			}
			.smart-toc-review-content {
				display: flex;
				align-items: flex-start;
				gap: 15px;
			}
			.smart-toc-review-icon {
				font-size: 32px;
				line-height: 1;
			}
			.smart-toc-review-text p {
				margin: 0 0 10px;
			}
			.smart-toc-review-actions {
				display: flex;
				gap: 10px;
				flex-wrap: wrap;
			}
			.smart-toc-review-actions .button {
				display: inline-flex;
				align-items: center;
				gap: 5px;
			}
		</style>

		<script>
			jQuery(document).ready(function($) {
				$('.smart-toc-review-btn').on('click', function(e) {
					var action = $(this).data('action');
					var nonce = $(this).closest('.smart-toc-review-notice').data('nonce');
					
					if (action !== 'reviewed') {
						e.preventDefault();
					}

					$.post(ajaxurl, {
						action: 'smart_toc_dismiss_review',
						review_action: action,
						nonce: nonce
					});

					$(this).closest('.smart-toc-review-notice').fadeOut();
				});

				// Handle the X button dismiss
				$(document).on('click', '.smart-toc-review-notice .notice-dismiss', function() {
					var nonce = $(this).closest('.smart-toc-review-notice').data('nonce');
					$.post(ajaxurl, {
						action: 'smart_toc_dismiss_review',
						review_action: 'later',
						nonce: nonce
					});
				});
			});
		</script>
		<?php
	}

	/**
	 * Handle review notice dismissal
	 */
	public function dismiss_review_notice() {
		check_ajax_referer( 'smart_toc_dismiss_review', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die();
		}

		$action = isset( $_POST['review_action'] ) ? sanitize_key( $_POST['review_action'] ) : 'dismiss';

		if ( 'reviewed' === $action || 'dismiss' === $action ) {
			update_option( 'smart_toc_review_done', true );
		} elseif ( 'later' === $action ) {
			// Reset install date to show again in 7 days
			update_option( 'smart_toc_install_date', time() );
		}

		update_option( 'smart_toc_review_dismissed', true );

		wp_die();
	}
}
