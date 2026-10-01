<?php
/**
 * REST preview endpoint for block editor previews.
 *
 * Replaces @wordpress/server-side-render with a first-party preview that
 * POSTs attributes and returns Renderer HTML. Keeps all iframe/ref
 * machinery out of the editor canvas.
 *
 * @package StaySuite\Companion\Blocks
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion\Blocks;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Serves rendered block HTML to the editor.
 */
class PreviewEndpoint {

    /**
     * REST namespace.
     *
     * @var string
     */
    const NAMESPACE = 'ssc/v1';

    /**
     * Preview route.
     *
     * @var string
     */
    const ROUTE = '/preview';

    /**
     * Wire up WordPress hooks.
     *
     * @return void
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register the preview route (editors only).
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(self::NAMESPACE, self::ROUTE, array(
            'methods'             => 'POST',
            'callback'            => array($this, 'render_preview'),
            'permission_callback' => array($this, 'can_preview'),
        ));
    }

    /**
     * Check preview capability.
     *
     * @return bool True for users who can edit posts.
     */
    public function can_preview() {
        return current_user_can('edit_posts');
    }

    /**
     * Render block HTML for the given attributes.
     *
     * @param WP_REST_Request $request REST request.
     * @return WP_REST_Response|WP_Error Response with html or error.
     */
    public function render_preview($request) {
        // NOTE: no sanitize_key() here — it strips the "/" all block names
        // contain. Strict allowlist comparison instead.
        $block = (string) $request->get_param('block');
        $attributes = $request->get_param('attributes');
        if (!is_array($attributes)) {
            $attributes = array();
        }
        switch ($block) {
            case 'ssc/term-tablets':
                $html = Renderer::render_term_tablets($this->coerce_tablets($attributes));
                break;
            case 'ssc/listing-carousel':
                $html = Renderer::render_listing_carousel($this->coerce_carousel($attributes));
                break;
            case 'ssc/hero-search':
                $html = Renderer::render_hero($this->coerce_hero($attributes));
                break;
            case 'ssc/group-booking':
                $html = $this->render_booking($attributes);
                break;
            case 'ssc/payment-strip':
                $html = Renderer::render_payment_strip(array(
                    'title'    => isset($attributes['title']) ? sanitize_text_field($attributes['title']) : '',
                    'image_id' => isset($attributes['image_id']) ? intval($attributes['image_id']) : 0,
                ));
                break;
            default:
                return new WP_Error('ssc_unknown_block', esc_html__('Unknown block.', 'staysuite-companion'), array('status' => 400));
        }
        // Marker class lets preview-only CSS (e.g. hiding the JS-driven
        // guest dropdown) apply without touching the frontend.
        return new WP_REST_Response(array('html' => '<div class="ssc-preview">' . $html . '</div>'), 200);
    }

    /**
     * Coerce tablets attributes to safe types.
     *
     * @param array<string,mixed> $attributes Raw attributes.
     * @return array<string,mixed> Coerced attributes.
     */
    private function coerce_tablets($attributes) {
        return array(
            'taxonomy'   => isset($attributes['taxonomy']) ? sanitize_key($attributes['taxonomy']) : 'property_city',
            'number'     => isset($attributes['number']) ? intval($attributes['number']) : 6,
            'hide_empty' => !empty($attributes['hide_empty']),
        );
    }

    /**
     * Coerce carousel attributes to safe types.
     *
     * @param array<string,mixed> $attributes Raw attributes.
     * @return array<string,mixed> Coerced attributes.
     */
    private function coerce_carousel($attributes) {
        return array(
            'title'         => isset($attributes['title']) ? sanitize_text_field($attributes['title']) : '',
            'source'        => isset($attributes['source']) ? sanitize_key($attributes['source']) : 'rooms',
            'taxonomy'      => isset($attributes['taxonomy']) ? sanitize_key($attributes['taxonomy']) : '',
            'term'          => isset($attributes['term']) ? sanitize_title($attributes['term']) : '',
            'city'          => isset($attributes['city']) ? sanitize_title($attributes['city']) : '',
            'count'         => isset($attributes['count']) ? intval($attributes['count']) : 8,
            'featured_only' => !empty($attributes['featured_only']),
            'include_ids'   => isset($attributes['include_ids']) ? sanitize_text_field($attributes['include_ids']) : '',
            'order'         => isset($attributes['order']) ? sanitize_key($attributes['order']) : 'featured',
        );
    }

    /**
     * Coerce hero attributes to safe types.
     *
     * @param array<string,mixed> $attributes Raw attributes.
     * @return array<string,mixed> Coerced attributes.
     */
    private function coerce_hero($attributes) {
        return array(
            'title'       => isset($attributes['title']) ? sanitize_text_field($attributes['title']) : '',
            'subtitle'    => isset($attributes['subtitle']) ? sanitize_text_field($attributes['subtitle']) : '',
            'image_id'    => isset($attributes['image_id']) ? intval($attributes['image_id']) : 0,
            'show_search' => !isset($attributes['show_search']) || !empty($attributes['show_search']),
            'search_mode' => isset($attributes['search_mode']) ? sanitize_key($attributes['search_mode']) : 'theme',
        );
    }
    /**
     * Render the booking mount node preview.
     *
     * @param array<string,mixed> $attributes Raw attributes.
     * @return string Mount node HTML.
     */
    private function render_booking($attributes) {
        $title = isset($attributes['title']) ? sanitize_text_field($attributes['title']) : '';
        $html = '<div class="ssc-booking" data-ssc-group-booking';
        if ($title !== '') {
            $html .= ' data-title="' . esc_attr($title) . '"';
        }
        $html .= '><p>' . esc_html__('Group booking form renders on the frontend.', 'staysuite-companion') . '</p></div>';
        return $html;
    }
}
