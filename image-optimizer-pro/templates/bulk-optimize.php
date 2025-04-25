<div class="wrap">
    <h1><?php _e('Bulk Image Optimizer', 'image-optimizer-pro'); ?></h1>
    
    <div class="iop-bulk-optimize-container">
        <div class="iop-stats-summary">
            <h2><?php _e('Optimization Summary', 'image-optimizer-pro'); ?></h2>
            <div id="iop-stats-container">
                <?php Image_Optimizer_Pro\Optimizer_Stats::display_stats_summary(); ?>
            </div>
        </div>
        
        <div class="iop-bulk-actions">
            <h2><?php _e('Bulk Actions', 'image-optimizer-pro'); ?></h2>
            
            <div class="iop-bulk-action-card">
                <h3><?php _e('Optimize All Images', 'image-optimizer-pro'); ?></h3>
                <p><?php _e('Optimize all images that haven\'t been optimized yet.', 'image-optimizer-pro'); ?></p>
                <button id="iop-start-bulk" class="button button-primary">
                    <?php _e('Start Bulk Optimization', 'image-optimizer-pro'); ?>
                </button>
                <div id="iop-bulk-progress" style="display: none;">
                    <div class="iop-progress-bar">
                        <div class="iop-progress-fill"></div>
                    </div>
                    <p class="iop-progress-text"></p>
                </div>
            </div>
            
            <div class="iop-bulk-action-card">
                <h3><?php _e('Restore All Backups', 'image-optimizer-pro'); ?></h3>
                <p><?php _e('Restore all images from their original backups.', 'image-optimizer-pro'); ?></p>
                <button id="iop-restore-all" class="button button-secondary">
                    <?php _e('Restore All', 'image-optimizer-pro'); ?>
                </button>
            </div>
        </div>
    </div>
</div>