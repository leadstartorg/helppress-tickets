<?php
/**
 * Template: Single Ticket
 *
 * @package HelpPress Tickets
 */

get_header();

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
/*
if (current_user_can('administrator')) {
    echo '<pre>';
    echo 'GET: '; print_r($_GET);
    echo 'POST: '; print_r($_POST);
    global $post;
    echo 'POST: '; print_r($post);
    global $wp_query;
    echo 'WP_Query: '; print_r($wp_query);
    echo '</pre>';
    // Don't exit so the rest can process
}
*/
// Check if ticket exists
if (!have_posts()) {
    ?>
    <div class="container my-5">
        <div class="alert alert-danger">
            <?php esc_html_e('Ticket not found.', 'helppress-tickets'); ?>
        </div>
    </div>
    <?php
    get_footer();
    return;
}

// Start the loop
while (have_posts()) : the_post();

    // Get the ticket
    $ticket = get_post();
    $ticket_id = $ticket->ID;
    $status = get_post_status($ticket_id);
    $email_cc = get_post_meta($ticket_id, '_hp_ticket_email_cc', true);
    $phone = get_post_meta($ticket_id, '_hp_ticket_phone', true);
    $is_private = get_post_meta($ticket_id, '_hp_ticket_private', true);
    $due_date = get_post_meta($ticket_id, '_hp_ticket_due_date', true);

    // Get priority
    $priority_terms = wp_get_object_terms($ticket_id, 'hp_ticket_priority');
    $priority = !empty($priority_terms) ? $priority_terms[0]->name : esc_html__('Not set', 'helppress-tickets');

    // Get categories
    $category_terms = wp_get_object_terms($ticket_id, 'hp_category');
    $category = !empty($category_terms) ? $category_terms[0]->name : esc_html__('Uncategorized', 'helppress-tickets');

    // Get tags
    $tag_terms = wp_get_object_terms($ticket_id, 'hp_tag');
    $tags = array();
    if (!empty($tag_terms)) {
        foreach ($tag_terms as $tag) {
            $tags[] = $tag->name;
        }
    }

    // Get status label and class
    $status_class = '';
    $status_label = '';

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
        case 'publish':
            $status_class = 'bg-success';
            $status_label = esc_html__('Published', 'helppress-tickets');
            break;
        case 'pending':
            $status_class = 'bg-warning text-dark';
            $status_label = esc_html__('Pending', 'helppress-tickets');
            break;
        default:
            $status_class = 'bg-secondary';
            $status_label = esc_html($status);
    }

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

    // Get author
    $author_id = get_post_field('post_author', $ticket_id);
    $author = get_userdata($author_id);
    $author_name = $author ? $author->display_name : esc_html__('Unknown', 'helppress-tickets');
    $author_email = $author ? $author->user_email : '';

    // Check if the ticket has been converted to KB article
    $kb_article_id = get_post_meta($ticket_id, '_hp_converted_to_kb_article', true);

    // Check if user has permission to view this ticket
    if ($ticket->post_author != get_current_user_id() && !current_user_can('edit_others_posts')) {
        ?>
        <div class="container my-5">
            <div class="alert alert-danger">
                <?php esc_html_e('You do not have permission to view this ticket.', 'helppress-tickets'); ?>
            </div>
        </div>
        <?php
        get_footer();
        return;
    }

    // Show messages based on URL parameters
    if (isset($_GET['status_changed']) && $_GET['status_changed'] === 'true') {
        ?>
        <div class="container mt-4">
            <div class="alert alert-success">
                <?php esc_html_e('Ticket status has been updated successfully.', 'helppress-tickets'); ?>
            </div>
        </div>
        <?php
    }

    if (isset($_GET['privacy_changed']) && $_GET['privacy_changed'] === 'true') {
        ?>
        <div class="container mt-4">
            <div class="alert alert-success">
                <?php esc_html_e('Ticket privacy setting has been updated successfully.', 'helppress-tickets'); ?>
            </div>
        </div>
        <?php
    }

    if (isset($_GET['replied']) && $_GET['replied'] === 'true') {
        ?>
        <div class="container mt-4">
            <div class="alert alert-success">
                <?php esc_html_e('Your reply has been added successfully.', 'helppress-tickets'); ?>
            </div>
        </div>
        <?php
    }
    ?>

    <div class="container my-5">
        <div class="helppress-tickets helppress-single-ticket">
            <div class="row">
                <div class="col-12">
                    <div class="helppress-tickets-header mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <a href="<?php echo esc_url(helppress_get_ticket_url(0, 'list')); ?>" class="btn btn-outline-secondary btn-sm mb-2">
                                    <i class="dashicons dashicons-arrow-left-alt"></i> <?php esc_html_e('Back to All Tickets', 'helppress-tickets'); ?>
                                </a>
                                <h2 class="mb-1">#<?php echo esc_html($ticket_id); ?> <?php echo esc_html(get_the_title($ticket_id)); ?></h2>
                                <div class="helppress-ticket-meta">
                                    <span class="badge <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_label); ?></span>
                                    <span class="badge <?php echo esc_attr($priority_class); ?>"><?php echo esc_html($priority); ?></span>
                                    <?php if ($is_private) : ?>
                                        <span class="badge bg-dark"><?php esc_html_e('Private', 'helppress-tickets'); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <?php if (is_user_logged_in() && ($ticket->post_author == get_current_user_id() || current_user_can('edit_post', $ticket_id))) : ?>
                                    <div class="btn-group">
                                        <a href="<?php echo esc_url(helppress_get_ticket_url($ticket_id, 'edit', true)); ?>" class="btn btn-outline-primary">
                                            <i class="dashicons dashicons-edit"></i> <?php esc_html_e('Edit', 'helppress-tickets'); ?>
                                        </a>
                                        <?php if ($status === 'hp_open') : ?>
                                            <a href="<?php echo esc_url(admin_url('admin-post.php?action=change_ticket_status&status=hp_in_progress&ticket_id=' . $ticket_id . '&_wpnonce=' . wp_create_nonce('change_ticket_status_' . $ticket_id) . '&redirect=' . urlencode(helppress_get_ticket_url($ticket_id)))); ?>" class="btn btn-outline-primary">
                                                <?php esc_html_e('Mark In Progress', 'helppress-tickets'); ?>
                                            </a>
                                        <?php elseif ($status === 'hp_in_progress') : ?>
                                            <a href="<?php echo esc_url(admin_url('admin-post.php?action=change_ticket_status&status=hp_resolved&ticket_id=' . $ticket_id . '&_wpnonce=' . wp_create_nonce('change_ticket_status_' . $ticket_id) . '&redirect=' . urlencode(helppress_get_ticket_url($ticket_id)))); ?>" class="btn btn-outline-success">
                                                <?php esc_html_e('Mark Resolved', 'helppress-tickets'); ?>
                                            </a>
                                        <?php elseif ($status === 'hp_resolved' || $status === 'hp_closed') : ?>
                                            <a href="<?php echo esc_url(admin_url('admin-post.php?action=change_ticket_status&status=hp_open&ticket_id=' . $ticket_id . '&_wpnonce=' . wp_create_nonce('change_ticket_status_' . $ticket_id) . '&redirect=' . urlencode(helppress_get_ticket_url($ticket_id)))); ?>" class="btn btn-outline-info">
                                                <?php esc_html_e('Reopen', 'helppress-tickets'); ?>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (!$is_private && !$kb_article_id && ($status === 'hp_resolved' || $status === 'hp_closed') && current_user_can('publish_posts')) : ?>
                                            <a href="<?php echo esc_url(admin_url('admin-post.php?action=convert_ticket_to_kb&ticket_id=' . $ticket_id . '&_wpnonce=' . wp_create_nonce('convert_ticket_' . $ticket_id) . '&redirect=' . urlencode(helppress_get_ticket_url($ticket_id)))); ?>" class="btn btn-outline-secondary">
                                                <?php esc_html_e('Convert to KB Article', 'helppress-tickets'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <?php if ($is_private) : ?>
                        <div class="alert alert-info d-flex align-items-center mb-4">
                            <div class="me-2"><i class="dashicons dashicons-lock"></i></div>
                            <div><?php esc_html_e('This ticket is private. Only you and support staff can view this conversation.', 'helppress-tickets'); ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if ($kb_article_id) : ?>
                        <div class="alert alert-success d-flex align-items-center mb-4">
                            <div class="me-2"><i class="dashicons dashicons-admin-site"></i></div>
                            <div>
                                <?php esc_html_e('This ticket has been converted to a Knowledge Base article.', 'helppress-tickets'); ?>
                                <a href="<?php echo esc_url(get_permalink($kb_article_id)); ?>" class="alert-link"><?php esc_html_e('View Article', 'helppress-tickets'); ?></a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="row mb-4">
                <div class="helppress-single-ticket-content col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><?php esc_html_e('Ticket Information', 'helppress-tickets'); ?></h5>
                        </div>
                        <div class="card-body">
                            <?php echo wp_kses_post(wpautop($ticket->post_content)); ?>
                            
                            <?php 
                            // Display attachment if any
                            $attachment_id = get_post_thumbnail_id($ticket_id);
                            if ($attachment_id) :
                                $attachment_url = wp_get_attachment_url($attachment_id);
                                $attachment = get_post($attachment_id);
                                $filetype = wp_check_filetype($attachment_url);
                                $filename = basename($attachment_url);
                            ?>
                                <div class="helppress-ticket-attachment mt-4 p-3 bg-light rounded">
                                    <h6><?php esc_html_e('Attachment:', 'helppress-tickets'); ?></h6>
                                    <?php if (in_array($filetype['ext'], array('jpg', 'jpeg', 'png', 'gif'))) : ?>
                                        <div class="mb-2">
                                            <img src="<?php echo esc_url($attachment_url); ?>" alt="<?php echo esc_attr($filename); ?>" class="img-fluid" style="max-height: 200px;">
                                        </div>
                                    <?php endif; ?>
                                    <a href="<?php echo esc_url($attachment_url); ?>" class="btn btn-sm btn-outline-secondary" download>
                                        <i class="dashicons dashicons-download"></i> <?php echo esc_html($filename); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="helppress-ticket-replies mt-4">
                        <h5 class="mb-3"><?php esc_html_e('Conversation', 'helppress-tickets'); ?></h5>                   
                        <?php                         
                            // Show reply form for open tickets
                            if ($status !== 'hp_closed') {
                                // Custom comment display implementation
                                ?>
                                <div id="comments" class="ticket-comments">
                                    <?php if (get_comments_number() > 0) : ?>
                                        <div class="comment-list p-0">
                                            <?php
                                            $comments = get_comments(array(
                                                'post_id' => $ticket_id,
                                                'status' => 'approve',
                                                'type' => 'ticket_reply',
                                                'order' => 'ASC', 
                                            ));

                                            // Manually sort by date (oldest first)
                                            usort($comments, function($a, $b) {
                                                return strtotime($a->comment_date) - strtotime($b->comment_date);
                                            });
                                                                                        
                                            // Display each comment with custom styling
                                            foreach ($comments as $comment) :
                                                // For status change comments, display differently
                                                if ($comment->comment_type === 'ticket_status_change') {
                                                    ?>
                                                    <div class="alert alert-info mb-3 status-change-notice">
                                                        <i class="dashicons dashicons-info me-2"></i>
                                                        <?php echo wpautop(get_comment_text($comment)); ?>
                                                        <small class="d-block text-muted mt-1">
                                                            <?php echo get_comment_date('M j, Y g:i a', $comment); ?>
                                                        </small>
                                                    </div>
                                                    <?php
                                                    continue; // Skip the rest of the loop for this comment
                                                }
                                                
                                                // Regular ticket reply display
                                                $is_agent = user_can($comment->user_id, 'edit_posts');
                                                $comment_class = $is_agent ? 'helppress-reply-support' : 'helppress-reply-customer';
                                                ?>
                                                <div class="card mb-3 border <?php echo esc_attr($comment_class); ?>" id="comment-<?php echo $comment->comment_ID; ?>">
                                                    <div class="card-header <?php echo $is_agent ? 'bg-primary text-white' : 'bg-light'; ?>">
                                                        <div class="d-flex align-items-center">
                                                            <div class="me-2"><?php echo get_avatar($comment, 48); ?></div>
                                                            <div>
                                                                <strong><?php echo get_comment_author($comment); ?></strong>
                                                                <?php if ($is_agent) : ?>
                                                                    <span class="badge bg-light text-dark ms-2"><?php esc_html_e('Support Agent', 'helppress-tickets'); ?></span>
                                                                <?php endif; ?>
                                                                <div class="helppress-reply-time <?php echo $is_agent ? 'text-white' : 'text-disabled'; ?>">
                                                                    <small><?php echo get_comment_date('M j, Y g:i a', $comment); ?></small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="card-body">
                                                        <?php echo wpautop(get_comment_text($comment)); ?>
                                                        
                                                        <?php
                                                        // Display attachment if any
                                                        $attachment_id = get_comment_meta($comment->comment_ID, '_hp_attachment_id', true);
                                                        if ($attachment_id) {
                                                            $attachment_url = wp_get_attachment_url($attachment_id);
                                                            $filetype = wp_check_filetype(basename($attachment_url));
                                                            $filename = basename($attachment_url);
                                                            ?>
                                                            <div class="helppress-ticket-attachment mt-4 p-3 bg-light rounded">
                                                                <h6><?php esc_html_e('Attachment:', 'helppress-tickets'); ?></h6>
                                                                <?php if (in_array($filetype['ext'], array('jpg', 'jpeg', 'png', 'gif'))) : ?>
                                                                    <div class="mb-2">
                                                                        <img src="<?php echo esc_url($attachment_url); ?>" alt="<?php echo esc_attr($filename); ?>" class="img-fluid" style="max-height: 200px;">
                                                                    </div>
                                                                <?php endif; ?>
                                                                <a href="<?php echo esc_url($attachment_url); ?>" class="btn btn-sm btn-outline-secondary" download>
                                                                    <i class="dashicons dashicons-download"></i> <?php echo esc_html($filename); ?>
                                                                </a>
                                                            </div>
                                                            <?php
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="comment-form-wrapper">
                                        <?php
                                        // Add TinyMCE to comment form
                                        add_filter('comment_form_defaults', function($defaults) {
                                            // Replace default comment field with TinyMCE editor
                                            ob_start();
                                            wp_editor('', 'comment', array(
                                                'media_buttons' => false,
                                                'textarea_name' => 'comment',
                                                'textarea_rows' => 6,
                                                'teeny'         => true,
                                                'quicktags'     => true,
                                                'tinymce'       => true,
                                            ));
                                            $editor = ob_get_clean();
                                            
                                            $defaults['comment_field'] = '<p class="comment-form-comment">' . 
                                                                        '<label for="comment">' . __('', 'helppress-tickets') . '</label>' . 
                                                                        $editor . '</p>';
                                            
                                            // Simplify the form
                                            $defaults['comment_notes_before'] = '';
                                            $defaults['title_reply'] = __('Add Reply', 'helppress-tickets');
                                            $defaults['class_submit'] = 'btn btn-primary mt-4';
                                            
                                            // Set comment type to ticket_reply
                                            $defaults['comment_type'] = 'ticket_reply';
                                            
                                            return $defaults;
                                        });
                                        
                                        comment_form();
                                        ?>
                                    </div>
                                </div>
                                <?php
                            } else {
                                ?>
                                <div class="alert alert-secondary mt-4">
                                    <?php esc_html_e('This ticket is closed. Contact support if you need further assistance.', 'helppress-tickets'); ?>
                                </div>
                                <?php
                            }
                        
                        ?>
                    </div>
                </div>
                
                <div class="helppress-single-ticket-aside col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><?php esc_html_e('Ticket Details', 'helppress-tickets'); ?></h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('Ticket ID:', 'helppress-tickets'); ?></th>
                                        <td>#<?php echo esc_html($ticket_id); ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('Status:', 'helppress-tickets'); ?></th>
                                        <td><span class="badge <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_label); ?></span></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('Priority:', 'helppress-tickets'); ?></th>
                                        <td><span class="badge <?php echo esc_attr($priority_class); ?>"><?php echo esc_html($priority); ?></span></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('Category:', 'helppress-tickets'); ?></th>
                                        <td><?php echo esc_html($category); ?></td>
                                    </tr>
                                    <?php if (!empty($tags)) : ?>
                                        <tr>
                                            <th scope="row"><?php esc_html_e('Tags:', 'helppress-tickets'); ?></th>
                                            <td>
                                                <?php foreach ($tags as $tag) : ?>
                                                    <span class="badge bg-secondary"><?php echo esc_html($tag); ?></span>
                                                <?php endforeach; ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('Created:', 'helppress-tickets'); ?></th>
                                        <td><?php echo esc_html(get_the_date('M j, Y g:i a', $ticket_id)); ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('Last Updated:', 'helppress-tickets'); ?></th>
                                        <td><?php echo esc_html(get_the_modified_date('M j, Y g:i a', $ticket_id)); ?></td>
                                    </tr>
                                    <?php if ($due_date) : ?>
                                        <tr>
                                            <th scope="row"><?php esc_html_e('Due Date:', 'helppress-tickets'); ?></th>
                                            <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($due_date))); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><?php esc_html_e('Requester Information', 'helppress-tickets'); ?></h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('Name:', 'helppress-tickets'); ?></th>
                                        <td><?php echo esc_html($author_name); ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('Email:', 'helppress-tickets'); ?></th>
                                        <td><?php echo esc_html($author_email); ?></td>
                                    </tr>
                                    <?php if ($email_cc) : ?>
                                        <tr>
                                            <th scope="row"><?php esc_html_e('CC Email:', 'helppress-tickets'); ?></th>
                                            <td><?php echo esc_html($email_cc); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php if ($phone) : ?>
                                        <tr>
                                            <th scope="row"><?php esc_html_e('Phone:', 'helppress-tickets'); ?></th>
                                            <td><?php echo esc_html($phone); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <?php if (is_user_logged_in() && ($ticket->post_author == get_current_user_id() || current_user_can('edit_post', $ticket_id))) : ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><?php esc_html_e('User Actions', 'helppress-tickets'); ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <?php if ($status !== 'hp_closed') : ?>
                                        <a href="<?php echo esc_url(admin_url('admin-post.php?action=change_ticket_status&status=hp_closed&ticket_id=' . $ticket_id . '&_wpnonce=' . wp_create_nonce('change_ticket_status_' . $ticket_id) . '&redirect=' . urlencode(helppress_get_ticket_url($ticket_id)))); ?>" class="btn btn-outline-secondary">
                                            <?php esc_html_e('Close Ticket', 'helppress-tickets'); ?>
                                        </a>
                                    <?php else : ?>
                                        <a href="<?php echo esc_url(admin_url('admin-post.php?action=change_ticket_status&status=hp_open&ticket_id=' . $ticket_id . '&_wpnonce=' . wp_create_nonce('change_ticket_status_' . $ticket_id) . '&redirect=' . urlencode(helppress_get_ticket_url($ticket_id)))); ?>" class="btn btn-outline-secondary">
                                            <?php esc_html_e('Reopen Ticket', 'helppress-tickets'); ?>
                                        </a>
                                    <?php endif; ?>
                                    <?php if(current_user_can('manage_options')): ?>
                                        <?php if (!$is_private) : ?>
                                            <a href="<?php echo esc_url(admin_url('admin-post.php?action=toggle_ticket_privacy&ticket_id=' . $ticket_id . '&_wpnonce=' . wp_create_nonce('toggle_ticket_privacy_' . $ticket_id) . '&redirect=' . urlencode(helppress_get_ticket_url($ticket_id)))); ?>" class="btn btn-outline-secondary">
                                                <?php esc_html_e('Make Private', 'helppress-tickets'); ?>
                                            </a>
                                        <?php else : ?>
                                            <a href="<?php echo esc_url(admin_url('admin-post.php?action=toggle_ticket_privacy&ticket_id=' . $ticket_id . '&_wpnonce=' . wp_create_nonce('toggle_ticket_privacy_' . $ticket_id) . '&redirect=' . urlencode(helppress_get_ticket_url($ticket_id)))); ?>" class="btn btn-outline-secondary">
                                                <?php esc_html_e('Make Public', 'helppress-tickets'); ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<?php endwhile; ?>

<?php get_footer(); ?>