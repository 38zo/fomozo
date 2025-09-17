<?php
/**
 * Database Manager
 *
 * @package FOMOZO
 * @since 0.1.0
 */

namespace FOMOZO\Database;

/**
 * Provides CRUD helpers for campaigns and analytics
 */
class DatabaseManager {

	/** @var \wpdb */
	private $db;

	/** @var string */
	private $tableCampaigns;

	/** @var string */
	private $tableAnalytics;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->db = $wpdb;
		$this->tableCampaigns = $wpdb->prefix . 'fomozo_campaigns';
		$this->tableAnalytics = $wpdb->prefix . 'fomozo_analytics';
	}

	/**
	 * Fetch all campaigns (most recent first)
	 *
	 * @return array
	 */
	public function getCampaigns() {
		return $this->db->get_results("SELECT * FROM {$this->tableCampaigns} ORDER BY created_at DESC");
	}

	/**
	 * Fetch active campaigns that are within the date window
	 *
	 * @return array
	 */
	public function getActiveCampaigns() {
		$sql = $this->db->prepare(
			"SELECT * FROM {$this->tableCampaigns} " .
			"WHERE status = %s " .
			"AND (start_date IS NULL OR start_date <= NOW()) " .
			"AND (end_date IS NULL OR end_date >= NOW())",
			'active'
		);
		return $this->db->get_results($sql);
	}

	/**
	 * Fetch a single campaign by id
	 *
	 * @param int $id
	 * @return object|null
	 */
	public function getCampaign($id) {
		return $this->db->get_row(
			$this->db->prepare("SELECT * FROM {$this->tableCampaigns} WHERE id = %d", (int) $id)
		);
	}

	/**
	 * Create or update a campaign
	 *
	 * @param array $data Allowed keys: name, type, status, settings, start_date, end_date
	 * @param int|null $id If provided, updates the campaign
	 * @return int Campaign id
	 */
	public function saveCampaign(array $data, $id = null) {
		$allowed = ['name', 'type', 'status', 'settings', 'start_date', 'end_date'];
		$payload = array_intersect_key($data, array_flip($allowed));

		if ($id) {
			$payload['updated_at'] = current_time('mysql');
			$this->db->update($this->tableCampaigns, $payload, ['id' => (int) $id]);
			return (int) $id;
		}

		$payload['created_at'] = current_time('mysql');
		$this->db->insert($this->tableCampaigns, $payload);
		return (int) $this->db->insert_id;
	}

	/**
	 * Delete a campaign and its analytics
	 *
	 * @param int $id
	 * @return bool True if a campaign row was deleted
	 */
	public function deleteCampaign($id) {
		$this->db->delete($this->tableAnalytics, ['campaign_id' => (int) $id]);
		$deleted = $this->db->delete($this->tableCampaigns, ['id' => (int) $id]);
		return (bool) $deleted;
	}

	/**
	 * Track an impression event
	 *
	 * @param int $campaignId
	 * @param string $userIp
	 * @param string $userAgent
	 * @param string $pageUrl
	 * @return int Insert id
	 */
	public function trackImpression($campaignId, $userIp, $userAgent = '', $pageUrl = '') {
		$this->db->insert($this->tableAnalytics, [
			'campaign_id' => (int) $campaignId,
			'type' => 'impression',
			'user_ip' => $userIp,
			'user_agent' => $userAgent,
			'page_url' => $pageUrl,
			'created_at' => current_time('mysql')
		]);
		return (int) $this->db->insert_id;
	}

	/**
	 * Count impressions for a campaign
	 *
	 * @param int $campaignId
	 * @return int
	 */
	public function getCampaignImpressions($campaignId) {
		return (int) $this->db->get_var(
			$this->db->prepare(
				"SELECT COUNT(*) FROM {$this->tableAnalytics} WHERE campaign_id = %d AND type = 'impression'",
				(int) $campaignId
			)
		);
	}
}


