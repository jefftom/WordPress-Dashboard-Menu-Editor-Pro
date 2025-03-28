<?php
/**
 * Admin page template
 *
 * @package Enhanced_Admin_Menu_Editor
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap came-admin-wrap">
    <h1><?php echo esc_html__('Admin Menu Editor', 'enhanced-admin-menu-editor'); ?></h1>
    
    <div class="notice notice-info">
        <p>
            <?php echo esc_html__('Customize your WordPress admin menu by dragging items to reorder, renaming items, and controlling visibility.', 'enhanced-admin-menu-editor'); ?>
        </p>
    </div>
    
    <div class="came-admin-container">
        <div class="came-admin-header">
            <div class="came-actions">
                <button id="came-save-menu" class="button button-primary">
                    <span class="dashicons dashicons-saved"></span>
                    <?php echo esc_html__('Save Changes', 'enhanced-admin-menu-editor'); ?>
                </button>
                <button id="came-reset-menu" class="button">
                    <span class="dashicons dashicons-undo"></span>
                    <?php echo esc_html__('Reset to Default', 'enhanced-admin-menu-editor'); ?>
                </button>
            </div>
            <div class="came-tabs">
                <a href="#came-tab-menu" class="came-tab active">
                    <span class="dashicons dashicons-menu"></span>
                    <?php echo esc_html__('Menu Structure', 'enhanced-admin-menu-editor'); ?>
                </a>
                <a href="#came-tab-roles" class="came-tab">
                    <span class="dashicons dashicons-groups"></span>
                    <?php echo esc_html__('Role Settings', 'enhanced-admin-menu-editor'); ?>
                </a>
            </div>
        </div>
        
        <div class="came-admin-body">
            <!-- Menu Structure Tab -->
            <div id="came-tab-menu" class="came-tab-content active">
                <div class="came-sidebar">
                    <div class="came-sidebar-section">
                        <h3><?php echo esc_html__('Instructions', 'enhanced-admin-menu-editor'); ?></h3>
                        <p><?php echo esc_html__('Click on a menu or submenu item to edit its properties. Drag items to reorder them.', 'enhanced-admin-menu-editor'); ?></p>
                        <ul class="came-instructions-list">
                            <li><?php echo esc_html__('Use the drag handle to reorder items', 'enhanced-admin-menu-editor'); ?></li>
                            <li><?php echo esc_html__('Click on an item to edit its name and visibility', 'enhanced-admin-menu-editor'); ?></li>
                            <li><?php echo esc_html__('Expand a menu item to manage its submenu items', 'enhanced-admin-menu-editor'); ?></li>
                            <li><?php echo esc_html__('Click "Save Changes" to apply your customizations', 'enhanced-admin-menu-editor'); ?></li>
                        </ul>
                    </div>
                    
                    <div class="came-sidebar-section">
                        <h3><?php echo esc_html__('Item Properties', 'enhanced-admin-menu-editor'); ?></h3>
                        <div id="came-item-properties">
                            <p class="came-no-selection">
                                <?php echo esc_html__('Select a menu or submenu item to edit its properties.', 'enhanced-admin-menu-editor'); ?>
                            </p>
                            
                            <div class="came-properties-form" style="display: none;">
                                <div class="came-form-group">
                                    <label for="came-item-name"><?php echo esc_html__('Display Name', 'enhanced-admin-menu-editor'); ?></label>
                                    <input type="text" id="came-item-name" class="came-property-field" data-property="name">
                                </div>
                                
                                <div class="came-form-group">
                                    <label>
                                        <input type="checkbox" id="came-item-hidden" class="came-property-field" data-property="hidden">
                                        <?php echo esc_html__('Hide this item', 'enhanced-admin-menu-editor'); ?>
                                    </label>
                                    <p class="description"><?php echo esc_html__('Hidden items will not be displayed in the admin menu.', 'enhanced-admin-menu-editor'); ?></p>
                                </div>
                                
                                <div class="came-form-group came-item-info">
                                    <h4><?php echo esc_html__('Item Information', 'enhanced-admin-menu-editor'); ?></h4>
                                    <div id="came-item-type-info" class="came-item-type-info">
                                        <p><strong><?php echo esc_html__('Type:', 'enhanced-admin-menu-editor'); ?></strong> <span id="came-item-type"></span></p>
                                        <p><strong><?php echo esc_html__('URL:', 'enhanced-admin-menu-editor'); ?></strong> <span id="came-item-url"></span></p>
                                    </div>
                                </div>
                                
                                <div class="came-form-actions">
                                    <button type="button" id="came-apply-changes" class="button button-primary">
                                        <?php echo esc_html__('Apply Changes', 'enhanced-admin-menu-editor'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="came-main-content">
                    <div class="came-menu-structure">
                        <div class="came-instructions">
                            <p><?php echo esc_html__('Drag and drop items to reorder. Click on an item to edit its properties.', 'enhanced-admin-menu-editor'); ?></p>
                        </div>
                        
                        <div id="came-menu-tree" class="came-menu-tree">
                            <!-- This will be populated by JavaScript -->
                            <div class="notice notice-info" style="margin: 20px 0;">
                                <p><?php echo esc_html__('Loading menu items...', 'enhanced-admin-menu-editor'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Role Settings Tab -->
            <div id="came-tab-roles" class="came-tab-content">
                <div class="came-roles-settings">
                    <h3><?php echo esc_html__('Apply Custom Menu To:', 'enhanced-admin-menu-editor'); ?></h3>
                    <p class="description">
                        <?php echo esc_html__('Select which user roles will see the customized admin menu. Users with roles that are not selected will see the default WordPress menu.', 'enhanced-admin-menu-editor'); ?>
                    </p>
                    
                    <div class="came-roles-list">
                        <?php 
                        $roles = $this->get_all_user_roles();
                        $saved_roles = isset($this->menu_settings['allowed_roles']) ? $this->menu_settings['allowed_roles'] : array();
                        
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
                </div>
            </div>
        </div>
        
        <div class="came-admin-footer">
            <div class="came-status-message"></div>
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
                <span class="came-item-hidden-indicator dashicons dashicons-hidden" title="<?php echo esc_attr__('This item is hidden', 'enhanced-admin-menu-editor'); ?>"></span>
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
            <span class="came-item-hidden-indicator dashicons dashicons-hidden" title="<?php echo esc_attr__('This item is hidden', 'enhanced-admin-menu-editor'); ?>"></span>
        </div>
    </div>
</script>