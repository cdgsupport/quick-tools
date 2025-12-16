<?php
declare(strict_types=1);

/**
 * The admin-specific functionality of the plugin.
 *
 * @package QuickTools
 * @since 1.0.0
 */
class Quick_Tools_Admin {

    private string $plugin_name;
    private string $version;

    public function __construct(string $plugin_name, string $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_styles(string $hook): void {
        // Only load on our admin pages
        if ($hook !== 'tools_page_quick-tools') {
            return;
        }

        // Enqueue Bootstrap 5 (CDN) - Scoped to our page only
        wp_enqueue_style(
            'bootstrap-5',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
            array(),
            '5.3.0'
        );

        // Custom overrides for Bootstrap/WP conflicts
        wp_add_inline_style('bootstrap-5', '
            .wrap.qt-bootstrap-wrapper { margin-top: 20px; }
            .qt-bootstrap-wrapper a { text-decoration: none; }
            /* Fix WP admin menu overlapping */
            .qt-bootstrap-wrapper .card { max-width: 100%; }
        ');

        wp_enqueue_style(
            $this->plugin_name,
            QUICK_TOOLS_PLUGIN_URL . 'admin/css/admin-style.css',
            array('bootstrap-5'),
            $this->version,
            'all'
        );
    }

    public function enqueue_scripts(string $hook): void {
        if ($hook !== 'tools_page_quick-tools') {
            return;
        }

        wp_enqueue_script(
            'bootstrap-js',
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
            array('jquery'),
            '5.3.0',
            true
        );

        wp_enqueue_script(
            $this->plugin_name,
            QUICK_TOOLS_PLUGIN_URL . 'admin/js/admin-script.js',
            array('jquery', 'bootstrap-js'),
            $this->version,
            true
        );

        wp_localize_script(
            $this->plugin_name,
            'quickToolsAjax',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('quick_tools_nonce'),
                'strings' => array(
                    'searching' => __('Searching...', 'quick-tools'),
                    'no_results' => __('No documentation found.', 'quick-tools'),
                    'error' => __('An error occurred.', 'quick-tools'),
                    'confirm_import' => __('Are you sure you want to import?', 'quick-tools'),
                    'export_success' => __('Export successful!', 'quick-tools'),
                    'import_success' => __('Import successful!', 'quick-tools'),
                )
            )
        );
    }

    /**
     * Add plugin admin menu under "Tools".
     */
    public function add_plugin_admin_menu(): void {
        add_management_page(
            __('Quick Tools', 'quick-tools'),
            __('Quick Tools', 'quick-tools'),
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_admin_page')
        );
    }

    public function display_plugin_admin_page(): void {
        include_once QUICK_TOOLS_PLUGIN_DIR . 'admin/views/admin-page.php';
    }

    public function register_settings(): void {
        // Manual form processing used
    }

    /**
     * Helper to find potential Options Pages for the dropdown selector.
     */
    public static function get_registered_options_pages(): array {
        global $menu, $submenu;
        
        $options_pages = array();
        
        // Standard WP pages to exclude
        $excludes = array(
            'index.php', 'edit.php', 'upload.php', 'edit-comments.php', 
            'themes.php', 'plugins.php', 'users.php', 'tools.php', 
            'options-general.php', 'edit.php?post_type=page'
        );

        // Check top level menus
        if (!empty($menu)) {
            foreach ($menu as $item) {
                if (!empty($item[0]) && !empty($item[2])) {
                    $slug = $item[2];
                    // If it's not a standard WP page and not a separator
                    if (!in_array($slug, $excludes) && strpos($item[4], 'wp-menu-separator') === false) {
                        $options_pages[$slug] = strip_tags($item[0]);
                    }
                }
            }
        }

        // Check submenus (often where Options pages live)
        if (!empty($submenu)) {
            foreach ($submenu as $parent => $items) {
                foreach ($items as $item) {
                    if (!empty($item[2]) && !in_array($item[2], $excludes)) {
                        // Create a readable name: "Parent > Child"
                        $parent_name = isset($menu) ? self::find_parent_name($parent, $menu) : $parent;
                        $page_name = strip_tags($item[0]);
                        $options_pages[$item[2]] = $parent_name . ' > ' . $page_name;
                    }
                }
            }
        }

        return $options_pages;
    }

    private static function find_parent_name($slug, $menu) {
        foreach ($menu as $item) {
            if ($item[2] === $slug) return strip_tags($item[0]);
        }
        return $slug;
    }

    // AJAX handlers remain the same...
    public function ajax_search_documentation(): void {
        check_ajax_referer('quick_tools_nonce', 'nonce');
        $search_term = sanitize_text_field($_POST['search_term']);
        $documentation = new Quick_Tools_Documentation();
        wp_send_json_success($documentation->search_documentation($search_term));
    }
    
    public function ajax_export_documentation(): void {
        check_ajax_referer('quick_tools_nonce', 'nonce');
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $documentation = new Quick_Tools_Documentation();
        $export_data = $documentation->export_documentation($category);
        $filename = 'quick-tools-documentation-' . date('Y-m-d-H-i-s') . '.json';
        wp_send_json_success(array('data' => $export_data, 'filename' => $filename));
    }

    public function ajax_import_documentation(): void {
        check_ajax_referer('quick_tools_nonce', 'nonce');
        if (!isset($_FILES['import_file'])) wp_send_json_error(__('No file.', 'quick-tools'));
        $file_content = file_get_contents($_FILES['import_file']['tmp_name']);
        $import_data = json_decode($file_content, true);
        $documentation = new Quick_Tools_Documentation();
        $result = $documentation->import_documentation($import_data);
        wp_send_json_success($result);
    }
}
