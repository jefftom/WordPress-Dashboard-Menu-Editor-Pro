<?php
/**
 * Admin page template for Multiple Menus
 * With improved UX
 *
 * @package Multiple_Menus_Admin_Editor
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap came-admin-wrap">
    <h1><?php echo esc_html__('Admin Menu Editor', 'multiple-menus-admin-editor'); ?></h1>
    
    <div class="came-admin-container">
        <div class="came-admin-header">
            <div class="came-config-selector">
                <h3 class="came-select-menu-heading"><?php echo esc_html__('Select Menu', 'multiple-menus-admin-editor'); ?></h3>
                <div class="came-config-controls">
                    <select id="came-config-dropdown">
                        <?php foreach ($this->all_menu_configs as $config_id => $config): ?>
                            <option value="<?php echo esc_attr($config_id); ?>" <?php selected($this->active_config_id, $config_id); ?>>
                                <?php echo esc_html($config['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button id="came-load-config" class="button">
                        <span class="dashicons dashicons-update"></span>
                        <?php echo esc_html__('Load', 'multiple-menus-admin-editor'); ?>
                    </button>
                    <button id="came-add-config" class="button">
                        <span class="dashicons dashicons-plus"></span>
                        <?php echo esc_html__('Add New', 'multiple-menus-admin-editor'); ?>
                    </button>
                    <button id="came-delete-config" class="button">
                        <span class="dashicons dashicons-trash"></span>
                        <?php echo esc_html__('Delete', 'multiple-menus-admin-editor'); ?>
                    </button>
                </div>
                <div class="came-config-name-wrapper">
                    <input type="text" id="came-config-name" placeholder="<?php echo esc_attr__('Configuration Name', 'multiple-menus-admin-editor'); ?>" value="<?php echo esc_attr($this->get_active_config()['name'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="came-actions">
                <button id="came-save-menu" class="button button-primary">
                    <span class="dashicons dashicons-saved"></span>
                    <?php echo esc_html__('Save Changes', 'multiple-menus-admin-editor'); ?>
                </button>
                <button id="came-reset-menu" class="button">
                    <span class="dashicons dashicons-undo"></span>
                    <?php echo esc_html__('Reset to Default', 'multiple-menus-admin-editor'); ?>
                </button>
            </div>
        </div>
        
        <div class="came-tabs">
            <a href="#came-tab-menu" class="came-tab active">
                <span class="dashicons dashicons-menu"></span>
                <?php echo esc_html__('Menu Structure', 'multiple-menus-admin-editor'); ?>
            </a>
            <a href="#came-tab-global-roles" class="came-tab">
                <span class="dashicons dashicons-groups"></span>
                <?php echo esc_html__('Global Role Settings', 'multiple-menus-admin-editor'); ?>
            </a>
        </div>
        
        <div class="came-admin-body">
            <!-- Menu Structure Tab -->
            <div id="came-tab-menu" class="came-tab-content active">
                <div class="came-sidebar">
                    <div class="came-sidebar-section">
                        <h3><?php echo esc_html__('Instructions', 'multiple-menus-admin-editor'); ?></h3>
                        <p><?php echo esc_html__('Click on a menu or submenu item to edit its properties. Drag items to reorder them.', 'multiple-menus-admin-editor'); ?></p>
                        <ul class="came-instructions-list">
                            <li><?php echo esc_html__('Use the drag handle to reorder items', 'multiple-menus-admin-editor'); ?></li>
                            <li><?php echo esc_html__('Click on an item to edit its name and visibility', 'multiple-menus-admin-editor'); ?></li>
                            <li><?php echo esc_html__('Expand a menu item to manage its submenu items', 'multiple-menus-admin-editor'); ?></li>
                            <li><?php echo esc_html__('Create multiple menu configurations and assign them to different roles', 'multiple-menus-admin-editor'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="came-sidebar-section">
                        <h3><?php echo esc_html__('Item Properties', 'multiple-menus-admin-editor'); ?></h3>
                        <div id="came-item-properties">
                            <p class="came-no-selection">
                                <?php echo esc_html__('Select a menu or submenu item to edit its properties.', 'multiple-menus-admin-editor'); ?>
                            </p>
                            
                            <div class="came-properties-form" style="display: none;">
                                <div class="came-form-group">
                                    <label for="came-item-name"><?php echo esc_html__('Display Name', 'multiple-menus-admin-editor'); ?></label>
                                    <input type="text" id="came-item-name" class="came-property-field" data-property="name">
                                </div>
                                
                                <div class="came-form-group">
                                    <label>
                                        <input type="checkbox" id="came-item-hidden" class="came-property-field" data-property="hidden">
                                        <?php echo esc_html__('Hide this item', 'multiple-menus-admin-editor'); ?>
                                    </label>
                                    <p class="description"><?php echo esc_html__('Hidden items will not be displayed in the admin menu.', 'multiple-menus-admin-editor'); ?></p>
                                </div>
                                
                                <div class="came-form-group came-item-info">
                                    <h4><?php echo esc_html__('Item Information', 'multiple-menus-admin-editor'); ?></h4>
                                    <div id="came-item-type-info" class="came-item-type-info">
                                        <p><strong><?php echo esc_html__('Type:', 'multiple-menus-admin-editor'); ?></strong> <span id="came-item-type"></span></p>
                                        <p><strong><?php echo esc_html__('URL:', 'multiple-menus-admin-editor'); ?></strong> <span id="came-item-url"></span></p>
                                    </div>
                                </div>
                                
                                <!-- Item-specific role settings -->
                                <div class="came-form-group came-item-roles">
                                    <h4><?php echo esc_html__('Show This Item To:', 'multiple-menus-admin-editor'); ?></h4>
                                    <p class="description"><?php echo esc_html__('Select which user roles will see this specific menu item.', 'multiple-menus-admin-editor'); ?></p>
                                    
                                    <div class="came-item-roles-list">
                                        <?php 
                                        $roles = $this->get_all_user_roles();
                                        
                                        foreach ($roles as $role_key => $role_name) : 
                                        ?>
                                            <div class="came-item-role-option">
                                                <label>
                                                    <input type="checkbox" name="came_item_roles[]" value="<?php echo esc_attr($role_key); ?>">
                                                    <?php echo esc_html($role_name); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="came-main-content">
                    <div class="came-menu-structure">
                        <div class="came-instructions">
                            <p><?php echo esc_html__('Drag and drop items to reorder. Click on an item to edit its properties.', 'multiple-menus-admin-editor'); ?></p>
                        </div>
                        
                        <div id="came-menu-tree" class="came-menu-tree">
                            <!-- This will be populated by JavaScript -->
                            <div class="notice notice-info" style="margin: 20px 0;">
                                <p><?php echo esc_html__('Loading menu items...', 'multiple-menus-admin-editor'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Global Role Settings Tab -->
            <div id="came-tab-global-roles" class="came-tab-content">
                <div class="came-roles-settings">
                    <h3><?php echo esc_html__('Apply This Menu Configuration To:', 'multiple-menus-admin-editor'); ?></h3>
                    <p class="description">
                        <?php echo esc_html__('Select which user roles will see this menu configuration. Users with roles that are not selected for any configuration will see the default WordPress menu.', 'multiple-menus-admin-editor'); ?>
                    </p>
                    
                    <?php
                    // Get active configuration
                    $active_config = $this->get_active_config();
                    $saved_roles = isset($active_config['allowed_roles']) ? $active_config['allowed_roles'] : array();
                    ?>
                    
                    <div class="came-roles-list">
                        <?php 
                        $roles = $this->get_all_user_roles();
                        
                        foreach ($roles as $role_key => $role_name) : 
                            $checked = in_array($role_key, $saved_roles) ? 'checked' : '';
                        ?>
                            <div class="came-role-option">
                                <label>
                                    <input type="checkbox" name="came_allowed_roles[]" value="<?php echo esc_attr($role_key); ?>" <?php echo $checked; ?>>
                                    <?php echo esc_html($role_name); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="came-roles-notice">
                        <p class="description">
                            <?php echo esc_html__('Note: If a user has multiple roles and more than one configuration applies, the first applicable configuration will be used.', 'multiple-menus-admin-editor'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="came-admin-footer">
            <div class="came-status-message"></div>
        </div>
    </div>
</div>

<!-- Add New Configuration Modal -->
<div id="came-add-config-modal" class="came-modal" style="display: none;">
    <div class="came-modal-content">
        <div class="came-modal-header">
            <span class="came-modal-close">&times;</span>
            <h2><?php echo esc_html__('Add New Configuration', 'multiple-menus-admin-editor'); ?></h2>
        </div>
        <div class="came-modal-body">
            <div class="came-form-group">
                <label for="came-new-config-name"><?php echo esc_html__('Configuration Name', 'multiple-menus-admin-editor'); ?></label>
                <input type="text" id="came-new-config-name" placeholder="<?php echo esc_attr__('Enter a name for this configuration', 'multiple-menus-admin-editor'); ?>">
            </div>
        </div>
        <div class="came-modal-footer">
            <button id="came-add-config-cancel" class="button"><?php echo esc_html__('Cancel', 'multiple-menus-admin-editor'); ?></button>
            <button id="came-add-config-confirm" class="button button-primary"><?php echo esc_html__('Add Configuration', 'multiple-menus-admin-editor'); ?></button>
        </div>
    </div>
</div>

<!-- Templates -->
<script type="text/template" id="came-menu-item-template">
    <div class="came-menu-item" data-item-id="{id}" data-item-url="{url}">
        <div class="came-item-header">
            <span class="came-item-handle dashicons dashicons-menu"></span>
            <span class="came-item-title">{name}</span>
            <div class="came-item-actions">
                <span class="came-item-toggle dashicons dashicons-arrow-down"></span>
                <span class="came-item-hidden-indicator dashicons dashicons-hidden" title="<?php echo esc_attr__('This item is hidden', 'multiple-menus-admin-editor'); ?>"></span>
            </div>
        </div>
        <div class="came-submenu-container" style="display: none;">
            <div class="came-submenu-list">
                <!-- Submenu items will go here -->
            </div>
        </div>
    </div>
</script>

<script type="text/template" id="came-submenu-item-template">
    <div class="came-submenu-item" data-item-id="{id}" data-item-url="{url}">
        <span class="came-item-handle dashicons dashicons-menu"></span>
        <span class="came-item-title">{name}</span>
        <div class="came-item-actions">
            <span class="came-item-hidden-indicator dashicons dashicons-hidden" title="<?php echo esc_attr__('This item is hidden', 'multiple-menus-admin-editor'); ?>"></span>
        </div>
    </div>
</script>