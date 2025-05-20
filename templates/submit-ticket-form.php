<?php
/**
 * Template: Submit Ticket Form
 *
 * @package HelpPress Tickets
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get user data
$user_data = HelpPress_Tickets_User::get_user_data();
?>

<div class="helppress-tickets helppress-tickets-form">
    <form class="helppress-tickets-submit-form" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field( 'helppress_submit_ticket', 'helppress_ticket_nonce' ); ?>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="ticket_first_name" class="form-label"><?php esc_html_e( 'First Name', 'helppress-tickets' ); ?></label>
                <input type="text" class="form-control" id="ticket_first_name" name="ticket_first_name" value="<?php echo esc_attr( $user_data['first_name'] ); ?>" required>
            </div>
            
            <div class="col-md-6 mb-3">
                <label for="ticket_last_name" class="form-label"><?php esc_html_e( 'Last Name', 'helppress-tickets' ); ?></label>
                <input type="text" class="form-control" id="ticket_last_name" name="ticket_last_name" value="<?php echo esc_attr( $user_data['last_name'] ); ?>" required>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="ticket_email" class="form-label"><?php esc_html_e( 'Email', 'helppress-tickets' ); ?></label>
                <input type="email" class="form-control" id="ticket_email" name="ticket_email" value="<?php echo esc_attr( $user_data['email'] ); ?>" readonly>
                <div class="form-text"><?php esc_html_e( 'This is your registered email address', 'helppress-tickets' ); ?></div>
            </div>
            
            <div class="col-md-6 mb-3">
                <label for="ticket_email_cc" class="form-label"><?php esc_html_e( 'CC Email', 'helppress-tickets' ); ?></label>
                <input type="email" class="form-control" id="ticket_email_cc" name="ticket_email_cc" placeholder="<?php esc_attr_e( 'Additional email to receive updates', 'helppress-tickets' ); ?>">
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12 mb-3">
                <label for="ticket_subject" class="form-label"><?php esc_html_e( 'Subject', 'helppress-tickets' ); ?></label>
                <input type="text" class="form-control" id="ticket_subject" name="ticket_subject" required>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12 mb-4">
                <label for="ticket_message" class="form-label"><?php esc_html_e( 'Message', 'helppress-tickets' ); ?></label>
                <?php
                // Use WordPress editor
                wp_editor( '', 'ticket_message', array(
                    'textarea_name' => 'ticket_message',
                    'textarea_rows' => 10,
                    'media_buttons' => false,
                    'teeny' => true,
                    'quicktags' => true,
                ) );
                ?>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="alert alert-info mb-4">
    <i class="dashicons dashicons-info"></i>
    <?php esc_html_e('Note: Category and tags cannot be modified after ticket creation. Please choose carefully.', 'helppress-tickets'); ?>
</div>
                <label for="ticket_category" class="form-label"><?php esc_html_e( 'Category', 'helppress-tickets' ); ?></label>
                <select class="form-select" id="ticket_category" name="ticket_category">
                    <option value=""><?php esc_html_e( 'Select a category', 'helppress-tickets' ); ?></option>
                    <?php
                    $categories = get_terms( array(
                        'taxonomy' => 'hp_category',
                        'hide_empty' => false,
                    ) );
                    
                    if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
                        foreach ( $categories as $category ) {
                            printf(
                                '<option value="%s">%s</option>',
                                esc_attr( $category->term_id ),
                                esc_html( $category->name )
                            );
                        }
                    }
                    ?>
                </select>
            </div>
            
            <div class="col-md-6 mb-3">
                <label for="ticket_tags" class="form-label"><?php esc_html_e( 'Tags', 'helppress-tickets' ); ?></label>
                <input type="text" class="form-control" id="ticket_tags" name="ticket_tags" placeholder="<?php esc_attr_e( 'Comma-separated keywords', 'helppress-tickets' ); ?>">
                <div class="form-text"><?php esc_html_e('Tags can only be set during ticket creation', 'helppress-tickets'); ?></div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="ticket_phone" class="form-label"><?php esc_html_e( 'Phone', 'helppress-tickets' ); ?></label>
                <input type="tel" class="form-control" id="ticket_phone" name="ticket_phone" value="<?php echo esc_attr( $user_data['phone'] ); ?>">
            </div>
            
            <div class="col-md-6 mb-3">
                <label for="ticket_priority" class="form-label"><?php esc_html_e( 'Priority', 'helppress-tickets' ); ?></label>
                <select class="form-select" id="ticket_priority" name="ticket_priority">
                    <option value=""><?php esc_html_e( 'Select priority', 'helppress-tickets' ); ?></option>
                    <?php
                    $priorities = get_terms( array(
                        'taxonomy' => 'hp_ticket_priority',
                        'hide_empty' => false,
                    ) );
                    
                    if ( ! empty( $priorities ) && ! is_wp_error( $priorities ) ) {
                        foreach ( $priorities as $priority ) {
                            printf(
                                '<option value="%s">%s</option>',
                                esc_attr( $priority->term_id ),
                                esc_html( $priority->name )
                            );
                        }
                    }
                    ?>
                </select>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="ticket_attachment" class="form-label"><?php esc_html_e( 'Attachment', 'helppress-tickets' ); ?></label>
                <input type="file" class="form-control" id="ticket_attachment" name="ticket_attachment">
                <div class="form-text"><?php esc_html_e( 'Max file size: 1MB. Allowed formats: jpg, jpeg, png, pdf, zip', 'helppress-tickets' ); ?></div>
            </div>
            
            <div class="col-md-6 mb-3">
                <label for="ticket_due_date" class="form-label"><?php esc_html_e( 'Due Date', 'helppress-tickets' ); ?> <span class="text-muted"><?php esc_html_e( '(Optional)', 'helppress-tickets' ); ?></span></label>
                <input type="date" class="form-control" id="ticket_due_date" name="ticket_due_date">
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="ticket_private" name="ticket_private" value="1">
                    <label class="form-check-label" for="ticket_private">
                        <?php esc_html_e( 'Make this ticket private (will not be converted to knowledgebase article)', 'helppress-tickets' ); ?>
                    </label>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <input type="hidden" name="redirect" value="<?php echo isset( $atts['redirect'] ) ? esc_attr( $atts['redirect'] ) : ''; ?>">
                <button type="submit" name="helppress_submit_ticket" class="btn btn-primary"><?php esc_html_e( 'Submit Ticket', 'helppress-tickets' ); ?></button>
            </div>
        </div>
    </form>
</div>
