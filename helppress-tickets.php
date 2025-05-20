<?php
/**
 * Plugin Name: HelpPress Tickets
 * Description: Ticketing system extension for HelpPress knowledge base
 * Version: 1.0.0
 * Author: Leadstart Media, Inc.
 * License: GPL2+
 * Text Domain: helppress-tickets
 * Requires: HelpPress
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('HPTICKETS_VERSION', '1.0.0');
define('HPTICKETS_FILE', __FILE__);
define('HPTICKETS_PATH', plugin_dir_path(__FILE__));
define('HPTICKETS_URL', plugin_dir_url(__FILE__));
define('HPTICKETS_BASENAME', plugin_basename(__FILE__));

/**
 * Check if HelpPress is active - using multiple methods to be more robust
 *
 * @return bool Whether HelpPress is active and available
 */
function helppress_tickets_check_dependencies() {
    // Check if HelpPress function exists
    if (function_exists('helppress')) {
        return true;
    }
    
    // Check if the class from HelpPress exists
    if (class_exists('HelpPress') || class_exists('HelpPress_Plugin')) {
        return true;
    }
    
    // Check if HelpPress is in the active plugins list
    $active_plugins = (array) get_option('active_plugins', array());
    
    if (in_array('helppress/helppress.php', $active_plugins, true)) {
        return true;
    }
    
    // Check if HelpPress is in the active network plugins list
    if (is_multisite()) {
        $network_plugins = (array) get_site_option('active_sitewide_plugins', array());
        
        if (isset($network_plugins['helppress/helppress.php'])) {
            return true;
        }
    }
    
    // If we get here, HelpPress is not active or not detected
    add_action('admin_notices', 'helppress_tickets_missing_notice');
    return false;
}

/**
 * Display admin notice if HelpPress is not active
 */
function helppress_tickets_missing_notice() {
    ?>
    <div class="error">
        <p><?php esc_html_e('HelpPress Tickets requires HelpPress to be installed and activated.', 'helppress-tickets'); ?></p>
    </div>
    <?php
}

/**
 * Load plugin files
 */
function helppress_tickets_load_files() {
    $file_list = array(
        'includes/class-helppress-tickets-post-types.php',
        'includes/class-helppress-tickets-template-loader.php',
        'includes/class-helppress-tickets-user.php',
        'includes/class-helppress-tickets-kb-conversion.php',
        'includes/class-helppress-tickets-admin-actions.php',
        'includes/class-helppress-tickets-settings.php',
        'includes/class-helppress-tickets-shortcodes.php',
        'includes/class-helppress-tickets-install.php',
        //'includes/class-helppress-tickets-reply.php',
        'includes/class-helppress-tickets-comments.php',
        //'includes/class-helppress-tickets-comment-list.php',
        //'includes/class-helppress-tickets-comment-editing.php',
        //'includes/class-helppress-tickets-comment-attachments.php',
    );
    
    foreach ($file_list as $file) {
        $file_path = HPTICKETS_PATH . $file;
        if (file_exists($file_path)) {
            require_once $file_path;
        } else {
            // Log error if file doesn't exist, but don't output to screen
            //error_log(sprintf('HelpPress Tickets: Could not load file %s', $file_path));
        }
    }
}

/**
 * Initialize plugin classes
 */
function helppress_tickets_init() {
    // Check if all required classes are available
    $required_classes = array(
        'HelpPress_Tickets_Post_Types',
        'HelpPress_Tickets_KB_Conversion',
        'HelpPress_Tickets_Shortcodes',
        //'HelpPress_Tickets_Reply',
        'HelpPress_Tickets_Comments',
    );
    
    $missing_classes = array();
    
    foreach ($required_classes as $class) {
        if (!class_exists($class)) {
            $missing_classes[] = $class;
        }
    }
    
    if (!empty($missing_classes)) {
        // Log error and bail if required classes are missing
        //error_log(sprintf('HelpPress Tickets: Missing required classes: %s', implode(', ', $missing_classes)));
        return;
    }
    
    // Initialize required classes
    new HelpPress_Tickets_Post_Types();
    new HelpPress_Tickets_KB_Conversion();
    new HelpPress_Tickets_Shortcodes();
    //new HelpPress_Tickets_Reply(); 
    new HelpPress_Tickets_Comments(); 
    
    // Fire action for other plugins
    do_action('helppress_tickets_init');
}

// Add to helppress-tickets.php
function helppress_check_required_pages() {
    // Pages to check with consistent option names
    $required_pages = array(
        'helppress_tickets_page_id' => __('Ticket List Page', 'helppress-tickets'),
        'helppress_tickets_submit_page_id' => __('Submit Ticket Page', 'helppress-tickets'),
        'helppress_tickets_edit_page_id' => __('Edit Ticket Page', 'helppress-tickets'),
        'helppress_tickets_status_page_id' => __('Ticket Status Check Page', 'helppress-tickets'),
        'helppress_tickets_single_page_id' => __('Single Ticket View Page', 'helppress-tickets'),
    );
    
    // Also check in options array with both naming conventions
    $option_keys_map = array(
        'helppress_tickets_page_id' => array('page_id', 'list_page'),
        'helppress_tickets_submit_page_id' => array('submit_page_id', 'submit_page'),
        'helppress_tickets_edit_page_id' => array('edit_page_id', 'edit_page'),
        'helppress_tickets_status_page_id' => array('status_page_id', 'status_page'),
        'helppress_tickets_single_page_id' => array('single_page_id', 'single_page'),
    );
    
    $missing_pages = array();
    $options_array = get_option('helppress_tickets_options', array());
    $hp_options = get_option('helppress_options', array());
    
    // Check each required page
    foreach ($required_pages as $option_name => $page_label) {
        $page_id = get_option($option_name);
        
        // If not found directly, try option keys in the options array
        if (!$page_id && isset($option_keys_map[$option_name])) {
            foreach ($option_keys_map[$option_name] as $key) {
                if (isset($options_array[$key])) {
                    $page_id = $options_array[$key];
                    // Update the direct option for future use
                    update_option($option_name, $page_id);
                    break;
                }
            }
        }
        
        // Also check in HelpPress main options array
        if (!$page_id) {
            $settings_key = str_replace('helppress_', '', $option_name);
            if (isset($hp_options[$settings_key])) {
                $page_id = $hp_options[$settings_key];
                // Update the direct option for future use
                update_option($option_name, $page_id);
            }
        }
        
        // Check if page exists
        if (!$page_id || !get_post($page_id)) {
            $missing_pages[$option_name] = $page_label;
        }
    }
    
    // Store results in global for later use
    if (!empty($missing_pages)) {
        $GLOBALS['helppress_missing_pages'] = $missing_pages;
        
        // Add admin notice
        if (is_admin()) {
            add_action('admin_notices', 'helppress_missing_pages_admin_notice');
        }
        
        // Add frontend notice hook
        add_action('wp_body_open', 'helppress_missing_pages_frontend_notice');
    }
}
add_action('init', 'helppress_check_required_pages');

// Admin notice for missing pages
function helppress_missing_pages_admin_notice() {
    $missing_pages = $GLOBALS['helppress_missing_pages'];
    
    echo '<div class="notice notice-error">';
    echo '<p><strong>' . esc_html__('HelpPress Tickets: Required pages are missing or not configured correctly', 'helppress-tickets') . '</strong></p>';
    echo '<ul>';
    
    foreach ($missing_pages as $option_name => $page_label) {
        echo '<li>' . esc_html($page_label) . ' - ' . 
            sprintf(
                __('<a href="%s">Configure in settings</a>', 'helppress-tickets'),
                admin_url('edit.php?post_type=hp_article&page=helppress_options')
            ) . 
            '</li>';
    }
    
    echo '</ul>';
    echo '</div>';
}

// Frontend notice for missing pages
function helppress_missing_pages_frontend_notice() {
    // Only show to administrators
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Get missing pages
    $missing_pages = isset($GLOBALS['helppress_missing_pages']) ? $GLOBALS['helppress_missing_pages'] : array();
    
    if (!empty($missing_pages)) {
        echo '<div class="helppress-admin-notice alert alert-warning">';
        echo '<p><strong>' . esc_html__('HelpPress Tickets Administrator Notice:', 'helppress-tickets') . '</strong> ';
        echo esc_html__('Some required ticket pages are not configured. Please check the admin settings.', 'helppress-tickets');
        echo '</p>';
        echo '</div>';
    }
}

// Updated URL function with safety checks
function helppress_get_ticket_url($ticket_id, $context = 'view') {
    switch ($context) {
        case 'edit':
            // Check if edit page is configured
            $edit_page_id = get_option('helppress_tickets_edit_page_id');

            // Then try the options array
            if (!$edit_page_id) {
                $ticket_options = get_option('helppress_tickets_options', array());
                if (isset($ticket_options['edit_page_id'])) {
                    $edit_page_id = $ticket_options['edit_page_id'];
                }
            }
            
            if ($edit_page_id && get_post($edit_page_id)) {
                return add_query_arg('edit_ticket', $ticket_id, get_permalink($edit_page_id));
            }

            // If we found a valid page ID, use it
            if ($edit_page_id) {
                return add_query_arg('edit_ticket', $ticket_id, get_permalink($edit_page_id));
            }
            
            // If not configured, return to view page with a notice flag
            return add_query_arg('edit_error', '1', helppress_get_ticket_url($ticket_id, 'view'));

            // For admins, use admin edit URL
            if (current_user_can('edit_others_posts')) {
                return get_edit_post_link($ticket_id);
            }

        case 'list':
            // Check if list page is configured
            $list_page_id = get_option('helppress_tickets_page_id');
            
            if ($list_page_id && get_post($list_page_id)) {
                return get_permalink($list_page_id);
            }

            // More specific fallback - get this from options
            $support_page = get_option('helppress_tickets_list_url');
            if($support_page) {
                return home_url($support_page);
            }

            // Fallback to home
            return home_url();
            
        case 'view':
        default:
            if ($ticket_id <= 0) {
                return helppress_get_ticket_url(0, 'list');
            }
            
            // Get the ticket
            $ticket = get_post($ticket_id);
            if (!$ticket || $ticket->post_type !== 'hp_ticket') {
                return helppress_get_ticket_url(0, 'list');
            }
            
            // Use the permalink or consistent URL structure
            return get_permalink($ticket_id) ?: home_url('/support/tickets/' . $ticket_id . '/');        
            
    }
}

// Create a shorthand/alias function for backwards compatibility
function helppress_tickets_get_ticket_url($ticket_id) {
    return helppress_get_ticket_url($ticket_id, 'view');
}

/**
 * Improved function to check if Bootstrap is already loaded
 */
function helppress_check_bootstrap_loaded() {
    $bootstrap_detected = false;
    
    // 1. Check registered script handles
    $bootstrap_js_handles = array(
        'bootstrap', 'bootstrap-js', 'bootstrap-script', 'bootstrap-min',
        'bootstrap-bundle', 'bs-bundle', 'bs-js', 'bs-javascript', 'bs-script'
    );
    
    foreach ($bootstrap_js_handles as $handle) {
        if (wp_script_is($handle, 'registered') || wp_script_is($handle, 'enqueued')) {
            return true; // Bootstrap JS detected
        }
    }
    
    // 2. Check CSS handles - FIX: was using wp_script_is instead of wp_style_is
    $bootstrap_css_handles = array(
        'bootstrap', 'bootstrap-css', 'bootstrap-style', 'bootstrap-min', 
        'bs-css', 'bs-style'
    );
    
    foreach ($bootstrap_css_handles as $handle) {
        if (wp_style_is($handle, 'registered') || wp_style_is($handle, 'enqueued')) {
            return true; // Bootstrap CSS detected
        }
    }
    
    // 3. Check for bootstrap in registered script URLs
    global $wp_scripts;
    if ($wp_scripts) {
        foreach ($wp_scripts->registered as $script) {
            if (isset($script->src) && (
                stripos($script->src, 'bootstrap') !== false ||
                stripos($script->src, '/bs/') !== false ||
                stripos($script->src, '/bs5/') !== false
            )) {
                return true; // Bootstrap pattern found in script URL
            }
        }
    }
    
    // 4. Check for bootstrap in registered style URLs
    global $wp_styles;
    if ($wp_styles) {
        foreach ($wp_styles->registered as $style) {
            if (isset($style->src) && (
                stripos($style->src, 'bootstrap') !== false ||
                stripos($style->src, '/bs/') !== false ||
                stripos($style->src, '/bs5/') !== false
            )) {
                return true; // Bootstrap pattern found in style URL
            }
        }
    }
    
    // 5. Alternative direct check technique (better than the ob_start method)
    add_action('wp_print_footer_scripts', function() use (&$bootstrap_detected) {
        // Set a flag in HTML that we can check for
        echo '<script>window.has_bootstrap = (typeof bootstrap !== "undefined" || typeof jQuery !== "undefined" && typeof jQuery.fn.modal !== "undefined");</script>';
        echo '<div id="bootstrap-detection" data-has-bootstrap="checking" style="display:none;"></div>';
        
        // Check if bootstrap exists by testing functionality
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                var detector = document.getElementById("bootstrap-detection");
                if (window.has_bootstrap) {
                    detector.setAttribute("data-has-bootstrap", "true");
                } else {
                    detector.setAttribute("data-has-bootstrap", "false");
                }
            });
        </script>';
    }, 9999);
    
    // 6. Final check - if not found, load Bootstrap
    if (!$bootstrap_detected) {
        // Return false and let the calling function decide what to do
        return false;
    }
    
    return true;
}

/**
 * Simple check if DataTables is already loaded
 */
function helppress_is_datatables_loaded() {
    // Just check for the most common script handle
    if (wp_script_is('datatables', 'registered') || wp_script_is('datatables', 'enqueued')) {
        return true;
    }
    
    // Quick check of registered scripts for 'datatable' in URL
    global $wp_scripts;
    if ($wp_scripts) {
        foreach ($wp_scripts->registered as $script) {
            if (isset($script->src) && stripos($script->src, 'datatable') !== false) {
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Ensure Dashicons are loaded
 */
function helppress_ensure_dashicons() {
    // Check if dashicons are already registered/enqueued
    if (!wp_style_is('dashicons', 'registered') && !wp_style_is('dashicons', 'enqueued')) {
        // Register and enqueue dashicons
        wp_enqueue_style('dashicons');
    } else if (!wp_style_is('dashicons', 'enqueued') && wp_style_is('dashicons', 'registered')) {
        // If registered but not enqueued, just enqueue them
        wp_enqueue_style('dashicons');
    }
}
add_action('wp_enqueue_scripts', 'helppress_ensure_dashicons');

/**
 * Enqueue scripts and styles
 */
function helppress_tickets_enqueue_scripts() {
    // Check if Bootstrap is already loaded
    $bootstrap_loaded = helppress_check_bootstrap_loaded();
    
    // Load Bootstrap only if not detected
    if (!$bootstrap_loaded) {
        wp_enqueue_script('helppress-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js', array('jquery'), '5.0.2', true);
        wp_enqueue_style('helppress-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css', array(), '5.0.2');
    }
    
    // Main plugin styles and scripts
    wp_enqueue_style('helppress-tickets', HPTICKETS_URL . 'assets/css/helppress-tickets.css', array(), HPTICKETS_VERSION);
    wp_enqueue_script('helppress-tickets', HPTICKETS_URL . 'assets/js/helppress-tickets.js', array('jquery'), HPTICKETS_VERSION, true);

    // Add admin ticket list filter script
    wp_enqueue_script('helppress-admin-ticket-filter', HPTICKETS_URL . 'assets/js/helppress-tickets-admin-list-filter.js', array('jquery', 'helppress-tickets'), HPTICKETS_VERSION, true);

    // Add DataTables
    // Check and load DataTables
    $datatables_loaded = helppress_is_datatables_loaded();

    if (!$helppress_is_datatables_loaded) {
        wp_enqueue_style('datatables', 'https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css', array(), '1.11.5');
        wp_enqueue_script('datatables', 'https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js', array('jquery'), '1.11.5', true);
        wp_enqueue_script('datatables-bootstrap', 'https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js', array('datatables'), '1.11.5', true);
    }
    
    // Add localized script data for AJAX
    wp_localize_script('helppress-tickets', 'helppress_tickets', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('helppress_tickets_nonce'),
        'ticket_base_url' => home_url('/support/tickets/'),
    ));
    
    // Additional localization for ticket list functionality
    wp_localize_script('helppress-tickets', 'helppress_tickets_list', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'list_nonce' => wp_create_nonce('helppress_tickets_list_nonce'),
        'search_nonce' => wp_create_nonce('helppress_tickets_search_nonce'),
        'current_page' => get_query_var('paged') ? get_query_var('paged') : 1,
    ));
    
    // Add translation strings for JS
    wp_localize_script('helppress-tickets', 'helppress_tickets_i18n', array(
        'fill_required' => esc_html__('Please fill in all required fields.', 'helppress-tickets'),
        'file_too_large' => esc_html__('The file is too large. Maximum size is 1MB.', 'helppress-tickets'),
        'invalid_file_type' => esc_html__('Invalid file type. Allowed types: jpg, jpeg, png, pdf, zip.', 'helppress-tickets'),
        'enter_all_fields' => esc_html__('Please enter both ticket ID and email address.', 'helppress-tickets'),
        'loading' => esc_html__('Loading...', 'helppress-tickets'),
        'ticket_found' => esc_html__('Ticket Found', 'helppress-tickets'),
        'ticket_id' => esc_html__('Ticket ID:', 'helppress-tickets'),
        'subject' => esc_html__('Subject:', 'helppress-tickets'),
        'status' => esc_html__('Status:', 'helppress-tickets'),
        'priority' => esc_html__('Priority:', 'helppress-tickets'),
        'last_updated' => esc_html__('Last Updated:', 'helppress-tickets'),
        'view_ticket' => esc_html__('View Ticket Details', 'helppress-tickets'),
        'error_checking' => esc_html__('An error occurred while checking the ticket status. Please try again.', 'helppress-tickets'),
        'confirm_action' => esc_html__('Are you sure you want to perform this action?', 'helppress-tickets'),
    ));
}

/**
 * Redirect old ticket URLs to new structure
 */
function helppress_tickets_redirect_old_urls() {
    // Check if this is a request for an old ticket URL
    if (preg_match('/^ticket\/(\d+)\/?$/', $_SERVER['REQUEST_URI'], $matches)) {
        $ticket_id = $matches[1];
        wp_redirect(home_url('/support/tickets/' . $ticket_id . '/'), 301);
        exit;
    }
    
    // Check for old ticket URL with slug
    if (preg_match('/^ticket\/([^\/]+)\/?$/', $_SERVER['REQUEST_URI'], $matches)) {
        $slug = $matches[1];
        
        // Try to find the ticket by slug
        $args = array(
            'name' => $slug,
            'post_type' => 'hp_ticket',
            'posts_per_page' => 1,
        );
        
        $ticket_query = new WP_Query($args);
        if ($ticket_query->have_posts()) {
            $ticket_query->the_post();
            $ticket_id = get_the_ID();
            wp_reset_postdata();
            
            wp_redirect(home_url('/support/tickets/' . $ticket_id . '/'), 301);
            exit;
        }
    }
}
add_action('template_redirect', 'helppress_tickets_redirect_old_urls');

/**
 * Modify search query to include HelpPress post types only
 *
 * @param WP_Query $query The WordPress query object
 * @return WP_Query Modified query
 */
function helppress_tickets_search_filter($query) {
    // Only modify frontend search when HelpPress search parameter is present
    if (!is_admin() && $query->is_main_query() && $query->is_search() && isset($_GET['hps']) && $_GET['hps'] == '1') {
        $query->set('post_type', array('hp_article', 'hp_ticket'));
    }
    return $query;
}
add_filter('pre_get_posts', 'helppress_tickets_search_filter');

/**
 * Register ticket endpoints for pretty permalinks
 */
function helppress_tickets_add_rewrite_endpoints() {
    // Add support for /ticket/ID/ URL format
    add_rewrite_rule(
        'ticket/([0-9]+)/?$',
        'index.php?post_type=hp_ticket&p=$matches[1]',
        'top'
    );
    
    // Support for /ticket/?ticket_id=ID format as fallback
    add_rewrite_tag('%ticket_id%', '([0-9]+)');
    add_rewrite_rule(
        'ticket/?$',
        'index.php?pagename=ticket&ticket_id=$matches[1]',
        'top'
    );
    
    flush_rewrite_rules();
}

/**
 * Load plugin textdomain
 */
function helppress_tickets_load_textdomain() {
    load_plugin_textdomain('helppress-tickets', false, dirname(HPTICKETS_BASENAME) . '/languages/');
}

/**
 * Plugin activation hook
 */
function helppress_tickets_activate() {
    // Check dependencies but won't disable the plugin yet
    helppress_tickets_check_dependencies();
    
    // Load required files
    helppress_tickets_load_files();
    
    // If installation class exists, run activation method
    if (class_exists('HelpPress_Tickets_Install')) {
        $installer = new HelpPress_Tickets_Install();
        $installer->activate();
    }
    
    // Register post types to make sure they're available
    if (class_exists('HelpPress_Tickets_Post_Types')) {
        $post_types = new HelpPress_Tickets_Post_Types();
        $post_types->register_post_types();
        $post_types->register_post_status();
        $post_types->register_taxonomies();
        $post_types->register_article_taxonomies();
    }
    
    // Store a flag indicating rewrite rules need to be flushed
    update_option('helppress_tickets_flush_rewrite_rules', 'yes');
    
    // Flush rewrite rules on activation
    flush_rewrite_rules();
}

/**
 * Plugin deactivation hook
 */
function helppress_tickets_deactivate() {
    // Load required files
    helppress_tickets_load_files();
    
    // If installation class exists, run deactivation method
    if (class_exists('HelpPress_Tickets_Install')) {
        $installer = new HelpPress_Tickets_Install();
        $installer->deactivate();
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'helppress_tickets_activate');
register_deactivation_hook(__FILE__, 'helppress_tickets_deactivate');

// Check dependencies
add_action('plugins_loaded', 'helppress_tickets_load_files');

// Initialize plugin if dependencies are met
add_action('plugins_loaded', function() {
    if (helppress_tickets_check_dependencies()) {
        // Initialize plugin
        add_action('init', 'helppress_tickets_init', 0);
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', 'helppress_tickets_enqueue_scripts');
        
        // Load text domain
        add_action('plugins_loaded', 'helppress_tickets_load_textdomain');
    }
}, 20); // Priority 20 to ensure HelpPress is loaded first

/**
 * Remove The Events Calendar hooks that interfere with ticket queries
 */
function remove_tec_hooks_for_tickets() {
    global $wp_query;
    
    // If this is a ticket page, remove The Events Calendar hooks
    if (isset($_GET['ticket_id']) || 
        (isset($wp_query->query_vars['post_type']) && 
         $wp_query->query_vars['post_type'] === 'hp_ticket')) {
        
        // Remove specific TEC hooks that might interfere
        if (class_exists('Tribe__Events__Query')) {
            remove_action('parse_query', array('Tribe__Events__Query', 'parse_query'), 50);
            remove_filter('posts_results', array('Tribe__Events__Query', 'posts_results'));
        }
    }
}
add_action('wp', 'remove_tec_hooks_for_tickets', 5);

/**
 * Direct database approach to set taxonomy terms
 * 
 * @param int $object_id The post ID
 * @param int $term_id The term ID
 * @param string $taxonomy The taxonomy name
 * @return bool Success or failure
 */
function set_taxonomy_term_direct($object_id, $term_id, $taxonomy) {
    global $wpdb;
    
    // Get the term taxonomy ID
    $term_taxonomy_id = $wpdb->get_var($wpdb->prepare(
        "SELECT tt.term_taxonomy_id FROM {$wpdb->term_taxonomy} tt 
        WHERE tt.term_id = %d AND tt.taxonomy = %s",
        $term_id,
        $taxonomy
    ));
    
    if (!$term_taxonomy_id) {
        //error_log("HelpPress Tickets: Term taxonomy ID not found for term {$term_id}");
        return false;
    }
    
    // First remove existing relationships for this taxonomy
    $wpdb->delete(
        $wpdb->term_relationships,
        array('object_id' => $object_id),
        array('%d')
    );
    
    // Insert the new relationship
    $result = $wpdb->insert(
        $wpdb->term_relationships,
        array(
            'object_id' => $object_id,
            'term_taxonomy_id' => $term_taxonomy_id,
            'term_order' => 0
        ),
        array('%d', '%d', '%d')
    );
    
    if ($result) {
        // Update term count
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->term_taxonomy} SET count = count + 1 
            WHERE term_taxonomy_id = %d",
            $term_taxonomy_id
        ));
        
        // Clear relevant caches
        clean_post_cache($object_id);
        clean_term_cache($term_id, $taxonomy);
        
        //error_log("HelpPress Tickets: Direct DB term setting succeeded for {$taxonomy}");
        return true;
    }
    
    //error_log("HelpPress Tickets: Direct DB term setting failed: " . $wpdb->last_error);
    return false;
}

/**
 * Taxonomy capability handler for tickets
 * Handles capability checks, term creation, and assignment
 * in a single, focused function
 *
 * @since 1.0.0
 */
function helppress_fix_ticket_taxonomy_capabilities() {
    // STAGE 1: CONTEXT VALIDATION
    // Only apply to logged-in users
    if (!is_user_logged_in()) {
        return;
    }
    
    // Define what constitutes a ticket context
    $ticket_context = false;
    $ticket_id = 0;
    $ticket_owner = false;
    
    // Check edit screen context
    if (isset($_GET['edit_ticket'])) {
        $ticket_id = absint($_GET['edit_ticket']);
        $ticket = get_post($ticket_id);
        if ($ticket && $ticket->post_type === 'hp_ticket') {
            $ticket_context = true;
            $ticket_owner = ($ticket->post_author == get_current_user_id());
        }
    }
    
    // Check form submission context
    if ((isset($_POST['helppress_update_ticket']) || isset($_POST['helppress_submit_ticket'])) && isset($_POST['ticket_id'])) {
        $ticket_id = absint($_POST['ticket_id']);
        $ticket = get_post($ticket_id);
        if ($ticket && $ticket->post_type === 'hp_ticket') {
            $ticket_context = true;
            $ticket_owner = ($ticket->post_author == get_current_user_id());
        }
    }
    
    // Only proceed if we're in a ticket context AND the user owns the ticket
    // (or is an admin, which is automatically allowed by WordPress)
    if (!$ticket_context || !$ticket_owner) {
        return;
    }
    
    // STAGE 2: CAPABILITY GRANTS
    // This comprehensive filter handles all taxonomy capability checks
    // for users editing their own tickets
    add_filter('user_has_cap', function($allcaps, $caps, $args, $user) use ($ticket_id) {
        // Only process capability checks for taxonomy operations
        if (isset($args[0]) && in_array($args[0], ['assign_terms', 'edit_terms', 'manage_terms'])) {
            // Only for our specific taxonomies
            if (isset($args[2]) && in_array($args[2], ['hp_category', 'hp_tag', 'hp_ticket_priority'])) {
                // Grant all necessary capabilities
                $allcaps[$args[0]] = true;
                
                // Also grant article-specific capabilities for shared taxonomies
                $allcaps['assign_hp_article_terms'] = true;
                $allcaps['edit_hp_article_terms'] = true;
                $allcaps['manage_hp_article_terms'] = true;
                
                // For explicit taxonomy-specific caps (in case they're checked)
                $allcaps['assign_' . $args[2]] = true;
                $allcaps['edit_' . $args[2]] = true;
                $allcaps['manage_' . $args[2]] = true;
                
                // For backwards compatibility with older WordPress versions
                $allcaps['edit_posts'] = true;
            }
        }

        // Add this segment within the filter for user_has_cap
        if (isset($args[0]) && $args[0] === 'create_terms' && isset($args[2]) && $args[2] === 'hp_tag') {
            // Always allow tag creation for ticket owners
            if ($ticket_owner) {
                $allcaps['create_terms'] = true;
            }
        }
        
        return $allcaps;
    }, 999, 4);
    
    // STAGE 3: META CAPABILITY MAPPING
    // This ensures capabilities are properly mapped for taxonomy operations
    add_filter('map_meta_cap', function($caps, $cap, $user_id, $args) use ($ticket_id, $ticket_owner) {
        // Only for taxonomy operations
        if (in_array($cap, ['assign_terms', 'edit_terms', 'manage_terms'])) {
            // Check if this is for our taxonomies
            if (isset($args[0]) && in_array($args[0], ['hp_category', 'hp_tag', 'hp_ticket_priority'])) {
                // Only for the ticket owner
                if ($ticket_owner) {
                    return ['exist']; // Grant permission by returning a capability all users have
                }
            }
        }
        
        return $caps;
    }, 999, 4);
    
    // STAGE 4: TERM CREATION HANDLING
    // This allows users to create new terms (tags)
    add_filter('pre_insert_term', function($term, $taxonomy) use ($ticket_owner) {
        // Only allow term creation for specific taxonomies and if user owns the ticket
        if ($ticket_owner && in_array($taxonomy, ['hp_tag'])) {
            // Log the operation for debugging
            //error_log("HelpPress Tickets: Allowing tag creation: {$term}");
            
            // Return the term unchanged to allow creation
            return $term;
        }
        
        return $term;
    }, 10, 2);
    
    // STAGE 5: FORM PROCESSING
    // If this is a form submission, add special handling for taxonomy updates
    if (isset($_POST['helppress_update_ticket']) || isset($_POST['helppress_submit_ticket'])) {
        // This runs late in the process to handle the tag/category setting
        add_action('save_post', function($post_id, $post, $update) use ($ticket_id) {
            // Only process for our specific ticket being updated
            if ($post_id != $ticket_id || !$update) {
                return;
            }
            
            // Process priority selection if provided
            if (isset($_POST['ticket_priority']) && !empty($_POST['ticket_priority'])) {
                $priority_id = absint($_POST['ticket_priority']);
                
                // Attempt standard method first
                $result = wp_set_object_terms($ticket_id, array($priority_id), 'hp_ticket_priority');
                
                // If standard method fails, fallback to direct DB approach
                if (is_wp_error($result)) {
                    //error_log("HelpPress Tickets: Standard priority update failed: " . $result->get_error_message());
                    
                    // Only use direct DB approach as a last resort
                    set_taxonomy_term_direct($ticket_id, $priority_id, 'hp_ticket_priority');
                }
            }
        }, 100, 3); // Priority 100 to run after standard save_post actions
    }
    
    // Log success for debugging
    //error_log("HelpPress Tickets: Applied taxonomy capability fixes for ticket #{$ticket_id}");
}
// Add this function to the init hook with high priority
add_action('init', 'helppress_fix_ticket_taxonomy_capabilities', 999);

/**
 * Sanitize tag input 
 */
function helppress_sanitize_tag_input($tags_input) {
    // Sanitize the whole string first
    $sanitized = sanitize_text_field(wp_unslash($tags_input));
    
    // Split, clean and return
    $tags = array_map('trim', explode(',', $sanitized));
    $tags = array_filter($tags); // Remove empty values
    
    return implode(', ', $tags);
}

function helppress_modify_comment_form_logged_in($defaults) {
    // Only modify for ticket post type
    if (!is_singular('hp_ticket')) {
        return $defaults;
    }
    
    if (isset($defaults['logged_in_as'])) {
        $user = wp_get_current_user();
        $logout_url = wp_logout_url(get_permalink());
        
        $defaults['logged_in_as'] = sprintf(
            '<p class="logged-in-as">%s <a href="%s">%s</a> <span class="required-field-message">%s</span></p>',
            sprintf(__('Logged in as %s.', 'helppress-tickets'), $user->display_name),
            $logout_url,
            __('Log out?', 'helppress-tickets'),
            $defaults['required_field_message']
        );
    }
    
    return $defaults;
}
add_filter('comment_form_defaults', 'helppress_modify_comment_form_logged_in');

/**
 * Add comment positioning fix script
 */
function helppress_tickets_comment_positioning_script() {
    // Only add on single ticket pages
    if (!is_singular('hp_ticket')) {
        return;
    }
    
    // Add inline script with proper dependency on jQuery
    $script = '
    jQuery(document).ready(function($) {
        // Find the target containers
        const ticketContentColumn = $(".single-hp_ticket .helppress-single-ticket-content");
        const commentFormWrapper = $(".single-hp_ticket .comment-form-wrapper");
        const sidebarColumn = $(".single-hp_ticket .helppress-single-ticket-aside");
        
        // Verify sidebar column has ticket details
        let isSidebarValid = false;
        if (sidebarColumn.length) {
            const ticketIdHeader = sidebarColumn.find("th[scope=\'row\']:contains(\'Ticket ID:\')");
            if (ticketIdHeader.length) {
                isSidebarValid = true;
            }
        }
        
        // Move elements if all required elements are found
        if (ticketContentColumn.length && commentFormWrapper.length && isSidebarValid) {
            // Move comment form to end of content column
            ticketContentColumn.append(commentFormWrapper);
            
            // Move sidebar after the content column for better mobile layout
            ticketContentColumn.after(sidebarColumn);
            
            //console.log("HelpPress: Comment positioning adjusted");
        }
    });
    ';
    
    // Add inline script with jQuery dependency
    wp_add_inline_script('jquery', $script);
}
add_action('wp_enqueue_scripts', 'helppress_tickets_comment_positioning_script', 99);

/**
 * Check if rewrite rules need to be flushed
 */
function helppress_tickets_check_flush_rewrite() {
    // Check if we need to flush
    if (get_option('helppress_tickets_flush_rewrite_rules') === 'yes') {
        // Register post types to ensure they're available
        if (class_exists('HelpPress_Tickets_Post_Types')) {
            $post_types = new HelpPress_Tickets_Post_Types();
            $post_types->register_post_types();
            $post_types->add_ticket_rewrite_rules();
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Update the flag
        update_option('helppress_tickets_flush_rewrite_rules', 'no');
    }
}
add_action('init', 'helppress_tickets_check_flush_rewrite', 20);

/**
 * Update ticket permalinks to use the new structure
 */
function helppress_tickets_update_permalinks() {
    global $wpdb;
    
    // Get all tickets
    $tickets = $wpdb->get_results(
        "SELECT ID FROM $wpdb->posts WHERE post_type = 'hp_ticket'"
    );
    
    if (!empty($tickets)) {
        foreach ($tickets as $ticket) {
            // Update permalink structure
            $post_name = wp_unique_post_slug(
                sanitize_title(get_the_title($ticket->ID)),
                $ticket->ID,
                'publish',
                'hp_ticket',
                0
            );
            
            // Update post name/slug
            $wpdb->update(
                $wpdb->posts,
                array('post_name' => $post_name),
                array('ID' => $ticket->ID)
            );
        }
    }
    
    // Flush rewrite rules after updating permalinks
    update_option('helppress_tickets_flush_rewrite_rules', 'yes');
}

