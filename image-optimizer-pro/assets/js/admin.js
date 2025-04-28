jQuery(document).ready(function($) {
    // Initialize bulk optimization
    let isOptimizing = false;
    let currentBatch = 0;
    const batchSize = 5;
    
    $('#iop-start-bulk').on('click', function() {
        if (isOptimizing) return;
        
        isOptimizing = true;
        currentBatch = 0;
        $('#iop-bulk-progress').show();
        $(this).prop('disabled', true);
        
        processBatch();
    });
    
    function processBatch() {
        $.ajax({
            url: iop_vars.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'iop_process_batch',
                nonce: iop_vars.nonce,
                batch: currentBatch,
                batch_size: batchSize
            },
            success: function(response) {
                if (response.success) {
                    const percent = Math.round((response.data.processed / response.data.total) * 100);
                    $('.iop-progress-fill').css('width', percent + '%');
                    $('.iop-progress-text').html(
                        response.data.processed + '/' + response.data.total + ' ' + 
                        iop_vars.images_processed + ' (' + percent + '%)'
                    );
                    
                    if (response.data.complete) {
                        isOptimizing = false;
                        $('#iop-start-bulk').prop('disabled', false);
                        location.reload(); // Refresh to show updated stats
                    } else {
                        currentBatch++;
                        setTimeout(processBatch, 500);
                    }
                } else {
                    showError(response.data);
                }
            },
            error: function(xhr) {
                showError(xhr.responseJSON?.data || iop_vars.error);
            }
        });
    }
    
    function showError(message) {
        isOptimizing = false;
        $('#iop-start-bulk').prop('disabled', false);
        alert(typeof message === 'string' ? message : JSON.stringify(message));
    }
});
