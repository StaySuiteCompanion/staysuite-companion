<?php
/**
 * Plugin installer: activation / deactivation tasks.
 *
 * @package StaySuite\Companion
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles plugin lifecycle hooks.
 */
class Installer {

    /**
     * Required parent theme slug.
     *
     * @var string
     */
    const REQUIRED_THEME = 'wprentals';

    /**
     * Option flag marking the vs_* -> ssc_* data migration as done.
     *
     * @var string
     */
    const MIGRATION_FLAG = 'ssc_data_migrated';

    /**
     * Run on plugin activation.
     *
     * Refuses to activate unless WP Rentals is the active theme, then
     * registers post types, migrates legacy brand data and flushes rules.
     *
     * @return void
     */
    public static function activate() {
        if (!self::is_required_theme_active()) {
            deactivate_plugins(plugin_basename(SSC_FILE));
            wp_die(
                esc_html__('StaySuite Companion for WP Rentals requires the WP Rentals theme to be installed and activated.', 'staysuite-companion'),
                esc_html__('Plugin Activation Error', 'staysuite-companion'),
                array('response' => 200, 'back_link' => true)
            );
        }
        Hotel\HotelCPT::register();
        Booking\RequestCPT::register();
        self::maybe_migrate();
        flush_rewrite_rules();
    }

    /**
     * One-time migration from the Varsity Surfers brand keys.
     *
     * Renames post types (vs_hotel, vs_group_request), _vsc_* meta keys,
     * the vs-homepage page template, and vs/* blocks plus vs_* shortcodes
     * inside post content. Idempotent via option flag — safe to call on
     * every load; only pre-rename sites are touched.
     *
     * @return void
     */
    public static function maybe_migrate() {
        if (get_option(self::MIGRATION_FLAG)) {
            return;
        }
        global $wpdb;
        // phpcs:disable WordPress.DB.DirectDatabaseQuery -- one-time upgrade routine.
        $wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'ssc_hotel' WHERE post_type = 'vs_hotel'");
        $wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'ssc_group_request' WHERE post_type = 'vs_group_request'");
        $meta_like = $wpdb->esc_like('_vsc_') . '%';
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->postmeta} SET meta_key = REPLACE(meta_key, '_vsc_', '_ssc_') WHERE meta_key LIKE %s",
            $meta_like
        ));
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_key = '_wp_page_template' AND meta_value = %s",
            'ssc-homepage',
            'vs-homepage'
        ));
        foreach (array('<!-- wp:vs/' => '<!-- wp:ssc/', '[vs_' => '[ssc_') as $from => $to) {
            $content_like = '%' . $wpdb->esc_like($from) . '%';
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, %s, %s) WHERE post_content LIKE %s",
                $from,
                $to,
                $content_like
            ));
        }
        // phpcs:enable
        update_option(self::MIGRATION_FLAG, 1);
        flush_rewrite_rules();
    }

    /**
     * Check whether WP Rentals is installed and active.
     *
     * get_template() returns the parent theme slug, so child themes
     * built on WP Rentals pass this check.
     *
     * @return bool True when the required theme is active.
     */
    public static function is_required_theme_active() {
        if (get_template() !== self::REQUIRED_THEME) {
            return false;
        }
        return wp_get_theme(self::REQUIRED_THEME)->exists();
    }

    /**
     * Run on plugin deactivation.
     *
     * @return void
     */
    public static function deactivate() {
        flush_rewrite_rules();
    }
}
