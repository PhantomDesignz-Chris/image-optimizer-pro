<?php
namespace Image_Optimizer_Pro;

class Optimizer_Ajax {
    private $optimizer;
    
    public function __construct() {
        $this->optimizer = new Image_Optimizer();
        
        add_action('wp_ajax_iop_optimize_single', [$this, 'optimize_single']);
        add_action('wp_ajax_iop_bulk_optimize', [$this, 'bulk_optimize']);
        add_action('wp_ajax_iop_restore_image', [$this, 'restore_image']);
        add_action('wp_ajax_iop_delete_backup', [$this, 'delete_backup']);
        add_action('wp_ajax_iop_get_stats', [$this, 'get_stats']);
    }
    
    public function optimize_single() {
        check_ajax_referer('iop_nonce', 'nonce');
        
        if (!current_user_can('upload_files')) {
            wp_send_json_error(__('Permission denied', 'image-optimizer-pro'));
        }
        
        $attachment_id = isset($_POST['attachment_id']) ? absint($_POST['attachment_id']) : 0;
        
        if (!$attachment_id) {
            wp_send_json_error(__('Invalid attachment ID', 'image-optimizer-pro'));
        }
        
        $result = $this->optimizer->optimize_upload($attachment_id);
        
        if ($result) {
            $stats = $this->get_attachment_stats($attachment_id);
            wp_send_json_success($stats);
        } else {
            wp_send_json_error(__('Optimization failed', 'image-optimizer-pro'));
        }
    }
    
    public function bulk_optimize() {
        check_ajax_referer('iop_nonce', 'nonce');
        
        if (!current_user_can('upload_files')) {
            wp_send_json_error(__('Permission denied', 'image-optimizer-pro'));
        }
        
        $offset = isset($_POST['offset']) ? absint($_POST['offset']) : 0;
        $batch_size = 5; // Number of images to process per request
        
        $args = [
            'post_type' => 'attachment',
            'post_mime_type' => ['image/jpeg', 'image/png'],
            'post_status' => 'inherit',
            'posts_per_page' => $batch_size,
            'offset' => $offset,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => 'iop_optimized',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ];
        
        $attachments = get_posts($args);
        
        if (empty($attachments)) {
            wp_send_json_success([
                'complete' => true,
                'message' => __('All images optimized!', 'image-optimizer-pro'),
                'processed' => 0,
                'total_savings' => 0
            ]);
        }
        
        $processed = 0;
        $total_savings = 0;
        
        foreach ($attachments as $attachment_id) {
            $result = $this->optimizer->optimize_upload($attachment_id);
            
            if ($result) {
                $processed++;
                $stats = $this->get_attachment_stats($attachment_id);
                $total_savings += $stats['savings_bytes'];
            }
        }
        
        wp_send_json_success([
            'complete' => false,
            'processed' => $processed,
            'total_savings' => size_format($total_savings, 2),
            'next_offset' => $offset + $batch_size
        ]);
    }
    
    private function get_attachment_stats($attachment_id) {
        // Implementation to get stats for an attachment
    }
    
    // Other AJAX methods for restoration, backup deletion, etc.
}