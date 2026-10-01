<?php
/**
 * Block and shortcode registration.
 *
 * Blocks render server-side through Renderer; shortcodes call the same
 * methods so Elementor and Gutenberg always produce identical output.
 *
 * @package StaySuite\Companion\Blocks
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion\Blocks;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registers dynamic blocks and shortcode wrappers.
 */
class Registry {

    /**
     * Block names and their attribute schemas.
     *
     * @var array<string,array<string,array<string,mixed>>>
     */
    const BLOCKS = array(
        'ssc/term-tablets' => array(),
        'ssc/listing-carousel' => array(),
        'ssc/hero-search' => array(),
    );

    /**
     * Wire up WordPress hooks.
     *
     * @return void
     */
    public function __construct() {
        add_action('init', array($this, 'register_editor_assets'));
        add_action('init', array($this, 'register_blocks'));
        add_shortcode('ssc_term_tablets', array($this, 'shortcode_tablets'));
        add_shortcode('ssc_listing_carousel', array($this, 'shortcode_carousel'));
        add_shortcode('ssc_hero', array($this, 'shortcode_hero'));
        add_shortcode('ssc_payment_strip', array($this, 'shortcode_payments'));
    }

    /**
     * Register the block editor bundle.
     *
     * @return void
     */
    public function register_editor_assets() {
        $asset = $this->get_editor_asset();
        wp_register_script(
            'ssc-editor',
            SSC_URL . 'assets/build/editor.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );
    }

    /**
     * Read the wp-scripts asset manifest for the editor bundle.
     *
     * @return array{dependencies: string[], version: string} Asset data.
     */
    private function get_editor_asset() {
        $fallback = array('dependencies' => array(), 'version' => SSC_VERSION);
        $path = SSC_PATH . 'assets/build/editor.asset.php';
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

    /**
     * Register dynamic blocks with server-side rendering.
     *
     * @return void
     */
    public function register_blocks() {
        if (!function_exists('register_block_type')) {
            return;
        }
        register_block_type('ssc/term-tablets', array(
            'editor_script'   => 'ssc-editor',
            'attributes'      => array(
                'taxonomy'   => array('type' => 'string', 'default' => 'property_city'),
                'number'     => array('type' => 'number', 'default' => 6),
                'hide_empty' => array('type' => 'boolean', 'default' => true),
            ),
            'render_callback' => array(Renderer::class, 'render_term_tablets'),
            'supports'        => array('align' => array('full', 'wide')),
        ));
        register_block_type('ssc/listing-carousel', array(
            'editor_script'   => 'ssc-editor',
            'attributes'      => array(
                'title'         => array('type' => 'string', 'default' => ''),
                'source'        => array('type' => 'string', 'default' => 'rooms'),
                'taxonomy'      => array('type' => 'string', 'default' => ''),
                'term'          => array('type' => 'string', 'default' => ''),
                'city'          => array('type' => 'string', 'default' => ''),
                'count'         => array('type' => 'number', 'default' => 8),
                'featured_only' => array('type' => 'boolean', 'default' => false),
                'include_ids'   => array('type' => 'string', 'default' => ''),
                'order'         => array('type' => 'string', 'default' => 'featured'),
            ),
            'render_callback' => array(Renderer::class, 'render_listing_carousel'),
            'supports'        => array('align' => array('full', 'wide')),
        ));
        register_block_type('ssc/hero-search', array(
            'editor_script'   => 'ssc-editor',
            'attributes'      => array(
                'title'       => array('type' => 'string', 'default' => ''),
                'subtitle'    => array('type' => 'string', 'default' => ''),
                'image_id'    => array('type' => 'number', 'default' => 0),
                'show_search' => array('type' => 'boolean', 'default' => true),
                'search_mode' => array('type' => 'string', 'default' => 'theme'),
            ),
            'supports'        => array('align' => array('full', 'wide')),
            'render_callback' => array(Renderer::class, 'render_hero'),
        ));
        register_block_type('ssc/payment-strip', array(
            'editor_script'   => 'ssc-editor',
            'attributes'      => array(
                'title'    => array('type' => 'string', 'default' => ''),
                'image_id' => array('type' => 'number', 'default' => 0),
            ),
            'render_callback' => array(Renderer::class, 'render_payment_strip'),
        ));
    }

    /**
     * Shortcode: [ssc_term_tablets taxonomy number hide_empty].
     *
     * @param array<string,string>|string $atts Shortcode attributes.
     * @return string Tablets HTML.
     */
    public function shortcode_tablets($atts) {
        $atts = shortcode_atts(
            array('taxonomy' => 'property_city', 'number' => 6, 'hide_empty' => '1'),
            $atts,
            'ssc_term_tablets'
        );
        return Renderer::render_term_tablets(array(
            'taxonomy'   => $atts['taxonomy'],
            'number'     => $atts['number'],
            'hide_empty' => $atts['hide_empty'] === '1',
        ));
    }

    /**
     * Shortcode: [ssc_listing_carousel ...].
     *
     * @param array<string,string>|string $atts Shortcode attributes.
     * @return string Carousel HTML.
     */
    public function shortcode_carousel($atts) {
        $atts = shortcode_atts(
            array(
                'title' => '', 'source' => 'rooms', 'taxonomy' => '', 'term' => '',
                'city' => '', 'count' => 8, 'featured_only' => '0', 'include_ids' => '', 'order' => 'featured',
            ),
            $atts,
            'ssc_listing_carousel'
        );
        return Renderer::render_listing_carousel(array(
            'title'         => $atts['title'],
            'source'        => $atts['source'],
            'taxonomy'      => $atts['taxonomy'],
            'term'          => $atts['term'],
            'city'          => $atts['city'],
            'count'         => $atts['count'],
            'featured_only' => $atts['featured_only'] === '1',
            'include_ids'   => $atts['include_ids'],
            'order'         => $atts['order'],
        ));
    }

    /**
     * Shortcode: [ssc_hero title subtitle image_id search_mode theme|simple|none].
     *
     * @param array<string,string>|string $atts Shortcode attributes.
     * @return string Hero HTML.
     */
    public function shortcode_hero($atts) {
        $atts = shortcode_atts(
            array('title' => '', 'subtitle' => '', 'image_id' => 0, 'search_mode' => 'theme', 'show_search' => '1'),
            $atts,
            'ssc_hero'
        );
        return Renderer::render_hero(array(
            'title'       => $atts['title'],
            'subtitle'    => $atts['subtitle'],
            'image_id'    => $atts['image_id'],
            'search_mode' => $atts['search_mode'],
            'show_search' => $atts['show_search'] === '1',
        ));
    }

    /**
     * Shortcode: [ssc_payment_strip title image_id].
     *
     * @param array<string,string>|string $atts Shortcode attributes.
     * @return string Strip HTML.
     */
    public function shortcode_payments($atts) {
        $atts = shortcode_atts(
            array('title' => 'Pay With', 'image_id' => 0),
            $atts,
            'ssc_payment_strip'
        );
        return Renderer::render_payment_strip(array(
            'title'    => $atts['title'],
            'image_id' => $atts['image_id'],
        ));
    }
}
