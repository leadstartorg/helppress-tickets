<?php
/**
 * Admin actions for HelpPress Tickets
 *
 * @package HelpPress Tickets
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin Actions class
 *
 * @since 1.0.0
 */
class HelpPress_Tickets_Admin_Actions {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Change ticket status action
        add_action( 'admin_post_change_ticket_status', array( $this, 'change_ticket_status' ) );
        
        // Toggle ticket privacy action
        add_action( 'admin_post_toggle_ticket_privacy', array( $this, 'toggle_ticket_privacy' ) );
        
        // Add ticket reply action
        add_action( 'admin_post_helppress_add_ticket_reply', array( $this, 'add_ticket_reply' ) );
        
        // Register meta boxes for tickets
        add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
        
        // Save ticket status on post save
        add_action( 'save_post_hp_ticket', array( $this, 'save_ticket_status' ), 10, 3 );
    }
    
    /**
     * Register meta boxes for tickets
     *
     * @since 1.0.0
     */
    public function register_meta_boxes() {
        add_meta_box(
            'hp_ticket_status',
            esc_html__( 'Ticket Status', 'helppress-tickets' ),
            array( $this, 'render_status_meta_box' ),
            'hp_ticket',
            'side',
            'high'
        );
    }
    
    /**
     * Render ticket status meta box
     *
     * @since 1.0.0
     * @param WP_Post $post Post object
     */
    public function render_status_meta_box( $post ) {
        $current_status = get_post_status( $post->ID );
        
        // If it's a new post, default to open status
        if ( $post->post_status === 'auto-draft' ) {
            $current_status = 'hp_open';
        }
        
        wp_nonce_field( 'hp_ticket_status_nonce', 'hp_ticket_status_nonce' );
        ?>
        <div class="components-panel__row">
            <div class="components-base-control">
                <div class="components-base-control__field">
                    <label class="components-base-control__label" for="hp_ticket_status">
                        <?php esc_html_e( 'Ticket Status', 'helppress-tickets' ); ?>
                    </label>
                    <select id="hp_ticket_status" name="hp_ticket_status" class="components-select-control__input">
                        <option value="hp_open" <?php selected( $current_status, 'hp_open' ); ?>><?php esc_html_e( 'Open', 'helppress-tickets' ); ?></option>
                        <option value="hp_in_progress" <?php selected( $current_status, 'hp_in_progress' ); ?>><?php esc_html_e( 'In Progress', 'helppress-tickets' ); ?></option>
                        <option value="hp_resolved" <?php selected( $current_status, 'hp_resolved' ); ?>><?php esc_html_e( 'Resolved', 'helppress-tickets' ); ?></option>
                        <option value="hp_closed" <?php selected( $current_status, 'hp_closed' ); ?>><?php esc_html_e( 'Closed', 'helppress-tickets' ); ?></option>
                        <option value="pending" <?php selected( $current_status, 'pending' ); ?>><?php esc_html_e( 'Pending Review', 'helppress-tickets' ); ?></option>
                    </select>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save ticket status when post is saved
     *
     * @since 1.0.0
     * @param int     $post_id Post ID
     * @param WP_Post $post    Post object
     * @param bool    $update  Whether this is an update
     */
    public function save_ticket_status( $post_id, $post, $update ) {
        // Skip if this is an autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        
        // Skip if this is a revision
        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }
        
        // Check permission
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        
        // Verify nonce
        if ( ! isset( $_POST['hp_ticket_status_nonce'] ) || ! wp_verify_nonce( 
            sanitize_text_field( wp_unslash( $_POST['hp_ticket_status_nonce'] ) ), 
            'hp_ticket_status_nonce' 
        ) ) {
            return;
        }
        
        // Set the ticket status if provided
        if ( isset( $_POST['hp_ticket_status'] ) ) {
            $new_status = sanitize_text_field( wp_unslash( $_POST['hp_ticket_status'] ) );
            
            // Only update if different from current status
            if ( $new_status !== get_post_status( $post_id ) ) {
                // Don't use wp_update_post as it will cause infinite loop
                global $wpdb;
                $wpdb->update(
                    $wpdb->posts, 
                    array( 'post_status' => $new_status ), 
                    array( 'ID' => $post_id )
                );
                
                // Update status change time
                update_post_meta( $post_id, '_hp_status_changed', current_time( 'mysql' ) );
                
                // Log status change
                $this->log_status_change( $post_id, get_post_status( $post_id ), $new_status );
                
                // Clean cache
                clean_post_cache( $post_id );
            }
        }
    }
    
    /**
     * Change ticket status
     *
     * @since 1.0.0
     */
    public function change_ticket_status() {
        // Check admin referer
        if (!isset($_GET['_wpnonce']) || !isset($_GET['ticket_id']) || !isset($_GET['status'])) {
            wp_die(esc_html__('Invalid request.', 'helppress-tickets'));
        }
        
        $ticket_id = intval($_GET['ticket_id']);
        $status = sanitize_text_field(wp_unslash($_GET['status']));
        
        // Verify nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'change_ticket_status_' . $ticket_id)) {
            wp_die(esc_html__('Security check failed.', 'helppress-tickets'));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_die(esc_html__('You must be logged in to perform this action.', 'helppress-tickets'));
        }
        
        // Get the ticket
        $ticket = get_post($ticket_id);
        
        // Check if ticket exists and is a ticket post type
        if (!$ticket || $ticket->post_type !== 'hp_ticket') {
            wp_die(esc_html__('Invalid ticket.', 'helppress-tickets'));
        }
        
        // Check permissions - user must be either the ticket author or have edit_post capability
        if ($ticket->post_author != get_current_user_id() && !current_user_can('edit_post', $ticket_id)) {
            wp_die(esc_html__('You do not have permission to change the status of this ticket.', 'helppress-tickets'));
        }
        
        // Check valid status
        $valid_statuses = array('hp_open', 'hp_in_progress', 'hp_resolved', 'hp_closed');
        if (!in_array($status, $valid_statuses, true)) {
            wp_die(esc_html__('Invalid status.', 'helppress-tickets'));
        }
        
        // Get current status
        $current_status = get_post_status($ticket_id);
        
        // Set new status
        $result = wp_update_post(array(
            'ID' => $ticket_id,
            'post_status' => $status
        ));
        
        if (is_wp_error($result)) {
            wp_die($result->get_error_message());
        }
        
        // Update status change time
        update_post_meta($ticket_id, '_hp_status_changed', current_time('mysql'));
        
        // Log status change
        $this->log_status_change($ticket_id, $current_status, $status);
        
        // Redirect back to ticket
        $redirect_url = isset($_GET['redirect']) ? esc_url_raw(wp_unslash($_GET['redirect'])) : helppress_get_ticket_url($ticket_id);

        // Add status changed message
        $redirect_url = add_query_arg('status_changed', 'true', $redirect_url);
        
        wp_safe_redirect($redirect_url);
        exit;
    }
    
    /**
     * Toggle ticket privacy
     *
     * @since 1.0.0
     */
    public function toggle_ticket_privacy() {
        // Check admin referer
        if ( ! isset( $_GET['_wpnonce'] ) || ! isset( $_GET['ticket_id'] ) ) {
            wp_die( esc_html__( 'Invalid request.', 'helppress-tickets' ) );
        }
        
        $ticket_id = intval( $_GET['ticket_id'] );
        
        // Verify nonce
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'toggle_ticket_privacy_' . $ticket_id ) ) {
            wp_die( esc_html__( 'Security check failed.', 'helppress-tickets' ) );
        }
        
        // Check permission
        if ( ! current_user_can( 'edit_post', $ticket_id ) ) {
            wp_die( esc_html__( 'You do not have permission to do this.', 'helppress-tickets' ) );
        }
        
        // Toggle private status
        $is_private = get_post_meta( $ticket_id, '_hp_ticket_private', true );
        
        update_post_meta( $ticket_id, '_hp_ticket_private', $is_private ? 0 : 1 );
        
        // Redirect back to ticket
        //$redirect_url = isset( $_GET['redirect'] ) ? esc_url_raw( wp_unslash( $_GET['redirect'] ) ) : add_query_arg( 'ticket_id', $ticket_id, wp_get_referer() );
        
        // In change_ticket_status() method
        $redirect_url = isset($_GET['redirect']) ? esc_url_raw(wp_unslash($_GET['redirect'])) : helppress_get_ticket_url($ticket_id);

        // Add privacy changed message
        $redirect_url = add_query_arg( 'privacy_changed', 'true', $redirect_url );
        
        wp_safe_redirect( $redirect_url );
        exit;
    }
    
    /**
     * Add ticket reply
     *
     * @since 1.0.0
     */
    public function add_ticket_reply() {
        // Debug logging
        //error_log('HelpPress Admin Actions: add_ticket_reply called');
        
        // Check if form was submitted
        if (!isset($_POST['helppress_reply_nonce']) || !isset($_POST['ticket_id']) || !isset($_POST['ticket_reply'])) {
            //error_log('HelpPress Admin Actions: Missing required fields');
            wp_die(esc_html__('Invalid request.', 'helppress-tickets'));
        }
        
        // Verify nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['helppress_reply_nonce'])), 'helppress_add_ticket_reply')) {
            //error_log('HelpPress Admin Actions: Nonce verification failed');
            wp_die(esc_html__('Security check failed.', 'helppress-tickets'));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_die(esc_html__('You must be logged in to reply to a ticket.', 'helppress-tickets'));
        }
        
        $ticket_id = intval($_POST['ticket_id']);
        $reply = wp_kses_post(wp_unslash($_POST['ticket_reply']));
        
        // Validate ticket
        $ticket = get_post($ticket_id);
        
        if (!$ticket || $ticket->post_type !== 'hp_ticket') {
            //error_log('HelpPress Admin Actions: Invalid ticket ' . $ticket_id);
            wp_die(esc_html__('Invalid ticket.', 'helppress-tickets'));
        }
        
        // Check if user has permission to reply (must be author or have edit capability)
        if (!current_user_can('edit_posts') && $ticket->post_author != get_current_user_id()) {
            wp_die(esc_html__('You do not have permission to reply to this ticket.', 'helppress-tickets'));
        }
        
        // Validate reply
        if (empty($reply)) {
            wp_die(esc_html__('Please enter a reply.', 'helppress-tickets'));
        }
        
        // Create the comment - using ticket_reply comment type instead of 'comment'
        $comment_data = array(
            'comment_post_ID' => $ticket_id,
            'comment_content' => $reply,
            'user_id' => get_current_user_id(),
            'comment_type' => 'ticket_reply', // Using custom type
            'comment_approved' => 1,  // Auto-approve 
        );
        
        $comment_id = wp_insert_comment($comment_data);
        
        if (!$comment_id) {
            //error_log('HelpPress Admin Actions: Failed to add reply - wp_insert_comment returned ' . $comment_id);
            wp_die(esc_html__('Failed to add reply. Please try again.', 'helppress-tickets'));
        }
        
        //error_log('HelpPress Admin Actions: Comment added successfully, ID: ' . $comment_id);
        
        // Handle file upload if provided
        if (!empty($_FILES['reply_attachment']['name'])) {
            $uploaded_file = $_FILES['reply_attachment'];
            
            if ($uploaded_file['error'] == 0) {
                // Include WordPress file handling
                if (!function_exists('wp_handle_upload')) {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                }
                
                $upload_overrides = array('test_form' => false);
                $movefile = wp_handle_upload($uploaded_file, $upload_overrides);
                
                if ($movefile && !isset($movefile['error'])) {
                    // Prepare file info for attachment
                    $wp_filetype = wp_check_filetype(basename($movefile['file']), null);
                    $attachment = array(
                        'post_mime_type' => $wp_filetype['type'],
                        'post_title'     => preg_replace('/\.[^.]+$/', '', basename($movefile['file'])),
                        'post_content'   => '',
                        'post_status'    => 'inherit'
                    );
                    
                    // Insert attachment into media library
                    $attach_id = wp_insert_attachment($attachment, $movefile['file'], $ticket_id);
                    
                    // Include image handling functions
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    
                    // Generate attachment metadata and update
                    $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
                    wp_update_attachment_metadata($attach_id, $attach_data);
                    
                    // Link attachment to the comment
                    update_comment_meta($comment_id, '_hp_attachment_id', $attach_id);
                    
                    //error_log('HelpPress Admin Actions: Attachment added to reply, ID: ' . $attach_id);
                }
            }
        }
        
        // Update ticket status if requested
        if (isset($_POST['mark_resolved']) && $_POST['mark_resolved']) {
            $current_status = get_post_status($ticket_id);
            
            // Check if user can change status (either owner or admin)
            if ($ticket->post_author == get_current_user_id() || current_user_can('edit_post', $ticket_id)) {
                wp_update_post(array(
                    'ID' => $ticket_id,
                    'post_status' => 'hp_resolved'
                ));
                
                // Update status change time
                update_post_meta($ticket_id, '_hp_status_changed', current_time('mysql'));
                
                // Log status change
                $this->log_status_change($ticket_id, $current_status, 'hp_resolved');
                
                //error_log('HelpPress Admin Actions: Ticket status changed to resolved');
            }
        } else {
            // Always update the post modified date when a new reply is added
            wp_update_post(array(
                'ID' => $ticket_id,
                'post_modified' => current_time('mysql'),
                'post_modified_gmt' => current_time('mysql', true)
            ));
        }
        
        // Send notification email if needed
        $this->send_notification_email($ticket_id, $comment_id);
        
        // Redirect back to ticket
        $redirect_url = helppress_get_ticket_url($ticket_id);
        
        // Add replied message
        $redirect_url = add_query_arg('replied', 'true', $redirect_url);
        
        //error_log('HelpPress Admin Actions: Redirecting to ' . $redirect_url);
        
        wp_safe_redirect($redirect_url);
        exit;
    }
    
    /**
     * Log status change
     *
     * @since 1.0.0
     * @param int    $ticket_id  Ticket ID
     * @param string $old_status Old status
     * @param string $new_status New status
     */
    private function log_status_change( $ticket_id, $old_status, $new_status ) {
        $user_id = get_current_user_id();
        $user = get_userdata( $user_id );
        $username = $user ? $user->display_name : esc_html__( 'System', 'helppress-tickets' );
        
        // Get status labels
        $status_labels = array(
            'hp_open' => esc_html__( 'Open', 'helppress-tickets' ),
            'hp_in_progress' => esc_html__( 'In Progress', 'helppress-tickets' ),
            'hp_resolved' => esc_html__( 'Resolved', 'helppress-tickets' ),
            'hp_closed' => esc_html__( 'Closed', 'helppress-tickets' ),
            'publish' => esc_html__( 'Published', 'helppress-tickets' ),
            'pending' => esc_html__( 'Pending Review', 'helppress-tickets' ),
        );
        
        $old_status_label = isset( $status_labels[$old_status] ) ? $status_labels[$old_status] : $old_status;
        $new_status_label = isset( $status_labels[$new_status] ) ? $status_labels[$new_status] : $new_status;
        
        // Create a comment for the status change
        $comment_data = array(
            'comment_post_ID' => $ticket_id,
            'comment_content' => sprintf(
                /* translators: %1$s: old status label, %2$s: new status label, %3$s: username */
                esc_html__( 'Status changed from %1$s to %2$s by %3$s', 'helppress-tickets' ),
                $old_status_label,
                $new_status_label,
                $username
            ),
            'user_id' => $user_id,
            //'comment_type' => 'helppress_status_change',
            'comment_type' => 'ticket_status_change',
            'comment_approved' => 1,     // Auto-approve
        );
        
        wp_insert_comment( $comment_data );
    }
    
    /**
     * Send notification email when a reply is added
     *
     * @since 1.0.0
     * @param int $ticket_id Ticket ID
     * @param int $comment_id Comment ID
     */
    private function send_notification_email($ticket_id, $comment_id) {
        //error_log('HelpPress Admin Actions: send_notification_email called for ticket ' . $ticket_id . ', comment ' . $comment_id);
        
        $comment = get_comment($comment_id);
        if (!$comment) {
            //error_log('HelpPress Admin Actions: Comment not found');
            return;
        }
        
        $ticket = get_post($ticket_id);
        if (!$ticket) {
            //error_log('HelpPress Admin Actions: Ticket not found');
            return;
        }
        
        $user = get_userdata($comment->user_id);
        if (!$user) {
            //error_log('HelpPress Admin Actions: User not found');
            return;
        }
        
        // Determine recipient email
        $recipient_email = '';
        
        // If support agent replied, notify the ticket author
        if (user_can($user->ID, 'edit_posts')) {
            $ticket_author = get_userdata($ticket->post_author);
            $recipient_email = $ticket_author->user_email;
            
            // Also send to CC email if provided
            $cc_email = get_post_meta($ticket_id, '_hp_ticket_email_cc', true);
            
            if ($cc_email && is_email($cc_email)) {
                $recipient_email .= ',' . $cc_email;
            }
        } 
        // If ticket author replied, notify support
        else {
            // Get admin email
            $recipient_email = get_option('admin_email');
        }
        
        // Exit if no recipient
        if (!$recipient_email) {
            //error_log('HelpPress Admin Actions: No recipient email found');
            return;
        }
        
        //error_log('HelpPress Admin Actions: Sending notification to ' . $recipient_email);
        
        // Prepare email content
        /* translators: %1$d: ticket ID, %2$s: ticket title */
        $subject = sprintf(esc_html__('[Ticket #%1$d] New Reply: %2$s', 'helppress-tickets'), $ticket_id, $ticket->post_title);
        
        /* translators: %1$d: ticket ID, %2$s: ticket title */
        $message = sprintf(
            esc_html__('A new reply has been added to ticket #%1$d: %2$s', 'helppress-tickets'),
            $ticket_id,
            $ticket->post_title
        );
        
        $message .= "\n\n";
        $message .= sprintf(esc_html__('From: %s', 'helppress-tickets'), $user->display_name);
        $message .= "\n\n";
        $message .= esc_html__('Reply:', 'helppress-tickets');
        $message .= "\n";
        $message .= wp_strip_all_tags($comment->comment_content);
        $message .= "\n\n";
        $message .= esc_html__('View Ticket:', 'helppress-tickets');
        $message .= "\n";
        
        // Use the helper function for consistent URLs
        $ticket_url = helppress_get_ticket_url($ticket_id);
        $message .= $ticket_url;
        
        // Send email
        $mail_result = wp_mail($recipient_email, $subject, $message);
        //error_log('HelpPress Admin Actions: Email sent, result: ' . ($mail_result ? 'success' : 'failed'));
    }
}

// Initialize the class
new HelpPress_Tickets_Admin_Actions();