<?php
if (!defined('WPINC')) die;

// Process Form
if (isset($_POST['submit_cpt'])) {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'quick-tools-cpt-settings')) wp_die('Security check failed');
    
    $existing_settings = get_option('quick_tools_settings', array());
    
    $existing_settings['show_cpt_widgets'] = isset($_POST['show_cpt_widgets']) ? 1 : 0;
    $existing_settings['cpt_module_style'] = $_POST['cpt_module_style'] ?? 'informative';
    
    $selected_cpts = array();
    if (isset($_POST['cpt_config']) && is_array($_POST['cpt_config'])) {
        foreach ($_POST['cpt_config'] as $cpt_slug => $config) {
            if (isset($config['enabled']) && $config['enabled'] == 1) {
                $selected_cpts[$cpt_slug] = array(
                    'location' => sanitize_text_field($config['location'])
                );
            }
        }
    }
    $existing_settings['selected_cpts'] = $selected_cpts;
    
    update_option('quick_tools_settings', $existing_settings);
    echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
}

$settings = get_option('quick_tools_settings', array());
$post_types = Quick_Tools_CPT_Dashboard::get_available_post_types();
$available_pages = Quick_Tools_Admin::get_registered_options_pages();
$module_style = $settings['cpt_module_style'] ?? 'informative';

$current_cpts = array();
if (isset($settings['selected_cpts'])) {
    foreach ($settings['selected_cpts'] as $key => $val) {
        if (is_array($val)) $current_cpts[$key] = $val;
        else $current_cpts[$val] = array('location' => 'dashboard');
    }
}
?>

<form method="post" action="">
    <?php wp_nonce_field('quick-tools-cpt-settings'); ?>
    
    <div class="qt-form-section">
        <h2 class="qt-section-title"><?php _e('Global Settings', 'quick-tools'); ?></h2>
        
        <div class="qt-toggle-wrapper">
            <label class="qt-toggle">
                <input type="checkbox" name="show_cpt_widgets" value="1" 
                       <?php checked(isset($settings['show_cpt_widgets']) ? $settings['show_cpt_widgets'] : 1, 1); ?>>
                <span class="qt-slider"></span>
            </label>
            <span class="qt-text-muted">Enable Quick Add functionality</span>
        </div>

        <div style="max-width: 400px;">
            <p class="qt-text-muted"><strong>Dashboard Widget Style</strong></p>
            <div class="qt-btn-group">
                <input type="radio" id="style_informative" name="cpt_module_style" value="informative" <?php checked($module_style, 'informative'); ?>>
                <label for="style_informative">Informative</label>
                
                <input type="radio" id="style_minimal" name="cpt_module_style" value="minimal" <?php checked($module_style, 'minimal'); ?>>
                <label for="style_minimal">Minimal</label>
            </div>
            <p class="description">Style only applies to Dashboard widgets. Options page buttons always appear as a notification bar.</p>
        </div>
    </div>

    <div class="qt-form-section">
        <h2 class="qt-section-title"><?php _e('Post Type Configuration', 'quick-tools'); ?></h2>
        <p class="description qt-mb-3">Select post types and choose where their "Add New" button should appear.</p>

        <?php if (empty($post_types)) : ?>
            <div class="notice notice-warning inline"><p>No custom post types found.</p></div>
        <?php else : ?>
            <div class="qt-cpt-grid">
                <?php foreach ($post_types as $pt) : 
                    $is_enabled = array_key_exists($pt->name, $current_cpts);
                    $location = $is_enabled ? ($current_cpts[$pt->name]['location'] ?? 'dashboard') : 'dashboard';
                    $stats = Quick_Tools_CPT_Dashboard::get_post_type_stats($pt->name);
                ?>
                <div class="qt-cpt-item <?php echo $is_enabled ? 'active' : ''; ?>">
                    <div class="qt-cpt-header">
                        <div class="qt-toggle-wrapper" style="margin-bottom:0;">
                            <label class="qt-toggle" style="transform:scale(0.8); margin-right: 5px;">
                                <input type="checkbox" 
                                       name="cpt_config[<?php echo esc_attr($pt->name); ?>][enabled]" 
                                       value="1" 
                                       class="qt-cpt-check"
                                       <?php checked($is_enabled, true); ?>>
                                <span class="qt-slider"></span>
                            </label>
                            <strong><?php echo esc_html($pt->labels->name); ?></strong>
                        </div>
                        <span class="qt-badge"><?php echo $pt->name; ?></span>
                    </div>
                    <div class="qt-cpt-body">
                        <div class="qt-stats-row">
                            <span><?php echo $stats['published']; ?> Published</span>
                            <span><?php echo $stats['draft']; ?> Drafts</span>
                        </div>
                        
                        <div class="qt-location-select">
                            <label class="description" style="display:block; margin-bottom:4px;">Button Location</label>
                            <select class="qt-full-width" name="cpt_config[<?php echo esc_attr($pt->name); ?>][location]">
                                <option value="dashboard" <?php selected($location, 'dashboard'); ?>>Dashboard Widget</option>
                                <?php if (!empty($available_pages)): ?>
                                    <optgroup label="Custom Options Pages">
                                        <?php foreach ($available_pages as $slug => $title): ?>
                                            <option value="<?php echo esc_attr($slug); ?>" <?php selected($location, $slug); ?>>
                                                <?php echo esc_html($title); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <p class="submit">
        <button type="submit" name="submit_cpt" class="button button-primary button-large">
            Save Settings
        </button>
    </p>
</form>

<script>
jQuery(document).ready(function($) {
    $('.qt-cpt-check').on('change', function() {
        if($(this).is(':checked')) {
            $(this).closest('.qt-cpt-item').addClass('active');
        } else {
            $(this).closest('.qt-cpt-item').removeClass('active');
        }
    });
});
</script>
