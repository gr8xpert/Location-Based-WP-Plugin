<?php
/**
 * Uninstall Lion Trust Locations
 *
 * @package LionTrust_Locations
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Preserve data by default - only delete if explicitly set to false
// To delete all data on uninstall, set: update_option( 'lt_locations_delete_data', true );
$delete_data = get_option( 'lt_locations_delete_data', false );

if ( ! $delete_data ) {
    return;
}

global $wpdb;

// Delete all lt_location posts and their meta
$posts = get_posts( array(
    'post_type'      => 'lt_location',
    'post_status'    => 'any',
    'posts_per_page' => -1,
    'fields'         => 'ids',
) );

foreach ( $posts as $post_id ) {
    wp_delete_post( $post_id, true );
}

// Delete taxonomy terms
$taxonomies = array( 'lt_property_type', 'lt_region' );

foreach ( $taxonomies as $taxonomy ) {
    $terms = get_terms( array(
        'taxonomy'   => $taxonomy,
        'hide_empty' => false,
        'fields'     => 'ids',
    ) );

    if ( ! is_wp_error( $terms ) ) {
        foreach ( $terms as $term_id ) {
            wp_delete_term( $term_id, $taxonomy );
        }
    }
}

// Delete plugin options
delete_option( 'lt_locations_version' );
delete_option( 'lt_locations_delete_data' );

// Clear any transients (using prepared statements)
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_lt_%' ) );
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_lt_%' ) );

// Flush rewrite rules
flush_rewrite_rules();
