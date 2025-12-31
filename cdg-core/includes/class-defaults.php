<?php
/**
 * Defaults Class
 *
 * Handles default WordPress/Divi modifications including:
 * - Disabling Comments
 * - Hiding/Renaming Divi Projects
 * - Renaming Posts
 *
 * @package CDG_Core
 * @since 1.2.0
 */

declare(strict_types=1);

class CDG_Core_Defaults
{
    /**
     * Plugin instance
     *
     * @var CDG_Core
     */
    private CDG_Core $plugin;

    /**
     * Constructor
     *
     * @param CDG_Core $plugin Plugin instance
     */
    public function __construct(CDG_Core $plugin)
    {
        $this->plugin = $plugin;
        $this->setup_hooks();
    }

    /**
     * Setup hooks
     *
     * @return void
     */
    private function setup_hooks(): void
    {
        // Comments
        if ($this->plugin->get_setting('disable_comments')) {
            $this->setup_disable_comments();
        }

        // Divi Projects
        if ($this->plugin->get_setting('hide_divi_projects')) {
            $this->setup_hide_divi_projects();
        } elseif ($this->plugin->get_setting('enable_project_rename')) {
            // Only rename if not hiding
            add_action('init', [$this, 'rename_project_type'], 100);
            add_action('admin_menu', [$this, 'change_project_menu_icon'], 99);
        }

        // Post Rename
        if ($this->plugin->get_setting('enable_post_rename')) {
            add_action('init', [$this, 'rename_post_type'], 99);
            add_action('admin_menu', [$this, 'change_post_menu_icon'], 99);
            add_action('admin_init', [$this, 'add_page_attributes_to_posts']);
        }
    }

    /**
     * Setup hooks to disable comments completely
     *
     * @return void
     */
    private function setup_disable_comments(): void
    {
        // Remove comment support from all post types
        add_action('init', [$this, 'remove_comment_support'], 100);

        // Close comments on frontend
        add_filter('comments_open', '__return_false', 20, 2);
        add_filter('pings_open', '__return_false', 20, 2);

        // Hide existing comments
        add_filter('comments_array', '__return_empty_array', 10, 2);
        add_filter('get_comments_number', '__return_zero');

        // Remove comments from admin menu
        add_action('admin_menu', [$this, 'remove_comments_admin_menu'], 999);

        // Remove comments meta boxes
        add_action('admin_init', [$this, 'remove_comments_meta_boxes']);

        // Remove comments from admin bar
        add_action('wp_before_admin_bar_render', [$this, 'remove_comments_admin_bar']);

        // Remove Recent Comments dashboard widget
        add_action('wp_dashboard_setup', [$this, 'remove_comments_dashboard_widget'], 999);

        // Disable comments REST API
        add_filter('rest_endpoints', [$this, 'disable_comments_rest_api']);

        // Redirect comments pages
        add_action('admin_init', [$this, 'redirect_comments_admin_pages']);

        // Remove comment-related items from admin bar on frontend
        add_action('admin_bar_menu', [$this, 'remove_comments_admin_bar_menu'], 999);

        // Disable comment feeds
        add_action('do_feed_rss2_comments', [$this, 'disable_comment_feeds'], 1);
        add_action('do_feed_atom_comments', [$this, 'disable_comment_feeds'], 1);

        // Remove X-Pingback header
        add_filter('wp_headers', [$this, 'remove_pingback_header']);

        // Remove comment rewrite rules
        add_filter('rewrite_rules_array', [$this, 'remove_comment_rewrite_rules']);
    }

    /**
     * Remove comment support from all post types
     *
     * @return void
     */
    public function remove_comment_support(): void
    {
        $post_types = get_post_types(['public' => true], 'names');

        foreach ($post_types as $post_type) {
            if (post_type_supports($post_type, 'comments')) {
                remove_post_type_support($post_type, 'comments');
                remove_post_type_support($post_type, 'trackbacks');
            }
        }
    }

    /**
     * Remove comments from admin menu
     *
     * @return void
     */
    public function remove_comments_admin_menu(): void
    {
        remove_menu_page('edit-comments.php');
        remove_submenu_page('options-general.php', 'options-discussion.php');
    }

    /**
     * Remove comments meta boxes from all post types
     *
     * @return void
     */
    public function remove_comments_meta_boxes(): void
    {
        $post_types = get_post_types(['public' => true], 'names');

        foreach ($post_types as $post_type) {
            remove_meta_box('commentstatusdiv', $post_type, 'normal');
            remove_meta_box('commentsdiv', $post_type, 'normal');
            remove_meta_box('trackbacksdiv', $post_type, 'normal');
        }
    }

    /**
     * Remove comments from admin bar
     *
     * @return void
     */
    public function remove_comments_admin_bar(): void
    {
        global $wp_admin_bar;

        if ($wp_admin_bar) {
            $wp_admin_bar->remove_menu('comments');
        }
    }

    /**
     * Remove comments admin bar menu items
     *
     * @param WP_Admin_Bar $wp_admin_bar Admin bar instance
     * @return void
     */
    public function remove_comments_admin_bar_menu($wp_admin_bar): void
    {
        $wp_admin_bar->remove_node('comments');
    }

    /**
     * Remove Recent Comments dashboard widget
     *
     * @return void
     */
    public function remove_comments_dashboard_widget(): void
    {
        remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
    }

    /**
     * Disable comments REST API endpoints
     *
     * @param array<string, mixed> $endpoints REST API endpoints
     * @return array<string, mixed>
     */
    public function disable_comments_rest_api(array $endpoints): array
    {
        unset($endpoints['/wp/v2/comments']);
        unset($endpoints['/wp/v2/comments/(?P<id>[\d]+)']);

        return $endpoints;
    }

    /**
     * Redirect any direct access to comments admin pages
     *
     * @return void
     */
    public function redirect_comments_admin_pages(): void
    {
        global $pagenow;

        if (!$pagenow) {
            return;
        }

        $blocked_pages = [
            'edit-comments.php',
            'options-discussion.php',
        ];

        if (in_array($pagenow, $blocked_pages, true)) {
            wp_safe_redirect(admin_url());
            exit;
        }
    }

    /**
     * Disable comment feeds
     *
     * @return void
     */
    public function disable_comment_feeds(): void
    {
        wp_die(
            esc_html__('Comments are disabled on this site.', 'cdg-core'),
            '',
            ['response' => 403]
        );
    }

    /**
     * Remove X-Pingback header
     *
     * @param array<string, string> $headers HTTP headers
     * @return array<string, string>
     */
    public function remove_pingback_header(array $headers): array
    {
        unset($headers['X-Pingback']);

        return $headers;
    }

    /**
     * Remove comment-related rewrite rules
     *
     * @param array<string, string> $rules Rewrite rules
     * @return array<string, string>
     */
    public function remove_comment_rewrite_rules(array $rules): array
    {
        foreach ($rules as $rule => $rewrite) {
            if (preg_match('/comment|comments/', $rule)) {
                unset($rules[$rule]);
            }
        }

        return $rules;
    }

    /**
     * Setup hooks to hide Divi Projects
     *
     * @return void
     */
    private function setup_hide_divi_projects(): void
    {
        // Unregister the project post type
        add_action('init', [$this, 'unregister_divi_projects'], 100);

        // Remove from admin menu as backup
        add_action('admin_menu', [$this, 'remove_projects_admin_menu'], 999);

        // Redirect any direct access to projects admin pages
        add_action('admin_init', [$this, 'redirect_projects_admin_pages']);
    }

    /**
     * Unregister Divi Projects post type and taxonomies
     *
     * @return void
     */
    public function unregister_divi_projects(): void
    {
        // Unregister the project post type
        if (post_type_exists('project')) {
            unregister_post_type('project');
        }

        // Unregister project taxonomies
        if (taxonomy_exists('project_category')) {
            unregister_taxonomy('project_category');
        }

        if (taxonomy_exists('project_tag')) {
            unregister_taxonomy('project_tag');
        }
    }

    /**
     * Remove Projects from admin menu
     *
     * @return void
     */
    public function remove_projects_admin_menu(): void
    {
        remove_menu_page('edit.php?post_type=project');
    }

    /**
     * Redirect any direct access to projects admin pages
     *
     * @return void
     */
    public function redirect_projects_admin_pages(): void
    {
        global $pagenow;

        if (!$pagenow) {
            return;
        }

        // Check if accessing project-related pages
        $is_project_page = false;

        if (in_array($pagenow, ['edit.php', 'post-new.php', 'post.php'], true)) {
            $post_type = isset($_GET['post_type']) ? sanitize_text_field(wp_unslash($_GET['post_type'])) : '';

            if ($post_type === 'project') {
                $is_project_page = true;
            }

            // Check for editing existing project
            if ($pagenow === 'post.php' && isset($_GET['post'])) {
                $post_id = absint($_GET['post']);
                $post = get_post($post_id);

                if ($post && $post->post_type === 'project') {
                    $is_project_page = true;
                }
            }
        }

        // Check taxonomy pages
        if ($pagenow === 'edit-tags.php') {
            $taxonomy = isset($_GET['taxonomy']) ? sanitize_text_field(wp_unslash($_GET['taxonomy'])) : '';

            if (in_array($taxonomy, ['project_category', 'project_tag'], true)) {
                $is_project_page = true;
            }
        }

        if ($is_project_page) {
            wp_safe_redirect(admin_url());
            exit;
        }
    }

    /**
     * Rename Divi project post type labels
     *
     * @return void
     */
    public function rename_project_type(): void
    {
        $post_type_object = get_post_type_object('project');

        if (!$post_type_object || !$post_type_object->labels) {
            return;
        }

        $plural = $this->plugin->get_setting('project_rename_plural');
        $singular = $this->plugin->get_setting('project_rename_singular');
        $menu = $this->plugin->get_setting('project_rename_menu');

        if (empty($plural) || empty($singular)) {
            return;
        }

        $labels = $post_type_object->labels;

        $labels->name = $plural;
        $labels->singular_name = $singular;
        $labels->menu_name = !empty($menu) ? $menu : $plural;
        $labels->name_admin_bar = $singular;
        $labels->add_new = sprintf(__('Add %s', 'cdg-core'), $singular);
        $labels->add_new_item = sprintf(__('Add New %s', 'cdg-core'), $singular);
        $labels->edit_item = sprintf(__('Edit %s', 'cdg-core'), $singular);
        $labels->new_item = sprintf(__('New %s', 'cdg-core'), $singular);
        $labels->view_item = sprintf(__('View %s', 'cdg-core'), $singular);
        $labels->view_items = sprintf(__('View %s', 'cdg-core'), $plural);
        $labels->search_items = sprintf(__('Search %s', 'cdg-core'), $plural);
        $labels->not_found = sprintf(__('No %s found', 'cdg-core'), strtolower($plural));
        $labels->not_found_in_trash = sprintf(__('No %s found in Trash', 'cdg-core'), strtolower($plural));
        $labels->all_items = sprintf(__('All %s', 'cdg-core'), $plural);
        $labels->archives = sprintf(__('%s Archives', 'cdg-core'), $singular);
        $labels->attributes = sprintf(__('%s Attributes', 'cdg-core'), $singular);
        $labels->insert_into_item = sprintf(__('Insert into %s', 'cdg-core'), strtolower($singular));
        $labels->uploaded_to_this_item = sprintf(__('Uploaded to this %s', 'cdg-core'), strtolower($singular));
        $labels->filter_items_list = sprintf(__('Filter %s list', 'cdg-core'), strtolower($plural));
        $labels->items_list_navigation = sprintf(__('%s list navigation', 'cdg-core'), $plural);
        $labels->items_list = sprintf(__('%s list', 'cdg-core'), $plural);
        $labels->item_published = sprintf(__('%s published.', 'cdg-core'), $singular);
        $labels->item_published_privately = sprintf(__('%s published privately.', 'cdg-core'), $singular);
        $labels->item_reverted_to_draft = sprintf(__('%s reverted to draft.', 'cdg-core'), $singular);
        $labels->item_scheduled = sprintf(__('%s scheduled.', 'cdg-core'), $singular);
        $labels->item_updated = sprintf(__('%s updated.', 'cdg-core'), $singular);
    }

    /**
     * Change project menu icon
     *
     * @return void
     */
    public function change_project_menu_icon(): void
    {
        global $menu;

        $icon = $this->plugin->get_setting('project_rename_icon');

        if (empty($icon)) {
            return;
        }

        foreach ($menu as $key => $item) {
            if (isset($item[2]) && $item[2] === 'edit.php?post_type=project') {
                $menu[$key][6] = $icon;
                break;
            }
        }
    }

    /**
     * Rename post type labels
     *
     * @return void
     */
    public function rename_post_type(): void
    {
        $post_type_object = get_post_type_object('post');

        if (!$post_type_object || !$post_type_object->labels) {
            return;
        }

        $plural = $this->plugin->get_setting('post_rename_plural');
        $singular = $this->plugin->get_setting('post_rename_singular');
        $menu = $this->plugin->get_setting('post_rename_menu');

        if (empty($plural) || empty($singular)) {
            return;
        }

        $labels = $post_type_object->labels;

        $labels->name = $plural;
        $labels->singular_name = $singular;
        $labels->menu_name = !empty($menu) ? $menu : $plural;
        $labels->name_admin_bar = $singular;
        $labels->add_new = sprintf(__('Add %s', 'cdg-core'), $singular);
        $labels->add_new_item = sprintf(__('Add New %s', 'cdg-core'), $singular);
        $labels->edit_item = sprintf(__('Edit %s', 'cdg-core'), $singular);
        $labels->new_item = sprintf(__('New %s', 'cdg-core'), $singular);
        $labels->view_item = sprintf(__('View %s', 'cdg-core'), $singular);
        $labels->view_items = sprintf(__('View %s', 'cdg-core'), $plural);
        $labels->search_items = sprintf(__('Search %s', 'cdg-core'), $plural);
        $labels->not_found = sprintf(__('No %s found', 'cdg-core'), strtolower($plural));
        $labels->not_found_in_trash = sprintf(__('No %s found in Trash', 'cdg-core'), strtolower($plural));
        $labels->all_items = sprintf(__('All %s', 'cdg-core'), $plural);
        $labels->archives = sprintf(__('%s Archives', 'cdg-core'), $singular);
        $labels->attributes = sprintf(__('%s Attributes', 'cdg-core'), $singular);
        $labels->insert_into_item = sprintf(__('Insert into %s', 'cdg-core'), strtolower($singular));
        $labels->uploaded_to_this_item = sprintf(__('Uploaded to this %s', 'cdg-core'), strtolower($singular));
        $labels->filter_items_list = sprintf(__('Filter %s list', 'cdg-core'), strtolower($plural));
        $labels->items_list_navigation = sprintf(__('%s list navigation', 'cdg-core'), $plural);
        $labels->items_list = sprintf(__('%s list', 'cdg-core'), $plural);
        $labels->item_published = sprintf(__('%s published.', 'cdg-core'), $singular);
        $labels->item_published_privately = sprintf(__('%s published privately.', 'cdg-core'), $singular);
        $labels->item_reverted_to_draft = sprintf(__('%s reverted to draft.', 'cdg-core'), $singular);
        $labels->item_scheduled = sprintf(__('%s scheduled.', 'cdg-core'), $singular);
        $labels->item_updated = sprintf(__('%s updated.', 'cdg-core'), $singular);
    }

    /**
     * Change post menu icon
     *
     * @return void
     */
    public function change_post_menu_icon(): void
    {
        global $menu;

        $icon = $this->plugin->get_setting('post_rename_icon');

        if (empty($icon)) {
            return;
        }

        foreach ($menu as $key => $item) {
            if (isset($item[2]) && $item[2] === 'edit.php') {
                $menu[$key][6] = $icon;
                break;
            }
        }
    }

    /**
     * Add page attributes support to posts
     *
     * @return void
     */
    public function add_page_attributes_to_posts(): void
    {
        add_post_type_support('post', 'page-attributes');
    }

    /**
     * Get available dashicons for admin
     *
     * @return array<string, string>
     */
    public static function get_available_icons(): array
    {
        return [
            'dashicons-admin-post' => 'Post (Default)',
            'dashicons-slides' => 'Slides',
            'dashicons-images-alt2' => 'Images',
            'dashicons-format-gallery' => 'Gallery',
            'dashicons-format-image' => 'Image',
            'dashicons-camera' => 'Camera',
            'dashicons-video-alt3' => 'Video',
            'dashicons-microphone' => 'Microphone',
            'dashicons-portfolio' => 'Portfolio',
            'dashicons-book' => 'Book',
            'dashicons-book-alt' => 'Book Alt',
            'dashicons-media-document' => 'Document',
            'dashicons-media-text' => 'Text',
            'dashicons-testimonial' => 'Testimonial',
            'dashicons-star-filled' => 'Star',
            'dashicons-heart' => 'Heart',
            'dashicons-awards' => 'Awards',
            'dashicons-calendar-alt' => 'Calendar',
            'dashicons-location' => 'Location',
            'dashicons-businessman' => 'Person',
            'dashicons-groups' => 'Groups',
            'dashicons-products' => 'Products',
            'dashicons-cart' => 'Cart',
            'dashicons-store' => 'Store',
            'dashicons-building' => 'Building',
            'dashicons-hammer' => 'Tools',
            'dashicons-clipboard' => 'Clipboard',
            'dashicons-analytics' => 'Analytics',
            'dashicons-chart-bar' => 'Chart',
            'dashicons-megaphone' => 'Megaphone',
            'dashicons-email' => 'Email',
            'dashicons-admin-links' => 'Links',
            'dashicons-admin-generic' => 'Generic',
        ];
    }
}
