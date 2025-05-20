<?php
/**
 * Tickets Comment List Admin
 *
 * @package HelpPress Tickets
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Comment List class
 */
class HelpPress_Tickets_Comment_List {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_ticket_replies_menu'));
    }

    /**
     * Add ticket replies admin menu
     */
    public function add_ticket_replies_menu() {
        add_submenu_page(
            'edit.php?post_type=hp_article',
            __('Ticket Replies', 'helppress-tickets'),
            __('Ticket Replies', 'helppress-tickets'),
            'moderate_comments',
            'ticket-replies',
            array($this, 'render_ticket_replies_page')
        );
    }

    /**
     * Render ticket replies admin page
     */
    public function render_ticket_replies_page() {
        $comment_type = isset($_GET['comment_type']) ? sanitize_text_field($_GET['comment_type']) : 'ticket_reply';
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Ticket Replies', 'helppress-tickets'); ?></h1>
            
            <ul class="subsubsub">
                <li>
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=hp_article&page=ticket-replies&comment_type=ticket_reply')); ?>" 
                       class="<?php echo $comment_type === 'ticket_reply' ? 'current' : ''; ?>">
                        <?php esc_html_e('Replies', 'helppress-tickets'); ?>
                    </a> |
                </li>
                <li>
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=hp_article&page=ticket-replies&comment_type=ticket_status_change')); ?>" 
                       class="<?php echo $comment_type === 'ticket_status_change' ? 'current' : ''; ?>">
                        <?php esc_html_e('Status Changes', 'helppress-tickets'); ?>
                    </a>
                </li>
            </ul>
            
            <?php
            // Setup custom comment list in WP List Table
            require_once(ABSPATH . 'wp-admin/includes/class-wp-comments-list-table.php');
            
            // Set screen options for the comments table
            set_current_screen('ticket-replies');
            
            // Set up the list table
            $comments_table = new WP_Comments_List_Table(array('screen' => 'ticket-replies'));
            
            // Add hidden fields for filtration
            $comments_table->prepare_items();
            
            // Add comment type filter
            add_filter('comments_clauses', function($clauses) use ($comment_type) {
                global $wpdb;
                $clauses['where'] .= $wpdb->prepare(" AND comment_type = %s", $comment_type);
                return $clauses;
            });
            
            ?>
            <form id="comments-form" method="get">
                <input type="hidden" name="post_type" value="hp_article" />
                <input type="hidden" name="page" value="ticket-replies" />
                <input type="hidden" name="comment_type" value="<?php echo esc_attr($comment_type); ?>" />
                
                <?php $comments_table->display(); ?>
            </form>
        </div>
        <?php
    }
}

// Initialize the class
new HelpPress_Tickets_Comment_List();