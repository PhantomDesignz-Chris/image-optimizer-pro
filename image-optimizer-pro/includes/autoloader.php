<?php
spl_autoload_register(function ($class) {
    // Only handle our plugin's classes
    $prefix = 'Image_Optimizer_Pro\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, strlen($prefix));
    
    // Create the file path
    $file = __DIR__ . '/' . str_replace('_', '-', strtolower($relative_class)) . '.php';
    
    // Debug logging
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("[Image Optimizer] Trying to load class: {$class}");
        error_log("[Image Optimizer] Looking for file: {$file}");
    }

    // Require the file if it exists
    if (file_exists($file)) {
        require $file;
    } elseif (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("[Image Optimizer] File not found: {$file}");
    }
});
