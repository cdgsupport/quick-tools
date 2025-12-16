<?php
declare(strict_types=1);

/**
 * Quick Tools
 *
 * @package           QuickTools
 * @author            Crawford Design Group
 * @copyright         2025 Crawford Design Group
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Quick Tools
 * Plugin URI:        https://crawforddesigngp.com/plugins/quick-tools
 * Description:       Adds configurable documentation dashboard widgets and quick CPT creation tools for efficient website management.
 * Version:           1.3.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Crawford Design Group
 * Author URI:        https://crawforddesigngp.com
 * Text Domain:       quick-tools
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Plugin version
define('QUICK_TOOLS_VERSION', '1.2.0');
define('QUICK_TOOLS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('QUICK_TOOLS_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_quick_tools(): void {
    require_once QUICK_TOOLS_PLUGIN_DIR . 'includes/class-activator.php';
    Quick_Tools_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_quick_tools(): void {
    require_once QUICK_TOOLS_PLUGIN_DIR . 'includes/class-deactivator.php';
    Quick_Tools_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_quick_tools');
register_deactivation_hook(__FILE__, 'deactivate_quick_tools');

/**
 * The core plugin class
 */
require QUICK_TOOLS_PLUGIN_DIR . 'includes/class-quick-tools.php';

/**
 * Begins execution of the plugin.
 */
function run_quick_tools(): void {
    $plugin = new Quick_Tools();
    $plugin->run();
}

// Initialize the plugin
run_quick_tools();
