<?php
/**
 * Settings schema and helpers
 *
 * @package FOMOZO
 * @since 0.1.0
 */

namespace FOMOZO\Core;

class Settings {

	/**
	 * Return settings schema with tabs/sections/fields
	 * Schema shape:
	 * [
	 *   'tabs' => [
	 *     'general' => ['title' => 'General', 'sections' => [
	 *         'privacy' => ['title' => 'Privacy', 'fields' => [
	 *            ['id'=>'fomozo_anonymize_users','title'=>'...','type'=>'checkbox','default'=>true,'desc'=>'...']
	 *         ]]
	 *     ]]
	 * ]
	 */
	public static function get_schema() {
		$schema = [
			'tabs' => [
				'general' => [
					'title' => __('General', 'fomozo'),
					'sections' => [
						'privacy' => [
							'title' => __('Privacy', 'fomozo'),
							'fields' => [
								[
									'id' => 'fomozo_anonymize_users',
									'title' => __('Anonymize customer names', 'fomozo'),
									'type' => 'checkbox',
									'default' => true,
									'desc' => __('Show names like "John D." instead of full names.', 'fomozo')
								]
							]
						],
						'behaviour' => [
							'title' => __('Behaviour', 'fomozo'),
							'fields' => [
								[
									'id' => 'fomozo_gap_ms',
									'title' => __('Gap Between Popups (ms)', 'fomozo'),
									'type' => 'number',
									'default' => 4000,
									'attrs' => ['min' => 0, 'max' => 60000, 'step' => 250],
									'desc' => __('Delay between popups after one hides and before the next shows.', 'fomozo')
								]
							]
						]
					]
				]
			]
		];

		/**
		 * Allow addons to modify or extend settings schema
		 */
		return apply_filters('fomozo_settings_schema', $schema);
	}

	/**
	 * Get all settings values using schema defaults
	 */
	public static function get_all() {
		$schema = self::get_schema();
		$values = [];
		foreach ($schema['tabs'] as $tab) {
			foreach ($tab['sections'] as $section) {
				foreach ($section['fields'] as $field) {
					$id = $field['id'];
					$default = isset($field['default']) ? $field['default'] : null;
					$values[$id] = get_option($id, $default);
				}
			}
		}
		return apply_filters('fomozo_get_settings', $values);
	}

	/**
	 * Get single setting value
	 */
	public static function get($id, $fallback = null) {
		$values = self::get_all();
		$value = array_key_exists($id, $values) ? $values[$id] : get_option($id, $fallback);
		return apply_filters('fomozo_get_setting', $value, $id);
	}

	/**
	 * Sanitize a value per field definition
	 */
	public static function sanitize_field($field, $raw) {
		switch ($field['type']) {
			case 'checkbox':
				return !empty($raw) ? 1 : 0;
			case 'number':
				$val = intval($raw);
				if (isset($field['attrs']['min'])) $val = max($field['attrs']['min'], $val);
				if (isset($field['attrs']['max'])) $val = min($field['attrs']['max'], $val);
				return $val;
			case 'select':
				$choices = isset($field['choices']) ? array_keys($field['choices']) : [];
				$val = sanitize_text_field((string)$raw);
				return in_array($val, $choices, true) ? $val : (isset($field['default']) ? $field['default'] : '');
			case 'text':
			default:
				return sanitize_text_field((string)$raw);
		}
	}
}


