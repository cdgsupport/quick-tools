<?php
declare(strict_types=1);

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @package QuickTools
 * @since 1.0.0
 */
class Quick_Tools {

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     *
     * @var Quick_Tools_Loader
     */
    protected Quick_Tools_Loader $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @var string
     */
    protected string $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @var string
     */
    protected string $version;

    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {
        $this->version = QUICK_TOOLS_VERSION;
        $this->plugin_name = 'quick-tools';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_dashboard_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies(): void {
        /**
         * The class responsible for orchestrating the actions and filters of the core plugin.
         */
        require_once QUICK_TOOLS_PLUGIN_DIR . 'includes/class-loader.php';

        /**
         * The class responsible for defining internationalization functionality.
         */
        require_once QUICK_TOOLS_PLUGIN_DIR . 'includes/class-i18n.php';

        /**
         * The class responsible for defining all actions in the admin area.
         */
        require_once QUICK_TOOLS_PLUGIN_DIR . 'includes/class-admin.php';

        /**
         * The class responsible for the documentation system.
         */
        require_once QUICK_TOOLS_PLUGIN_DIR . 'includes/class-documentation.php';

        /**
         * The class responsible for CPT dashboard widgets.
         */
        require_once QUICK_TOOLS_PLUGIN_DIR . 'includes/class-cpt-dashboard.php';

        $this->loader = new Quick_Tools_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     */
    private function set_locale(): void {
        $plugin_i18n = new Quick_Tools_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     */
    private function define_admin_hooks(): void {
        $plugin_admin = new Quick_Tools_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
        
        // AJAX hooks
        $this->loader->add_action('wp_ajax_qt_search_documentation', $plugin_admin, 'ajax_search_documentation');
        $this->loader->add_action('wp_ajax_qt_export_documentation', $plugin_admin, 'ajax_export_documentation');
        $this->loader->add_action('wp_ajax_qt_import_documentation', $plugin_admin, 'ajax_import_documentation');
    }

    /**
     * Register all dashboard-related hooks.
     */
    private function define_dashboard_hooks(): void {
        $documentation = new Quick_Tools_Documentation();
        $cpt_dashboard = new Quick_Tools_CPT_Dashboard();

        // Documentation hooks
        $this->loader->add_action('init', $documentation, 'register_post_type');
        $this->loader->add_action('init', $documentation, 'register_taxonomy');
        $this->loader->add_action('wp_dashboard_setup', $documentation, 'add_dashboard_widgets');
        $this->loader->add_action('admin_menu', $documentation, 'add_documentation_viewer');

        // CPT Dashboard hooks
        $this->loader->add_action('wp_dashboard_setup', $cpt_dashboard, 'add_dashboard_widgets');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     */
    public function run(): void {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it.
     *
     * @return string The plugin name.
     */
    public function get_plugin_name(): string {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return Quick_Tools_Loader The loader instance.
     */
    public function get_loader(): Quick_Tools_Loader {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return string The plugin version.
     */
    public function get_version(): string {
        return $this->version;
    }
}
