<?php
namespace FOMOZO\Integrations;

interface IntegrationInterface {
	public function get_id();
	public function get_title();
	public function get_description();
	public function get_logo_url();
	public function is_available();
	public function is_active();
	public function activate();
	public function deactivate();
	public function register_hooks();
}


