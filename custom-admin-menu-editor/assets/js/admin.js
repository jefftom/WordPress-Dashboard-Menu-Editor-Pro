/**
 * Custom Admin Menu Editor JavaScript
 */

(function($) {
    'use strict';

    // Store menu data
    var menuData = {
        items: [],
        currentItemId: null
    };

    // Store color settings
    var colorSettings = {
        menu_bg_color: '#23282d',
        menu_text_color: '#ffffff',
        hover_bg_color: '#0073aa',
        hover_text_color: '#ffffff'
    };

    // Initialize plugin
    $(document).ready(function() {
        // Load menu data
        loadMenuData();
        
        // Initialize tabs
        initTabs();
        
        // Initialize event handlers
        initEventHandlers();
        
        // Initialize sortable
        initSortable();
    });

    /**
     * Load menu data from WordPress data
     */
    function loadMenuData() {
        // Get data from localized script
        var adminMenu = came_data.admin_menu;
        
        // Check if we have saved menu data
        if (typeof came_data.saved_menu !== 'undefined' && came_data.saved_menu.length > 0) {
            // Use saved menu data
            menuData.items = came_data.saved_menu;
        } else {
            // Use default admin menu
            menuData.items = adminMenu;
        }
        
        // Check if we have color settings
        if (typeof came_data.color_settings !== 'undefined') {
            colorSettings = came_data.color_settings;
            applyColorSettings();
        }
        
        // Render menu tree
        renderMenuTree();
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
     * Initialize event handlers
     */
    function initEventHandlers() {
        // Save menu button
        $('#came-save-menu').on('click', function() {
            saveMenuSettings();
        });
        
        // Reset menu button
        $('#came-reset-menu').on('click', function() {
            if (confirm(came_data.text_confirm_reset)) {
                resetMenuSettings();
            }
        });
        
        // Add menu item button
        $('#came-add-menu-item').on('click', function() {
            addMenuItem();
        });
        
        // Add separator button
        $('#came-add-separator').on('click', function() {
            addSeparator();
        });
        
        // Apply property changes button
        $('#came-apply-changes').on('click', function() {
            applyItemChanges();
        });
        
        // Delete item button
        $('#came-delete-item').on('click', function() {
            deleteSelectedItem();
        });
        
        // Select icon button
        $('#came-select-icon').on('click', function() {
            toggleIconPicker();
        });
        
        // Close icon picker button
        $('#came-icon-picker-close').on('click', function() {
            $('#came-icon-picker').hide();
        });
        
        // Icon search
        $('#came-icon-search').on('input', function() {
            filterIcons($(this).val());
        });
        
        // Icon selection
        $(document).on('click', '.came-icon-option', function() {
            selectIcon($(this).data('icon'));
        });
        
        // Apply color settings
        $('#came-apply-colors').on('click', function() {
            saveColorSettings();
        });
        
        // Reset color settings
        $('#came-reset-colors').on('click', function() {
            resetColorSettings();
        });
        
        // Toggle submenu visibility
        $(document).on('click', '.came-item-toggle', function(e) {
            e.stopPropagation();
            var $menuItem = $(this).closest('.came-menu-item');
            toggleSubmenu($menuItem);
        });
        
        // Add submenu item
        $(document).on('click', '.came-add-submenu-button', function() {
            var parentId = $(this).closest('.came-menu-item').data('item-id');
            addSubmenuItem(parentId);
        });
        
        // Select menu item
        $(document).on('click', '.came-menu-item', function(e) {
            if (!$(e.target).hasClass('came-item-handle')) {
                selectMenuItem($(this));
            }
        });
        
        // Select submenu item
        $(document).on('click', '.came-submenu-item', function(e) {
            if (!$(e.target).hasClass('came-item-handle')) {
                selectSubmenuItem($(this));
            }
        });
    }

    /**
     * Initialize sortable menu
     */
    function initSortable() {
        // Make main menu sortable
        $('#came-menu-tree').sortable({
            handle: '.came-item-handle',
            placeholder: 'came-sortable-placeholder',
            forcePlaceholderSize: true,
            update: function() {
                updateMenuOrder();
            }
        });
        
        // Make submenus sortable
        $('.came-submenu-list').sortable({
            handle: '.came-item-handle',
            placeholder: 'came-sortable-placeholder',
            forcePlaceholderSize: true,
            containment: 'parent',
            update: function() {
                updateSubmenuOrder($(this).closest('.came-menu-item').data('item-id'));
            }
        });
    }

    /**
     * Render menu tree
     */
    function renderMenuTree() {
        var $menuTree = $('#came-menu-tree');
        $menuTree.empty();
        
        // Render each menu item
        $.each(menuData.items, function(index, item) {
            renderMenuItem($menuTree, item);
        });
        
        // Reinitialize sortable for new items
        initSortable();
    }

    /**
     * Render a menu item
     */
    function renderMenuItem($container, item) {
        var template = '';
        var itemHtml = '';
        
        if (item.separator) {
            // This is a separator
            template = $('#came-separator-template').html();
            itemHtml = template.replace('{id}', item.id);
        } else {
            // This is a regular menu item
            template = $('#came-menu-item-template').html();
            
            itemHtml = template
                .replace(/{id}/g, item.id)
                .replace(/{name}/g, item.name)
                .replace(/{icon}/g, item.icon || 'dashicons-admin-generic');
        }
        
        var $menuItem = $(itemHtml);
        
        // Add hidden class if needed
        if (item.hidden) {
            $menuItem.addClass('came-item-hidden');
        } else {
            $menuItem.removeClass('came-item-hidden');
        }
        
        // Append to container
        $container.append($menuItem);
        
        // Render submenu if this is not a separator
        if (!item.separator && item.submenu && item.submenu.length > 0) {
            var $submenuList = $menuItem.find('.came-submenu-list');
            
            // Render each submenu item
            $.each(item.submenu, function(index, subItem) {
                renderSubmenuItem($submenuList, subItem);
            });
        }
    }

    /**
     * Render a submenu item
     */
    function renderSubmenuItem($container, item) {
        var template = $('#came-submenu-item-template').html();
        
        var itemHtml = template
            .replace(/{id}/g, item.id)
            .replace(/{name}/g, item.name);
        
        var $submenuItem = $(itemHtml);
        
        // Add hidden class if needed
        if (item.hidden) {
            $submenuItem.addClass('came-item-hidden');
        } else {
            $submenuItem.removeClass('came-item-hidden');
        }
        
        // Append to container
        $container.append($submenuItem);
    }

    /**
     * Add a new menu item
     */
    function addMenuItem() {
        var newId = 'menu-custom-' + Date.now();
        
        var newItem = {
            id: newId,
            name: 'Custom Menu',
            url: 'custom-page-' + Date.now(),
            capability: 'read',
            icon: 'dashicons-admin-generic',
            position: menuData.items.length,
            custom: true,
            submenu: []
        };
        
        // Add to menu data
        menuData.items.push(newItem);
        
        // Render new item
        renderMenuItem($('#came-menu-tree'), newItem);
        
        // Select the new item
        selectMenuItem($('#came-menu-tree').find('.came-menu-item:last'));
        
        // Show success message
        showStatusMessage('Menu item added. Don\'t forget to save your changes.', 'success');
    }

    /**
     * Add a separator
     */
    function addSeparator() {
        var newId = 'separator-' + Date.now();
        
        var newItem = {
            id: newId,
            separator: true,
            position: menuData.items.length
        };
        
        // Add to menu data
        menuData.items.push(newItem);
        
        // Render new separator
        renderMenuItem($('#came-menu-tree'), newItem);
        
        // Show success message
        showStatusMessage('Separator added. Don\'t forget to save your changes.', 'success');
    }

    /**
     * Add a new submenu item
     */
    function addSubmenuItem(parentId) {
        var newId = 'submenu-custom-' + Date.now();
        
        var newItem = {
            id: newId,
            name: 'Custom Submenu',
            url: 'custom-subpage-' + Date.now(),
            capability: 'read',
            position: 0,
            custom: true
        };
        
        // Find parent menu item
        var parentIndex = findMenuItemIndex(parentId);
        
        if (parentIndex !== -1) {
            // Calculate position
            if (menuData.items[parentIndex].submenu) {
                newItem.position = menuData.items[parentIndex].submenu.length;
            } else {
                menuData.items[parentIndex].submenu = [];
            }
            
            // Add to submenu
            menuData.items[parentIndex].submenu.push(newItem);
            
            // Render submenu
            var $menuItem = $('.came-menu-item[data-item-id="' + parentId + '"]');
            var $submenuList = $menuItem.find('.came-submenu-list');
            
            renderSubmenuItem($submenuList, newItem);
            
            // Show submenu if it's hidden
            if ($menuItem.find('.came-submenu-container').is(':hidden')) {
                toggleSubmenu($menuItem);
            }
            
            // Select the new submenu item
            selectSubmenuItem($submenuList.find('.came-submenu-item:last'));
            
            // Show success message
            showStatusMessage('Submenu item added. Don\'t forget to save your changes.', 'success');
        }
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
        
        // Get item data
        var itemId = $menuItem.data('item-id');
        menuData.currentItemId = itemId;
        
        // Find menu item
        var itemIndex = findMenuItemIndex(itemId);
        
        if (itemIndex !== -1) {
            var item = menuData.items[itemIndex];
            
            // Display item properties
            displayItemProperties(item);
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
                displayItemProperties(item, true);
            }
        }
    }

    /**
     * Display item properties
     */
    function displayItemProperties(item, isSubmenu) {
        // Show properties form
        $('.came-no-selection').hide();
        $('.came-properties-form').show();
        
        // Set values
        $('#came-item-name').val(item.name || '');
        $('#came-item-url').val(item.url || '');
        $('#came-item-capability').val(item.capability || '');
        $('#came-item-hidden').prop('checked', item.hidden || false);
        
        // Icon (only for main menu items)
        if (!isSubmenu) {
            $('#came-item-icon').val(item.icon || '');
            updateIconPreview(item.icon || '');
            $('.came-icon-selector').show();
        } else {
            $('.came-icon-selector').hide();
        }
        
        // Custom item content
        if (item.custom) {
            $('#came-item-custom-content').val(item.custom_content || '');
            $('.came-custom-item-only').show();
        } else {
            $('.came-custom-item-only').hide();
        }
    }

    /**
     * Apply item changes
     */
    function applyItemChanges() {
        if (!menuData.currentItemId) {
            return;
        }
        
        // Get values
        var name = $('#came-item-name').val();
        var url = $('#came-item-url').val();
        var capability = $('#came-item-capability').val();
        var icon = $('#came-item-icon').val();
        var hidden = $('#came-item-hidden').is(':checked');
        var customContent = $('#came-item-custom-content').val();
        
        // Find selected item
        var $selectedItem = $('.came-menu-item.selected, .came-submenu-item.selected');
        
        if ($selectedItem.hasClass('came-menu-item')) {
            // This is a main menu item
            var itemIndex = findMenuItemIndex(menuData.currentItemId);
            
            if (itemIndex !== -1) {
                // Update item data
                menuData.items[itemIndex].name = name;
                menuData.items[itemIndex].url = url;
                menuData.items[itemIndex].capability = capability;
                menuData.items[itemIndex].icon = icon;
                menuData.items[itemIndex].hidden = hidden;
                
                if (menuData.items[itemIndex].custom) {
                    menuData.items[itemIndex].custom_content = customContent;
                }
                
                // Update display
                $selectedItem.find('.came-item-title').text(name);
                $selectedItem.find('.came-item-icon').attr('class', 'came-item-icon dashicons ' + icon);
                
                if (hidden) {
                    $selectedItem.addClass('came-item-hidden');
                } else {
                    $selectedItem.removeClass('came-item-hidden');
                }
            }
        } else if ($selectedItem.hasClass('came-submenu-item')) {
            // This is a submenu item
            var parentId = $selectedItem.closest('.came-menu-item').data('item-id');
            var parentIndex = findMenuItemIndex(parentId);
            
            if (parentIndex !== -1) {
                var submenuIndex = findSubmenuItemIndex(parentIndex, menuData.currentItemId);
                
                if (submenuIndex !== -1) {
                    // Update item data
                    menuData.items[parentIndex].submenu[submenuIndex].name = name;
                    menuData.items[parentIndex].submenu[submenuIndex].url = url;
                    menuData.items[parentIndex].submenu[submenuIndex].capability = capability;
                    menuData.items[parentIndex].submenu[submenuIndex].hidden = hidden;
                    
                    if (menuData.items[parentIndex].submenu[submenuIndex].custom) {
                        menuData.items[parentIndex].submenu[submenuIndex].custom_content = customContent;
                    }
                    
                    // Update display
                    $selectedItem.find('.came-item-title').text(name);
                    
                    if (hidden) {
                        $selectedItem.addClass('came-item-hidden');
                    } else {
                        $selectedItem.removeClass('came-item-hidden');
                    }
                }
            }
        }
        
        // Show success message
        showStatusMessage('Item updated. Don\'t forget to save your changes.', 'success');
    }

    /**
     * Delete selected item
     */
    function deleteSelectedItem() {
        if (!menuData.currentItemId) {
            return;
        }
        
        if (!confirm('Are you sure you want to delete this item?')) {
            return;
        }
        
        // Find selected item
        var $selectedItem = $('.came-menu-item.selected, .came-submenu-item.selected');
        
        if ($selectedItem.hasClass('came-menu-item')) {
            // This is a main menu item
            var itemIndex = findMenuItemIndex(menuData.currentItemId);
            
            if (itemIndex !== -1) {
                // Remove from data
                menuData.items.splice(itemIndex, 1);
                
                // Remove from display
                $selectedItem.remove();
            }
        } else if ($selectedItem.hasClass('came-submenu-item')) {
            // This is a submenu item
            var parentId = $selectedItem.closest('.came-menu-item').data('item-id');
            var parentIndex = findMenuItemIndex(parentId);
            
            if (parentIndex !== -1) {
                var submenuIndex = findSubmenuItemIndex(parentIndex, menuData.currentItemId);
                
                if (submenuIndex !== -1) {
                    // Remove from data
                    menuData.items[parentIndex].submenu.splice(submenuIndex, 1);
                    
                    // Remove from display
                    $selectedItem.remove();
                }
            }
        }
        
        // Reset current item
        menuData.currentItemId = null;
        
        // Hide properties form
        $('.came-properties-form').hide();
        $('.came-no-selection').show();
        
        // Show success message
        showStatusMessage('Item deleted. Don\'t forget to save your changes.', 'success');
    }

    /**
     * Update menu order after sorting
     */
    function updateMenuOrder() {
        var newItems = [];
        
        // Get new order
        $('#came-menu-tree .came-menu-item').each(function() {
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
        $('.came-menu-item[data-item-id="' + parentId + '"] .came-submenu-item').each(function() {
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
     * Toggle icon picker
     */
    function toggleIconPicker() {
        $('#came-icon-picker').toggle();
        
        // Focus search field
        if ($('#came-icon-picker').is(':visible')) {
            $('#came-icon-search').focus();
        }
    }

    /**
     * Filter icons by search term
     */
    function filterIcons(searchTerm) {
        searchTerm = searchTerm.toLowerCase();
        
        $('.came-icon-option').each(function() {
            var iconName = $(this).data('icon').toLowerCase();
            
            if (iconName.indexOf(searchTerm) !== -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }

    /**
     * Select an icon
     */
    function selectIcon(iconClass) {
        // Set icon value
        $('#came-item-icon').val(iconClass);
        
        // Update preview
        updateIconPreview(iconClass);
        
        // Hide picker
        $('#came-icon-picker').hide();
    }

    /**
     * Update icon preview
     */
    function updateIconPreview(iconClass) {
        $('#came-icon-preview').attr('class', 'dashicons ' + iconClass);
    }

    /**
     * Save color settings
     */
    function saveColorSettings() {
        // Get color values
        colorSettings.menu_bg_color = $('#came-menu-bg-color').val();
        colorSettings.menu_text_color = $('#came-menu-text-color').val();
        colorSettings.hover_bg_color = $('#came-hover-bg-color').val();
        colorSettings.hover_text_color = $('#came-hover-text-color').val();
        
        // Apply and save
        applyColorSettings();
        
        // Show success message
        showStatusMessage('Color settings updated. Don\'t forget to save your changes.', 'success');
    }

    /**
     * Reset color settings
     */
    function resetColorSettings() {
        // Reset to defaults
        colorSettings = {
            menu_bg_color: '#23282d',
            menu_text_color: '#ffffff',
            hover_bg_color: '#0073aa',
            hover_text_color: '#ffffff'
        };
        
        // Update form values
        $('#came-menu-bg-color').val(colorSettings.menu_bg_color);
        $('#came-menu-text-color').val(colorSettings.menu_text_color);
        $('#came-hover-bg-color').val(colorSettings.hover_bg_color);
        $('#came-hover-text-color').val(colorSettings.hover_text_color);
        
        // Apply settings
        applyColorSettings();
        
        // Show success message
        showStatusMessage('Color settings reset. Don\'t forget to save your changes.', 'success');
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
        
        // Get advanced settings
        var showMenuForAdmins = $('#came-show-menu-for-admins').is(':checked');
        var preserveSettings = $('#came-preserve-settings').is(':checked');
        
        // Prepare data
        var data = {
            action: 'came_save_menu',
            nonce: came_data.nonce,
            menu_data: JSON.stringify(menuData.items),
            allowed_roles: allowedRoles,
            color_settings: colorSettings,
            show_menu_for_admins: showMenuForAdmins,
            preserve_settings: preserveSettings
        };
        
        // Show loading message
        showStatusMessage('Saving menu settings...', 'info');
        
        // Send AJAX request
        $.post(came_data.ajax_url, data, function(response) {
            if (response.success) {
                showStatusMessage(response.data.message, 'success');
            } else {
                showStatusMessage(response.data.message, 'error');
            }
        }).fail(function() {
            showStatusMessage('Error saving menu settings. Please try again.', 'error');
        });
    }
    
    /**
     * Reset menu settings
     */
    function resetMenuSettings() {
        // Prepare data
        var data = {
            action: 'came_reset_menu',
            nonce: came_data.nonce
        };
        
        // Show loading message
        showStatusMessage('Resetting menu settings...', 'info');
        
        // Send AJAX request
        $.post(came_data.ajax_url, data, function(response) {
            if (response.success) {
                // Reload page
                window.location.reload();
            } else {
                showStatusMessage(response.data.message, 'error');
            }
        }).fail(function() {
            showStatusMessage('Error resetting menu settings. Please try again.', 'error');
        });
    }
    
    /**
     * Show status message
     */
    function showStatusMessage(message, type) {
        var $statusMessage = $('.came-status-message');
        
        // Set message and class
        $statusMessage.text(message).removeClass('success error info').addClass(type);
        
        // Show message
        $statusMessage.fadeIn(200);
        
        // Auto hide after 5 seconds for success messages
        if (type === 'success') {
            setTimeout(function() {
                $statusMessage.fadeOut(200);
            }, 5000);
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
    
    /**
     * Apply color settings
     */
    function applyColorSettings() {
        // Create CSS
        var css = 
            '.came-admin-custom-colors #adminmenu, .came-admin-custom-colors #adminmenuback, .came-admin-custom-colors #adminmenuwrap {' +
            '  background-color: ' + colorSettings.menu_bg_color + ';' +
            '}' +
            '.came-admin-custom-colors #adminmenu a, .came-admin-custom-colors #adminmenu div.wp-menu-image:before {' +
            '  color: ' + colorSettings.menu_text_color + ';' +
            '}' +
            '.came-admin-custom-colors #adminmenu li.menu-top:hover, .came-admin-custom-colors #adminmenu li.opensub > a.menu-top, .came-admin-custom-colors #adminmenu li > a.menu-top:focus {' +
            '  background-color: ' + colorSettings.hover_bg_color + ';' +
            '  color: ' + colorSettings.hover_text_color + ';' +
            '}' +
            '.came-admin-custom-colors #adminmenu li.menu-top:hover div.wp-menu-image:before, .came-admin-custom-colors #adminmenu li.opensub > a.menu-top div.wp-menu-image:before {' +
            '  color: ' + colorSettings.hover_text_color + ';' +
            '}';
            
        // Add or update style element
        var $style = $('#came-custom-colors-css');
        
        if ($style.length === 0) {
            $('<style id="came-custom-colors-css">' + css + '</style>').appendTo('head');
        } else {
            $style.text(css);
        }
        
        // Add body class
        $('body').addClass('came-admin-custom-colors');
    }