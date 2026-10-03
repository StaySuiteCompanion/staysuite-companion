<?php
/**
 * Plugin Name:       StaySuite Companion for WpRentals
 * Plugin URI:        https://jktanmay.com/products/staysuite-companion
 * Description:       Hotel pages, homepage booking blocks and group quote requests for the WpRentals theme. No theme files are modified.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Tanmay Kirtania
 * Author URI:        https://jktanmay.com
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       staysuite-companion
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

define( 'SSC_VERSION', '1.0.0' );
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
    private $min_php = '8.1';

    /**
     * User meta key remembering the theme-notice dismissal.
     *
     * @var string
     */
    const NOTICE_DISMISS_META = 'ssc_theme_notice_dismissed';

    /**
     * Nonce action for notice dismissal.
     *
     * @var string
     */
    const NOTICE_NONCE_ACTION = 'ssc_dismiss_notice';

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
                    __( 'StaySuite Companion for WpRentals requires PHP %s or newer.', 'staysuite-companion' ),
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
        add_filter( 'plugin_action_links_' . plugin_basename( SSC_FILE ), array( $this, 'plugin_action_links' ) );
        add_action( 'admin_notices', array( $this, 'theme_check_notice' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'notice_assets' ) );
        add_action( 'wp_ajax_ssc_dismiss_notice', array( $this, 'dismiss_notice' ) );
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
     * Warn when the active theme is not WpRentals.
     *
     * Administrators only, dismissed per user, and only on the Plugins
     * screen and StaySuite/Hotels screens — never a global nag.
     *
     * @return void
     */
    public function theme_check_notice() {
        if ( get_template() === Installer::REQUIRED_THEME ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        if ( get_user_meta( get_current_user_id(), self::NOTICE_DISMISS_META, true ) ) {
            return;
        }
        if ( ! $this->is_notice_screen() ) {
            return;
        }
        printf(
            '<div class="notice notice-warning is-dismissible" data-ssc-dismissible="theme"><p>%s</p></div>',
            esc_html__( 'StaySuite Companion for WpRentals is built for the WpRentals theme. Some features may not work with the active theme.', 'staysuite-companion' )
        );
    }

    /**
     * Whether the current admin screen may show the theme notice.
     *
     * @return bool True on the Plugins screen and StaySuite/Hotels screens.
     */
    private function is_notice_screen() {
        if ( ! function_exists( 'get_current_screen' ) ) {
            return false;
        }
        $screen = get_current_screen();
        if ( ! $screen instanceof \WP_Screen ) {
            return false;
        }
        $allowed = array(
            'plugins',
            'toplevel_page_' . Admin\Settings::MENU_SLUG,
            'edit-' . Hotel\HotelCPT::POST_TYPE,
            Hotel\HotelCPT::POST_TYPE,
            'edit-' . Booking\RequestCPT::POST_TYPE,
            Booking\RequestCPT::POST_TYPE,
        );
        $assign = isset( $this->container['assign_page'] ) ? $this->container['assign_page'] : null;
        if ( $assign instanceof Admin\AssignPage && '' !== $assign->hook_suffix() ) {
            $allowed[] = $assign->hook_suffix();
        }
        return in_array( $screen->id, $allowed, true );
    }

    /**
     * Load the notice-dismissal script where the notice may appear.
     *
     * @return void
     */
    public function notice_assets() {
        if ( get_template() === Installer::REQUIRED_THEME || ! current_user_can( 'manage_options' ) ) {
            return;
        }
        if ( ! $this->is_notice_screen() ) {
            return;
        }
        wp_enqueue_script( 'ssc-notice', SSC_URL . 'assets/js/ssc-notice.js', array(), SSC_VERSION, true );
        wp_localize_script(
            'ssc-notice', 'sscNotice', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( self::NOTICE_NONCE_ACTION ),
            )
        );
    }

    /**
     * Remember a notice dismissal for the current user.
     *
     * @return void
     */
    public function dismiss_notice() {
        check_ajax_referer( self::NOTICE_NONCE_ACTION, 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array(), 403 );
        }
        $key = isset( $_POST['key'] ) && is_string( $_POST['key'] )
            ? sanitize_key( wp_unslash( $_POST['key'] ) )
            : '';
        if ( 'theme' !== $key ) {
            wp_send_json_error( array(), 400 );
        }
        update_user_meta( get_current_user_id(), self::NOTICE_DISMISS_META, 1 );
        wp_send_json_success();
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
