<?php
declare(strict_types=1);

/**
 * The CPT dashboard functionality of the plugin.
 */
class Quick_Tools_CPT_Dashboard {

    const MODULE_STYLE_INFORMATIVE = 'informative';
    const MODULE_STYLE_MINIMAL = 'minimal';

    /**
     * Initialize Dashboard Widgets.
     */
    public function add_dashboard_widgets(): void {
        $settings = get_option('quick_tools_settings', array());
        
        // Handle legacy setting (array of slugs) vs new setting (array of configs)
        $cpt_configs = $this->get_normalized_cpt_settings($settings);

        if (empty($cpt_configs)) {
            return;
        }

        // Separate CPTs by location
        $dashboard_cpts = array();
        
        foreach ($cpt_configs as $cpt => $config) {
            $location = isset($config['location']) ? $config['location'] : 'dashboard';
            
            if ($location === 'dashboard') {
                $dashboard_cpts[] = $cpt;
            }
        }

        // If no CPTs are assigned to dashboard, bail
        if (empty($dashboard_cpts)) {
            return;
        }

        $module_style = $settings['cpt_module_style'] ?? self::MODULE_STYLE_INFORMATIVE;

        if ($module_style === self::MODULE_STYLE_MINIMAL) {
            $this->add_minimal_dashboard_widget($dashboard_cpts);
        } else {
            $this->add_informative_dashboard_widgets($dashboard_cpts);
        }
    }

    /**
     * Hook to inject buttons into custom Options Pages.
     */
    public function inject_options_page_buttons(): void {
        $settings = get_option('quick_tools_settings', array());
        $cpt_configs = $this->get_normalized_cpt_settings($settings);
        
        $current_screen = get_current_screen();
        if (!$current_screen) return;

        // Current page slug is usually in $_GET['page'] for options pages
        $current_page_slug = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        
        if (empty($current_page_slug)) return;

        foreach ($cpt_configs as $cpt => $config) {
            $location = isset($config['location']) ? $config['location'] : 'dashboard';
            
            // If this CPT is assigned to the current page
            if ($location === $current_page_slug) {
                $this->render_floating_quick_add_button($cpt);
            }
        }
    }

    /**
     * Render a button at the top of an options page.
     */
    private function render_floating_quick_add_button($cpt): void {
        $pt_obj = get_post_type_object($cpt);
        if (!$pt_obj || !current_user_can($pt_obj->cap->create_posts)) return;

        $link = admin_url('post-new.php?post_type=' . $cpt);
        
        echo '<div class="notice notice-info is-dismissible" style="display:flex; align-items:center; justify-content:space-between; padding:10px;">';
        echo '<span><strong>Quick Tools:</strong> Create new ' . esc_html($pt_obj->labels->singular_name) . '</span>';
        echo '<a href="' . esc_url($link) . '" class="button button-primary">';
        echo '<span class="dashicons dashicons-plus-alt2" style="vertical-align:text-bottom;"></span> Add New';
        echo '</a>';
        echo '</div>';
    }

    /**
     * Normalize settings to handle both old (simple array) and new (assoc array) formats.
     */
    private function get_normalized_cpt_settings($settings): array {
        if (!isset($settings['selected_cpts'])) return array();

        $cpts = $settings['selected_cpts'];
        $normalized = array();

        // If it's the new format (associative array stored in separate key or mixed)
        // Note: For simplicity, we are checking if the value is an array (new config) or string (old slug)
        foreach ($cpts as $key => $val) {
            if (is_array($val)) {
                // New format: 'slug' => ['location' => '...']
                $normalized[$key] = $val;
            } else {
                // Old format: '0' => 'slug' or 'slug' => 'slug'
                // We default these to dashboard
                $normalized[$val] = array('location' => 'dashboard');
            }
        }
        return $normalized;
    }

    private function add_minimal_dashboard_widget(array $selected_cpts): void {
        // ... (Code remains same as original, just loops through $selected_cpts) ...
        wp_add_dashboard_widget(
            'qt_cpt_minimal',
            __('Quick Add Posts', 'quick-tools'),
            array($this, 'render_minimal_dashboard_widget'),
            null,
            array('selected_cpts' => $selected_cpts)
        );
    }

    private function add_informative_dashboard_widgets(array $selected_cpts): void {
        // ... (Code remains same as original) ...
        foreach ($selected_cpts as $cpt) {
            $post_type_object = get_post_type_object($cpt);
            if ($post_type_object && current_user_can($post_type_object->cap->create_posts)) {
                wp_add_dashboard_widget(
                    'qt_cpt_widget_' . $cpt,
                    sprintf(__('Quick Add: %s', 'quick-tools'), $post_type_object->labels->singular_name),
                    array($this, 'render_informative_cpt_widget'),
                    null,
                    array('cpt' => $cpt, 'post_type_object' => $post_type_object)
                );
            }
        }
    }

    // ... (Rest of render methods: render_minimal_dashboard_widget, render_informative_cpt_widget, render_recent_posts, etc. remain largely the same) ...
    // Note: Ensure render methods accept the new data structure or the array passed from add_* methods.
    
    public function render_minimal_dashboard_widget($post, $callback_args): void {
        $selected_cpts = $callback_args['args']['selected_cpts'];
        echo '<div class="qt-cpt-widget qt-minimal-widget"><div class="qt-minimal-buttons">';
        foreach ($selected_cpts as $cpt) {
            $post_type_object = get_post_type_object($cpt);
            if ($post_type_object && current_user_can($post_type_object->cap->create_posts)) {
                $add_new_url = admin_url('post-new.php?post_type=' . $cpt);
                echo '<a href="' . esc_url($add_new_url) . '" class="button button-primary button-large qt-minimal-button">';
                echo '<span class="dashicons dashicons-plus-alt2"></span> ' . sprintf(__('Add %s', 'quick-tools'), esc_html($post_type_object->labels->singular_name));
                echo '</a>';
            }
        }
        echo '</div></div>';
    }

    public function render_informative_cpt_widget($post, $callback_args): void {
        // Implementation remains identical to provided original
        // Just ensuring class structure is valid
        $cpt = $callback_args['args']['cpt'];
        $post_type_object = $callback_args['args']['post_type_object'];
        
        $add_new_url = admin_url('post-new.php?post_type=' . $cpt);
        $manage_url = admin_url('edit.php?post_type=' . $cpt);
        
        $recent_posts = wp_count_posts($cpt);
        $published_count = isset($recent_posts->publish) ? $recent_posts->publish : 0;
        
        echo '<div class="qt-cpt-widget qt-informative-widget">';
        echo '<div class="qt-cpt-main-action" style="text-align:center; padding: 15px;">';
        echo '<a href="' . esc_url($add_new_url) . '" class="button button-primary button-hero" style="width:100%; text-align:center;">';
        echo sprintf(__('Add New %s', 'quick-tools'), esc_html($post_type_object->labels->singular_name));
        echo '</a></div>';
        
        // Stats
        echo '<div class="qt-cpt-stats" style="display:flex; justify-content:space-around; border-top:1px solid #eee; padding:10px;">';
        echo '<div><strong>' . $published_count . '</strong> Published</div>';
        echo '</div>';
        
        echo '<div class="qt-cpt-actions" style="padding:10px; text-align:center; border-top:1px solid #eee;">';
        echo '<a href="' . esc_url($manage_url) . '" class="button button-secondary">Manage All</a>';
        echo '</div></div>';
    }

    public static function get_available_post_types(): array {
        $post_types = get_post_types(array('_builtin' => false, 'public' => true), 'objects');
        unset($post_types['qt_documentation']);
        return $post_types;
    }
    
    public static function get_post_type_stats(string $post_type): array {
        $counts = wp_count_posts($post_type);
        return array(
            'published' => isset($counts->publish) ? $counts->publish : 0,
            'draft' => isset($counts->draft) ? $counts->draft : 0,
        );
    }
}
