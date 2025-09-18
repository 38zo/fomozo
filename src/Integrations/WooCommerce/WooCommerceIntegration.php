<?php
namespace FOMOZO\Integrations\WooCommerce;

use FOMOZO\Integrations\IntegrationInterface;

class WooCommerceIntegration implements IntegrationInterface {
	public function get_id() { return 'woocommerce'; }
	public function get_title() { return 'WooCommerce'; }
	public function get_description() { return __('Pulls recent orders to display sales notifications.', 'fomozo'); }
	public function get_logo_url() { return defined('FOMOZO_URL') ? FOMOZO_URL . 'assets/logos/woocommerce.png' : ''; }
	public function is_available() { return class_exists('WooCommerce'); }
	public function is_active() { return in_array($this->get_id(), get_option('fomozo_integrations_active', []), true); }
	public function activate() {}
	public function deactivate() {}

	public function register_hooks() {
		if (!$this->is_available()) { return; }
		// Listen to order completed
		add_action('woocommerce_thankyou', [$this, 'handle_order'], 10, 1);
		// Provide notifications for core generator via filter
		add_filter('fomozo_external_notifications', function($list, $campaigns) {
			$woos = WooProvider::build_notifications($campaigns);
			return array_merge(is_array($list) ? $list : [], $woos);
		}, 10, 2);
	}

	public function handle_order($order_id) {
		if (!$order_id) { return; }
		$order = function_exists('wc_get_order') ? wc_get_order($order_id) : null;
		if (!$order) { return; }

		$items = $order->get_items();
		$product_name = '';
		foreach ($items as $item) { $product_name = $item->get_name(); break; }
		$billing = $order->get_address('billing');
		$location = trim(($billing['city'] ?? '') . ', ' . ($billing['country'] ?? ''));
		$customer = trim(($billing['first_name'] ?? 'Customer') . ' ' . substr(($billing['last_name'] ?? ''), 0, 1) . '.');

		do_action('fomozo_track_event', 'sale', [
			'product' => $product_name,
			'location' => $location,
			'customer' => $customer,
			'time' => __('just now', 'fomozo'),
			'order_id' => $order_id,
		]);
	}
}

// Add provider method to return notifications from recent orders
namespace FOMOZO\Integrations\WooCommerce;

class WooProvider {
    public static function build_notifications($campaigns) {
        if (!function_exists('wc_get_orders')) { return []; }
        // Only campaigns configured for Woo sales (or generic sales) are considered
        $woocommerceCampaigns = array_filter((array)$campaigns, function($c) {
            $s = json_decode($c->settings ?? '{}', true);
            $int = $s['integration'] ?? '';
            $sub = $s['campaign_subtype'] ?? '';
            return $c->type === 'sales' && ($int === '' || $int === 'woocommerce') && ($sub === '' || $sub === 'sales');
        });
        if (empty($woocommerceCampaigns)) { return []; }

        $orders = wc_get_orders([
			'limit' => 15,
			'orderby' => 'date',
			'order' => 'DESC',
			// Include common sales-related statuses to avoid empty results on fresh stores
			'status' => array('completed', 'processing', 'on-hold', 'pending'),
		]);
		if (empty($orders)) { return []; }

		$notifications = [];
        foreach ($woocommerceCampaigns as $campaign) {
			if ($campaign->type !== 'sales') { continue; }
			$settings = json_decode($campaign->settings, true);
			$order = $orders[array_rand($orders)];
			$payload = self::order_payload($order);
			if (!$payload) { continue; }
			$template = $settings['message_template'] ?? '{customer} from {location} purchased {product} {time}';
			$message = str_replace(['{customer}','{location}','{product}','{time}'], [$payload['customer'],$payload['location'],$payload['product'],$payload['time']], $template);
			$notifications[] = [
				'id' => $campaign->id,
				'type' => 'sales',
				'template' => $settings['template'] ?? 'bottom-left',
				'message' => $message,
				'delay' => $settings['delay'] ?? 3000,
				'duration' => $settings['duration'] ?? 5000,
				'settings' => $settings,
			];
		}
		return $notifications;
	}

	private static function order_payload($order) {
		if (!$order) { return null; }
		$items = $order->get_items();
		$product_name = '';
		foreach ($items as $item) { $product_name = $item->get_name(); break; }
		$billing = $order->get_address('billing');
		$city = trim($billing['city'] ?? '');
		$country = trim($billing['country'] ?? '');
		$location = trim($city . (empty($city) || empty($country) ? '' : ', ') . $country);
		$anonymize = get_option('fomozo_anonymize_users', true);
		$first = trim($billing['first_name'] ?? 'Customer');
		$last = trim($billing['last_name'] ?? '');
		$customer = $anonymize ? ($first . ' ' . (empty($last) ? '' : strtoupper($last[0]) . '.')) : trim($first . ' ' . $last);
		$time = human_time_diff(strtotime($order->get_date_created()), current_time('timestamp')) . ' ' . __('ago', 'fomozo');
		return [
			'product' => $product_name ?: __('a product', 'fomozo'),
			'location' => $location ?: __('somewhere', 'fomozo'),
			'customer' => $customer,
			'time' => $time,
		];
	}
}


