<?php
/**
 * Helper Functions
 *
 * @package LionTrust_Locations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get the parent location for a given location
 *
 * @param int|WP_Post $post Post ID or object.
 * @return WP_Post|null Parent post object or null.
 */
function lt_get_parent_location( $post = null ) {
    $post = get_post( $post );

    if ( ! $post || $post->post_type !== 'lt_location' ) {
        return null;
    }

    if ( $post->post_parent === 0 ) {
        return $post;
    }

    return get_post( $post->post_parent );
}

/**
 * Get all children of a location
 *
 * @param int|WP_Post $post Post ID or object.
 * @return array Array of child posts.
 */
function lt_get_child_locations( $post = null ) {
    $post = get_post( $post );

    if ( ! $post || $post->post_type !== 'lt_location' ) {
        return array();
    }

    $parent_id = $post->post_parent === 0 ? $post->ID : $post->post_parent;

    return get_posts( array(
        'post_type'      => 'lt_location',
        'post_status'    => 'publish',
        'post_parent'    => $parent_id,
        'posts_per_page' => -1,
        'orderby'        => 'menu_order title',
        'order'          => 'ASC',
    ) );
}

/**
 * Get the location family (parent + all children)
 *
 * @param int|WP_Post $post Post ID or object.
 * @return array Array with 'parent' and 'children' keys.
 */
function lt_get_location_family( $post = null ) {
    $post = get_post( $post );

    if ( ! $post || $post->post_type !== 'lt_location' ) {
        return array( 'parent' => null, 'children' => array() );
    }

    $parent = lt_get_parent_location( $post );
    $children = lt_get_child_locations( $post );

    return array(
        'parent'   => $parent,
        'children' => $children,
    );
}

/**
 * Check if location is a parent
 *
 * @param int|WP_Post $post Post ID or object.
 * @return bool
 */
function lt_is_parent_location( $post = null ) {
    $post = get_post( $post );

    if ( ! $post || $post->post_type !== 'lt_location' ) {
        return false;
    }

    return $post->post_parent === 0;
}

/**
 * Check if location is a child
 *
 * @param int|WP_Post $post Post ID or object.
 * @return bool
 */
function lt_is_child_location( $post = null ) {
    $post = get_post( $post );

    if ( ! $post || $post->post_type !== 'lt_location' ) {
        return false;
    }

    return $post->post_parent !== 0;
}

/**
 * Get short description for a location
 *
 * @param int|WP_Post $post Post ID or object.
 * @return string
 */
function lt_get_short_description( $post = null ) {
    $post = get_post( $post );

    if ( ! $post ) {
        return '';
    }

    // First priority: custom short description meta field
    $short_desc = get_post_meta( $post->ID, '_lt_short_description', true );

    if ( ! empty( $short_desc ) ) {
        return $short_desc;
    }

    // Second priority: excerpt
    if ( ! empty( $post->post_excerpt ) ) {
        return $post->post_excerpt;
    }

    // Third priority: truncated content (strip shortcodes/page builder code)
    $content = $post->post_content;

    // Strip all shortcodes (Divi, Elementor, WPBakery, etc.)
    $content = strip_shortcodes( $content );

    // Remove any remaining bracket content (catches Divi's [/et_pb...] style)
    $content = preg_replace( '/\[[^\]]*\]/', '', $content );

    // Strip HTML tags
    $content = wp_strip_all_tags( $content );

    // Clean up whitespace
    $content = preg_replace( '/\s+/', ' ', $content );
    $content = trim( $content );

    // If content is empty after stripping, return empty
    if ( empty( $content ) ) {
        return '';
    }

    return wp_trim_words( $content, 25, '...' );
}

/**
 * Get property type term for a child location
 *
 * @param int|WP_Post $post Post ID or object.
 * @return WP_Term|null
 */
function lt_get_property_type( $post = null ) {
    $post = get_post( $post );

    if ( ! $post ) {
        return null;
    }

    $terms = get_the_terms( $post->ID, 'lt_property_type' );

    if ( $terms && ! is_wp_error( $terms ) ) {
        return $terms[0];
    }

    return null;
}

/**
 * Get region term for a location
 *
 * @param int|WP_Post $post Post ID or object.
 * @return WP_Term|null
 */
function lt_get_region( $post = null ) {
    $post = get_post( $post );

    if ( ! $post ) {
        return null;
    }

    $terms = get_the_terms( $post->ID, 'lt_region' );

    if ( $terms && ! is_wp_error( $terms ) ) {
        return $terms[0];
    }

    return null;
}

/**
 * Render a location card
 *
 * @param int|WP_Post $post       Post ID or object.
 * @param array       $args       Additional arguments.
 * @return string HTML output.
 */
function lt_render_location_card( $post = null, $args = array() ) {
    $post = get_post( $post );

    if ( ! $post ) {
        return '';
    }

    $defaults = array(
        'show_children' => true,
        'show_image'    => true,
        'show_excerpt'  => true,
        'class'         => '',
    );

    $args = wp_parse_args( $args, $defaults );

    $is_parent = lt_is_parent_location( $post );
    $parent = lt_get_parent_location( $post );
    $children = $is_parent ? lt_get_child_locations( $post ) : array();

    ob_start();
    ?>
    <article class="lt-location-card <?php echo esc_attr( $args['class'] ); ?>">
        <?php if ( $args['show_image'] ) : ?>
            <div class="lt-card-image">
                <a href="<?php echo esc_url( get_permalink( $post ) ); ?>">
                    <?php if ( has_post_thumbnail( $post ) ) : ?>
                        <?php echo get_the_post_thumbnail( $post, 'large', array( 'alt' => esc_attr( $post->post_title ) ) ); ?>
                    <?php else : ?>
                        <div class="lt-card-placeholder"></div>
                    <?php endif; ?>
                </a>
            </div>
        <?php endif; ?>

        <div class="lt-card-content">
            <h3 class="lt-card-title">
                <a href="<?php echo esc_url( get_permalink( $post ) ); ?>">
                    <?php echo esc_html( $is_parent ? $post->post_title : $parent->post_title ); ?>
                </a>
            </h3>

            <?php if ( $args['show_excerpt'] ) : ?>
                <p class="lt-card-excerpt">
                    <?php echo esc_html( lt_get_short_description( $is_parent ? $post : $parent ) ); ?>
                </p>
            <?php endif; ?>

            <a href="<?php echo esc_url( get_permalink( $is_parent ? $post : $parent ) ); ?>" class="lt-read-more">
                <?php esc_html_e( 'Explore Area', 'liontrust-locations' ); ?>
            </a>
        </div>

        <?php if ( $args['show_children'] && ! empty( $children ) ) : ?>
            <div class="lt-card-buttons">
                <?php foreach ( $children as $child ) :
                    $property_type = lt_get_property_type( $child );
                    if ( $property_type ) :
                ?>
                    <a href="<?php echo esc_url( get_permalink( $child ) ); ?>" class="lt-btn">
                        <?php echo esc_html( $property_type->name ); ?>
                    </a>
                <?php endif; endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
    <?php
    return ob_get_clean();
}

/**
 * Get default property types
 *
 * @return array
 */
function lt_get_default_property_types() {
    return array(
        'apartments'  => __( 'Apartments', 'liontrust-locations' ),
        'penthouses'  => __( 'Penthouses', 'liontrust-locations' ),
        'townhouses'  => __( 'Townhouses', 'liontrust-locations' ),
        'villas'      => __( 'Villas', 'liontrust-locations' ),
    );
}

/**
 * Get all regions
 *
 * @return array Array of WP_Term objects.
 */
function lt_get_regions() {
    $terms = get_terms( array(
        'taxonomy'   => 'lt_region',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ) );

    return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * Get all property types
 *
 * @return array Array of WP_Term objects.
 */
function lt_get_property_types() {
    $terms = get_terms( array(
        'taxonomy'   => 'lt_property_type',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ) );

    return is_wp_error( $terms ) ? array() : $terms;
}
