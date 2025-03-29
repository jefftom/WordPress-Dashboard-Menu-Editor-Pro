/**
 * Multiple Menus Admin Editor JavaScript
 * Supports managing multiple menu configurations
 * With auto-apply changes and improved UX
 */

(function($) {
    'use strict';

    // Store menu data
    var menuData = {
        items: [],
        currentItemId: null,
        currentItemType: null, // 'menu' or 'submenu'
        configId: '',
        configName: ''
    };

    // Initialize plugin
    $(document).ready(function() {
        // Initialize configuration controls
        initConfigControls();
        
        // Initialize tabs
        initTabs();
        
        // Initialize event handlers
        initEventHandlers();
        
        // Initialize modals
        initModals();
        
        // Load menu data if available
        if (typeof came_data !== 'undefined') {
            // Set config information
            menuData.configId = came_data.active_config_id || '';
            
            var activeConfig = null;
            if (menuData.configId && came_data.all_configs && came_data.all_configs[menuData.configId]) {
                activeConfig = came_data.all_configs[menuData.configId];
                menuData.configName = activeConfig.name || '';
            }
            
            // Set config name in input field
            $('#came-config-name').val(menuData.configName);
            
            // Check for saved menu first
            if (came_data.saved_menu && came_data.saved_menu.length > 0) {
                menuData.items = came_data.saved_menu;
                console.log('Loaded saved menu data for configuration:', menuData.configId);
            } 
            // Fall back to current admin menu structure
            else if (came_data.admin_menu) {
                menuData.items = came_data.admin_menu;
                console.log('Loaded default menu structure for new configuration');
            }
            
            // Render the menu - with a slight delay to ensure all DOM is ready
            setTimeout(function() {
                renderMenuTree();
            }, 100);
        } else {
            console.error('came_data is not defined. Script data not properly localized.');
        }
    });
    
    /**
     * Initialize configuration controls
     */
    function initConfigControls() {
        // Load configuration
        $('#came-load-config').on('click', function() {
            var configId = $('#came-config-dropdown').val();
            if (configId) {
                loadConfiguration(configId);
            }
        });
        
        // Configuration dropdown change (auto-load)
        $('#came-config-dropdown').on('change', function() {
            var configId = $(this).val();
            if (configId) {
                loadConfiguration(configId);
            }
        });
        
        // Add new configuration button
        $('#came-add-config').on('click', function() {
            showAddConfigModal();
        });
        
        // Delete configuration button
        $('#came-delete-config').on('click', function() {
            var configId = $('#came-config-dropdown').val();
            if (configId && confirm(came_data.text_confirm_delete || 'Are you sure you want to delete this configuration?')) {
                deleteConfiguration(configId);
            }
        });
    }

    /**
     * Initialize tabs
     */
    function initTabs() {
        $('.came-tab').on('click', function(e) {
            e.preventDefault();
            
            var targetTab = $(this).attr('href');
            
            // Update tab navigation
            $('.came-tab').removeClass('active');
            $(this).addClass('active');
            
            // Show target tab content
            $('.came-tab-content').removeClass('active');
            $(targetTab).addClass('active');
        });
    }
    
    /**
     * Initialize modals
     */
    function initModals() {
        // Close modal when clicking the X or outside the modal
        $('.came-modal-close, .came-modal').on('click', function(e) {
            if ($(e.target).hasClass('came-modal') || $(e.target).hasClass('came-modal-close')) {
                hideModals();
            }
        });
        
        // Add configuration confirm button
        $('#came-add-config-confirm').on('click', function() {
            var configName = $('#came-new-config-name').val();
            if (configName) {
                addConfiguration(configName);
                hideModals();
            } else {
                alert('Please enter a name for the configuration');
            }
        });
        
        // Add configuration cancel button
        $('#came-add-config-cancel').on('click', function() {
            hideModals();
        });
    }
    
    /**
     * Show the Add Configuration modal
     */
    function showAddConfigModal() {
        $('#came-new-config-name').val('');
        $('#came-add-config-modal').show();
    }
    
    /**
     * Hide all modals
     */
    function hideModals() {
        $('.came-modal').hide();
    }

    /**
     * Initialize event handlers
     */
    function initEventHandlers() {
        // Save menu button
        $('#came-save-menu').on('click', function() {
            saveMenuSettings();
        });
        
        // Reset menu button
        $('#came-reset-menu').on('click', function() {
            if (confirm(came_data.text_confirm_reset || 'Are you sure you want to reset the menu to default?')) {
                resetMenuSettings();
            }
        });
        
        // Select menu item - use delegation with specific targeting
        $(document).on('click', '.came-menu-item > .came-item-header', function(e) {
            // Skip if we clicked on handle or toggle
            if ($(e.target).hasClass('came-item-handle') || 
                $(e.target).hasClass('came-item-toggle') ||
                $(e.target).closest('.came-item-toggle').length > 0) {
                return;
            }
            selectMenuItem($(this).closest('.came-menu-item'));
        });
        
        // Select submenu item - use delegation
        $(document).on('click', '.came-submenu-item', function(e) {
            // Skip if we clicked on handle
            if ($(e.target).hasClass('came-item-handle')) {
                return;
            }
            selectSubmenuItem($(this));
        });
        
        // Toggle submenu visibility - use delegation with specific targeting
        $(document).on('click', '.came-item-toggle', function(e) {
            e.stopPropagation(); // Prevent bubbling to parent click handlers
            var $menuItem = $(this).closest('.came-menu-item');
            toggleSubmenu($menuItem);
        });
        
        // Auto-apply when item properties change
        $('#came-item-name').on('input', function() {
            // Small delay to avoid too many updates
            clearTimeout($(this).data('timer'));
            $(this).data('timer', setTimeout(function() {
                applyItemChanges();
            }, 500));
        });
        
        $('#came-item-hidden').on('change', function() {
            applyItemChanges();
        });
        
        // Auto-apply when role checkboxes change
        $(document).on('change', '.came-item-role-option input[type="checkbox"]', function() {
            updateItemRoles();
        });
        
        // Update configuration name when input changes
        $('#came-config-name').on('input', function() {
            menuData.configName = $(this).val();
        });
    }

    /**
     * Initialize sortable menu
     */
    function initSortable() {
        // Check if jQuery UI sortable is available
        if ($.fn.sortable) {
            // Make main menu sortable
            $('#came-menu-tree').sortable({
                handle: '.came-item-handle',
                placeholder: 'came-sortable-placeholder',
                update: function() {
                    updateMenuOrder();
                }
            });
            
            // Make submenus sortable
            $('.came-submenu-list').sortable({
                handle: '.came-item-handle',
                placeholder: 'came-sortable-placeholder',
                update: function(event, ui) {
                    var parentId = $(this).closest('.came-menu-item').data('item-id');
                    updateSubmenuOrder(parentId);
                }
            });
        } else {
            console.warn('jQuery UI Sortable not available. Drag and drop functionality disabled.');
        }
    }

    /**
     * Render menu tree - completely clear and rebuild
     */
    function renderMenuTree() {
        var $menuTree = $('#came-menu-tree');
        $menuTree.empty();
        
        if (!menuData.items || menuData.items.length === 0) {
            $menuTree.html('<div class="notice notice-info" style="margin: 20px 0;"><p>No menu items available for this configuration. Save to create a new menu structure.</p></div>');
            return;
        }
        
        // Debug check - look for duplicates before rendering
        checkForDuplicateItems();
        
        // Render each menu item
        $.each(menuData.items, function(index, item) {
            renderMenuItem($menuTree, item);
        });
        
        // Initialize sortable for new items
        initSortable();
        
        console.log('Menu tree rendered for configuration:', menuData.configId);
    }
    
    /**
     * Utility to check for duplicate items
     */
    function checkForDuplicateItems() {
        var urlCounts = {};
        
        // Check main menu items
        $.each(menuData.items, function(i, item) {
            if (!urlCounts[item.url]) {
                urlCounts[item.url] = 1;
            } else {
                console.warn('Duplicate main menu item found for URL:', item.url);
                urlCounts[item.url]++;
            }
            
            // Also check submenu items
            if (item.submenu && item.submenu.length) {
                var submenuUrlCounts = {};
                
                $.each(item.submenu, function(j, subitem) {
                    if (!submenuUrlCounts[subitem.url]) {
                        submenuUrlCounts[subitem.url] = 1;
                    } else {
                        console.warn('Duplicate submenu item found for URL:', subitem.url, 'under parent:', item.url);
                        submenuUrlCounts[subitem.url]++;
                    }
                });
            }
        });
    }

    /**
     * Render a menu item
     */
    function renderMenuItem($container, item) {
        // Get the menu item template
        var template = $('#came-menu-item-template').html();
        
        // Replace template variables with actual values
        var html = template
            .replace(/{id}/g, item.id || '')
            .replace(/{name}/g, item.name || 'Menu Item')
            .replace(/{url}/g, item.url || '');
        
        var $menuItem = $(html);
        
        // Add hidden class if needed
        if (item.hidden) {
            $menuItem.addClass('came-item-hidden');
        }
        
        // Append to container
        $container.append($menuItem);
        
        // Clear any submenu content to prevent duplicates
        var $submenuList = $menuItem.find('.came-submenu-list');
        $submenuList.empty();
        
        // Render submenu items if they exist
        if (item.submenu && item.submenu.length > 0) {
            $.each(item.submenu, function(index, subItem) {
                renderSubmenuItem($submenuList, subItem);
            });
        }
    }

    /**
     * Render a submenu item
     */
    function renderSubmenuItem($container, item) {
        // Get the submenu item template
        var template = $('#came-submenu-item-template').html();
        
        // Replace template variables with actual values
        var html = template
            .replace(/{id}/g, item.id || '')
            .replace(/{name}/g, item.name || 'Submenu Item')
            .replace(/{url}/g, item.url || '');
        
        var $submenuItem = $(html);
        
        // Add hidden class if needed
        if (item.hidden) {
            $submenuItem.addClass('came-item-hidden');
        }
        
        // Append to container
        $container.append($submenuItem);
    }

    /**
     * Toggle submenu visibility
     */
    function toggleSubmenu($menuItem) {
        var $submenuContainer = $menuItem.find('.came-submenu-container');
        var $toggle = $menuItem.find('.came-item-toggle');
        
        if ($submenuContainer.is(':visible')) {
            $submenuContainer.slideUp(200);
            $toggle.removeClass('dashicons-arrow-up').addClass('dashicons-arrow-down');
        } else {
            $submenuContainer.slideDown(200);
            $toggle.removeClass('dashicons-arrow-down').addClass('dashicons-arrow-up');
        }
    }

    /**
     * Select a menu item
     */
    function selectMenuItem($menuItem) {
        // Deselect all items
        $('.came-menu-item, .came-submenu-item').removeClass('selected');
        
        // Select this item
        $menuItem.addClass('selected');
        
        // Store item type
        menuData.currentItemType = 'menu';
        
        // Get item data
        var itemId = $menuItem.data('item-id');
        menuData.currentItemId = itemId;
        
        // Find menu item
        var itemIndex = findMenuItemIndex(itemId);
        
        if (itemIndex !== -1) {
            var item = menuData.items[itemIndex];
            
            // Display item properties
            displayItemProperties(item, 'menu');
        }
    }

    /**
     * Select a submenu item
     */
    function selectSubmenuItem($submenuItem) {
        // Deselect all items
        $('.came-menu-item, .came-submenu-item').removeClass('selected');
        
        // Select this item
        $submenuItem.addClass('selected');
        
        // Store item type
        menuData.currentItemType = 'submenu';
        
        // Get item data
        var itemId = $submenuItem.data('item-id');
        var parentId = $submenuItem.closest('.came-menu-item').data('item-id');
        menuData.currentItemId = itemId;
        
        // Find submenu item
        var parentIndex = findMenuItemIndex(parentId);
        
        if (parentIndex !== -1) {
            var submenuIndex = findSubmenuItemIndex(parentIndex, itemId);
            
            if (submenuIndex !== -1) {
                var item = menuData.items[parentIndex].submenu[submenuIndex];
                
                // Display item properties
                displayItemProperties(item, 'submenu');
            }
        }
    }

    /**
     * Display item properties
     */
    function displayItemProperties(item, itemType) {
        // Show properties form
        $('.came-no-selection').hide();
        $('.came-properties-form').show();
        
        // Set values
        $('#came-item-name').val(item.name || '');
        $('#came-item-hidden').prop('checked', item.hidden || false);
        
        // Set item information
        $('#came-item-type').text(itemType === 'menu' ? 'Main Menu Item' : 'Submenu Item');
        $('#came-item-url').text(item.url || '');
        
        // Update role checkboxes
        updateRoleCheckboxes(item);
    }
    
    /**
     * Update role checkboxes based on selected item
     */
    function updateRoleCheckboxes(item) {
        // Get all available roles
        var allRoles = $('.came-item-role-option input[type="checkbox"]').map(function() {
            return $(this).val();
        }).get();
        
        // Clear all checkboxes
        $('.came-item-role-option input[type="checkbox"]').prop('checked', false);
        
        // Set checkboxes based on item roles
        if (item.roles && Array.isArray(item.roles)) {
            $.each(item.roles, function(i, role) {
                $('.came-item-role-option input[value="' + role + '"]').prop('checked', true);
            });
        } else {
            // If no roles set, check all by default
            $('.came-item-role-option input[type="checkbox"]').prop('checked', true);
        }
    }

    /**
     * Apply item changes automatically
     */
    function applyItemChanges() {
        if (!menuData.currentItemId || !menuData.currentItemType) {
            return;
        }
        
        // Get values
        var name = $('#came-item-name').val();
        var hidden = $('#came-item-hidden').is(':checked');
        
        if (menuData.currentItemType === 'menu') {
            // Find selected menu item
            var $selectedItem = $('.came-menu-item.selected');
            var itemIndex = findMenuItemIndex(menuData.currentItemId);
            
            if (itemIndex !== -1 && $selectedItem.length) {
                // Update item data
                menuData.items[itemIndex].name = name;
                menuData.items[itemIndex].hidden = hidden;
                
                // Update display
                $selectedItem.find('> .came-item-header .came-item-title').text(name);
                
                if (hidden) {
                    $selectedItem.addClass('came-item-hidden');
                } else {
                    $selectedItem.removeClass('came-item-hidden');
                }
            }
        } else if (menuData.currentItemType === 'submenu') {
            // Find selected submenu item
            var $selectedSubItem = $('.came-submenu-item.selected');
            var parentId = $selectedSubItem.closest('.came-menu-item').data('item-id');
            var parentIndex = findMenuItemIndex(parentId);
            
            if (parentIndex !== -1) {
                var submenuIndex = findSubmenuItemIndex(parentIndex, menuData.currentItemId);
                
                if (submenuIndex !== -1 && $selectedSubItem.length) {
                    // Update item data
                    menuData.items[parentIndex].submenu[submenuIndex].name = name;
                    menuData.items[parentIndex].submenu[submenuIndex].hidden = hidden;
                    
                    // Update display
                    $selectedSubItem.find('.came-item-title').text(name);
                    
                    if (hidden) {
                        $selectedSubItem.addClass('came-item-hidden');
                    } else {
                        $selectedSubItem.removeClass('came-item-hidden');
                    }
                }
            }
        }
        
        // Show subtle success message
        showStatusMessage('Changes applied automatically', 'success', 1500);
    }
    
    /**
     * Update item role settings
     */
    function updateItemRoles() {
        if (!menuData.currentItemId || !menuData.currentItemType) {
            return;
        }
        
        // Get selected roles
        var selectedRoles = $('.came-item-role-option input:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (menuData.currentItemType === 'menu') {
            var itemIndex = findMenuItemIndex(menuData.currentItemId);
            
            if (itemIndex !== -1) {
                // Update item roles
                menuData.items[itemIndex].roles = selectedRoles;
            }
        } else if (menuData.currentItemType === 'submenu') {
            var $selectedSubItem = $('.came-submenu-item.selected');
            var parentId = $selectedSubItem.closest('.came-menu-item').data('item-id');
            var parentIndex = findMenuItemIndex(parentId);
            
            if (parentIndex !== -1) {
                var submenuIndex = findSubmenuItemIndex(parentIndex, menuData.currentItemId);
                
                if (submenuIndex !== -1) {
                    // Update submenu item roles
                    menuData.items[parentIndex].submenu[submenuIndex].roles = selectedRoles;
                }
            }
        }
        
        // Show subtle success message
        showStatusMessage('Role settings updated', 'success', 1500);
    }

    /**
     * Update menu order after sorting
     */
    function updateMenuOrder() {
        var newItems = [];
        
        // Get new order
        $('#came-menu-tree > .came-menu-item').each(function() {
            var itemId = $(this).data('item-id');
            var itemIndex = findMenuItemIndex(itemId);
            
            if (itemIndex !== -1) {
                newItems.push(menuData.items[itemIndex]);
            }
        });
        
        // Update menu data
        menuData.items = newItems;
        
        // Show success message
        showStatusMessage('Menu order updated. Don\'t forget to save your changes.', 'success');
    }

    /**
     * Update submenu order after sorting
     */
    function updateSubmenuOrder(parentId) {
        var parentIndex = findMenuItemIndex(parentId);
        
        if (parentIndex === -1) {
            return;
        }
        
        var newSubmenu = [];
        
        // Get new order
        $('.came-menu-item[data-item-id="' + parentId + '"] .came-submenu-list > .came-submenu-item').each(function() {
            var itemId = $(this).data('item-id');
            var submenuIndex = findSubmenuItemIndex(parentIndex, itemId);
            
            if (submenuIndex !== -1) {
                newSubmenu.push(menuData.items[parentIndex].submenu[submenuIndex]);
            }
        });
        
        // Update submenu data
        menuData.items[parentIndex].submenu = newSubmenu;
        
        // Show success message
        showStatusMessage('Submenu order updated. Don\'t forget to save your changes.', 'success');
    }
    
    /**
     * Load a configuration
     */
    function loadConfiguration(configId) {
        // Skip if the ID is the same as current
        if (configId === menuData.configId) {
            return;
        }
        
        // Show loading message
        showStatusMessage('Loading configuration...', 'info');
        
        // Prepare data
        var data = {
            action: 'came_load_config',
            nonce: came_data.nonce,
            config_id: configId
        };
        
        // Send AJAX request
        $.post(came_data.ajax_url, data, function(response) {
            console.log('Load configuration response:', response);
            
            if (response.success) {
                // Update menu data
                menuData.configId = response.data.config_id;
                menuData.configName = response.data.config_name;
                menuData.items = response.data.menu_items || [];
                
                // Update UI
                $('#came-config-name').val(menuData.configName);
                
                // Update global roles
                var allowedRoles = response.data.allowed_roles || [];
                $('input[name="came_allowed_roles[]"]').prop('checked', false);
                $.each(allowedRoles, function(i, role) {
                    $('input[name="came_allowed_roles[]"][value="' + role + '"]').prop('checked', true);
                });
                
                // Render menu tree
                renderMenuTree();
                
                // Show success message
                showStatusMessage(response.data.message, 'success');
            } else {
                showStatusMessage(response.data.message || 'Error loading configuration.', 'error');
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX error:', status, error);
            showStatusMessage('Error loading configuration. Please check browser console for details.', 'error');
        });
    }
    
    /**
     * Add a new configuration
     */
    function addConfiguration(configName) {
        // Show loading message
        showStatusMessage('Creating new configuration...', 'info');
        
        // Prepare data
        var data = {
            action: 'came_add_config',
            nonce: came_data.nonce,
            config_name: configName
        };
        
        // Send AJAX request
        $.post(came_data.ajax_url, data, function(response) {
            console.log('Add configuration response:', response);
            
            if (response.success) {
                // Update data
                menuData.configId = response.data.config_id;
                menuData.configName = configName;
                menuData.items = []; // Start with empty menu
                
                // Update UI
                $('#came-config-name').val(menuData.configName);
                
                // Update config dropdown
                updateConfigDropdown(response.data.all_configs, menuData.configId);
                
                // Reset roles
                $('input[name="came_allowed_roles[]"]').prop('checked', false);
                $('input[name="came_allowed_roles[]"][value="administrator"]').prop('checked', true);
                
                // Render menu tree with default items
                menuData.items = came_data.admin_menu;
                renderMenuTree();
                
                // Show success message
                showStatusMessage(response.data.message, 'success');
            } else {
                showStatusMessage(response.data.message || 'Error creating configuration.', 'error');
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX error:', status, error);
            showStatusMessage('Error creating configuration. Please check browser console for details.', 'error');
        });
    }
    
    /**
     * Delete a configuration
     */
    function deleteConfiguration(configId) {
        // Show loading message
        showStatusMessage('Deleting configuration...', 'info');
        
        // Prepare data
        var data = {
            action: 'came_delete_config',
            nonce: came_data.nonce,
            config_id: configId
        };
        
        // Send AJAX request
        $.post(came_data.ajax_url, data, function(response) {
            console.log('Delete configuration response:', response);
            
            if (response.success) {
                // Update config dropdown
                updateConfigDropdown(response.data.all_configs, response.data.config_id);
                
                // Load the new active configuration
                loadConfiguration(response.data.config_id);
                
                // Show success message
                showStatusMessage(response.data.message, 'success');
            } else {
                showStatusMessage(response.data.message || 'Error deleting configuration.', 'error');
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX error:', status, error);
            showStatusMessage('Error deleting configuration. Please check browser console for details.', 'error');
        });
    }
    
    /**
     * Update configuration dropdown
     */
    function updateConfigDropdown(configs, selectedId) {
        var $dropdown = $('#came-config-dropdown');
        $dropdown.empty();
        
        $.each(configs, function(id, config) {
            var $option = $('<option></option>').val(id).text(config.name);
            if (id === selectedId) {
                $option.prop('selected', true);
            }
            $dropdown.append($option);
        });
    }

    /**
     * Save menu settings to server
     */
    function saveMenuSettings() {
        // Get role settings
        var allowedRoles = [];
        $('input[name="came_allowed_roles[]"]:checked').each(function() {
            allowedRoles.push($(this).val());
        });
        
        // Make sure we have at least one role selected
        if (allowedRoles.length === 0) {
            showStatusMessage('Please select at least one user role to apply changes to.', 'error');
            return;
        }
        
        // Get configuration name
        var configName = $('#came-config-name').val();
        if (!configName) {
            showStatusMessage('Please enter a name for this configuration.', 'error');
            return;
        }
        
        // Prepare data
        var data = {
            action: 'came_save_menu',
            nonce: came_data.nonce,
            menu_data: JSON.stringify(menuData.items),
            allowed_roles: allowedRoles,
            config_id: menuData.configId,
            config_name: configName
        };
        
        // Show loading message
        showStatusMessage('Saving menu settings...', 'info');
        
        // Log AJAX request for debugging
        console.log('Sending AJAX save request with data:', data);
        
        // Send AJAX request
        $.post(came_data.ajax_url, data, function(response) {
            console.log('AJAX response:', response);
            
            if (response.success) {
                // Update configuration dropdown
                updateConfigDropdown(response.data.all_configs, response.data.config_id);
                
                showStatusMessage(response.data.message, 'success');
            } else {
                showStatusMessage(response.data.message || 'Error saving settings.', 'error');
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX error:', status, error);
            showStatusMessage('Error saving menu settings. Please check browser console for details.', 'error');
        });
    }
    
    /**
     * Reset menu settings
     */
    function resetMenuSettings() {
        // Check if we have a configuration ID
        if (!menuData.configId) {
            showStatusMessage('No active configuration to reset.', 'error');
            return;
        }
        
        // Show loading message
        showStatusMessage('Resetting menu settings...', 'info');
        
        // Prepare data
        var data = {
            action: 'came_reset_menu',
            nonce: came_data.nonce,
            config_id: menuData.configId
        };
        
        // Send AJAX request
        $.post(came_data.ajax_url, data, function(response) {
            console.log('Reset response:', response);
            
            if (response.success) {
                // Reset menu data
                menuData.items = came_data.admin_menu || [];
                
                // Render menu tree
                renderMenuTree();
                
                showStatusMessage(response.data.message, 'success');
            } else {
                showStatusMessage(response.data.message || 'Error resetting settings.', 'error');
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX error:', status, error);
            showStatusMessage('Error resetting menu settings.', 'error');
        });
    }
    
    /**
     * Show status message
     */
    function showStatusMessage(message, type, duration) {
        var $statusMessage = $('.came-status-message');
        
        // Set message and class
        $statusMessage.text(message).removeClass('success error info').addClass(type);
        
        // Show message
        $statusMessage.fadeIn(200);
        
        // Auto hide after specified duration or default (5s for success, stay for others)
        if (type === 'success') {
            setTimeout(function() {
                $statusMessage.fadeOut(200);
            }, duration || 5000);
        }
    }
    
    /**
     * Find menu item index by ID
     */
    function findMenuItemIndex(itemId) {
        for (var i = 0; i < menuData.items.length; i++) {
            if (menuData.items[i].id === itemId) {
                return i;
            }
        }
        
        return -1;
    }
    
    /**
     * Find submenu item index by ID
     */
    function findSubmenuItemIndex(parentIndex, itemId) {
        if (!menuData.items[parentIndex].submenu) {
            return -1;
        }
        
        for (var i = 0; i < menuData.items[parentIndex].submenu.length; i++) {
            if (menuData.items[parentIndex].submenu[i].id === itemId) {
                return i;
            }
        }
        
        return -1;
    }
    
})(jQuery);
