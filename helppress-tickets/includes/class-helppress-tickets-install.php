<?php
/**
 * Installation
 *
 * @package HelpPress Tickets
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Installation class
 *
 * @since 1.0.0
 */
class HelpPress_Tickets_Install {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        register_activation_hook( HPTICKETS_FILE, array( $this, 'activate' ) );
        add_action( 'admin_init', array( $this, 'check_version' ) );
        
        // Add action for cleanup on plugin deactivation
        register_deactivation_hook( HPTICKETS_FILE, array( $this, 'deactivate' ) );
    }

    /**
     * Activate the plugin
     *
     * @since 1.0.0
     */
    public function activate() {
        // Set plugin version
        $this->set_version();
        
        // Create custom database tables
        $this->create_tables();
        
        // Add capabilities
        $this->add_capabilities();
        
        // Create default priority terms
        $this->create_default_terms();
        
        // Create default pages
        $this->create_default_pages();
        
        // Schedule cleanup event
        $this->schedule_events();
        
        // Clear rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Deactivate the plugin
     *
     * @since 1.0.0
     */
    public function deactivate() {
        // Clear scheduled events
        $this->clear_scheduled_events();
        
        // Clear rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Set plugin version
     *
     * @since 1.0.0
     */
    private function set_version() {
        update_option( 'helppress_tickets_version', HPTICKETS_VERSION );
    }
    
    /**
     * Check plugin version and run upgrade routines if necessary
     *
     * @since 1.0.0
     */
    public function check_version() {
        if ( get_option( 'helppress_tickets_version' ) !== HPTICKETS_VERSION ) {
            $this->activate();
        }
    }
    
    /**
     * Create custom database tables
     *
     * @since 1.0.0
     */
    private function create_tables() {
        global $wpdb;
        
        $wpdb->hide_errors();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create ticket activity log table
        $table_name = $wpdb->prefix . 'helppress_ticket_logs';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ticket_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            action varchar(100) NOT NULL,
            message text NOT NULL,
            date_created datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id),
            KEY ticket_id (ticket_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Create ticket replies table
        $table_name = $wpdb->prefix . 'helppress_ticket_replies';
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            ticket_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            content longtext NOT NULL,
            attachment_id bigint(20) unsigned DEFAULT 0,
            is_agent tinyint(1) DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY ticket_id (ticket_id)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Log table creation
        //error_log('HelpPress Tickets: Tables created');
    }
    
    /**
     * Add capabilities to roles
     *
     * @since 1.0.0
     */
    private function add_capabilities() {
        global $wp_roles;
        
        if ( ! class_exists( 'WP_Roles' ) ) {
            return;
        }
        
        if ( ! isset( $wp_roles ) ) {
            $wp_roles = new WP_Roles();
        }
        
        // Capabilities for administrators
        $capabilities = array(
            'edit_hp_ticket' => true,
            'read_hp_ticket' => true,
            'delete_hp_ticket' => true,
            'edit_hp_tickets' => true,
            'edit_others_hp_tickets' => true,
            'publish_hp_tickets' => true,
            'read_private_hp_tickets' => true,
            'delete_hp_tickets' => true,
            'delete_private_hp_tickets' => true,
            'delete_published_hp_tickets' => true,
            'delete_others_hp_tickets' => true,
            'edit_private_hp_tickets' => true,
            'edit_published_hp_tickets' => true,
            'manage_hp_ticket_terms' => true,
            'edit_hp_ticket_terms' => true,
            'delete_hp_ticket_terms' => true,
            'assign_hp_ticket_terms' => true,
            'edit_hp_article_terms' => true,
            'manage_hp_article_terms' => true,
            'delete_hp_article_terms' => true,
            'assign_hp_article_terms' => true,
        );
        
        // Add capabilities to administrator
        $admin_role = $wp_roles->get_role( 'administrator' );
        
        if ( $admin_role ) {
            foreach ( $capabilities as $cap => $value ) {
                $admin_role->add_cap( $cap );
            }
        }
        
        // Add limited capabilities to editor
        $editor_role = $wp_roles->get_role( 'editor' );
        
        if ( $editor_role ) {
            $editor_capabilities = array(
                'edit_hp_ticket' => true,
                'read_hp_ticket' => true,
                'delete_hp_ticket' => true,
                'edit_hp_tickets' => true,
                'edit_others_hp_tickets' => true,
                'publish_hp_tickets' => true,
                'read_private_hp_tickets' => true,
                'delete_hp_tickets' => true,
                'delete_private_hp_tickets' => true,
                'delete_published_hp_tickets' => true,
                'delete_others_hp_tickets' => true,
                'edit_private_hp_tickets' => true,
                'edit_published_hp_tickets' => true,
                'manage_hp_ticket_terms' => true,
                'edit_hp_ticket_terms' => true,
                'delete_hp_ticket_terms' => true,
                'assign_hp_ticket_terms' => true,
                'edit_hp_article_terms' => true,
                'manage_hp_article_terms' => true,
                'delete_hp_article_terms' => true,
                'assign_hp_article_terms' => true,
            );
            
            foreach ( $editor_capabilities as $cap => $value ) {
                $editor_role->add_cap( $cap );
            }
        }
        
        // Add basic capabilities to author
        $author_role = $wp_roles->get_role( 'author' );
        
        if ( $author_role ) {
            $author_capabilities = array(
                'edit_hp_ticket' => true,
                'read_hp_ticket' => true,
                'edit_hp_tickets' => true,
                'publish_hp_tickets' => true,
                'read_private_hp_tickets' => true,
                'edit_private_hp_tickets' => true,
                'edit_published_hp_tickets' => true,
                'manage_hp_ticket_terms' => true,
                'edit_hp_ticket_terms' => true,
                'delete_hp_ticket_terms' => true,
                'assign_hp_ticket_terms' => true,
                'edit_hp_article_terms' => true,
                'manage_hp_article_terms' => true,
                'assign_hp_article_terms' => true,
            );
            
            foreach ( $author_capabilities as $cap => $value ) {
                $author_role->add_cap( $cap );
            }
        }

        // Add basic capabilities to contributor
        $contributor_role = $wp_roles->get_role( 'contributor' );
        
        if ( $contributor_role ) {
            $contributor_capabilities = array(
                'edit_hp_ticket' => true,
                'read_hp_ticket' => true,
                'edit_hp_tickets' => true,
                'publish_hp_tickets' => true,
                'read_private_hp_tickets' => true,
                'edit_private_hp_tickets' => true,
                'edit_published_hp_tickets' => true,
                'manage_hp_ticket_terms' => true,
                'edit_hp_ticket_terms' => true,
                'delete_hp_ticket_terms' => true,
                'assign_hp_ticket_terms' => true,
                'edit_hp_article_terms' => true,
                'manage_hp_article_terms' => true,
                'assign_hp_article_terms' => true,
            );
            
            foreach ( $contributor_capabilities as $cap => $value ) {
                $contributor_role->add_cap( $cap );
            }
        }

        // Add basic capabilities to contributor
        $customer_role = $wp_roles->get_role( 'customer' );
        
        if ( $customer_role ) {
            $customer_capabilities = array(
                'edit_hp_ticket' => true,
                'read_hp_ticket' => true,
                'edit_hp_tickets' => true,
                'publish_hp_tickets' => true,
                'read_private_hp_tickets' => true,
                'edit_private_hp_tickets' => true,
                'edit_published_hp_tickets' => true,
                'manage_hp_ticket_terms' => true,
                'edit_hp_ticket_terms' => true,
                'delete_hp_ticket_terms' => true,
                'assign_hp_ticket_terms' => true,
                'edit_hp_article_terms' => true,
                'manage_hp_article_terms' => true,
                'assign_hp_article_terms' => true,
            );
            
            foreach ( $customer_capabilities as $cap => $value ) {
                $customer_role->add_cap( $cap );
            }
        }

        // Add minimal capabilities to subscriber
        $subscriber_role = $wp_roles->get_role('subscriber');
        
        if ($subscriber_role) {
            $subscriber_capabilities = array(
                'edit_hp_ticket' => true,
                'read_hp_ticket' => true,
                'edit_hp_tickets' => true,
                'publish_hp_tickets' => true,
                'read_private_hp_tickets' => true,
                'edit_private_hp_tickets' => true,
                'edit_published_hp_tickets' => true,
                'manage_hp_ticket_terms' => true,
                'edit_hp_ticket_terms' => true,
                'delete_hp_ticket_terms' => true,
                'assign_hp_ticket_terms' => true,
                'edit_hp_article_terms' => true,
                'manage_hp_article_terms' => true,
                'assign_hp_article_terms' => true,
            );
            
            foreach ($subscriber_capabilities as $cap => $value) {
                $subscriber_role->add_cap($cap);
            }
        }
    }
    
    /**
     * Create default priority terms
     *
     * @since 1.0.0
     */
    private function create_default_terms() {
        // Load post types class to ensure taxonomies are registered
        if ( ! class_exists( 'HelpPress_Tickets_Post_Types' ) ) {
            require_once HPTICKETS_PATH . 'includes/class-helppress-tickets-post-types.php';
            new HelpPress_Tickets_Post_Types();
        }
        
        // Create default priorities if they don't exist
        $priorities = array(
            'low' => array(
                'name' => 'Low',
                'description' => 'Low priority tickets that are not time-sensitive.',
            ),
            'medium' => array(
                'name' => 'Medium',
                'description' => 'Medium priority tickets that should be addressed soon.',
            ),
            'high' => array(
                'name' => 'High',
                'description' => 'High priority tickets that require prompt attention.',
            ),
            'urgent' => array(
                'name' => 'Urgent',
                'description' => 'Urgent tickets that require immediate attention.',
            ),
        );
        
        foreach ( $priorities as $slug => $data ) {
            if ( ! term_exists( $slug, 'hp_ticket_priority' ) ) {
                wp_insert_term(
                    $data['name'],
                    'hp_ticket_priority',
                    array(
                        'slug' => $slug,
                        'description' => $data['description'],
                    )
                );
            }
        }
    }
    
    /**
     * Create default pages for ticket management
     *
     * @since 1.0.0
     */
    private function create_default_pages() {
        // Create ticket listing page if it doesn't exist
        $ticket_page_id = get_option( 'helppress_tickets_page_id' );
        if ( ! $ticket_page_id ) {
            $page_id = wp_insert_post( array(
                'post_title'     => esc_html__( 'My Support Tickets', 'helppress-tickets' ),
                'post_content'   => '[helppress_ticket_list]',
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'comment_status' => 'closed',
            ) );
            
            if ( $page_id && ! is_wp_error( $page_id ) ) {
                update_option( 'helppress_tickets_page_id', $page_id );

                // Also store in options array for compatibility
                $ticket_options = get_option('helppress_tickets_options', array());
                $ticket_options['page_id'] = $page_id;
                $ticket_options['list_page'] = $page_id; // Also store with alternative key
                update_option('helppress_tickets_options', $ticket_options);
            }
        }
        
        // Create ticket submission page if it doesn't exist
        $submit_page_id = get_option('helppress_tickets_submit_page_id');
        if (!$submit_page_id) {
            $page_id = wp_insert_post(array(
                'post_title'     => esc_html__('Submit Support Ticket', 'helppress-tickets'),
                'post_content'   => '[helppress_submit_ticket]',
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'comment_status' => 'closed',
            ));
            
            if ($page_id && !is_wp_error($page_id)) {
                update_option('helppress_tickets_submit_page_id', $page_id);
                
                // Also store in options array for compatibility
                $ticket_options = get_option('helppress_tickets_options', array());
                $ticket_options['submit_page_id'] = $page_id;
                $ticket_options['submit_page'] = $page_id; // Also store with alternative key
                update_option('helppress_tickets_options', $ticket_options);
            }
        }
        
        // Create ticket status check page if it doesn't exist
        $status_page_id = get_option('helppress_tickets_status_page_id');
        if (!$status_page_id) {
            $page_id = wp_insert_post(array(
                'post_title'     => esc_html__('Check Ticket Status', 'helppress-tickets'),
                'post_content'   => '[helppress_check_status]',
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'comment_status' => 'closed',
            ));
            
            if ($page_id && !is_wp_error($page_id)) {
                update_option('helppress_tickets_status_page_id', $page_id);
                
                // Also store in options array for compatibility
                $ticket_options = get_option('helppress_tickets_options', array());
                $ticket_options['status_page_id'] = $page_id;
                $ticket_options['status_page'] = $page_id; // Also store with alternative key
                update_option('helppress_tickets_options', $ticket_options);
            }
        }
    
        // Create single ticket page if it doesn't exist
        $single_page_id = get_option('helppress_tickets_single_page_id');
        if (!$single_page_id) {
            $page_id = wp_insert_post(array(
                'post_title'     => esc_html__('View Ticket', 'helppress-tickets'),
                'post_content'   => '[helppress_single_ticket]',
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'comment_status' => 'closed',
            ));
            
            if ($page_id && !is_wp_error($page_id)) {
                update_option('helppress_tickets_single_page_id', $page_id);
                
                // Also store in options array for compatibility
                $ticket_options = get_option('helppress_tickets_options', array());
                $ticket_options['single_page_id'] = $page_id;
                $ticket_options['single_page'] = $page_id; // Also store with alternative key
                update_option('helppress_tickets_options', $ticket_options);
            }
        }
        
        // Create edit ticket page if it doesn't exist
        $edit_page_id = get_option('helppress_tickets_edit_page_id');
        if (!$edit_page_id) {
            $page_id = wp_insert_post(array(
                'post_title'     => esc_html__('Edit Ticket', 'helppress-tickets'),
                'post_content'   => '[helppress_edit_ticket]',
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'comment_status' => 'closed',
            ));
            
            if ($page_id && !is_wp_error($page_id)) {
                update_option('helppress_tickets_edit_page_id', $page_id);
                
                // Also store in options array for compatibility
                $ticket_options = get_option('helppress_tickets_options', array());
                $ticket_options['edit_page_id'] = $page_id;
                $ticket_options['edit_page'] = $page_id; // Also store with alternative key
                update_option('helppress_tickets_options', $ticket_options);
            }
        } 
    }
    
    /**
     * Schedule events
     *
     * @since 1.0.0
     */
    private function schedule_events() {
        // Schedule daily cleanup of old ticket data
        if ( ! wp_next_scheduled( 'helppress_tickets_daily_cleanup' ) ) {
            wp_schedule_event( time(), 'daily', 'helppress_tickets_daily_cleanup' );
        }
    }
    
    /**
     * Clear scheduled events
     *
     * @since 1.0.0
     */
    private function clear_scheduled_events() {
        wp_clear_scheduled_hook( 'helppress_tickets_daily_cleanup' );
    }
}

// Initialize the class
new HelpPress_Tickets_Install();
