<?php
/**
 * StaySuite top-level menu, tabbed React settings shell and behavior flags.
 *
 * All StaySuite sections live on one page (menu slug ssc-staysuite) with
 * tabs registered through the ssc.admin.tabs JS filter, so Pro injects
 * License/AI tabs without touching free. Go Pro only renders when Pro
 * is absent. Settings persist via the ssc/v1/settings REST route.
 *
 * @package StaySuite\Companion\Admin
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion\Admin;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Menu, settings storage, REST and frontend behavior flags.
 */
class Settings {

    /**
     * Option key for the settings array.
     *
     * @var string
     */
    const OPTION = 'ssc_settings';

    /**
     * StaySuite menu slug.
     *
     * @var string
     */
    const MENU_SLUG = 'ssc-staysuite';

    /**
     * Default values.
     *
     * @return array<string,mixed> Defaults.
     */
    public static function defaults() {
        return array(
            'capsule'        => 1,
            'default_adults' => 2,
            'dividers'       => 1,
            'animations'     => 1,
            'hero_height'    => 75,
            'color_mode'     => 'theme',
            'color_submit'   => '#137699',
            'color_hover'    => '#022947',
        );
    }

    /**
     * Read and sanitize settings.
     *
     * @param string|null $key Single key or null for all.
     * @return mixed Value or full array.
     */
    public static function get($key = null) {
        $all = wp_parse_args((array) get_option(self::OPTION, array()), self::defaults());
        $all['capsule'] = !empty($all['capsule']) ? 1 : 0;
        $all['dividers'] = !empty($all['dividers']) ? 1 : 0;
        $all['animations'] = !empty($all['animations']) ? 1 : 0;
        $all['default_adults'] = min(6, max(0, intval($all['default_adults'])));
        $all['hero_height'] = min(100, max(30, intval($all['hero_height'])));
        $all['color_mode'] = ($all['color_mode'] === 'custom') ? 'custom' : 'theme';
        $all['color_submit'] = sanitize_hex_color((string) $all['color_submit']) ?: '#137699';
        $all['color_hover'] = sanitize_hex_color((string) $all['color_hover']) ?: '#022947';
        if ($key === null) {
            return $all;
        }
        return array_key_exists($key, $all) ? $all[$key] : null;
    }

    /**
     * Sanitize a raw settings payload (REST + legacy shapes).
     *
     * @param array<string,mixed> $raw Raw input.
     * @return array<string,mixed> Sanitized settings.
     */
    public static function sanitize($raw) {
        if (!is_array($raw)) {
            $raw = array();
        }
        return array(
            'capsule'        => !empty($raw['capsule']) ? 1 : 0,
            'default_adults' => isset($raw['default_adults']) ? min(6, max(0, intval($raw['default_adults']))) : 2,
            'dividers'       => !empty($raw['dividers']) ? 1 : 0,
            'animations'     => !empty($raw['animations']) ? 1 : 0,
            'hero_height'    => isset($raw['hero_height']) ? min(100, max(30, intval($raw['hero_height']))) : 75,
            'color_mode'     => (isset($raw['color_mode']) && $raw['color_mode'] === 'custom') ? 'custom' : 'theme',
            'color_submit'   => isset($raw['color_submit']) ? (sanitize_hex_color((string) $raw['color_submit']) ?: '#137699') : '#137699',
            'color_hover'    => isset($raw['color_hover']) ? (sanitize_hex_color((string) $raw['color_hover']) ?: '#022947') : '#022947',
        );
    }

    /**
     * Wire up hooks.
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_assets'));
        add_action('rest_api_init', array($this, 'rest_routes'));
        add_filter('body_class', array($this, 'body_classes'));
        add_filter('submenu_file', array($this, 'highlight_tab'), 10, 2);
    }

    /**
     * Register the StaySuite top-level menu (below Hotels).
     *
     * The page itself is the tab shell; each tab also gets a submenu
     * entry deep-linking via ?tab= so sections stay discoverable.
     *
     * @return void
     */
    public function menu() {
        add_menu_page(
            esc_html__('StaySuite', 'staysuite-companion'),
            esc_html__('StaySuite', 'staysuite-companion'),
            'manage_options',
            self::MENU_SLUG,
            array($this, 'render_page'),
            self::logo_icon(),
            26
        );
        add_submenu_page(
            self::MENU_SLUG,
            esc_html__('Settings', 'staysuite-companion'),
            esc_html__('Settings', 'staysuite-companion'),
            'manage_options',
            self::MENU_SLUG . '&tab=settings',
            array($this, 'render_page')
        );
        if (!defined('SSC_PRO_VERSION')) {
            add_submenu_page(
                self::MENU_SLUG,
                esc_html__('Go Pro', 'staysuite-companion'),
                esc_html__('Go Pro', 'staysuite-companion'),
                'manage_options',
                self::MENU_SLUG . '&tab=go-pro',
                array($this, 'render_page')
            );
        }
    }

    /**
     * Highlight the matching tab submenu on the single page.
     *
     * @param string|false $submenu_file Current submenu file.
     * @param string       $parent_file  Current parent file.
     * @return string|false Submenu file with tab suffix when applicable.
     */
    public function highlight_tab($submenu_file, $parent_file) {
        if ($parent_file !== self::MENU_SLUG || !isset($_GET['tab'])) {
            return $submenu_file;
        }
        $tab = sanitize_key(wp_unslash($_GET['tab']));
        if (!in_array($tab, array('settings', 'go-pro'), true)) {
            return $submenu_file;
        }
        return self::MENU_SLUG . '&tab=' . $tab;
    }

    /**
     * Brand SVG as menu icon.
     *
     * @return string Data URI or dashicon fallback.
     */
    public static function logo_icon() {
        static $icon = null;
        if ($icon === null) {
            $svg = SSC_PATH . 'assets/images/staysuite-logo-menu.svg';
            $icon = is_readable($svg)
                ? 'data:image/svg+xml;base64,' . base64_encode((string) file_get_contents($svg))
                : 'dashicons-admin-generic';
        }
        return $icon;
    }

    /**
     * Render the tab shell (React mounts here).
     *
     * @return void
     */
    public function render_page() {
        printf(
            '<div id="ssc-admin-root" data-pro="%d" data-logo="%s"></div>',
            defined('SSC_PRO_VERSION') ? 1 : 0,
            esc_url(SSC_URL . 'assets/images/staysuite-logo.svg')
        );
    }

    /**
     * Load the admin tab bundle on the StaySuite page.
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public function admin_assets($hook) {
        if ($hook !== 'toplevel_page_' . self::MENU_SLUG) {
            return;
        }
        $asset = $this->app_asset();
        wp_enqueue_script(
            'ssc-admin',
            SSC_URL . 'assets/build/admin.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );
        wp_enqueue_style(
            'ssc-admin',
            SSC_URL . 'assets/css/ssc-admin.css',
            array(),
            $this->css_version()
        );
    }

    /**
     * Read the wp-scripts asset manifest for the admin bundle.
     *
     * @return array{dependencies: string[], version: string} Asset data.
     */
    private function app_asset() {
        $fallback = array('dependencies' => array('wp-element', 'wp-hooks', 'wp-api-fetch', 'wp-i18n', 'wp-components'), 'version' => SSC_VERSION);
        $path = SSC_PATH . 'assets/build/admin.asset.php';
        if (!file_exists($path)) {
            return $fallback;
        }
        $asset = include $path;
        if (!is_array($asset)) {
            return $fallback;
        }
        return array(
            'dependencies' => isset($asset['dependencies']) ? (array) $asset['dependencies'] : $fallback['dependencies'],
            'version'      => isset($asset['version']) ? (string) $asset['version'] : SSC_VERSION,
        );
    }

    /**
     * Version the admin stylesheet by content hash.
     *
     * @return string Cache-busting version.
     */
    private function css_version() {
        $path = SSC_PATH . 'assets/css/ssc-admin.css';
        if (file_exists($path)) {
            $hash = md5_file($path);
            if (is_string($hash) && $hash !== '') {
                return substr($hash, 0, 12);
            }
        }
        return SSC_VERSION;
    }

    /**
     * Register the settings REST route.
     *
     * @return void
     */
    public function rest_routes() {
        register_rest_route('ssc/v1', '/settings', array(
            array(
                'methods'             => 'GET',
                'callback'            => array($this, 'rest_get'),
                'permission_callback' => array($this, 'rest_auth'),
            ),
            array(
                'methods'             => 'POST',
                'callback'            => array($this, 'rest_save'),
                'permission_callback' => array($this, 'rest_auth'),
            ),
        ));
    }

    /**
     * Capability check for settings routes.
     *
     * @return bool True for admins.
     */
    public function rest_auth() {
        return current_user_can('manage_options');
    }

    /**
     * Return current settings.
     *
     * @return WP_REST_Response Settings payload.
     */
    public function rest_get() {
        return new WP_REST_Response(array('settings' => self::get()), 200);
    }

    /**
     * Save settings from a JSON payload.
     *
     * @param WP_REST_Request $request REST request.
     * @return WP_REST_Response|WP_Error Fresh settings or error.
     */
    public function rest_save($request) {
        $raw = $request->get_param('settings');
        if (!is_array($raw)) {
            return new WP_Error('ssc_bad_settings', esc_html__('Settings payload missing.', 'staysuite-companion'), array('status' => 400));
        }
        update_option(self::OPTION, self::sanitize($raw));
        return new WP_REST_Response(array('settings' => self::get()), 200);
    }

    /**
     * Flag disabled behaviors for CSS/JS.
     *
     * @param string[] $classes Body classes.
     * @return string[] Classes with flags added.
     */
    public function body_classes($classes) {
        if (is_admin()) {
            return $classes;
        }
        if (!self::get('dividers')) {
            $classes[] = 'ssc-no-dividers';
        }
        if (!self::get('animations')) {
            $classes[] = 'ssc-no-animations';
        }
        return $classes;
    }
}
