<?php
namespace Image_Optimizer_Pro;

class Optimizer_Tools {
    public static function format_bytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    public static function get_image_formats() {
        $formats = [
            'original' => __('Original Format', 'image-optimizer-pro'),
            'webp' => __('WebP', 'image-optimizer-pro')
        ];
        
        if (function_exists('imageavif') && version_compare(PHP_VERSION, '8.1', '>=')) {
            $formats['avif'] = __('AVIF', 'image-optimizer-pro');
        }
        
        return $formats;
    }
    
    public static function is_webp_supported() {
        return (function_exists('imagewebp') || (extension_loaded('imagick') && in_array('WEBP', \Imagick::queryFormats())));
    }
    
    public static function is_avif_supported() {
        return function_exists('imageavif') && version_compare(PHP_VERSION, '8.1', '>=');
    }
    
    public static function get_server_info() {
        return [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
            'imagick_installed' => extension_loaded('imagick'),
            'gd_installed' => extension_loaded('gd'),
            'webp_supported' => self::is_webp_supported(),
            'avif_supported' => self::is_avif_supported(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time')
        ];
    }

    public function reset_optimization_status() {
    global $wpdb;
    
    // Reset postmeta
    $wpdb->query("
        DELETE FROM {$wpdb->postmeta} 
        WHERE meta_key IN ('iop_optimized', 'iop_original_size', 'iop_optimized_size')
    ");
    
    // Clear stats table
    $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}iop_optimizations");
    
    // Clear backups
    array_map('unlink', glob(IOP_BACKUP_DIR . '/*/*.*'));
    array_map('rmdir', glob(IOP_BACKUP_DIR . '/*'));
}
    
    public static function display_server_info() {
        $info = self::get_server_info();
        
        echo '<div class="iop-server-info">';
        echo '<h3>' . __('Server Information', 'image-optimizer-pro') . '</h3>';
        echo '<ul>';
        
        foreach ($info as $key => $value) {
            $key = str_replace('_', ' ', $key);
            $key = ucwords($key);
            
            if (is_bool($value)) {
                $value = $value ? __('Yes', 'image-optimizer-pro') : __('No', 'image-optimizer-pro');
            }
            
            echo '<li><strong>' . esc_html($key) . ':</strong> ' . esc_html($value) . '</li>';
        }
        
        echo '</ul>';
        echo '</div>';
    }
}
