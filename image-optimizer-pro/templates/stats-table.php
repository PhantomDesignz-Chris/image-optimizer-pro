<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$table_name = $wpdb->prefix . 'iop_optimizations';
$optimizations = $wpdb->get_results("
    SELECT o.*, p.post_title as filename 
    FROM $table_name o
    LEFT JOIN {$wpdb->posts} p ON o.attachment_id = p.ID
    ORDER BY o.optimized_date DESC
    LIMIT 10
");
?>

<?php if (!empty($optimizations)) : ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Filename', 'image-optimizer-pro'); ?></th>
                <th><?php _e('Original Size', 'image-optimizer-pro'); ?></th>
                <th><?php _e('Optimized Size', 'image-optimizer-pro'); ?></th>
                <th><?php _e('Savings', 'image-optimizer-pro'); ?></th>
                <th><?php _e('Date', 'image-optimizer-pro'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($optimizations as $optimization) : ?>
                <tr>
                    <td><?php echo esc_html($optimization->filename); ?></td>
                    <td><?php echo size_format($optimization->original_size, 2); ?></td>
                    <td><?php echo size_format($optimization->optimized_size, 2); ?></td>
                    <td>
                        <?php echo size_format($optimization->original_size - $optimization->optimized_size, 2); ?>
                        (<?php echo number_format($optimization->savings_percent, 2); ?>%)
                    </td>
                    <td><?php echo date_i18n(get_option('date_format'), strtotime($optimization->optimized_date)); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else : ?>
    <p><?php _e('No optimization data available yet.', 'image-optimizer-pro'); ?></p>
<?php endif; ?>
