<?php
/**
 * Shortcodes
 *
 * @package HelpPress Tickets
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcodes class
 *
 * @since 1.0.0
 */
class HelpPress_Tickets_Shortcodes {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_shortcode('helppress_submit_ticket', array($this, 'submit_ticket_shortcode'));
        add_shortcode('helppress_ticket_list', array($this, 'ticket_list_shortcode'));
        add_shortcode('helppress_admin_ticket_list', array($this, 'admin_ticket_list_shortcode'));
        add_shortcode('helppress_single_ticket', array($this, 'single_ticket_shortcode'));
        add_shortcode('helppress_check_status', array($this, 'check_status_shortcode'));
        add_shortcode('helppress_edit_ticket', array($this, 'edit_ticket_shortcode'));
        
        // Handle form submissions
        add_action('init', array($this, 'handle_ticket_submission'));
        add_action('init', array($this, 'handle_ticket_update'));
        add_action('wp_ajax_helppress_check_ticket_status', array($this, 'ajax_check_ticket_status'));
        add_action('wp_ajax_nopriv_helppress_check_ticket_status', array($this, 'ajax_check_ticket_status'));
    }

    /**
     * Submit ticket shortcode
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function submit_ticket_shortcode($atts) {
        $atts = shortcode_atts(array(
            'redirect' => '',
        ), $atts, 'helppress_submit_ticket');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return sprintf(
                '<div class="helppress-tickets-notice helppress-tickets-error">%s</div>',
                esc_html__('You must be logged in to submit a ticket.', 'helppress-tickets')
            );
        }
        
        ob_start();
        
        // Check for submission success message
        if (isset($_GET['ticket_submitted']) && $_GET['ticket_submitted'] == 'success') {
            echo '<div class="helppress-tickets-notice helppress-tickets-success">' . 
                esc_html__('Your ticket has been submitted successfully.', 'helppress-tickets') . 
                '</div>';
        }
        
        // Load the submit ticket form template using the template loader
        HelpPress_Tickets_Template_Loader::get_template('submit-ticket-form.php', array('atts' => $atts));
        
        return ob_get_clean();
    }

    /**
     * Edit ticket shortcode
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function edit_ticket_shortcode($atts) {
        $atts = shortcode_atts(array(
            'redirect' => '',
        ), $atts, 'helppress_edit_ticket');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return sprintf(
                '<div class="helppress-tickets-notice helppress-tickets-error">%s</div>',
                esc_html__('You must be logged in to edit a ticket.', 'helppress-tickets')
            );
        }
        
        ob_start();
        
        // Get ticket ID from URL parameter
        $ticket_id = isset($_GET['edit_ticket']) ? absint($_GET['edit_ticket']) : 0;
        
        if (!$ticket_id) {
            echo '<div class="alert alert-danger">';
            esc_html_e('No ticket specified for editing.', 'helppress-tickets');
            echo '</div>';
            return ob_get_clean();
        }
        
        // Get the ticket
        $ticket = get_post($ticket_id);
        
        if (!$ticket || $ticket->post_type !== 'hp_ticket') {
            echo '<div class="alert alert-danger">';
            esc_html_e('Invalid ticket specified.', 'helppress-tickets');
            echo '</div>';
            return ob_get_clean();
        }
        
        // Check if user has permission to edit this ticket
        if ($ticket->post_author != get_current_user_id() && !current_user_can('edit_post', $ticket_id)) {
            echo '<div class="alert alert-danger">';
            esc_html_e('You do not have permission to edit this ticket.', 'helppress-tickets');
            echo '</div>';
            return ob_get_clean();
        }
        
        // Show success message if ticket was updated
        if (isset($_GET['updated']) && $_GET['updated'] === 'true') {
            echo '<div class="alert alert-success mb-4">';
            esc_html_e('Ticket updated successfully.', 'helppress-tickets');
            echo '</div>';
        }
        
        // Load the edit ticket form template
        HelpPress_Tickets_Template_Loader::get_template('edit-ticket-form.php', array(
            'ticket' => $ticket,
            'atts' => $atts
        ));
        
        return ob_get_clean();
    }
    
    /**
     * User ticket list shortcode
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function ticket_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => -1,
            'order' => 'DESC',
            'orderby' => 'date',
            //'ajax' => 'true',
        ), $atts, 'helppress_ticket_list');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return sprintf(
                '<div class="helppress-tickets-notice helppress-tickets-error">%s</div>',
                esc_html__('You must be logged in to view your tickets.', 'helppress-tickets')
            );
        } 
        
        ob_start();

        // Get search parameter from URL if present
        $search = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';
        
        // Common query args
        $base_args = array(
            'post_type' => 'hp_ticket',
            'posts_per_page' => intval($atts['limit']),
            'order' => $atts['order'],
            'orderby' => $atts['orderby'],
            'author' => get_current_user_id(),
        );

        /*
        // Add search parameter if provided
        if (!empty($search)) {
            $base_args['s'] = $search;
        }
        */
        
        // Handle search - simple approach
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search_term = sanitize_text_field(wp_unslash($_GET['search']));
            
            if (is_numeric($search_term)) {
                // If numeric, use post ID parameter
                $base_args['p'] = absint($search_term);
            } else {
                // Otherwise use text search
                $base_args['s'] = $search_term;
            }
        }

        // Query for "All" tickets tab - includes all statuses
        $all_args = $base_args;
        $all_args['post_status'] = array('hp_open', 'hp_in_progress', 'hp_resolved', 'hp_closed', 'pending');
        $tickets_all = new WP_Query($all_args);
        
        // Query for "Open" tickets tab
        $open_args = $base_args;
        $open_args['post_status'] = 'hp_open';
        $tickets_open = new WP_Query($open_args);
        
        // Query for "In Progress" tickets tab
        $in_progress_args = $base_args;
        $in_progress_args['post_status'] = 'hp_in_progress';
        $tickets_in_progress = new WP_Query($in_progress_args);
        
        // Query for "Resolved/Closed" tickets tab
        $closed_args = $base_args;
        $closed_args['post_status'] = array('hp_resolved', 'hp_closed');
        $tickets_closed = new WP_Query($closed_args);
        
        // Load the ticket list template
        HelpPress_Tickets_Template_Loader::get_template('ticket-list.php', array( 
            'tickets_all' => $tickets_all,
            'tickets_open' => $tickets_open,
            'tickets_in_progress' => $tickets_in_progress,
            'tickets_closed' => $tickets_closed,
            'atts' => $atts,
        ));
        
        return ob_get_clean();
    }
    
    /**
     * Admin ticket list shortcode
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function admin_ticket_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => -1,
            'order' => 'DESC',
            'orderby' => 'date',
            'status' => '',
            'priority' => '',
        ), $atts, 'helppress_admin_ticket_list');
        
        // Check if user has admin capabilities
        if (!current_user_can('edit_others_posts')) {
            return sprintf(
                '<div class="helppress-tickets-notice helppress-tickets-error">%s</div>',
                esc_html__('You do not have permission to view all tickets.', 'helppress-tickets')
            );
        }
        
        ob_start();
        
        // Common query args
        $base_args = array(
            'post_type' => 'hp_ticket',
            'posts_per_page' => intval($atts['limit']),
            'order' => $atts['order'],
            'orderby' => $atts['orderby'],
        );

        // Add status filter from URL or shortcode
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $status = sanitize_text_field(wp_unslash($_GET['status']));
            $base_args['post_status'] = explode(',', $status);
        } elseif (!empty($atts['status'])) {
            $base_args['post_status'] = explode(',', $atts['status']);
        }

        // Handle search
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search_term = sanitize_text_field(wp_unslash($_GET['search']));
            
            if (is_numeric($search_term)) {
                // If numeric, search by post ID
                $base_args['p'] = absint($search_term);
            } elseif (is_email($search_term)) {
                // Email search 
                $base_args['s'] = $search_term;
            } else {
                // Regular text search 
                $base_args['s'] = $search_term;
            }
        }

        // Handle date range filtering
        if (isset($_GET['date_from']) && !empty($_GET['date_from']) || isset($_GET['date_to']) && !empty($_GET['date_to'])) {
            $date_query = array();
            
            if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
                $date_query['after'] = sanitize_text_field(wp_unslash($_GET['date_from']));
            }
            
            if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
                $date_to = sanitize_text_field(wp_unslash($_GET['date_to']));
                $date_query['before'] = $date_to;
                $date_query['inclusive'] = true;
            }
            
            $base_args['date_query'] = $date_query;
        }
        
        if (isset($_GET['priority']) && !empty($_GET['priority'])) {
            $base_args['tax_query'][] = array(
                'taxonomy' => 'hp_ticket_priority',
                'field' => 'slug',
                'terms' => sanitize_text_field(wp_unslash($_GET['priority'])),
            );
        } elseif (!empty($atts['priority'])) {
            $base_args['tax_query'][] = array(
                'taxonomy' => 'hp_ticket_priority',
                'field' => 'slug',
                'terms' => explode(',', $atts['priority']),
            );
        }
        
        if (isset($_GET['category']) && !empty($_GET['category'])) {
            $base_args['tax_query'][] = array(
                'taxonomy' => 'hp_category',
                'field' => 'slug',
                'terms' => sanitize_text_field(wp_unslash($_GET['category'])),
            );
        }
        
        if (isset($_GET['orderby']) && !empty($_GET['orderby'])) {
            $base_args['orderby'] = sanitize_text_field(wp_unslash($_GET['orderby']));
            
            if (isset($_GET['order']) && !empty($_GET['order'])) {
                $base_args['order'] = sanitize_text_field(wp_unslash($_GET['order']));
            }
        }
        
        // Query for "All" tickets tab
        $all_args = $base_args;
        $all_args['post_status'] = array('hp_open', 'hp_in_progress', 'hp_resolved', 'hp_closed', 'pending', 'publish');
        $tickets_all = new WP_Query($all_args);
        
        // Query for "Open" tickets tab
        $open_args = $base_args;
        $open_args['post_status'] = 'hp_open';
        $tickets_open = new WP_Query($open_args);
        
        // Query for "In Progress" tickets tab
        $in_progress_args = $base_args;
        $in_progress_args['post_status'] = 'hp_in_progress';
        $tickets_in_progress = new WP_Query($in_progress_args);
        
        // Query for "Resolved/Closed" tickets tab
        $closed_args = $base_args;
        $closed_args['post_status'] = array('hp_resolved', 'hp_closed');
        $tickets_closed = new WP_Query($closed_args);
        
        // Load the admin ticket list template
        HelpPress_Tickets_Template_Loader::get_template('ticket-list-admin.php', array( 
            'tickets_all' => $tickets_all,
            'tickets_open' => $tickets_open,
            'tickets_in_progress' => $tickets_in_progress,
            'tickets_closed' => $tickets_closed,
            'atts' => $atts,
        ));
        
        return ob_get_clean();
    }
    
    /**
     * Single ticket shortcode
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function single_ticket_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts, 'helppress_single_ticket');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return sprintf(
                '<div class="helppress-tickets-notice helppress-tickets-error">%s</div>',
                esc_html__('You must be logged in to view ticket details.', 'helppress-tickets')
            );
        }
        
        // First try to get ticket ID from URL parameter
        $ticket_id = isset($_GET['ticket_id']) ? absint($_GET['ticket_id']) : 0;
        
        // If not in query string, try to get from URL path
        if (!$ticket_id) {
            global $wp;
            $current_url = home_url(add_query_arg(array(), $wp->request));
            // Updated regex pattern for new URL structure
            if (preg_match('/support\/tickets\/(\d+)\/?$/', $current_url, $matches)) {
                $ticket_id = absint($matches[1]);
            }
        }
        
        // If still not found, try shortcode attribute
        if (!$ticket_id) {
            $ticket_id = absint($atts['id']);
        }
        
        ob_start();
        
        // Debug info to help diagnose the issue
        if (current_user_can('manage_options') && isset($_GET['debug'])) {
            echo '<div class="alert alert-info">';
            echo 'Ticket ID: ' . $ticket_id . '<br>';
            echo 'Current URL: ' . esc_html(add_query_arg(array(), $wp->request)) . '<br>';
            echo '</div>';
        }
        
        // If no ticket is specified, show search form
        if (!$ticket_id) {
            ?>
            <div class="helppress-tickets-search mb-5">
                <h3><?php esc_html_e('Find Your Ticket', 'helppress-tickets'); ?></h3>
                <p><?php esc_html_e('Enter your ticket ID to view your support ticket details.', 'helppress-tickets'); ?></p>
                <form method="get" action="" class="mb-4">
                    <div class="input-group">
                        <input type="number" name="ticket_id" class="form-control" placeholder="<?php esc_attr_e('Ticket ID', 'helppress-tickets'); ?>" required min="1">
                        <button type="submit" class="btn btn-primary"><?php esc_html_e('Find Ticket', 'helppress-tickets'); ?></button>
                    </div>
                </form>
                
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><?php esc_html_e('Recent Tickets', 'helppress-tickets'); ?></h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Show recent tickets for the current user
                        $recent_tickets = get_posts(array(
                            'post_type' => 'hp_ticket',
                            'author' => get_current_user_id(),
                            'posts_per_page' => 5,
                            'post_status' => array('hp_open', 'hp_in_progress', 'hp_resolved', 'hp_closed'),
                        ));
                        
                        if (!empty($recent_tickets)) {
                            echo '<ul class="list-group list-group-flush">';
                            foreach ($recent_tickets as $ticket) {
                                $status = get_post_status($ticket->ID);
                                $status_class = $this->get_status_class($status);
                                $status_label = $this->get_status_label($status);
                                
                                echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
                                echo '<a href="' . esc_url(helppress_get_ticket_url($ticket->ID)) . '">';
                                echo '<strong>#' . esc_html($ticket->ID) . '</strong> - ' . esc_html($ticket->post_title);
                                echo '</a>';
                                echo '<span class="badge ' . esc_attr($status_class) . '">' . esc_html($status_label) . '</span>';
                                echo '</li>';
                            }
                            echo '</ul>';
                        } else {
                            echo '<p class="text-center">' . esc_html__('You have no recent tickets.', 'helppress-tickets') . '</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }
        
        // Get the ticket
        $ticket = get_post($ticket_id);
        
        if (!$ticket || $ticket->post_type !== 'hp_ticket') {
            return sprintf(
                '<div class="helppress-tickets-notice helppress-tickets-error">%s</div>',
                esc_html__('Ticket not found.', 'helppress-tickets')
            );
        }
        
        // Check permissions - must be author or admin
        if ($ticket->post_author != get_current_user_id() && !current_user_can('edit_others_posts')) {
            return sprintf(
                '<div class="helppress-tickets-notice helppress-tickets-error">%s</div>',
                esc_html__('You do not have permission to view this ticket.', 'helppress-tickets')
            );
        }
        
        // Show messages based on URL parameters
        if (isset($_GET['status_changed']) && $_GET['status_changed'] === 'true') {
            echo '<div class="alert alert-success mb-4">' . esc_html__('Ticket status has been updated successfully.', 'helppress-tickets') . '</div>';
        }
        
        if (isset($_GET['privacy_changed']) && $_GET['privacy_changed'] === 'true') {
            echo '<div class="alert alert-success mb-4">' . esc_html__('Ticket privacy setting has been updated successfully.', 'helppress-tickets') . '</div>';
        }
        
        if (isset($_GET['replied']) && $_GET['replied'] === 'true') {
            echo '<div class="alert alert-success mb-4">' . esc_html__('Your reply has been added successfully.', 'helppress-tickets') . '</div>';
        }
        
        // Make sure comments are enabled for this ticket
        add_post_type_support('hp_ticket', 'comments');
        
        // Load the single ticket template
        // Load template with WordPress comments
        HelpPress_Tickets_Template_Loader::get_template('single-hp_ticket.php', array('ticket' => $ticket));
        
        
        return ob_get_clean();
    }
    
    /**
     * Check ticket status shortcode
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function check_status_shortcode($atts) {
        $atts = shortcode_atts(array(
            'redirect' => '',
        ), $atts, 'helppress_check_status');
        
        ob_start();
        
        // Load the check status form template
        HelpPress_Tickets_Template_Loader::get_template('ticket-status-form.php', array('atts' => $atts));
        
        return ob_get_clean();
    }
    
    /**
     * Handle ticket submission from the frontend form
     *
     * @since 1.0.0
     */
    public function handle_ticket_submission() {
        // Check if form was submitted
        if (!isset($_POST['helppress_submit_ticket']) || !isset($_POST['helppress_ticket_nonce'])) {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['helppress_ticket_nonce'])), 'helppress_submit_ticket')) {
            wp_die(esc_html__('Security check failed.', 'helppress-tickets'));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_die(esc_html__('You must be logged in to submit a ticket.', 'helppress-tickets'));
        }
        
        // Validate required fields
        if (empty($_POST['ticket_subject'])) {
            wp_die(esc_html__('Please enter a subject for your ticket.', 'helppress-tickets'));
        }
        
        if (empty($_POST['ticket_message'])) {
            wp_die(esc_html__('Please enter a message for your ticket.', 'helppress-tickets'));
        }
        
        // Get default status from settings
        $default_status = helppress_tickets_get_setting('tickets_default_status', 'hp_open');
        
        // Create the ticket post
        $ticket_data = array(
            'post_title'    => sanitize_text_field(wp_unslash($_POST['ticket_subject'])),
            'post_content'  => wp_kses_post(wp_unslash($_POST['ticket_message'])),
            'post_status'   => $default_status, // Get from settings
            'post_type'     => 'hp_ticket',
            'post_author'   => get_current_user_id(),
        );
        
        $ticket_id = wp_insert_post($ticket_data);
        
        if (is_wp_error($ticket_id)) {
            wp_die($ticket_id->get_error_message());
        }
        
        // Save ticket meta
        if (isset($_POST['ticket_email_cc'])) {
            update_post_meta($ticket_id, '_hp_ticket_email_cc', sanitize_email(wp_unslash($_POST['ticket_email_cc'])));
        }
        
        if (isset($_POST['ticket_phone'])) {
            update_post_meta($ticket_id, '_hp_ticket_phone', sanitize_text_field(wp_unslash($_POST['ticket_phone'])));
        }
        
        update_post_meta($ticket_id, '_hp_ticket_private', isset($_POST['ticket_private']) ? 1 : 0);
        
        if (isset($_POST['ticket_due_date']) && !empty($_POST['ticket_due_date'])) {
            update_post_meta($ticket_id, '_hp_ticket_due_date', sanitize_text_field(wp_unslash($_POST['ticket_due_date'])));
        }
        
        // Set categories if selected
        if (isset($_POST['ticket_category']) && !empty($_POST['ticket_category'])) {
            wp_set_object_terms($ticket_id, absint($_POST['ticket_category']), 'hp_category');
        }
        
        // Set tags if entered
        if (isset($_POST['ticket_tags']) && !empty($_POST['ticket_tags'])) {
            $tags = array_map('trim', explode(',', sanitize_text_field(wp_unslash($_POST['ticket_tags']))));
            wp_set_object_terms($ticket_id, $tags, 'hp_tag');
        }
        /*
        // Set priority if selected
        if (isset($_POST['ticket_priority']) && !empty($_POST['ticket_priority'])) {
            wp_set_object_terms($ticket_id, absint($_POST['ticket_priority']), 'hp_ticket_priority');
        } else {
            // Set default priority from settings
            $default_priority = helppress_tickets_get_setting('default_priority', 'medium');
            $priority_term = get_term_by('slug', $default_priority, 'hp_ticket_priority');
            if ($priority_term) {
                wp_set_object_terms($ticket_id, $priority_term->term_id, 'hp_ticket_priority');
            }
        }
        */
        
        // Set priority if selected - Direct approach
        if (isset($_POST['ticket_priority']) && !empty($_POST['ticket_priority'])) {
            // Log raw priority input for debugging
            //error_log('HP Tickets Debug: New ticket priority submitted: ' . $_POST['ticket_priority']);
            
            // Get priority ID 
            $priority_id = absint($_POST['ticket_priority']);
            
            // For new submissions, need to make absolutely sure taxonomy exists
            if (!taxonomy_exists('hp_ticket_priority')) {
                // Register taxonomy manually if it doesn't exist
                register_taxonomy(
                    'hp_ticket_priority',
                    'hp_ticket',
                    array(
                        'hierarchical' => true,
                        'public' => true,
                        'show_ui' => true
                    )
                );
                //error_log('HP Tickets Debug: Had to register taxonomy hp_ticket_priority');
            }
            
            // Direct approach to set priority term
            $result = wp_set_object_terms($ticket_id, array($priority_id), 'hp_ticket_priority');
            
            if (is_wp_error($result)) {
                //error_log('HP Tickets Debug: Error setting priority for new ticket: ' . $result->get_error_message());
                
                // Try direct database approach if standard WP functions fail
                global $wpdb;
                
                // Get the term taxonomy ID for this priority
                $term_taxonomy_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT tt.term_taxonomy_id FROM {$wpdb->term_taxonomy} tt 
                    WHERE tt.term_id = %d AND tt.taxonomy = %s",
                    $priority_id,
                    'hp_ticket_priority'
                ));
                
                if ($term_taxonomy_id) {
                    // Insert the term relationship directly
                    $wpdb->insert(
                        $wpdb->term_relationships,
                        array(
                            'object_id' => $ticket_id,
                            'term_taxonomy_id' => $term_taxonomy_id,
                            'term_order' => 0
                        ),
                        array('%d', '%d', '%d')
                    );
                    
                    //error_log('HP Tickets Debug: Used direct DB approach to set priority, result: ' . 
                        ($wpdb->last_error ? $wpdb->last_error : 'success'));
                        
                    // Update count in the term_taxonomy table
                    $wpdb->query($wpdb->prepare(
                        "UPDATE {$wpdb->term_taxonomy} SET count = count + 1 WHERE term_taxonomy_id = %d",
                        $term_taxonomy_id
                    ));
                    
                    // Clear caches
                    clean_post_cache($ticket_id);
                    clean_term_cache($priority_id, 'hp_ticket_priority');
                }
            } else {
                //error_log('HP Tickets Debug: Successfully set priority for new ticket: ' . print_r($result, true));
            }
        }

        
        // Handle file upload
        if (!empty($_FILES['ticket_attachment']['name'])) {
            $this->handle_ticket_attachment($_FILES['ticket_attachment'], $ticket_id);
        }
        
        // Send notification email
        $this->send_ticket_notification($ticket_id);
        
        // IMPROVED REDIRECTION LOGIC
        // First check for custom redirection in the form
        $redirect_url = !empty($_POST['redirect']) ? esc_url_raw(wp_unslash($_POST['redirect'])) : '';

        if (empty($redirect_url)) {
            // Always use the helper function for consistent URLs
            $redirect_url = helppress_get_ticket_url($ticket_id);
            
            // If that fails for some reason, fall back to options
            if (!$redirect_url) {
                $single_page_id = get_option('helppress_tickets_single_page_id');
                if ($single_page_id) {
                    $redirect_url = add_query_arg('ticket_id', $ticket_id, get_permalink($single_page_id));
                } else {
                    // Fall back to tickets list page with success message
                    $list_page_id = get_option('helppress_tickets_page_id');
                    if ($list_page_id) {
                        $redirect_url = add_query_arg('ticket_submitted', 'success', get_permalink($list_page_id));
                    }
                }
            }
        }

        wp_safe_redirect($redirect_url);
        exit;
    }
    
    /**
     * Handle ticket update from frontend
     *
     * @since 1.0.0
     */
    public function handle_ticket_update() {
        // Check if form was submitted
        if (!isset($_POST['helppress_update_ticket']) || !isset($_POST['helppress_ticket_update_nonce'])) {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['helppress_ticket_update_nonce'])), 'helppress_update_ticket')) {
            wp_die(esc_html__('Security check failed.', 'helppress-tickets'));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_die(esc_html__('You must be logged in to update a ticket.', 'helppress-tickets'));
        }
        
        // Get ticket ID
        $ticket_id = isset($_POST['ticket_id']) ? absint($_POST['ticket_id']) : 0;
        
        if (!$ticket_id) {
            wp_die(esc_html__('No ticket specified.', 'helppress-tickets'));
        }
        
        // Get the ticket
        $ticket = get_post($ticket_id);
        
        if (!$ticket || $ticket->post_type !== 'hp_ticket') {
            wp_die(esc_html__('Invalid ticket specified.', 'helppress-tickets'));
        }
        
        // Check if user has permission to edit this ticket
        if ($ticket->post_author != get_current_user_id() && !current_user_can('edit_post', $ticket_id)) {
            wp_die(esc_html__('You do not have permission to edit this ticket.', 'helppress-tickets'));
        }
        
        // Validate required fields
        if (empty($_POST['ticket_subject'])) {
            wp_die(esc_html__('Please enter a subject for your ticket.', 'helppress-tickets'));
        }
        
        if (empty($_POST['ticket_message'])) {
            wp_die(esc_html__('Please enter a message for your ticket.', 'helppress-tickets'));
        }
        
        // Update the ticket
        $ticket_data = array(
            'ID'           => $ticket_id,
            'post_title'   => sanitize_text_field(wp_unslash($_POST['ticket_subject'])),
            'post_content' => wp_kses_post(wp_unslash($_POST['ticket_message'])),
        );
        
        $updated = wp_update_post($ticket_data);
        
        if (is_wp_error($updated)) {
            wp_die($updated->get_error_message());
        }
        
        // Update ticket meta
        if (isset($_POST['ticket_email_cc'])) {
            update_post_meta($ticket_id, '_hp_ticket_email_cc', sanitize_email(wp_unslash($_POST['ticket_email_cc'])));
        }
        
        if (isset($_POST['ticket_phone'])) {
            update_post_meta($ticket_id, '_hp_ticket_phone', sanitize_text_field(wp_unslash($_POST['ticket_phone'])));
        }
        
        update_post_meta($ticket_id, '_hp_ticket_private', isset($_POST['ticket_private']) ? 1 : 0);
        /*
        // Set categories if selected
        if (isset($_POST['ticket_category']) && !empty($_POST['ticket_category'])) {
            wp_set_object_terms($ticket_id, absint($_POST['ticket_category']), 'hp_category');
        }
        
        // Set tags if entered
        if (isset($_POST['ticket_tags']) && !empty($_POST['ticket_tags'])) {
            $tags = array_map('trim', explode(',', sanitize_text_field(wp_unslash($_POST['ticket_tags']))));
            wp_set_object_terms($ticket_id, $tags, 'hp_tag');
        }
        */
        
        // Set priority if selected - Direct approach
        if (isset($_POST['ticket_priority'])) {
            // Log raw priority input for debugging
            //error_log('HP Tickets Debug: Update ticket priority submitted: ' . $_POST['ticket_priority']);
            
            // Allow for empty priority (to remove priority)
            if (empty($_POST['ticket_priority'])) {
                wp_set_object_terms($ticket_id, array(), 'hp_ticket_priority');
                //error_log('HP Tickets Debug: Cleared priority terms (empty value)');
            } else {
                // Get priority ID 
                $priority_id = absint($_POST['ticket_priority']);
                
                // Grant temporary capability for this action if needed
                $was_capability_added = false;
                
                if ($ticket->post_author == get_current_user_id() && !current_user_can('assign_terms')) {
                    add_filter('map_meta_cap', function($caps, $cap, $user_id, $args) use ($ticket_id) {
                        if ($cap === 'assign_terms' && 
                            isset($args[0]) && $args[0] === 'hp_ticket_priority' &&
                            isset($args[1]) && $args[1] == $ticket_id) {
                            return array('exist'); // A capability all users have
                        }
                        return $caps;
                    }, 10, 4);
                    
                    $was_capability_added = true;
                    //error_log('HP Tickets Debug: Added temporary capability for term assignment');
                }
                
                // Direct approach to set priority term
                $result = wp_set_object_terms($ticket_id, array($priority_id), 'hp_ticket_priority');
                
                if (is_wp_error($result)) {
                    //error_log('HP Tickets Debug: Error setting priority for ticket update: ' . $result->get_error_message());
                    
                    // Try direct database approach if standard WP functions fail
                    global $wpdb;
                    
                    // First remove any existing relationships
                    $wpdb->delete(
                        $wpdb->term_relationships,
                        array('object_id' => $ticket_id),
                        array('%d')
                    );
                    
                    // Get the term taxonomy ID for this priority
                    $term_taxonomy_id = $wpdb->get_var($wpdb->prepare(
                        "SELECT tt.term_taxonomy_id FROM {$wpdb->term_taxonomy} tt 
                        WHERE tt.term_id = %d AND tt.taxonomy = %s",
                        $priority_id,
                        'hp_ticket_priority'
                    ));
                    
                    if ($term_taxonomy_id) {
                        // Insert the term relationship directly
                        $wpdb->insert(
                            $wpdb->term_relationships,
                            array(
                                'object_id' => $ticket_id,
                                'term_taxonomy_id' => $term_taxonomy_id,
                                'term_order' => 0
                            ),
                            array('%d', '%d', '%d')
                        );
                        
                        //error_log('HP Tickets Debug: Used direct DB approach to update priority, result: ' . 
                            ($wpdb->last_error ? $wpdb->last_error : 'success'));
                            
                        // Update count in the term_taxonomy table
                        $wpdb->query($wpdb->prepare(
                            "UPDATE {$wpdb->term_taxonomy} SET count = count + 1 WHERE term_taxonomy_id = %d",
                            $term_taxonomy_id
                        ));
                        
                        // Clear caches
                        clean_post_cache($ticket_id);
                        clean_term_cache($priority_id, 'hp_ticket_priority');
                    }
                } else {
                    //error_log('HP Tickets Debug: Successfully updated priority: ' . print_r($result, true));
                    
                    // Verify the update worked by checking the database directly
                    global $wpdb;
                    $term_exists = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->term_relationships} tr
                        JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                        WHERE tr.object_id = %d AND tt.term_id = %d AND tt.taxonomy = %s",
                        $ticket_id,
                        $priority_id,
                        'hp_ticket_priority'
                    ));
                    
                    //error_log('HP Tickets Debug: DB verification - term relationship exists: ' . ($term_exists ? 'Yes' : 'No'));
                }
                
                // Remove temporary capability if it was added
                if ($was_capability_added) {
                    // The filter will be removed automatically when the function completes
                    //error_log('HP Tickets Debug: Temporary capability filter will be removed');
                }
            }
        }

        // Handle file upload if provided
        if (!empty($_FILES['ticket_attachment']['name'])) {
            $this->handle_ticket_attachment($_FILES['ticket_attachment'], $ticket_id);
        }
        
        // Redirect after submission
        $redirect_url = !empty($_POST['redirect']) ? esc_url_raw(wp_unslash($_POST['redirect'])) : '';
        
        if (empty($redirect_url)) {
            // Default to edit page with success message
            $redirect_url = add_query_arg(array(
                'edit_ticket' => $ticket_id,
                'updated' => 'true'
            ), get_permalink(get_option('helppress_tickets_edit_page_id')));
        }
        
        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Handle ticket attachment upload
     *
     * @since 1.0.0
     * @param array $file     The uploaded file
     * @param int   $ticket_id The ticket ID
     * @return int|false Attachment ID on success, false on failure
     */
    private function handle_ticket_attachment($file, $ticket_id) {
        // Check if file is valid
        if ($file['error'] != 0) {
            return false;
        }
        
        // Get allowed file types from settings
        $allowed_types = helppress_tickets_get_setting('tickets_allowed_file_types', 'jpg,jpeg,png,pdf,zip');
        $allowed_types = array_map('trim', explode(',', $allowed_types));
        
        // Get max file size from settings
        $max_size = absint(helppress_tickets_get_setting('tickets_max_attachment_size', 1024)) * 1024; // Convert KB to bytes
        
        // Check file size
        if ($file['size'] > $max_size) {
            return false;
        }
        
        // Check file type
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed_types)) {
            return false;
        }
        
        // Include WordPress file handling
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($file, $upload_overrides);
        
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
            
            // Link attachment to ticket
            set_post_thumbnail($ticket_id, $attach_id);
            
            return $attach_id;
        }
        
        return false;
    }
    
    /**
     * Send notification email for new ticket
     *
     * @since 1.0.0
     * @param int $ticket_id Ticket ID
     */
    private function send_ticket_notification($ticket_id) {
        // Check if email notifications are enabled
        if (!helppress_tickets_get_setting('tickets_email_notifications', true)) {
            return;
        }
        
        $ticket = get_post($ticket_id);
        if (!$ticket) {
            return;
        }
        
        $author = get_userdata($ticket->post_author);
        if (!$author) {
            return;
        }
        
        // Send notification to admin
        $admin_email = get_option('admin_email');
        $subject = sprintf(
            /* translators: %1$d: ticket ID, %2$s: ticket subject */
            esc_html__('[New Ticket #%1$d] %2$s', 'helppress-tickets'),
            $ticket_id,
            $ticket->post_title
        );
        
        $message = sprintf(
            /* translators: %1$s: user name, %2$d: ticket ID, %3$s: ticket subject */
            esc_html__('A new support ticket has been submitted by %1$s.', 'helppress-tickets'),
            $author->display_name
        );
        
        $message .= "\n\n";
        $message .= sprintf(
            /* translators: %1$d: ticket ID, %2$s: ticket subject */
            esc_html__('Ticket #%1$d: %2$s', 'helppress-tickets'),
            $ticket_id,
            $ticket->post_title
        );
        
        $message .= "\n\n";
        $message .= esc_html__('Message:', 'helppress-tickets');
        $message .= "\n";
        $message .= wp_strip_all_tags($ticket->post_content);
        
        $message .= "\n\n";
        $message .= esc_html__('View Ticket:', 'helppress-tickets');
        $message .= "\n";
        
        // Get ticket URL for admin - using direct permalink
        $ticket_url = admin_url('post.php?post=' . $ticket_id . '&action=edit');
        $message .= $ticket_url;
        
        wp_mail($admin_email, $subject, $message);
        
        // Send confirmation to user
        $user_email = $author->user_email;
        $subject = sprintf(
            /* translators: %1$d: ticket ID, %2$s: ticket subject */
            esc_html__('[Ticket #%1$d] Your support request has been received', 'helppress-tickets'),
            $ticket_id,
            $ticket->post_title
        );
        
        $message = sprintf(
            /* translators: %s: user name */
            esc_html__('Hello %s,', 'helppress-tickets'),
            $author->display_name
        );
        
        $message .= "\n\n";
        $message .= esc_html__('Thank you for contacting us. Your support ticket has been received and is being reviewed by our support team.', 'helppress-tickets');
        
        $message .= "\n\n";
        $message .= sprintf(
            /* translators: %1$d: ticket ID, %2$s: ticket subject */
            esc_html__('Ticket #%1$d: %2$s', 'helppress-tickets'),
            $ticket_id,
            $ticket->post_title
        );
        
        $message .= "\n\n";
        $message .= esc_html__('We will respond to your ticket as soon as possible.', 'helppress-tickets');
        
        $message .= "\n\n";
        $message .= esc_html__('View Ticket:', 'helppress-tickets');
        $message .= "\n";
        
        // Get ticket URL for user - using the helper function
        $ticket_url = helppress_get_ticket_url($ticket_id);
        $message .= $ticket_url;
        
        // Send user email
        wp_mail($user_email, $subject, $message);
        
        // Send CC if provided
        $cc_email = get_post_meta($ticket_id, '_hp_ticket_email_cc', true);
        if ($cc_email && is_email($cc_email)) {
            wp_mail($cc_email, $subject, $message);
        }
    }
    
    /**
     * AJAX handler for checking ticket status
     *
     * @since 1.0.0
     */
    public function ajax_check_ticket_status() {
        check_ajax_referer('helppress_tickets_nonce', 'nonce');
        
        $ticket_id = isset($_POST['ticket_id']) ? absint($_POST['ticket_id']) : 0;
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        
        if (!$ticket_id || !$email) {
            wp_send_json_error(array(
                'message' => esc_html__('Please provide both ticket ID and email.', 'helppress-tickets')
            ));
        }
        
        // Get the ticket
        $ticket = get_post($ticket_id);
        
        if (!$ticket || $ticket->post_type !== 'hp_ticket') {
            wp_send_json_error(array(
                'message' => esc_html__('Ticket not found.', 'helppress-tickets')
            ));
        }
        
        // Check if email matches ticket author
        $author = get_user_by('id', $ticket->post_author);
        
        if (!$author || $author->user_email !== $email) {
            // Also check email CC
            $email_cc = get_post_meta($ticket_id, '_hp_ticket_email_cc', true);
            
            if ($email_cc !== $email) {
                wp_send_json_error(array(
                    'message' => esc_html__('The provided email does not match the ticket.', 'helppress-tickets')
                ));
            }
        }
        
        // Get ticket status
        $status = get_post_status($ticket_id);
        $status_label = $this->get_status_label($status);
        
        // Get priority
        $priority_terms = wp_get_object_terms($ticket_id, 'hp_ticket_priority');
        $priority = !empty($priority_terms) ? $priority_terms[0]->name : esc_html__('Not set', 'helppress-tickets');
        
        // Get last update
        $last_modified = get_the_modified_date('Y-m-d H:i:s', $ticket_id);
        
        // Get ticket URL - use direct permalink
        $ticket_url = get_permalink($ticket_id);
        
        wp_send_json_success(array(
            'id' => $ticket_id,
            'subject' => get_the_title($ticket_id),
            'status' => $status_label,
            'priority' => $priority,
            'updated' => $last_modified,
            'link' => $ticket_url,
        ));
    }
    
    /**
     * Get human-readable status label
     *
     * @since 1.0.0
     * @param string $status Post status
     * @return string Status label
     */
    public function get_status_label($status) {
        $statuses = array(
            'hp_open' => esc_html__('Open', 'helppress-tickets'),
            'hp_in_progress' => esc_html__('In Progress', 'helppress-tickets'),
            'hp_resolved' => esc_html__('Resolved', 'helppress-tickets'),
            'hp_closed' => esc_html__('Closed', 'helppress-tickets'),
            'publish' => esc_html__('Published', 'helppress-tickets'),
            'pending' => esc_html__('Pending', 'helppress-tickets'),
            'draft' => esc_html__('Draft', 'helppress-tickets'),
            'private' => esc_html__('Private', 'helppress-tickets'),
        );
        
        return isset($statuses[$status]) ? $statuses[$status] : $status;
    }

    /**
     * Get status class for styling
     *
     * @since 1.0.0
     * @param string $status Post status
     * @return string CSS class for status
     */
    public function get_status_class($status) {
        $status_class = '';
        switch ($status) {
            case 'hp_open':
                $status_class = 'bg-info text-dark';
                break;
            case 'hp_in_progress':
                $status_class = 'bg-primary';
                break;
            case 'hp_resolved':
                $status_class = 'bg-success';
                break;
            case 'hp_closed':
                $status_class = 'bg-secondary';
                break;
            case 'publish':
                $status_class = 'bg-success';
                break;
            case 'pending':
                $status_class = 'bg-warning text-dark';
                break;
            default:
                $status_class = 'bg-secondary';
        }
        return $status_class;
    }
}

// Initialize the class
new HelpPress_Tickets_Shortcodes();