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
    // First require the autoloader
    require_once IOP_PLUGIN_DIR . 'includes/autoloader.php';
    
    try {
        // Verify class exists before using it
        if (!class_exists('Image_Optimizer_Pro\Image_Optimizer')) {
            throw new Exception('Image_Optimizer class could not be loaded');
        }

        // Rest of your initialization code...
        if (!extension_loaded('imagick') && !extension_loaded('gd')) {
            add_action('admin_notices', 'iop_image_lib_notice');
            return;
        }
        
        wp_mkdir_p(IOP_LOG_DIR);
        wp_mkdir_p(IOP_BACKUP_DIR);
        
        new Image_Optimizer_Pro\Image_Optimizer();
        new Image_Optimizer_Pro\Optimizer_Admin();
        new Image_Optimizer_Pro\Optimizer_Ajax();
        new Image_Optimizer_Pro\Optimizer_Cron();
        
        register_activation_hook(__FILE__, ['Image_Optimizer_Pro\Optimizer_Admin', 'activate']);
        register_deactivation_hook(__FILE__, ['Image_Optimizer_Pro\Optimizer_Admin', 'deactivate']);
        
    } catch (Exception $e) {
        add_action('admin_notices', function() use ($e) {
            echo '<div class="error"><p>Image Optimizer Pro Error: ' 
                . esc_html($e->getMessage()) . '</p></div>';
        });
    }
}
add_action('plugins_loaded', 'iop_init');

function iop_image_lib_notice() {
    echo '<div class="error"><p>';
    _e('Image Optimizer Pro requires either the Imagick or GD PHP extension to be installed. Please contact your hosting provider to install one of these extensions.', 'image-optimizer-pro');
    echo '</p></div>';
}
