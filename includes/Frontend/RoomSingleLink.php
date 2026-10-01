<?php
/**
 * "Part of Hotel X" box on single room pages.
 *
 * @package StaySuite\Companion\Frontend
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion\Frontend;

use StaySuite\Companion\Hotel\Repository;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Appends the hotel callout to room content.
 */
class RoomSingleLink {

    /**
     * Wire up WordPress hooks.
     *
     * @return void
     */
    public function __construct() {
        add_filter('the_content', array($this, 'append_hotel_box'), 25);
    }

    /**
     * Append the hotel box to single room content.
     *
     * @param string $content Post content.
     * @return string Content with hotel box appended when linked.
     */
    public function append_hotel_box($content) {
        if (!is_singular('estate_property') || !is_main_query() || !in_the_loop()) {
            return $content;
        }
        $hotel_id = Repository::get_room_hotel_id(get_the_ID());
        if ($hotel_id <= 0 || get_post_status($hotel_id) !== 'publish') {
            return $content;
        }
        return $content . $this->render_box($hotel_id);
    }

    /**
     * Build the hotel callout markup.
     *
     * @param int $hotel_id Hotel post ID.
     * @return string Box HTML.
     */
    private function render_box($hotel_id) {
        $room_count = Repository::get_room_count($hotel_id);
        $min_price = Repository::get_min_price($hotel_id);
        $url = get_permalink($hotel_id);

        $box = '<aside class="ssc-hotel-box">';
        $box .= '<div class="ssc-hotel-box-label">' . esc_html__('Part of', 'staysuite-companion') . '</div>';
        $box .= '<a class="ssc-hotel-box-name" href="' . esc_url($url) . '">' . esc_html(get_the_title($hotel_id)) . '</a>';

        $meta = array();
        if ($room_count > 0) {
            /* translators: %d: number of rooms */
            $meta[] = sprintf(esc_html(_n('%d room', '%d rooms', $room_count, 'staysuite-companion')), intval($room_count));
        }
        if ($min_price > 0) {
            /* translators: %s: starting price */
            $meta[] = sprintf(esc_html__('from %s / night', 'staysuite-companion'), Repository::format_price($min_price));
        }
        if (!empty($meta)) {
            $box .= '<div class="ssc-hotel-box-meta">' . esc_html(implode(' · ', $meta)) . '</div>';
        }
        $box .= '<a class="ssc-hotel-box-cta" href="' . esc_url($url) . '">' . esc_html__('View hotel & all rooms', 'staysuite-companion') . '</a>';
        $box .= '</aside>';

        return $box;
    }
}
