<?php
/**
 * REST API Endpoints
 *
 * @package LionTrust_Locations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LT_Rest_API {

    /**
     * API namespace
     *
     * @var string
     */
    private $namespace = 'lt/v1';

    /**
     * Register REST routes
     */
    public function register_routes() {
        // Search locations
        register_rest_route( $this->namespace, '/locations/search', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'search_locations' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                's' => array(
                    'description'       => 'Search term',
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'page' => array(
                    'description'       => 'Page number',
                    'type'              => 'integer',
                    'default'           => 1,
                    'sanitize_callback' => 'absint',
                ),
                'per_page' => array(
                    'description'       => 'Items per page',
                    'type'              => 'integer',
                    'default'           => 12,
                    'sanitize_callback' => 'absint',
                ),
                'region' => array(
                    'description'       => 'Filter by region slug',
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'parents_only' => array(
                    'description'       => 'Only return parent locations',
                    'type'              => 'boolean',
                    'default'           => true,
                ),
            ),
        ) );

        // Get nearby locations
        register_rest_route( $this->namespace, '/locations/(?P<id>\d+)/nearby', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_nearby' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'id' => array(
                    'description'       => 'Location ID',
                    'type'              => 'integer',
                    'required'          => true,
                    'sanitize_callback' => 'absint',
                ),
                'limit' => array(
                    'description'       => 'Number of nearby locations',
                    'type'              => 'integer',
                    'default'           => 12,
                    'sanitize_callback' => 'absint',
                ),
            ),
        ) );

        // Get children of a location
        register_rest_route( $this->namespace, '/locations/(?P<id>\d+)/children', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_children' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'id' => array(
                    'description'       => 'Parent Location ID',
                    'type'              => 'integer',
                    'required'          => true,
                    'sanitize_callback' => 'absint',
                ),
            ),
        ) );
    }

    /**
     * Search locations
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function search_locations( $request ) {
        $search       = $request->get_param( 's' );
        $page         = $request->get_param( 'page' );
        $per_page     = min( $request->get_param( 'per_page' ), 50 ); // Cap at 50
        $region       = $request->get_param( 'region' );
        $parents_only = $request->get_param( 'parents_only' );

        $args = array(
            'post_type'      => 'lt_location',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'orderby'        => 'title',
            'order'          => 'ASC',
        );

        // Search term
        if ( ! empty( $search ) ) {
            $args['s'] = $search;
        }

        // Parents only
        if ( $parents_only ) {
            $args['post_parent'] = 0;
        }

        // Region filter
        if ( ! empty( $region ) ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'lt_region',
                    'field'    => 'slug',
                    'terms'    => $region,
                ),
            );
        }

        $query = new WP_Query( $args );
        $locations = array();

        foreach ( $query->posts as $post ) {
            $locations[] = $this->format_location( $post );
        }

        return rest_ensure_response( array(
            'locations'   => $locations,
            'total'       => $query->found_posts,
            'total_pages' => $query->max_num_pages,
            'page'        => $page,
            'per_page'    => $per_page,
        ) );
    }

    /**
     * Get nearby locations
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_nearby( $request ) {
        $post_id = $request->get_param( 'id' );
        $limit   = $request->get_param( 'limit' );

        $post = get_post( $post_id );

        if ( ! $post || $post->post_type !== 'lt_location' ) {
            return new WP_Error( 'not_found', __( 'Location not found', 'liontrust-locations' ), array( 'status' => 404 ) );
        }

        $nearby = new LT_Nearby();
        $nearby_locations = $nearby->get_nearby_locations( $post_id, $limit );

        $formatted = array();
        foreach ( $nearby_locations as $location ) {
            $formatted[] = $this->format_location( $location, true );
        }

        return rest_ensure_response( array(
            'locations' => $formatted,
        ) );
    }

    /**
     * Get children of a location
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public function get_children( $request ) {
        $parent_id = $request->get_param( 'id' );

        $parent = get_post( $parent_id );

        if ( ! $parent || $parent->post_type !== 'lt_location' ) {
            return new WP_Error( 'not_found', __( 'Location not found', 'liontrust-locations' ), array( 'status' => 404 ) );
        }

        $children = get_posts( array(
            'post_type'      => 'lt_location',
            'post_status'    => 'publish',
            'post_parent'    => $parent_id,
            'posts_per_page' => -1,
            'orderby'        => 'menu_order title',
            'order'          => 'ASC',
        ) );

        $formatted = array();
        foreach ( $children as $child ) {
            $formatted[] = $this->format_location( $child, true );
        }

        return rest_ensure_response( array(
            'parent'   => $this->format_location( $parent, true ),
            'children' => $formatted,
        ) );
    }

    /**
     * Format location for API response
     *
     * @param WP_Post $post       Post object.
     * @param bool    $simplified Whether to return simplified data.
     * @return array
     */
    private function format_location( $post, $simplified = false ) {
        $data = array(
            'id'    => $post->ID,
            'title' => $post->post_title,
            'slug'  => $post->post_name,
            'url'   => get_permalink( $post->ID ),
        );

        if ( $simplified ) {
            return $data;
        }

        // Full data - use helper function to strip page builder shortcodes
        $data['short_description'] = lt_get_short_description( $post );
        $data['excerpt']           = $post->post_excerpt;
        $data['parent_id']         = $post->post_parent;

        // Featured image
        $thumbnail_id = get_post_thumbnail_id( $post->ID );
        if ( $thumbnail_id ) {
            $data['image'] = array(
                'id'       => $thumbnail_id,
                'url'      => get_the_post_thumbnail_url( $post->ID, 'full' ),
                'thumb'    => get_the_post_thumbnail_url( $post->ID, 'large' ),
                'alt'      => get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ),
            );
        } else {
            $data['image'] = null;
        }

        // Property type (for children)
        $property_types = get_the_terms( $post->ID, 'lt_property_type' );
        if ( $property_types && ! is_wp_error( $property_types ) ) {
            $data['property_type'] = array(
                'id'   => $property_types[0]->term_id,
                'name' => $property_types[0]->name,
                'slug' => $property_types[0]->slug,
            );
        } else {
            $data['property_type'] = null;
        }

        // Region
        $regions = get_the_terms( $post->ID, 'lt_region' );
        if ( $regions && ! is_wp_error( $regions ) ) {
            $data['region'] = array(
                'id'   => $regions[0]->term_id,
                'name' => $regions[0]->name,
                'slug' => $regions[0]->slug,
            );
        } else {
            $data['region'] = null;
        }

        // Children (for parents)
        if ( $post->post_parent === 0 ) {
            $children = get_posts( array(
                'post_type'      => 'lt_location',
                'post_status'    => 'publish',
                'post_parent'    => $post->ID,
                'posts_per_page' => -1,
                'orderby'        => 'menu_order title',
                'order'          => 'ASC',
            ) );

            $data['children'] = array();
            foreach ( $children as $child ) {
                $child_data = array(
                    'id'    => $child->ID,
                    'title' => $child->post_title,
                    'slug'  => $child->post_name,
                    'url'   => get_permalink( $child->ID ),
                );

                $child_types = get_the_terms( $child->ID, 'lt_property_type' );
                if ( $child_types && ! is_wp_error( $child_types ) ) {
                    $child_data['property_type'] = $child_types[0]->slug;
                }

                $data['children'][] = $child_data;
            }
        }

        return $data;
    }
}
