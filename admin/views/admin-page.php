<?php
/**
 * Provide a admin area view for the plugin
 */
if (!defined('WPINC')) {
    die;
}

$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'documentation';
?>

<div class="wrap qt-admin-page">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php
    if (get_option('quick_tools_activated')) {
        delete_option('quick_tools_activated');
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Quick Tools has been activated! Configure your settings below.', 'quick-tools'); ?></p>
        </div>
        <?php
    }
    ?>

    <div class="qt-admin-header">
        <p class="qt-description">
            <?php _e('Quick Tools provides dashboard widgets for documentation and custom post type management to streamline your workflow.', 'quick-tools'); ?>
        </p>
    </div>

    <nav class="nav-tab-wrapper qt-nav-tabs">
        <a href="?page=quick-tools&tab=documentation" 
           class="nav-tab <?php echo $active_tab === 'documentation' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-media-document"></span>
            <?php _e('Documentation', 'quick-tools'); ?>
        </a>
        <a href="?page=quick-tools&tab=cpt-dashboard" 
           class="nav-tab <?php echo $active_tab === 'cpt-dashboard' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-admin-post"></span>
            <?php _e('CPT Dashboard', 'quick-tools'); ?>
        </a>
        <a href="?page=quick-tools&tab=import-export" 
           class="nav-tab <?php echo $active_tab === 'import-export' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-download"></span>
            <?php _e('Import/Export', 'quick-tools'); ?>
        </a>
    </nav>

    <div class="qt-tab-content">
        <?php
        switch ($active_tab) {
            case 'documentation':
                include_once QUICK_TOOLS_PLUGIN_DIR . 'admin/views/documentation-tab.php';
                break;
            case 'cpt-dashboard':
                include_once QUICK_TOOLS_PLUGIN_DIR . 'admin/views/cpt-dashboard-tab.php';
                break;
            case 'import-export':
                include_once QUICK_TOOLS_PLUGIN_DIR . 'admin/views/import-export-tab.php';
                break;
            default:
                include_once QUICK_TOOLS_PLUGIN_DIR . 'admin/views/documentation-tab.php';
                break;
        }
        ?>
    </div>

    <div class="qt-sidebar">
        <div class="qt-sidebar-box">
            <h3><?php _e('Quick Stats', 'quick-tools'); ?></h3>
            <?php
            $doc_count = wp_count_posts('qt_documentation');
            $settings = get_option('quick_tools_settings', array());
            $selected_cpts_count = 0;
            if (isset($settings['selected_cpts'])) {
                $selected_cpts_count = count($settings['selected_cpts']);
            }
            
            $category_count = wp_count_terms(array(
                'taxonomy' => 'qt_documentation_category',
                'hide_empty' => false,
            ));
            if (is_wp_error($category_count)) {
                $category_count = 0;
            }
            ?>
            <ul class="qt-stats-list">
                <li>
                    <strong><?php echo esc_html($doc_count->publish ?? 0); ?></strong>
                    <?php _e('Documentation Items', 'quick-tools'); ?>
                </li>
                <li>
                    <strong><?php echo esc_html($selected_cpts_count); ?></strong>
                    <?php _e('Active CPT Widgets', 'quick-tools'); ?>
                </li>
                <li>
                    <strong><?php echo esc_html($category_count); ?></strong>
                    <?php _e('Documentation Categories', 'quick-tools'); ?>
                </li>
            </ul>
        </div>

        <div class="qt-sidebar-box">
            <h3><?php _e('Quick Actions', 'quick-tools'); ?></h3>
            <p>
                <a href="<?php echo admin_url('post-new.php?post_type=qt_documentation'); ?>" 
                   class="button button-primary">
                    <?php _e('Add Documentation', 'quick-tools'); ?>
                </a>
            </p>
            <p>
                <a href="<?php echo admin_url('edit.php?post_type=qt_documentation'); ?>" 
                   class="button button-secondary">
                    <?php _e('Manage Documentation', 'quick-tools'); ?>
                </a>
            </p>
            <p>
                <a href="<?php echo admin_url('index.php'); ?>" 
                   class="button button-secondary">
                    <?php _e('View Dashboard', 'quick-tools'); ?>
                </a>
            </p>
        </div>

        <div class="qt-sidebar-box">
            <h3><?php _e('Support', 'quick-tools'); ?></h3>
            <p><?php _e('Need help with Quick Tools?', 'quick-tools'); ?></p>
            <p>
                <strong><?php _e('Crawford Design Group', 'quick-tools'); ?></strong><br>
                <a href="https://crawforddesigngp.com" target="_blank"><?php _e('Visit Website', 'quick-tools'); ?></a>
            </p>
        </div>
    </div>
</div>
