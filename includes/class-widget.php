<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sidebar Widget Integration
 *
 * @package Anik_Smart_TOC
 */
class Aniksmta_Widget {

	/**
	 * Settings instance
	 *
	 * @var Aniksmta_Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param Aniksmta_Settings $settings Settings instance.
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;
		add_action( 'widgets_init', array( $this, 'register_widget' ) );
	}

	/**
	 * Register TOC widget.
	 */
	public function register_widget() {
		register_widget( 'Aniksmta_Toc_Widget' );
	}
}

/**
 * TOC sidebar widget.
 */
class Aniksmta_Toc_Widget extends WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'aniksmta_toc_widget',
			__( 'Smart TOC', 'anik-smart-table-of-contents' ),
			array(
				'description' => __( 'Display a Smart TOC in a widget area.', 'anik-smart-table-of-contents' ),
			)
		);
	}

	/**
	 * Frontend widget output.
	 *
	 * @param array $args Display args.
	 * @param array $instance Widget instance.
	 */
	public function widget( $args, $instance ) {
		if ( ! is_singular() ) {
			return;
		}

		global $post;
		if ( ! ( $post instanceof WP_Post ) ) {
			return;
		}

		$title     = isset( $instance['title'] ) ? sanitize_text_field( $instance['title'] ) : '';
		$collapsed = ! empty( $instance['collapsed'] );

		$overrides = array(
			'collapsed' => $collapsed,
		);
		if ( '' !== $title ) {
			$overrides['title'] = $title;
		}

		$content = preg_replace( '/\[aniksmta_toc[^\]]*\]/', '', $post->post_content );
		if ( ! is_string( $content ) ) {
			$content = $post->post_content;
		}
		$content = wptexturize( $content );
		$content = wpautop( $content );
		$content = do_shortcode( $content );

		$settings = new Aniksmta_Settings();
		$render   = new Aniksmta_Render( $settings, true );
		$toc_html = $render->generate_toc_html( $content, $overrides );

		if ( '' === $toc_html ) {
			return;
		}

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Provided by current theme.
		echo $toc_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped while building TOC HTML.
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Provided by current theme.
	}

	/**
	 * Widget settings form.
	 *
	 * @param array $instance Widget instance.
	 */
	public function form( $instance ) {
		$title     = isset( $instance['title'] ) ? $instance['title'] : '';
		$collapsed = ! empty( $instance['collapsed'] );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
				<?php esc_html_e( 'TOC Title', 'anik-smart-table-of-contents' ); ?>
			</label>
			<input class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
				type="text"
				value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'collapsed' ) ); ?>">
				<input
					id="<?php echo esc_attr( $this->get_field_id( 'collapsed' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'collapsed' ) ); ?>"
					type="checkbox"
					value="1"
					<?php checked( $collapsed ); ?>>
				<?php esc_html_e( 'Collapsed by default', 'anik-smart-table-of-contents' ); ?>
			</label>
		</p>
		<?php
	}

	/**
	 * Save widget settings.
	 *
	 * @param array $new_instance New values.
	 * @param array $old_instance Existing values.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title']     = isset( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['collapsed'] = ! empty( $new_instance['collapsed'] );

		return $instance;
	}
}
