/**
 * Enhanced Admin Menu Editor JavaScript
 * Fixed version for submenu handling
 */

(function($) {
    'use strict';

    // Store menu data
    var menuData = {
        items: [],
        currentItemId: null,
        currentItemType: null // 'menu' or 'submenu'
    };

    // Initialize plugin
    $(document).ready(function() {
        // Initialize tabs
        initTabs();
        
        // Initialize event handlers
        initEventHandlers();
        
        // Load menu data if available
        if (typeof came_data !== 'undefined') {
            // Check for saved menu first
            if (came_data.saved_menu && came_data.saved_menu.length > 0) {
                menuData.items = came_data.saved_menu;
                console.log('Loaded saved menu data');
            } 
            // Fall back to current admin menu structure
            else if (came_data.admin_menu) {
                menuData.items = came_data.admin_menu;
                console.log('Loaded default menu structure');
            }
            
            // Render the menu
            renderMenuTree();
        } else {
            console.error('came_data is not defined. Script data not properly localized.');
        }
    });

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
            if (confirm(came_data.text_confirm_reset || 'Are you sure you want to reset the menu to default?')) {
                resetMenuSettings();
            }
        });
        
        // Apply property changes button
        $('#came-apply-changes').on('click', function() {
            applyItemChanges();
        });
        
        // Select menu item
        $(document).on('click', '.came-menu-item > .came-item-header', function(e) {
            if (!$(e.target).hasClass('came-item-handle') && !$(e.target).hasClass('came-item-toggle')) {
                selectMenuItem($(this).closest('.came-menu-item'));
            }
        });
        
        // Select submenu item
        $(document).on('click', '.came-submenu-item', function(e) {
            if (!$(e.target).hasClass('came-item-handle')) {
                selectSubmenuItem($(this));
            }
        });
        
        // Toggle submenu visibility
        $(document).on('click', '.came-item-toggle', function(e) {
            e.stopPropagation();
            var $menuItem = $(this).closest('.came-menu-item');
            toggleSubmenu($menuItem);
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
                connectWith: '.came-submenu-list',
                update: function(event, ui) {
                    // Only process if this is the receiving container
                    if (this === ui.item.parent()[0]) {
                        var parentId = $(this).closest('.came-menu-item').data('item-id');
                        updateSubmenuOrder(parentId);
                    }
                }
            });
        } else {
            console.warn('jQuery UI Sortable not available. Drag and drop functionality disabled.');
        }
    }

    /**
     * Render menu tree
     */
    function renderMenuTree() {
        var $menuTree = $('#came-menu-tree');
        $menuTree.empty();
        
        if (menuData.items.length === 0) {
            $menuTree.html('<div class="notice notice-info" style="margin: 20px 0;"><p>No menu items available or menu data could not be loaded.</p></div>');
            return;
        }
        
        // Render each menu item
        $.each(menuData.items, function(index, item) {
            renderMenuItem($menuTree, item);
        });
        
        // Initialize sortable for new items
        initSortable();
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
        
        // Render submenu items if they exist
        if (item.submenu && item.submenu.length > 0) {
            var $submenuList = $menuItem.find('.came-submenu-list');
            
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
    }

    /**
     * Apply item changes
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
        
        // Show success message
        showStatusMessage('Item updated. Don\'t forget to save your changes.', 'success');
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
        
        // Prepare data
        var data = {
            action: 'came_save_menu',
            nonce: came_data.nonce,
            menu_data: JSON.stringify(menuData.items),
            allowed_roles: allowedRoles
        };
        
        // Show loading message
        showStatusMessage('Saving menu settings...', 'info');
        
        // Log AJAX request for debugging
        console.log('Sending AJAX save request with data:', data);
        
        // Send AJAX request
        $.post(came_data.ajax_url, data, function(response) {
            console.log('AJAX response:', response);
            
            if (response.success) {
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
        // Prepare data
        var data = {
            action: 'came_reset_menu',
            nonce: came_data.nonce
        };
        
        // Show loading message
        showStatusMessage('Resetting menu settings...', 'info');
        
        // Send AJAX request
        $.post(came_data.ajax_url, data, function(response) {
            console.log('Reset response:', response);
            
            if (response.success) {
                // Reload page
                window.location.reload();
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
    
})(jQuery);