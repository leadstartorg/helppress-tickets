<?php
/**
 * Settings
 *
 * @package HelpPress Tickets
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Settings class
 *
 * @since 1.0.0
 */
class HelpPress_Tickets_Settings {

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Add all settings to main HelpPress settings
        add_filter( 'helppress_settings_indexes', array( $this, 'add_ticket_settings_index' ) );
        add_filter( 'helppress_settings', array( $this, 'add_ticket_settings' ) );
        
        // Hook into HelpPress settings save
        add_action( 'helppress_save_settings', array( $this, 'update_ticket_settings' ) );
        
        // Register custom field renderers for CMB2
        add_action( 'cmb2_init', array( $this, 'register_cmb2_custom_fields' ) );
    }

    /**
     * Register custom CMB2 field types
     */
    public function register_cmb2_custom_fields() {
        // Register auto-close days field renderer
        add_action( 'cmb2_render_tickets_auto_close_days', array( $this, 'render_auto_close_days_field' ), 10, 5 );
        
        // Register shortcodes table renderer
        add_action( 'cmb2_render_tickets_shortcodes_table', array( $this, 'render_shortcodes_table' ), 10, 5 );
    }
    
    /**
     * Render auto-close days field for CMB2
     */
    public function render_auto_close_days_field( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {
        $options = get_option( 'helppress_tickets_options', array() );
        $auto_close_days = isset( $options['auto_close_days'] ) ? $options['auto_close_days'] : 30;
        ?>
        <input type="number" name="helppress_tickets_options[auto_close_days]" value="<?php echo esc_attr( $auto_close_days ); ?>" min="0" max="365" />
        <p class="cmb2-metabox-description"><?php echo esc_html( $field->args['desc'] ); ?></p>
        <?php
    }
    
    /**
     * Render shortcodes table for CMB2
     */
    public function render_shortcodes_table( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {
        ?>
        <table class="widefat" style="max-width: 800px;">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Shortcode', 'helppress-tickets' ); ?></th>
                    <th><?php esc_html_e( 'Description', 'helppress-tickets' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>[helppress_submit_ticket]</code></td>
                    <td><?php esc_html_e( 'Displays the ticket submission form.', 'helppress-tickets' ); ?></td>
                </tr>
                <tr>
                    <td><code>[helppress_ticket_list]</code></td>
                    <td><?php esc_html_e( 'Displays a list of the current user\'s tickets.', 'helppress-tickets' ); ?></td>
                </tr>
                <tr>
                    <td><code>[helppress_admin_ticket_list]</code></td>
                    <td><?php esc_html_e( 'Displays a list of all tickets (admin only).', 'helppress-tickets' ); ?></td>
                </tr>
                <tr>
                    <td><code>[helppress_single_ticket]</code></td>
                    <td><?php esc_html_e( 'Displays a single ticket. Use with URL parameter ?ticket_id=X.', 'helppress-tickets' ); ?></td>
                </tr>
                <tr>
                    <td><code>[helppress_check_status]</code></td>
                    <td><?php esc_html_e( 'Displays a form for checking ticket status without logging in.', 'helppress-tickets' ); ?></td>
                </tr>
                <tr>
                    <td><code>[helppress_edit_ticket]</code></td>
                    <td><?php esc_html_e( 'Displays the ticket editing form. Use with URL parameter ?edit_ticket=X.', 'helppress-tickets' ); ?></td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    /**
     * Add ticket settings index
     *
     * @since 1.0.0
     * @param array $indexes Settings indexes
     * @return array Modified settings indexes
     */
    public function add_ticket_settings_index( $indexes ) {
        $indexes['tickets'] = 50; // After breadcrumb (40)
        return $indexes;
    }

    /**
     * Add ticket settings
     *
     * @since 1.0.0
     * @param array $settings Settings
     * @return array Modified settings
     */
    public function add_ticket_settings( $settings ) {
        $indexes = apply_filters( 'helppress_settings_indexes', array() );
        
        // ===== GENERAL TICKET SETTINGS =====
        if ( isset( $indexes['tickets'] ) ) {
            if ( ! isset( $settings[$indexes['tickets']] ) ) {
                $settings[$indexes['tickets']] = array();
            }
            
            // Add ticket settings heading
            $settings[$indexes['tickets']][] = array(
                'type' => 'title',
                'id' => 'heading_tickets',
                'name' => esc_html__( 'SUPPORT TICKETS', 'helppress-tickets' ),
            );
            
            // Enable tickets
            $settings[$indexes['tickets']][] = array(
                'type' => 'checkbox',
                'id' => 'tickets_enabled',
                'name' => esc_html__( 'Enable Tickets', 'helppress-tickets' ),
                'desc' => esc_html__( 'Enable the support ticket system.', 'helppress-tickets' ),
                'default' => true,
            );
            
            // Email notifications
            $settings[$indexes['tickets']][] = array(
                'type' => 'checkbox',
                'id' => 'tickets_email_notifications',
                'name' => esc_html__( 'Email Notifications', 'helppress-tickets' ),
                'desc' => esc_html__( 'Send email notifications for new tickets and replies.', 'helppress-tickets' ),
                'default' => true,
            );
            
            // Auto-convert to KB
            $settings[$indexes['tickets']][] = array(
                'type' => 'checkbox',
                'id' => 'tickets_auto_convert',
                'name' => esc_html__( 'Auto-Convert to KB Article', 'helppress-tickets' ),
                'desc' => esc_html__( 'Automatically convert resolved tickets to knowledge base articles if they are not marked as private.', 'helppress-tickets' ),
                'default' => false,
            );

            // Auto-close Days field - using custom CMB2 field type
            $settings[$indexes['tickets']][] = array(
                'type' => 'tickets_auto_close_days',
                'id' => 'tickets_auto_close_days',
                'name' => esc_html__( 'Auto-close Days', 'helppress-tickets' ),
                'desc' => esc_html__( 'Number of days after which resolved tickets will be automatically closed (0 to disable).', 'helppress-tickets' ),
            );
            
            // Default ticket status
            $settings[$indexes['tickets']][] = array(
                'type' => 'select',
                'id' => 'tickets_default_status',
                'name' => esc_html__( 'Default Ticket Status', 'helppress-tickets' ),
                'desc' => esc_html__( 'The default status for new tickets.', 'helppress-tickets' ),
                'options' => array(
                    'hp_open' => esc_html__( 'Open', 'helppress-tickets' ),
                    'hp_in_progress' => esc_html__( 'In Progress', 'helppress-tickets' ),
                    'pending' => esc_html__( 'Pending Review', 'helppress-tickets' ),
                ),
                'default' => 'hp_open',
            );

            // Default priority
            $settings[$indexes['tickets']][] = array(
                'type' => 'select',
                'id' => 'tickets_default_priority',
                'name' => esc_html__( 'Default Priority', 'helppress-tickets' ),
                'desc' => esc_html__( 'The default priority for new tickets.', 'helppress-tickets' ),
                'options' => $this->get_priority_options(),
                'default' => 'medium',
            );
            
            // Allow attachments
            $settings[$indexes['tickets']][] = array(
                'type' => 'checkbox',
                'id' => 'tickets_allow_attachments',
                'name' => esc_html__( 'Allow Attachments', 'helppress-tickets' ),
                'desc' => esc_html__( 'Allow users to attach files to tickets and replies.', 'helppress-tickets' ),
                'default' => true,
            );
            
            // Maximum attachment size
            $settings[$indexes['tickets']][] = array(
                'type' => 'text',
                'id' => 'tickets_max_attachment_size',
                'name' => esc_html__( 'Max Attachment Size (KB)', 'helppress-tickets' ),
                'desc' => esc_html__( 'Maximum file size for attachments in kilobytes.', 'helppress-tickets' ),
                'default' => '1024', // 1MB
                'attributes' => array(
                    'type' => 'number',
                    'min' => '1',
                    'max' => '5120', // 5MB
                ),
            );
            
            // Allowed file types
            $settings[$indexes['tickets']][] = array(
                'type' => 'text',
                'id' => 'tickets_allowed_file_types',
                'name' => esc_html__( 'Allowed File Types', 'helppress-tickets' ),
                'desc' => esc_html__( 'Comma-separated list of allowed file extensions for attachments.', 'helppress-tickets' ),
                'default' => 'jpg,jpeg,png,pdf,zip',
            );
            
            // Section end
            $settings[$indexes['tickets']][] = array(
                'type' => 'sectionend',
                'id' => 'tickets_general_end',
            );
            
            // ===== TICKET PAGES SETUP =====
            $settings[$indexes['tickets']][] = array(
                'type' => 'title',
                'id' => 'heading_tickets_pages',
                'name' => esc_html__( 'TICKET PAGES SETUP', 'helppress-tickets' ),
                'desc' => esc_html__( 'Configure the pages used for the ticket system.', 'helppress-tickets' ),
            );
            
            // Ticket submission page - update ID field to match naming convention
            $settings[$indexes['tickets']][] = array(
                'type' => 'select',
                'id' => 'tickets_submit_page_id', // Changed from tickets_submission_page
                'name' => esc_html__('Ticket Submission Page', 'helppress-tickets'),
                'desc' => esc_html__('Select the page where the ticket submission form is displayed.', 'helppress-tickets'),
                'options' => $this->get_pages_options(),
                'default' => get_option('helppress_tickets_submit_page_id', ''),
            );
            
            // Ticket list page - update ID field
            $settings[$indexes['tickets']][] = array(
                'type' => 'select',
                'id' => 'tickets_page_id', // Changed from tickets_list_page
                'name' => esc_html__('Ticket List Page', 'helppress-tickets'),
                'desc' => esc_html__('Select the page where the ticket list is displayed.', 'helppress-tickets'),
                'options' => $this->get_pages_options(),
                'default' => get_option('helppress_tickets_page_id', ''),
            );
            
            // Ticket status check page - update ID field
            $settings[$indexes['tickets']][] = array(
                'type' => 'select',
                'id' => 'tickets_status_page_id', // Changed from tickets_status_page
                'name' => esc_html__('Ticket Status Check Page', 'helppress-tickets'),
                'desc' => esc_html__('Select the page where the ticket status check form is displayed.', 'helppress-tickets'),
                'options' => $this->get_pages_options(),
                'default' => get_option('helppress_tickets_status_page_id', ''),
            );
            
            // Single ticket page - update ID field
            $settings[$indexes['tickets']][] = array(
                'type' => 'select',
                'id' => 'tickets_single_page_id', // Changed from tickets_single_page
                'name' => esc_html__('Single Ticket Page', 'helppress-tickets'),
                'desc' => esc_html__('Select the page where single tickets are displayed.', 'helppress-tickets'),
                'options' => $this->get_pages_options(),
                'default' => get_option('helppress_tickets_single_page_id', ''),
            );
            
            // Edit ticket page - update ID field
            $settings[$indexes['tickets']][] = array(
                'type' => 'select',
                'id' => 'tickets_edit_page_id', // Changed from tickets_edit_page
                'name' => esc_html__('Edit Ticket Page', 'helppress-tickets'),
                'desc' => esc_html__('Select the page where the ticket edit form is displayed.', 'helppress-tickets'),
                'options' => $this->get_pages_options(),
                'default' => get_option('helppress_tickets_edit_page_id', ''),
            );
            
            // Note: No "TICKET REPLIES SETTINGS" section as we're using WordPress comments
        }
        
        return $settings;
    }
    
    /**
     * Get priority options for select field
     *
     * @since 1.0.0
     * @return array Options for priority select
     */
    private function get_priority_options() {
        $priorities = get_terms(array(
            'taxonomy' => 'hp_ticket_priority',
            'hide_empty' => false,
        ));
        
        $options = array();
        
        if (!empty($priorities) && !is_wp_error($priorities)) {
            foreach ($priorities as $priority) {
                $options[$priority->slug] = $priority->name;
            }
        } else {
            // Default priorities if terms don't exist yet
            $options = array(
                'low' => esc_html__('Low', 'helppress-tickets'),
                'medium' => esc_html__('Medium', 'helppress-tickets'),
                'high' => esc_html__('High', 'helppress-tickets'),
                'urgent' => esc_html__('Urgent', 'helppress-tickets'),
            );
        }
        
        return $options;
    }
    
    /**
     * Get pages for select options
     *
     * @since 1.0.0
     * @return array Pages options
     */
    private function get_pages_options() {
        $pages = get_pages();
        $options = array( '' => esc_html__( 'Select a page', 'helppress-tickets' ) );
        
        if ( $pages ) {
            foreach ( $pages as $page ) {
                $options[$page->ID] = $page->post_title;
            }
        }
        
        return $options;
    }
    
    /**
     * Update ticket settings handler for when HelpPress saves settings
     * 
     * @since 1.0.0
     * @param array $settings The settings being saved
     */
    public function update_ticket_settings($settings) {
        // Extract ticket-specific settings
        $ticket_settings = array();

        foreach ($settings as $key => $value) {
            if (strpos($key, 'tickets_') === 0) {
                $short_key = str_replace('tickets_', '', $key);
                
                // Handle page ID options consistently
                if (strpos($key, '_page_id') !== false) {
                    // Update direct options with consistent naming
                    update_option('helppress_' . $key, $value);
                    
                    // Also store in options array
                    $ticket_settings[$short_key] = $value;
                    
                    // For compatibility, store with both naming patterns
                    $alt_key = str_replace('_page_id', '_page', $short_key);
                    $ticket_settings[$alt_key] = $value;
                } else {
                    // Handle other non-page options
                    update_option('helppress_tickets_' . $short_key, $value);
                    $ticket_settings[$short_key] = $value;
                }
            }
        }
        // Handle custom auto_close_days option
        if (isset($_POST['helppress_tickets_options']) && isset($_POST['helppress_tickets_options']['auto_close_days'])) {
            $auto_close_days = absint($_POST['helppress_tickets_options']['auto_close_days']);
            $ticket_settings['auto_close_days'] = $auto_close_days;
            update_option('helppress_tickets_auto_close_days', $auto_close_days);
        }
        
        // Update the options array
        if (!empty($ticket_settings)) {
            $existing_options = get_option('helppress_tickets_options', array());
            $updated_options = array_merge($existing_options, $ticket_settings);
            update_option('helppress_tickets_options', $updated_options);
        }
    }
    
    /**
     * Get ticket setting
     *
     * @since 1.0.0
     * @param string $key Setting key
     * @param mixed $default Default value
     * @return mixed Setting value
     */
    public static function get_setting( $key, $default = null ) {
        // Get all HelpPress options
        $options = maybe_unserialize( get_option( 'helppress_options' ) );
        
        // First check in the main HelpPress options
        if ( $options && isset( $options['tickets_' . $key] ) ) {
            return $options['tickets_' . $key];
        }
        
        // Then check in dedicated options
        $direct_option = get_option( 'helppress_tickets_' . $key );
        if ( $direct_option !== false ) {
            return $direct_option;
        }
        
        // Then check in legacy options structure
        $ticket_options = get_option( 'helppress_tickets_options', array() );
        if ( isset( $ticket_options[$key] ) ) {
            return $ticket_options[$key];
        }
        
        return $default;
    }
}

// Initialize the class
new HelpPress_Tickets_Settings();

/**
 * Get ticket setting
 *
 * @since 1.0.0
 * @param string $key Setting key
 * @param mixed $default Default value
 * @return mixed Setting value
 */
function helppress_tickets_get_setting( $key, $default = null ) {
    return HelpPress_Tickets_Settings::get_setting( $key, $default );
}