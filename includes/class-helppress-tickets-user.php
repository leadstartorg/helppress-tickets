<?php
/**
 * User
 *
 * @package HelpPress Tickets
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * User class
 *
 * @since 1.0.0
 */
class HelpPress_Tickets_User {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'show_user_profile', array( $this, 'add_custom_user_fields' ) );
        add_action( 'edit_user_profile', array( $this, 'add_custom_user_fields' ) );
        add_action( 'personal_options_update', array( $this, 'save_custom_user_fields' ) );
        add_action( 'edit_user_profile_update', array( $this, 'save_custom_user_fields' ) );
    }

    /**
     * Add custom user profile fields
     *
     * @since 1.0.0
     * @param WP_User $user User object
     */
    public function add_custom_user_fields( $user ) {
        ?>
        <h3><?php esc_html_e( 'Support Ticket Information', 'helppress-tickets' ); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="phone"><?php esc_html_e( 'Phone Number', 'helppress-tickets' ); ?></label></th>
                <td>
                    <input type="text" name="phone" id="phone" value="<?php echo esc_attr( get_user_meta( $user->ID, 'phone', true ) ); ?>" class="regular-text" />
                    <p class="description"><?php esc_html_e( 'Phone number for support ticket contact.', 'helppress-tickets' ); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save custom user profile fields
     *
     * @since 1.0.0
     * @param int $user_id User ID
     * @return bool|void
     */
    public function save_custom_user_fields( $user_id ) {
        if ( ! current_user_can( 'edit_user', $user_id ) ) {
            return false;
        }

        if ( isset( $_POST['phone'] ) ) {
            update_user_meta( $user_id, 'phone', sanitize_text_field( wp_unslash( $_POST['phone'] ) ) );
        }
    }

    /**
     * Get user data for ticket forms
     *
     * @since 1.0.0
     * @param int $user_id User ID (default: current user)
     * @return array User data
     */
    public static function get_user_data( $user_id = 0 ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }

        if ( ! $user_id ) {
            return array();
        }

        $user = get_userdata( $user_id );
        
        if ( ! $user ) {
            return array();
        }

        $first_name = get_user_meta( $user_id, 'first_name', true );
        $last_name = get_user_meta( $user_id, 'last_name', true );
        $phone = get_user_meta( $user_id, 'phone', true );

        return array(
            'user_id' => $user_id,
            'email' => $user->user_email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $user->display_name,
            'phone' => $phone,
        );
    }

    /**
     * Check if user can access a ticket
     *
     * @since 1.0.0
     * @param int $ticket_id Ticket ID
     * @param int $user_id   User ID (default: current user)
     * @return bool True if user can access ticket
     */
    public static function can_access_ticket( $ticket_id, $user_id = 0 ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }

        if ( ! $user_id ) {
            return false;
        }

        // Get the ticket
        $ticket = get_post( $ticket_id );

        if ( ! $ticket || $ticket->post_type !== 'hp_ticket' ) {
            return false;
        }

        // Admin users can access all tickets
        if ( current_user_can( 'edit_others_posts' ) ) {
            return true;
        }

        // Users can access their own tickets
        if ( $ticket->post_author == $user_id ) {
            return true;
        }

        // Check if user email matches ticket CC
        $user = get_userdata( $user_id );
        $email_cc = get_post_meta( $ticket_id, '_hp_ticket_email_cc', true );
        
        if ( $email_cc && $user && $user->user_email === $email_cc ) {
            return true;
        }

        return false;
    }

    /**
     * Get user tickets count
     *
     * @since 1.0.0
     * @param int    $user_id User ID (default: current user)
     * @param string $status  Ticket status (default: all)
     * @return int Number of tickets
     */
    public static function get_tickets_count( $user_id = 0, $status = '' ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }

        if ( ! $user_id ) {
            return 0;
        }

        $args = array(
            'post_type' => 'hp_ticket',
            'author' => $user_id,
            'posts_per_page' => -1,
            'fields' => 'ids',
        );

        if ( $status ) {
            $args['post_status'] = $status;
        }

        // Add search parameter if provided
        if (!empty($search)) {
            $args['s'] = $search;
        }

        $tickets = get_posts( $args );

        return count( $tickets );
    }
}

// Initialize the class
new HelpPress_Tickets_User();
