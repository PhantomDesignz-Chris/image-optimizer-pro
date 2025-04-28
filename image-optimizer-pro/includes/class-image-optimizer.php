<?php
namespace Image_Optimizer_Pro;

class Image_Optimizer {
    private $compression_level = 80;
    private $max_width = 1920;
    private $max_height = 1080;
    private $strip_metadata = true;
    private $convert_to = 'original';
    private $backup_originals = true;
    
    public function __construct() {
        $this->load_settings();
    }
    
    private function load_settings() {
        $options = get_option('iop_settings');
        
        if ($options) {
            $this->compression_level = $options['compression_level'] ?? $this->compression_level;
            $this->max_width = $options['max_width'] ?? $this->max_width;
            $this->max_height = $options['max_height'] ?? $this->max_height;
            $this->strip_metadata = $options['strip_metadata'] ?? $this->strip_metadata;
            $this->convert_to = $options['convert_to'] ?? $this->convert_to;
            $this->backup_originals = $options['backup_originals'] ?? $this->backup_originals;
        }
    }
    
    public function optimize_upload($attachment_id) {
        if (!wp_attachment_is_image($attachment_id)) {
            error_log("Attachment {$attachment_id} is not an image");
            return false;
        }
        
        $file_path = get_attached_file($attachment_id);
        
        // Verify file consistency
        $mime_type = get_post_mime_type($attachment_id);
        if (!$this->validate_file_consistency($file_path, $mime_type)) {
            error_log("File type mismatch for attachment {$attachment_id}");
            return false;
        }
        
        $result = $this->optimize_image($file_path, $attachment_id);
        
        if ($result) {
            $this->optimize_image_sizes($attachment_id);
        }
        
        return $result;
    }
    
    public function optimize_image($file_path, $attachment_id = 0) {
        if (!file_exists($file_path)) {
            error_log("Image file not found: {$file_path}");
            return false;
        }
        
        try {
            // Validate image type
            $image_type = exif_imagetype($file_path);
            if (!in_array($image_type, [IMAGETYPE_JPEG, IMAGETYPE_PNG])) {
                error_log("Invalid image type: {$file_path}");
                return false;
            }
            
            if (!$this->is_valid_image_file($file_path)) {
                error_log("Invalid/corrupted image file: {$file_path}");
                return false;
            }
        } catch (\Exception $e) {
            error_log("Image validation failed: " . $e->getMessage());
            return false;
        }
        
        // Backup original if enabled
        if ($this->backup_originals && $attachment_id) {
            $this->backup_image($file_path, $attachment_id);
        }
        
        // Resize if needed
        if ($this->should_resize($file_path)) {
            $this->resize_image($file_path);
        }
        
        // Optimize based on format
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $result = false;
        
        try {
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $result = $this->optimize_jpeg($file_path);
                    break;
                case 'png':
                    $result = $this->optimize_png($file_path);
                    break;
                default:
                    error_log("Unsupported file format: {$extension}");
                    return false;
            }
        } catch (\Exception $e) {
            error_log("Optimization failed: " . $e->getMessage());
            return false;
        }
        
        // Convert format if needed
        if ($this->convert_to !== 'original' && $result) {
            $result = $this->convert_image($file_path, $this->convert_to);
        }
        
        // Log the optimization
        if ($result && $attachment_id) {
            $this->log_optimization($file_path, $attachment_id);
        }
        
        return $result;
    }
    
    private function validate_file_consistency($file_path, $mime_type) {
        $actual_type = exif_imagetype($file_path);
        $expected_types = [
            'image/jpeg' => IMAGETYPE_JPEG,
            'image/png'  => IMAGETYPE_PNG,
        ];
        return $actual_type === ($expected_types[$mime_type] ?? null);
    }
    
    private function is_valid_image_file($file_path) {
        try {
            if (extension_loaded('imagick')) {
                $imagick = new \Imagick();
                $imagick->pingImage($file_path);
                return true;
            }
            
            if (extension_loaded('gd')) {
                return getimagesize($file_path) !== false;
            }
            
            return false;
        } catch (\Exception $e) {
            error_log("Image validation failed: " . $e->getMessage());
            return false;
        }
    }
    
    private function should_resize($file_path) {
        if (!$this->max_width && !$this->max_height) return false;
        
        list($width, $height) = getimagesize($file_path);
        return $width > $this->max_width || $height > $this->max_height;
    }
    
    private function resize_image($file_path) {
        try {
            $editor = wp_get_image_editor($file_path);
            
            if (is_wp_error($editor)) {
                error_log("Image editor error: " . $editor->get_error_message());
                return false;
            }
            
            $editor->resize($this->max_width, $this->max_height, false);
            $result = $editor->save($file_path);
            
            return !is_wp_error($result);
            
        } catch (\Exception $e) {
            error_log("Resize failed: " . $e->getMessage());
            return false;
        }
    }
    
    private function optimize_jpeg($file_path) {
        try {
            if (extension_loaded('imagick')) {
                $image = new \Imagick($file_path);
                
                if ($image->getImageFormat() !== 'JPEG') {
                    error_log("Invalid JPEG format: {$file_path}");
                    return false;
                }
                
                if ($this->strip_metadata) {
                    $image->stripImage();
                }
                
                $image->setImageCompression(\Imagick::COMPRESSION_JPEG);
                $image->setImageCompressionQuality($this->compression_level);
                $image->writeImage($file_path);
                $image->clear();
                
                return true;
            }
            
            if (extension_loaded('gd')) {
                $image = imagecreatefromjpeg($file_path);
                if (!$image) {
                    error_log("GD failed to load JPEG: {$file_path}");
                    return false;
                }
                
                imagejpeg($image, $file_path, $this->compression_level);
                imagedestroy($image);
                
                return true;
            }
            
            error_log("No image library available for JPEG optimization");
            return false;
            
        } catch (\Exception $e) {
            error_log("JPEG optimization failed: " . $e->getMessage());
            return false;
        }
    }
    
    private function optimize_png($file_path) {
        try {
            if (extension_loaded('imagick')) {
                $image = new \Imagick($file_path);
                
                $image->setFormat('png');
                $image->setImageCompressionQuality($this->compression_level);
                
                if ($this->strip_metadata) {
                    $image->stripImage();
                }
                
                $image->writeImage($file_path);
                $image->clear();
                
                return true;
            }
            
            if (extension_loaded('gd')) {
                $image = imagecreatefrompng($file_path);
                if (!$image) {
                    error_log("GD failed to load PNG: {$file_path}");
                    return false;
                }
                
                imagepng($image, $file_path, round(9 - ($this->compression_level / 100 * 9)));
                imagedestroy($image);
                
                return true;
            }
            
            error_log("No image library available for PNG optimization");
            return false;
            
        } catch (\Exception $e) {
            error_log("PNG optimization failed: " . $e->getMessage());
            return false;
        }
    }
    
    private function convert_image($file_path, $format) {
        try {
            $new_path = pathinfo($file_path, PATHINFO_DIRNAME) . '/' . 
                       pathinfo($file_path, PATHINFO_FILENAME) . '.' . $format;
            
            if (extension_loaded('imagick')) {
                $image = new \Imagick($file_path);
                $image->setImageFormat($format);
                $image->writeImage($new_path);
                $image->clear();
            } elseif (extension_loaded('gd')) {
                $image = $this->gd_create_image($file_path);
                $this->gd_save_image($image, $new_path, $format);
                imagedestroy($image);
            } else {
                error_log("No image library available for conversion");
                return false;
            }
            
            // Update the attachment if converted successfully
            if (file_exists($new_path)) {
                unlink($file_path);
                return $new_path;
            }
            
            return false;
            
        } catch (\Exception $e) {
            error_log("Format conversion failed: " . $e->getMessage());
            return false;
        }
    }
    
    private function gd_create_image($file_path) {
        $type = exif_imagetype($file_path);
        
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($file_path);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($file_path);
            default:
                throw new \Exception("Unsupported image type for GD");
        }
    }
    
    private function gd_save_image($image, $path, $format) {
        switch ($format) {
            case 'webp':
                imagewebp($image, $path, $this->compression_level);
                break;
            case 'avif':
                imageavif($image, $path, $this->compression_level);
                break;
            default:
                throw new \Exception("Unsupported output format for GD");
        }
    }
    
    private function backup_image($file_path, $attachment_id) {
        try {
            $backup_dir = IOP_BACKUP_DIR . $attachment_id . '/';
            
            if (!wp_mkdir_p($backup_dir)) {
                error_log("Failed to create backup directory: {$backup_dir}");
                return false;
            }
            
            $backup_path = $backup_dir . basename($file_path);
            
            if (!copy($file_path, $backup_path)) {
                error_log("Failed to create backup for: {$file_path}");
                return false;
            }
            
            return true;
            
        } catch (\Exception $e) {
            error_log("Backup failed: " . $e->getMessage());
            return false;
        }
    }
    
    private function log_optimization($file_path, $attachment_id) {
        try {
            global $wpdb;
            
            $original_size = filesize($file_path);
            $optimized_size = filesize($file_path); // Will be updated after conversion
            $savings = $original_size - $optimized_size;
            $savings_percent = $original_size > 0 ? ($savings / $original_size) * 100 : 0;
            
            $format = pathinfo($file_path, PATHINFO_EXTENSION);
            $backup_exists = file_exists(IOP_BACKUP_DIR . $attachment_id . '/' . basename($file_path));
            
            $wpdb->insert(
                $wpdb->prefix . 'iop_optimizations',
                [
                    'attachment_id' => $attachment_id,
                    'original_size' => $original_size,
                    'optimized_size' => $optimized_size,
                    'savings_percent' => $savings_percent,
                    'optimized_date' => current_time('mysql'),
                    'format' => $format,
                    'backup_exists' => $backup_exists ? 1 : 0
                ],
                ['%d', '%d', '%d', '%f', '%s', '%s', '%d']
            );
            
            update_post_meta($attachment_id, 'iop_optimized', true);
            update_post_meta($attachment_id, 'iop_optimized_size', $optimized_size);
            update_post_meta($attachment_id, 'iop_original_size', $original_size);
            
        } catch (\Exception $e) {
            error_log("Optimization logging failed: " . $e->getMessage());
        }
    }
    
    public function optimize_image_sizes($attachment_id) {
        $metadata = wp_get_attachment_metadata($attachment_id);
        
        if (empty($metadata['sizes'])) {
            return;
        }
        
        $base_dir = dirname(get_attached_file($attachment_id)) . '/';
        
        foreach ($metadata['sizes'] as $size => $size_data) {
            $size_path = $base_dir . $size_data['file'];
            if (file_exists($size_path)) {
                $this->optimize_image($size_path);
            }
        }
    }
}
