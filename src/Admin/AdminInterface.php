<?php
/**
 * Admin Interface
 *
 * @package FOMOZO
 * @since 0.1.0
 */

namespace FOMOZO\Admin;

/**
 * Admin interface handler
 */
class AdminInterface {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize admin hooks
     */
    private function init_hooks() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'handle_form_submissions']);
        add_action('admin_notices', [$this, 'show_admin_notices']);
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('FOMOZO', 'fomozo'),
            __('FOMOZO', 'fomozo'),
            'manage_options',
            'fomozo',
            [$this, 'dashboard_page'],
            'dashicons-megaphone',
            30
        );
        
        // Dashboard (rename first submenu)
        add_submenu_page(
            'fomozo',
            __('Dashboard', 'fomozo'),
            __('Dashboard', 'fomozo'),
            'manage_options',
            'fomozo',
            [$this, 'dashboard_page']
        );
        
        // Campaigns
        add_submenu_page(
            'fomozo',
            __('Campaigns', 'fomozo'),
            __('Campaigns', 'fomozo'),
            'manage_options',
            'fomozo-campaigns',
            [$this, 'campaigns_page']
        );
        
        // Settings
        add_submenu_page(
            'fomozo',
            __('Settings', 'fomozo'),
            __('Settings', 'fomozo'),
            'manage_options',
            'fomozo-settings',
            [$this, 'settings_page']
        );
    }
    
    /**
     * Dashboard page
     */
    public function dashboard_page() {
        $stats = $this->get_dashboard_stats();
        ?>
        <div class="wrap">
            <h1 class="fomozo-dashboard-header">
                <?php _e('FOMOZO Dashboard', 'fomozo'); ?>
                <a href="<?php echo admin_url('admin.php?page=fomozo-campaigns&action=new'); ?>" class="page-title-action">
                    <?php _e('Create New Campaign', 'fomozo'); ?>
                </a>
            </h1>
            
            <div class="fomozo-dashboard">
                <div class="fomozo-stats">
                    <div class="stat-box">
                        <h3><?php echo esc_html($stats['total_campaigns']); ?></h3>
                        <p><?php _e('Total Campaigns', 'fomozo'); ?></p>
                    </div>
                    <div class="stat-box">
                        <h3><?php echo esc_html($stats['active_campaigns']); ?></h3>
                        <p><?php _e('Active Campaigns', 'fomozo'); ?></p>
                    </div>
                    <div class="stat-box">
                        <h3><?php echo esc_html($stats['total_impressions']); ?></h3>
                        <p><?php _e('Total Impressions', 'fomozo'); ?></p>
                    </div>
                    <div class="stat-box">
                        <h3><?php echo esc_html($stats['impressions_today']); ?></h3>
                        <p><?php _e('Today\'s Impressions', 'fomozo'); ?></p>
                    </div>
                </div>
                
                <?php if (empty($stats['total_campaigns'])): ?>
                <div class="fomozo-welcome">
                    <h2><?php _e('Welcome to FOMOZO!', 'fomozo'); ?></h2>
                    <p><?php _e('Get started by creating your first social proof campaign.', 'fomozo'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=fomozo-campaigns&action=new'); ?>" class="button button-primary button-hero">
                        <?php _e('Create Your First Campaign', 'fomozo'); ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Campaigns page
     */
    public function campaigns_page() {
        $action = $_GET['action'] ?? 'list';
        $campaign_id = $_GET['id'] ?? 0;
        
        switch ($action) {
            case 'new':
                $this->render_campaign_form();
                break;
            case 'edit':
                $this->render_campaign_form($campaign_id);
                break;
            case 'delete':
                $this->delete_campaign($campaign_id);
                $this->render_campaigns_list();
                break;
            default:
                $this->render_campaigns_list();
                break;
        }
    }
    
    /**
     * Render campaigns list
     */
    private function render_campaigns_list() {
        $campaigns = $this->get_campaigns();
        ?>
        <div class="wrap">
            <h1>
                <?php _e('Campaigns', 'fomozo'); ?>
                <a href="<?php echo admin_url('admin.php?page=fomozo-campaigns&action=new'); ?>" class="page-title-action">
                    <?php _e('Add New', 'fomozo'); ?>
                </a>
            </h1>
            
            <div class="fomozo-campaigns-list">
                <?php if (empty($campaigns)): ?>
                    <div class="no-campaigns">
                        <p><?php _e('No campaigns found. Create your first campaign!', 'fomozo'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=fomozo-campaigns&action=new'); ?>" class="button button-primary">
                            <?php _e('Create Campaign', 'fomozo'); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Name', 'fomozo'); ?></th>
                                <th><?php _e('Type', 'fomozo'); ?></th>
                                <th><?php _e('Status', 'fomozo'); ?></th>
                                <th><?php _e('Impressions', 'fomozo'); ?></th>
                                <th><?php _e('Created', 'fomozo'); ?></th>
                                <th><?php _e('Actions', 'fomozo'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($campaigns as $campaign): ?>
                            <tr>
                                <td><strong><?php echo esc_html($campaign->name); ?></strong></td>
                                <td><?php echo esc_html(ucfirst($campaign->type)); ?></td>
                                <td>
                                    <span class="status status-<?php echo esc_attr($campaign->status); ?>">
                                        <?php echo esc_html(ucfirst($campaign->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($this->get_campaign_impressions($campaign->id)); ?></td>
                                <td><?php echo esc_html(date('M j, Y', strtotime($campaign->created_at))); ?></td>
                                <td>
                                    <a href="<?php echo admin_url("admin.php?page=fomozo-campaigns&action=edit&id={$campaign->id}"); ?>" class="button button-small">
                                        <?php _e('Edit', 'fomozo'); ?>
                                    </a>
                                    <a href="<?php echo admin_url("admin.php?page=fomozo-campaigns&action=delete&id={$campaign->id}"); ?>" 
                                       class="button button-small button-link-delete" 
                                       onclick="return confirm('<?php _e('Are you sure you want to delete this campaign?', 'fomozo'); ?>')">
                                        <?php _e('Delete', 'fomozo'); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render campaign form
     */
    private function render_campaign_form($campaign_id = 0) {
        $campaign = $campaign_id ? $this->get_campaign($campaign_id) : null;
        $settings = $campaign ? json_decode($campaign->settings, true) : [];
        
        ?>
        <div class="wrap">
            <h1><?php echo $campaign ? __('Edit Campaign', 'fomozo') : __('New Campaign', 'fomozo'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('fomozo_save_campaign', 'fomozo_nonce'); ?>
                <input type="hidden" name="action" value="save_campaign">
                <input type="hidden" name="campaign_id" value="<?php echo esc_attr($campaign_id); ?>">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="campaign_name"><?php _e('Campaign Name', 'fomozo'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="campaign_name" name="campaign_name" 
                                   value="<?php echo esc_attr($campaign->name ?? ''); ?>" 
                                   class="regular-text" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="campaign_type"><?php _e('Campaign Type', 'fomozo'); ?></label>
                        </th>
                        <td>
                            <select id="campaign_type" name="campaign_type" required>
                                <option value="sales" <?php selected($campaign->type ?? '', 'sales'); ?>>
                                    <?php _e('Sales Notifications', 'fomozo'); ?>
                                </option>
                            </select>
                            <p class="description"><?php _e('More types available in Pro version', 'fomozo'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="template"><?php _e('Template', 'fomozo'); ?></label>
                        </th>
                        <td>
                            <select id="template" name="template">
                                <option value="bottom-left" <?php selected($settings['template'] ?? '', 'bottom-left'); ?>>
                                    <?php _e('Bottom Left Popup', 'fomozo'); ?>
                                </option>
                            </select>
                            <p class="description"><?php _e('More templates available in Pro version', 'fomozo'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="message_template"><?php _e('Message Template', 'fomozo'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="message_template" name="message_template" 
                                   value="<?php echo esc_attr($settings['message_template'] ?? '{customer} from {location} purchased {product} {time}'); ?>" 
                                   class="large-text">
                            <p class="description">
                                <?php _e('Use: {customer}, {location}, {product}, {time}', 'fomozo'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="delay"><?php _e('Display Delay (ms)', 'fomozo'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="delay" name="delay" 
                                   value="<?php echo esc_attr($settings['delay'] ?? 3000); ?>" 
                                   min="0" max="30000" step="500">
                            <p class="description"><?php _e('How long to wait before showing notification', 'fomozo'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="duration"><?php _e('Display Duration (ms)', 'fomozo'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="duration" name="duration" 
                                   value="<?php echo esc_attr($settings['duration'] ?? 5000); ?>" 
                                   min="1000" max="15000" step="500">
                            <p class="description"><?php _e('How long to show notification', 'fomozo'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Display Rules', 'fomozo'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="sitewide" value="1" 
                                       <?php checked($settings['display_rules']['sitewide'] ?? false); ?>>
                                <?php _e('Show on all pages', 'fomozo'); ?>
                            </label>
                            <p class="description"><?php _e('Advanced targeting available in Pro version', 'fomozo'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="status"><?php _e('Status', 'fomozo'); ?></label>
                        </th>
                        <td>
                            <select id="status" name="status">
                                <option value="active" <?php selected($campaign->status ?? '', 'active'); ?>>
                                    <?php _e('Active', 'fomozo'); ?>
                                </option>
                                <option value="inactive" <?php selected($campaign->status ?? '', 'inactive'); ?>>
                                    <?php _e('Inactive', 'fomozo'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button($campaign ? __('Update Campaign', 'fomozo') : __('Create Campaign', 'fomozo')); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('FOMOZO Settings', 'fomozo'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('fomozo_save_settings', 'fomozo_nonce'); ?>
                <input type="hidden" name="action" value="save_settings">
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Privacy Settings', 'fomozo'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="anonymize_users" value="1" 
                                       <?php checked(get_option('fomozo_anonymize_users', true)); ?>>
                                <?php _e('Anonymize customer names (e.g., "John D." instead of "John Doe")', 'fomozo'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Handle form submissions
     */
    public function handle_form_submissions() {
        if (!isset($_POST['action']) || !wp_verify_nonce($_POST['fomozo_nonce'] ?? '', 'fomozo_save_campaign') && !wp_verify_nonce($_POST['fomozo_nonce'] ?? '', 'fomozo_save_settings')) {
            return;
        }
        
        switch ($_POST['action']) {
            case 'save_campaign':
                $this->save_campaign();
                break;
            case 'save_settings':
                $this->save_settings();
                break;
        }
    }
    
    /**
     * Save campaign
     */
    private function save_campaign() {
        global $wpdb;
        
        $campaign_id = intval($_POST['campaign_id'] ?? 0);
        $name = sanitize_text_field($_POST['campaign_name']);
        $type = sanitize_text_field($_POST['campaign_type']);
        $status = sanitize_text_field($_POST['status']);
        
        $settings = [
            'template' => sanitize_text_field($_POST['template']),
            'message_template' => sanitize_text_field($_POST['message_template']),
            'delay' => intval($_POST['delay']),
            'duration' => intval($_POST['duration']),
            'display_rules' => [
                'sitewide' => !empty($_POST['sitewide'])
            ],
            'anonymize' => get_option('fomozo_anonymize_users', true)
        ];
        
        $data = [
            'name' => $name,
            'type' => $type,
            'status' => $status,
            'settings' => json_encode($settings)
        ];
        
        $table = $wpdb->prefix . 'fomozo_campaigns';
        
        if ($campaign_id) {
            $data['updated_at'] = current_time('mysql');
            $wpdb->update($table, $data, ['id' => $campaign_id]);
            $message = __('Campaign updated successfully!', 'fomozo');
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert($table, $data);
            $message = __('Campaign created successfully!', 'fomozo');
        }
        
        add_action('admin_notices', function() use ($message) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
        });
        
        // Redirect to campaigns list
        wp_redirect(admin_url('admin.php?page=fomozo-campaigns'));
        exit;
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        update_option('fomozo_anonymize_users', !empty($_POST['anonymize_users']));
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved successfully!', 'fomozo') . '</p></div>';
        });
    }
    
    /**
     * Show admin notices
     */
    public function show_admin_notices() {
        // Notices are added dynamically in other methods
    }
    
    /**
     * Get dashboard statistics
     */
    private function get_dashboard_stats() {
        global $wpdb;
        
        $campaigns_table = $wpdb->prefix . 'fomozo_campaigns';
        $analytics_table = $wpdb->prefix . 'fomozo_analytics';
        
        return [
            'total_campaigns' => $wpdb->get_var("SELECT COUNT(*) FROM {$campaigns_table}"),
            'active_campaigns' => $wpdb->get_var("SELECT COUNT(*) FROM {$campaigns_table} WHERE status = 'active'"),
            'total_impressions' => $wpdb->get_var("SELECT COUNT(*) FROM {$analytics_table} WHERE type = 'impression'"),
            'impressions_today' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$analytics_table} WHERE type = 'impression' AND DATE(created_at) = %s",
                current_time('Y-m-d')
            ))
        ];
    }
    
    /**
     * Get all campaigns
     */
    private function get_campaigns() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'fomozo_campaigns';
        return $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC");
    }
    
    /**
     * Get single campaign
     */
    private function get_campaign($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'fomozo_campaigns';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id));
    }
    
    /**
     * Get campaign impressions count
     */
    private function get_campaign_impressions($campaign_id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'fomozo_analytics';
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE campaign_id = %d AND type = 'impression'",
            $campaign_id
        ));
    }
    
    /**
     * Delete campaign
     */
    private function delete_campaign($campaign_id) {
        global $wpdb;
        
        $campaigns_table = $wpdb->prefix . 'fomozo_campaigns';
        $analytics_table = $wpdb->prefix . 'fomozo_analytics';
        
        // Delete campaign
        $wpdb->delete($campaigns_table, ['id' => $campaign_id]);
        
        // Delete related analytics
        $wpdb->delete($analytics_table, ['campaign_id' => $campaign_id]);
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Campaign deleted successfully!', 'fomozo') . '</p></div>';
        });
    }
}
