<?php
spl_autoload_register(function ($class_name) {
    $namespace = 'Image_Optimizer_Pro\\';
    
    // Only autoload our plugin's classes
    if (strpos($class_name, $namespace) !== 0) {
        return;
    }
    
    // Remove namespace prefix
    $class_name = str_replace($namespace, '', $class_name);
    
    // Convert to file path
    $class_file = str_replace('_', '-', strtolower($class_name)) . '.php';
    
    // Possible file paths
    $locations = [
        IOP_PLUGIN_DIR . 'includes/',
        IOP_PLUGIN_DIR . 'includes/classes/'
    ];
    
    // Try each location
    foreach ($locations as $location) {
        $file = $location . $class_file;
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
    
    // Log if class not found (but don't break the site)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("Image Optimizer Pro: Class {$class_name} not found in {$class_file}");
    }
});
