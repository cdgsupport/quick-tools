<?php
/**
 * Documentation tab content
 */

if (!defined('WPINC')) {
    die;
}

if (isset($_POST['submit_documentation'])) {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'quick-tools-documentation-settings')) {
        wp_die('Security check failed');
    }
    
    $existing_settings = get_option('quick_tools_settings', array());
    $existing_settings['show_documentation_widgets'] = isset($_POST['show_documentation_widgets']) ? 1 : 0;
    $existing_settings['show_documentation_status'] = isset($_POST['show_documentation_status']) ? 1 : 0;
    $existing_settings['documentation_widget_limit'] = isset($_POST['documentation_widget_limit']) ? 
        max(1, min(10, intval($_POST['documentation_widget_limit']))) : 5;
    $existing_settings['documentation_module_style'] = $_POST['documentation_module_style'] ?? 'informative';
    
    update_option('quick_tools_settings', $existing_settings);
    
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Documentation settings saved!', 'quick-tools') . '</p></div>';
}

$settings = get_option('quick_tools_settings', array());
$module_style = isset($settings['documentation_module_style']) ? $settings['documentation_module_style'] : 'informative';
?>

<div class="qt-tab-panel" id="documentation-panel">
    <form method="post" action="">
        <?php wp_nonce_field('quick-tools-documentation-settings'); ?>
        
        <div class="qt-settings-section">
            <h2><?php _e('Documentation Dashboard Widgets', 'quick-tools'); ?></h2>
            <p class="description">
                <?php _e('Configure how documentation appears on the WordPress dashboard.', 'quick-tools'); ?>
            </p>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><?php _e('Enable Documentation Widgets', 'quick-tools'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="show_documentation_widgets" 
                                           value="1" <?php 
                                           $checked_value = isset($settings['show_documentation_widgets']) ? $settings['show_documentation_widgets'] : 1;
                                           if ($checked_value == 1) echo 'checked="checked"'; 
                                           ?>>
                                    <?php _e('Show documentation widgets on the dashboard', 'quick-tools'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Module Style', 'quick-tools'); ?></th>
                        <td>
                            <fieldset>
                                <div class="qt-module-style-options">
                                    <label class="qt-module-style-option">
                                        <input type="radio" name="documentation_module_style" 
                                               value="informative" 
                                               <?php checked($module_style, 'informative'); ?>>
                                        <span class="qt-module-style-label">
                                            <strong><?php _e('Informative', 'quick-tools'); ?></strong>
                                            <span class="qt-module-style-description">
                                                <?php _e('One widget per category with documentation items listed', 'quick-tools'); ?>
                                            </span>
                                        </span>
                                    </label>
                                    
                                    <label class="qt-module-style-option">
                                        <input type="radio" name="documentation_module_style" 
                                               value="minimal" 
                                               <?php checked($module_style, 'minimal'); ?>>
                                        <span class="qt-module-style-label">
                                            <strong><?php _e('Minimal', 'quick-tools'); ?></strong>
                                            <span class="qt-module-style-description">
                                                <?php _e('Single widget with one button per category', 'quick-tools'); ?>
                                            </span>
                                        </span>
                                    </label>
                                </div>
                            </fieldset>
                        </td>
                    </tr>

                    <tr class="qt-informative-options" <?php echo $module_style === 'minimal' ? 'style="display:none;"' : ''; ?>>
                        <th scope="row"><?php _e('Items per Widget', 'quick-tools'); ?></th>
                        <td>
                            <input type="number" name="documentation_widget_limit" 
                                   value="<?php echo esc_attr(isset($settings['documentation_widget_limit']) ? $settings['documentation_widget_limit'] : 5); ?>"
                                   min="1" max="10" class="small-text">
                        </td>
                    </tr>

                    <tr class="qt-informative-options" <?php echo $module_style === 'minimal' ? 'style="display:none;"' : ''; ?>>
                        <th scope="row"><?php _e('Status Indicators', 'quick-tools'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="show_documentation_status" 
                                           value="1" <?php 
                                           $checked_value = isset($settings['show_documentation_status']) ? $settings['show_documentation_status'] : 1;
                                           if ($checked_value == 1) echo 'checked="checked"'; 
                                           ?>>
                                    <?php _e('Show publication status in widgets', 'quick-tools'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="qt-settings-section">
            <h2><?php _e('Documentation Categories', 'quick-tools'); ?></h2>
            <div class="qt-categories-overview">
                <?php
                $categories = get_terms(array(
                    'taxonomy' => 'qt_documentation_category',
                    'hide_empty' => false,
                ));

                if (!empty($categories) && !is_wp_error($categories)) {
                    echo '<div class="qt-categories-grid">';
                    foreach ($categories as $category) {
                        echo '<div class="qt-category-card">';
                        echo '<h4>' . esc_html($category->name) . '</h4>';
                        echo '<p class="qt-category-count">' . $category->count . ' ' . __('items', 'quick-tools') . '</p>';
                        echo '</div>';
                    }
                    echo '</div>';
                }
                ?>
            </div>

            <p>
                <a href="<?php echo admin_url('edit-tags.php?taxonomy=qt_documentation_category&post_type=qt_documentation'); ?>" 
                   class="button button-secondary">
                    <?php _e('Manage Categories', 'quick-tools'); ?>
                </a>
                <a href="<?php echo admin_url('post-new.php?post_type=qt_documentation'); ?>" 
                   class="button button-primary">
                    <?php _e('Add New Documentation', 'quick-tools'); ?>
                </a>
            </p>
        </div>

        <?php submit_button(__('Save Documentation Settings', 'quick-tools'), 'primary', 'submit_documentation'); ?>
    </form>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    $('input[name="documentation_module_style"]').on('change', function() {
        if ($(this).val() === 'minimal') {
            $('.qt-informative-options').hide();
        } else {
            $('.qt-informative-options').show();
        }
    });
});
</script>
