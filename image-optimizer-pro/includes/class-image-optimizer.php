<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Image_Optimizer_Pro_Optimize {
    private $quality;
    private $preserve_metadata;

    public function __construct($quality = 80, $preserve_metadata = false) {
        $this->quality = $quality;
        $this->preserve_metadata = $preserve_metadata;
    }

    public function optimize_image($attachment_id, $force = false) {
        if (!wp_attachment_is_image($attachment_id)) {
            return new WP_Error('not_an_image', __('The specified attachment is not an image.', 'image-optimizer-pro'));
        }

        if (!$force && get_post_meta($attachment_id, '_image_optimizer_pro_optimized', true)) {
            return new WP_Error('already_optimized', __('The image has already been optimized.', 'image-optimizer-pro'));
        }

        $file_path = get_attached_file($attachment_id);
        
        // ====== FIX ADDED STARTS ======
        $backup_dir = wp_upload_dir()['basedir'] . '/image-optimizer-pro-backups/';
        if (!file_exists($backup_dir)) {
            wp_mkdir_p($backup_dir);
        }
        $backup_path = $backup_dir . basename($file_path);
        
        if (!file_exists($backup_path)) {
            copy($file_path, $backup_path);
        }
        // ====== FIX ADDED ENDS ======

        $original_size = filesize($file_path);
        $optimized = $this->compress_image($file_path);

        if (is_wp_error($optimized)) {
            return $optimized;
        }

        $optimized_size = filesize($file_path);

        // ====== FIX ADDED STARTS ======
        if ($original_size > 0 && $optimized_size > 0) {
            $savings = $original_size - $optimized_size;
            update_post_meta($attachment_id, '_image_optimizer_pro_savings', $savings);
            update_post_meta($attachment_id, '_image_optimizer_pro_optimized', true);
            update_post_meta($attachment_id, '_image_optimizer_pro_optimized_size', $optimized_size);
        } else {
            $savings = 0;
        }
        // ====== FIX ADDED ENDS ======

        return array(
            'original_size' => $original_size,
            'optimized_size' => $optimized_size,
            'savings' => $savings,
            'success' => true
        );
    }

    private function compress_image($file_path) {
        // ... (keep all existing compression code unchanged) ...
    }
}
?>
