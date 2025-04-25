<?php
namespace Image_Optimizer_Pro;

class Optimizer_Cron {
    private $optimizer;
    
    public function __construct() {
        $this->optimizer = new Image_Optimizer();
        
        add_action('iop_daily_optimization', [$this, 'daily_optimization']);
        add_filter('cron_schedules', [$this, 'add_cron_schedules']);
    }
    
    public function add_cron_schedules($schedules) {
        $schedules['iop_every_six_hours'] = [
            'interval' => 6 * HOUR_IN_SECONDS,
            'display' => __('Every 6 hours', 'image-optimizer-pro')
        ];
        
        return $schedules;
    }
    
    public function daily_optimization() {
        $args = [
            'post_type' => 'attachment',
            'post_mime_type' => ['image/jpeg', 'image/png'],
            'post_status' => 'inherit',
            'posts_per_page' => -1,
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
            return;
        }
        
        // Process in batches to prevent timeouts
        $batch_size = 20;
        $batches = array_chunk($attachments, $batch_size);
        
        foreach ($batches as $batch) {
            foreach ($batch as $attachment_id) {
                $this->optimizer->optimize_upload($attachment_id);
            }
            
            // Give the server a breather
            if (count($batches) > 1) {
                sleep(2);
            }
        }
        
        // Send notification email
        $this->send_notification(count($attachments));
    }
    
    private function send_notification($count) {
        $to = get_option('admin_email');
        $subject = __('Daily Image Optimization Complete', 'image-optimizer-pro');
        $message = sprintf(
            __('%d images were optimized in your daily optimization run.', 'image-optimizer-pro'),
            $count
        );
        
        wp_mail($to, $subject, $message);
    }
}