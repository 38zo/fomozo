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

	/**
	 * Check if required tables exist
	 *
	 * @return bool
	 */
	public function tablesExist() {
		$campaigns_exists = $this->db->get_var(
			$this->db->prepare(
				"SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
				DB_NAME,
				$this->tableCampaigns
			)
		);

		$analytics_exists = $this->db->get_var(
			$this->db->prepare(
				"SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
				DB_NAME,
				$this->tableAnalytics
			)
		);

		return (bool) $campaigns_exists && (bool) $analytics_exists;
	}

	/**
	 * Ensure tables exist, recreate if missing
	 *
	 * @return bool True if tables exist or were created successfully
	 */
	public function ensureTablesExist() {
		if ( $this->tablesExist() ) {
			return true;
		}

		// Tables don't exist, recreate them
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		
		$charset_collate = $this->db->get_charset_collate();
		
		// Campaigns table
		$campaigns_sql = "CREATE TABLE {$this->tableCampaigns} (
			id int(11) NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			type varchar(50) NOT NULL,
			status varchar(20) DEFAULT 'active',
			settings longtext,
			start_date datetime DEFAULT NULL,
			end_date datetime DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY type (type),
			KEY status (status)
		) $charset_collate;";
		
		// Analytics table
		$analytics_sql = "CREATE TABLE {$this->tableAnalytics} (
			id int(11) NOT NULL AUTO_INCREMENT,
			campaign_id int(11) NOT NULL,
			type varchar(50) NOT NULL,
			user_ip varchar(45),
			user_agent text,
			page_url varchar(500),
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY campaign_id (campaign_id),
			KEY type (type),
			KEY created_at (created_at)
		) $charset_collate;";
		
		$result1 = dbDelta( $campaigns_sql );
		$result2 = dbDelta( $analytics_sql );
		
		// Check if tables were created successfully
		return $this->tablesExist();
	}

	/**
	 * Get table names for debugging
	 *
	 * @return array
	 */
	public function getTableNames() {
		return [
			'campaigns' => $this->tableCampaigns,
			'analytics' => $this->tableAnalytics
		];
	}
}


