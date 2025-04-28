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
                handleSuccess(response.data);
            } else {
                showError(response.data || 'Unknown error occurred');
            }
        },
        error: function(xhr) {
            let errorMsg = 'Request failed: ';
            if (xhr.responseJSON && xhr.responseJSON.data) {
                errorMsg += xhr.responseJSON.data;
            } else {
                errorMsg += xhr.statusText;
            }
            showError(errorMsg);
        }
    });
}

function handleSuccess(data) {
    const percent = Math.round((data.processed / data.total) * 100);
    $('.iop-progress-fill').css('width', percent + '%');
    $('.iop-progress-text').html(
        `${data.processed}/${data.total} ${iop_vars.images_processed} (${percent}%)`
    );
    
    if (data.complete) {
        isOptimizing = false;
        $('#iop-start-bulk').prop('disabled', false);
        setTimeout(() => location.reload(), 2000); // Refresh after 2 seconds
    } else {
        currentBatch++;
        setTimeout(processBatch, 500);
    }
}

function showError(message) {
    isOptimizing = false;
    $('#iop-start-bulk').prop('disabled', false);
    alert(`${iop_vars.error}: ${message}`);
}
});
