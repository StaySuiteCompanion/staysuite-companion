<?php
/**
 * Frontend asset registration (styles + React build).
 *
 * @package StaySuite\Companion\Frontend
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion\Frontend;

use StaySuite\Companion\Admin\Settings;
use StaySuite\Companion\Booking\QuoteAjax;
use StaySuite\Companion\Hotel\HotelCPT;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueues plugin CSS and the compiled React app.
 */
class Scripts {

    /**
     * Script handle for the React app.
     *
     * @var string
     */
    const APP_HANDLE = 'ssc-app';

    /**
     * Wire up WordPress hooks.
     *
     * @return void
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue'));
        add_action('enqueue_block_assets', array($this, 'enqueue_editor'));
    }

    /**
     * Enqueue styles and app script on relevant frontend pages.
     *
     * @return void
     */
    public function enqueue() {
        if (!$this->should_load()) {
            return;
        }
        wp_enqueue_style('ssc-hotel', SSC_URL . 'assets/css/ssc-hotel.css', array(), $this->css_version());
        wp_add_inline_style('ssc-hotel', Theme::inline_vars());

        $asset = $this->get_app_asset();
        wp_enqueue_script(
            self::APP_HANDLE,
            SSC_URL . 'assets/build/index.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );
        wp_localize_script(self::APP_HANDLE, 'sscCards', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce(CardBadge::NONCE_ACTION),
        ));
        wp_localize_script(self::APP_HANDLE, 'sscBooking', array(
            'ajaxurl'     => admin_url('admin-ajax.php'),
            'quote_nonce' => wp_create_nonce(QuoteAjax::NONCE_ACTION),
            'cities'      => $this->get_cities(),
        ));
        wp_localize_script(self::APP_HANDLE, 'sscSettings', array(
            'capsule' => (int) Settings::get('capsule'),
            'adults'  => (int) Settings::get('default_adults'),
        ));
    }

    /**
     * Version the stylesheet by content hash so CSS edits bust browser cache.
     *
     * Filemtime is unreliable here (edits preserve it), so the version
     * derives from the file contents and always changes with the CSS.
     *
     * @return string Cache-busting version.
     */
    private function css_version() {
        $path = SSC_PATH . 'assets/css/ssc-hotel.css';
        if (file_exists($path)) {
            $hash = md5_file($path);
            if (is_string($hash) && $hash !== '') {
                return substr($hash, 0, 12);
            }
            $mtime = filemtime($path);
            if (is_int($mtime)) {
                return (string) $mtime;
            }
        }
        return SSC_VERSION;
    }

    /**
     * Enqueue the plugin stylesheet inside the block editor (and its
     * iframe canvas) so server-rendered previews match the frontend.
     * Frontend output is untouched — same handle, so no duplicates.
     *
     * @return void
     */
    public function enqueue_editor() {
        if (!is_admin()) {
            return;
        }
        wp_enqueue_style('ssc-hotel', SSC_URL . 'assets/css/ssc-hotel.css', array(), $this->css_version());
        wp_add_inline_style('ssc-hotel', Theme::inline_vars());
    }

    /**
     * Get city options for the group booking form.
     *
     * @return array<int,array{slug:string,name:string}> City list.
     */
    private function get_cities() {
        if (!taxonomy_exists('property_city')) {
            return array();
        }
        $terms = get_terms(array(
            'taxonomy'   => 'property_city',
            'hide_empty' => false,
            'number'     => 200,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ));
        if (is_wp_error($terms)) {
            return array();
        }
        $cities = array();
        foreach ($terms as $term) {
            $cities[] = array('slug' => $term->slug, 'name' => $term->name);
        }
        return $cities;
    }

    /**
     * Check whether the current page can contain listings or hotel views.
     *
     * @return bool True when assets are needed.
     */
    private function should_load() {
        return is_singular(array(HotelCPT::POST_TYPE, 'estate_property'))
            || is_archive() || is_search() || is_home() || is_page();
    }

    /**
     * Read the wp-scripts asset manifest for dependencies and version.
     *
     * @return array{dependencies: string[], version: string} Asset data.
     */
    private function get_app_asset() {
        $fallback = array('dependencies' => array(), 'version' => SSC_VERSION);
        $path = SSC_PATH . 'assets/build/index.asset.php';
        if (!file_exists($path)) {
            return $fallback;
        }
        $asset = include $path;
        if (!is_array($asset)) {
            return $fallback;
        }
        return array(
            'dependencies' => isset($asset['dependencies']) ? (array) $asset['dependencies'] : array(),
            'version'      => isset($asset['version']) ? (string) $asset['version'] : SSC_VERSION,
        );
    }
}
