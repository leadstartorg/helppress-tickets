<?php
/**
 * Post Types
 *
 * @package HelpPress Tickets
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Post Types class
 *
 * @since 1.0.0
 */
class HelpPress_Tickets_Post_Types {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_post_status'));
        add_action('init', array($this, 'register_taxonomies'));

        // Use the same categories and tags as knowledge base
        add_action('init', array($this, 'register_article_taxonomies'));

        // Handle ticket rewrites
        add_action('init', array($this, 'add_ticket_rewrite_rules'));
        
        // Handle single ticket template
        add_filter('single_template', array($this, 'load_single_ticket_template'));
        
        // Handle archive ticket template 
        add_filter('archive_template', array($this, 'load_archive_ticket_template'));
        
        // Add permalink structure for tickets
        add_filter('post_type_link', array($this, 'ticket_permalink_structure'), 10, 2);
        
        // High priority template override to ensure correct template loading
        add_filter('template_include', array($this, 'force_single_ticket_template'), 99999);

        // Enable comments on all ticket posts
        $this->enable_comments_on_tickets();
   }

    /**
     * Register the support ticket post type
     *
     * @since 1.0.0
     */
    public function register_post_types() {
        register_post_type(
            'hp_ticket',
            apply_filters('helppress_tickets_register_post_type_args', array(
                'labels' => array(
                    'name'                  => esc_html_x('Support Tickets', 'Post type general name', 'helppress-tickets'),
                    'singular_name'         => esc_html_x('Support Ticket', 'Post type singular name', 'helppress-tickets'),
                    'menu_name'             => esc_html_x('Tickets', 'Admin Menu text', 'helppress-tickets'),
                    'name_admin_bar'        => esc_html_x('Ticket', 'Add New on Toolbar', 'helppress-tickets'),
                    'add_new'               => esc_html__('Add New', 'helppress-tickets'),
                    'add_new_item'          => esc_html__('Add New Ticket', 'helppress-tickets'),
                    'new_item'              => esc_html__('New Ticket', 'helppress-tickets'),
                    'edit_item'             => esc_html__('Edit Ticket', 'helppress-tickets'),
                    'view_item'             => esc_html__('View Ticket', 'helppress-tickets'),
                    'all_items'             => esc_html__('All Tickets', 'helppress-tickets'),
                    'search_items'          => esc_html__('Search Tickets', 'helppress-tickets'),
                    'parent_item_colon'     => esc_html__('Parent Tickets:', 'helppress-tickets'),
                    'not_found'             => esc_html__('No tickets found.', 'helppress-tickets'),
                    'not_found_in_trash'    => esc_html__('No tickets found in Trash.', 'helppress-tickets'),
                ),
                'description'           => esc_html__('Support ticket custom post type', 'helppress-tickets'),
                'public'                => true,
                'publicly_queryable'    => true,
                'show_ui'               => true,
                'show_in_menu'          => 'edit.php?post_type=hp_article', // Show under HelpPress menu
                'show_in_rest'          => true,
                'rest_base'             => '',
                'rest_controller_class' => 'WP_REST_Posts_Controller',
                'rest_namespace'        => 'wp/v2',
                'has_archive'           => false, // No archives
                'show_in_nav_menus'     => true,
                'delete_with_user'      => false,
                'exclude_from_search'   => true,
                'capability_type'       => 'post',
                'map_meta_cap'          => true,
                'hierarchical'          => false,
                'can_export'            => true,
                'rewrite'               => array(
                    'slug'              => 'support/tickets',
                    'with_front'        => true,
                    'pages'             => true,
                    'feeds'             => false,
                ),
                'query_var'             => 'ticket_id',
                'supports'              => array(
                    'title', 'editor', 'thumbnail', 'excerpt', 
                    'custom-fields', 'revisions', 'author', 'comments'
                ),
                'taxonomies'            => array('hp_category', 'hp_tag', 'hp_ticket_priority'),
            ))
        );

        // Register custom comment types for ticket replies
        if (function_exists('register_comment_type')) {
            // Available since WordPress 5.5
            register_comment_type('ticket_reply', array(
                'label' => __('Ticket Reply', 'helppress-tickets'),
                'public' => true,
                'hierarchical' => false,
            ));
            
            register_comment_type('ticket_status_change', array(
                'label' => __('Ticket Status Change', 'helppress-tickets'),
                'public' => true,
                'hierarchical' => false,
            ));
        }
    }

    /**
     * This function should be added to the class and called during initialization
     * It ensures that comments are enabled on all ticket posts regardless of status
     */
    public function enable_comments_on_tickets() {
        // This ensures comments are enabled on all ticket posts
        add_filter('comments_open', array($this, 'force_comments_open_for_tickets'), 999, 2);
    }

    /**
     * Force comments to be open on ticket posts
     *
     * @since 1.0.0
     * @param bool $open    Whether comments are open
     * @param int  $post_id Post ID
     * @return bool Modified open status
     */
    public function force_comments_open_for_tickets($open, $post_id) {
        if (get_post_type($post_id) === 'hp_ticket') {
            return true;
        }
        
        return $open;
    }

    /**
     * Register custom post statuses for tickets
     *
     * @since 1.0.0
     */
    public function register_post_status() {
        register_post_status('hp_open', array(
            'label'                     => esc_html_x('Open', 'Ticket status', 'helppress-tickets'),
            'public'                    => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Open <span class="count">(%s)</span>', 'Open <span class="count">(%s)</span>', 'helppress-tickets')
        ));
        
        register_post_status('hp_in_progress', array(
            'label'                     => esc_html_x('In Progress', 'Ticket status', 'helppress-tickets'),
            'public'                    => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('In Progress <span class="count">(%s)</span>', 'In Progress <span class="count">(%s)</span>', 'helppress-tickets')
        ));
        
        register_post_status('hp_resolved', array(
            'label'                     => esc_html_x('Resolved', 'Ticket status', 'helppress-tickets'),
            'public'                    => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Resolved <span class="count">(%s)</span>', 'Resolved <span class="count">(%s)</span>', 'helppress-tickets')
        ));
        
        register_post_status('hp_closed', array(
            'label'                     => esc_html_x('Closed', 'Ticket status', 'helppress-tickets'),
            'public'                    => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Closed <span class="count">(%s)</span>', 'Closed <span class="count">(%s)</span>', 'helppress-tickets')
        ));
    }
    
    /**
     * Register taxonomies for tickets
     *
     * @since 1.0.0
     */
    public function register_taxonomies() {
        // Register the ticket priority taxonomy
        register_taxonomy(
            'hp_ticket_priority',
            'hp_ticket',
            apply_filters('helppress_tickets_register_priority_args', array(
                'labels' => array(
                    'name'                  => esc_html_x('Priorities', 'Taxonomy general name', 'helppress-tickets'),
                    'singular_name'         => esc_html_x('Priority', 'Taxonomy singular name', 'helppress-tickets'),
                    'menu_name'             => esc_html__('Priorities', 'helppress-tickets'),
                ),
                'hierarchical'          => true,
                'public'                => true,
                'show_ui'               => true,
                'show_admin_column'     => true,
                'show_in_nav_menus'     => false,
                'show_in_rest'          => true,
                'query_var'             => 'ticket_priority', // Use distinct query var to prevent conflicts
                'rewrite'               => array('slug' => 'ticket-priority'),
            ))
        );
        
        // Add default priorities
        if (!term_exists('Low', 'hp_ticket_priority')) {
            wp_insert_term('Low', 'hp_ticket_priority', array('slug' => 'low'));
        }
        if (!term_exists('Medium', 'hp_ticket_priority')) {
            wp_insert_term('Medium', 'hp_ticket_priority', array('slug' => 'medium'));
        }
        if (!term_exists('High', 'hp_ticket_priority')) {
            wp_insert_term('High', 'hp_ticket_priority', array('slug' => 'high'));
        }
        if (!term_exists('Urgent', 'hp_ticket_priority')) {
            wp_insert_term('Urgent', 'hp_ticket_priority', array('slug' => 'urgent'));
        }
    }

    public function register_article_taxonomies() {
        // Associate existing knowledge base categories with tickets
        register_taxonomy_for_object_type('hp_category', 'hp_ticket');
        
        // Associate existing knowledge base tags with tickets
        register_taxonomy_for_object_type('hp_tag', 'hp_ticket');
    }
    
    /**
     * Add ticket rewrite rules
     *
     * @since 1.0.0
     */
    public function add_ticket_rewrite_rules() {
        // Add rewrite rule for ID-based access (highest priority)
        add_rewrite_rule(
            '^support/tickets/(\d+)/?$',
            'index.php?post_type=hp_ticket&p=$matches[1]',
            'top'
        );
    
        // Add rewrite rule for slug-based access
        add_rewrite_rule(
            '^support/tickets/([^/]+)/?$',
            'index.php?ticket_id=$matches[1]',
            'top'
        );
    
        // Add support for pagination
        add_rewrite_rule(
            '^support/tickets/([^/]+)/page/(\d+)/?$',
            'index.php?ticket_id=$matches[1]&paged=$matches[2]',
            'top'
        );
    
        // Add support for attachments
        add_rewrite_rule(
            '^support/tickets/([^/]+)/([^/]+)/?$',
            'index.php?ticket_id=$matches[1]&attachment=$matches[2]',
            'top'
        );
    
        // Ensure query var is registered
        global $wp;
        if (!in_array('ticket_id', $wp->public_query_vars)) {
            $wp->add_query_var('ticket_id');
        }
    }
    
    /**
     * Force single ticket template
     *
     * @since 1.0.0
     * @param string $template Current template
     * @return string Template path
     */
    public function force_single_ticket_template($template) {
        global $wp_query, $post;
        
        // Check if this is a ticket page by looking at the post type
        if (isset($post) && is_object($post) && $post->post_type === 'hp_ticket') {
            // First check theme directory
            $theme_template = locate_template(array('single-hp_ticket.php'));
            
            if ($theme_template) {
                return $theme_template;
            }
            
            // Otherwise use plugin template
            $plugin_template = HPTICKETS_PATH . 'templates/single-hp_ticket.php';
            
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }

    /**
     * Load single ticket template
     *
     * @since 1.0.0
     * @param string $template Current template
     * @return string Template path
     */
    public function load_single_ticket_template($template) {
        global $post;
        
        if (is_object($post) && $post->post_type === 'hp_ticket') {
            // First check theme directory
            $theme_template = locate_template(array('single-hp_ticket.php'));
            
            if ($theme_template) {
                return $theme_template;
            }
            
            // Otherwise use plugin template
            $plugin_template = HPTICKETS_PATH . 'templates/single-hp_ticket.php';
            
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Load archive ticket template 
     *
     * @since 1.0.0
     * @param string $template Current template
     * @return string Template path
     */
    public function load_archive_ticket_template($template) {
        if (is_post_type_archive('hp_ticket')) {
            // First check theme directory
            $theme_template = locate_template(array('archive-hp_ticket.php'));
            
            if ($theme_template) {
                return $theme_template;
            }
            
            // Otherwise use plugin template
            $plugin_template = HPTICKETS_PATH . 'templates/archive-hp_ticket.php';
            
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Customize ticket permalink structure
     *
     * @since 1.0.0
     * @param string  $permalink The permalink
     * @param WP_Post $post      The post object
     * @return string Modified permalink
     */
    public function ticket_permalink_structure($permalink, $post) {
        if ($post->post_type !== 'hp_ticket') {
            return $permalink;
        }
        
        // Get the base URL
        $base_url = home_url('/support/tickets/');
        
        // Always use ticket ID as primary identifier by default
        $permalink = $base_url . $post->ID . '/';
        
        // If ticket is public (not private) and has title, optionally allow title in URL for SEO
        $is_private = get_post_meta($post->ID, '_hp_ticket_private', true);
        if (!$is_private && $post->post_name && $post->post_name !== 'ticket-' . $post->ID) {
            // Still allow access via ID for backward compatibility
            // But primary URL can use title for public tickets
            $permalink = $base_url . $post->post_name . '/';
        }
        
        return $permalink;
    }
}

// Add a simple function to check for template issues
function helppress_tickets_debug_template() {
    if (!is_admin() && current_user_can('manage_options') && isset($_GET['hp_debug'])) {
        global $wp_query, $post, $template;
        
        echo '<div style="position:fixed; bottom:20px; right:20px; z-index:9999; background:#fff; padding:20px; border:2px solid red; max-width:90%;">';
        echo '<h3>Ticket Debug Info</h3>';
        echo '<p>Current URL: ' . esc_html($_SERVER['REQUEST_URI']) . '</p>';
        echo '<p>Template: ' . esc_html($template) . '</p>';
        echo '<p>Post type: ' . (isset($post) && is_object($post) ? esc_html($post->post_type) : 'None') . '</p>';
        echo '<p>Is singular: ' . (is_singular() ? 'Yes' : 'No') . '</p>';
        echo '<p>Query vars: <pre>' . esc_html(print_r($wp_query->query_vars, true)) . '</pre></p>';
        echo '</div>';
    }
}
add_action('wp_footer', 'helppress_tickets_debug_template');