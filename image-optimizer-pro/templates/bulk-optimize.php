<div class="wrap">
    <h1><?php _e('Bulk Image Optimizer', 'image-optimizer-pro'); ?></h1>
    
    <?php if (!empty($_GET['bulk_optimized'])) : ?>
        <div class="notice notice-success">
            <p><?php printf(__('%d images were optimized.', 'image-optimizer-pro'), intval($_GET['bulk_optimized'])); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="iop-bulk-container">
        <!-- Stats Summary Card -->
        <div class="iop-card">
            <h2><?php _e('Optimization Summary', 'image-optimizer-pro'); ?></h2>
            <div id="iop-stats-container">
                <?php
                $stats_handler = new Image_Optimizer_Pro\Optimizer_Stats();
                $total_stats = $stats_handler->get_total_stats();
                ?>
                <div class="iop-stats-grid">
                    <div class="iop-stat-item">
                        <div class="iop-stat-value"><?php echo number_format($total_stats['total_images']); ?></div>
                        <div class="iop-stat-label"><?php _e('Total Images', 'image-optimizer-pro'); ?></div>
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
        </div>

        <!-- Bulk Actions Card -->
        <div class="iop-card">
            <h2><?php _e('Bulk Actions', 'image-optimizer-pro'); ?></h2>
            <div class="iop-bulk-actions">
                <div class="iop-action-card">
                    <h3><?php _e('Optimize All Images', 'image-optimizer-pro'); ?></h3>
                    <p><?php _e('Process all unoptimized images in your media library.', 'image-optimizer-pro'); ?></p>
                    <button id="iop-start-bulk" class="button button-primary">
                        <?php _e('Start Bulk Optimization', 'image-optimizer-pro'); ?>
                    </button>
                    <div id="iop-bulk-progress" style="display:none; margin-top:20px;">
                        <div class="iop-progress-bar">
                            <div class="iop-progress-fill"></div>
                        </div>
                        <div class="iop-progress-text"></div>
                        <div class="iop-progress-details"></div>
                    </div>
                </div>
                
                <div class="iop-action-card">
                    <h3><?php _e('Restore All Backups', 'image-optimizer-pro'); ?></h3>
                    <p><?php _e('Restore all optimized images to their original versions.', 'image-optimizer-pro'); ?></p>
                    <button id="iop-restore-all" class="button">
                        <?php _e('Restore All', 'image-optimizer-pro'); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Optimization Log -->
        <div class="iop-card">
            <h2><?php _e('Recent Optimizations', 'image-optimizer-pro'); ?></h2>
            <?php
            $recent_optimizations = $stats_handler->get_recent_optimizations(10);
            if (!empty($recent_optimizations)) :
            ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Image', 'image-optimizer-pro'); ?></th>
                            <th><?php _e('Original Size', 'image-optimizer-pro'); ?></th>
                            <th><?php _e('Optimized Size', 'image-optimizer-pro'); ?></th>
                            <th><?php _e('Savings', 'image-optimizer-pro'); ?></th>
                            <th><?php _e('Date', 'image-optimizer-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_optimizations as $optimization) : ?>
                            <tr>
                                <td><?php echo esc_html($optimization->filename); ?></td>
                                <td><?php echo size_format($optimization->original_size, 2); ?></td>
                                <td><?php echo size_format($optimization->optimized_size, 2); ?></td>
                                <td>
                                    <?php echo size_format($optimization->original_size - $optimization->optimized_size, 2); ?>
                                    (<?php echo number_format($optimization->savings_percent, 2); ?>%)
                                </td>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($optimization->optimized_date)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php _e('No optimizations have been performed yet.', 'image-optimizer-pro'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
