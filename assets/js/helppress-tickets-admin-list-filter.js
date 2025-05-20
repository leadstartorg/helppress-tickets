(function($) {
    // Initialize ticket filtering system
    const TicketFilter = {
        // Configuration 
        config: {
            contentSelector: '.helppress-tickets-admin-list',
            loadingClass: 'is-loading',
            activeTabClass: 'active'
        },
        
        // State management
        state: {
            priority: '',
            category: '',
            orderby: '',
            order: '',
            search: '',
            status: '',
            date_from: '',
            date_to: '',
            search_type: '',
            currentTab: 'all'
        },
        
        // Initialize the filter system
        init: function() {
            this.loadStateFromUrl();
            this.bindEvents();
            this.syncFormToState();
            
            // Add loading overlay to the content area only
            $('#ticketStatusAdminContent').css('position', 'relative');
            $('#ticketStatusAdminContent').append('<div class="loading-overlay" style="display:none"><div class="spinner-border text-primary"></div></div>');
            
            // Set up initial date constraints
            if ($('#date-from').val()) {
                $('#date-to').attr('min', $('#date-from').val());
            }
            if ($('#date-to').val()) {
                $('#date-from').attr('max', $('#date-to').val());
            }
        },
        
        // Load initial state from URL parameters
        loadStateFromUrl: function() {
            const urlParams = new URLSearchParams(window.location.search);
            
            // Set filter values from URL
            this.state.priority = urlParams.get('priority') || '';
            this.state.category = urlParams.get('category') || '';
            this.state.search = urlParams.get('search') || '';
            this.state.date_from = urlParams.get('date_from') || '';
            this.state.date_to = urlParams.get('date_to') || '';
            this.state.search_type = urlParams.get('search_type') || '';
            
            // Set ordering
            if (urlParams.has('orderby')) {
                this.state.orderby = urlParams.get('orderby');
                this.state.order = urlParams.get('order') || 'DESC';
            }
            
            // Set status and active tab
            if (urlParams.has('status')) {
                this.state.status = urlParams.get('status');
                
                // Set current tab based on status
                if (this.state.status === 'hp_open') {
                    this.state.currentTab = 'open';
                } else if (this.state.status === 'hp_in_progress') {
                    this.state.currentTab = 'in-progress';
                } else if (this.state.status.includes('hp_resolved') || this.state.status.includes('hp_closed')) {
                    this.state.currentTab = 'closed';
                }
            }
        },
        
        // Bind events to DOM elements
        bindEvents: function() {
            const self = this;
            
            // Filter changes
            $('#priority-filter').on('change', function() {
                self.state.priority = $(this).val();
                self.updateContent();
            });
            
            $('#category-filter').on('change', function() {
                self.state.category = $(this).val();
                self.updateContent();
            });
            
            $('#sort-by').on('change', function() {
                const value = $(this).val();
                if (value) {
                    const parts = value.split('-');
                    if (parts.length === 2) {
                        self.state.orderby = parts[0];
                        self.state.order = parts[1].toUpperCase();
                    }
                } else {
                    self.state.orderby = '';
                    self.state.order = '';
                }
                self.updateContent();
            });
            
            // Date range filter - trigger on change with debounce
            let dateChangeTimer;
            
            $('#date-from, #date-to').on('change', function() {
                const fieldId = $(this).attr('id');
                const value = $(this).val();
                
                // Handle date interdependency
                if (fieldId === 'date-from') {
                    if (value) {
                        $('#date-to').attr('min', value);
                    } else {
                        $('#date-to').removeAttr('min');
                    }
                    self.state.date_from = value;
                } else if (fieldId === 'date-to') {
                    if (value) {
                        $('#date-from').attr('max', value);
                    } else {
                        $('#date-from').removeAttr('max');
                    }
                    self.state.date_to = value;
                }
                
                // Debounce to prevent multiple rapid requests
                clearTimeout(dateChangeTimer);
                dateChangeTimer = setTimeout(function() {
                    self.updateContent();
                }, 300);
            });
            
            // Search form submission
            $('.helppress-tickets-filter form').on('submit', function(e) {
                e.preventDefault();
                self.state.search = $('input[name="search"]').val();
                
                // Set search_type parameter for admin search (ID, email, tag)
                self.state.search_type = 'advanced';
                
                self.updateContent();
            });
            
            // Tab changes - ONLY update state and URL, NOT content
            $('.nav-link[data-bs-toggle="tab"]').on('shown.bs.tab', function() {
                const tabId = $(this).attr('id');
                
                // Update state based on selected tab
                if (tabId === 'admin-open-tab') {
                    self.state.status = 'hp_open';
                    self.state.currentTab = 'open';
                } else if (tabId === 'admin-in-progress-tab') {
                    self.state.status = 'hp_in_progress';
                    self.state.currentTab = 'in-progress';
                } else if (tabId === 'admin-closed-tab') {
                    self.state.status = 'hp_resolved,hp_closed';
                    self.state.currentTab = 'closed';
                } else {
                    self.state.status = '';
                    self.state.currentTab = 'all';
                }
                
                // Just update the URL without making an AJAX request
                self.updateHistory();
            });
        },
        
        // Sync form fields with current state
        syncFormToState: function() {
            // Set form values
            $('#priority-filter').val(this.state.priority);
            $('#category-filter').val(this.state.category);
            $('input[name="search"]').val(this.state.search);
            
            // Set date values
            $('#date-from').val(this.state.date_from);
            $('#date-to').val(this.state.date_to);
            
            // Set sort value
            if (this.state.orderby) {
                $('#sort-by').val(this.state.orderby + '-' + this.state.order.toLowerCase());
            }
            
            // Activate the correct tab
            const tabId = 'admin-' + (this.state.currentTab === 'all' ? 'all' : 
                        this.state.currentTab === 'open' ? 'open' : 
                        this.state.currentTab === 'in-progress' ? 'in-progress' : 'closed') + '-tab';
            
            const tabEl = document.getElementById(tabId);
            if (tabEl && typeof bootstrap !== 'undefined' && bootstrap.Tab) {
                const tab = new bootstrap.Tab(tabEl);
                tab.show();
            }
        },
        
        // Build URL with current state
        buildUrl: function() {
            const params = new URLSearchParams();
            
            // Add filter parameters
            if (this.state.priority) params.set('priority', this.state.priority);
            if (this.state.category) params.set('category', this.state.category);
            if (this.state.search) params.set('search', this.state.search);
            if (this.state.search_type) params.set('search_type', this.state.search_type);
            if (this.state.status) params.set('status', this.state.status);
            
            // Add date parameters
            if (this.state.date_from) params.set('date_from', this.state.date_from);
            if (this.state.date_to) params.set('date_to', this.state.date_to);
            
            // Add ordering parameters
            if (this.state.orderby) {
                params.set('orderby', this.state.orderby);
                params.set('order', this.state.order);
            }
            
            // Add AJAX flag
            params.set('ajax', '1');
            
            return window.location.pathname + '?' + params.toString();
        },
        
        // Update browser history
        updateHistory: function() {
            const params = new URLSearchParams();
            
            // Add filter parameters
            if (this.state.priority) params.set('priority', this.state.priority);
            if (this.state.category) params.set('category', this.state.category);
            if (this.state.search) params.set('search', this.state.search);
            if (this.state.search_type) params.set('search_type', this.state.search_type);
            if (this.state.status) params.set('status', this.state.status);
            
            // Add date parameters
            if (this.state.date_from) params.set('date_from', this.state.date_from);
            if (this.state.date_to) params.set('date_to', this.state.date_to);
            
            // Add ordering parameters
            if (this.state.orderby) {
                params.set('orderby', this.state.orderby);
                params.set('order', this.state.order);
            }
            
            // Update history without the AJAX parameter
            const url = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            window.history.pushState({ path: url }, '', url);
        },
        
        // Show loading indicator
        showLoading: function() {
            $('.loading-overlay').fadeIn(150);
            $('#ticketStatusAdminContent').addClass(this.config.loadingClass);
        },
        
        // Hide loading indicator
        hideLoading: function() {
            $('.loading-overlay').fadeOut(150);
            $('#ticketStatusAdminContent').removeClass(this.config.loadingClass);
        },
        
        // Update content via AJAX - only called for filter changes, not tab changes
        updateContent: function() {
            const self = this;
            const url = this.buildUrl();
            
            // Show loading indicator
            this.showLoading();
            
            // Fetch updated content
            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'html',
                success: function(response) {
                    // Parse the response
                    const $response = $($.parseHTML(response));
                    
                    // Update ALL tab contents at once
                    $response.find('.tab-pane').each(function() {
                        const tabId = $(this).attr('id');
                        $('#' + tabId).html($(this).html());
                    });
                    
                    // Update counts in tab headers
                    self.updateTabCounts($response);
                    
                    // Update URL in browser
                    self.updateHistory();
                    
                    // Hide loading indicator
                    self.hideLoading();
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error: " + status + " - " + error);
                    alert('Error loading content. Please try again.');
                    self.hideLoading();
                }
            });
        },
        
        // Update the ticket counts in tabs
        updateTabCounts: function($response) {
            // Extract tab buttons with counts
            const $newTabButtons = $response.find('.nav-link[data-bs-toggle="tab"]');
            
            // Update each tab's count
            $newTabButtons.each(function() {
                const tabId = $(this).attr('id');
                const count = $(this).text().match(/\((\d+)\)/);
                
                if (count && count[1]) {
                    // Update the count in the current page's tab
                    const currentText = $('#' + tabId).text();
                    const newText = currentText.replace(/\(\d+\)/, '(' + count[1] + ')');
                    $('#' + tabId).text(newText);
                }
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Only initialize if we're on the admin ticket list page
        if ($('.helppress-tickets-admin-list').length) {
            // Initialize the ticket filter system
            TicketFilter.init();
            
            // Apply styling for loading overlay
            $('<style>\
                .loading-overlay {\
                    position: absolute;\
                    top: 0;\
                    left: 0;\
                    width: 100%;\
                    height: 100%;\
                    background: rgba(255,255,255,0.7);\
                    display: flex;\
                    justify-content: center;\
                    align-items: center;\
                    z-index: 9999;\
                }\
                #ticketStatusAdminContent.is-loading {\
                    min-height: 200px;\
                }\
            </style>').appendTo('head');
        }
    });
})(jQuery);

/*
jQuery(document).ready(function($) {
    // Handle filters
    $('#priority-filter, #category-filter, #sort-by').on('change', function() {
        var queryParams = new URLSearchParams(window.location.search);
        
        // Handle priority filter
        if ($(this).attr('id') === 'priority-filter') {
            if ($(this).val()) {
                queryParams.set('priority', $(this).val());
            } else {
                queryParams.delete('priority');
            }
        }
        
        // Handle category filter
        if ($(this).attr('id') === 'category-filter') {
            if ($(this).val()) {
                queryParams.set('category', $(this).val());
            } else {
                queryParams.delete('category');
            }
        }
        
        // Handle sort
        if ($(this).attr('id') === 'sort-by') {
            var sortVal = $(this).val().split('-');
            if (sortVal.length === 2) {
                queryParams.set('orderby', sortVal[0]);
                queryParams.set('order', sortVal[1].toUpperCase());
            }
        }
        
        window.location.search = queryParams.toString();
    });
});
*/



