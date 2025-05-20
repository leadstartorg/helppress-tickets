<?php
/**
 * KB Conversion
 *
 * @package HelpPress Tickets
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * KB Conversion class
 *
 * @since 1.0.0
 */
class HelpPress_Tickets_KB_Conversion {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'save_post_hp_ticket', array( $this, 'maybe_convert_to_kb_article' ), 20, 3 );
        add_action( 'transition_post_status', array( $this, 'check_status_transition' ), 10, 3 );
        add_action( 'add_meta_boxes', array( $this, 'add_conversion_meta_box' ) );
        add_action( 'admin_post_convert_ticket_to_kb', array( $this, 'handle_manual_conversion' ) );
    }

    /**
     * Add conversion meta box
     *
     * @since 1.0.0
     */
    public function add_conversion_meta_box() {
        add_meta_box(
            'hp_ticket_to_kb',
            esc_html__( 'Convert to Knowledge Base Article', 'helppress-tickets' ),
            array( $this, 'render_conversion_meta_box' ),
            'hp_ticket',
            'side',
            'default'
        );
    }

    /**
     * Render conversion meta box
     *
     * @since 1.0.0
     * @param WP_Post $post Post object
     */
    public function render_conversion_meta_box( $post ) {
        // Check if already converted
        $kb_article_id = get_post_meta( $post->ID, '_hp_converted_to_kb_article', true );
        
        if ( $kb_article_id ) {
            $article_link = get_permalink( $kb_article_id );
            ?>
            <p><?php esc_html_e( 'This ticket has been converted to a KB article:', 'helppress-tickets' ); ?></p>
            <p><a href="<?php echo esc_url( $article_link ); ?>" target="_blank"><?php esc_html_e( 'View Article', 'helppress-tickets' ); ?></a> | 
               <a href="<?php echo esc_url( get_edit_post_link( $kb_article_id ) ); ?>"><?php esc_html_e( 'Edit Article', 'helppress-tickets' ); ?></a></p>
            <?php
        } else {
            // Display conversion form
            wp_nonce_field( 'hp_convert_ticket_to_kb', 'hp_convert_ticket_to_kb_nonce' );
            ?>
            <p><?php esc_html_e( 'Convert this ticket to a knowledge base article to share the solution publicly.', 'helppress-tickets' ); ?></p>
            
            <p>
                <label>
                    <input type="checkbox" name="hp_convert_to_kb" value="1" />
                    <?php esc_html_e( 'Convert to KB article', 'helppress-tickets' ); ?>
                </label>
            </p>
            
            <p>
                <label for="hp_kb_title"><?php esc_html_e( 'Article Title:', 'helppress-tickets' ); ?></label><br>
                <input type="text" id="hp_kb_title" name="hp_kb_title" value="<?php echo esc_attr( $post->post_title ); ?>" class="widefat">
            </p>
            
            <p><?php esc_html_e( 'Article content will be based on ticket replies.', 'helppress-tickets' ); ?></p>
            
            <p>
                <button type="submit" class="button button-primary" name="hp_manual_convert_to_kb">
                    <?php esc_html_e( 'Convert Now', 'helppress-tickets' ); ?>
                </button>
            </p>
            <?php
        }
    }

    /**
     * Check if a ticket should be converted to KB article on save
     *
     * @since 1.0.0
     * @param int     $post_id Post ID
     * @param WP_Post $post    Post object
     * @param bool    $update  Whether this is an update
     */
    public function maybe_convert_to_kb_article( $post_id, $post, $update ) {
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
        
        // Check if we should convert
        if ( ! isset( $_POST['hp_convert_to_kb'] ) || ! $_POST['hp_convert_to_kb'] ) {
            return;
        }
        
        // Verify nonce
        if ( ! isset( $_POST['hp_convert_ticket_to_kb_nonce'] ) || ! wp_verify_nonce( 
            sanitize_text_field( wp_unslash( $_POST['hp_convert_ticket_to_kb_nonce'] ) ), 
            'hp_convert_ticket_to_kb' 
        ) ) {
            return;
        }
        
        // Check if ticket is private
        $is_private = get_post_meta( $post_id, '_hp_ticket_private', true );
        
        if ( $is_private ) {
            return;
        }
        
        // Convert the ticket
        $this->convert_ticket_to_kb_article( $post_id );
    }
    
    /**
     * Handle manual conversion through admin action
     *
     * @since 1.0.0
     */
    public function handle_manual_conversion() {
        // Check permission
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( esc_html__( 'You do not have permission to do this.', 'helppress-tickets' ) );
        }
        
        // Get ticket ID
        $ticket_id = isset( $_GET['ticket_id'] ) ? intval( $_GET['ticket_id'] ) : 0;
        
        if ( ! $ticket_id ) {
            wp_die( esc_html__( 'No ticket specified.', 'helppress-tickets' ) );
        }
        
        // Verify nonce
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( 
            sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 
            'convert_ticket_' . $ticket_id 
        ) ) {
            wp_die( esc_html__( 'Security check failed.', 'helppress-tickets' ) );
        }
        
        // Convert the ticket
        $article_id = $this->convert_ticket_to_kb_article( $ticket_id );
        
        if ( $article_id ) {
            // Redirect to the new article
            wp_safe_redirect( get_edit_post_link( $article_id, 'redirect' ) );
            exit;
        } else {
            // Redirect back to ticket
            wp_safe_redirect( get_edit_post_link( $ticket_id, 'redirect' ) );
            exit;
        }
    }
    
    /**
     * Check status transition for automatic conversion
     *
     * @since 1.0.0
     * @param string  $new_status New status
     * @param string  $old_status Old status
     * @param WP_Post $post       Post object
     */
    public function check_status_transition( $new_status, $old_status, $post ) {
        // Only proceed if this is a ticket
        if ( $post->post_type !== 'hp_ticket' ) {
            return;
        }
        
        // Check if we're transitioning to resolved/closed
        if ( ( $new_status === 'hp_resolved' || $new_status === 'hp_closed' ) && $old_status !== 'hp_resolved' && $old_status !== 'hp_closed' ) {
            // Check if ticket is private
            $is_private = get_post_meta( $post->ID, '_hp_ticket_private', true );
            
            if ( ! $is_private ) {
                // Check if auto-convert is enabled in settings
                $auto_convert = apply_filters( 'helppress_tickets_auto_convert_to_kb', false );
                
                if ( $auto_convert ) {
                    $this->convert_ticket_to_kb_article( $post->ID );
                }
            }
        }
    }
    
    /**
     * Convert a ticket to a KB article
     *
     * @since 1.0.0
     * @param int $ticket_id Ticket ID
     * @return int|false The KB article ID or false on failure
     */
    public function convert_ticket_to_kb_article( $ticket_id ) {
        // Check if already converted
        $existing_article = get_post_meta( $ticket_id, '_hp_converted_to_kb_article', true );
        
        if ( $existing_article ) {
            return $existing_article;
        }
        
        // Get the ticket
        $ticket = get_post( $ticket_id );
        
        if ( ! $ticket || $ticket->post_type !== 'hp_ticket' ) {
            return false;
        }
        
        // Prepare article content
        $content = $this->prepare_ticket_content_for_kb( $ticket_id );
        
        // Get title from form submission or use ticket title
        $title = isset( $_POST['hp_kb_title'] ) ? sanitize_text_field( wp_unslash( $_POST['hp_kb_title'] ) ) : $ticket->post_title;
        
        // Create KB article
        $article_data = array(
            'post_title'    => $title,
            'post_content'  => $content,
            'post_status'   => 'publish',
            'post_type'     => 'hp_article',
            'post_author'   => $ticket->post_author,
        );
        
        $article_id = wp_insert_post( $article_data );
        
        if ( is_wp_error( $article_id ) ) {
            return false;
        }
        
        // Copy categories and tags
        $taxonomies = array( 'hp_category', 'hp_tag' );
        
        foreach ( $taxonomies as $taxonomy ) {
            $terms = wp_get_object_terms( $ticket_id, $taxonomy, array( 'fields' => 'ids' ) );
            
            if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                wp_set_object_terms( $article_id, $terms, $taxonomy );
            }
        }
        
        // Link ticket with article
        update_post_meta( $ticket_id, '_hp_converted_to_kb_article', $article_id );
        update_post_meta( $article_id, '_hp_converted_from_ticket', $ticket_id );
        
        // Add ticket ID as comment on the article
        wp_insert_comment( array(
            'comment_post_ID' => $article_id,
            /* translators: %d: ticket ID */
            'comment_content' => sprintf( esc_html__( 'This article was created from ticket #%d.', 'helppress-tickets' ), $ticket_id ),
            'comment_type' => 'helppress_ticket_conversion',
            'comment_author' => 'HelpPress',
            'comment_approved' => 1,
        ) );
        
        return $article_id;
    }
    
    /**
     * Prepare ticket content for KB article
     *
     * @since 1.0.0
     * @param int $ticket_id Ticket ID
     * @return string The formatted content
     */
    protected function prepare_ticket_content_for_kb( $ticket_id ) {
        $ticket = get_post( $ticket_id );
        $content = $ticket->post_content;
        
        // Add ticket comments/replies to the content
        $comments = get_comments( array(
            'post_id' => $ticket_id,
            'status' => 'approve',
            'order' => 'ASC',
        ) );
        
        if ( ! empty( $comments ) ) {
            // Add a heading for the solution
            $content .= "\n\n<h2>" . esc_html__( 'Solution', 'helppress-tickets' ) . "</h2>\n\n";
            
            // Process each comment for the solution
            foreach ( $comments as $comment ) {
                // Skip customer comments, only include team responses
                if ( ! user_can( $comment->user_id, 'edit_posts' ) ) {
                    continue;
                }
                
                // Format and add comment content
                $comment_content = wpautop( $comment->comment_content );
                $content .= $comment_content . "\n\n";
            }
        }
        
        // Allow filtering of the prepared content
        return apply_filters( 'helppress_tickets_kb_article_content', $content, $ticket_id );
    }
}

// Initialize the class
new HelpPress_Tickets_KB_Conversion();
