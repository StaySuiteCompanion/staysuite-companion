<?php
/**
 * Group booking request storage and admin workflow.
 *
 * @package StaySuite\Companion\Booking
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion\Booking;

use WP_Post;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Stores group quote requests and tracks their status.
 */
class RequestCPT {

    /**
     * Post type slug.
     *
     * @var string
     */
    const POST_TYPE = 'ssc_group_request';

    /**
     * Request statuses.
     *
     * @var array<string,string>
     */
    const STATUSES = array(
        'pending'   => 'Pending',
        'quoted'    => 'Quoted',
        'confirmed' => 'Confirmed',
        'cancelled' => 'Cancelled',
    );

    /**
     * Wire up WordPress hooks.
     *
     * @return void
     */
    public function __construct() {
        add_action('init', array(__CLASS__, 'register'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_status'));
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', array($this, 'add_list_columns'));
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', array($this, 'render_list_column'), 10, 2);
    }

    /**
     * Register the request post type (admin UI only).
     *
     * @return void
     */
    public static function register() {
        register_post_type(self::POST_TYPE, array(
            'labels' => array(
                'name'               => esc_html__('Group Requests', 'staysuite-companion'),
                'singular_name'      => esc_html__('Group Request', 'staysuite-companion'),
                'add_new'            => esc_html__('Add New', 'staysuite-companion'),
                'add_new_item'       => esc_html__('Add New Request', 'staysuite-companion'),
                'edit_item'          => esc_html__('Edit Request', 'staysuite-companion'),
                'search_items'       => esc_html__('Search Requests', 'staysuite-companion'),
                'not_found'          => esc_html__('No requests found', 'staysuite-companion'),
                'all_items'          => esc_html__('All Requests', 'staysuite-companion'),
            ),
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => 'ssc-staysuite',
            'supports'     => array('title'),
            'show_in_rest' => false,
            'capabilities' => array(
                'create_posts' => 'edit_posts',
            ),
            'map_meta_cap' => true,
        ));
    }

    /**
     * Get a request's status.
     *
     * @param int $request_id Request post ID.
     * @return string Status slug, defaults to pending.
     */
    public static function get_status($request_id) {
        $status = get_post_meta(intval($request_id), '_ssc_status', true);
        return isset(self::STATUSES[$status]) ? $status : 'pending';
    }

    /**
     * Register the details / status meta box.
     *
     * @return void
     */
    public function add_meta_box() {
        add_meta_box(
            'ssc_request_details',
            esc_html__('Request Details', 'staysuite-companion'),
            array($this, 'render_meta_box'),
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    /**
     * Render request details and status selector.
     *
     * @param WP_Post $post Current request post.
     * @return void
     */
    public function render_meta_box($post) {
        wp_nonce_field('ssc_request_status', 'ssc_request_status_nonce');
        $fields = array(
            'city'         => __('City', 'staysuite-companion'),
            'check_in'     => __('Check in', 'staysuite-companion'),
            'check_out'    => __('Check out', 'staysuite-companion'),
            'rooms'        => __('Rooms needed', 'staysuite-companion'),
            'guests'       => __('Guests', 'staysuite-companion'),
            'male'         => __('Male', 'staysuite-companion'),
            'female'       => __('Female', 'staysuite-companion'),
            'budget_min'   => __('Budget min', 'staysuite-companion'),
            'budget_max'   => __('Budget max', 'staysuite-companion'),
            'name'         => __('Contact name', 'staysuite-companion'),
            'email'        => __('Contact email', 'staysuite-companion'),
            'phone'        => __('Contact phone', 'staysuite-companion'),
            'requirements' => __('Extra requirements', 'staysuite-companion'),
        );
        print '<table class="form-table">';
        foreach ($fields as $key => $label) {
            $value = get_post_meta($post->ID, '_ssc_' . $key, true);
            print '<tr><th>' . esc_html($label) . '</th><td>' . esc_html($value) . '</td></tr>';
        }
        $matched = (array) get_post_meta($post->ID, '_ssc_matched', true);
        $links = array();
        foreach ($matched as $room_id) {
            $room_id = intval($room_id);
            if (get_post_status($room_id)) {
                $links[] = '<a href="' . esc_url(get_edit_post_link($room_id)) . '">' . esc_html(get_the_title($room_id)) . '</a>';
            }
        }
        print '<tr><th>' . esc_html__('Suggested properties', 'staysuite-companion') . '</th><td>'
            . (!empty($links) ? implode('<br>', $links) : esc_html__('None', 'staysuite-companion')) . '</td></tr>';
        print '</table>';
        print '<p><label for="ssc_request_status"><strong>' . esc_html__('Status', 'staysuite-companion') . '</strong></label> ';
        print '<select id="ssc_request_status" name="ssc_request_status">';
        foreach (self::STATUSES as $slug => $label) {
            print '<option value="' . esc_attr($slug) . '" ' . selected(self::get_status($post->ID), $slug, false) . '>'
                . esc_html($label) . '</option>';
        }
        print '</select></p>';
    }

    /**
     * Save the request status.
     *
     * @param int $post_id Post being saved.
     * @return void
     */
    public function save_status($post_id) {
        if (!isset($_POST['ssc_request_status_nonce']) || !wp_verify_nonce(sanitize_key($_POST['ssc_request_status_nonce']), 'ssc_request_status')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        if (get_post_type($post_id) !== self::POST_TYPE) {
            return;
        }
        $status = isset($_POST['ssc_request_status']) ? sanitize_key($_POST['ssc_request_status']) : 'pending';
        update_post_meta($post_id, '_ssc_status', isset(self::STATUSES[$status]) ? $status : 'pending');
    }

    /**
     * Add admin list columns.
     *
     * @param array<string,string> $columns List table columns.
     * @return array<string,string> Columns with request fields added.
     */
    public function add_list_columns($columns) {
        $columns['ssc_city'] = esc_html__('City', 'staysuite-companion');
        $columns['ssc_dates'] = esc_html__('Dates', 'staysuite-companion');
        $columns['ssc_party'] = esc_html__('Party', 'staysuite-companion');
        $columns['ssc_status'] = esc_html__('Status', 'staysuite-companion');
        return $columns;
    }

    /**
     * Render admin list column cells.
     *
     * @param string $column  Column slug.
     * @param int    $post_id Request post ID.
     * @return void
     */
    public function render_list_column($column, $post_id) {
        switch ($column) {
            case 'ssc_city':
                echo esc_html(get_post_meta($post_id, '_ssc_city', true));
                break;
            case 'ssc_dates':
                echo esc_html(trim(get_post_meta($post_id, '_ssc_check_in', true) . ' → ' . get_post_meta($post_id, '_ssc_check_out', true), ' →'));
                break;
            case 'ssc_party':
                printf(
                    /* translators: 1: rooms, 2: guests */
                    esc_html__('%1$s rooms · %2$s guests', 'staysuite-companion'),
                    esc_html(get_post_meta($post_id, '_ssc_rooms', true)),
                    esc_html(get_post_meta($post_id, '_ssc_guests', true))
                );
                break;
            case 'ssc_status':
                echo esc_html(self::STATUSES[self::get_status($post_id)]);
                break;
        }
    }
}
