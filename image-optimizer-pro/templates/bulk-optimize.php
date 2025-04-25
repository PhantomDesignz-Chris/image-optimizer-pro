<div class="wrap">
    <h1><?php _e('Bulk Image Optimizer', 'image-optimizer-pro'); ?></h1>
    
    <div class="iop-bulk-container">
        <!-- Stats Summary -->
        <div class="iop-stats-card">
            <h2><?php _e('Optimization Summary', 'image-optimizer-pro'); ?></h2>
            <div class="iop-stats-grid">
                <div class="iop-stat-item">
                    <div class="iop-stat-value"><?php echo number_format($total_stats['total_images']); ?></div>
                    <div class="iop-stat-label"><?php _e('Images Processed', 'image-optimizer-pro'); ?></div>
                </div>
                <div class="iop-stat-item">
                    <div class="iop-stat-value"><?php echo size_format($total_stats['total_savings'], 2); ?></div>
                    <div class="iop-stat-label"><?php _e('Total Savings', 'image-optimizer-pro'); ?></div>
                </div>
                <div class="iop-stat-item">
                    <div class="iop-stat-value"><?php echo number_format($total_stats['savings_percent'], 2); ?>%</div>
                    <div class="iop-stat-label"><?php _e('Average Reduction', 'image-optimizer-pro'); ?></div>
                </div>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div class="iop-bulk-actions">
            <div class="iop-bulk-card">
                <h3><?php _e('Optimize All Images', 'image-optimizer-pro'); ?></h3>
                <p><?php _e('Process all unoptimized images in your media library.', 'image-optimizer-pro'); ?></p>
                
                <div id="iop-bulk-optimize-controls">
                    <button id="iop-start-bulk" class="button button-primary">
                        <?php _e('Start Bulk Optimization', 'image-optimizer-pro'); ?>
                    </button>
                    <button id="iop-pause-bulk" class="button" style="display:none;">
                        <?php _e('Pause', 'image-optimizer-pro'); ?>
                    </button>
                    
                    <div id="iop-bulk-progress" style="display:none; margin-top:20px;">
                        <div class="iop-progress-bar">
                            <div class="iop-progress-fill"></div>
                        </div>
                        <div class="iop-progress-text"></div>
                        <div class="iop-progress-details"></div>
                    </div>
                </div>
            </div>

            <div class="iop-bulk-card">
                <h3><?php _e('Image Status Report', 'image-optimizer-pro'); ?></h3>
                <div id="iop-image-report">
                    <div class="iop-report-row">
                        <span class="iop-report-label"><?php _e('Total Images:', 'image-optimizer-pro'); ?></span>
                        <span class="iop-report-value" id="iop-total-images">0</span>
                    </div>
                    <div class="iop-report-row">
                        <span class="iop-report-label"><?php _e('Optimized:', 'image-optimizer-pro'); ?></span>
                        <span class="iop-report-value" id="iop-optimized-count">0</span>
                    </div>
                    <div class="iop-report-row">
                        <span class="iop-report-label"><?php _e('Unoptimized:', 'image-optimizer-pro'); ?></span>
                        <span class="iop-report-value" id="iop-unoptimized-count">0</span>
                    </div>
                    <div class="iop-report-row">
                        <span class="iop-report-label"><?php _e('With Backups:', 'image-optimizer-pro'); ?></span>
                        <span class="iop-report-value" id="iop-backup-count">0</span>
                    </div>
                </div>
                <button id="iop-refresh-stats" class="button">
                    <?php _e('Refresh Report', 'image-optimizer-pro'); ?>
                </button>
            </div>
        </div>

        <!-- Optimization Log -->
        <div class="iop-optimization-log">
            <h3><?php _e('Recent Optimizations', 'image-optimizer-pro'); ?></h3>
            <div class="iop-log-container">
                <?php require_once IOP_PLUGIN_DIR . 'templates/stats-table.php'; ?>
            </div>
        </div>
    </div>
</div>
