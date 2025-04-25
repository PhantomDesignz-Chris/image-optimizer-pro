<?php
namespace Image_Optimizer_Pro;

class Image_Optimizer {
    private $compression_level = 80;
    private $max_width = 1920;
    private $max_height = 1080;
    private $strip_metadata = true;
    private $convert_to = 'original'; // 'webp', 'avif', 'original'
    private $backup_originals = true;
    
    public function __construct() {
        $this->load_settings();
    }
    
    private function load_settings() {
        $options = get_option('iop_settings');
        
        if ($options) {
            $this->compression_level = isset($options['compression_level']) ? $options['compression_level'] : $this->compression_level;
            $this->max_width = isset($options['max_width']) ? $options['max_width'] : $this->max_width;
            $this->max_height = isset($options['max_height']) ? $options['max_height'] : $this->max_height;
            $this->strip_metadata = isset($options['strip_metadata']) ? (bool)$options['strip_metadata'] : $this->strip_metadata;
            $this->convert_to = isset($options['convert_to']) ? $options['convert_to'] : $this->convert_to;
            $this->backup_originals = isset($options['backup_originals']) ? (bool)$options['backup_originals'] : $this->backup_originals;
        }
    }
    
    public function optimize_upload($attachment_id) {
        if (!wp_attachment_is_image($attachment_id)) {
            return false;
        }
        
        $file_path = get_attached_file($attachment_id);
        $result = $this->optimize_image($file_path, $attachment_id);
        
        // Optimize all generated sizes
        $this->optimize_image_sizes($attachment_id);
        
        return $result;
    }
    
    public function optimize_image($file_path, $attachment_id = 0) {
        if (!file_exists($file_path)) {
            return false;
        }
        
        $file_info = pathinfo($file_path);
        $extension = strtolower($file_info['extension']);
        
        // Backup original if enabled
        if ($this->backup_originals && $attachment_id) {
            $this->backup_image($file_path, $attachment_id);
        }
        
        // Resize if needed
        if ($this->should_resize($file_path)) {
            $this->resize_image($file_path);
        }
        
        // Optimize based on format
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $result = $this->optimize_jpeg($file_path);
                break;
            case 'png':
                $result = $this->optimize_png($file_path);
                break;
            default:
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
    
    private function should_resize($file_path) {
        if (!$this->max_width && !$this->max_height) {
            return false;
        }
        
        list($width, $height) = getimagesize($file_path);
        
        return $width > $this->max_width || $height > $this->max_height;
    }
    
    private function resize_image($file_path) {
        // Implementation would use Imagick or GD to resize
        // This is a simplified version
        $editor = wp_get_image_editor($file_path);
        
        if (!is_wp_error($editor)) {
            $editor->resize($this->max_width, $this->max_height, false);
            $editor->save($file_path);
        }
    }
    
    private function optimize_jpeg($file_path) {
        // Implementation would use either Imagick or GD
        // This is a simplified version
        $quality = $this->compression_level;
        
        if (extension_loaded('imagick')) {
            $image = new \Imagick($file_path);
            
            if ($this->strip_metadata) {
                $image->stripImage();
            }
            
            $image->setImageCompression(\Imagick::COMPRESSION_JPEG);
            $image->setImageCompressionQuality($quality);
            $image->writeImage($file_path);
            $image->clear();
            
            return true;
        } elseif (extension_loaded('gd')) {
            $image = imagecreatefromjpeg($file_path);
            imagejpeg($image, $file_path, $quality);
            imagedestroy($image);
            
            return true;
        }
        
        return false;
    }
    
    private function optimize_png($file_path) {
        // Similar implementation for PNG
        // Would use pngquant or other optimization tools
    }
    
    private function convert_image($file_path, $format) {
        // Implementation for format conversion
    }
    
    private function backup_image($file_path, $attachment_id) {
        // Implementation for backing up original images
    }
    
    private function log_optimization($file_path, $attachment_id) {
        // Implementation for logging optimizations
    }
    
    public function optimize_image_sizes($attachment_id) {
        // Get all image sizes
        $sizes = wp_get_registered_image_subsizes();
        $metadata = wp_get_attachment_metadata($attachment_id);
        
        if (empty($metadata['sizes'])) {
            return;
        }
        
        $file_dir = dirname(get_attached_file($attachment_id));
        
        foreach ($metadata['sizes'] as $size => $size_data) {
            $size_path = $file_dir . '/' . $size_data['file'];
            $this->optimize_image($size_path);
        }
    }
    
    // Other methods for bulk optimization, restoration, etc.
}