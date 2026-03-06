<?php
/**
 * Plugin Activator
 *
 * Handles activation tasks like creating default taxonomy terms.
 *
 * @package LionTrust_Locations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LT_Activator {

    /**
     * Activate the plugin
     */
    public static function activate() {
        // Register CPT and taxonomies first
        self::register_post_type();
        self::register_taxonomies();

        // Create default terms
        self::create_default_terms();

        // Store plugin version
        update_option( 'lt_locations_version', LT_LOCATIONS_VERSION );

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Register the CPT during activation
     */
    private static function register_post_type() {
        $args = array(
            'public'       => true,
            'hierarchical' => true,
            'has_archive'  => true,
            'rewrite'      => array( 'slug' => 'popular-locations', 'with_front' => false ),
        );

        register_post_type( 'lt_location', $args );
    }

    /**
     * Register taxonomies during activation
     */
    private static function register_taxonomies() {
        register_taxonomy( 'lt_property_type', 'lt_location', array(
            'hierarchical' => true,
            'rewrite'      => array( 'slug' => 'property-type' ),
        ) );

        register_taxonomy( 'lt_region', 'lt_location', array(
            'hierarchical' => true,
            'rewrite'      => array( 'slug' => 'region' ),
        ) );
    }

    /**
     * Create default taxonomy terms
     */
    private static function create_default_terms() {
        // Default property types
        $property_types = array(
            'apartments'  => 'Apartments',
            'penthouses'  => 'Penthouses',
            'townhouses'  => 'Townhouses',
            'villas'      => 'Villas',
        );

        foreach ( $property_types as $slug => $name ) {
            if ( ! term_exists( $slug, 'lt_property_type' ) ) {
                wp_insert_term( $name, 'lt_property_type', array( 'slug' => $slug ) );
            }
        }

        // Default regions
        $regions = array(
            'costa-del-sol'   => 'Costa del Sol',
            'costa-blanca'    => 'Costa Blanca',
            'costa-brava'     => 'Costa Brava',
            'balearic-islands' => 'Balearic Islands',
            'canary-islands'  => 'Canary Islands',
        );

        foreach ( $regions as $slug => $name ) {
            if ( ! term_exists( $slug, 'lt_region' ) ) {
                wp_insert_term( $name, 'lt_region', array( 'slug' => $slug ) );
            }
        }
    }
}
