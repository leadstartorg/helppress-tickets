# HelpPress Tickets

A ticketing system extension for the HelpPress Knowledge Base plugin.

## Description

HelpPress Tickets extends the popular HelpPress Knowledge Base plugin with a ticketing system. It allows your users to submit support tickets, track their status, and enables your support team to manage tickets efficiently.

### Features

- **User Ticket Submission**: Logged-in users can submit support tickets with detailed information.
- **Ticket Management**: View, respond to, and manage tickets through a clean, intuitive interface.
- **Admin Dashboard**: Comprehensive admin view with filtering and sorting options.
- **Knowledge Base Integration**: Convert resolved tickets to knowledge base articles for future reference.
- **Custom Statuses**: Track tickets through their lifecycle with custom statuses (Open, In Progress, Resolved, Closed).
- **Priority Levels**: Classify tickets by priority (Low, Medium, High, Urgent).
- **File Attachments**: Support for file attachments on tickets.
- **Email Notifications**: Automatic email notifications for ticket updates.
- **Status Checking**: Allow users to check ticket status without logging in.
- **Customizable**: Fully customizable with hooks and filters.

## Installation

1. Upload the `helppress-tickets` folder to the `/wp-content/plugins/` directory
2. Ensure HelpPress plugin is installed and activated
3. Activate the HelpPress Tickets plugin through the 'Plugins' menu in WordPress
4. Configure the plugin via HelpPress > Settings > Support Tickets

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- HelpPress plugin (latest version recommended)

## Usage

### Shortcodes

HelpPress Tickets provides 5 main shortcodes:

1. `[helppress_submit_ticket]` - Displays the ticket submission form.
2. `[helppress_edit_ticket]` - Displays the ticket submission form.
3. `[helppress_ticket_list]` - Shows a list of the current user's tickets.
4. `[helppress_admin_ticket_list]` - Displays an admin view of all tickets (only visible to users with appropriate permissions).
5. `[helppress_single_ticket]` - Shows details of a single ticket.
6. `[helppress_check_status]` - Provides a form to check ticket status without logging in.

### Example Usage

#### Creating a Support Ticket Page

1. Create a new page called "Submit a Ticket"
2. Add the shortcode: `[helppress_submit_ticket]`
3. Publish the page

#### Creating Edit Ticket Page

1. Create a new page called "Edit Ticket"
2. Add the shortcode: `[helppress_edit_ticket]`
3. Publish the page

#### Creating a My Tickets Page

1. Create a new page called "My Tickets"
2. Add the shortcode: `[helppress_ticket_list]`
3. Publish the page

#### Creating an Admin Tickets Dashboard

1. Create a new page called "Ticket Management"
2. Add the shortcode: `[helppress_admin_ticket_list]`
3. Publish the page (only admins will see the full functionality)

#### Setting Up the Ticket Viewing Page

1. Create a new page called "View Ticket"
2. Add the shortcode: `[helppress_single_ticket]`
3. Publish the page

#### Adding a Ticket Status Checker

1. Add the shortcode `[helppress_check_status]` to any page
2. Users can now check their ticket status without logging in

## Frequently Asked Questions

### Can I customize the ticket form fields?

Yes, you can use filters to customize the form fields. See the documentation for examples.

### How does the KB conversion work?

When a ticket is resolved, admins can convert it to a knowledge base article. The ticket title becomes the article title, and the ticket content plus any support responses become the article content.

### Does it work with my theme?

The plugin is designed to work with any properly coded WordPress theme. It uses Bootstrap 5 for styling to ensure compatibility and responsive design.

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by Jessica and Claude AI

## Changelog

### 1.0.0
* Initial release
