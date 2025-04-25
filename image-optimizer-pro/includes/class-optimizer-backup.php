<?php
namespace Image_Optimizer_Pro;

class Optimizer_Backup {
    public function __construct() {
        add_action('iop_pre_optimization', [$this, 'maybe_backup_image']);
    }
    
    public function maybe_backup_image($attachment_id) {
        $options = get_option('iop_settings');
        
        if (isset($options['backup_originals']) && $options['backup_originals']) {
            $this->backup_image($attachment_id);
        }
    }
    
    public function backup_image($attachment_id) {
        $file_path = get_attached_file($attachment_id);
        
        if (!file_exists($file_path)) {
            return false;
        }
        
        $backup_path = $this->get_backup_path($attachment_id);
        $backup_dir = dirname($backup_path);
        
        if (!file_exists($backup_dir)) {
            wp_mkdir_p($backup_dir);
        }
        
        return copy($file_path, $backup_path);
    }
    
    public function restore_image($attachment_id) {
        $backup_path = $this->get_backup_path($attachment_id);
        $file_path = get_attached_file($attachment_id);
        
        if (!file_exists($backup_path)) {
            return false;
        }
        
        // Restore the main image
        $result = copy($backup_path, $file_path);
        
        if ($result) {
            // Update attachment metadata
            $metadata = wp_generate_attachment_metadata($attachment_id, $file_path);
            wp_update_attachment_metadata($attachment_id, $metadata);
            
            // Update optimization stats
            delete_post_meta($attachment_id, 'iop_optimized');
            delete_post_meta($attachment_id, 'iop_optimized_size');
            delete_post_meta($attachment_id, 'iop_original_size');
            
            return true;
        }
        
        return false;
    }
    
    public function delete_backup($attachment_id) {
        $backup_path = $this->get_backup_path($attachment_id);
        
        if (file_exists($backup_path)) {
            return unlink($backup_path);
        }
        
        return false;
    }
    
    public function get_backup_path($attachment_id) {
        $file_path = get_attached_file($attachment_id);
        $file_info = pathinfo($file_path);
        
        return IOP_BACKUP_DIR . $attachment_id . '/' . $file_info['basename'];
    }
    
    public function backup_exists($attachment_id) {
        return file_exists($this->get_backup_path($attachment_id));
    }
}