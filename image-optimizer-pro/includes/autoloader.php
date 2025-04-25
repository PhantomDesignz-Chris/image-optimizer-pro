<?php
spl_autoload_register(function ($class_name) {
    $namespace = 'Image_Optimizer_Pro';
    
    if (strpos($class_name, $namespace) !== 0) {
        return;
    }
    
    $class_name = str_replace($namespace, '', $class_name);
    $class_name = str_replace('\\', DIRECTORY_SEPARATOR, $class_name);
    $class_file = IOP_PLUGIN_DIR . 'includes' . $class_name . '.php';
    
    if (file_exists($class_file)) {
        require_once $class_file;
    }
});