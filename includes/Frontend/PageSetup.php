<?php
/**
 * Page-level adjustments for hero pages.
 *
 * Adds a body class when the current singular page uses the hero block,
 * letting the stylesheet suppress the theme map/search header and widen
 * the content without touching theme files.
 *
 * @package StaySuite\Companion\Frontend
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Flags hero pages for CSS treatment.
 */
class PageSetup {

    /**
     * Wire up WordPress hooks.
     *
     * @return void
     */
    public function __construct() {
        add_filter( 'body_class', array( $this, 'add_hero_class' ) );
    }

    /**
     * Add ssc-has-hero on singular pages containing the hero block.
     *
     * @param string[] $classes Body classes.
     * @return string[] Body classes with hero flag added when applicable.
     */
    public function add_hero_class( $classes ) {
        if ( is_singular() && has_block( 'ssc/hero-search' ) ) {
            $classes[] = 'ssc-has-hero';
        }
        return $classes;
    }
}
