<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Image_Optimizer_Pro_Restore {
    public function restore_image($attachment_id) {
        if (!wp_attachment_is_image($attachment_id)) {
            return new WP_Error('not_an_image', __('The specified attachment is not an image.', 'image-optimizer-pro'));
        }

        $file_path = get_attached_file($attachment_id);
        $backup_dir = wp_upload_dir()['basedir'] . '/image-optimizer-pro-backups/';
        $backup_path = $backup_dir . basename($file_path);

        if (!file_exists($backup_path)) {
            return new WP_Error('no_backup', __('No backup found for this image.', 'image-optimizer-pro'));
        }

        if (!copy($backup_path, $file_path)) {
            return new WP_Error('restore_failed', __('Failed to restore the image.', 'image-optimizer-pro'));
        }

        unlink($backup_path);

        delete_post_meta($attachment_id, '_image_optimizer_pro_optimized');
        delete_post_meta($attachment_id, '_image_optimizer_pro_savings');
        delete_post_meta($attachment_id, '_image_optimizer_pro_optimized_size');

        return true;
    }

    // ====== FIX ADDED STARTS ======
    public function restore_all_images() {
        $optimized_images = get_posts(array(
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'    => array(
                array(
                    'key'     => '_image_optimizer_pro_optimized',
                    'value'   => '1',
                    'compare' => '='
                )
            )
        ));

        if (empty($optimized_images)) {
            return new WP_Error('no_images', __('No optimized images found.', 'image-optimizer-pro'));
        }

        $results = array();
        foreach ($optimized_images as $image_id) {
            $results[$image_id] = $this->restore_image($image_id);
        }

        return $results;
    }
    // ====== FIX ADDED ENDS ======
}
?>
