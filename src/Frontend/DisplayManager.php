<?php
/**
 * Frontend Display Manager
 *
 * @package FOMOZO
 * @since 0.1.0
 */

namespace FOMOZO\Frontend;

/**
 * Handles rendering of the frontend container and related hooks
 */
class DisplayManager {

	/**
	 * Bootstrap frontend hooks
	 */
	public function __construct() {
		if ($this->should_disable_frontend_output()) {
			return;
		}

		add_action('wp_footer', [$this, 'render_container'], 100);
		add_shortcode('fomozo', [$this, 'shortcode']);
	}

	/**
	 * Determine if frontend output should be skipped
	 */
	private function should_disable_frontend_output() {
		if (is_admin()) {
			return true;
		}

		if (function_exists('wp_is_json_request') && wp_is_json_request()) {
			return true;
		}

		if (is_feed()) {
			return true;
		}

		return false;
	}

	/**
	 * Shortcode handler: [fomozo]
	 */
	public function shortcode() {
		ob_start();
		$this->render_container(false);
		return ob_get_clean();
	}

	/**
	 * Output the frontend container that the JS will mount into
	 *
	 * @param bool $echo Whether to echo directly (default true)
	 */
	public function render_container($echo = true) {
		$branding = '';
		if (get_option('fomozo_show_branding', true)) {
			$branding = '<div class="fomozo-branding"><a href="https://example.com" target="_blank" rel="nofollow noopener">Powered by FOMOZO</a></div>';
		}

		$container = '<div id="fomozo-root" class="fomozo-root" aria-live="polite" aria-atomic="true"></div>' . $branding;

		if ($echo === false) {
			echo $container; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}

		echo $container; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}


