<?php
/**
 * Plugin Name: Image Optimizer Pro
 * Description: Optimize and compress images in your WordPress media library.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPLv2 or later
 * Text Domain: image-optimizer-pro
 */

defined('ABSPATH') || exit;

// Define plugin constants
define('IOP_VERSION', '1.0.0');
define('IOP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IOP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('IOP_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('IOP_LOG_DIR', WP_CONTENT_DIR . '/iop-logs/');
define('IOP_BACKUP_DIR', WP_CONTENT_DIR . '/iop-backups/');

// Check for required PHP version
if (version_compare(PHP_VERSION, '7.0', '<')) {
    add_action('admin_notices', 'iop_php_version_notice');
    return;
}

function iop_php_version_notice() {
    echo '<div class="error"><p>';
    printf(
        __('Image Optimizer Pro requires PHP 7.0 or higher. Your server is running PHP %s. Please upgrade PHP.', 'image-optimizer-pro'),
        PHP_VERSION
    );
    echo '</p></div>';
}

// Autoload classes
require_once IOP_PLUGIN_DIR . 'includes/autoloader.php';

// Initialize the plugin
function iop_init() {
    // Check if Imagick or GD is installed
    if (!extension_loaded('imagick') && !extension_loaded('gd')) {
        add_action('admin_notices', 'iop_image_lib_notice');
        return;
    }
    
    // Create necessary directories
    if (!file_exists(IOP_LOG_DIR)) {
        wp_mkdir_p(IOP_LOG_DIR);
    }
    if (!file_exists(IOP_BACKUP_DIR)) {
        wp_mkdir_p(IOP_BACKUP_DIR);
    }
    
    // Load plugin classes
    $image_optimizer = new Image_Optimizer_Pro\Image_Optimizer();
    $admin_interface = new Image_Optimizer_Pro\Optimizer_Admin();
    $ajax_handlers = new Image_Optimizer_Pro\Optimizer_Ajax();
    $cron_jobs = new Image_Optimizer_Pro\Optimizer_Cron();
    
    // Register activation/deactivation hooks
    register_activation_hook(__FILE__, ['Image_Optimizer_Pro\Optimizer_Admin', 'activate']);
    register_deactivation_hook(__FILE__, ['Image_Optimizer_Pro\Optimizer_Admin', 'deactivate']);
}
add_action('plugins_loaded', 'iop_init');

function iop_image_lib_notice() {
    echo '<div class="error"><p>';
    _e('Image Optimizer Pro requires either the Imagick or GD PHP extension to be installed. Please contact your hosting provider to install one of these extensions.', 'image-optimizer-pro');
    echo '</p></div>';
}