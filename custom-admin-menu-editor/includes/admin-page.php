<?php
/**
 * Admin page template
 *
 * @package Custom_Admin_Menu_Editor
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap came-admin-wrap">
    <h1><?php echo esc_html__('Custom Admin Menu Editor', 'custom-admin-menu-editor'); ?></h1>
    
    <div class="came-admin-container">
        <div class="came-admin-header">
            <div class="came-actions">
                <button id="came-save-menu" class="button button-primary">
                    <span class="dashicons dashicons-saved"></span>
                    <?php echo esc_html__('Save Changes', 'custom-admin-menu-editor'); ?>
                </button>
                <button id="came-reset-menu" class="button">
                    <span class="dashicons dashicons-undo"></span>
                    <?php echo esc_html__('Reset to Default', 'custom-admin-menu-editor'); ?>
                </button>
            </div>
            <div class="came-tabs">
                <a href="#came-tab-menu" class="came-tab active">
                    <span class="dashicons dashicons-menu"></span>
                    <?php echo esc_html__('Menu Structure', 'custom-admin-menu-editor'); ?>
                </a>
                <a href="#came-tab-roles" class="came-tab">
                    <span class="dashicons dashicons-groups"></span>
                    <?php echo esc_html__('Role Settings', 'custom-admin-menu-editor'); ?>
                </a>
                <a href="#came-tab-settings" class="came-tab">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php echo esc_html__('General Settings', 'custom-admin-menu-editor'); ?>
                </a>
            </div>
        </div>
        
        <div class="came-admin-body">
            <!-- Menu Structure Tab -->
            <div id="came-tab-menu" class="came-tab-content active">
                <div class="came-sidebar">
                    <div class="came-sidebar-section">
                        <h3><?php echo esc_html__('Add New Item', 'custom-admin-menu-editor'); ?></h3>
                        <div class="came-add-new-item">
                            <button id="came-add-menu-item" class="button">
                                <span class="dashicons dashicons-plus"></span>
                                <?php echo esc_html__('Add Menu Item', 'custom-admin-menu-editor'); ?>
                            </button>
                            <button id="came-add-separator" class="button">
                                <span class="dashicons dashicons-minus"></span>
                                <?php echo esc_html__('Add Separator', 'custom-admin-menu-editor'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="came-sidebar-section">
                        <h3><?php echo esc_html__('Item Properties', 'custom-admin-menu-editor'); ?></h3>
                        <div id="came-item-properties">
                            <p class="came-no-selection">
                                <?php echo esc_html__('Select a menu item to edit its properties.', 'custom-admin-menu-editor'); ?>
                            </p>
                            
                            <div class="came-properties-form" style="display: none;">
                                <div class="came-form-group">
                                    <label for="came-item-name"><?php echo esc_html__('Name', 'custom-admin-menu-editor'); ?></label>
                                    <input type="text" id="came-item-name" class="came-property-field" data-property="name">
                                </div>
                                
                                <div class="came-form-group">
                                    <label for="came-item-url"><?php echo esc_html__('URL / Slug', 'custom-admin-menu-editor'); ?></label>
                                    <input type="text" id="came-item-url" class="came-property-field" data-property="url">
                                </div>
                                
                                <div class="came-form-group">
                                    <label for="came-item-capability"><?php echo esc_html__('Required Capability', 'custom-admin-menu-editor'); ?></label>
                                    <input type="text" id="came-item-capability" class="came-property-field" data-property="capability">
                                    <p class="description">
                                        <a href="https://wordpress.org/documentation/article/roles-and-capabilities/" target="_blank">
                                            <?php echo esc_html__('Learn about capabilities', 'custom-admin-menu-editor'); ?>
                                        </a>
                                    </p>
                                </div>
                                
                                <div class="came-form-group came-icon-selector">
                                    <label for="came-item-icon"><?php echo esc_html__('Icon', 'custom-admin-menu-editor'); ?></label>
                                    <div class="came-icon-preview">
                                        <span id="came-icon-preview" class="dashicons"></span>
                                    </div>
                                    <button type="button" id="came-select-icon" class="button">
                                        <?php echo esc_html__('Select Icon', 'custom-admin-menu-editor'); ?>
                                    </button>
                                    <input type="hidden" id="came-item-icon" class="came-property-field" data-property="icon">
                                    
                                    <div id="came-icon-picker" style="display: none;">
                                        <div class="came-icon-picker-header">
                                            <input type="text" id="came-icon-search" placeholder="<?php echo esc_attr__('Search icons...', 'custom-admin-menu-editor'); ?>">
                                            <button type="button" id="came-icon-picker-close" class="button">
                                                <span class="dashicons dashicons-no-alt"></span>
                                            </button>
                                        </div>
                                        <div class="came-icon-list">
                                            <?php foreach ($this->get_available_dashicons() as $icon) : ?>
                                                <div class="came-icon-option" data-icon="<?php echo esc_attr($icon); ?>">
                                                    <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="came-form-group">
                                    <label>
                                        <input type="checkbox" id="came-item-hidden" class="came-property-field" data-property="hidden">
                                        <?php echo esc_html__('Hide this item', 'custom-admin-menu-editor'); ?>
                                    </label>
                                </div>
                                
                                <div class="came-form-group came-custom-item-only" style="display: none;">
                                    <label for="came-item-custom-content"><?php echo esc_html__('Custom Content', 'custom-admin-menu-editor'); ?></label>
                                    <textarea id="came-item-custom-content" class="came-property-field" data-property="custom_content" rows="5"></textarea>
                                </div>
                                
                                <div class="came-form-actions">
                                    <button type="button" id="came-apply-changes" class="button button-primary">
                                        <?php echo esc_html__('Apply Changes', 'custom-admin-menu-editor'); ?>
                                    </button>
                                    <button type="button" id="came-delete-item" class="button button-link-delete">
                                        <?php echo esc_html__('Delete Item', 'custom-admin-menu-editor'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="came-main-content">
                    <div class="came-menu-structure">
                        <div class="came-instructions">
                            <p><?php echo esc_html__('Drag and drop items to reorder. Click on an item to edit its properties.', 'custom-admin-menu-editor'); ?></p>
                        </div>
                        
                        <div id="came-menu-tree" class="came-menu-tree">
                            <!-- This will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Role Settings Tab -->
            <div id="came-tab-roles" class="came-tab-content">
                <div class="came-roles-settings">
                    <h3><?php echo esc_html__('Apply Custom Menu To:', 'custom-admin-menu-editor'); ?></h3>
                    <p class="description">
                        <?php echo esc_html__('Select which user roles will see the customized admin menu. Users with roles that are not selected will see the default WordPress menu.', 'custom-admin-menu-editor'); ?>
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
            
            <!-- General Settings Tab -->
            <div id="came-tab-settings" class="came-tab-content">
                <div class="came-general-settings">
                    <h3><?php echo esc_html__('Color Scheme', 'custom-admin-menu-editor'); ?></h3>
                    <div class="came-color-settings">
                        <div class="came-form-group">
                            <label for="came-menu-bg-color"><?php echo esc_html__('Menu Background', 'custom-admin-menu-editor'); ?></label>
                            <input type="color" id="came-menu-bg-color" class="came-color-field" data-property="menu_bg_color" value="#23282d">
                        </div>
                        
                        <div class="came-form-group">
                            <label for="came-menu-text-color"><?php echo esc_html__('Menu Text', 'custom-admin-menu-editor'); ?></label>
                            <input type="color" id="came-menu-text-color" class="came-color-field" data-property="menu_text_color" value="#ffffff">
                        </div>
                        
                        <div class="came-form-group">
                            <label for="came-hover-bg-color"><?php echo esc_html__('Hover Background', 'custom-admin-menu-editor'); ?></label>
                            <input type="color" id="came-hover-bg-color" class="came-color-field" data-property="hover_bg_color" value="#0073aa">
                        </div>
                        
                        <div class="came-form-group">
                            <label for="came-hover-text-color"><?php echo esc_html__('Hover Text', 'custom-admin-menu-editor'); ?></label>
                            <input type="color" id="came-hover-text-color" class="came-color-field" data-property="hover_text_color" value="#ffffff">
                        </div>
                        
                        <div class="came-form-actions">
                            <button type="button" id="came-apply-colors" class="button button-primary">
                                <?php echo esc_html__('Apply Colors', 'custom-admin-menu-editor'); ?>
                            </button>
                            <button type="button" id="came-reset-colors" class="button">
                                <?php echo esc_html__('Reset to Default', 'custom-admin-menu-editor'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <h3><?php echo esc_html__('Advanced Settings', 'custom-admin-menu-editor'); ?></h3>
                    <div class="came-advanced-settings">
                        <div class="came-form-group">
                            <label>
                                <input type="checkbox" id="came-show-menu-for-admins" checked>
                                <?php echo esc_html__('Always show full menu to administrators', 'custom-admin-menu-editor'); ?>
                            </label>
                            <p class="description">
                                <?php echo esc_html__('Administrators will always see all menu items, regardless of role settings.', 'custom-admin-menu-editor'); ?>
                            </p>
                        </div>
                        
                        <div class="came-form-group">
                            <label>
                                <input type="checkbox" id="came-preserve-settings">
                                <?php echo esc_html__('Preserve settings on plugin deactivation', 'custom-admin-menu-editor'); ?>
                            </label>
                            <p class="description">
                                <?php echo esc_html__('Keep all menu settings when the plugin is deactivated.', 'custom-admin-menu-editor'); ?>
                            </p>
                        </div>
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
    <div class="came-menu-item" data-item-id="{id}">
        <div class="came-item-header">
            <span class="came-item-handle dashicons dashicons-menu"></span>
            <span class="came-item-icon dashicons {icon}"></span>
            <span class="came-item-title">{name}</span>
            <div class="came-item-actions">
                <span class="came-item-toggle dashicons dashicons-arrow-down"></span>
                <span class="came-item-hidden-indicator dashicons dashicons-hidden" title="<?php echo esc_attr__('This item is hidden', 'custom-admin-menu-editor'); ?>"></span>
            </div>
        </div>
        <div class="came-submenu-container" style="display: none;">
            <div class="came-submenu-list">
                <!-- Submenu items will go here -->
            </div>
            <div class="came-add-submenu">
                <button type="button" class="button came-add-submenu-button">
                    <span class="dashicons dashicons-plus"></span>
                    <?php echo esc_html__('Add Submenu Item', 'custom-admin-menu-editor'); ?>
                </button>
            </div>
        </div>
    </div>
</script>

<script type="text/template" id="came-submenu-item-template">
    <div class="came-submenu-item" data-item-id="{id}">
        <span class="came-item-handle dashicons dashicons-menu"></span>
        <span class="came-item-title">{name}</span>
        <div class="came-item-actions">
            <span class="came-item-hidden-indicator dashicons dashicons-hidden" title="<?php echo esc_attr__('This item is hidden', 'custom-admin-menu-editor'); ?>"></span>
        </div>
    </div>
</script>

<script type="text/template" id="came-separator-template">
    <div class="came-menu-item came-separator" data-item-id="{id}">
        <div class="came-item-header">
            <span class="came-item-handle dashicons dashicons-menu"></span>
            <span class="came-separator-line"></span>
            <div class="came-item-actions">
                <span class="came-item-title"><?php echo esc_html__('Separator', 'custom-admin-menu-editor'); ?></span>
            </div>
        </div>
    </div>
</script>