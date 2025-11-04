<?php
namespace FOMOZO\Integrations\WooCommerce;

use FOMOZO\Integrations\IntegrationInterface;
use FOMOZO\Integrations\WooCommerce\WooProvider;

class WooCommerceIntegration implements IntegrationInterface {
	public function get_id() { return 'woocommerce'; }
	public function get_title() { return 'WooCommerce'; }
	public function get_description() { return __( 'Pulls recent orders to display sales notifications.', 'fomozo'); }
	public function get_logo_url() { return defined( 'FOMOZO_URL') ? FOMOZO_URL . 'assets/logos/woocommerce.png' : ''; }
	public function is_available() { return class_exists( 'WooCommerce'); }
	public function is_active() { return in_array($this->get_id(), get_option( 'fomozo_integrations_active', []), true); }
	public function activate() {}
	public function deactivate() {}

	public function register_hooks() {
		if ( ! $this->is_available()) { return; }
		// Listen to order completed
		add_action( 'woocommerce_thankyou', [$this, 'handle_order'], 10, 1);
		// Provide notifications for core generator via filter
		add_filter( 'fomozo_external_notifications', function($list, $campaigns) {
			$woos = WooProvider::build_notifications( $campaigns );
			return array_merge(is_array($list ) ? $list : [], $woos );
		}, 10, 2);
	}

	public function handle_order($order_id) {
		if (!$order_id) { return; }
		$order = function_exists( 'wc_get_order') ? wc_get_order($order_id) : null;
		if (!$order) { return; }

		$items = $order->get_items();
		$product_name = '';
		foreach ($items as $item) { $product_name = $item->get_name(); break; }
		$billing = $order->get_address( 'billing');
		$location = trim(($billing['city'] ?? '') . ', ' . ($billing['country'] ?? ''));
		$customer = trim(($billing['first_name'] ?? 'Customer') . ' ' . substr(($billing['last_name'] ?? ''), 0, 1) . '.');

		do_action( 'fomozo_track_event', 'sale', [
			'product' => $product_name,
			'location' => $location,
			'customer' => $customer,
			'time' => __( 'just now', 'fomozo'),
			'order_id' => $order_id,
		]);
	}
}



