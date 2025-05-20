<?php
/**
 * Template: Admin Ticket List
 *
 * @package HelpPress Tickets
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get current tab from URL or default to 'all'
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'all';

?>

<div class="helppress-tickets helppress-tickets-admin-list">
    <div class="helppress-tickets-header d-flex justify-content-between align-items-center mb-4">
        <h2><?php esc_html_e( 'Support Tickets', 'helppress-tickets' ); ?></h2>
        <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=hp_ticket' ) ); ?>" class="btn btn-primary"><?php esc_html_e( 'Create Ticket for User', 'helppress-tickets' ); ?></a>
    </div>
    <div class="helppress-tickets-filter mb-4">
        <div class="row">
            <div class="col-md-4">
                <div class="d-flex mb-3 align-items-center">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">From</span>
                        <input type="date" id="date-from" name="date_from" class="form-control" 
                            value="<?php echo isset($_GET['date_from']) ? esc_attr($_GET['date_from']) : ''; ?>">
                    </div>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">To</span>
                        <input type="date" id="date-to" name="date_to" class="form-control" 
                            max="<?php echo date('Y-m-d'); ?>" 
                            value="<?php echo isset($_GET['date_to']) ? esc_attr($_GET['date_to']) : ''; ?>">
                    </div>
                </div>
            </div>       
            <div class="col-md-3">
                <select id="priority-filter" name="priority" class="form-select mb-3">
                    <option value=""><?php esc_html_e('Filter by Priority', 'helppress-tickets'); ?></option>
                    <?php
                    $priorities = get_terms(array(
                        'taxonomy' => 'hp_ticket_priority',
                        'hide_empty' => false,
                    ));
                    
                    if (!empty($priorities) && !is_wp_error($priorities)) {
                        foreach ($priorities as $priority) {
                            $selected = (isset($_GET['priority']) && $_GET['priority'] === $priority->slug) ? ' selected' : '';
                            printf(
                                '<option value="%s"%s>%s</option>',
                                esc_attr($priority->slug),
                                $selected,
                                esc_html($priority->name)
                            );
                        }
                    }
                    ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <select id="category-filter" name="category" class="form-select mb-3">
                    <option value=""><?php esc_html_e('Filter by Category', 'helppress-tickets'); ?></option>
                    <?php
                    $categories = get_terms(array(
                        'taxonomy' => 'hp_category',
                        'hide_empty' => false,
                    ));
                    
                    if (!empty($categories) && !is_wp_error($categories)) {
                        foreach ($categories as $category) {
                            $selected = (isset($_GET['category']) && $_GET['category'] === $category->slug) ? ' selected' : '';
                            printf(
                                '<option value="%s"%s>%s</option>',
                                esc_attr($category->slug),
                                $selected,
                                esc_html($category->name)
                            );
                        }
                    }
                    ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <select id="sort-by" name="orderby" class="form-select mb-3">
                    <option value="date-desc"<?php echo (!isset($_GET['orderby']) || (isset($_GET['orderby']) && $_GET['orderby'] === 'date' && isset($_GET['order']) && $_GET['order'] === 'DESC')) ? ' selected' : ''; ?>><?php esc_html_e('Newest First', 'helppress-tickets'); ?></option>
                    <option value="date-asc"<?php echo (isset($_GET['orderby']) && $_GET['orderby'] === 'date' && isset($_GET['order']) && $_GET['order'] === 'ASC') ? ' selected' : ''; ?>><?php esc_html_e('Oldest First', 'helppress-tickets'); ?></option>
                    <option value="modified-desc"<?php echo (isset($_GET['orderby']) && $_GET['orderby'] === 'modified' && isset($_GET['order']) && $_GET['order'] === 'DESC') ? ' selected' : ''; ?>><?php esc_html_e('Recently Updated', 'helppress-tickets'); ?></option>
                    <option value="title-asc"<?php echo (isset($_GET['orderby']) && $_GET['orderby'] === 'title' && isset($_GET['order']) && $_GET['order'] === 'ASC') ? ' selected' : ''; ?>><?php esc_html_e('Subject A-Z', 'helppress-tickets'); ?></option>
                </select>
            </div>
        </div>
    
        <div class="row">
            <div class="col-md-12">
                <form method="get" class="d-flex">
                    <?php 
                    // Preserve current tab and other filter parameters
                    echo '<input type="hidden" name="tab" value="' . esc_attr($current_tab) . '">';
                    
                    // Preserve other filter parameters as needed
                    foreach (['priority', 'category', 'date_from', 'date_to', 'orderby', 'order'] as $param) {
                        if (isset($_GET[$param])) {
                            echo '<input type="hidden" name="' . esc_attr($param) . '" value="' . esc_attr($_GET[$param]) . '">';
                        }
                    }
                    ?>
                    <input type="text" name="search" class="form-control" placeholder="<?php esc_attr_e('Search tickets by subject or ID', 'helppress-tickets'); ?>" value="<?php echo isset($_GET['search']) ? esc_attr(sanitize_text_field(wp_unslash($_GET['search']))) : ''; ?>">
                    <button type="submit" class="btn btn-outline-secondary ms-2"><?php esc_html_e('Search', 'helppress-tickets'); ?></button>
                </form>
            </div>
        </div>
    </div>
    <ul class="nav nav-tabs mb-4" id="ticketStatusAdminTabs" role="tablist">
        <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo $current_tab === 'all' ? 'active' : ''; ?>" id="admin-all-tab" data-bs-toggle="tab" data-bs-target="#admin-all" type="button" role="tab" aria-controls="admin-all" aria-selected="<?php echo $current_tab === 'all' ? 'true' : 'false'; ?>">
        <?php echo sprintf(esc_html__('All (%d)', 'helppress-tickets'), $tickets_all->found_posts); ?>
        </button>
        </li>
        <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo $current_tab === 'open' ? 'active' : ''; ?>" id="admin-open-tab" data-bs-toggle="tab" data-bs-target="#admin-open" type="button" role="tab" aria-controls="admin-open" aria-selected="<?php echo $current_tab === 'open' ? 'true' : 'false'; ?>">
        <?php echo sprintf(esc_html__('Open (%d)', 'helppress-tickets'), $tickets_open->found_posts); ?>
        </button>
        </li>
        <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo $current_tab === 'in_progress' ? 'active' : ''; ?>" id="admin-in-progress-tab" data-bs-toggle="tab" data-bs-target="#admin-in-progress" type="button" role="tab" aria-controls="admin-in-progress" aria-selected="<?php echo $current_tab === 'in_progress' ? 'true' : 'false'; ?>">
        <?php echo sprintf(esc_html__('In Progress (%d)', 'helppress-tickets'), $tickets_in_progress->found_posts); ?>
        </button>
        </li>
        <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo $current_tab === 'closed' ? 'active' : ''; ?>" id="admin-closed-tab" data-bs-toggle="tab" data-bs-target="#admin-closed" type="button" role="tab" aria-controls="admin-closed" aria-selected="<?php echo $current_tab === 'closed' ? 'true' : 'false'; ?>">
        <?php echo sprintf(esc_html__('Resolved/Closed (%d)', 'helppress-tickets'), $tickets_closed->found_posts); ?>
        </button>
        </li>
    </ul>
    <div class="tab-content" id="ticketStatusAdminContent">
        <!-- All Tickets Tab -->
        <div class="tab-pane fade <?php echo $current_tab === 'all' ? 'show active' : ''; ?>" id="admin-all" role="tabpanel" aria-labelledby="admin-all-tab">
            <?php if ($tickets_all->have_posts()): ?>
                <div class="table-responsive">
                    <table id="admin_all_table" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('ID', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Subject', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Status', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Priority', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('User', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Category', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Created', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Last Updated', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Actions', 'helppress-tickets'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($tickets_all->have_posts()) : $tickets_all->the_post(); 
                                // Include ticket row template part
                                HelpPress_Tickets_Template_Loader::get_template_part('parts/ticket-row', 'admin');
                            endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info"><?php esc_html_e('No tickets found.', 'helppress-tickets'); ?></div>
            <?php endif; ?>
        </div>
        <!-- Open Tickets Tab -->
        <div class="tab-pane fade <?php echo $current_tab === 'open' ? 'show active' : ''; ?>" id="admin-open" role="tabpanel" aria-labelledby="admin-open-tab">
            <?php if ($tickets_open->have_posts()): ?>
                <div class="table-responsive">
                    <table id="admin_open_table" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('ID', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Subject', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Status', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Priority', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('User', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Category', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Created', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Last Updated', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Actions', 'helppress-tickets'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($tickets_open->have_posts()) : $tickets_open->the_post(); 
                                // Include ticket row template part
                                HelpPress_Tickets_Template_Loader::get_template_part('parts/ticket-row', 'admin');
                            endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info"><?php esc_html_e('No open tickets found.', 'helppress-tickets'); ?></div>
            <?php endif; ?>
        </div>
        <!-- In Progress Tickets Tab -->
        <div class="tab-pane fade <?php echo $current_tab === 'in_progress' ? 'show active' : ''; ?>" id="admin-in-progress" role="tabpanel" aria-labelledby="admin-in-progress-tab">
            <?php if ($tickets_in_progress->have_posts()): ?>
                <div class="table-responsive">
                    <table id="admin_in_progress_table" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('ID', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Subject', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Status', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Priority', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('User', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Category', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Created', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Last Updated', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Actions', 'helppress-tickets'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($tickets_in_progress->have_posts()) : $tickets_in_progress->the_post(); 
                                // Include ticket row template part
                                HelpPress_Tickets_Template_Loader::get_template_part('parts/ticket-row', 'admin');
                            endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info"><?php esc_html_e('No tickets in progress found.', 'helppress-tickets'); ?></div>
            <?php endif; ?>
        </div>
        <!-- Closed Tickets Tab -->
        <div class="tab-pane fade <?php echo $current_tab === 'closed' ? 'show active' : ''; ?>" id="admin-closed" role="tabpanel" aria-labelledby="admin-closed-tab">
            <?php if ($tickets_closed->have_posts()): ?>
                <div class="table-responsive">
                    <table id="admin_closed_table" class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('ID', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Subject', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Status', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Priority', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('User', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Category', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Created', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Last Updated', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Actions', 'helppress-tickets'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($tickets_closed->have_posts()) : $tickets_closed->the_post(); 
                                // Include ticket row template part
                                HelpPress_Tickets_Template_Loader::get_template_part('parts/ticket-row', 'admin');
                            endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info"><?php esc_html_e('No resolved or closed tickets found.', 'helppress-tickets'); ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php wp_reset_postdata(); ?>
</div>