<?php
/**
 * Template: Ticket Status Form
 *
 * @package HelpPress Tickets
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="helppress-tickets helppress-ticket-status-form">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0"><?php esc_html_e( 'Check Ticket Status', 'helppress-tickets' ); ?></h4>
        </div>
        <div class="card-body">
            <p class="text-muted"><?php esc_html_e( 'Enter your ticket ID and email address to check the status of your ticket.', 'helppress-tickets' ); ?></p>
            
            <div id="helppress-status-form">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="ticket_id" class="form-label"><?php esc_html_e( 'Ticket ID', 'helppress-tickets' ); ?></label>
                        <input type="number" class="form-control" id="ticket_id" placeholder="<?php esc_attr_e( 'e.g. 123', 'helppress-tickets' ); ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="ticket_email" class="form-label"><?php esc_html_e( 'Email Address', 'helppress-tickets' ); ?></label>
                        <input type="email" class="form-control" id="ticket_email" placeholder="<?php esc_attr_e( 'Email used to create the ticket', 'helppress-tickets' ); ?>" required>
                    </div>
                </div>
                
                <div class="d-grid">
                    <button type="button" id="check-status-btn" class="btn btn-primary"><?php esc_html_e( 'Check Status', 'helppress-tickets' ); ?></button>
                </div>
                
                <div id="ticket-status-result" class="mt-4" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#check-status-btn').on('click', function() {
        var ticketId = $('#ticket_id').val();
        var email = $('#ticket_email').val();
        
        if (!ticketId || !email) {
            $('#ticket-status-result').html('<div class="alert alert-danger"><?php echo esc_js( __( 'Please enter both ticket ID and email address.', 'helppress-tickets' ) ); ?></div>').show();
            return;
        }
        
        // Show loading
        $('#ticket-status-result').html('<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden"><?php echo esc_js( __( 'Loading...', 'helppress-tickets' ) ); ?></span></div></div>').show();
        
        // Make AJAX call
        $.ajax({
            url: helppress_tickets.ajax_url,
            type: 'POST',
            data: {
                action: 'helppress_check_ticket_status',
                ticket_id: ticketId,
                email: email,
                nonce: helppress_tickets.nonce
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    var statusClass = '';
                    
                    // Determine status class
                    if (data.status.toLowerCase().includes('open')) {
                        statusClass = 'bg-info text-dark';
                    } else if (data.status.toLowerCase().includes('progress')) {
                        statusClass = 'bg-primary';
                    } else if (data.status.toLowerCase().includes('resolved')) {
                        statusClass = 'bg-success';
                    } else if (data.status.toLowerCase().includes('closed')) {
                        statusClass = 'bg-secondary';
                    } else {
                        statusClass = 'bg-secondary';
                    }
                    
                    // Build result HTML
                    var html = '<div class="card">';
                    html += '<div class="card-header bg-light"><h5 class="mb-0"><?php echo esc_js( __( 'Ticket Found', 'helppress-tickets' ) ); ?></h5></div>';
                    html += '<div class="card-body">';
                    html += '<table class="table">';
                    html += '<tr><th><?php echo esc_js( __( 'Ticket ID:', 'helppress-tickets' ) ); ?></th><td>#' + data.id + '</td></tr>';
                    html += '<tr><th><?php echo esc_js( __( 'Subject:', 'helppress-tickets' ) ); ?></th><td>' + data.subject + '</td></tr>';
                    html += '<tr><th><?php echo esc_js( __( 'Status:', 'helppress-tickets' ) ); ?></th><td><span class="badge ' + statusClass + '">' + data.status + '</span></td></tr>';
                    html += '<tr><th><?php echo esc_js( __( 'Priority:', 'helppress-tickets' ) ); ?></th><td>' + data.priority + '</td></tr>';
                    html += '<tr><th><?php echo esc_js( __( 'Last Updated:', 'helppress-tickets' ) ); ?></th><td>' + data.updated + '</td></tr>';
                    html += '</table>';
                    
                    if (data.link) {
                        html += '<div class="d-grid gap-2"><a href="' + data.link + '" class="btn btn-primary"><?php echo esc_js( __( 'View Ticket Details', 'helppress-tickets' ) ); ?></a></div>';
                    }
                    
                    html += '</div></div>';
                    
                    $('#ticket-status-result').html(html);
                } else {
                    $('#ticket-status-result').html('<div class="alert alert-danger">' + response.data.message + '</div>');
                }
            },
            error: function() {
                $('#ticket-status-result').html('<div class="alert alert-danger"><?php echo esc_js( __( 'An error occurred while checking the ticket status. Please try again.', 'helppress-tickets' ) ); ?></div>');
            }
        });
    });
});
</script>
