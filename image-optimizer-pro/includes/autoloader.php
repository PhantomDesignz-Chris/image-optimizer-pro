<?php
spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefix = 'Image_Optimizer_Pro\\';
    
    // Base directory for the namespace prefix
    $base_dir = __DIR__ . '/includes/';
    
    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Replace namespace separators and underscores with directory separators
    $file = $base_dir . str_replace(['\\', '_'], ['/', '-'], strtolower($relative_class)) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    } else {
        error_log("[Image Optimizer Pro] File not found: {$file}");
    }
});
