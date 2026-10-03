<?php
/**
 * Cleanup on plugin uninstall.
 *
 * Always removes the plugin's own options and transients. Hotels, Group
 * Requests, post meta and the generated homepage page are kept, unless
 * the "Delete all StaySuite data when the plugin is uninstalled" setting
 * (Settings → Advanced) is on.
 *
 * @package StaySuite\Companion
 * @author Tanmay Kirtania <jktanmay@gmail.com>
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Remove plugin data for one site.
 *
 * @return void
 */
function ssc_uninstall_site() {
    global $wpdb;

    $settings = get_option( 'ssc_settings', array() );
    $delete_data = is_array( $settings ) && ! empty( $settings['delete_on_uninstall'] );

    delete_option( 'ssc_settings' );
    delete_option( 'ssc_homepage_page_id' );

    $like = $wpdb->esc_like( '_transient_ssc_' ) . '%';
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            $like,
            $wpdb->esc_like( '_transient_timeout_ssc_' ) . '%'
        )
    );

    if ( ! $delete_data ) {
        return;
    }

    $post_ids = get_posts(
        array(
            'post_type'      => array( 'ssc_hotel', 'ssc_group_request' ),
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        )
    );
    foreach ( $post_ids as $post_id ) {
        wp_delete_post( intval( $post_id ), true );
    }

    $meta_like = $wpdb->esc_like( '_ssc_' ) . '%';
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
            $meta_like
        )
    );
}

if ( is_multisite() ) {
    $site_ids = get_sites(
        array(
            'fields' => 'ids',
            'number' => 0,
        )
    );
    foreach ( $site_ids as $site_id ) {
        switch_to_blog( intval( $site_id ) );
        ssc_uninstall_site();
        restore_current_blog();
    }
} else {
    ssc_uninstall_site();
}
