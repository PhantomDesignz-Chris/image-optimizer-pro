jQuery(document).ready(function($) {
    // Single image optimization
    $('.image-optimizer-pro-optimize').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        var attachment_id = button.data('attachment-id');

        button.prop('disabled', true).text('Optimizing...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'image_optimizer_pro_optimize',
                attachment_id: attachment_id,
                nonce: image_optimizer_pro_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Image optimized successfully! Savings: ' + formatBytes(response.data.savings));
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function(xhr) {
                alert('Request failed: ' + xhr.statusText);
            },
            complete: function() {
                button.prop('disabled', false).text('Optimize');
            }
        });
    });

    // ====== FIX ADDED STARTS ======
    // Bulk actions handler
    $(document).on('click', '.image-optimizer-pro-bulk-action', function(e) {
        e.preventDefault();
        var action = $(this).data('action');
        var button = $(this);

        button.prop('disabled', true).text('Processing...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'image_optimizer_pro_' + action,
                nonce: image_optimizer_pro_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(action === 'restore_all' ? 'All images restored successfully!' : 'Bulk optimization completed!');
                    location.reload();
                } else {
                    alert('Error: ' + (response.data || 'Unknown error'));
                }
            },
            error: function(xhr) {
                alert('Request failed: ' + xhr.statusText);
            },
            complete: function() {
                button.prop('disabled', false).text(action === 'optimize_all' ? 'Optimize All' : 'Restore All');
            }
        });
    });
    // ====== FIX ADDED ENDS ======

    function formatBytes(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
});
