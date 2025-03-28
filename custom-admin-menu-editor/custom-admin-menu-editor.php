<?php
/**
 * Plugin Name: Custom Admin Menu Editor
 * Plugin URI: https://yourwebsite.com/custom-admin-menu-editor
 * Description: Customize your WordPress admin menu with drag and drop functionality, role-based permissions, custom icons, and more.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL-2.0+
 * Text Domain: custom-admin-menu-editor
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('CAME_VERSION', '1.0.0');
define('CAME_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CAME_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class
 */
class Custom_Admin_Menu_Editor {
    /**
     * Instance of this class.
     *
     * @var object
     */
    protected static $instance = null;

    /**
     * Stored menu settings
     *
     * @var array
     */
    private $menu_settings = array();

    /**
     * Initialize the plugin.
     */
    private function __construct() {
        // Load saved menu settings
        $this->menu_settings = get_option('came_menu_settings', array());

        // Add admin menu item
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Filter admin menu
        add_action('admin_menu', array($this, 'modify_admin_menu'), 999);

        // Register AJAX handlers
        add_action('wp_ajax_came_save_menu', array($this, 'ajax_save_menu'));
        add_action('wp_ajax_came_reset_menu', array($this, 'ajax_reset_menu'));

        // Add settings link to plugins page
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
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
            __('Admin Menu Editor', 'custom-admin-menu-editor'),
            __('Admin Menu Editor', 'custom-admin-menu-editor'),
            'manage_options',
            'custom-admin-menu-editor',
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
        $settings_link = '<a href="' . admin_url('options-general.php?page=custom-admin-menu-editor') . '">' . __('Settings', 'custom-admin-menu-editor') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_assets($hook) {
        if ('settings_page_custom-admin-menu-editor' !== $hook) {
            return;
        }

        // Enqueue jQuery UI and Sortable
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('jquery-ui-draggable');
        wp_enqueue_script('jquery-ui-droppable');

        // Enqueue custom scripts
        wp_enqueue_script(
            'came-admin-js',
            CAME_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-sortable'),
            CAME_VERSION,
            true
        );

        // Pass data to script
        wp_localize_script('came-admin-js', 'came_data', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('came_nonce'),
            'current_user_roles' => $this->get_current_user_roles(),
            'dashicons' => $this->get_available_dashicons(),
            'admin_menu' => $this->get_admin_menu_structure()
        ));

        // Enqueue custom styles
        wp_enqueue_style(
            'came-admin-css',
            CAME_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            CAME_VERSION
        );

        // Enqueue dashicons
        wp_enqueue_style('dashicons');
    }

    /**
     * Display the admin page
     */
    public function display_plugin_admin_page() {
        include_once CAME_PLUGIN_DIR . 'includes/admin-page.php';
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
            'dashicons-album',
            'dashicons-align-center',
            'dashicons-align-left',
            'dashicons-align-none',
            'dashicons-align-right',
            'dashicons-analytics',
            'dashicons-archive',
            'dashicons-arrow-down',
            'dashicons-arrow-left',
            'dashicons-arrow-right',
            'dashicons-arrow-up',
            'dashicons-art',
            'dashicons-awards',
            'dashicons-backup',
            'dashicons-book',
            'dashicons-calendar',
            'dashicons-cart',
            'dashicons-category',
            'dashicons-chart-area',
            'dashicons-chart-bar',
            'dashicons-chart-line',
            'dashicons-chart-pie',
            'dashicons-clipboard',
            'dashicons-clock',
            'dashicons-cloud',
            'dashicons-desktop',
            'dashicons-editor-code',
            'dashicons-email',
            'dashicons-format-gallery',
            'dashicons-groups',
            'dashicons-heart',
            'dashicons-id',
            'dashicons-lock',
            'dashicons-performance',
            'dashicons-plus',
            'dashicons-shield',
            'dashicons-star-filled',
            'dashicons-warning'
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
    private function get_all_user_roles() {
        global $wp_roles;
        
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        
        return $wp_roles->get_names();
    }

    /**
     * Get original WordPress admin menu structure
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
                if (empty($menu_item[0])) {
                    continue;
                }
                
                // Clean up menu item name
                $menu_name = preg_replace('/<span.*?>.*?<\/span>/i', '', $menu_item[0]);
                $menu_name = trim(strip_tags($menu_name));
                
                $item = array(
                    'id' => 'menu-' . sanitize_title($menu_name),
                    'name' => $menu_name,
                    'url' => $menu_item[2],
                    'capability' => $menu_item[1],
                    'icon' => isset($menu_item[6]) ? $menu_item[6] : '',
                    'position' => $menu_key,
                    'submenu' => array()
                );
                
                // Add submenu items if they exist
                if (isset($original_submenu[$menu_item[2]]) && is_array($original_submenu[$menu_item[2]])) {
                    foreach ($original_submenu[$menu_item[2]] as $submenu_key => $submenu_item) {
                        $submenu_name = preg_replace('/<span.*?>.*?<\/span>/i', '', $submenu_item[0]);
                        $submenu_name = trim(strip_tags($submenu_name));
                        
                        $item['submenu'][] = array(
                            'id' => 'submenu-' . sanitize_title($submenu_name),
                            'name' => $submenu_name,
                            'url' => $submenu_item[2],
                            'capability' => $submenu_item[1],
                            'position' => $submenu_key
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
        global $menu, $submenu;
        
        // Check if user has permission to see modified menu
        $current_user_roles = $this->get_current_user_roles();
        $allowed_roles = isset($this->menu_settings['allowed_roles']) ? $this->menu_settings['allowed_roles'] : array();
        
        $can_see_modified_menu = false;
        foreach ($current_user_roles as $role) {
            if (in_array($role, $allowed_roles)) {
                $can_see_modified_menu = true;
                break;
            }
        }
        
        if (!$can_see_modified_menu || empty($this->menu_settings['menu_items'])) {
            return;
        }
        
        // Store original menu
        $original_menu = $menu;
        $original_submenu = $submenu;
        
        // Clear the current menu
        $menu = array();
        $submenu = array();
        
        // Rebuild menu according to saved structure
        foreach ($this->menu_settings['menu_items'] as $item) {
            if (isset($item['hidden']) && $item['hidden']) {
                continue;
            }
            
            // Find original menu item
            $original_item = null;
            foreach ($original_menu as $orig_key => $orig_item) {
                if ($orig_item[2] === $item['url']) {
                    $original_item = $orig_item;
                    break;
                }
            }
            
            if ($original_item === null) {
                // This might be a custom menu item
                if (isset($item['custom']) && $item['custom']) {
                    $menu_position = isset($item['position']) ? $item['position'] : null;
                    $menu_icon = isset($item['icon']) ? $item['icon'] : 'dashicons-admin-generic';
                    
                    // Add custom menu item
                    add_menu_page(
                        $item['name'],
                        $item['name'],
                        isset($item['capability']) ? $item['capability'] : 'read',
                        $item['url'],
                        function() use ($item) {
                            echo '<div class="wrap">';
                            echo '<h1>' . esc_html($item['name']) . '</h1>';
                            if (isset($item['custom_content'])) {
                                echo wp_kses_post($item['custom_content']);
                            } else {
                                echo '<p>' . esc_html__('Custom menu page content.', 'custom-admin-menu-editor') . '</p>';
                            }
                            echo '</div>';
                        },
                        $menu_icon,
                        $menu_position
                    );
                }
                continue;
            }
            
            // Modify menu item if needed
            $modified_item = $original_item;
            
            if (isset($item['name']) && !empty($item['name'])) {
                $modified_item[0] = $item['name'];
            }
            
            if (isset($item['capability']) && !empty($item['capability'])) {
                $modified_item[1] = $item['capability'];
            }
            
            if (isset($item['icon']) && !empty($item['icon'])) {
                $modified_item[6] = $item['icon'];
            }
            
            // Add modified menu item
            $menu_position = isset($item['position']) ? $item['position'] : null;
            $menu[$menu_position] = $modified_item;
            
            // Process submenu if it exists
            if (isset($item['submenu']) && !empty($item['submenu'])) {
                foreach ($item['submenu'] as $sub_item) {
                    if (isset($sub_item['hidden']) && $sub_item['hidden']) {
                        continue;
                    }
                    
                    // Find original submenu item
                    $original_sub_item = null;
                    if (isset($original_submenu[$item['url']])) {
                        foreach ($original_submenu[$item['url']] as $orig_sub_key => $orig_sub_item) {
                            if ($orig_sub_item[2] === $sub_item['url']) {
                                $original_sub_item = $orig_sub_item;
                                break;
                            }
                        }
                    }
                    
                    if ($original_sub_item === null) {
                        // This might be a custom submenu item
                        if (isset($sub_item['custom']) && $sub_item['custom']) {
                            // Add custom submenu item
                            add_submenu_page(
                                $item['url'],
                                $sub_item['name'],
                                $sub_item['name'],
                                isset($sub_item['capability']) ? $sub_item['capability'] : 'read',
                                $sub_item['url'],
                                function() use ($sub_item) {
                                    echo '<div class="wrap">';
                                    echo '<h1>' . esc_html($sub_item['name']) . '</h1>';
                                    if (isset($sub_item['custom_content'])) {
                                        echo wp_kses_post($sub_item['custom_content']);
                                    } else {
                                        echo '<p>' . esc_html__('Custom submenu page content.', 'custom-admin-menu-editor') . '</p>';
                                    }
                                    echo '</div>';
                                }
                            );
                        }
                        continue;
                    }
                    
                    // Modify submenu item if needed
                    $modified_sub_item = $original_sub_item;
                    
                    if (isset($sub_item['name']) && !empty($sub_item['name'])) {
                        $modified_sub_item[0] = $sub_item['name'];
                    }
                    
                    if (isset($sub_item['capability']) && !empty($sub_item['capability'])) {
                        $modified_sub_item[1] = $sub_item['capability'];
                    }
                    
                    // Add modified submenu item
                    $submenu_position = isset($sub_item['position']) ? $sub_item['position'] : null;
                    
                    if (!isset($submenu[$item['url']])) {
                        $submenu[$item['url']] = array();
                    }
                    
                    $submenu[$item['url']][$submenu_position] = $modified_sub_item;
                }
            }
        }
        
        // Sort menu and submenu by position
        ksort($menu);
        foreach ($submenu as $parent => $items) {
            ksort($submenu[$parent]);
        }
    }

    /**
     * AJAX handler for saving menu structure
     */
    public function ajax_save_menu() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'came_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'custom-admin-menu-editor')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions.', 'custom-admin-menu-editor')));
        }
        
        // Get and sanitize data
        $menu_data = isset($_POST['menu_data']) ? $_POST['menu_data'] : '';
        $allowed_roles = isset($_POST['allowed_roles']) ? (array) $_POST['allowed_roles'] : array();
        
        // Decode JSON data
        $menu_items = json_decode(stripslashes($menu_data), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(array('message' => __('Invalid data format.', 'custom-admin-menu-editor')));
        }
        
        // Save settings
        $this->menu_settings = array(
            'menu_items' => $menu_items,
            'allowed_roles' => $allowed_roles
        );
        
        update_option('came_menu_settings', $this->menu_settings);
        
        wp_send_json_success(array('message' => __('Menu settings saved successfully.', 'custom-admin-menu-editor')));
    }

    /**
     * AJAX handler for resetting menu structure
     */
    public function ajax_reset_menu() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'came_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'custom-admin-menu-editor')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have sufficient permissions.', 'custom-admin-menu-editor')));
        }
        
        // Delete saved settings
        delete_option('came_menu_settings');
        $this->menu_settings = array();
        
        wp_send_json_success(array('message' => __('Menu settings reset successfully.', 'custom-admin-menu-editor')));
    }
}

// Initialize the plugin
add_action('plugins_loaded', array('Custom_Admin_Menu_Editor', 'get_instance'));

/**
 * Include required files
 */
require_once CAME_PLUGIN_DIR . 'includes/admin-page.php';