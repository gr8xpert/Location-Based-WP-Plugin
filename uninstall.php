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

// Option to preserve data on uninstall (can be set via filter before uninstall)
$preserve_data = get_option( 'lt_locations_preserve_data', false );

if ( $preserve_data ) {
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
delete_option( 'lt_locations_preserve_data' );

// Clear any transients
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_lt_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_lt_%'" );

// Flush rewrite rules
flush_rewrite_rules();
