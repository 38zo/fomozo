<?php

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