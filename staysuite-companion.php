<?php
/**
 * Plugin Name: StaySuite Companion for WP Rentals
 * Description: Hotel grouping, homepage blocks and group booking for WP Rentals. Works alongside the theme — no theme files are modified.
 * Plugin URI: https://jktanmay.com
 * Author: Tanmay Kirtania
 * Author URI: https://jktanmay.com
 * Version: 0.2.1
 * License: GPL-3.0-or-later
 * Text Domain: staysuite-companion
 * Domain Path: /languages
 * Requires PHP: 7.4
 *
 * @package StaySuite\Companion
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion;

use StaySuite\Companion\Admin\AssignPage;
use StaySuite\Companion\Admin\Settings;
use StaySuite\Companion\Frontend\CardBadge;
use StaySuite\Companion\Frontend\RoomSingleLink;
use StaySuite\Companion\Frontend\Scripts;
use StaySuite\Companion\Frontend\TemplateLoader;
use StaySuite\Companion\Hotel\HotelCPT;
use StaySuite\Companion\Hotel\RoomLink;

// Don't call the file directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

define( 'SSC_VERSION', '0.2.1' );
define( 'SSC_FILE', __FILE__ );
define( 'SSC_PATH', plugin_dir_path( __FILE__ ) );
define( 'SSC_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin class.
 *
 * Singleton that owns the class container and wires up
 * includes, instantiation and action hooks.
 */
final class Plugin {

    /**
     * Plugin version.
     *
     * @var string
     */
    public $version = SSC_VERSION;

    /**
     * Minimum PHP version required.
     *
     * @var string
     */
    private $min_php = '7.4';

    /**
     * Holds shared class instances.
     *
     * @var array<string, object>
     */
    private $container = array();

    /**
     * Single instance of this class.
     *
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * Get or create the plugin instance.
     *
     * @return Plugin Single instance of this class.
     */
    public static function init() {
        if ( ! isset( self::$instance ) || ! ( self::$instance instanceof Plugin ) ) {
            self::$instance = new Plugin();
            self::$instance->setup();
        }
        return self::$instance;
    }

    /**
     * Set up the plugin: includes, instances and hooks.
     *
     * @return void
     */
    private function setup() {
        if ( ! $this->is_supported_php() ) {
            add_action( 'admin_notices', array( $this, 'php_version_notice' ) );
            return;
        }

        $this->instantiate();
        $this->init_actions();

        do_action( 'ssc_loaded' );
    }

    /**
     * Magic getter for container instances.
     *
     * @param string $prop Instance key.
     * @return mixed Instance or null when unknown.
     */
    public function __get( $prop ) {
        if ( array_key_exists( $prop, $this->container ) ) {
            return $this->container[ $prop ];
        }
        return null;
    }

    /**
     * Magic isset for container instances.
     *
     * @param string $prop Instance key.
     * @return bool Whether the instance exists.
     */
    public function __isset( $prop ) {
        return isset( $this->container[ $prop ] );
    }

    /**
     * Check whether the server PHP version is supported.
     *
     * @return bool True when supported.
     */
    public function is_supported_php() {
        return version_compare( PHP_VERSION, $this->min_php, '>=' );
    }

    /**
     * Show an admin notice when PHP is too old.
     *
     * @return void
     */
    public function php_version_notice() {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html(
                sprintf(
                    /* translators: %s: minimum PHP version */
                    __( 'StaySuite Companion for WP Rentals requires PHP %s or newer.', 'staysuite-companion' ),
                    $this->min_php
                )
            )
        );
    }

    /**
     * Instantiate domain classes and keep shared ones in the container.
     *
     * @return void
     */
    private function instantiate() {
        $this->container['hotel_cpt']       = new HotelCPT();
        $this->container['room_link']       = new RoomLink();
        $this->container['template_loader'] = new TemplateLoader();
        $this->container['page_template']   = new Frontend\PageTemplate();
        $this->container['room_single']     = new RoomSingleLink();
        $this->container['card_badge']      = new CardBadge();
        $this->container['scripts']         = new Scripts();
        $this->container['page_setup']      = new Frontend\PageSetup();
        $this->container['settings']        = new Admin\Settings();
        $this->container['homepage_setup']  = new Admin\HomepageSetup();
        $this->container['blocks']          = new Blocks\Registry();
        $this->container['block_preview']   = new Blocks\PreviewEndpoint();
        $this->container['patterns']        = new Blocks\Patterns();

        $this->container['group_request']    = new Booking\RequestCPT();
        $this->container['quote_form']       = new Booking\QuoteForm();
        $this->container['quote_ajax']       = new Booking\QuoteAjax();

        if ( is_admin() ) {
            $this->container['assign_page'] = new AssignPage();
            $this->container['term_repair'] = new Admin\TermRepair();
            $this->container['term_image'] = new Admin\TermImage();
        }
    }

    /**
     * Initialize localization, links and admin notices.
     *
     * @return void
     */
    private function init_actions() {
        add_action( 'init', array( $this, 'localization_setup' ) );
        add_action( 'init', array( __NAMESPACE__ . '\Installer', 'maybe_migrate' ), 5 );
        add_filter( 'plugin_action_links_' . plugin_basename( SSC_FILE ), array( $this, 'plugin_action_links' ) );
        add_action( 'admin_notices', array( $this, 'theme_check_notice' ) );
    }

    /**
     * Load plugin translations on init (WP 6.7 compatible).
     *
     * @return void
     */
    public function localization_setup() {
        load_plugin_textdomain(
            'staysuite-companion',
            false,
            dirname( plugin_basename( SSC_FILE ) ) . '/languages/'
        );
    }

    /**
     * Add action links on the plugins list.
     *
     * @param string[] $links Existing action links.
     * @return string[] Links with companion pages added.
     */
    public function plugin_action_links( $links ) {
        $links[] = '<a href="' . esc_url( admin_url( 'edit.php?post_type=' . HotelCPT::POST_TYPE ) ) . '">'
            . esc_html__( 'Hotels', 'staysuite-companion' ) . '</a>';
        return $links;
    }

    /**
     * Warn when the active theme is not WP Rentals.
     *
     * @return void
     */
    public function theme_check_notice() {
        if ( get_template() !== 'wprentals' ) {
            print '<div class="notice notice-warning"><p>'
                . esc_html__( 'StaySuite Companion for WP Rentals is built for the WP Rentals theme. Some features may not work with the active theme.', 'staysuite-companion' )
                . '</p></div>';
        }
    }
}

/**
 * Get the plugin instance.
 *
 * @return Plugin Single instance of this class.
 */
// phpcs:disable Universal.Files.SeparateFunctionsFromOO.Mixed -- Bootstrap file: the singleton accessor belongs beside the class it returns.
function plugin() {
    return Plugin::init();
}
// phpcs:enable Universal.Files.SeparateFunctionsFromOO.Mixed

add_action( 'plugins_loaded', __NAMESPACE__ . '\plugin', 5 );

register_activation_hook( __FILE__, array( __NAMESPACE__ . '\Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( __NAMESPACE__ . '\Installer', 'deactivate' ) );
