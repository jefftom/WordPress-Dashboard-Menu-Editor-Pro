<?php
/**
 * Plugin Name: Advanced Admin Menu Editor
 * Plugin URI: https://yourwebsite.com/advanced-admin-menu-editor
 * Description: Customize your WordPress admin menu with support for multiple configurations.
 * Version: 2.0.0
 * Author: Your Name
 * License: GPL-2.0+
 * Text Domain: advanced-admin-menu-editor
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('CAME_VERSION', '2.0.0');
define('CAME_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CAME_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class
 */
class Advanced_Admin_Menu_Editor {
    /**
     * Instance of this class.
     *
     * @var object
     */
    protected static $instance = null;

    /**
     * All menu configurations
     *
     * @var array
     */
    private $all_menu_configs = array();
    
    /**
     * Active configuration ID
     *
     * @var string
     */
    private $active_config_id = '';
    
    /**
     * Flag to prevent duplicate processing
     * 
     * @var bool
     */
    private $menu_processed = false;

    /**
     * Initialize the plugin.
     */
    private function __construct() {
        // Load saved menu configurations
        $this->all_menu_configs = get_option('came_menu_configs', array());
        
        // Set the active configuration ID from user meta (if exists)
        $user_id = get_current_user_id();
        if ($user_id) {
            $user_active_config = get_user_meta($user_id, 'came_active_config', true);
            if (!empty($user_active_config) && isset($this->all_menu_configs[$user_active_config])) {
                $this->active_config_id = $user_active_config;
            }
        }
        
        // Setup default configuration if none exist
        if (empty($this->all_menu_configs)) {
            $this->setup_default_config();
        }
        
        // Add admin menu item
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Filter admin menu
        add_action('admin_menu', array($this, 'modify_admin_menu'), 999);

        // Register AJAX handlers
        add_action('wp_ajax_came_save_menu', array($this, 'ajax_save_menu'));
        add_action('wp_ajax_came_reset_menu', array($this, 'ajax_reset_menu'));
        add_action('wp_ajax_came_add_config', array($this, 'ajax_add_config'));
        add_action('wp_ajax_came_delete_config', array($this, 'ajax_delete_config'));
        add_action('wp_ajax_came_load_config', array($this, 'ajax_load_config'));

        // Add settings link to plugins page
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
    }
    
    /**
     * Setup default configuration
     */
    private function setup_default_config() {
        $this->all_menu_configs = array(
            'default' => array(
                'name' => 'Default Configuration',
                'menu_items' => array(),
                'allowed_roles' => array('administrator'),
                'is_active' => true
            )
        );
        
        // Save default configuration
        update_option('came_menu_configs', $this->all_menu_configs);
        
        // Set active configuration
        $this->active_config_id = 'default';
    }
    
    /**
     * Get active configuration
     */
    public function get_active_config() {
        // If no active config set, find the first active one or use the first available
        if (empty($this->active_config_id)) {
            foreach ($this->all_menu_configs as $config_id => $config) {
                if (isset($config['is_active']) && $config['is_active']) {
                    $this->active_config_id = $config_id;
                    break;
                }
            }
            
            // If still no active config, use the first one
            if (empty($this->active_config_id) && !empty($this->all_menu_configs)) {
                $this->active_config_id = array_key_first($this->all_menu_configs);
            }
        }
        
        // Return the active configuration or empty array if none found
        return isset($this->all_menu_configs[$this->active_config_id]) 
               ? $this->all_menu_configs[$this->active_config_id] 
               : array();
    }

    /**
     * Return an instance of this class.
     *
     * @return object A single instance of this class.
     */
    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Add the admin menu item
     */
    public function add_admin_menu() {
        add_submenu_page(
            'options-general.php',
            __('Admin Menu Editor', 'advanced-admin-menu-editor'),
            __('Admin Menu Editor', 'advanced-admin-menu-editor'),
            'manage_options',
            'advanced-admin-menu-editor',
            array($this, 'display_plugin_admin_page')
        );
    }

    /**
     * Add settings link to plugin list
     *
     * @param array $links Array of plugin action links
     * @return array Modified array of plugin action links
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=advanced-admin-menu-editor') . '">' . __('Settings', 'advanced-admin-menu-editor') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_assets($hook) {
        if ('settings_page_advanced-admin-menu-editor' !== $hook) {
            return;
        }

        // Enqueue jQuery UI and Sortable
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-draggable');
        wp_enqueue_script('jquery-ui-droppable');

        // Check if CSS file exists before enqueueing
        $css_file = CAME_PLUGIN_DIR . 'assets/css/admin.css';
        if (file_exists($css_file)) {
            wp_enqueue_style(
                'came-admin-css',
                CAME_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                CAME_VERSION
            );
        }

        // Check if JS file exists before enqueueing
        $js_file = CAME_PLUGIN_DIR . 'assets/js/admin.js';
        if (file_exists($js_file)) {
            wp_enqueue_script(
                'came-admin-js',
                CAME_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery', 'jquery-ui-sortable'),
                CAME_VERSION,
                true
            );
            
            // Get active configuration
            $active_config = $this->get_active_config();
            
            // Get admin menu structure with unique IDs
            $admin_menu = $this->get_admin_menu_structure();
            
            // Pass data to script
            wp_localize_script('came-admin-js', 'came_data', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('came_nonce'),
                'current_user_roles' => $this->get_current_user_roles(),
                'dashicons' => $this->get_available_dashicons(),
                'admin_menu' => $admin_menu,
                'saved_menu' => isset($active_config['menu_items']) ? $active_config['menu_items'] : array(),
                'active_config_id' => $this->active_config_id,
                'all_configs' => $this->all_menu_configs,
                'text_confirm_reset' => __('Are you sure you want to reset the menu to default?', 'advanced-admin-menu-editor'),
                'text_confirm_delete' => __('Are you sure you want to delete this configuration?', 'advanced-admin-menu-editor')
            ));
        }

        // Enqueue dashicons
        wp_enqueue_style('dashicons');
    }

    /**
     * Display the admin page
     */
    public function display_plugin_admin_page() {
        // Check if admin page file exists
        $admin_page_file = CAME_PLUGIN_DIR . 'includes/admin-page.php';
        
        if (file_exists($admin_page_file)) {
            include_once $admin_page_file;
        } else {
            // Fallback if admin page file doesn't exist
            echo '<div class="wrap">';
            echo '<h1>' . esc_html__('Admin Menu Editor', 'advanced-admin-menu-editor') . '</h1>';
            echo '<div class="notice notice-error"><p>' . 
                 esc_html__('Error: Admin page template file not found. Please reinstall the plugin.', 'advanced-admin-menu-editor') . 
                 '</p></div>';
            echo '</div>';
        }
    }

    /**
     * Get list of all available dashicons
     *
     * @return array List of dashicons
     */
    private function get_available_dashicons() {
        return array(
            'dashicons-admin-appearance',
            'dashicons-admin-collapse',
            'dashicons-admin-comments',
            'dashicons-admin-customizer',
            'dashicons-admin-generic',
            'dashicons-admin-home',
            'dashicons-admin-links',
            'dashicons-admin-media',
            'dashicons-admin-multisite',
            'dashicons-admin-network',
            'dashicons-admin-page',
            'dashicons-admin-plugins',
            'dashicons-admin-post',
            'dashicons-admin-settings',
            'dashicons-admin-site',
            'dashicons-admin-tools',
            'dashicons-admin-users',
            'dashicons-dashboard',
            'dashicons-menu',
            'dashicons-format-standard',
            'dashicons-format-image',
            'dashicons-format-gallery',
            'dashicons-format-audio',
            'dashicons-format-video',
            'dashicons-format-chat'
        );
    }

    /**
     * Get current user roles
     *
     * @return array User roles
     */
    private function get_current_user_roles() {
        $roles = array();
        $user = wp_get_current_user();
        
        if (!empty($user->roles) && is_array($user->roles)) {
            $roles = $user->roles;
        }
        
        return $roles;
    }

    /**
     * Get all available user roles
     *
     * @return array All WordPress user roles
     */
    public function get_all_user_roles() {
        global $wp_roles;
        
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        
        return $wp_roles->get_names();
    }

    /**
     * Get original WordPress admin menu structure with guaranteed unique IDs
     *
     * @return array Admin menu structure
     */
    private function get_admin_menu_structure() {
        global $menu, $submenu;
        
        // Store original menu
        $original_menu = $menu;
        $original_submenu = $submenu;
        
        // Get menu structure
        $menu_structure = array();
        
        if (!empty($original_menu) && is_array($original_menu)) {
            foreach ($original_menu as $menu_key => $menu_item) {
                if (empty($menu_item[0]) || empty($menu_item[2])) {
                    continue;
                }
                
                // Clean up menu item name
                $menu_name = preg_replace('/<span.*?>.*?<\/span>/i', '', $menu_item[0]);
                $menu_name = trim(strip_tags($menu_name));
                
                // Create a guaranteed unique ID using URL and a random ID
                $menu_id = 'menu-' . sanitize_key($menu_item[2]) . '-' . uniqid();
                
                $item = array(
                    'id' => $menu_id,
                    'name' => $menu_name,
                    'url' => $menu_item[2],
                    'capability' => $menu_item[1],
                    'icon' => isset($menu_item[6]) ? $menu_item[6] : '',
                    'position' => intval($menu_key),
                    'submenu' => array()
                );
                
                // Add submenu items if they exist
                if (isset($original_submenu[$menu_item[2]]) && is_array($original_submenu[$menu_item[2]])) {
                    foreach ($original_submenu[$menu_item[2]] as $submenu_key => $submenu_item) {
                        if (empty($submenu_item[0]) || empty($submenu_item[2])) continue;
                        
                        $submenu_name = preg_replace('/<span.*?>.*?<\/span>/i', '', $submenu_item[0]);
                        $submenu_name = trim(strip_tags($submenu_name));
                        
                        // Create a guaranteed unique ID using parent URL, submenu URL and a random ID
                        $submenu_id = 'submenu-' . sanitize_key($menu_item[2]) . '-' . sanitize_key($submenu_item[2]) . '-' . uniqid();
                        
                        $item['submenu'][] = array(
                            'id' => $submenu_id,
                            'name' => $submenu_name,
                            'url' => $submenu_item[2],
                            'capability' => $submenu_item[1],
                            'position' => intval($submenu_key)
                        );
                    }
                }
                
                $menu_structure[] = $item;
            }
        }
        
        return $menu_structure;
    }

    /**
     * Modify admin menu based on saved settings
     */
    public function modify_admin_menu() {
        // Prevent duplicate processing
        if ($this->menu_processed) {
            return;
        }
        
        // Mark as processed to prevent multiple calls
        $this->menu_processed = true;
        
        global $menu, $submenu;
        
        // Find which configurations apply to the current user
        $current_user_roles = $this->get_current_user_roles();
        $applicable_configs = array();
        
        foreach ($this->all_menu_configs as $config_id => $config) {
            if (!isset($config['is_active']) || !$config['is_active']) {
                continue;
            }
            
            $allowed_roles = isset($config['allowed_roles']) ? $config['allowed_roles'] : array();
            
            // Check if current user has an allowed role
            $can_see_config = false;
            foreach ($current_user_roles as $role) {
                if (in_array($role, $allowed_roles)) {
                    $can_see_config = true;
                    break;
                }
            }
            
            if ($can_see_config && !empty($config['menu_items'])) {
                $applicable_configs[] = $config;
            }
        }
        
        // If no applicable configurations found, return
        if (empty($applicable_configs)) {
            return;
        }
        
        // Use the first applicable configuration
        $active_config = $applicable_configs[0];
        
        // Store original menu
        $original_menu = $menu;
        $original_submenu = $submenu;
        
        // Clear the current menu
        $menu = array();
        
        // Build a lookup table for original menu items by URL
        $menu_lookup = array();
        foreach ($original_menu as $position => $item) {
            if (isset($item[2])) {
                $menu_lookup[$item[2]] = $item;
            }
        }
        
        // Build a lookup table for submenu items by parent URL and item URL
        $submenu_lookup = array();
        foreach ($original_submenu as $parent => $items) {
            if (!isset($submenu_lookup[$parent])) {
                $submenu_lookup[$parent] = array();
            }
            
            foreach ($items as $position => $item) {
                if (isset($item[2])) {
                    $submenu_lookup[$parent][$item[2]] = $item;
                }
            }
        }
        
        // Process each menu item according to settings
        $new_menu_order = 10;
        foreach ($active_config['menu_items'] as $item) {
            // Skip hidden items
            if (isset($item['hidden']) && $item['hidden']) {
                continue;
            }
            
            // If item has specific roles defined, check if user has one of them
            if (isset($item['roles']) && !empty($item['roles'])) {
                $show_item = false;
                foreach ($current_user_roles as $role) {
                    if (in_array($role, $item['roles'])) {
                        $show_item = true;
                        break;
                    }
                }
                if (!$show_item) {
                    continue;
                }
            }
            
            // Find original menu item
            if (!isset($menu_lookup[$item['url']])) {
                continue;
            }
            
            $original_item = $menu_lookup[$item['url']];
            
            // Modify menu item
            $modified_item = $original_item;
            
            // Update name if set
            if (isset($item['name']) && !empty($item['name'])) {
                $modified_item[0] = $item['name'];
            }
            
            // Add to menu at the specified position
            $menu[$new_menu_order] = $modified_item;
            $new_menu_order += 10;
            
            // Clear existing submenu to prevent duplicates
            if (isset($submenu[$item['url']])) {
                $submenu[$item['url']] = array();
            }
            
            // Process submenu if it exists
            if (isset($item['submenu']) && !empty($item['submenu']) && isset($submenu_lookup[$item['url']])) {
                // Create new submenu array if it doesn't exist
                if (!isset($submenu[$item['url']])) {
                    $submenu[$item['url']] = array();
                }
                
                // Process each submenu item
                $new_submenu_order = 10;
                foreach ($item['submenu'] as $sub_item) {
                    // Skip hidden submenu items
                    if (isset($sub_item['hidden']) && $sub_item['hidden']) {
                        continue;
                    }
                    
                    // If submenu item has specific roles defined, check if user has one of them
                    if (isset($sub_item['roles']) && !empty($sub_item['roles'])) {
                        $show_subitem = false;
                        foreach ($current_user_roles as $role) {
                            if (in_array($role, $sub_item['roles'])) {
                                $show_subitem = true;
                                break;
                            }
                        }
                        if (!$show_subitem) {
                            continue;
                        }
                    }
                    
                    // Find original submenu item
                    if (isset($submenu_lookup[$item['url']][$sub_item['url']])) {
                        $original_sub_item = $submenu_lookup[$item['url']][$sub_item['url']];
                        
                        // Modify submenu item
                        $modified_sub_item = $original_sub_item;
                        
                        // Update name if set
                        if (isset($sub_item['name']) && !empty($sub_item['name'])) {
                            $modified_sub_item[0] = $sub_item['name'];
                        }
                        
                        // Add to submenu at the specified position
                        $submenu[$item['url']][$new_submenu_order] = $modified_sub_item;
                        $new_submenu_order += 10;
                    }
                }
            }
        }
        
        // Sort menu by position
        ksort($menu);
        
        // Sort submenus by position
        foreach ($submenu as $parent => $items) {
            ksort($submenu[$parent]);
        }
    }

    /**
     * AJAX handler for saving menu configuration
     */
    public function ajax_save_menu() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'came_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'advanced-admin-menu-editor')));
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions.', 'advanced-admin-menu-editor')));
            return;
        }
        
        // Get and sanitize data
        $menu_data = isset($_POST['menu_data']) ? wp_unslash($_POST['menu_data']) : '';
        $allowed_roles = isset($_POST['allowed_roles']) ? (array) $_POST['allowed_roles'] : array();
        $config_id = isset($_POST['config_id']) ? sanitize_key($_POST['config_id']) : '';
        $config_name = isset($_POST['config_name']) ? sanitize_text_field($_POST['config_name']) : '';
        
        // Ensure we have a configuration ID
        if (empty($config_id)) {
            wp_send_json_error(array('message' => __('No configuration ID provided.', 'advanced-admin-menu-editor')));
            return;
        }
        
        // Decode JSON data
        $menu_items = json_decode(stripslashes($menu_data), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(array(
                'message' => __('Invalid data format.', 'advanced-admin-menu-editor') . ' ' . json_last_error_msg(),
                'details' => json_last_error_msg()
            ));
            return;
        }
        
        // Update or add configuration
        $this->all_menu_configs[$config_id] = array(
            'name' => !empty($config_name) ? $config_name : __('Configuration', 'advanced-admin-menu-editor') . ' ' . $config_id,
            'menu_items' => $menu_items,
            'allowed_roles' => $allowed_roles,
            'is_active' => true
        );
        
        // Save all configurations
        update_option('came_menu_configs', $this->all_menu_configs);
        
        // Set active configuration
        $this->active_config_id = $config_id;
        
        // Save active configuration to user meta
        $user_id = get_current_user_id();
        if ($user_id) {
            update_user_meta($user_id, 'came_active_config', $config_id);
        }
        
        wp_send_json_success(array(
            'message' => __('Menu configuration saved successfully.', 'advanced-admin-menu-editor'),
            'config_id' => $config_id,
            'all_configs' => $this->all_menu_configs
        ));
    }
    
    /**
     * AJAX handler for adding a new configuration
     */
    public function ajax_add_config() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'came_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'advanced-admin-menu-editor')));
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions.', 'advanced-admin-menu-editor')));
            return;
        }
        
        // Get and sanitize data
        $config_name = isset($_POST['config_name']) ? sanitize_text_field($_POST['config_name']) : '';
        
        // Generate a unique ID
        $config_id = 'config-' . uniqid();
        
        // Create new configuration
        $this->all_menu_configs[$config_id] = array(
            'name' => !empty($config_name) ? $config_name : __('New Configuration', 'advanced-admin-menu-editor'),
            'menu_items' => array(),
            'allowed_roles' => array('administrator'),
            'is_active' => true
        );
        
        // Save all configurations
        update_option('came_menu_configs', $this->all_menu_configs);
        
        // Set active configuration
        $this->active_config_id = $config_id;
        
        // Save active configuration to user meta
        $user_id = get_current_user_id();
        if ($user_id) {
            update_user_meta($user_id, 'came_active_config', $config_id);
        }
        
        wp_send_json_success(array(
            'message' => __('New configuration created.', 'advanced-admin-menu-editor'),
            'config_id' => $config_id,
            'all_configs' => $this->all_menu_configs
        ));
    }
    
    /**
     * AJAX handler for deleting a configuration
     */
    public function ajax_delete_config() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'came_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'advanced-admin-menu-editor')));
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions.', 'advanced-admin-menu-editor')));
            return;
        }
        
        // Get and sanitize data
        $config_id = isset($_POST['config_id']) ? sanitize_key($_POST['config_id']) : '';
        
        // Ensure we have a configuration ID
        if (empty($config_id)) {
            wp_send_json_error(array('message' => __('No configuration ID provided.', 'advanced-admin-menu-editor')));
            return;
        }
        
        // Prevent deleting the last configuration
        if (count($this->all_menu_configs) <= 1) {
            wp_send_json_error(array('message' => __('Cannot delete the last configuration.', 'advanced-admin-menu-editor')));
            return;
        }
        
        // Remove configuration
        unset($this->all_menu_configs[$config_id]);
        
        // Save all configurations
        update_option('came_menu_configs', $this->all_menu_configs);
        
        // Reset active configuration to the first available
        if ($this->active_config_id === $config_id) {
            $this->active_config_id = array_key_first($this->all_menu_configs);
            
            // Save new active configuration to user meta
            $user_id = get_current_user_id();
            if ($user_id) {
                update_user_meta($user_id, 'came_active_config', $this->active_config_id);
            }
        }
        
        wp_send_json_success(array(
            'message' => __('Configuration deleted.', 'advanced-admin-menu-editor'),
            'config_id' => $this->active_config_id,
            'all_configs' => $this->all_menu_configs
        ));
    }
    
    /**
     * AJAX handler for loading a configuration
     */
    public function ajax_load_config() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'came_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'advanced-admin-menu-editor')));
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions.', 'advanced-admin-menu-editor')));
            return;
        }
        
        // Get and sanitize data
        $config_id = isset($_POST['config_id']) ? sanitize_key($_POST['config_id']) : '';
        
        // Ensure we have a configuration ID
        if (empty($config_id) || !isset($this->all_menu_configs[$config_id])) {
            wp_send_json_error(array('message' => __('Invalid configuration ID.', 'advanced-admin-menu-editor')));
            return;
        }
        
        // Set active configuration
        $this->active_config_id = $config_id;
        $active_config = $this->all_menu_configs[$config_id];
        
        // Save active configuration to user meta
        $user_id = get_current_user_id();
        if ($user_id) {
            update_user_meta($user_id, 'came_active_config', $config_id);
        }
        
        wp_send_json_success(array(
            'message' => __('Configuration loaded.', 'advanced-admin-menu-editor'),
            'config_id' => $config_id,
            'config_name' => $active_config['name'],
            'menu_items' => isset($active_config['menu_items']) ? $active_config['menu_items'] : array(),
            'allowed_roles' => isset($active_config['allowed_roles']) ? $active_config['allowed_roles'] : array(),
            'all_configs' => $this->all_menu_configs
        ));
    }

    /**
     * AJAX handler for resetting menu structure
     */
    public function ajax_reset_menu() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'came_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'advanced-admin-menu-editor')));
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions.', 'advanced-admin-menu-editor')));
            return;
        }
        
        // Get and sanitize data
        $config_id = isset($_POST['config_id']) ? sanitize_key($_POST['config_id']) : '';
        
        // Ensure we have a configuration ID
        if (empty($config_id) || !isset($this->all_menu_configs[$config_id])) {
            wp_send_json_error(array('message' => __('Invalid configuration ID.', 'advanced-admin-menu-editor')));
            return;
        }
        
        // Reset menu items for the configuration
        $this->all_menu_configs[$config_id]['menu_items'] = array();
        
        // Save all configurations
        update_option('came_menu_configs', $this->all_menu_configs);
        
        wp_send_json_success(array(
            'message' => __('Configuration reset successfully.', 'advanced-admin-menu-editor'),
            'config_id' => $config_id,
            'all_configs' => $this->all_menu_configs
        ));
    }
}

// Initialize the plugin
add_action('plugins_loaded', array('Advanced_Admin_Menu_Editor', 'get_instance'));