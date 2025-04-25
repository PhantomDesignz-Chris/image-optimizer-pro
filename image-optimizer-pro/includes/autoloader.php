<?php
spl_autoload_register(function ($class) {
    // Only handle our plugin's classes
    if (strpos($class, 'Image_Optimizer_Pro\\') !== 0) {
        return;
    }

    // Convert class name to file path
    $relative_class = substr($class, strlen('Image_Optimizer_Pro\\'));
    $file = __DIR__ . '/' . str_replace('_', '-', strtolower($relative_class)) . '.php';

    // Verify the file exists
    if (file_exists($file)) {
        require $file;
    } else {
        error_log("[Image Optimizer] Class file not found: {$file}");
    }
});
