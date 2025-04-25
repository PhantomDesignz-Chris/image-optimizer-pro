<?php if (!defined('ABSPATH')) exit; ?>

<div class="iop-stats-table-wrap">
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Filename', 'image-optimizer-pro'); ?></th>
                <th><?php _e('Original Size', 'image-optimizer-pro'); ?></th>
                <th><?php _e('Optimized Size', 'image-optimizer-pro'); ?></th>
                <th><?php _e('Savings', 'image-optimizer-pro'); ?></th>
                <th><?php _e('Date', 'image-optimizer-pro'); ?></th>
                <th><?php _e('Actions', 'image-optimizer-pro'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($optimizations as $optimization) : ?>
                <tr>
                    <td>
                        <a href="<?php echo get_edit_post_link($optimization->attachment_id); ?>" target="_blank">
                            <?php echo esc_html($optimization->filename); ?>
                        </a>
                    </td>
                    <td><?php echo size_format($optimization->original_size, 2); ?></td>
                    <td><?php echo size_format($optimization->optimized_size, 2); ?></td>
                    <td>
                        <span class="iop-savings-badge">
                            <?php echo number_format($optimization->savings_percent, 2); ?>%
                        </span>
                        (<?php echo size_format($optimization->original_size - $optimization->optimized_size, 2); ?>)
                    </td>
                    <td><?php echo date_i18n(get_option('date_format'), strtotime($optimization->optimized_date)); ?></td>
                    <td>
                        <?php if ($optimization->backup_exists) : ?>
                            <button class="button iop-restore-btn" 
                                    data-attachment-id="<?php echo $optimization->attachment_id; ?>">
                                <?php _e('Restore', 'image-optimizer-pro'); ?>
                            </button>
                        <?php endif; ?>
                        <a href="<?php echo wp_get_attachment_url($optimization->attachment_id); ?>" 
                           target="_blank" 
                           class="button">
                            <?php _e('View', 'image-optimizer-pro'); ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6">
                    <div class="tablenav-pages">
                        <?php echo paginate_links([
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo;'),
                            'next_text' => __('&raquo;'),
                            'total' => $total_pages,
                            'current' => $current_page
                        ]); ?>
                    </div>
                </td>
            </tr>
        </tfoot>
    </table>
</div>