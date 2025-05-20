<?php
/**
 * Template: Edit Ticket Form
 *
 * @package HelpPress Tickets
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check if we have a ticket
if (empty($ticket)) {
    ?>
    <div class="alert alert-danger">
        <?php esc_html_e('Invalid ticket specified for editing.', 'helppress-tickets'); ?>
    </div>
    <?php
    return;
}

// Get ticket metadata
$ticket_id = $ticket->ID;
$email_cc = get_post_meta($ticket_id, '_hp_ticket_email_cc', true);
$phone = get_post_meta($ticket_id, '_hp_ticket_phone', true);
$is_private = get_post_meta($ticket_id, '_hp_ticket_private', true);

// Get priority
$priority_terms = wp_get_object_terms($ticket_id, 'hp_ticket_priority');
$selected_priority = !empty($priority_terms) ? $priority_terms[0]->term_id : 0;

// Get categories
$category_terms = wp_get_object_terms($ticket_id, 'hp_category');
$selected_category = !empty($category_terms) ? $category_terms[0]->term_id : 0;

// Get tags
$tag_terms = wp_get_object_terms($ticket_id, 'hp_tag');
$tags = array();
if (!empty($tag_terms)) {
    foreach ($tag_terms as $tag) {
        $tags[] = $tag->name;
    }
}
$tags_string = implode(', ', $tags);

// Get user data
$user_data = HelpPress_Tickets_User::get_user_data();
?>

<div class="helppress-tickets helppress-tickets-form">
    <h2><?php esc_html_e('Edit Ticket', 'helppress-tickets'); ?></h2>
    
    <form class="helppress-tickets-edit-form" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('helppress_update_ticket', 'helppress_ticket_update_nonce'); ?>
        <input type="hidden" name="ticket_id" value="<?php echo esc_attr($ticket_id); ?>">
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="ticket_first_name" class="form-label"><?php esc_html_e('First Name', 'helppress-tickets'); ?></label>
                <input type="text" class="form-control" id="ticket_first_name" name="ticket_first_name" value="<?php echo esc_attr($user_data['first_name']); ?>" readonly>
            </div>
            
            <div class="col-md-6 mb-3">
                <label for="ticket_last_name" class="form-label"><?php esc_html_e('Last Name', 'helppress-tickets'); ?></label>
                <input type="text" class="form-control" id="ticket_last_name" name="ticket_last_name" value="<?php echo esc_attr($user_data['last_name']); ?>" readonly>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="ticket_email" class="form-label"><?php esc_html_e('Email', 'helppress-tickets'); ?></label>
                <input type="email" class="form-control" id="ticket_email" name="ticket_email" value="<?php echo esc_attr($user_data['email']); ?>" readonly>
                <div class="form-text"><?php esc_html_e('This is your registered email address', 'helppress-tickets'); ?></div>
            </div>
            
            <div class="col-md-6 mb-3">
                <label for="ticket_email_cc" class="form-label"><?php esc_html_e('CC Email', 'helppress-tickets'); ?></label>
                <input type="email" class="form-control" id="ticket_email_cc" name="ticket_email_cc" value="<?php echo esc_attr($email_cc); ?>" placeholder="<?php esc_attr_e('Additional email to receive updates', 'helppress-tickets'); ?>">
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12 mb-3">
                <label for="ticket_subject" class="form-label"><?php esc_html_e('Subject', 'helppress-tickets'); ?></label>
                <input type="text" class="form-control" id="ticket_subject" name="ticket_subject" value="<?php echo esc_attr($ticket->post_title); ?>" required>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12 mb-4">
                <label for="ticket_message" class="form-label"><?php esc_html_e('Message', 'helppress-tickets'); ?></label>
                <?php
                // Use WordPress editor
                wp_editor($ticket->post_content, 'ticket_message', array(
                    'textarea_name' => 'ticket_message',
                    'textarea_rows' => 10,
                    'media_buttons' => false,
                    'teeny' => true,
                    'quicktags' => true,
                ));
                ?>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="ticket_category" class="form-label"><?php esc_html_e('Category', 'helppress-tickets'); ?></label>
                <?php
                // Display the selected category name instead of dropdown
                $category_name = !empty($category_terms) ? $category_terms[0]->name : esc_html__('Uncategorized', 'helppress-tickets');
                ?>
                <input type="text" class="form-control" value="<?php echo esc_attr($category_name); ?>" readonly disabled>
                <div class="form-text"><?php esc_html_e('Category cannot be modified after ticket creation', 'helppress-tickets'); ?></div>
                
                <!-- Hidden field to preserve the existing category -->
                <input type="hidden" name="original_category" value="<?php echo !empty($category_terms) ? esc_attr($category_terms[0]->term_id) : ''; ?>">
            </div>
            
            <div class="col-md-6 mb-3">
                <label for="ticket_tags" class="form-label"><?php esc_html_e('Tags', 'helppress-tickets'); ?></label>
                <input type="text" class="form-control" id="ticket_tags" name="ticket_tags" value="<?php echo esc_attr($tags_string); ?>" readonly disabled>
                <div class="form-text"><?php esc_html_e('Tags cannot be modified after ticket creation', 'helppress-tickets'); ?></div>
                
                <!-- Add a hidden field to preserve existing tags -->
                <input type="hidden" name="original_tags" value="<?php echo esc_attr($tags_string); ?>">
            </div>

        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="ticket_phone" class="form-label"><?php esc_html_e('Phone', 'helppress-tickets'); ?></label>
                <input type="tel" class="form-control" id="ticket_phone" name="ticket_phone" value="<?php echo esc_attr($phone); ?>">
            </div>
            
            <div class="col-md-6 mb-3">
                <label for="ticket_priority" class="form-label"><?php esc_html_e('Priority', 'helppress-tickets'); ?></label>
                <select class="form-select" id="ticket_priority" name="ticket_priority">
                    <option value=""><?php esc_html_e('Select priority', 'helppress-tickets'); ?></option>
                    <?php
                    $priorities = get_terms(array(
                        'taxonomy' => 'hp_ticket_priority',
                        'hide_empty' => false,
                    ));
                    
                    if (!empty($priorities) && !is_wp_error($priorities)) {
                        foreach ($priorities as $priority) {
                            printf(
                                '<option value="%s" %s>%s</option>',
                                esc_attr($priority->term_id),
                                selected($selected_priority, $priority->term_id, false),
                                esc_html($priority->name)
                            );
                        }
                    }
                    ?>
                </select>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="ticket_attachment" class="form-label"><?php esc_html_e('New Attachment', 'helppress-tickets'); ?></label>
                <input type="file" class="form-control" id="ticket_attachment" name="ticket_attachment">
                <div class="form-text"><?php esc_html_e('Max file size: 1MB. Allowed formats: jpg, jpeg, png, pdf, zip', 'helppress-tickets'); ?></div>
                
                <?php 
                // Display existing attachment if any
                $attachment_id = get_post_thumbnail_id($ticket_id);
                if ($attachment_id) {
                    $attachment_url = wp_get_attachment_url($attachment_id);
                    $filename = basename($attachment_url);
                    ?>
                    <div class="mt-2">
                        <p><?php esc_html_e('Current attachment:', 'helppress-tickets'); ?></p>
                        <a href="<?php echo esc_url($attachment_url); ?>" class="btn btn-sm btn-outline-secondary" target="_blank">
                            <i class="dashicons dashicons-download"></i> <?php echo esc_html($filename); ?>
                        </a>
                    </div>
                    <?php
                }
                ?>
            </div>
            
            <div class="col-md-6 mb-3">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" id="ticket_private" name="ticket_private" value="1" <?php checked($is_private, '1'); ?>>
                    <label class="form-check-label" for="ticket_private">
                        <?php esc_html_e('Make this ticket private (will not be converted to knowledgebase article)', 'helppress-tickets'); ?>
                    </label>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <input type="hidden" name="redirect" value="<?php echo isset($atts['redirect']) ? esc_attr($atts['redirect']) : ''; ?>">
                <button type="submit" name="helppress_update_ticket" class="btn btn-primary"><?php esc_html_e('Update Ticket', 'helppress-tickets'); ?></button>
                <a href="<?php echo esc_url(get_permalink($ticket_id)); ?>" class="btn btn-outline-secondary ms-2"><?php esc_html_e('Cancel', 'helppress-tickets'); ?></a>
            </div>
        </div>
    </form>
</div>