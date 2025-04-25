jQuery(document).ready(function($) {
    // Initialize bulk optimization
    let isOptimizing = false;
    let currentBatch = 0;
    const batchSize = 5;
    
    // Refresh image stats
    function refreshStats() {
        $.get(ajaxurl, {
            action: 'iop_get_image_stats',
            nonce: iop_vars.nonce
        }, function(response) {
            if (response.success) {
                $('#iop-total-images').text(response.data.total);
                $('#iop-optimized-count').text(response.data.optimized);
                $('#iop-unoptimized-count').text(response.data.unoptimized);
                $('#iop-backup-count').text(response.data.backups);
            }
        });
    }
    
    // Start bulk optimization
    $('#iop-start-bulk').on('click', function() {
        if (isOptimizing) return;
        
        isOptimizing = true;
        currentBatch = 0;
        $('#iop-bulk-progress').show();
        $('#iop-pause-bulk').show();
        $(this).prop('disabled', true);
        
        processBatch();
    });
    
    // Process a batch of images
    function processBatch() {
        if (!isOptimizing) return;
        
        $.post(ajaxurl, {
            action: 'iop_process_batch',
            nonce: iop_vars.nonce,
            batch: currentBatch,
            batch_size: batchSize
        }, function(response) {
            if (response.success) {
                // Update progress
                const percent = Math.round((response.data.processed / response.data.total) * 100);
                $('.iop-progress-fill').css('width', percent + '%');
                $('.iop-progress-text').text(
                    response.data.processed + '/' + response.data.total + ' ' + 
                    iop_vars.images_processed + ' (' + percent + '%)'
                );
                $('.iop-progress-details').text(
                    iop_vars.saved + ': ' + response.data.savings + '<br>' +
                    iop_vars.current_image + ': ' + response.data.current_file
                );
                
                // Refresh stats
                refreshStats();
                
                // Process next batch or complete
                if (response.data.complete) {
                    optimizationComplete();
                } else {
                    currentBatch++;
                    setTimeout(processBatch, 500); // Brief pause between batches
                }
            } else {
                alert(iop_vars.error + ': ' + response.data.message);
                optimizationComplete();
            }
        }).fail(function() {
            alert(iop_vars.error);
            optimizationComplete();
        });
    }
    
    // Complete optimization
    function optimizationComplete() {
        isOptimizing = false;
        $('#iop-start-bulk').prop('disabled', false);
        $('#iop-pause-bulk').hide();
        refreshStats();
    }
    
    // Pause optimization
    $('#iop-pause-bulk').on('click', function() {
        isOptimizing = false;
        $(this).hide();
        $('#iop-start-bulk').show().prop('disabled', false);
    });
    
    // Refresh stats on page load
    refreshStats();
    $('#iop-refresh-stats').on('click', refreshStats);
});
