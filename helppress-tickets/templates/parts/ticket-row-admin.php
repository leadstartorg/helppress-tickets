<?php
/**
 * Template Part: Admin Ticket Row
 *
 * @package HelpPress Tickets
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$ticket_id = get_the_ID();
$status = get_post_status();
$status_class = '';
$status_label = '';

// Get status class and label
switch ( $status ) {
    case 'hp_open':
        $status_class = 'bg-info text-dark';
        $status_label = esc_html__( 'Open', 'helppress-tickets' );
        break;
    case 'hp_in_progress':
        $status_class = 'bg-primary';
        $status_label = esc_html__( 'In Progress', 'helppress-tickets' );
        break;
    case 'hp_resolved':
        $status_class = 'bg-success';
        $status_label = esc_html__( 'Resolved', 'helppress-tickets' );
        break;
    case 'hp_closed':
        $status_class = 'bg-secondary';
        $status_label = esc_html__( 'Closed', 'helppress-tickets' );
        break;
    case 'publish':
        $status_class = 'bg-success';
        $status_label = esc_html__( 'Published', 'helppress-tickets' );
        break;
    case 'pending':
        $status_class = 'bg-warning text-dark';
        $status_label = esc_html__( 'Pending', 'helppress-tickets' );
        break;
    default:
        $status_class = 'bg-secondary';
        $status_label = esc_html( $status );
}

// Get priority
$priority_terms = wp_get_object_terms( $ticket_id, 'hp_ticket_priority' );
//$priority = ! empty( $priority_terms ) ? $priority_terms[0]->name : esc_html__( 'Not set', 'helppress-tickets' );
$priority = esc_html__( 'Not set', 'helppress-tickets' );
$priority_class = 'bg-secondary';

// Check for WP_Error before accessing array elements
if ( !is_wp_error($priority_terms) && !empty($priority_terms) ) {
    $priority = $priority_terms[0]->name;
    
    // Get priority class
    switch ( strtolower( $priority_terms[0]->slug ) ) {
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

// Get requester
$author_id = get_post_field( 'post_author', $ticket_id );
$author = get_userdata( $author_id );
$requester = $author ? $author->display_name : esc_html__( 'Unknown', 'helppress-tickets' );

// Get category
$category_terms = wp_get_object_terms( $ticket_id, 'hp_category' );
//$category = ! empty( $category_terms ) ? $category_terms[0]->name : esc_html__( 'Uncategorized', 'helppress-tickets' );
$category = esc_html__( 'Uncategorized', 'helppress-tickets' );

// Check for WP_Error before accessing array elements
if ( !is_wp_error($category_terms) && !empty($category_terms) ) {
    $category = $category_terms[0]->name;
}

// Is private?
$is_private = get_post_meta( $ticket_id, '_hp_ticket_private', true );
?>
<tr>
    <td>
        #<?php echo esc_html( $ticket_id ); ?>
        <?php if ( $is_private ) : ?>
            <i class="dashicons dashicons-lock" title="<?php esc_attr_e( 'Private Ticket', 'helppress-tickets' ); ?>"></i>
        <?php endif; ?>
    </td>
    <td>
        <?php the_title(); ?>
    </td>
    <td><span class="badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_label ); ?></span></td>
    <td><span class="badge <?php echo esc_attr( $priority_class ); ?>"><?php echo esc_html( $priority ); ?></span></td>
    <td><?php echo esc_html( $requester ); ?></td>
    <td><?php echo esc_html( $category ); ?></td>
    <td><?php echo esc_html( get_the_date( 'M j, Y g:i a' ) ); ?></td>
    <td><?php echo esc_html( get_the_modified_date( 'M j, Y g:i a' ) ); ?></td>
    <td>
        <div class="btn-group">
            <a href="<?php echo esc_url(helppress_get_ticket_url($ticket_id, 'view')); ?>" class="btn btn-sm btn-outline-primary">
                <?php esc_html_e('View', 'helppress-tickets'); ?>
            </a>
            <a href="<?php echo esc_url(get_edit_post_link($ticket_id)); ?>" class="btn btn-sm btn-outline-secondary">
                <?php esc_html_e('Edit', 'helppress-tickets'); ?>
            </a>
        </div>
    </td>
</tr>