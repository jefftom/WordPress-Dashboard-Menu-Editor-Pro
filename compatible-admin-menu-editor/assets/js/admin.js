/**
 * Advanced Admin Menu Editor JavaScript
 * Fix for saving changes
 */

// Fix for the save menu function
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
    
    // Make a deep copy of the menuData.items to avoid reference issues
    var menuItemsCopy = JSON.parse(JSON.stringify(menuData.items));
    
    // Prepare data
    var data = {
        action: 'came_save_menu',
        nonce: came_data.nonce,
        menu_data: JSON.stringify(menuItemsCopy),
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
            console.error('Error details:', response.data);
        }
    }).fail(function(xhr, status, error) {
        console.error('AJAX error:', status, error);
        showStatusMessage('Error saving menu settings. Please check browser console for details.', 'error');
    });
}