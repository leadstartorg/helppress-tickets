<?php
/**
 * Comment Attachments
 * 
 * @package HelpPress Tickets
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Comment Attachments class
 */
class HelpPress_Tickets_Comment_Attachments {

    /**
     * Constructor
     */
    public function __construct() {
        // Only enable for ticket posts
        add_action('wp', array($this, 'init_comment_attachments'));
    }
    
    /**
     * Initialize comment attachments
     */
    public function init_comment_attachments() {
        // Only for ticket posts
        if (!is_singular('hp_ticket')) {
            return;
        }
        
        // Add form enctype
        add_filter('comment_form_defaults', array($this, 'modify_comment_form'));
        
        // Add attachment field
        add_action('comment_form_logged_in_after', array($this, 'add_attachment_field'));
        add_action('comment_form_after_fields', array($this, 'add_attachment_field'));
        
        // Process and save attachments
        add_filter('preprocess_comment', array($this, 'validate_comment_attachment'));
        add_action('comment_post', array($this, 'save_comment_attachment'));
    }
    
    /**
     * Modify comment form to include enctype
     */
    public function modify_comment_form($defaults) {
        $defaults['comment_form_before'] = '<form id="commentform" class="comment-form" method="post" enctype="multipart/form-data">';
        return $defaults;
    }
    
    /**
     * Add attachment field to comment form
     */
    public function add_attachment_field() {
        ?>
        <p class="comment-form-attachment">
            <label for="comment-attachment"><?php esc_html_e('Attachment (optional)', 'helppress-tickets'); ?></label>
            <input type="file" name="comment-attachment" id="comment-attachment" class="form-control" />
            <small class="form-text text-muted">
                <?php 
                esc_html_e('Max file size: 1MB. Allowed formats: jpg, jpeg, png, pdf, zip', 'helppress-tickets'); 
                ?>
            </small>
        </p>
        <?php
    }
    
    /**
     * Validate comment attachment
     */
    public function validate_comment_attachment($commentdata) {
        // Check if file was uploaded
        if (!isset($_FILES['comment-attachment']) || !isset($_FILES['comment-attachment']['name']) || empty($_FILES['comment-attachment']['name'])) {
            return $commentdata;
        }
        
        // Check file size (1MB limit)
        if ($_FILES['comment-attachment']['size'] > 1048576) {
            wp_die(esc_html__('File is too large. Maximum size is 1MB.', 'helppress-tickets'));
        }
        
        // Check file type
        $file_info = wp_check_filetype($_FILES['comment-attachment']['name']);
        $allowed_types = array('jpg', 'jpeg', 'png', 'pdf', 'zip');
        
        if (!in_array($file_info['ext'], $allowed_types)) {
            wp_die(esc_html__('Invalid file type. Allowed types: jpg, jpeg, png, pdf, zip.', 'helppress-tickets'));
        }
        
        return $commentdata;
    }
    
    /**
     * Save comment attachment
     */
    public function save_comment_attachment($comment_id) {
        // Check if file was uploaded
        if (!isset($_FILES['comment-attachment']) || !isset($_FILES['comment-attachment']['name']) || empty($_FILES['comment-attachment']['name'])) {
            return;
        }
        
        // Include required files
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        // Upload the file and save as attachment
        $attachment_id = media_handle_upload('comment-attachment', get_comment($comment_id)->comment_post_ID);
        
        if (!is_wp_error($attachment_id)) {
            // Save attachment ID in comment meta
            add_comment_meta($comment_id, '_hp_attachment_id', $attachment_id);
            
            // Log success
            //error_log('HelpPress Tickets: Attachment added to comment ' . $comment_id . ', ID: ' . $attachment_id);
        } else {
            // Log error
            //error_log('HelpPress Tickets: Error adding attachment to comment ' . $comment_id . ': ' . $attachment_id->get_error_message());
        }
    }
}

// Initialize the class
new HelpPress_Tickets_Comment_Attachments();