<?php
namespace Pushengage\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Feedback Class.
 *
 * @since 4.1.4.1
 */
class PluginFeedback {

	/**
	 * Singleton instance.
	 *
	 * @var PluginFeedback
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return PluginFeedback
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'admin_footer', array( $this, 'deactivation_feedback_template' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		// Add plugin row meta links.
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_additional_links' ), 10, 2 );
	}

	/**
	 * Add plugin row meta links
	 *
	 * @since 4.1.4.1
	 *
	 * @param array $links
	 * @param string $file
	 *
	 * @return array
	 */
	public function plugin_row_additional_links( $links, $plugin_file ) {
		if ( ! strpos( $plugin_file, basename( PUSHENGAGE_FILE ) ) ) {
			return $links;
		}

		$additional_links_data = array(
			array(
				'label' => __( 'Suggest a Feature', 'pushengage' ),
				'url'   => 'https://www.pushengage.com/wordpress-plugin-user-survey/?utm_source=WordPress&utm_medium=PluginRowMeta&utm_content=Suggest a Feature',
			),
		);

		$additional_links = array();
		foreach ( $additional_links_data as $link_data ) {
			$additional_links[] = '<a href="' . esc_url( $link_data['url'] ) . '" target="_blank">' . esc_html( $link_data['label'] ) . '</a>';
		}

		return array_merge( $links, $additional_links );
	}

	/**
	 * Add deactivation feedback template.
	 */
	public function deactivation_feedback_template() {
		global $pagenow;

		if ( 'plugins.php' !== $pagenow ) {
			return;
		}

		?>
			<div style="display: none;" class="pushengage-deactivation-feedback-modal">
				<div class="pushengage-deactivation-feedback-modal-body">
					<div class="pushengage-deactivation-feedback-modal-body-header">
						<h3><?php esc_html_e( 'PushEngage Deactivation', 'pushengage' ); ?></h3>
					</div>
					<div class="pushengage-deactivation-feedback-modal-body-content">
						<p class="pushengage-deactivation-fb-title"><?php esc_html_e( 'If you have a moment, please let us know why you’re deactivating.', 'pushengage' ); ?></p>
						<div class="pushengage-feedback-reasons">
							<label class="pushengage-feedback-reason">
								<input type="radio" name="deactivation_reason" value="did_not_work" />
								<span><?php esc_html_e( 'The plugin didn’t work', 'pushengage' ); ?></span>
							</label>

							<label class="pushengage-feedback-reason">
								<input type="radio" name="deactivation_reason" value="better_plugin" />
								<span><?php esc_html_e( 'I found a better plugin', 'pushengage' ); ?></span>
							</label>

							<label class="pushengage-feedback-reason">
								<input type="radio" name="deactivation_reason" value="missing_feature" />
								<span><?php esc_html_e( 'Missing a specific feature', 'pushengage' ); ?></span>
							</label>

							<label class="pushengage-feedback-reason">
								<input type="radio" name="deactivation_reason" value="did_not_work_as_expected" />
								<span>
								<?php
								esc_html_e(
									'Didn’t work as expected',
									'pushengage'
								);
								?>
									</span>
							</label>

							<label class="pushengage-feedback-reason">
								<input type="radio" name="deactivation_reason" value="temporary_deactivation" />
								<span><?php esc_html_e( 'It’s a temporary deactivation - I’m troubleshooting an issue', 'pushengage' ); ?></span>
							</label>

							<label class="pushengage-feedback-reason">
								<input type="radio" name="deactivation_reason" value="other" />
								<span><?php esc_html_e( 'Others', 'pushengage' ); ?></span>
							</label>
						</div>
						<div style="display: none;" class="pushengage-feedback-details">
							<textarea name="feedback_details" placeholder="<?php esc_attr_e( 'Please share your thoughts', 'pushengage' ); ?>" rows="4" class="pushengage-feedback-textarea"></textarea>
						</div>
					</div>

					<div class="pushengage-deactivation-feedback-modal-body-footer">
						<button type="button" class="pushengage-cancel button edit-attachment"><?php esc_html_e( 'Cancel', 'pushengage' ); ?></button>
						<button type="button" class="button-primary pushengage-submit-skip-deactivate"><?php esc_html_e( 'Skip & Deactivate', 'pushengage' ); ?></button>
					</div>
				</div>
			</div>
		<?php
	}

	/**
	 * Enqueue deactivation feedback scripts.
	 */
	public function enqueue_scripts() {
		global $pagenow;

		if ( 'plugins.php' !== $pagenow ) {
			return;
		}

		wp_enqueue_script(
			'pushengage-deactivation-feedback',
			PUSHENGAGE_PLUGIN_URL . 'assets/js/deactivation-feedback.js',
			array( 'jquery' ),
			PUSHENGAGE_VERSION,
			true
		);

		wp_enqueue_style(
			'pushengage-deactivation-feedback',
			PUSHENGAGE_PLUGIN_URL . 'assets/css/deactivation-feedback.css',
			array(),
			PUSHENGAGE_VERSION
		);

		// Localize script with AJAX URL and nonce
		wp_localize_script(
			'pushengage-deactivation-feedback',
			'pushengageDeactivationFeedbackVars',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'pe_deactivation_feedback_nonce' ),
			)
		);
	}
}
