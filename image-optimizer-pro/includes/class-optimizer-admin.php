<?php
namespace Image_Optimizer_Pro;

class Optimizer_Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        // Add media library columns
        add_filter('manage_media_columns', [$this, 'add_media_columns']);
        add_action('manage_media_custom_column', [$this, 'media_column_content'], 10, 2);
        
        // Add bulk actions
        add_filter('bulk_actions-upload', [$this, 'register_bulk_actions']);
        add_filter('handle_bulk_actions-upload', [$this, 'handle_bulk_actions'], 10, 3);
        
        // Add compare viewer action
        add_filter('media_row_actions', [$this, 'add_media_row_action'], 10, 2);
    }
    
    public static function activate() {
        self::create_stats_table();
        wp_schedule_event(time(), 'daily', 'iop_daily_optimization');
    }
    
    public static function deactivate() {
        wp_clear_scheduled_hook('iop_daily_optimization');
    }
    
    private static function create_stats_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'iop_optimizations';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            attachment_id bigint(20) NOT NULL,
            original_size bigint(20) NOT NULL,
            optimized_size bigint(20) NOT NULL,
            savings_percent float NOT NULL,
            optimized_date datetime NOT NULL,
            format varchar(10) NOT NULL,
            backup_exists tinyint(1) DEFAULT 0,
            PRIMARY KEY  (id),
            KEY attachment_id (attachment_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function add_admin_menu() {
        add_options_page(
            __('Image Optimizer Pro', 'image-optimizer-pro'),
            __('Image Optimizer', 'image-optimizer-pro'),
            'manage_options',
            'image-optimizer-pro',
            [$this, 'settings_page']
        );
        
        add_media_page(
            __('Bulk Optimize', 'image-optimizer-pro'),
            __('Bulk Optimize', 'image-optimizer-pro'),
            'upload_files',
            'iop-bulk-optimize',
            [$this, 'bulk_optimize_page']
        );
        
        add_media_page(
            __('Compare Images', 'image-optimizer-pro'),
            '',
            'upload_files',
            'iop-compare-viewer',
            [$this, 'compare_viewer_page']
        );
    }
    
    public function settings_page() {
        require_once IOP_PLUGIN_DIR . 'templates/admin-settings.php';
    }
    
    public function bulk_optimize_page() {
        $stats_handler = new Optimizer_Stats();
        $total_stats = $stats_handler->get_total_stats();
        $recent_optimizations = $stats_handler->get_recent_optimizations(20);
        
        require_once IOP_PLUGIN_DIR . 'templates/bulk-optimize.php';
    }
    
    public function compare_viewer_page() {
        if (!isset($_GET['attachment_id']) || !current_user_can('upload_files')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'image-optimizer-pro'));
        }
        
        $attachment_id = absint($_GET['attachment_id']);
        $backup_handler = new Optimizer_Backup();
        $stats_handler = new Optimizer_Stats();
        
        $original_path = $backup_handler->get_backup_path($attachment_id);
        $optimized_path = get_attached_file($attachment_id);
        
        if (!file_exists($original_path)) {
            wp_die(__('Original backup not found for this image.', 'image-optimizer-pro'));
        }
        
        // Create temporary attachment for original if needed
        $original_id = $this->maybe_create_temp_attachment($original_path, $attachment_id);
        
        $stats = $stats_handler->get_stats_for_attachment($attachment_id);
        $original_size = filesize($original_path);
        $optimized_size = filesize($optimized_path);
        $format = pathinfo($optimized_path, PATHINFO_EXTENSION);
        
        require_once IOP_PLUGIN_DIR . 'templates/compare-viewer.php';
    }
    
    private function maybe_create_temp_attachment($file_path, $original_id) {
        $existing = get_posts([
            'post_type' => 'attachment',
            'meta_key' => '_iop_temp_original_for',
            'meta_value' => $original_id,
            'posts_per_page' => 1
        ]);
        
        if (!empty($existing)) {
            return $existing[0]->ID;
        }
        
        $file_name = basename($file_path);
        $upload_dir = wp_upload_dir();
        $new_path = $upload_dir['path'] . '/iop-original-' . $file_name;
        
        copy($file_path, $new_path);
        
        $file_type = wp_check_filetype($new_path);
        $attachment = [
            'guid' => $upload_dir['url'] . '/iop-original-' . $file_name,
            'post_mime_type' => $file_type['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', $file_name),
            'post_content' => '',
            'post_status' => 'inherit'
        ];
        
        $attach_id = wp_insert_attachment($attachment, $new_path);
        update_post_meta($attach_id, '_iop_temp_original_for', $original_id);
        update_post_meta($attach_id, '_iop_is_temp', true);
        
        return $attach_id;
    }
    
    public function enqueue_scripts($hook) {
        if ($hook === 'settings_page_image-optimizer-pro' || $hook === 'media_page_iop-bulk-optimize') {
            wp_enqueue_style('iop-admin-css', IOP_PLUGIN_URL . 'assets/css/admin.css', [], IOP_VERSION);
            wp_enqueue_script('iop-admin-js', IOP_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], IOP_VERSION, true);
            
            wp_localize_script('iop-admin-js', 'iop_vars', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('iop_nonce'),
                'optimizing' => __('Optimizing...', 'image-optimizer-pro'),
                'error' => __('Error occurred', 'image-optimizer-pro'),
                'confirm_restore' => __('Are you sure you want to restore the original image? This cannot be undone.', 'image-optimizer-pro'),
                'saved' => __('saved', 'image-optimizer-pro')
            ]);
        }
        
        if ($hook === 'media_page_iop-compare-viewer') {
            wp_enqueue_style('iop-frontend-css', IOP_PLUGIN_URL . 'assets/css/frontend.css', [], IOP_VERSION);
            wp_enqueue_script('iop-frontend-js', IOP_PLUGIN_URL . 'assets/js/frontend.js', ['jquery'], IOP_VERSION, true);
        }
    }
    
    public function add_media_columns($columns) {
        $columns['iop_optimized'] = __('Optimized', 'image-optimizer-pro');
        $columns['iop_savings'] = __('Savings', 'image-optimizer-pro');
        return $columns;
    }
    
    public function media_column_content($column_name, $attachment_id) {
        if ($column_name === 'iop_optimized') {
            if (get_post_meta($attachment_id, 'iop_optimized', true)) {
                echo '<span class="dashicons dashicons-yes-alt" style="color:#46b450;"></span>';
            } else {
                echo '<span class="dashicons dashicons-no-alt" style="color:#dc3232;"></span>';
            }
        }
        
        if ($column_name === 'iop_savings') {
            $original = get_post_meta($attachment_id, 'iop_original_size', true);
            $optimized = get_post_meta($attachment_id, 'iop_optimized_size', true);
            
            if ($original && $optimized) {
                $savings = $original - $optimized;
                $percent = round(($savings / $original) * 100, 2);
                echo size_format($savings, 2) . ' (' . $percent . '%)';
            }
        }
    }
    
    public function register_bulk_actions($actions) {
        $actions['iop_bulk_optimize'] = __('Optimize Images', 'image-optimizer-pro');
        $actions['iop_bulk_restore'] = __('Restore Originals', 'image-optimizer-pro');
        return $actions;
    }
    
    public function handle_bulk_actions($redirect_to, $doaction, $post_ids) {
        if ($doaction !== 'iop_bulk_optimize' && $doaction !== 'iop_bulk_restore') {
            return $redirect_to;
        }
        
        $optimizer = new Image_Optimizer();
        $backup = new Optimizer_Backup();
        
        foreach ($post_ids as $post_id) {
            if ($doaction === 'iop_bulk_optimize') {
                $optimizer->optimize_upload($post_id);
            } elseif ($doaction === 'iop_bulk_restore') {
                $backup->restore_image($post_id);
            }
        }
        
        $redirect_to = add_query_arg('bulk_optimized', count($post_ids), $redirect_to);
        return $redirect_to;
    }
    
    public function add_media_row_action($actions, $post) {
        if (!wp_attachment_is_image($post->ID) || !get_post_meta($post->ID, 'iop_optimized', true)) {
            return $actions;
        }
        
        $actions['iop_compare'] = sprintf(
            '<a href="%s" aria-label="%s">%s</a>',
            admin_url('upload.php?page=iop-compare-viewer&attachment_id=' . $post->ID),
            esc_attr__('Compare optimized and original versions', 'image-optimizer-pro'),
            __('Compare', 'image-optimizer-pro')
        );
        
        return $actions;
    }
    
public function register_settings() {
    register_setting('iop_settings_group', 'iop_settings');

    // General Settings Section
    add_settings_section(
        'iop_general_settings',
        __('General Settings', 'image-optimizer-pro'),
        [$this, 'general_settings_section'],
        'image-optimizer-pro'
    );

    // Compression Level
    add_settings_field(
        'iop_compression_level',
        __('Compression Level', 'image-optimizer-pro'),
        [$this, 'compression_level_field'],
        'image-optimizer-pro',
        'iop_general_settings'
    );

    // Image Resizing
    add_settings_field(
        'iop_resize_large_images',
        __('Resize Large Images', 'image-optimizer-pro'),
        [$this, 'resize_field'],
        'image-optimizer-pro',
        'iop_general_settings'
    );

    // EXIF Metadata
    add_settings_field(
        'iop_strip_metadata',
        __('EXIF Metadata', 'image-optimizer-pro'),
        [$this, 'metadata_field'],
        'image-optimizer-pro',
        'iop_general_settings'
    );

    // Format Conversion
    add_settings_field(
        'iop_convert_format',
        __('Format Conversion', 'image-optimizer-pro'),
        [$this, 'format_field'],
        'image-optimizer-pro',
        'iop_general_settings'
    );

    // Backup Settings
    add_settings_field(
        'iop_backup_originals',
        __('Backup Originals', 'image-optimizer-pro'),
        [$this, 'backup_field'],
        'image-optimizer-pro',
        'iop_general_settings'
    );

    // Advanced Settings Section
    add_settings_section(
        'iop_advanced_settings',
        __('Advanced Settings', 'image-optimizer-pro'),
        [$this, 'advanced_settings_section'],
        'image-optimizer-pro'
    );

    // Optimization Mode
    add_settings_field(
        'iop_optimization_mode',
        __('Optimization Mode', 'image-optimizer-pro'),
        [$this, 'optimization_mode_field'],
        'image-optimizer-pro',
        'iop_advanced_settings'
    );
}

// Add all the corresponding field methods:
public function resize_field() {
    $options = get_option('iop_settings');
    ?>
    <label>
        <input type="checkbox" name="iop_settings[resize_large]" <?php checked($options['resize_large'] ?? true); ?>>
        <?php _e('Resize images larger than:', 'image-optimizer-pro'); ?>
    </label>
    <div style="margin-top: 10px;">
        <input type="number" name="iop_settings[max_width]" value="<?php echo esc_attr($options['max_width'] ?? 1920); ?>" style="width: 80px;"> ×
        <input type="number" name="iop_settings[max_height]" value="<?php echo esc_attr($options['max_height'] ?? 1080); ?>" style="width: 80px;"> px
    </div>
    <?php
}

public function metadata_field() {
    $options = get_option('iop_settings');
    ?>
    <label>
        <input type="checkbox" name="iop_settings[strip_metadata]" <?php checked($options['strip_metadata'] ?? true); ?>>
        <?php _e('Remove EXIF metadata (camera info, GPS location, etc.)', 'image-optimizer-pro'); ?>
    </label>
    <?php
}

public function format_field() {
    $options = get_option('iop_settings');
    $current = $options['convert_to'] ?? 'original';
    ?>
    <select name="iop_settings[convert_to]">
        <option value="original" <?php selected($current, 'original'); ?>><?php _e('Keep original format', 'image-optimizer-pro'); ?></option>
        <option value="webp" <?php selected($current, 'webp'); ?>><?php _e('Convert to WebP', 'image-optimizer-pro'); ?></option>
        <?php if (function_exists('imageavif')) : ?>
            <option value="avif" <?php selected($current, 'avif'); ?>><?php _e('Convert to AVIF', 'image-optimizer-pro'); ?></option>
        <?php endif; ?>
    </select>
    <?php
}

public function backup_field() {
    $options = get_option('iop_settings');
    ?>
    <label>
        <input type="checkbox" name="iop_settings[backup_originals]" <?php checked($options['backup_originals'] ?? true); ?>>
        <?php _e('Keep original images as backup', 'image-optimizer-pro'); ?>
    </label>
    <?php
}
    
    public function general_settings_section() {
        echo '<p>' . __('Configure how Image Optimizer Pro processes your images.', 'image-optimizer-pro') . '</p>';
    }
    
    public function compression_level_field() {
        $options = get_option('iop_settings');
        $value = $options['compression_level'] ?? 80;
        
        echo '<input type="range" id="iop_compression_level" name="iop_settings[compression_level]" 
              min="50" max="100" value="' . esc_attr($value) . '" class="iop-range-input">
              <span class="iop-range-value">' . esc_html($value) . '%</span>
              <p class="description">' . __('Higher values mean better quality but larger files.', 'image-optimizer-pro') . '</p>';
    }
    
    // Other settings field methods...
}
