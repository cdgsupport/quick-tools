<?php
declare(strict_types=1);

/**
 * Fired during plugin deactivation.
 *
 * @package QuickTools
 * @since 1.0.0
 */
class Quick_Tools_Deactivator {

    /**
     * Deactivate the plugin.
     *
     * Cleans up rewrite rules and removes temporary flags.
     * Note: Settings and content are preserved for potential reactivation.
     */
    public static function deactivate(): void {
        // Flush rewrite rules to clean up
        flush_rewrite_rules();

        // Remove activation flag
        delete_option('quick_tools_activated');

        // Note: We don't delete settings or content on deactivation
        // This preserves user data in case they reactivate
    }
}
