<?php
namespace Image_Optimizer_Pro;

class Optimizer_Stats {
    public function __construct() {
        add_action('admin_init', [$this, 'export_stats']);
    }
    
public function get_total_stats() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'iop_optimizations';
    
    $stats = $wpdb->get_row("
        SELECT 
            COUNT(*) as total_images,
            SUM(original_size) as total_original_size,
            SUM(optimized_size) as total_optimized_size,
            SUM(original_size - optimized_size) as total_savings
        FROM $table_name
    ");
    
    return [
        'total_images' => $stats->total_images ?: 0,
        'total_original' => $stats->total_original_size ?: 0,
        'total_optimized' => $stats->total_optimized_size ?: 0,
        'total_savings' => $stats->total_savings ?: 0,
        'savings_percent' => $stats->total_original_size ? 
            round(($stats->total_savings / $stats->total_original_size) * 100, 2) : 0
    ];
}

public function get_recent_optimizations($limit = 10) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'iop_optimizations';
    
    return $wpdb->get_results($wpdb->prepare("
        SELECT o.*, p.post_title as filename
        FROM $table_name o
        LEFT JOIN {$wpdb->posts} p ON o.attachment_id = p.ID
        ORDER BY o.optimized_date DESC
        LIMIT %d
    ", $limit));
}

    
    public function export_stats() {
        if (!isset($_GET['iop_export_stats']) || !current_user_can('manage_options')) {
            return;
        }
        
        check_admin_referer('iop_export_stats');
        
        $stats = $this->get_recent_optimizations(1000);
        $filename = 'image-optimizer-stats-' . date('Y-m-d') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        echo json_encode($stats, JSON_PRETTY_PRINT);
        exit;
    }
    
    public function log_optimization($attachment_id, $original_size, $optimized_size, $format) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'iop_optimizations';
        
        $savings_percent = round((($original_size - $optimized_size) / $original_size) * 100, 2);
        
        $wpdb->insert(
            $table_name,
            [
                'attachment_id' => $attachment_id,
                'original_size' => $original_size,
                'optimized_size' => $optimized_size,
                'savings_percent' => $savings_percent,
                'optimized_date' => current_time('mysql'),
                'format' => $format,
                'backup_exists' => file_exists($this->get_backup_path($attachment_id)) ? 1 : 0
            ],
            ['%d', '%d', '%d', '%f', '%s', '%s', '%d']
        );
        
        // Add a meta flag to mark this image as optimized
        update_post_meta($attachment_id, 'iop_optimized', true);
        update_post_meta($attachment_id, 'iop_optimized_size', $optimized_size);
        update_post_meta($attachment_id, 'iop_original_size', $original_size);
    }
    
    // Other methods for statistics handling
}
