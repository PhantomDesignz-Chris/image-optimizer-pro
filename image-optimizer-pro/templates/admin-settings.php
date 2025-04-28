<?php 
if (!defined('ABSPATH')) exit;

// Debug: Check if classes are loaded
if (!class_exists('Image_Optimizer_Pro\Optimizer_Stats')) {
    echo '<div class="error"><p>';
    echo __('Error: Required classes not loaded. Please check error logs.', 'image-optimizer-pro');
    echo '</p></div>';
    return;
}

$stats_handler = new Image_Optimizer_Pro\Optimizer_Stats();
$total_stats = $stats_handler->get_total_stats();
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors(); ?>
    
    <div class="iop-settings-container">
        <div class="iop-settings-main">
            <form method="post" action="options.php">
                <?php
                settings_fields('iop_settings_group');
                do_settings_sections('image-optimizer-pro');
                submit_button();
                ?>
            </form>
        </div>
        
        <div class="iop-settings-sidebar">
            <?php Image_Optimizer_Pro\Optimizer_Stats::display_stats_summary(); ?>
            <?php Image_Optimizer_Pro\Optimizer_Tools::display_server_info(); ?>
            
            <div class="iop-export-section">
                <h3><?php _e('Export Statistics', 'image-optimizer-pro'); ?></h3>
                <a href="<?php echo wp_nonce_url(admin_url('options-general.php?page=image-optimizer-pro&iop_export_stats=1'), 'iop_export_stats'); ?>" class="button">
                    <?php _e('Export as JSON', 'image-optimizer-pro'); ?>
                </a>
            </div>
        </div>
    </div>
</div>
