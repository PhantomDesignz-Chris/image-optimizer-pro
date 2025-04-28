<?php
namespace Image_Optimizer_Pro;

class Optimizer_Ajax {
    private $optimizer;
    
    public function __construct() {
        $this->optimizer = new Image_Optimizer();

            add_action('update_option_iop_settings', function() {
        $this->load_settings();
    });
    $this->load_settings();
        
        // Register AJAX handlers
        add_action('wp_ajax_iop_get_image_stats', [$this, 'get_image_stats']);
        add_action('wp_ajax_iop_process_batch', [$this, 'process_batch']);
        add_action('wp_ajax_iop_restore_image', [$this, 'restore_image']);
        add_action('wp_ajax_iop_delete_backup', [$this, 'delete_backup']);
    }

    public function get_image_stats() {
        try {
            $this->verify_nonce();
            $this->check_permissions();

            global $wpdb;
            
            $total = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->posts} 
                WHERE post_type = 'attachment' 
                AND post_mime_type LIKE 'image/%'"
            );
            
            $optimized = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} 
                WHERE meta_key = 'iop_optimized' 
                AND meta_value = '1'"
            );
            
            $backups = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}iop_optimizations 
                WHERE backup_exists = 1"
            );

            wp_send_json_success([
                'total' => (int)$total,
                'optimized' => (int)$optimized,
                'unoptimized' => (int)$total - (int)$optimized,
                'backups' => (int)$backups
            ]);

        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage(), 400);
        }
    }

    public function process_batch() {
        try {
            set_time_limit(0);
            wp_raise_memory_limit('image-optimizer');
            $this->verify_nonce();
            $this->check_permissions();

            $batch = isset($_POST['batch']) ? absint($_POST['batch']) : 0;
            $batch_size = isset($_POST['batch_size']) ? absint($_POST['batch_size']) : 5;
            
            $args = [
                'post_type' => 'attachment',
                'post_mime_type' => ['image/jpeg', 'image/png'],
                'post_status' => 'inherit',
                'posts_per_page' => $batch_size,
                'offset' => $batch * $batch_size,
                'fields' => 'ids',
                'meta_query' => [
                    [
                        'key' => 'iop_optimized',
                        'compare' => 'NOT EXISTS'
                    ]
                ]
            ];
            
            $attachments = get_posts($args);
            $total_unoptimized = $this->count_unoptimized_images();
            
            if (empty($attachments)) {
                wp_send_json_success([
                    'processed' => $batch * $batch_size,
                    'total' => $total_unoptimized,
                    'complete' => true,
                    'savings' => '0B',
                    'current_file' => ''
                ]);
            }
            
            $processed = 0;
            $savings = 0;
            $last_file = '';
            
            foreach ($attachments as $attachment_id) {
                try {
                    $result = $this->optimizer->optimize_upload($attachment_id);
                    if ($result) {
                        $processed++;
                        error_log("Successfully optimized: {$attachment_id}");
                    } else {
                        error_log("Failed to optimize: {$attachment_id}");
                    }
                } catch (\Exception $e) {
                    error_log("Optimization error for {$attachment_id}: " . $e->getMessage());
                }
            }
            
            wp_send_json_success([
                'processed' => ($batch * $batch_size) + $processed,
                'total' => $total_unoptimized,
                'complete' => (($batch * $batch_size) + $processed) >= $total_unoptimized,
                'savings' => size_format($savings, 2),
                'current_file' => $last_file
            ]);

        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage(), 400);
        }
    }

    private function verify_nonce() {
        if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'iop_nonce')) {
            throw new \Exception('Security check failed');
        }
    }

private function log_optimization($file_path, $attachment_id) {
    // Get size BEFORE optimization
    $original_size = filesize($file_path);
    
    // Wait for file system changes
    clearstatcache(true, $file_path);
    usleep(500000); // 0.5 second delay
    
    // Get size AFTER optimization
    $optimized_size = filesize($file_path);
    
    // Ensure valid calculation
    if ($optimized_size >= $original_size) {
        error_log("No savings detected for: {$file_path}");
        $optimized_size = $original_size;
    }
    
    // Return or store the optimization results
    return [
        'original_size' => $original_size,
        'optimized_size' => $optimized_size,
        'savings' => $original_size - $optimized_size
    ];
}

private function check_permissions() {
    if (!current_user_can('upload_files')) {
        throw new \Exception('Permission denied');
    }
}

    private function count_unoptimized_images() {
        $args = [
            'post_type' => 'attachment',
            'post_mime_type' => ['image/jpeg', 'image/png'],
            'post_status' => 'inherit',
            'fields' => 'ids',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'iop_optimized',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ];
        
        $query = new \WP_Query($args);
        return $query->post_count;
    }

    // Rest of your methods..
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
