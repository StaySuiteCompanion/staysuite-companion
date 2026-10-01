<?php
/**
 * Block patterns: one-click homepage layout.
 *
 * Registers a StaySuite pattern category and the full homepage
 * pattern (hero, tablets, carousels, features, payments, group booking).
 * Everything stays editable in the block editor.
 *
 * @package StaySuite\Companion\Blocks
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion\Blocks;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registers pattern category and patterns.
 */
class Patterns {

    /**
     * Wire up WordPress hooks.
     *
     * @return void
     */
    public function __construct() {
        add_action('init', array($this, 'register'));
    }

    /**
     * Register category and homepage pattern.
     *
     * @return void
     */
    public function register() {
        if (!function_exists('register_block_pattern')) {
            return;
        }
        register_block_pattern_category('vs', array(
            'label' => esc_html__('StaySuite', 'staysuite-companion'),
        ));
        register_block_pattern('ssc/homepage', array(
            'title'       => esc_html__('StaySuite Homepage', 'staysuite-companion'),
            'description' => esc_html__('Cover hero with search, destination tablets, listing carousels, why-choose grid, payments and group booking.', 'staysuite-companion'),
            'categories'  => array('vs'),
            'content'     => self::homepage_content(),
        ));
    }

    /**
     * Homepage pattern markup.
     *
     * @return string Block grammar for the full homepage.
     */
    private static function homepage_content() {
        return <<<'HTML'
<!-- wp:ssc/hero-search {"align":"full","search_mode":"theme"} /-->
<!-- wp:ssc/term-tablets {"taxonomy":"property_city","number":6} /-->
<!-- wp:ssc/listing-carousel {"title":"Featured hotels","source":"hotels","featured_only":true,"count":8} /-->
<!-- wp:ssc/listing-carousel {"title":"Popular stays","source":"rooms","count":8} /-->
<!-- wp:group {"className":"ssc-features"} -->
<div class="wp-block-group ssc-features"><!-- wp:heading -->
<h2>Why choose <em>StaySuite</em> to book a holiday home?</h2>
<!-- /wp:heading -->
<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:html -->
<span class="ssc-feature-icon"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="13" width="4" height="7" rx="1"/><rect x="17" y="13" width="4" height="7" rx="1"/><path d="M5 13V9a7 7 0 0 1 14 0v4"/></svg></span>
<!-- /wp:html -->
<!-- wp:heading {"level":3} -->
<h3>24/7 Support hours for customers</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>We offer you 24/7 support to manage your listing or your holiday planning starting now.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:html -->
<span class="ssc-feature-icon"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12V4h8l9 9-8 8z"/><circle cx="8" cy="9" r="1.4"/></svg></span>
<!-- /wp:html -->
<!-- wp:heading {"level":3} -->
<h3>The most affordable rentals platform</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>You will find the most affordable rentals prices for rooms in Bangladesh on our platform.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:html -->
<span class="ssc-feature-icon"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 11v5"/><path d="M12 8h.01"/></svg></span>
<!-- /wp:html -->
<!-- wp:heading {"level":3} -->
<h3>Transparent booking process</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>You will find the most affordable rentals prices for houses in Bangladesh on our platform.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:html -->
<span class="ssc-feature-icon"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 5a3.5 3.5 0 0 1 0 7"/><path d="M17.5 14.5a6.5 6.5 0 0 1 4 5.5"/></svg></span>
<!-- /wp:html -->
<!-- wp:heading {"level":3} -->
<h3>We work directly with the locals</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>You will find listings from local home owners who are ready to welcome you in their homes.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:html -->
<span class="ssc-feature-icon"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6"/><path d="M10 21h4"/><path d="M12 3a6 6 0 0 0-4 10.5c.8.7 1 1.5 1 2.5h6c0-1 .2-1.8 1-2.5A6 6 0 0 0 12 3z"/></svg></span>
<!-- /wp:html -->
<!-- wp:heading {"level":3} -->
<h3>Register for free and add your listing</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Add your listing on our platform and publish it without paying any fee to us while the listing is live.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:html -->
<span class="ssc-feature-icon"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6z"/><path d="M9 12l2 2 4-4"/></svg></span>
<!-- /wp:html -->
<!-- wp:heading {"level":3} -->
<h3>Safe rental process guaranteed</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>StaySuite makes it easy and quick to rent properties in your favorite holiday place.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:html -->
<span class="ssc-feature-icon"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8h16v3a2 2 0 0 0 0 5v3H4v-3a2 2 0 0 0 0-5z"/><path d="M14 8v11"/></svg></span>
<!-- /wp:html -->
<!-- wp:heading {"level":3} -->
<h3>We know the fun activities</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>StaySuite makes it convenient for you to have the fun activities whenever and wherever you want.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column"><!-- wp:html -->
<span class="ssc-feature-icon"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="11" rx="2"/><circle cx="12" cy="12.5" r="2.5"/><path d="M6.5 10h.01M17.5 15h.01"/></svg></span>
<!-- /wp:html -->
<!-- wp:heading {"level":3} -->
<h3>Rent by price per night or price per guest</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Rent places by paying a fee for each night booked, or for each guest who checks-in.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
<!-- wp:ssc/payment-strip /-->
HTML;
    }
}
