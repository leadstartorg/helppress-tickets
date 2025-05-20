<?php
/**
 * Template Loader
 *
 * @package HelpPress Tickets
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Template Loader class
 * 
 * Based on the HelpPress template loader structure
 *
 * @since 1.0.0
 */
class HelpPress_Tickets_Template_Loader {

    /**
     * Get template part (for templates like the ticket submission form).
     *
     * @since 1.0.0
     * @param string $slug Template slug.
     * @param string $name Optional. Template name.
     * @param array  $args Optional. Arguments to pass to the template.
     */
    public static function get_template_part($slug, $name = '', $args = array()) {
        // Extract arguments to use in template
        if (!empty($args) && is_array($args)) {
            extract($args);
        }
        
        $template = '';

        // Look in yourtheme/helppress-tickets/slug-name.php and yourtheme/helppress-tickets/slug.php
        if ($name) {
            $template = locate_template(array("helppress-tickets/{$slug}-{$name}.php", "helppress-tickets/{$slug}.php"));
        } else {
            $template = locate_template(array("helppress-tickets/{$slug}.php"));
        }

        // Check in plugin's parts directory if name is provided
        if (!$template && $name) {
            $plugin_template = HPTICKETS_PATH . "templates/parts/{$slug}-{$name}.php";
            if (file_exists($plugin_template)) {
                $template = $plugin_template;
            }
        }

        // If template is still not found, check in plugin's templates directory
        if (!$template) {
            if ($name) {
                $template = HPTICKETS_PATH . "templates/{$slug}-{$name}.php";
            } else {
                $template = HPTICKETS_PATH . "templates/{$slug}.php";
            }
        }

        // Allow third party plugins to filter template file from their plugin.
        $template = apply_filters('helppress_tickets_get_template_part', $template, $slug, $name);

        if ($template && file_exists($template)) {
            include($template);
        }
    }

    /**
     * Get other templates (e.g. ticket list) passing attributes and including the file.
     *
     * @since 1.0.0
     * @param string $template_name Template name.
     * @param array  $args          Optional. Arguments to pass to the template.
     * @param string $template_path Optional. Template path.
     * @param string $default_path  Optional. Default path.
     */
    public static function get_template($template_name, $args = array(), $template_path = '', $default_path = '') {
        if (!empty($args) && is_array($args)) {
            extract($args);
        }

        $located = self::locate_template($template_name, $template_path, $default_path);

        if (!file_exists($located)) {
            /* translators: %s: template file path */
            _doing_it_wrong(__FUNCTION__, sprintf('<code>%s</code> does not exist.', esc_html($located)), '1.0.0');
            return;
        }

        // Allow 3rd party plugin filter template file from their plugin.
        $located = apply_filters('helppress_tickets_get_template', $located, $template_name, $args, $template_path, $default_path);

        do_action('helppress_tickets_before_template_part', $template_name, $template_path, $located, $args);

        include($located);

        do_action('helppress_tickets_after_template_part', $template_name, $template_path, $located, $args);
    }
    
    /**
     * Locate a template and return the path for inclusion.
     *
     * @since 1.0.0
     * @param string $template_name Template name.
     * @param string $template_path Optional. Template path.
     * @param string $default_path  Optional. Default path.
     * @return string
     */
    public static function locate_template($template_name, $template_path = '', $default_path = '') {
        if (!$template_path) {
            $template_path = 'helppress-tickets/';
        }

        if (!$default_path) {
            $default_path = HPTICKETS_PATH . 'templates/';
        }

        // Look within passed path within the theme - this is priority.
        $template = locate_template(
            array(
                trailingslashit($template_path) . $template_name,
                $template_name,
            )
        );

        // Get default template if we're still here
        if (!$template || empty($template)) {
            $template = trailingslashit($default_path) . $template_name;
        }

        // Return what we found.
        return apply_filters('helppress_tickets_locate_template', $template, $template_name, $template_path);
    }
    
    /**
     * Get template content and return as string instead of including directly.
     *
     * @since 1.0.0
     * @param string $template_name Template name.
     * @param array  $args          Optional. Arguments to pass to the template.
     * @param string $template_path Optional. Template path.
     * @param string $default_path  Optional. Default path.
     * @return string Template content
     */
    public static function get_template_html($template_name, $args = array(), $template_path = '', $default_path = '') {
        ob_start();
        self::get_template($template_name, $args, $template_path, $default_path);
        return ob_get_clean();
    }
}