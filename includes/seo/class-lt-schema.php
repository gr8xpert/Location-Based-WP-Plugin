<?php
/**
 * JSON-LD Schema Output
 *
 * @package LionTrust_Locations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LT_Schema {

    /**
     * Output JSON-LD schema in head
     */
    public function output_schema() {
        if ( ! is_singular( 'lt_location' ) ) {
            return;
        }

        $post = get_queried_object();

        if ( ! $post ) {
            return;
        }

        $schemas = array();

        // Breadcrumb schema
        $schemas[] = $this->get_breadcrumb_schema( $post );

        // Place or WebPage schema based on parent/child
        if ( $post->post_parent === 0 ) {
            $schemas[] = $this->get_place_schema( $post );
        } else {
            $schemas[] = $this->get_webpage_schema( $post );
        }

        foreach ( $schemas as $schema ) {
            if ( ! empty( $schema ) ) {
                echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
            }
        }
    }

    /**
     * Get Place schema for parent locations
     *
     * @param WP_Post $post Post object.
     * @return array
     */
    private function get_place_schema( $post ) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type'    => 'Place',
            '@id'      => get_permalink( $post->ID ) . '#place',
            'name'     => $post->post_title,
            'url'      => get_permalink( $post->ID ),
        );

        // Description
        $short_desc = get_post_meta( $post->ID, '_lt_short_description', true );
        if ( ! empty( $short_desc ) ) {
            $schema['description'] = $short_desc;
        } elseif ( ! empty( $post->post_excerpt ) ) {
            $schema['description'] = $post->post_excerpt;
        }

        // Image
        $image_id = get_post_thumbnail_id( $post->ID );
        if ( $image_id ) {
            $image_url = get_the_post_thumbnail_url( $post->ID, 'full' );
            $image_meta = wp_get_attachment_metadata( $image_id );

            $schema['image'] = array(
                '@type'  => 'ImageObject',
                'url'    => $image_url,
                'width'  => isset( $image_meta['width'] ) ? $image_meta['width'] : null,
                'height' => isset( $image_meta['height'] ) ? $image_meta['height'] : null,
            );
        }

        // Geo coordinates
        $lat = get_post_meta( $post->ID, '_lt_latitude', true );
        $lon = get_post_meta( $post->ID, '_lt_longitude', true );

        if ( ! empty( $lat ) && ! empty( $lon ) ) {
            $schema['geo'] = array(
                '@type'     => 'GeoCoordinates',
                'latitude'  => floatval( $lat ),
                'longitude' => floatval( $lon ),
            );
        }

        // Region
        $regions = get_the_terms( $post->ID, 'lt_region' );
        if ( $regions && ! is_wp_error( $regions ) ) {
            $schema['containedInPlace'] = array(
                '@type' => 'AdministrativeArea',
                'name'  => $regions[0]->name,
            );
        }

        return $schema;
    }

    /**
     * Get WebPage schema for child locations (property type pages)
     *
     * @param WP_Post $post Post object.
     * @return array
     */
    private function get_webpage_schema( $post ) {
        $parent = get_post( $post->post_parent );

        $schema = array(
            '@context'        => 'https://schema.org',
            '@type'           => 'WebPage',
            '@id'             => get_permalink( $post->ID ) . '#webpage',
            'name'            => $post->post_title,
            'url'             => get_permalink( $post->ID ),
            'isPartOf'        => array(
                '@type' => 'WebSite',
                'url'   => home_url( '/' ),
                'name'  => get_bloginfo( 'name' ),
            ),
        );

        // Description
        $short_desc = get_post_meta( $post->ID, '_lt_short_description', true );
        if ( ! empty( $short_desc ) ) {
            $schema['description'] = $short_desc;
        } elseif ( ! empty( $post->post_excerpt ) ) {
            $schema['description'] = $post->post_excerpt;
        }

        // Image
        $image_id = get_post_thumbnail_id( $post->ID );
        if ( ! $image_id && $parent ) {
            $image_id = get_post_thumbnail_id( $parent->ID );
        }

        if ( $image_id ) {
            $schema['primaryImageOfPage'] = array(
                '@type' => 'ImageObject',
                'url'   => wp_get_attachment_url( $image_id ),
            );
        }

        // Property type
        $property_types = get_the_terms( $post->ID, 'lt_property_type' );
        if ( $property_types && ! is_wp_error( $property_types ) ) {
            $schema['about'] = array(
                '@type' => 'Thing',
                'name'  => $property_types[0]->name . ' in ' . ( $parent ? $parent->post_title : '' ),
            );
        }

        // Related parent location
        if ( $parent ) {
            $schema['mainEntity'] = array(
                '@type' => 'Place',
                '@id'   => get_permalink( $parent->ID ) . '#place',
                'name'  => $parent->post_title,
                'url'   => get_permalink( $parent->ID ),
            );
        }

        return $schema;
    }

    /**
     * Get BreadcrumbList schema
     *
     * @param WP_Post $post Post object.
     * @return array
     */
    private function get_breadcrumb_schema( $post ) {
        $items = array();
        $position = 1;

        // Home
        $items[] = array(
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => __( 'Home', 'liontrust-locations' ),
            'item'     => home_url( '/' ),
        );

        // Locations archive
        $items[] = array(
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => __( 'Locations', 'liontrust-locations' ),
            'item'     => get_post_type_archive_link( 'lt_location' ),
        );

        // Parent location (if this is a child)
        if ( $post->post_parent !== 0 ) {
            $parent = get_post( $post->post_parent );
            if ( $parent ) {
                $items[] = array(
                    '@type'    => 'ListItem',
                    'position' => $position++,
                    'name'     => $parent->post_title,
                    'item'     => get_permalink( $parent->ID ),
                );
            }
        }

        // Current page
        $items[] = array(
            '@type'    => 'ListItem',
            'position' => $position,
            'name'     => $post->post_title,
            'item'     => get_permalink( $post->ID ),
        );

        return array(
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        );
    }
}
