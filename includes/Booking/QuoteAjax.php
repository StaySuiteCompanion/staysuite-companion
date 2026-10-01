<?php
/**
 * Group quote matching: query, availability, persist, notify.
 *
 * @package StaySuite\Companion\Booking
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion\Booking;

use StaySuite\Companion\Hotel\Repository;
use WP_Query;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles group quote submissions over AJAX.
 */
class QuoteAjax {

    /**
     * Nonce action for quote submissions.
     *
     * @var string
     */
    const NONCE_ACTION = 'ssc_group_quote';

    /**
     * Maximum matches returned to the browser.
     *
     * @var int
     */
    const MAX_MATCHES = 12;

    /**
     * Rooms scanned per request before availability filtering.
     *
     * @var int
     */
    const POOL_SIZE = 60;

    /**
     * Wire up WordPress hooks.
     *
     * @return void
     */
    public function __construct() {
        add_action('wp_ajax_ssc_group_quote', array($this, 'handle'));
        add_action('wp_ajax_nopriv_ssc_group_quote', array($this, 'handle'));
    }

    /**
     * Validate input, find matching rooms, persist the request, notify.
     *
     * @return void
     */
    public function handle() {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        $input = $this->sanitize_input($_POST);
        $error = $this->validate($input);
        if ($error !== '') {
            wp_send_json_error(array('message' => $error));
        }
        $matches = $this->find_matches($input);
        $request_id = $this->persist($input, $matches);
        $this->notify($request_id, $input, $matches);
        /**
         * Fires after a group request is stored and mailed.
         *
         * Pro entry point: deposit/invoice handoff, CRM sync, reminders.
         *
         * @param int                           $request_id Request post ID.
         * @param array<string,mixed>           $input      Sanitized input.
         * @param array<int,array<string,mixed>> $matches    Match list.
         */
        do_action('ssc_group_request_saved', $request_id, $input, $matches);
        wp_send_json_success(array(
            'request_id' => $request_id,
            'total'      => count($matches),
            'matches'    => $matches,
        ));
    }

    /**
     * Sanitize raw submission data.
     *
     * @param array<string,mixed> $raw Raw POST data.
     * @return array<string,mixed> Sanitized input.
     */
    private function sanitize_input($raw) {
        $budget_min = isset($raw['budget_min']) ? floatval($raw['budget_min']) : 0;
        $budget_max = isset($raw['budget_max']) ? floatval($raw['budget_max']) : 0;
        if ($budget_min > 0 && $budget_max > 0 && $budget_min > $budget_max) {
            $tmp = $budget_min;
            $budget_min = $budget_max;
            $budget_max = $tmp;
        }
        $input = array(
            'city'         => isset($raw['city']) ? sanitize_title($raw['city']) : '',
            'location_text' => isset($raw['location_text']) ? sanitize_text_field($raw['location_text']) : '',
            'check_in'     => isset($raw['check_in']) ? sanitize_text_field($raw['check_in']) : '',
            'check_out'    => isset($raw['check_out']) ? sanitize_text_field($raw['check_out']) : '',
            'rooms'        => isset($raw['rooms']) ? max(1, intval($raw['rooms'])) : 1,
            'guests'       => isset($raw['guests']) ? max(1, intval($raw['guests'])) : 2,
            'male'         => isset($raw['male']) ? max(0, intval($raw['male'])) : 0,
            'female'       => isset($raw['female']) ? max(0, intval($raw['female'])) : 0,
            'budget_min'   => max(0, $budget_min),
            'budget_max'   => max(0, $budget_max),
            'name'         => isset($raw['name']) ? sanitize_text_field($raw['name']) : '',
            'email'        => isset($raw['email']) ? sanitize_email($raw['email']) : '',
            'phone'        => isset($raw['phone']) ? sanitize_text_field($raw['phone']) : '',
            'requirements' => isset($raw['requirements']) ? sanitize_textarea_field($raw['requirements']) : '',
        );
        /**
         * Filter the sanitized quote payload (Pro: add-ons, pricing options).
         *
         * @param array<string,mixed> $input Sanitized input.
         * @param array<string,mixed> $raw   Raw POST data.
         */
        return apply_filters('ssc_quote_payload', $input, $raw);
    }

    /**
     * Validate sanitized input.
     *
     * @param array<string,mixed> $input Sanitized input.
     * @return string Error message or empty string when valid.
     */
    private function validate($input) {
        if ($input['name'] === '') {
            return esc_html__('Please tell us your name.', 'staysuite-companion');
        }
        if (!is_email($input['email'])) {
            return esc_html__('Please enter a valid email address.', 'staysuite-companion');
        }
        if ($input['city'] !== '' && !get_term_by('slug', $input['city'], 'property_city')) {
            return esc_html__('Please choose a valid place.', 'staysuite-companion');
        }
        $dates_set = $input['check_in'] !== '' || $input['check_out'] !== '';
        if ($dates_set && (strtotime($input['check_in']) === false || strtotime($input['check_out']) === false)) {
            return esc_html__('Please enter valid check-in and check-out dates.', 'staysuite-companion');
        }
        return '';
    }

    /**
     * Resolve free location text to a city term slug.
     *
     * @param string $text Location text from the theme search.
     * @return string City slug or empty string.
     */
    private function resolve_city_slug($text) {
        $text = trim($text);
        if ($text === '' || !taxonomy_exists('property_city')) {
            return '';
        }
        $term = get_term_by('slug', sanitize_title($text), 'property_city');
        if ($term instanceof \WP_Term) {
            return $term->slug;
        }
        $terms = get_terms(array(
            'taxonomy'   => 'property_city',
            'search'     => $text,
            'number'     => 1,
            'hide_empty' => false,
            'fields'     => 'slugs',
        ));
        if (!is_wp_error($terms) && !empty($terms)) {
            return $terms[0];
        }
        return '';
    }

    /**
     * Find rooms matching city, capacity and budget, then availability.
     *
     * Note: budget filters the base property_price meta. Multi-currency
     * conversion (theme divides by cookie rate) is not applied in v1.
     *
     * @param array<string,mixed> $input Sanitized input.
     * @return array<int,array<string,mixed>> Match list.
     */
    private function find_matches($input) {
        $city_slug = $input['city'];
        if ($city_slug === '' && $input['location_text'] !== '') {
            $city_slug = $this->resolve_city_slug($input['location_text']);
        }        $args = array(
            'post_type'      => 'estate_property',
            'post_status'    => 'publish',
            'posts_per_page' => self::POOL_SIZE,
            'no_found_rows'  => true,
            'orderby'        => 'meta_value_num date',
            'meta_key'       => 'property_price',
            'order'          => 'ASC',
            'meta_query'     => array(
                array(
                    'key'     => 'guest_no',
                    'value'   => $input['guests'],
                    'type'    => 'NUMERIC',
                    'compare' => '>=',
                ),
            ),
        );
        if ($input['budget_min'] > 0 || $input['budget_max'] > 0) {
            $args['meta_query'][] = array(
                'key'     => 'property_price',
                'value'   => array($input['budget_min'], $input['budget_max'] > 0 ? $input['budget_max'] : 999999999),
                'type'    => 'NUMERIC',
                'compare' => 'BETWEEN',
            );
        }
        if ($input['city'] !== '') {
            $args['tax_query'] = array(
                array('taxonomy' => 'property_city', 'field' => 'slug', 'terms' => array($input['city'])),
            );
        } elseif ($city_slug !== '') {
            $args['tax_query'] = array(
                array('taxonomy' => 'property_city', 'field' => 'slug', 'terms' => array($city_slug)),
            );
        }
        $pool = new WP_Query($args);
        $matches = array();
        $check_dates = $input['check_in'] !== '' && $input['check_out'] !== '';
        foreach ($pool->posts as $room_id) {
            if (count($matches) >= self::MAX_MATCHES) {
                break;
            }
            if ($check_dates && function_exists('wpestate_check_booking_valability')) {
                if (!wpestate_check_booking_valability($input['check_in'], $input['check_out'], $room_id)) {
                    continue;
                }
            }
            $matches[] = $this->match_data(intval($room_id));
        }
        return $matches;
    }

    /**
     * Build one match entry for the browser.
     *
     * @param int $room_id Room post ID.
     * @return array<string,mixed> Match data.
     */
    private function match_data($room_id) {
        $price = floatval(get_post_meta($room_id, 'property_price', true));
        $thumb = get_the_post_thumbnail_url($room_id, 'medium');
        return array(
            'id'     => $room_id,
            'title'  => get_the_title($room_id),
            'url'    => get_permalink($room_id),
            'image'  => $thumb !== false ? $thumb : '',
            'price'  => $price > 0 ? Repository::format_price($price) : '',
            'guests' => intval(get_post_meta($room_id, 'guest_no', true)),
        );
    }

    /**
     * Persist the request as a ssc_group_request post.
     *
     * @param array<string,mixed>         $input   Sanitized input.
     * @param array<int,array<string,mixed>> $matches Match list.
     * @return int Request post ID.
     */
    private function persist($input, $matches) {
        $city_name = $input['city'] !== '' ? $input['city'] : ($input['location_text'] !== '' ? $input['location_text'] : 'anywhere');
        $request_id = wp_insert_post(array(
            'post_title'  => sprintf('Group request — %s — %s', $input['name'], $city_name),
            'post_type'   => RequestCPT::POST_TYPE,
            'post_status' => 'publish',
        ));
        if ($request_id <= 0) {
            return 0;
        }
        foreach (array('city', 'location_text', 'check_in', 'check_out', 'rooms', 'guests', 'male', 'female', 'budget_min', 'budget_max', 'name', 'email', 'phone', 'requirements') as $key) {
            update_post_meta($request_id, '_ssc_' . $key, $input[$key]);
        }
        update_post_meta($request_id, '_ssc_status', 'pending');
        update_post_meta($request_id, '_ssc_matched', wp_list_pluck($matches, 'id'));
        return intval($request_id);
    }

    /**
     * Email the admin digest and the requester confirmation.
     *
     * @param int                           $request_id Request post ID.
     * @param array<string,mixed>           $input      Sanitized input.
     * @param array<int,array<string,mixed>> $matches    Match list.
     * @return void
     */
    private function notify($request_id, $input, $matches) {
        if ($request_id <= 0) {
            return;
        }
        $lines = array(
            sprintf('Name: %s', $input['name']),
            sprintf('Email: %s', $input['email']),
            sprintf('Phone: %s', $input['phone']),
            sprintf('City: %s', $input['city']),
            sprintf('Dates: %s → %s', $input['check_in'], $input['check_out']),
            sprintf('Rooms: %d · Guests: %d (M:%d F:%d)', $input['rooms'], $input['guests'], $input['male'], $input['female']),
            sprintf('Budget: %s – %s', $input['budget_min'], $input['budget_max']),
            '',
            'Requirements:',
            $input['requirements'],
            '',
            sprintf('Suggested properties: %d', count($matches)),
            sprintf('Review: %s', get_edit_post_link($request_id, 'display')),
        );
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        wp_mail(
            get_option('admin_email'),
            sprintf('[%s] New group booking request #%d', get_bloginfo('name'), $request_id),
            implode("\n", $lines),
            $headers
        );
        wp_mail(
            $input['email'],
            sprintf(__('Your group booking request (#%d) is received', 'staysuite-companion'), $request_id),
            implode("\n", array(
                sprintf(__('Hi %s,', 'staysuite-companion'), $input['name']),
                '',
                sprintf(
                    __('We found %d properties in your budget and our team will contact you shortly with a quote.', 'staysuite-companion'),
                    count($matches)
                ),
            )),
            $headers
        );
    }
}
