<?php
// No namespace declaration here - this should be a regular PHP file

spl_autoload_register(function ($class) {
    // Only handle our plugin's classes
    $prefix = 'Image_Optimizer_Pro\\';
    
    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Replace namespace separators and underscores with directory separators
    $file = __DIR__ . '/' . str_replace(['\\', '_'], ['/', '-'], strtolower($relative_class)) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    } elseif (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("[Image Optimizer] Class file not found: {$file}");
    }
});
