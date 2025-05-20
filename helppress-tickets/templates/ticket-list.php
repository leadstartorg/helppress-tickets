<?php
/**
 * Template: Ticket List (With Bootstrap Tabs)
 *
 * @package HelpPress Tickets
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="helppress-tickets helppress-tickets-list">
    <div class="helppress-tickets-header d-flex justify-content-between align-items-center mb-4">
        <h2><?php esc_html_e('My Support Tickets', 'helppress-tickets'); ?></h2>
        <?php
        // Get submit ticket page URL
        $submit_page_id = get_option('helppress_tickets_submit_page_id');
        $new_ticket_url = $submit_page_id ? get_permalink($submit_page_id) : '#';
        ?>
        <a href="<?php echo esc_url($new_ticket_url); ?>" class="btn btn-primary">
            <?php esc_html_e('Submit New Ticket', 'helppress-tickets'); ?>
        </a>
    </div>

    <!-- Search Form -->
    <div class="helppress-tickets-filter mb-4">
        <div class="row">
            <div class="col-md-12">
                <form method="get" class="d-flex">
                    <input type="text" name="search" class="form-control" placeholder="<?php esc_attr_e('Search tickets by subject or ID', 'helppress-tickets'); ?>" value="<?php echo isset($_GET['search']) ? esc_attr(sanitize_text_field(wp_unslash($_GET['search']))) : ''; ?>">
                    <button type="submit" class="btn btn-outline-secondary ms-2"><?php esc_html_e('Search', 'helppress-tickets'); ?></button>
                </form>
            </div>
        </div>
    </div>
    <!-- Bootstrap 5 Tabs -->
    <ul class="nav nav-tabs mb-4" id="ticketStatusTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab" aria-controls="all" aria-selected="true">
                <?php echo sprintf(esc_html__('All (%d)', 'helppress-tickets'), $tickets_all->found_posts); ?>
                
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="open-tab" data-bs-toggle="tab" data-bs-target="#open" type="button" role="tab" aria-controls="open" aria-selected="false">
                <?php echo sprintf(esc_html__('Open (%d)', 'helppress-tickets'), $tickets_open->found_posts); ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="in-progress-tab" data-bs-toggle="tab" data-bs-target="#in-progress" type="button" role="tab" aria-controls="in-progress" aria-selected="false">
                <?php echo sprintf(esc_html__('In Progress (%d)', 'helppress-tickets'), $tickets_in_progress->found_posts); ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="closed-tab" data-bs-toggle="tab" data-bs-target="#closed" type="button" role="tab" aria-controls="closed" aria-selected="false">
                <?php echo sprintf(esc_html__('Resolved/Closed (%d)', 'helppress-tickets'), $tickets_closed->found_posts); ?>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="ticketStatusContent">
        <!-- All Tickets Tab -->
        <div class="tab-pane fade show active" id="all" role="tabpanel" aria-labelledby="all-tab">
            <?php if ($tickets_all->have_posts()): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('ID', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Subject', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Status', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Priority', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Last Updated', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Actions', 'helppress-tickets'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($tickets_all->have_posts()) : $tickets_all->the_post(); 
                                // Include ticket row template part
                                HelpPress_Tickets_Template_Loader::get_template_part('parts/ticket-row', 'user');
                            endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($tickets_all->max_num_pages > 1): ?>
                    <div class="helppress-tickets-pagination mt-4">
                        <?php
                        $big = 999999999;
                        $pagination = paginate_links(array(
                            'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                            'format' => '?paged=%#%',
                            'current' => max(1, get_query_var('paged')),
                            'total' => $tickets_all->max_num_pages,
                            'prev_text' => '&laquo; ' . esc_html__('Previous', 'helppress-tickets'),
                            'next_text' => esc_html__('Next', 'helppress-tickets') . ' &raquo;',
                            'type' => 'list',
                            'show_all' => false,
                            'end_size' => 1,
                            'mid_size' => 2,
                            'add_args' => array()
                        ));
                        
                        // Convert to Bootstrap pagination
                        $pagination = str_replace('page-numbers', 'page-link', $pagination);
                        $pagination = str_replace('<li>', '<li class="page-item">', $pagination);
                        $pagination = str_replace('<li class="page-item current">', '<li class="page-item active">', $pagination);
                        $pagination = str_replace('<ul class="page-link">', '<ul class="pagination justify-content-center">', $pagination);
                        
                        echo wp_kses_post($pagination);
                        ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info"><?php esc_html_e('No tickets found.', 'helppress-tickets'); ?></div>
            <?php endif; ?>
        </div>
        
        <!-- Open Tickets Tab -->
        <div class="tab-pane fade" id="open" role="tabpanel" aria-labelledby="open-tab">
            <?php if ($tickets_open->have_posts()): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('ID', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Subject', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Status', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Priority', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Last Updated', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Actions', 'helppress-tickets'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($tickets_open->have_posts()) : $tickets_open->the_post(); 
                                // Include ticket row template part
                                HelpPress_Tickets_Template_Loader::get_template_part('parts/ticket-row', 'user');
                            endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($tickets_all->max_num_pages > 1): ?>
                    <div class="helppress-tickets-pagination mt-4">
                        <?php
                        $big = 999999999;
                        $pagination = paginate_links(array(
                            'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                            'format' => '?paged=%#%',
                            'current' => max(1, get_query_var('paged')),
                            'total' => $tickets_all->max_num_pages,
                            'prev_text' => '&laquo; ' . esc_html__('Previous', 'helppress-tickets'),
                            'next_text' => esc_html__('Next', 'helppress-tickets') . ' &raquo;',
                            'type' => 'list',
                            'show_all' => false,
                            'end_size' => 1,
                            'mid_size' => 2,
                            'add_args' => array()
                        ));
                        
                        // Convert to Bootstrap pagination
                        $pagination = str_replace('page-numbers', 'page-link', $pagination);
                        $pagination = str_replace('<li>', '<li class="page-item">', $pagination);
                        $pagination = str_replace('<li class="page-item current">', '<li class="page-item active">', $pagination);
                        $pagination = str_replace('<ul class="page-link">', '<ul class="pagination justify-content-center">', $pagination);
                        
                        echo wp_kses_post($pagination);
                        ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info"><?php esc_html_e('No open tickets found.', 'helppress-tickets'); ?></div>
            <?php endif; ?>
        </div>
        
        <!-- In Progress Tickets Tab -->
        <div class="tab-pane fade" id="in-progress" role="tabpanel" aria-labelledby="in-progress-tab">
            <?php if ($tickets_in_progress->have_posts()): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('ID', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Subject', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Status', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Priority', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Last Updated', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Actions', 'helppress-tickets'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($tickets_in_progress->have_posts()) : $tickets_in_progress->the_post(); 
                                // Include ticket row template part
                                HelpPress_Tickets_Template_Loader::get_template_part('parts/ticket-row', 'user');
                            endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($tickets_all->max_num_pages > 1): ?>
                    <div class="helppress-tickets-pagination mt-4">
                        <?php
                        $big = 999999999;
                        $pagination = paginate_links(array(
                            'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                            'format' => '?paged=%#%',
                            'current' => max(1, get_query_var('paged')),
                            'total' => $tickets_all->max_num_pages,
                            'prev_text' => '&laquo; ' . esc_html__('Previous', 'helppress-tickets'),
                            'next_text' => esc_html__('Next', 'helppress-tickets') . ' &raquo;',
                            'type' => 'list',
                            'show_all' => false,
                            'end_size' => 1,
                            'mid_size' => 2,
                            'add_args' => array()
                        ));
                        
                        // Convert to Bootstrap pagination
                        $pagination = str_replace('page-numbers', 'page-link', $pagination);
                        $pagination = str_replace('<li>', '<li class="page-item">', $pagination);
                        $pagination = str_replace('<li class="page-item current">', '<li class="page-item active">', $pagination);
                        $pagination = str_replace('<ul class="page-link">', '<ul class="pagination justify-content-center">', $pagination);
                        
                        echo wp_kses_post($pagination);
                        ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info"><?php esc_html_e('No tickets in progress found.', 'helppress-tickets'); ?></div>
            <?php endif; ?>
        </div>
        <!-- Closed Tickets Tab -->
        <div class="tab-pane fade" id="closed" role="tabpanel" aria-labelledby="closed-tab">
            <?php if ($tickets_closed->have_posts()): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('ID', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Subject', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Status', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Priority', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Last Updated', 'helppress-tickets'); ?></th>
                                <th><?php esc_html_e('Actions', 'helppress-tickets'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($tickets_closed->have_posts()) : $tickets_closed->the_post(); 
                                // Include ticket row template part
                                HelpPress_Tickets_Template_Loader::get_template_part('parts/ticket-row', 'user');
                            endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($tickets_all->max_num_pages > 1): ?>
                    <div class="helppress-tickets-pagination mt-4">
                        <?php
                        $big = 999999999;
                        $pagination = paginate_links(array(
                            'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                            'format' => '?paged=%#%',
                            'current' => max(1, get_query_var('paged')),
                            'total' => $tickets_all->max_num_pages,
                            'prev_text' => '&laquo; ' . esc_html__('Previous', 'helppress-tickets'),
                            'next_text' => esc_html__('Next', 'helppress-tickets') . ' &raquo;',
                            'type' => 'list',
                            'show_all' => false,
                            'end_size' => 1,
                            'mid_size' => 2,
                            'add_args' => array()
                        ));
                        
                        // Convert to Bootstrap pagination
                        $pagination = str_replace('page-numbers', 'page-link', $pagination);
                        $pagination = str_replace('<li>', '<li class="page-item">', $pagination);
                        $pagination = str_replace('<li class="page-item current">', '<li class="page-item active">', $pagination);
                        $pagination = str_replace('<ul class="page-link">', '<ul class="pagination justify-content-center">', $pagination);
                        
                        echo wp_kses_post($pagination);
                        ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info"><?php esc_html_e('No resolved or closed tickets found.', 'helppress-tickets'); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php wp_reset_postdata(); ?>
