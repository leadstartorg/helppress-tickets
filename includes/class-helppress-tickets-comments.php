<?php
/**
 * Comment Integration for HelpPress Tickets
 *
 * @package HelpPress Tickets
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Tickets Comment class
 *
 * @since 1.0.0
 */
class HelpPress_Tickets_Comments {

    /**
     * Custom ticket reply comment type
     *
     * @var string
     */
    private $ticket_reply_type = 'ticket_reply';

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Register comment hooks
        $this->register_comment_hooks();
    }

    /**
     * Register all hooks related to ticket comments/replies
     *
     * @since 1.0.0
     */
    public function register_comment_hooks() {
        // Register comment hooks
        add_action('comment_post', array($this, 'process_comment_add'), 20, 3);
        
        // Allow comments on tickets, even if they're drafts or pending
        add_action('init', array($this, 'maybe_enable_comments'));
        
        // Remove URL field from comments and add the custom type
        add_filter('comment_form_default_fields', array($this, 'modify_comment_default_fields'));
        
        // Add custom comment type to the form
        add_filter('comment_form_defaults', array($this, 'add_reply_comment_type_to_form'));
        
        // Pre-process comment data to add the reply type
        add_filter('preprocess_comment', array($this, 'pre_process_comment_data'));
        
        // Disable flood check for ticket replies
        add_filter('comment_flood_filter', array($this, 'maybe_deactivate_fast_replies_check'), 10, 3);
        
        // Disable moderation emails for ticket replies
        add_filter('notify_moderator', array($this, 'deactivate_moderation_emails'), 10, 2);

        //Auto approve commentss
        add_filter('pre_comment_approved', array($this, 'helppress_tickets_auto_approve_comments'), 10, 2);

        //Include all comments in query
        //add_action('pre_get_comments', array($this, 'helppress_tickets_include_all_comments_in_query'), 999);

        //force comments open
        add_filter('comments_open', array($this, 'helppress_tickets_force_comments_open'), 999, 2);

        //remove comment urls
        add_filter('comment_form_default_fields', array($this, 'helppress_tickets_remove_url_field'));

        //enable tinymce on textarea for comments/replies
        add_filter('comment_form_defaults', array($this, 'helppress_tickets_enable_tinymce_comments'), 10, 1);

        //include custom comment types for post type hp-ticket
        add_filter('get_comment_type', array($this, 'helppress_tickets_custom_comment_types'));
        add_filter('comment_type_clauses', array($this, 'helppress_tickets_include_custom_comment_types'), 10, 1);

        // Uncomment this line to debug:
        //add_filter('pre_get_comments', array($this, 'helppress_tickets_debug_comment_query'), 9999);

        // Add custom comment box with TinyMCE
        add_action('wp_enqueue_scripts', array($this, 'enqueue_comment_scripts'));

    }

    /**
     * Get custom ticket reply type
     *
     * @since 1.0.0
     * @return string Ticket reply comment type
     */
    public function get_ticket_reply_type() {
        return $this->ticket_reply_type;
    }

    /**
     * Process new comment/reply additions
     *
     * @since 1.0.0
     * @param int    $comment_ID      Comment ID
     * @param int    $comment_approved Comment approved status
     * @param array  $comment_data    Comment data
     */
    public function process_comment_add($comment_ID, $comment_approved, $comment_data) {
        // Nothing to process if we don't have the basic data
        if (empty($comment_ID) || empty($comment_data['comment_post_ID'])) {
            return;
        }

        // Check if this is a ticket post
        if (!$this->is_ticket_resource($comment_data['comment_post_ID'])) {
            return;
        }

        // Create helppress reply data with reply and ticket info
        $helppress_reply_data = array_merge($comment_data, array(
            'reply_id'      => $comment_ID,
            'ticket_id'     => $comment_data['comment_post_ID'],
            'author_id'     => $comment_data['user_id'],
            'author_email'  => '',
        ));

        // Handle author email
        $author_email = '';
        if (!empty($comment_data['comment_author_email'])) {
            $author_email = sanitize_email($comment_data['comment_author_email']);
        } else {
            $maybe_email = get_post_meta($comment_data['comment_post_ID'], '_hp_ticket_email_cc', true);
            if (!empty($maybe_email)) {
                $author_email = $maybe_email;
            }
        }
        $helppress_reply_data['author_email'] = is_email($author_email) ? $author_email : '';

        // Fire action for ticket activity
        do_action('helppress_tickets_action_ticket_activity', $helppress_reply_data);
        
        // Open a closed ticket when a reply is added
        $this->open_closed_ticket($helppress_reply_data);
        
        // Update ticket modified date
        wp_update_post(array(
            'ID'            => $helppress_reply_data['ticket_id'],
            'post_modified' => current_time('mysql'),
        ));
    }

    /**
     * Re-opens a closed ticket when a reply is added
     *
     * @since 1.0.0
     * @param array $helppress_reply_data Reply data
     */
    public function open_closed_ticket(array $helppress_reply_data) {
        if (empty($helppress_reply_data['ticket_id'])) {
            return;
        }

        // Get ticket status
        $status = get_post_status($helppress_reply_data['ticket_id']);
        
        // If ticket is closed or resolved, reopen it
        if ($status == 'hp_closed' || $status == 'hp_resolved') {
            // Get the ticket
            $ticket = get_post($helppress_reply_data['ticket_id']);
            
            // Check if user is logged in or is the ticket author
            if (is_user_logged_in() && 
                (current_user_can('edit_posts') || 
                 $ticket->post_author == get_current_user_id())) {
                
                wp_update_post(array(
                    'ID'          => $helppress_reply_data['ticket_id'],
                    'post_status' => 'hp_open',
                ));
                
                // Add a note about reopening
                $this->add_status_change_comment(
                    $helppress_reply_data['ticket_id'], 
                    $helppress_reply_data['author_id'], 
                    $status, 
                    'hp_open'
                );
            }
        }
    }
    
    /**
     * Add a status change comment
     *
     * @since 1.0.0
     * @param int    $ticket_id  Ticket ID
     * @param int    $user_id    User ID
     * @param string $old_status Old status
     * @param string $new_status New status
     */
    private function add_status_change_comment($ticket_id, $user_id, $old_status, $new_status) {
        $status_labels = array(
            'hp_open'        => __('Open', 'helppress-tickets'),
            'hp_in_progress' => __('In Progress', 'helppress-tickets'),
            'hp_resolved'    => __('Resolved', 'helppress-tickets'),
            'hp_closed'      => __('Closed', 'helppress-tickets'),
            'publish'        => __('Published', 'helppress-tickets'),
            'pending'        => __('Pending Review', 'helppress-tickets'),
        );
        
        $old_status_label = isset($status_labels[$old_status]) ? $status_labels[$old_status] : $old_status;
        $new_status_label = isset($status_labels[$new_status]) ? $status_labels[$new_status] : $new_status;
        
        $user = get_userdata($user_id);
        $username = $user ? $user->display_name : __('System', 'helppress-tickets');
        
        $comment_data = array(
            'comment_post_ID' => $ticket_id,
            'comment_content' => sprintf(
                /* translators: %1$s: old status label, %2$s: new status label, %3$s: username */
                __('Status changed from %1$s to %2$s by %3$s', 'helppress-tickets'),
                $old_status_label,
                $new_status_label,
                $username
            ),
            'user_id' => $user_id,
            'comment_type' => 'ticket_status_change',
            'comment_approved' => 1,
        );
        
        wp_insert_comment($comment_data);
    }

    /**
     * Check if a post is a ticket
     *
     * @since 1.0.0
     * @param int $ticket_id Ticket ID
     * @return bool True if it's a ticket
     */
    public function is_ticket_resource($ticket_id = 0) {
        // Get ticket ID from current post if not specified
        if (empty($ticket_id)) {
            global $post;
            
            if (!$post) {
                return false;
            }
            
            $ticket_id = $post->ID;
        }

        // No ticket id so it's not our comment form
        if (empty($ticket_id)) {
            return false;
        }

        // Check if it's our post type
        if (get_post_type($ticket_id) != 'hp_ticket') {
            return false;
        }

        return true;
    }

    /**
     * Enable comments on drafts and pending tickets
     *
     * @since 1.0.0
     */
    public function maybe_enable_comments() {
        add_filter('comments_open', array($this, 'enable_comments_for_tickets'), 999, 2);
    }

    /**
     * Allows comments on ticket posts, even if they're drafts or pending
     *
     * @since 1.0.0
     * @param bool $open     Whether comments are open
     * @param int  $post_id  Post ID
     * @return bool Modified open status
     */
    public function enable_comments_for_tickets($open, $post_id) {
        if (get_post_type($post_id) == 'hp_ticket') {
            $open = true;
        }

        return $open;
    }

    /**
     * Removes URL field from comments and adds the custom type
     *
     * @since 1.0.0
     * @param array $fields Default comment fields
     * @return array Modified fields
     */
    public function modify_comment_default_fields($fields) {
        if (!$this->is_ticket_resource()) {
            return $fields;
        }

        $comment_type = $this->get_ticket_reply_type();
        $fields['comment_type'] = sprintf('<input type="hidden" name="comment_type" value="%s" id="comment_type" />', 
            esc_attr($comment_type));

        // Remove URL field
        unset($fields['url']);
        
        return $fields;
    }

    /**
     * Adds custom comment type to the form
     *
     * @since 1.0.0
     * @param array $defaults Default comment form settings
     * @return array Modified settings
     */
    public function add_reply_comment_type_to_form($defaults) {
        if (!$this->is_ticket_resource()) {
            return $defaults;
        }

        $comment_type = $this->get_ticket_reply_type();
        $defaults['title_reply_after'] .= sprintf('<input type="hidden" name="comment_type" value="%s" id="comment_type" />', 
            esc_attr($comment_type));
            
        return $defaults;
    }

    /**
     * Pre-processes comment data to add the reply type
     *
     * @since 1.0.0
     * @param array $comment_data Comment data
     * @return array Modified data
     */
    public function pre_process_comment_data($comment_data) {
        if (!$this->is_ticket_resource($comment_data['comment_post_ID'])) {
            return $comment_data;
        }

        $comment_data['comment_type'] = $this->get_ticket_reply_type();
        $comment_data['comment_approved'] = 1; // Auto-approve ticket replies

        return $comment_data;
    }

    /**
     * Disables WordPress's flood check for ticket replies
     *
     * @since 1.0.0
     * @param bool  $block             Whether to block the comment
     * @param int   $time_last_comment Time of last comment
     * @param int   $time_new_comment  Time of new comment
     * @return bool Whether to block the comment
     */
    public function maybe_deactivate_fast_replies_check($block, $time_last_comment, $time_new_comment) {
        if ($this->is_ticket_resource()) {
            return false;
        }

        return $block;
    }

    /**
     * Disables moderation emails for ticket replies
     *
     * @since 1.0.0
     * @param bool $maybe_notify Whether to notify
     * @param int  $comment_id   Comment ID
     * @return bool Whether to notify
     */
    public function deactivate_moderation_emails($maybe_notify, $comment_id) {
        $comment = get_comment($comment_id);

        // This comment is a ticket reply
        if (!empty($comment->comment_post_ID) && $this->is_ticket_resource($comment->comment_post_ID)) {
            return false;
        }

        return $maybe_notify;
    }

    public function auto_approve_ticket_comments($approved, $commentdata) {
        // If it's already approved or not a ticket, leave it alone
        if ($approved === 1 || !isset($commentdata['comment_post_ID'])) {
            return $approved;
        }
        
        // Auto-approve both types of comments on tickets
        if ($this->is_ticket_resource($commentdata['comment_post_ID'])) {
            // Check if it's one of our custom types
            if (isset($commentdata['comment_type']) && 
                in_array($commentdata['comment_type'], ['ticket_reply', 'ticket_status_change'])) {
                return 1; // Approved
            }
        }
        
        return $approved;
    }

    /**
     * Auto-approve comments on tickets
     */
    public function helppress_tickets_auto_approve_comments($approved, $commentdata) {
        if (!empty($commentdata['comment_post_ID'])) {
            $post_type = get_post_type($commentdata['comment_post_ID']);
            if ($post_type === 'hp_ticket') {
                return 1; // Approved
            }
        }
        return $approved;
    }

    /**
     * Ensure WordPress displays custom comment types in comments template
     */
    public function helppress_tickets_include_all_comments_in_query($query) {
        if (is_singular('hp_ticket')) {
            // When viewing a single ticket, include all comment types
            $query->query_vars['type__in'] = ['comment', 'ticket_reply', 'ticket_status_change'];
            
            // Remove any type__not_in restriction that might be present
            unset($query->query_vars['type__not_in']);
        }
        
        return $query;
    }

    /**
     * Force comments open for ticket post type
     */
    public function helppress_tickets_force_comments_open($open, $post_id) {
        if (get_post_type($post_id) === 'hp_ticket') {
            return true;
        }
        return $open;
    }

    /**
     * Remove URL field from comment form on ticket pages
     */
    public function helppress_tickets_remove_url_field($fields) {
        if (is_singular('hp_ticket')) {
            if (isset($fields['url'])) {
                unset($fields['url']);
            }
        }
        return $fields;
    }

    /**
     * Enable TinyMCE editor for comment forms on tickets
     */
    public function helppress_tickets_enable_tinymce_comments($defaults) {
        if (is_singular('hp_ticket')) {
            // Save original field first
            $original_field = isset($defaults['comment_field']) ? $defaults['comment_field'] : '';
            
            // Create new comment field with editor
            ob_start();
            wp_editor('', 'comment', array(
                'media_buttons' => false,
                'textarea_rows' => 8,
                'teeny'         => true,
                'tinymce'       => true,
                'quicktags'     => true,
            ));
            $editor = ob_get_clean();
            
            // Replace the default textarea with the editor
            $defaults['comment_field'] = $editor;
        }
        
        return $defaults;
    }

    /**
     * Add custom comment types to the list of allowed comment types
     */
    /*
    //WordPress is passing as a string not an array, so no good!
    public function helppress_tickets_custom_comment_types($types) {
        $types[] = 'ticket_reply';
        $types[] = 'ticket_status_change';
        return $types;
    }
    */

    /**
     * Add custom comment types to the list of allowed comment types
     */
    public function helppress_tickets_custom_comment_types($type) {
        // Check if $type is one of our custom types
        if ($type === '') {
            return 'ticket_reply'; // Return first custom type if empty
        }
        
        // If it's already our type, just return it
        if ($type === 'ticket_reply' || $type === 'ticket_status_change') {
            return $type;
        }
        
        // Otherwise, return the original type
        return $type;
    }
    /**
     * Include custom comment types in queries and counts
     */
    public function helppress_tickets_include_custom_comment_types($comment_types) {
        // If it's already an array, add our types
        if (is_array($comment_types)) {
            $comment_types[] = 'ticket_reply';
            $comment_types[] = 'ticket_status_change';
        } 
        // If it's a string, convert to array first
        else if (is_string($comment_types)) {
            $comment_types = array($comment_types, 'ticket_reply', 'ticket_status_change');
        }
        
        return $comment_types;
    }

    /**
     * Debug comment queries - add this temporarily if comments still don't show up
     */
    public function helppress_tickets_debug_comment_query($query) {
        if (is_singular('hp_ticket') && current_user_can('manage_options')) {
            //error_log('Comment Query on Ticket: ' . print_r($query->query_vars, true));
        }
        return $query;
    }

    /**
     * Enqueue scripts needed for comment form
     */
    public function enqueue_comment_scripts() {
        if (is_singular('hp_ticket')) {
            // Ensure we load TinyMCE
            wp_enqueue_editor();
            
            // Add our custom script
            wp_enqueue_script(
                'helppress-tickets-comments', 
                HPTICKETS_URL . 'assets/js/helppress-tickets-comments.js', 
                array('jquery'), 
                HPTICKETS_VERSION, 
                true
            );
        }
    }

}

// Initialize the class
new HelpPress_Tickets_Comments();
