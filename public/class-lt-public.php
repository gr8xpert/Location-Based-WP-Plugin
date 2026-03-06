<?php
/**
 * Public/Frontend Functionality
 *
 * @package LionTrust_Locations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LT_Public {

    /**
     * Enqueue frontend styles
     */
    public function enqueue_styles() {
        // Only load on location pages or when shortcode is present
        if ( ! $this->should_load_assets() ) {
            return;
        }

        wp_enqueue_style(
            'lt-public',
            LT_LOCATIONS_PLUGIN_URL . 'public/css/lt-public.css',
            array(),
            LT_LOCATIONS_VERSION
        );
    }

    /**
     * Enqueue frontend scripts
     */
    public function enqueue_scripts() {
        if ( ! $this->should_load_assets() ) {
            return;
        }

        wp_enqueue_script(
            'lt-search',
            LT_LOCATIONS_PLUGIN_URL . 'public/js/lt-search.js',
            array( 'jquery' ),
            LT_LOCATIONS_VERSION,
            true
        );

        wp_localize_script( 'lt-search', 'ltLocations', array(
            'apiUrl'     => rest_url( 'lt/v1/' ),
            'nonce'      => wp_create_nonce( 'wp_rest' ),
            'i18n'       => array(
                'loading'     => __( 'Loading...', 'liontrust-locations' ),
                'noResults'   => __( 'No locations found.', 'liontrust-locations' ),
                'error'       => __( 'An error occurred. Please try again.', 'liontrust-locations' ),
                'readMore'    => __( 'Explore Area', 'liontrust-locations' ),
                'loadMore'    => __( 'Load More', 'liontrust-locations' ),
                'apartments'  => __( 'Apartments', 'liontrust-locations' ),
                'penthouses'  => __( 'Penthouses', 'liontrust-locations' ),
                'townhouses'  => __( 'Townhouses', 'liontrust-locations' ),
                'villas'      => __( 'Villas', 'liontrust-locations' ),
            ),
            'placeholderImage' => LT_LOCATIONS_PLUGIN_URL . 'public/images/placeholder.svg',
        ) );
    }

    /**
     * Check if assets should be loaded
     *
     * @return bool
     */
    private function should_load_assets() {
        global $post;

        // Always load on location CPT pages
        if ( is_singular( 'lt_location' ) || is_post_type_archive( 'lt_location' ) ) {
            return true;
        }

        // Check for shortcodes in content
        if ( $post && is_a( $post, 'WP_Post' ) ) {
            $shortcodes = array( 'lt_search', 'lt_location_grid', 'lt_interlinking', 'lt_nearby_locations' );

            foreach ( $shortcodes as $shortcode ) {
                if ( has_shortcode( $post->post_content, $shortcode ) ) {
                    return true;
                }
            }
        }

        // Allow filtering
        return apply_filters( 'lt_load_public_assets', false );
    }

    /**
     * Load custom templates
     *
     * @param string $template Current template path.
     * @return string Modified template path.
     */
    public function template_loader( $template ) {
        if ( is_post_type_archive( 'lt_location' ) ) {
            $custom_template = $this->locate_template( 'archive-lt_location.php' );
            if ( $custom_template ) {
                return $custom_template;
            }
        }

        if ( is_singular( 'lt_location' ) ) {
            $custom_template = $this->locate_template( 'single-lt_location.php' );
            if ( $custom_template ) {
                return $custom_template;
            }
        }

        return $template;
    }

    /**
     * Locate template file with theme override support
     *
     * @param string $template_name Template file name.
     * @return string|false Template path or false if not found.
     */
    private function locate_template( $template_name ) {
        // Check theme first (allows theme override)
        $theme_template = locate_template( array(
            'liontrust-locations/' . $template_name,
            $template_name,
        ) );

        if ( $theme_template ) {
            return $theme_template;
        }

        // Fall back to plugin template
        $plugin_template = LT_LOCATIONS_PLUGIN_DIR . 'templates/' . $template_name;

        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }

        return false;
    }
}
