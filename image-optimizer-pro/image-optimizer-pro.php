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

// MANUAL CLASS LOADER - PUT AT TOP OF FILE
$manual_load = [
    'class-image-optimizer.php',
    'class-optimizer-admin.php',
    'class-optimizer-ajax.php',
    'class-optimizer-cron.php'
];

foreach ($manual_load as $file) {
    $path = IOP_PLUGIN_DIR . 'includes/' . $file;
    if (file_exists($path)) {
        require_once $path;
    } else {
        wp_die("Missing required file: {$file}");
    }
}

// Debugging setup
if (!function_exists('iop_debug_log')) {
    function iop_debug_log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Image Optimizer Pro] ' . print_r($message, true));
        }
    }
}

// Check PHP version
if (version_compare(PHP_VERSION, '7.0.0', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>';
        printf(
            __('Image Optimizer Pro requires PHP 7.0 or higher. Your server is running PHP %s. Please upgrade PHP.', 'image-optimizer-pro'),
            PHP_VERSION
        );
        echo '</p></div>';
    });
    return;
}

// Load the autoloader
$autoloader_path = IOP_PLUGIN_DIR . 'includes/autoloader.php';
if (!file_exists($autoloader_path)) {
    add_action('admin_notices', function() use ($autoloader_path) {
        echo '<div class="error"><p>';
        printf(
            __('Image Optimizer Pro could not find the autoloader at %s', 'image-optimizer-pro'),
            esc_html($autoloader_path)
        );
        echo '</p></div>';
    });
    return;
}

require_once $autoloader_path;

// TEMPORARY: Manual class loader - remove after fixing autoloader
function iop_manual_load_classes() {
    $classes = [
        'class-image-optimizer',
        'class-optimizer-admin',
        'class-optimizer-ajax',
        'class-optimizer-cron',
        'class-optimizer-stats',
        'class-optimizer-backup',
        'class-optimizer-tools'
    ];
    
    foreach ($classes as $class) {
        $file = IOP_PLUGIN_DIR . 'includes/' . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
        } else {
            error_log("[Image Optimizer] Missing file: {$file}");
        }
    }
}
iop_manual_load_classes();


// Initialize the plugin
function iop_init() {

        // Load textdomain
    add_action('init', function() {
        load_plugin_textdomain(
            'image-optimizer-pro',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    });
    
    try {
        // Debug class existence
        if (!class_exists('Image_Optimizer_Pro\Image_Optimizer')) {
            throw new RuntimeException(
                'Image_Optimizer class not found. Check file naming and namespace.'
            );
        }

        // Check for required image libraries
        $missing_extensions = [];
        if (!extension_loaded('imagick') && !extension_loaded('gd')) {
            $missing_extensions[] = 'Imagick or GD';
        }
        
        if (!function_exists('wp_get_image_editor')) {
            $missing_extensions[] = 'WP_Image_Editor';
        }

        if (!empty($missing_extensions)) {
            throw new RuntimeException(
                'Missing required extensions: ' . implode(', ', $missing_extensions)
            );
        }

        // Create necessary directories
        $directories = [
            IOP_LOG_DIR => __('Log directory', 'image-optimizer-pro'),
            IOP_BACKUP_DIR => __('Backup directory', 'image-optimizer-pro')
        ];

        foreach ($directories as $dir => $description) {
            if (!wp_mkdir_p($dir)) {
                throw new RuntimeException(
                    sprintf(__('Could not create %s: %s', 'image-optimizer-pro'), 
                    $description, 
                    $dir
                )
                );
            }
            
            if (!is_writable($dir)) {
                throw new RuntimeException(
                    sprintf(__('%s is not writable: %s', 'image-optimizer-pro'),
                    $description,
                    $dir
                )
                );
            }
        }

        // Initialize main components
        new Image_Optimizer_Pro\Image_Optimizer();
        new Image_Optimizer_Pro\Optimizer_Admin();
        new Image_Optimizer_Pro\Optimizer_Ajax();
        new Image_Optimizer_Pro\Optimizer_Cron();

    } catch (Exception $e) {
        add_action('admin_notices', function() use ($e) {
            echo '<div class="error"><p>';
            echo '<strong>' . __('Image Optimizer Pro Error', 'image-optimizer-pro') . ':</strong> ';
            echo esc_html($e->getMessage());
            echo '</p></div>';
            
            iop_debug_log($e->getMessage());
            iop_debug_log('Trace: ' . $e->getTraceAsString());
        });
        
        return;
    }
}

// Register activation/deactivation hooks
register_activation_hook(__FILE__, function() {
    include_once IOP_PLUGIN_DIR . 'includes/class-optimizer-admin.php';
    Image_Optimizer_Pro\Optimizer_Admin::activate();
});

register_deactivation_hook(__FILE__, function() {
    include_once IOP_PLUGIN_DIR . 'includes/class-optimizer-admin.php';
    Image_Optimizer_Pro\Optimizer_Admin::deactivate();
});

// TEST _ DELETE AFTER

add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    
    $missing = [];
    $classes = [
        'Optimizer_Stats',
        'Optimizer_Admin',
        'Image_Optimizer'
    ];
    
    foreach ($classes as $class) {
        if (!class_exists('Image_Optimizer_Pro\\' . $class)) {
            $missing[] = $class;
        }
    }
    
    if (!empty($missing)) {
        echo '<div class="notice notice-error"><pre>';
        echo "Missing Classes:\n";
        print_r($missing);
        echo "\n\nDebug Info:\n";
        echo "Plugin Path: " . IOP_PLUGIN_DIR . "\n";
        
        foreach ($missing as $class) {
            $file = 'class-' . strtolower(str_replace('_', '-', $class)) . '.php';
            $path = IOP_PLUGIN_DIR . 'includes/' . $file;
            echo "\nChecking: {$file}\n";
            echo "Exists: " . (file_exists($path) ? 'Yes' : 'NO') . "\n";
            if (file_exists($path)) {
                echo "First line: " . htmlspecialchars(fgets(fopen($path, 'r')));
            }
        }
        
        echo '</pre></div>';
    }
});

// Initialize the plugin
add_action('plugins_loaded', 'iop_init');
