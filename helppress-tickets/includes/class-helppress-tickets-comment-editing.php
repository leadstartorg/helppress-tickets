<?php
/**
 * Comment Editing
 *
 * @package HelpPress Tickets
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Comment Editing class
 */
class HelpPress_Tickets_Comment_Editing {

    /**
     * Constructor
     */
    public function __construct() {
        // Add editing scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_helppress_get_comment_form', array($this, 'ajax_get_comment_form'));
        add_action('wp_ajax_helppress_save_comment', array($this, 'ajax_save_comment'));
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        if (is_singular('hp_ticket')) {
            wp_enqueue_script(
                'helppress-comment-edit', 
                HPTICKETS_URL . 'assets/js/helppress-comment-edit.js', 
                array('jquery'), 
                HPTICKETS_VERSION, 
                true
            );
            
            wp_localize_script('helppress-comment-edit', 'helppressComments', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('helppress_edit_comment'),
                'edit_text' => esc_html__('Edit', 'helppress-tickets'),
                'save_text' => esc_html__('Save', 'helppress-tickets'),
                'cancel_text' => esc_html__('Cancel', 'helppress-tickets')
            ));
        }
    }
    
    /**
     * AJAX: Get comment editing form
     */
    public function ajax_get_comment_form() {
        // Check nonce
        check_ajax_referer('helppress_edit_comment', 'nonce');
        
        // Get comment ID
        $comment_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
        
        // Get comment
        $comment = get_comment($comment_id);
        
        // Check if user can edit this comment
        if (!$comment || !(current_user_can('edit_comment', $comment_id) || $comment->user_id == get_current_user_id())) {
            wp_send_json_error(array('message' => __('You do not have permission to edit this comment.', 'helppress-tickets')));
        }
        
        // Get comment content
        $content = $comment->comment_content;
        
        // Output editor
        ob_start();
        wp_editor($content, 'edit_comment_' . $comment_id, array(
            'media_buttons' => false,
            'textarea_name' => 'edit_comment_content',
            'textarea_rows' => 8,
            'teeny' => true,
            'quicktags' => true,
            'tinymce' => true
        ));
        $editor = ob_get_clean();
        
        wp_send_json_success(array(
            'editor' => $editor,
            'content' => $content
        ));
    }
    
    /**
     * AJAX: Save edited comment
     */
    public function ajax_save_comment() {
        // Check nonce
        check_ajax_referer('helppress_edit_comment', 'nonce');
        
        // Get comment ID and content
        $comment_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
        $content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
        
        // Get comment
        $comment = get_comment($comment_id);
        
        // Check if user can edit this comment
        if (!$comment || !(current_user_can('edit_comment', $comment_id) || $comment->user_id == get_current_user_id())) {
            wp_send_json_error(array('message' => __('You do not have permission to edit this comment.', 'helppress-tickets')));
        }
        
        // Update comment
        $result = wp_update_comment(array(
            'comment_ID' => $comment_id,
            'comment_content' => $content
        ));
        
        if ($result) {
            // Get updated comment
            $updated_comment = get_comment($comment_id);
            
            wp_send_json_success(array(
                'message' => __('Comment updated successfully.', 'helppress-tickets'),
                'content' => apply_filters('comment_text', $updated_comment->comment_content, $updated_comment)
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to update comment.', 'helppress-tickets')));
        }
    }
}

// Initialize the class
new HelpPress_Tickets_Comment_Editing();