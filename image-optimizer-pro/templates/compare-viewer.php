<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap">
    <h1><?php _e('Image Comparison Viewer', 'image-optimizer-pro'); ?></h1>
    
    <div class="iop-comparison-viewer">
        <div class="iop-comparison-container">
            <div class="iop-before-after-labels">
                <span class="iop-before-label"><?php _e('Original', 'image-optimizer-pro'); ?></span>
                <span class="iop-after-label"><?php _e('Optimized', 'image-optimizer-pro'); ?></span>
            </div>
            
            <?php echo wp_get_attachment_image($original_id, 'full', false, [
                'class' => 'iop-original-image',
                'data-iop-type' => 'original'
            ]); ?>
            
            <div class="iop-image-slider">
                <?php echo wp_get_attachment_image($optimized_id, 'full', false, [
                    'class' => 'iop-optimized-image',
                    'data-iop-type' => 'optimized'
                ]); ?>
                
                <div class="iop-slider-handle"></div>
            </div>
        </div>
        
        <div class="iop-comparison-stats">
            <h3><?php _e('Optimization Statistics', 'image-optimizer-pro'); ?></h3>
            <table class="widefat">
                <tr>
                    <th><?php _e('Original Size:', 'image-optimizer-pro'); ?></th>
                    <td><?php echo size_format($original_size, 2); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Optimized Size:', 'image-optimizer-pro'); ?></th>
                    <td><?php echo size_format($optimized_size, 2); ?></td>
                </tr>
                <tr>
                    <th><?php _e('Savings:', 'image-optimizer-pro'); ?></th>
                    <td>
                        <?php echo size_format($original_size - $optimized_size, 2); ?>
                        (<?php echo number_format((($original_size - $optimized_size) / $original_size * 100), 2); ?>%)
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Format:', 'image-optimizer-pro'); ?></th>
                    <td><?php echo strtoupper($format); ?></td>
                </tr>
            </table>
            
            <div class="iop-comparison-actions">
                <button class="button button-primary iop-restore-btn" 
                        data-attachment-id="<?php echo $attachment_id; ?>">
                    <?php _e('Restore Original', 'image-optimizer-pro'); ?>
                </button>
                <a href="<?php echo admin_url('upload.php?page=iop-bulk-optimize'); ?>" 
                   class="button">
                    <?php _e('Back to Optimizer', 'image-optimizer-pro'); ?>
                </a>
            </div>
        </div>
    </div>
</div>