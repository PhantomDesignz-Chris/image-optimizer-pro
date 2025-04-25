jQuery(document).ready(function($) {
    // Bulk optimization handler
    $('#iop-start-bulk').on('click', function(e) {
        e.preventDefault();
        startBulkOptimization(0);
    });
    
    // Restore all handler
    $('#iop-restore-all').on('click', function(e) {
        e.preventDefault();
        if (confirm(iop_vars.confirm_restore)) {
            restoreAllImages();
        }
    });
    
    function startBulkOptimization(offset) {
        var $button = $('#iop-start-bulk');
        var $progress = $('#iop-bulk-progress');
        var $progressFill = $('.iop-progress-fill');
        var $progressText = $('.iop-progress-text');
        
        $button.prop('disabled', true);
        $progress.show();
        
        $.ajax({
            url: iop_vars.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'iop_bulk_optimize',
                nonce: iop_vars.nonce,
                offset: offset
            },
            success: function(response) {
                if (response.data.complete) {
                    $progressText.text(response.data.message);
                    $button.prop('disabled', false);
                    updateStatsSummary();
                    return;
                }
                
                // Update progress
                var percent = Math.round((offset + response.data.processed) / response.data.total * 100);
                $progressFill.css('width', percent + '%');
                $progressText.text(
                    iop_vars.optimizing + ' ' + 
                    (offset + response.data.processed) + '/' + response.data.total + ' - ' + 
                    response.data.total_savings + ' ' + iop_vars.saved
                );
                
                // Process next batch
                startBulkOptimization(response.data.next_offset);
            },
            error: function() {
                $progressText.text(iop_vars.error);
                $button.prop('disabled', false);
            }
        });
    }
    
    function restoreAllImages() {
        // Similar implementation for restoring all images
    }
    
    function updateStatsSummary() {
        $.get({
            url: iop_vars.ajax_url,
            data: {
                action: 'iop_get_stats',
                nonce: iop_vars.nonce
            },
            success: function(response) {
                $('#iop-stats-container').text(response.data.html);
            }
        });
    }
    
    // Other JS functions for the plugin
});
