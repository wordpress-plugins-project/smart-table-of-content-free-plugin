<?php
/**
 * Admin Settings Page
 *
 * @package Smart_TOC
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
    }

    /**
     * Add settings link to plugins page
     */
    public function plugin_action_links( $links ) {
        $settings_link = '<a href="' . admin_url( 'options-general.php?page=smart-toc' ) . '">' . __( 'Settings', 'smart-toc' ) . '</a>';
        $pro_link = '<a href="https://codecanyon.net/" target="_blank" style="color:#00a32a;font-weight:600;">' . __( 'Get Pro', 'smart-toc' ) . '</a>';
        array_unshift( $links, $settings_link );
        $links[] = $pro_link;
        return $links;
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __( 'Smart TOC Settings', 'smart-toc' ),
            __( 'Smart TOC', 'smart-toc' ),
            'manage_options',
            'smart-toc',
            array( $this, 'settings_page' )
        );
    }

    /**
     * Enqueue admin assets
     */
    public function admin_assets( $hook ) {
        if ( 'settings_page_smart-toc' !== $hook && 'post.php' !== $hook && 'post-new.php' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'smart-toc-admin',
            SMART_TOC_URL . 'assets/css/admin.css',
            array(),
            SMART_TOC_VERSION
        );

        // Color picker
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );

        wp_add_inline_script( 'wp-color-picker', '
            jQuery(document).ready(function($) {
                $(".smart-toc-color-picker").wpColorPicker();
            });
        ' );
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
    public function sanitize_settings( $input ) {
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

        $sanitized['smooth_scroll'] = ! empty( $input['smooth_scroll'] );
        $sanitized['highlight_active'] = ! empty( $input['highlight_active'] );

        $sanitized['title'] = isset( $input['title'] ) 
            ? sanitize_text_field( $input['title'] ) 
            : __( 'Table of Contents', 'smart-toc' );

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
        ?>
        <div class="wrap smart-toc-admin">
            <h1><?php esc_html_e( 'Smart Table of Contents', 'smart-toc' ); ?></h1>
            
            <!-- Pro Banner -->
            <div class="smart-toc-pro-banner">
                <div class="pro-banner-content">
                    <h3>🚀 <?php esc_html_e( 'Upgrade to Smart TOC Pro', 'smart-toc' ); ?></h3>
                    <p><?php esc_html_e( 'Get advanced features like Sticky TOC, Reading Progress Bar, Gutenberg Block, Theme Presets, and more!', 'smart-toc' ); ?></p>
                    <a href="https://codecanyon.net/" target="_blank" class="button button-primary"><?php esc_html_e( 'Get Pro Version', 'smart-toc' ); ?></a>
                </div>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields( 'smart_toc_settings_group' ); ?>
                
                <div class="smart-toc-settings-grid">
                    <!-- General Settings -->
                    <div class="smart-toc-card">
                        <h2><?php esc_html_e( 'General Settings', 'smart-toc' ); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Enable TOC', 'smart-toc' ); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="smart_toc_settings[enabled]" value="1" <?php checked( $settings['enabled'] ); ?>>
                                        <?php esc_html_e( 'Enable Table of Contents globally', 'smart-toc' ); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Post Types', 'smart-toc' ); ?></th>
                                <td>
                                    <?php
                                    $post_types = get_post_types( array( 'public' => true ), 'objects' );
                                    foreach ( $post_types as $post_type ) :
                                        if ( 'attachment' === $post_type->name ) continue;
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
                                <th scope="row"><?php esc_html_e( 'Minimum Headings', 'smart-toc' ); ?></th>
                                <td>
                                    <input type="number" 
                                           name="smart_toc_settings[min_headings]" 
                                           value="<?php echo esc_attr( $settings['min_headings'] ); ?>" 
                                           min="1" 
                                           max="10"
                                           class="small-text">
                                    <p class="description"><?php esc_html_e( 'Minimum number of headings required to display TOC', 'smart-toc' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Heading Levels', 'smart-toc' ); ?></th>
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
                        <h2><?php esc_html_e( 'Display Settings', 'smart-toc' ); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e( 'TOC Title', 'smart-toc' ); ?></th>
                                <td>
                                    <input type="text" 
                                           name="smart_toc_settings[title]" 
                                           value="<?php echo esc_attr( $settings['title'] ); ?>" 
                                           class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Position', 'smart-toc' ); ?></th>
                                <td>
                                    <select name="smart_toc_settings[position]">
                                        <option value="before_content" <?php selected( $settings['position'], 'before_content' ); ?>>
                                            <?php esc_html_e( 'Before Content', 'smart-toc' ); ?>
                                        </option>
                                        <option value="after_first_paragraph" <?php selected( $settings['position'], 'after_first_paragraph' ); ?>>
                                            <?php esc_html_e( 'After First Paragraph', 'smart-toc' ); ?>
                                        </option>
                                        <option value="manual" <?php selected( $settings['position'], 'manual' ); ?>>
                                            <?php esc_html_e( 'Manual (Shortcode Only)', 'smart-toc' ); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Default State', 'smart-toc' ); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="smart_toc_settings[default_collapsed]" value="1" <?php checked( $settings['default_collapsed'] ); ?>>
                                        <?php esc_html_e( 'Collapsed by default', 'smart-toc' ); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Show Numbers', 'smart-toc' ); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="smart_toc_settings[show_numbers]" value="1" <?php checked( $settings['show_numbers'] ); ?>>
                                        <?php esc_html_e( 'Display numbers before TOC items (1, 2, 3...)', 'smart-toc' ); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Theme Color', 'smart-toc' ); ?></th>
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
                        <h2><?php esc_html_e( 'Behavior Settings', 'smart-toc' ); ?></h2>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Smooth Scroll', 'smart-toc' ); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="smart_toc_settings[smooth_scroll]" value="1" <?php checked( $settings['smooth_scroll'] ); ?>>
                                        <?php esc_html_e( 'Enable smooth scrolling to headings', 'smart-toc' ); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Highlight Active', 'smart-toc' ); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="smart_toc_settings[highlight_active]" value="1" <?php checked( $settings['highlight_active'] ); ?>>
                                        <?php esc_html_e( 'Highlight current section in TOC', 'smart-toc' ); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Scroll Offset', 'smart-toc' ); ?></th>
                                <td>
                                    <input type="number" 
                                           name="smart_toc_settings[scroll_offset]" 
                                           value="<?php echo esc_attr( $settings['scroll_offset'] ); ?>" 
                                           min="0" 
                                           max="200"
                                           class="small-text"> px
                                    <p class="description"><?php esc_html_e( 'Offset from top when scrolling (useful for fixed headers)', 'smart-toc' ); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Pro Features Preview -->
                    <div class="smart-toc-card smart-toc-pro-features">
                        <h2>✨ <?php esc_html_e( 'Pro Features', 'smart-toc' ); ?></h2>
                        <ul class="pro-features-list">
                            <li>🔒 <?php esc_html_e( 'Sticky/Floating TOC', 'smart-toc' ); ?></li>
                            <li>🔒 <?php esc_html_e( 'Reading Progress Bar', 'smart-toc' ); ?></li>
                            <li>🔒 <?php esc_html_e( 'Estimated Reading Time', 'smart-toc' ); ?></li>
                            <li>🔒 <?php esc_html_e( 'Back to Top Button', 'smart-toc' ); ?></li>
                            <li>🔒 <?php esc_html_e( 'Keyboard Navigation', 'smart-toc' ); ?></li>
                            <li>🔒 <?php esc_html_e( 'Multiple Theme Presets', 'smart-toc' ); ?></li>
                            <li>🔒 <?php esc_html_e( 'Custom CSS Support', 'smart-toc' ); ?></li>
                            <li>🔒 <?php esc_html_e( 'Mobile-specific Options', 'smart-toc' ); ?></li>
                            <li>🔒 <?php esc_html_e( 'Gutenberg Block', 'smart-toc' ); ?></li>
                            <li>🔒 <?php esc_html_e( 'Sidebar Widget', 'smart-toc' ); ?></li>
                        </ul>
                        <a href="https://codecanyon.net/" target="_blank" class="button button-primary"><?php esc_html_e( 'Unlock All Features', 'smart-toc' ); ?></a>
                    </div>
                </div>

                <?php submit_button(); ?>
            </form>

            <!-- Shortcode Info -->
            <div class="smart-toc-card">
                <h2><?php esc_html_e( 'Shortcode Usage', 'smart-toc' ); ?></h2>
                <p><?php esc_html_e( 'Use the following shortcode to manually place the TOC:', 'smart-toc' ); ?></p>
                <code>[smart_toc]</code>
                <p style="margin-top: 10px;"><?php esc_html_e( 'With custom title:', 'smart-toc' ); ?></p>
                <code>[smart_toc title="In This Article"]</code>
                <p style="margin-top: 10px;"><?php esc_html_e( 'Collapsed by default:', 'smart-toc' ); ?></p>
                <code>[smart_toc collapsed="true"]</code>
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
                'smart_toc_meta_box',
                __( 'Smart TOC', 'smart-toc' ),
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
            <?php esc_html_e( 'Disable TOC for this post', 'smart-toc' ); ?>
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

        if ( ! wp_verify_nonce( $_POST['smart_toc_meta_box_nonce'], 'smart_toc_meta_box' ) ) {
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
}
