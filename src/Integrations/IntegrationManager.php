<?php
namespace FOMOZO\Integrations;

class IntegrationManager {

	/** @var IntegrationInterface[] */
	private $integrations = [];

	public function __construct() {
		// Allow third-parties to register
		do_action( 'fomozo_integrations_register', $this);
		$this->load_active_state();
	}

	public function register(IntegrationInterface $integration) {
		$this->integrations[$integration->get_id()] = $integration;
	}

	public function get_all() {
		return $this->integrations;
	}

	public function get_active_ids() {
		$ids = get_option( 'fomozo_integrations_active', []);
		return is_array( $ids) ? $ids : [];
	}

	public function is_active( $id) {
		return in_array( $id, $this->get_active_ids(), true);
	}

	public function activate( $id) {
		$ids = $this->get_active_ids();
		if (!in_array( $id, $ids, true) ) {
			$ids[] = $id;
			update_option( 'fomozo_integrations_active', $ids);
			if (isset( $this->integrations[$id]) ) {
				$this->integrations[$id]->activate();
				$this->integrations[$id]->register_hooks();
			}
		}
	}

	public function deactivate( $id) {
		$ids = array_values(array_filter( $this->get_active_ids(), function( $x ) use ( $id ) { return $x !== $id; } ) );
		update_option( 'fomozo_integrations_active', $ids );
		if ( isset( $this->integrations[$id] ) ) {
			$this->integrations[$id]->deactivate();
		}
	}

	private function load_active_state() {
		$active = $this->get_active_ids();
		foreach ( $active as $id ) {
			if ( isset( $this->integrations[$id] ) ) {
				$this->integrations[$id]->register_hooks();
			}
		}
	}
}


