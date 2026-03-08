<?php
/**
 * Location Custom Post Type
 *
 * @package LionTrust_Locations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LT_Location_CPT {

    /**
     * Initialize CPT and sitemap support
     */
    public function init() {
        $this->register();
        $this->register_sitemap_support();
    }

    /**
     * Register sitemap support for popular plugins
     */
    private function register_sitemap_support() {
        // WordPress Core Sitemaps (5.5+)
        add_filter( 'wp_sitemaps_post_types', array( $this, 'add_to_core_sitemap' ) );

        // Yoast SEO
        add_filter( 'wpseo_sitemap_supported_post_types', array( $this, 'add_to_yoast_sitemap' ) );

        // Rank Math - Multiple hooks for full support
        add_filter( 'rank_math/sitemap/post_types', array( $this, 'add_to_rankmath_sitemap' ) );
        add_filter( 'rank_math/sitemap/exclude_post_type', array( $this, 'rankmath_include_post_type' ), 10, 2 );
        add_action( 'rank_math/sitemap/post_type_archive_link', array( $this, 'rankmath_archive_link' ), 10, 2 );
        add_filter( 'rank_math/sitemap/index', array( $this, 'rankmath_add_to_index' ) );

        // Rank Math - Enable in settings on activation
        add_action( 'admin_init', array( $this, 'rankmath_enable_sitemap_option' ) );

        // All in One SEO
        add_filter( 'aioseo_sitemap_post_types', array( $this, 'add_to_aioseo_sitemap' ) );

        // XML Sitemap Generator (by Auctollo)
        add_filter( 'sm_add_post_type', array( $this, 'add_to_xml_sitemap_generator' ), 10, 2 );
    }

    /**
     * Add to WordPress Core Sitemap (5.5+)
     */
    public function add_to_core_sitemap( $post_types ) {
        $post_types['lt_location'] = get_post_type_object( 'lt_location' );
        return $post_types;
    }

    /**
     * Add to Yoast SEO Sitemap
     */
    public function add_to_yoast_sitemap( $post_types ) {
        $post_types[] = 'lt_location';
        return array_unique( $post_types );
    }

    /**
     * Add to Rank Math Sitemap
     */
    public function add_to_rankmath_sitemap( $post_types ) {
        $post_types['lt_location'] = 'lt_location';
        return $post_types;
    }

    /**
     * Ensure Rank Math doesn't exclude our post type
     */
    public function rankmath_include_post_type( $exclude, $post_type ) {
        if ( $post_type === 'lt_location' ) {
            return false;
        }
        return $exclude;
    }

    /**
     * Rank Math archive link
     */
    public function rankmath_archive_link( $link, $post_type ) {
        if ( $post_type === 'lt_location' ) {
            return get_post_type_archive_link( 'lt_location' );
        }
        return $link;
    }

    /**
     * Add lt_location sitemap to Rank Math sitemap index
     */
    public function rankmath_add_to_index( $index ) {
        // Check if lt_location sitemap already exists in index
        $sitemap_url = home_url( '/lt_location-sitemap.xml' );

        foreach ( $index as $entry ) {
            if ( isset( $entry['loc'] ) && strpos( $entry['loc'], 'lt_location-sitemap' ) !== false ) {
                return $index; // Already exists
            }
        }

        // Get the latest lt_location post for lastmod
        $latest_post = get_posts( array(
            'post_type'      => 'lt_location',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'orderby'        => 'modified',
            'order'          => 'DESC',
        ) );

        $lastmod = ! empty( $latest_post ) ? $latest_post[0]->post_modified_gmt : current_time( 'mysql', true );

        // Add lt_location sitemap to index
        $index[] = array(
            'loc'     => $sitemap_url,
            'lastmod' => $lastmod,
        );

        return $index;
    }

    /**
     * Enable Rank Math sitemap option for lt_location
     */
    public function rankmath_enable_sitemap_option() {
        // Only run once
        if ( get_option( 'lt_rankmath_sitemap_configured' ) ) {
            return;
        }

        // Check if Rank Math is active
        if ( ! class_exists( 'RankMath' ) ) {
            return;
        }

        // Get Rank Math sitemap options
        $sitemap_options = get_option( 'rank-math-options-sitemap', array() );

        // Enable lt_location in sitemap
        if ( is_array( $sitemap_options ) ) {
            $sitemap_options['pt_lt_location_sitemap'] = 'on';
            $sitemap_options['pt_lt_location_archive_sitemap'] = 'on';
            update_option( 'rank-math-options-sitemap', $sitemap_options );
        }

        // Mark as configured
        update_option( 'lt_rankmath_sitemap_configured', true );
    }

    /**
     * Add to All in One SEO Sitemap
     */
    public function add_to_aioseo_sitemap( $post_types ) {
        $post_types[] = 'lt_location';
        return array_unique( $post_types );
    }

    /**
     * Add to XML Sitemap Generator
     */
    public function add_to_xml_sitemap_generator( $include, $post_type ) {
        if ( $post_type === 'lt_location' ) {
            return true;
        }
        return $include;
    }

    /**
     * Register the custom post type
     */
    public function register() {
        $labels = array(
            'name'                  => _x( 'Locations', 'Post type general name', 'liontrust-locations' ),
            'singular_name'         => _x( 'Location', 'Post type singular name', 'liontrust-locations' ),
            'menu_name'             => _x( 'Locations', 'Admin Menu text', 'liontrust-locations' ),
            'name_admin_bar'        => _x( 'Location', 'Add New on Toolbar', 'liontrust-locations' ),
            'add_new'               => __( 'Add New', 'liontrust-locations' ),
            'add_new_item'          => __( 'Add New Location', 'liontrust-locations' ),
            'new_item'              => __( 'New Location', 'liontrust-locations' ),
            'edit_item'             => __( 'Edit Location', 'liontrust-locations' ),
            'view_item'             => __( 'View Location', 'liontrust-locations' ),
            'all_items'             => __( 'All Locations', 'liontrust-locations' ),
            'search_items'          => __( 'Search Locations', 'liontrust-locations' ),
            'parent_item_colon'     => __( 'Parent Location:', 'liontrust-locations' ),
            'not_found'             => __( 'No locations found.', 'liontrust-locations' ),
            'not_found_in_trash'    => __( 'No locations found in Trash.', 'liontrust-locations' ),
            'featured_image'        => _x( 'Location Image', 'Overrides the "Featured Image" phrase', 'liontrust-locations' ),
            'set_featured_image'    => _x( 'Set location image', 'Overrides the "Set featured image" phrase', 'liontrust-locations' ),
            'remove_featured_image' => _x( 'Remove location image', 'Overrides the "Remove featured image" phrase', 'liontrust-locations' ),
            'use_featured_image'    => _x( 'Use as location image', 'Overrides the "Use as featured image" phrase', 'liontrust-locations' ),
            'archives'              => _x( 'Location Archives', 'The post type archive label', 'liontrust-locations' ),
            'insert_into_item'      => _x( 'Insert into location', 'Overrides the "Insert into post" phrase', 'liontrust-locations' ),
            'uploaded_to_this_item' => _x( 'Uploaded to this location', 'Overrides the "Uploaded to this post" phrase', 'liontrust-locations' ),
            'filter_items_list'     => _x( 'Filter locations list', 'Screen reader text', 'liontrust-locations' ),
            'items_list_navigation' => _x( 'Locations list navigation', 'Screen reader text', 'liontrust-locations' ),
            'items_list'            => _x( 'Locations list', 'Screen reader text', 'liontrust-locations' ),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'exclude_from_search' => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'query_var'           => true,
            'rewrite'             => array(
                'slug'         => 'popular-locations',
                'with_front'   => false,
                'hierarchical' => true,
            ),
            'capability_type'     => 'page',
            'has_archive'         => true,
            'hierarchical'        => true,
            'menu_position'       => 20,
            'menu_icon'           => 'dashicons-location-alt',
            'supports'            => array(
                'title',
                'editor',
                'thumbnail',
                'excerpt',
                'page-attributes',
                'revisions',
            ),
            'show_in_rest'        => true,
            'rest_base'           => 'lt-locations',
            'taxonomies'          => array( 'lt_property_type', 'lt_region' ),
        );

        register_post_type( 'lt_location', $args );

        // Register meta for REST API
        $this->register_meta();
    }

    /**
     * Register meta fields for REST API
     */
    private function register_meta() {
        $meta_fields = array(
            '_lt_short_description' => array(
                'type'         => 'string',
                'description'  => 'Short description for cards',
                'single'       => true,
                'show_in_rest' => true,
            ),
            '_lt_nearby_locations' => array(
                'type'         => 'array',
                'description'  => 'Manually selected nearby locations',
                'single'       => true,
                'show_in_rest' => array(
                    'schema' => array(
                        'type'  => 'array',
                        'items' => array( 'type' => 'integer' ),
                    ),
                ),
            ),
            '_lt_latitude' => array(
                'type'         => 'number',
                'description'  => 'Location latitude',
                'single'       => true,
                'show_in_rest' => true,
            ),
            '_lt_longitude' => array(
                'type'         => 'number',
                'description'  => 'Location longitude',
                'single'       => true,
                'show_in_rest' => true,
            ),
        );

        foreach ( $meta_fields as $key => $args ) {
            register_post_meta( 'lt_location', $key, $args );
        }
    }
}
