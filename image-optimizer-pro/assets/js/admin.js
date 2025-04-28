jQuery(document).ready(function($) {
    const iop = {
        init() {
            this.bindEvents();
            this.refreshStats();
        },

        bindEvents() {
            $('#iop-start-bulk').on('click', (e) => this.startOptimization(e));
            $('#iop-refresh-stats').on('click', (e) => this.refreshStats(e));
        },

        startOptimization(e) {
            e.preventDefault();
            let currentBatch = 0;
            const batchSize = 5;
            
            const processBatch = () => {
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
                    success: (response) => {
                        if (response.success) {
                            this.updateProgress(response.data);
                            if (!response.data.complete) {
                                currentBatch++;
                                setTimeout(processBatch, 500);
                            } else {
                                this.completeOptimization();
                            }
                        } else {
                            this.showError(response.data);
                        }
                    },
                    error: (xhr) => {
                        this.showError(xhr.responseJSON?.data || iop_vars.error);
                    }
                });
            };

            $('#iop-bulk-progress').show();
            $('#iop-start-bulk').prop('disabled', true);
            processBatch();
        },

        refreshStats(e) {
            if (e) e.preventDefault();
            $.ajax({
                url: iop_vars.ajax_url,
                type: 'GET',
                dataType: 'json',
                data: {
                    action: 'iop_get_image_stats',
                    nonce: iop_vars.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateStatsDisplay(response.data);
                    } else {
                        this.showError(response.data);
                    }
                },
                error: (xhr) => {
                    this.showError(xhr.responseJSON?.data || iop_vars.error);
                }
            });
        },

        updateProgress(data) {
            const percent = Math.round((data.processed / data.total) * 100);
            $('.iop-progress-fill').css('width', percent + '%');
            $('.iop-progress-text').html(`
                ${data.processed}/${data.total} ${iop_vars.images_processed} (${percent}%)<br>
                ${iop_vars.saved}: ${data.savings}
            `);
        },

        completeOptimization() {
            $('#iop-start-bulk').prop('disabled', false);
            setTimeout(() => location.reload(), 2000);
        },

        updateStatsDisplay(data) {
            $('#iop-total-images').text(data.total);
            $('#iop-optimized-count').text(data.optimized);
            $('#iop-unoptimized-count').text(data.unoptimized);
            $('#iop-backup-count').text(data.backups);
        },

showError(message) {
    $('#iop-bulk-progress').hide();
    $('#iop-start-bulk').prop('disabled', false);
    
    // Enhanced error display
    const errorMessage = `
        <div class="notice notice-error">
            <p>${iop_vars.error}: ${message}</p>
            <p>${iop_vars.error_advice}</p>
        </div>
    `;
    
    $('#iop-bulk-optimize-controls').prepend(errorMessage);
    
    setTimeout(() => {
        $('.notice-error').fadeOut(1000);
    }, 5000);
}
    };

    iop.init();
});
