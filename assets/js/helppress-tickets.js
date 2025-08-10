/**
 * HelpPress Tickets JavaScript
 */
(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        initTicketForms();
        initTicketActions();
    });

    /**
     * Initialize ticket form functionality
     */
    function initTicketForms() {
        // Form validation for submit ticket form
        $('.helppress-tickets-submit-form').on('submit', function(e) {
            var requiredFields = $(this).find('[required]');
            var valid = true;

            requiredFields.each(function() {
                if (!$(this).val()) {
                    valid = false;
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            if (!valid) {
                e.preventDefault();
                $('<div class="alert alert-danger mt-3">' + helppress_tickets_i18n.fill_required + '</div>')
                    .insertBefore($(this).find('button[type="submit"]').parent())
                    .delay(5000)
                    .fadeOut(function() {
                        $(this).remove();
                    });
                
                // Scroll to first invalid field
                $('html, body').animate({
                    scrollTop: $(this).find('.is-invalid:first').offset().top - 100
                }, 500);
            }
        });

        // File upload validation
        $('input[type="file"]').on('change', function() {
            var fileInput = $(this);
            //var maxSize = 1 * 1024 * 1024; // 1MB
            var maxSize = 2 * 1024 * 1024 * 1024; // 2GB
            var allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf', 'application/zip'];
            
            if (fileInput[0].files.length > 0) {
                var file = fileInput[0].files[0];
                
                // Check file size
                if (file.size > maxSize) {
                    alert(helppress_tickets_i18n.file_too_large);
                    fileInput.val('');
                    return;
                }
                
                // Check file type
                if ($.inArray(file.type, allowedTypes) === -1) {
                    alert(helppress_tickets_i18n.invalid_file_type);
                    fileInput.val('');
                    return;
                }
            }
        });
    }
    
    /**
     * Initialize ticket action functionality
     */
    function initTicketActions() {
        // Ticket status check
        $('#check-status-btn').on('click', function() {
            var ticketId = $('#ticket_id').val();
            var email = $('#ticket_email').val();
            
            if (!ticketId || !email) {
                $('#ticket-status-result').html('<div class="alert alert-danger">' + helppress_tickets_i18n.enter_all_fields + '</div>').show();
                return;
            }
            
            // Show loading
            $('#ticket-status-result').html('<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">' + helppress_tickets_i18n.loading + '</span></div></div>').show();
            
            // Make AJAX call
            $.ajax({
                url: helppress_tickets.ajax_url,
                type: 'POST',
                data: {
                    action: 'helppress_check_ticket_status',
                    ticket_id: ticketId,  // Changed from ticket_id to ticket_id
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
                        html += '<div class="card-header bg-light"><h5 class="mb-0">' + helppress_tickets_i18n.ticket_found + '</h5></div>';
                        html += '<div class="card-body">';
                        html += '<table class="table">';
                        html += '<tr><th>' + helppress_tickets_i18n.ticket_id + '</th><td>#' + data.id + '</td></tr>';
                        html += '<tr><th>' + helppress_tickets_i18n.subject + '</th><td>' + data.subject + '</td></tr>';
                        html += '<tr><th>' + helppress_tickets_i18n.status + '</th><td><span class="badge ' + statusClass + '">' + data.status + '</span></td></tr>';
                        html += '<tr><th>' + helppress_tickets_i18n.priority + '</th><td>' + data.priority + '</td></tr>';
                        html += '<tr><th>' + helppress_tickets_i18n.last_updated + '</th><td>' + data.updated + '</td></tr>';
                        html += '</table>';
                        
                        if (data.link) {
                            html += '<div class="d-grid gap-2"><a href="' + data.link + '" class="btn btn-primary">' + helppress_tickets_i18n.view_ticket + '</a></div>';
                        }
                        
                        html += '</div></div>';
                        
                        $('#ticket-status-result').html(html);
                    } else {
                        $('#ticket-status-result').html('<div class="alert alert-danger">' + response.data.message + '</div>');
                    }
                },
                error: function() {
                    $('#ticket-status-result').html('<div class="alert alert-danger">' + helppress_tickets_i18n.error_checking + '</div>');
                }
            });
        });

        // Confirm ticket close/resolve actions
        $('.helppress-tickets').on('click', '.ticket-action-confirm', function(e) {
            if (!confirm(helppress_tickets_i18n.confirm_action)) {
                e.preventDefault();
            }
        });

        // Auto-focus search field
        if ($('.helppress-tickets-filter input[name="search"]').length) {
            $('.helppress-tickets-filter input[name="search"]').focus();
        }
    }
})(jQuery);


// Handle admin ticket search
jQuery(document).ready(function($) {
    $('.helppress-tickets-admin-list form').on('submit', function(e) {
        // Don't prevent default form submission - let it work naturally
        // This allows the search to work without AJAX
    });
});

//Ticket List Pagination with DataTables
(function($) {
    // Initialize DataTables - works with both admin and user ticket lists
    function initTables() {
        // Target both containers
        $('#ticketStatusAdminContent table, #ticketStatusContent table').each(function() {
            // Destroy if already initialized
            if ($.fn.DataTable.isDataTable(this)) {
                $(this).DataTable().destroy();
            }
            
            // Initialize
            $(this).DataTable({
                pageLength: 5,
                lengthMenu: [5, 10, 25, 50]
            });
        });
    }
    
    $(document).ready(function() {
        // Initial setup
        initTables();
        
        // Patch the existing AJAX system to reinitialize tables
        const originalAjax = $.ajax;
        $.ajax = function() {
            const originalSuccess = arguments[0].success;
            
            if (originalSuccess) {
                arguments[0].success = function(response) {
                    // Call the original success handler
                    originalSuccess.apply(this, arguments);
                    
                    // Then reinitialize tables
                    initTables();
                };
            }
            
            return originalAjax.apply(this, arguments);
        };
        
        // Fix tables when switching tabs - works with both admin and user interfaces
        $('.nav-link[data-bs-toggle="tab"]').on('shown.bs.tab', function() {
            const tabId = $(this).attr('data-bs-target');
            $(tabId + ' table').DataTable().columns.adjust();
        });
    });
})(jQuery);

// Tag field handling
$('#ticket_tags').on('change blur', function() {
    // Clean up tag input (remove duplicate commas, trim whitespace)
    var value = $(this).val();
    var tags = value.split(',');
    
    // Filter, clean and rejoin
    tags = tags.map(function(tag) {
        return tag.trim();
    }).filter(function(tag) {
        return tag.length > 0;
    });
    
    $(this).val(tags.join(', '));
});
