<?php
/**
 * Repairs property_area term meta missing the cityparent key.
 *
 * The theme reads $term_meta['cityparent'] without a guard, which emits
 * "array offset on bool" notices for area terms saved before the meta
 * existed. This backfills the key instead of editing theme files.
 *
 * @package StaySuite\Companion\Admin
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

namespace StaySuite\Companion\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ensures area term options always carry cityparent.
 */
class TermRepair {

    /**
     * Sweep interval guard key.
     *
     * @var string
     */
    const SWEEP_TRANSIENT = 'ssc_term_repair_sweep';

    /**
     * Wire up WordPress hooks.
     *
     * @return void
     */
    public function __construct() {
        add_action('admin_init', array($this, 'maybe_sweep'));
        add_action('created_property_area', array($this, 'repair_term'));
        add_action('edited_property_area', array($this, 'repair_term'));
    }

    /**
     * Sweep all area terms at most once a week.
     *
     * @return void
     */
    public function maybe_sweep() {
        if (get_transient(self::SWEEP_TRANSIENT) !== false) {
            return;
        }
        set_transient(self::SWEEP_TRANSIENT, 1, WEEK_IN_SECONDS);
        $terms = get_terms(array('taxonomy' => 'property_area', 'hide_empty' => false, 'fields' => 'ids'));
        if (is_wp_error($terms)) {
            return;
        }
        foreach ($terms as $term_id) {
            $this->repair_term(intval($term_id));
        }
    }

    /**
     * Backfill the cityparent key for one term.
     *
     * @param int $term_id Term ID.
     * @return void
     */
    public function repair_term($term_id) {
        $key = 'taxonomy_' . intval($term_id);
        $meta = get_option($key);
        if (!is_array($meta)) {
            $meta = array();
        }
        if (!array_key_exists('cityparent', $meta)) {
            $meta['cityparent'] = '';
            update_option($key, $meta);
        }
    }
}
