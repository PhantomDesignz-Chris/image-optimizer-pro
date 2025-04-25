<?php
spl_autoload_register(function ($class) {
    // Only handle our plugin's classes
    if (strpos($class, 'Image_Optimizer_Pro\\') !== 0) {
        return;
    }

    // Convert to file path
    $file = __DIR__ . '/' . str_replace(
        ['Image_Optimizer_Pro\\', '_'],
        ['', '-'],
        strtolower($class)
    ) . '.php';

    // Verify the exact path we're trying to load
    if (!file_exists($file)) {
        error_log("[IOP] Class file not found: {$file}");
        return;
    }

    // Verify the file contains the expected class
    $contents = file_get_contents($file);
    $class_name = substr($class, strrpos($class, '\\') + 1);
    if (!preg_match('/class\s+' . $class_name . '\b/', $contents)) {
        error_log("[IOP] Class {$class_name} not found in file: {$file}");
        return;
    }

    require $file;
});
