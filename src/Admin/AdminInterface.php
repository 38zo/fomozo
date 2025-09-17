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
     * Integrations page
     */
    public function integrations_page() {
        $manager = new \FOMOZO\Integrations\IntegrationManager();
        $all = $manager->get_all();
        $query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        if (!empty($_POST['fomozo_toggle_integration']) && check_admin_referer('fomozo_integrations')) {
            $id = sanitize_text_field($_POST['integration_id'] ?? '');
            $action = sanitize_text_field($_POST['integration_action'] ?? '');
            if ($action === 'activate') { $manager->activate($id); }
            if ($action === 'deactivate') { $manager->deactivate($id); }
            wp_safe_redirect(admin_url('admin.php?page=fomozo-integrations'));
            exit;
        }
        ?>
        <div class="wrap">
            <h1><?php _e('Integrations', 'fomozo'); ?></h1>
            <form method="get" action="" style="margin:10px 0;">
                <input type="hidden" name="page" value="fomozo-integrations" />
                <p class="search-box">
                    <label class="screen-reader-text" for="fomozo-int-search"><?php _e('Search Integrations', 'fomozo'); ?></label>
                    <input type="search" id="fomozo-int-search" name="s" value="<?php echo esc_attr($query); ?>" />
                    <input type="submit" class="button" value="<?php _e('Search Integrations', 'fomozo'); ?>" />
                </p>
            </form>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Integration', 'fomozo'); ?></th>
                        <th><?php _e('Description', 'fomozo'); ?></th>
                        <th><?php _e('Status', 'fomozo'); ?></th>
                        <th><?php _e('Actions', 'fomozo'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all as $id => $integration):
                        $title = $integration->get_title();
                        if ($query && stripos($title, $query) === false) { continue; }
                        $active = $manager->is_active($id);
                        $available = $integration->is_available();
                        ?>
                        <tr>
                            <td style="display:flex;align-items:center;gap:10px;">
                                <img src="<?php echo esc_url($integration->get_logo_url()); ?>" alt="" style="width:28px;height:28px;object-fit:contain;" />
                                <strong><?php echo esc_html($integration->get_title()); ?></strong>
                            </td>
                            <td><?php echo esc_html($integration->get_description()); ?></td>
                            <td>
                                <?php if (!$available): ?>
                                    <span class="status status-inactive"><?php _e('Unavailable', 'fomozo'); ?></span>
                                <?php else: ?>
                                    <span class="status <?php echo $active ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $active ? esc_html__('Active', 'fomozo') : esc_html__('Inactive', 'fomozo'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($available): ?>
                                <form method="post" style="display:inline;">
                                    <?php wp_nonce_field('fomozo_integrations'); ?>
                                    <input type="hidden" name="fomozo_toggle_integration" value="1" />
                                    <input type="hidden" name="integration_id" value="<?php echo esc_attr($id); ?>" />
                                    <?php if ($active): ?>
                                        <input type="hidden" name="integration_action" value="deactivate" />
                                        <button class="button"><?php _e('Deactivate', 'fomozo'); ?></button>
                                    <?php else: ?>
                                        <input type="hidden" name="integration_action" value="activate" />
                                        <button class="button button-primary"><?php _e('Activate', 'fomozo'); ?></button>
                                    <?php endif; ?>
                                </form>
                                <?php else: ?>
                                    <em><?php _e('Install the dependency to enable.', 'fomozo'); ?></em>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
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

        // Integrations
        add_submenu_page(
            'fomozo',
            __('Integrations', 'fomozo'),
            __('Integrations', 'fomozo'),
            'manage_options',
            'fomozo-integrations',
            [$this, 'integrations_page']
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
        $schema = \FOMOZO\Core\Settings::get_schema();
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : array_key_first($schema['tabs']);
        if (!isset($schema['tabs'][$active_tab])) { $active_tab = array_key_first($schema['tabs']); }
        ?>
        <div class="wrap">
            <h1><?php _e('FOMOZO Settings', 'fomozo'); ?></h1>

            <h2 class="nav-tab-wrapper">
                <?php foreach ($schema['tabs'] as $tab_id => $tab): ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=fomozo-settings&tab=' . $tab_id)); ?>" class="nav-tab <?php echo $tab_id === $active_tab ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($tab['title']); ?>
                    </a>
                <?php endforeach; ?>
            </h2>

            <form method="post" action="">
                <?php wp_nonce_field('fomozo_save_settings', 'fomozo_nonce'); ?>
                <input type="hidden" name="action" value="save_settings">
                <input type="hidden" name="tab" value="<?php echo esc_attr($active_tab); ?>">

                <?php foreach ($schema['tabs'][$active_tab]['sections'] as $section_id => $section): ?>
                    <h2><?php echo esc_html($section['title']); ?></h2>
                    <table class="form-table">
                        <?php foreach ($section['fields'] as $field): ?>
                            <tr>
                                <th scope="row">
                                    <label for="<?php echo esc_attr($field['id']); ?>"><?php echo esc_html($field['title']); ?></label>
                                </th>
                                <td>
                                    <?php echo $this->render_field($field); // escaped in renderer ?>
                                    <?php if (!empty($field['desc'])): ?>
                                        <p class="description"><?php echo esc_html($field['desc']); ?></p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endforeach; ?>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    private function render_field($field) {
        $id = $field['id'];
        $type = $field['type'] ?? 'text';
        $value = \FOMOZO\Core\Settings::get($id, $field['default'] ?? '');
        $attrs = $field['attrs'] ?? [];

        $attr_html = '';
        foreach ($attrs as $k => $v) {
            $attr_html .= ' ' . esc_attr($k) . '="' . esc_attr($v) . '"';
        }

        switch ($type) {
            case 'checkbox':
                return '<label><input type="checkbox" id="' . esc_attr($id) . '" name="' . esc_attr($id) . '" value="1" ' . checked((int)$value, 1, false) . '> ' . esc_html($field['title']) . '</label>';
            case 'number':
                return '<input type="number" id="' . esc_attr($id) . '" name="' . esc_attr($id) . '" value="' . esc_attr($value) . '"' . $attr_html . ' />';
            case 'select':
                $html = '<select id="' . esc_attr($id) . '" name="' . esc_attr($id) . '"' . $attr_html . '>';
                $choices = $field['choices'] ?? [];
                foreach ($choices as $key => $label) {
                    $html .= '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . esc_html($label) . '</option>';
                }
                $html .= '</select>';
                return $html;
            case 'text':
            default:
                return '<input type="text" id="' . esc_attr($id) . '" name="' . esc_attr($id) . '" value="' . esc_attr($value) . '"' . $attr_html . ' />';
        }
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
        $schema = \FOMOZO\Core\Settings::get_schema();
        foreach ($schema['tabs'] as $tab) {
            foreach ($tab['sections'] as $section) {
                foreach ($section['fields'] as $field) {
                    $id = $field['id'];
                    $raw = $_POST[$id] ?? null;
                    $sanitized = \FOMOZO\Core\Settings::sanitize_field($field, $raw);
                    update_option($id, $sanitized);
                }
            }
        }

        do_action('fomozo_settings_saved');

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

        // Compute start of today and start of tomorrow in site timezone
        $tz_timestamp = current_time('timestamp');
        $start_of_today = strtotime('today', $tz_timestamp);
        $start_of_tomorrow = strtotime('tomorrow', $tz_timestamp);
        $start_str = date('Y-m-d H:i:s', $start_of_today);
        $end_str = date('Y-m-d H:i:s', $start_of_tomorrow);
        
        return [
            'total_campaigns' => $wpdb->get_var("SELECT COUNT(*) FROM {$campaigns_table}"),
            'active_campaigns' => $wpdb->get_var("SELECT COUNT(*) FROM {$campaigns_table} WHERE status = 'active'"),
            'total_impressions' => $wpdb->get_var("SELECT COUNT(*) FROM {$analytics_table} WHERE type = 'impression'"),
            'impressions_today' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$analytics_table} WHERE type = 'impression' AND created_at >= %s AND created_at < %s",
                $start_str,
                $end_str
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
