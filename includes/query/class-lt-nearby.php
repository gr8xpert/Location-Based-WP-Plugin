<?php
/**
 * Nearby Locations Logic
 *
 * Handles the fallback chain for nearby locations:
 * 1. Manual selection (admin picks)
 * 2. Same region taxonomy
 * 3. Random fallback
 *
 * @package LionTrust_Locations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LT_Nearby {

    /**
     * Get nearby locations for a given location
     *
     * @param int $post_id Location post ID.
     * @param int $limit   Number of locations to return.
     * @return array Array of WP_Post objects.
     */
    public function get_nearby_locations( $post_id, $limit = 12 ) {
        $post = get_post( $post_id );

        if ( ! $post || $post->post_type !== 'lt_location' ) {
            return array();
        }

        // If this is a child, get nearby for the parent
        $parent_id = $post->post_parent !== 0 ? $post->post_parent : $post_id;

        $locations = array();

        // Step 1: Try manual selection
        $manual_nearby = get_post_meta( $parent_id, '_lt_nearby_locations', true );

        if ( ! empty( $manual_nearby ) && is_array( $manual_nearby ) ) {
            $manual_posts = get_posts( array(
                'post_type'      => 'lt_location',
                'post_status'    => 'publish',
                'post__in'       => $manual_nearby,
                'post_parent'    => 0,
                'posts_per_page' => $limit,
                'orderby'        => 'post__in',
            ) );

            $locations = array_merge( $locations, $manual_posts );
        }

        // If we have enough, return
        if ( count( $locations ) >= $limit ) {
            return array_slice( $locations, 0, $limit );
        }

        // Step 2: Same region fallback
        $remaining = $limit - count( $locations );
        $exclude_ids = wp_list_pluck( $locations, 'ID' );
        $exclude_ids[] = $parent_id;

        $regions = get_the_terms( $parent_id, 'lt_region' );

        if ( $regions && ! is_wp_error( $regions ) ) {
            $region_posts = get_posts( array(
                'post_type'      => 'lt_location',
                'post_status'    => 'publish',
                'post_parent'    => 0,
                'posts_per_page' => $remaining,
                'exclude'        => $exclude_ids,
                'orderby'        => 'rand',
                'tax_query'      => array(
                    array(
                        'taxonomy' => 'lt_region',
                        'field'    => 'term_id',
                        'terms'    => wp_list_pluck( $regions, 'term_id' ),
                    ),
                ),
            ) );

            $locations = array_merge( $locations, $region_posts );
            $exclude_ids = array_merge( $exclude_ids, wp_list_pluck( $region_posts, 'ID' ) );
        }

        // If we have enough, return
        if ( count( $locations ) >= $limit ) {
            return array_slice( $locations, 0, $limit );
        }

        // Step 3: Random fallback
        $remaining = $limit - count( $locations );

        $random_posts = get_posts( array(
            'post_type'      => 'lt_location',
            'post_status'    => 'publish',
            'post_parent'    => 0,
            'posts_per_page' => $remaining,
            'exclude'        => $exclude_ids,
            'orderby'        => 'rand',
        ) );

        $locations = array_merge( $locations, $random_posts );

        return array_slice( $locations, 0, $limit );
    }

    /**
     * Get nearby locations grouped for display (4 columns x 3 rows)
     *
     * @param int $post_id Location post ID.
     * @return array Array of WP_Post objects.
     */
    public function get_nearby_grid( $post_id ) {
        return $this->get_nearby_locations( $post_id, 12 );
    }

    /**
     * Calculate distance between two coordinates (Haversine formula)
     *
     * @param float $lat1 Latitude of first point.
     * @param float $lon1 Longitude of first point.
     * @param float $lat2 Latitude of second point.
     * @param float $lon2 Longitude of second point.
     * @return float Distance in kilometers.
     */
    public function calculate_distance( $lat1, $lon1, $lat2, $lon2 ) {
        $earth_radius = 6371; // km

        $lat1_rad = deg2rad( $lat1 );
        $lat2_rad = deg2rad( $lat2 );
        $delta_lat = deg2rad( $lat2 - $lat1 );
        $delta_lon = deg2rad( $lon2 - $lon1 );

        $a = sin( $delta_lat / 2 ) * sin( $delta_lat / 2 ) +
             cos( $lat1_rad ) * cos( $lat2_rad ) *
             sin( $delta_lon / 2 ) * sin( $delta_lon / 2 );

        $c = 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );

        return $earth_radius * $c;
    }

    /**
     * Get locations sorted by distance (if coordinates available)
     *
     * @param int   $post_id Location post ID.
     * @param int   $limit   Number of locations to return.
     * @param float $max_km  Maximum distance in kilometers.
     * @return array Array of locations with distance data.
     */
    public function get_by_distance( $post_id, $limit = 12, $max_km = 100 ) {
        $post = get_post( $post_id );
        $parent_id = $post->post_parent !== 0 ? $post->post_parent : $post_id;

        $lat = get_post_meta( $parent_id, '_lt_latitude', true );
        $lon = get_post_meta( $parent_id, '_lt_longitude', true );

        // If no coordinates, fall back to standard nearby
        if ( empty( $lat ) || empty( $lon ) ) {
            return $this->get_nearby_locations( $post_id, $limit );
        }

        // Get all parent locations with coordinates
        $all_locations = get_posts( array(
            'post_type'      => 'lt_location',
            'post_status'    => 'publish',
            'post_parent'    => 0,
            'posts_per_page' => -1,
            'exclude'        => array( $parent_id ),
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => '_lt_latitude',
                    'compare' => 'EXISTS',
                ),
                array(
                    'key'     => '_lt_longitude',
                    'compare' => 'EXISTS',
                ),
            ),
        ) );

        $locations_with_distance = array();

        foreach ( $all_locations as $location ) {
            $loc_lat = get_post_meta( $location->ID, '_lt_latitude', true );
            $loc_lon = get_post_meta( $location->ID, '_lt_longitude', true );

            if ( empty( $loc_lat ) || empty( $loc_lon ) ) {
                continue;
            }

            $distance = $this->calculate_distance( $lat, $lon, $loc_lat, $loc_lon );

            if ( $distance <= $max_km ) {
                $location->distance = $distance;
                $locations_with_distance[] = $location;
            }
        }

        // Sort by distance
        usort( $locations_with_distance, function( $a, $b ) {
            return $a->distance <=> $b->distance;
        } );

        // Get the requested number
        $result = array_slice( $locations_with_distance, 0, $limit );

        // If not enough, supplement with standard nearby
        if ( count( $result ) < $limit ) {
            $exclude_ids = wp_list_pluck( $result, 'ID' );
            $exclude_ids[] = $parent_id;

            $additional = $this->get_nearby_locations( $post_id, $limit - count( $result ) );
            $additional = array_filter( $additional, function( $loc ) use ( $exclude_ids ) {
                return ! in_array( $loc->ID, $exclude_ids );
            } );

            $result = array_merge( $result, array_values( $additional ) );
        }

        return array_slice( $result, 0, $limit );
    }
}
