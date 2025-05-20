<?php
/**
 * Template Part: User Ticket Row
 *
 * @package HelpPress Tickets
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$ticket_id = get_the_ID();
$post = get_post($ticket_id);
$status = get_post_status();
$status_class = '';
$status_label = '';

// Get status class and label
switch ($status) {
    case 'hp_open':
        $status_class = 'bg-info text-dark';
        $status_label = esc_html__('Open', 'helppress-tickets');
        break;
    case 'hp_in_progress':
        $status_class = 'bg-primary';
        $status_label = esc_html__('In Progress', 'helppress-tickets');
        break;
    case 'hp_resolved':
        $status_class = 'bg-success';
        $status_label = esc_html__('Resolved', 'helppress-tickets');
        break;
    case 'hp_closed':
        $status_class = 'bg-secondary';
        $status_label = esc_html__('Closed', 'helppress-tickets');
        break;
    default:
        $status_class = 'bg-secondary';
        $status_label = esc_html($status);
}

// Get priority
$priority_terms = wp_get_object_terms($ticket_id, 'hp_ticket_priority');
$priority = !empty($priority_terms) ? $priority_terms[0]->name : esc_html__('Not set', 'helppress-tickets');

// Get priority class
$priority_class = 'bg-secondary';
if (!empty($priority_terms)) {
    switch (strtolower($priority_terms[0]->slug)) {
        case 'low':
            $priority_class = 'bg-success';
            break;
        case 'medium':
            $priority_class = 'bg-info text-dark';
            break;
        case 'high':
            $priority_class = 'bg-warning text-dark';
            break;
        case 'urgent':
            $priority_class = 'bg-danger';
            break;
    }
}
?>
<tr>
    <td>#<?php echo esc_html($ticket_id); ?></td>
    <td>
        <!-- <a href="<?php //echo esc_url(helppress_get_ticket_url($ticket_id, 'view')); ?>"> -->
            <?php echo esc_html(get_the_title()); ?>
        <!-- </a> -->
    </td>
    <td><span class="badge <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_label); ?></span></td>
    <td><span class="badge <?php echo esc_attr($priority_class); ?>"><?php echo esc_html($priority); ?></span></td>
    <td><?php echo esc_html(get_the_modified_date('M j, Y g:i a')); ?></td>
    <td>
        <div class="btn-group">
            <a href="<?php echo esc_url(helppress_get_ticket_url($ticket_id, 'view')); ?>" class="btn btn-sm btn-outline-primary">
                <?php esc_html_e('View', 'helppress-tickets'); ?>
            </a>
            
            <?php 
            // Show edit button based on permissions and status
            if ($status !== 'hp_closed' && $status !== 'hp_resolved'):
                // For admins, link to admin edit screen
                if (current_user_can('edit_post', $ticket_id) || $post->post_author == get_current_user_id()):
                    ?>
                    <!--<a href="<?php //echo esc_url(get_edit_post_link($ticket_id)); ?>" class="btn btn-sm btn-outline-secondary">
                        <?php //esc_html_e('Edit', 'helppress-tickets'); ?>
                    </a> -->
                    <a href="<?php echo esc_url(helppress_get_ticket_url($ticket_id, 'edit')); ?>" class="btn btn-sm btn-outline-secondary">
                        <?php esc_html_e('Edit', 'helppress-tickets'); ?>
                    </a>
                    <?php
                // For regular users who own the ticket, link to frontend form
                elseif (get_current_user_id() === $post->post_author):
                    ?>
                    <a href="<?php echo esc_url(helppress_get_ticket_url($ticket_id, 'edit')); ?>" class="btn btn-sm btn-outline-secondary">
                        <?php esc_html_e('Edit', 'helppress-tickets'); ?>
                    </a>
                    <?php
                endif;
            endif; 
            ?>
        </div>
    </td>
</tr>