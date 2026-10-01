<?php
/**
 * Template routing for plugin-owned views.
 *
 * @package StaySuite\Companion\Frontend
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion\Frontend;

use StaySuite\Companion\Hotel\HotelCPT;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Serves the hotel single template from the plugin.
 */
class TemplateLoader {

    /**
     * Wire up WordPress hooks.
     *
     * @return void
     */
    public function __construct() {
        add_filter( 'template_include', array( $this, 'load_hotel_template' ), 20 );
    }

    /**
     * Route hotel single pages to the plugin template.
     *
     * @param string $template Template path resolved by WordPress.
     * @return string Plugin template for hotels, otherwise untouched.
     */
    public function load_hotel_template( $template ) {
        if ( is_singular( HotelCPT::POST_TYPE ) ) {
            $plugin_template = SSC_PATH . 'templates/single-ssc_hotel.php';
            if ( file_exists( $plugin_template ) ) {
                /**
                 * Filter the hotel single template path (Pro: overrides).
                 *
                 * @param string $plugin_template Plugin template file.
                 */
                return apply_filters( 'ssc_single_hotel_template', $plugin_template );
            }
        }
        return $template;
    }
}
