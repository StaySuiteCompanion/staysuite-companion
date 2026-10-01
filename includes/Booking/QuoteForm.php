<?php
/**
 * Group quote form: shortcode and block output.
 *
 * The form itself is a React app; PHP renders the mount node so the
 * same output works in Gutenberg and Elementor/shortcodes.
 *
 * @package StaySuite\Companion\Booking
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion\Booking;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Renders the group booking mount node.
 */
class QuoteForm {

    /**
     * Wire up WordPress hooks.
     *
     * @return void
     */
    public function __construct() {
        add_shortcode( 'ssc_group_booking', array( $this, 'shortcode' ) );
        add_action( 'init', array( $this, 'register_block' ) );
    }

    /**
     * Register the Gutenberg block (server-side rendered mount node).
     *
     * @return void
     */
    public function register_block() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }
        register_block_type(
            'ssc/group-booking', array(
				'editor_script'   => 'ssc-editor',
				'attributes'      => array(
					'title' => array(
						'type' => 'string',
						'default' => '',
					),
				),
				'render_callback' => array( $this, 'render' ),
            )
        );
    }

    /**
     * Shortcode: [ssc_group_booking title="..."].
     *
     * @param array<string,string>|string $atts Shortcode attributes.
     * @return string Mount node HTML.
     */
    public function shortcode( $atts ) {
        $atts = shortcode_atts( array( 'title' => '' ), $atts, 'ssc_group_booking' );
        return $this->render( array( 'title' => $atts['title'] ) );
    }

    /**
     * Render the React mount node.
     *
     * @param array<string,mixed> $atts Title attribute.
     * @return string Mount node HTML.
     */
    public function render( $atts ) {
        $title = isset( $atts['title'] ) ? sanitize_text_field( $atts['title'] ) : '';
        $html = '<div class="ssc-booking" data-ssc-group-booking';
        if ( $title !== '' ) {
            $html .= ' data-title="' . esc_attr( $title ) . '"';
        }
        $html .= '>';
        $html .= '<noscript><p>' . esc_html__( 'Group booking needs JavaScript enabled.', 'staysuite-companion' ) . '</p></noscript>';
        $html .= '</div>';
        return $html;
    }
}
