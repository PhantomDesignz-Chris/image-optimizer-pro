<?php
/**
 * Main plugin class
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Image_Optimizer {
    private static $instance;
    public $optimize;
    public $restore;
    public $admin;
    public $settings;

    public static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->includes();
        $this->init_components();
        $this->setup_hooks();
    }

    private function includes() {
        require_once plugin_dir_path(__FILE__) . 'includes/class-image-optimizer-pro-optimize.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-image-optimizer-pro-restore.php';
        require_once plugin_dir_path(__FILE__) . 'admin/class-image-optimizer-pro-admin.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-image-optimizer-pro-settings.php';
    }

    private function init_components() {
        $quality = get_option('image_optimizer_pro_quality', 80);
        $preserve_metadata = get_option('image_optimizer_pro_preserve_metadata', false);

        $this->optimize = new Image_Optimizer_Pro_Optimize($quality, $preserve_metadata);
        $this->restore = new Image_Optimizer_Pro_Restore();
        $this->admin = new Image_Optimizer_Pro_Admin();
        $this->settings = new Image_Optimizer_Pro_Settings();
    }

    private function setup_hooks() {
        add_action('admin_init', array($this, 'check_image_library'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function check_image_library() {
        if (!extension_loaded('gd') && !extension_loaded('imagick')) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>';
                _e('Image Optimizer Pro requires either GD or Imagick PHP extension to function.', 'image-optimizer-pro');
                echo '</p></div>';
            });
        }
    }

    public function activate() {
        // Create necessary directories
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'] . '/image-optimizer-pro-backups/';
        
        if (!file_exists($backup_dir)) {
            wp_mkdir_p($backup_dir);
        }

        // Set default options if not exists
        add_option('image_optimizer_pro_quality', 80);
        add_option('image_optimizer_pro_preserve_metadata', false);
    }

    public function deactivate() {
        // Cleanup options if needed
        // delete_option('image_optimizer_pro_quality');
        // delete_option('image_optimizer_pro_preserve_metadata');
    }
}

// Initialize the plugin
function image_optimizer_pro_init() {
    return Image_Optimizer::get_instance();
}
add_action('plugins_loaded', 'image_optimizer_pro_init');

// Global helper function
function image_optimizer_pro() {
    return Image_Optimizer::get_instance();
}
