<?php
spl_autoload_register(function ($class_name) {
    // Only handle our plugin's classes
    if (strpos($class_name, 'Image_Optimizer_Pro\\') !== 0) {
        return;
    }

    // Convert class name to file path
    $file = __DIR__ . '/includes/' . strtolower(str_replace(
        ['Image_Optimizer_Pro\\', '_'],
        ['', '-'],
        $class_name
    )) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
