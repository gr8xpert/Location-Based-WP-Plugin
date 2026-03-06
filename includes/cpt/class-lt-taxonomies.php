<?php
/**
 * Custom Taxonomies
 *
 * @package LionTrust_Locations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LT_Taxonomies {

    /**
     * Register custom taxonomies
     */
    public function register() {
        $this->register_property_type();
        $this->register_region();
    }

    /**
     * Register Property Type taxonomy
     */
    private function register_property_type() {
        $labels = array(
            'name'                       => _x( 'Property Types', 'taxonomy general name', 'liontrust-locations' ),
            'singular_name'              => _x( 'Property Type', 'taxonomy singular name', 'liontrust-locations' ),
            'search_items'               => __( 'Search Property Types', 'liontrust-locations' ),
            'popular_items'              => __( 'Popular Property Types', 'liontrust-locations' ),
            'all_items'                  => __( 'All Property Types', 'liontrust-locations' ),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __( 'Edit Property Type', 'liontrust-locations' ),
            'update_item'                => __( 'Update Property Type', 'liontrust-locations' ),
            'add_new_item'               => __( 'Add New Property Type', 'liontrust-locations' ),
            'new_item_name'              => __( 'New Property Type Name', 'liontrust-locations' ),
            'separate_items_with_commas' => __( 'Separate property types with commas', 'liontrust-locations' ),
            'add_or_remove_items'        => __( 'Add or remove property types', 'liontrust-locations' ),
            'choose_from_most_used'      => __( 'Choose from the most used property types', 'liontrust-locations' ),
            'not_found'                  => __( 'No property types found.', 'liontrust-locations' ),
            'menu_name'                  => __( 'Property Types', 'liontrust-locations' ),
            'back_to_items'              => __( '&larr; Back to Property Types', 'liontrust-locations' ),
        );

        $args = array(
            'labels'            => $labels,
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud'     => false,
            'show_in_rest'      => true,
            'rest_base'         => 'lt-property-types',
            'rewrite'           => array(
                'slug'         => 'property-type',
                'with_front'   => false,
                'hierarchical' => false,
            ),
        );

        register_taxonomy( 'lt_property_type', array( 'lt_location' ), $args );
    }

    /**
     * Register Region taxonomy
     */
    private function register_region() {
        $labels = array(
            'name'                       => _x( 'Regions', 'taxonomy general name', 'liontrust-locations' ),
            'singular_name'              => _x( 'Region', 'taxonomy singular name', 'liontrust-locations' ),
            'search_items'               => __( 'Search Regions', 'liontrust-locations' ),
            'popular_items'              => __( 'Popular Regions', 'liontrust-locations' ),
            'all_items'                  => __( 'All Regions', 'liontrust-locations' ),
            'parent_item'                => __( 'Parent Region', 'liontrust-locations' ),
            'parent_item_colon'          => __( 'Parent Region:', 'liontrust-locations' ),
            'edit_item'                  => __( 'Edit Region', 'liontrust-locations' ),
            'update_item'                => __( 'Update Region', 'liontrust-locations' ),
            'add_new_item'               => __( 'Add New Region', 'liontrust-locations' ),
            'new_item_name'              => __( 'New Region Name', 'liontrust-locations' ),
            'separate_items_with_commas' => __( 'Separate regions with commas', 'liontrust-locations' ),
            'add_or_remove_items'        => __( 'Add or remove regions', 'liontrust-locations' ),
            'choose_from_most_used'      => __( 'Choose from the most used regions', 'liontrust-locations' ),
            'not_found'                  => __( 'No regions found.', 'liontrust-locations' ),
            'menu_name'                  => __( 'Regions', 'liontrust-locations' ),
            'back_to_items'              => __( '&larr; Back to Regions', 'liontrust-locations' ),
        );

        $args = array(
            'labels'            => $labels,
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud'     => false,
            'show_in_rest'      => true,
            'rest_base'         => 'lt-regions',
            'rewrite'           => array(
                'slug'         => 'region',
                'with_front'   => false,
                'hierarchical' => true,
            ),
        );

        register_taxonomy( 'lt_region', array( 'lt_location' ), $args );
    }
}
