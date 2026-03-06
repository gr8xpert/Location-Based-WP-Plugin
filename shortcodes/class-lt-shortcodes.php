<?php
/**
 * Shortcodes
 *
 * @package LionTrust_Locations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LT_Shortcodes {

    /**
     * Register all shortcodes
     */
    public function register() {
        add_shortcode( 'lt_search', array( $this, 'search_shortcode' ) );
        add_shortcode( 'lt_location_grid', array( $this, 'grid_shortcode' ) );
        add_shortcode( 'lt_interlinking', array( $this, 'interlinking_shortcode' ) );
        add_shortcode( 'lt_nearby_locations', array( $this, 'nearby_shortcode' ) );
    }

    /**
     * Search shortcode with AJAX results
     *
     * [lt_search per_page="12" show_region_filter="true"]
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function search_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'per_page'           => 12,
            'show_region_filter' => 'false',
            'placeholder'        => __( 'Search locations...', 'liontrust-locations' ),
            'columns'            => 3,
        ), $atts, 'lt_search' );

        // Ensure scripts are loaded
        wp_enqueue_script( 'lt-search' );
        wp_enqueue_style( 'lt-public' );

        $regions = lt_get_regions();
        $show_regions = filter_var( $atts['show_region_filter'], FILTER_VALIDATE_BOOLEAN );

        ob_start();
        ?>
        <div class="lt-search-container">
            <form class="lt-search-form" role="search">
                <div class="lt-search-wrapper">
                    <div class="lt-search-field">
                        <label for="lt-search-input" class="screen-reader-text">
                            <?php esc_html_e( 'Search locations', 'liontrust-locations' ); ?>
                        </label>
                        <input
                            type="search"
                            id="lt-search-input"
                            class="lt-search-input"
                            placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
                            autocomplete="off"
                        >
                    </div>

                    <?php if ( $show_regions && ! empty( $regions ) ) : ?>
                        <div class="lt-search-field">
                            <label for="lt-search-region" class="screen-reader-text">
                                <?php esc_html_e( 'Filter by region', 'liontrust-locations' ); ?>
                            </label>
                            <select id="lt-search-region" class="lt-search-region lt-search-select">
                                <option value=""><?php esc_html_e( 'All Regions', 'liontrust-locations' ); ?></option>
                                <?php foreach ( $regions as $region ) : ?>
                                    <option value="<?php echo esc_attr( $region->slug ); ?>">
                                        <?php echo esc_html( $region->name ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="lt-search-button">
                        <?php esc_html_e( 'Search', 'liontrust-locations' ); ?>
                    </button>
                </div>
            </form>

            <div class="lt-search-loading" aria-hidden="true"></div>

            <div class="lt-search-results lt-locations-grid lt-cols-<?php echo esc_attr( $atts['columns'] ); ?>">
                <?php
                // Initial load of locations
                $initial_locations = get_posts( array(
                    'post_type'      => 'lt_location',
                    'post_status'    => 'publish',
                    'post_parent'    => 0,
                    'posts_per_page' => intval( $atts['per_page'] ),
                    'orderby'        => 'title',
                    'order'          => 'ASC',
                ) );

                foreach ( $initial_locations as $location ) {
                    echo lt_render_location_card( $location );
                }
                ?>
            </div>

            <div class="lt-search-pagination lt-pagination"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Static grid shortcode
     *
     * [lt_location_grid count="6" region="costa-del-sol" columns="3"]
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function grid_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'count'         => 6,
            'region'        => '',
            'columns'       => 3,
            'show_children' => 'true',
            'orderby'       => 'title',
            'order'         => 'ASC',
            'ids'           => '',
        ), $atts, 'lt_location_grid' );

        wp_enqueue_style( 'lt-public' );

        $args = array(
            'post_type'      => 'lt_location',
            'post_status'    => 'publish',
            'post_parent'    => 0,
            'posts_per_page' => intval( $atts['count'] ),
            'orderby'        => sanitize_text_field( $atts['orderby'] ),
            'order'          => sanitize_text_field( $atts['order'] ),
        );

        // Specific IDs
        if ( ! empty( $atts['ids'] ) ) {
            $args['post__in'] = array_map( 'absint', explode( ',', $atts['ids'] ) );
            $args['orderby']  = 'post__in';
        }

        // Region filter
        if ( ! empty( $atts['region'] ) ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'lt_region',
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field( $atts['region'] ),
                ),
            );
        }

        $locations = get_posts( $args );

        if ( empty( $locations ) ) {
            return '<div class="lt-no-results"><p>' . esc_html__( 'No locations found.', 'liontrust-locations' ) . '</p></div>';
        }

        $show_children = filter_var( $atts['show_children'], FILTER_VALIDATE_BOOLEAN );

        ob_start();
        ?>
        <div class="lt-locations-grid lt-cols-<?php echo esc_attr( $atts['columns'] ); ?>">
            <?php
            foreach ( $locations as $location ) {
                echo lt_render_location_card( $location, array(
                    'show_children' => $show_children,
                ) );
            }
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Interlinking shortcode
     *
     * [lt_interlinking] - Shows links to all 5 pages in the location set
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function interlinking_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'post_id' => 0,
            'title'   => '',
        ), $atts, 'lt_interlinking' );

        wp_enqueue_style( 'lt-public' );

        $post_id = $atts['post_id'] ? intval( $atts['post_id'] ) : get_the_ID();
        $post = get_post( $post_id );

        if ( ! $post || $post->post_type !== 'lt_location' ) {
            return '';
        }

        $family = lt_get_location_family( $post );
        $parent = $family['parent'];
        $children = $family['children'];

        if ( ! $parent ) {
            return '';
        }

        // Title
        $title = $atts['title'];
        if ( empty( $title ) ) {
            /* translators: %s: Location name */
            $title = sprintf( __( 'Explore %s', 'liontrust-locations' ), $parent->post_title );
        }

        ob_start();
        ?>
        <section class="lt-interlinking">
            <h3 class="lt-interlinking-title"><?php echo esc_html( $title ); ?></h3>
            <div class="lt-interlinks">
                <a href="<?php echo esc_url( get_permalink( $parent->ID ) ); ?>"
                   class="<?php echo ( $post_id === $parent->ID ) ? 'active' : ''; ?>">
                    <?php esc_html_e( 'Overview', 'liontrust-locations' ); ?>
                </a>

                <?php foreach ( $children as $child ) :
                    $property_type = lt_get_property_type( $child );
                    $label = $property_type ? $property_type->name : $child->post_title;
                ?>
                    <a href="<?php echo esc_url( get_permalink( $child->ID ) ); ?>"
                       class="<?php echo ( $post_id === $child->ID ) ? 'active' : ''; ?>">
                        <?php echo esc_html( $label ); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * Nearby locations shortcode
     *
     * [lt_nearby_locations count="12" columns="4"]
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function nearby_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'post_id' => 0,
            'count'   => 12,
            'columns' => 4,
            'title'   => __( 'Nearby Locations', 'liontrust-locations' ),
        ), $atts, 'lt_nearby_locations' );

        wp_enqueue_style( 'lt-public' );

        $post_id = $atts['post_id'] ? intval( $atts['post_id'] ) : get_the_ID();
        $post = get_post( $post_id );

        if ( ! $post || $post->post_type !== 'lt_location' ) {
            return '';
        }

        $nearby = new LT_Nearby();
        $locations = $nearby->get_nearby_locations( $post_id, intval( $atts['count'] ) );

        if ( empty( $locations ) ) {
            return '';
        }

        ob_start();
        ?>
        <section class="lt-nearby">
            <?php if ( ! empty( $atts['title'] ) ) : ?>
                <h3 class="lt-nearby-title"><?php echo esc_html( $atts['title'] ); ?></h3>
            <?php endif; ?>

            <div class="lt-nearby-grid" style="grid-template-columns: repeat(<?php echo esc_attr( $atts['columns'] ); ?>, 1fr);">
                <?php foreach ( $locations as $location ) : ?>
                    <a href="<?php echo esc_url( get_permalink( $location->ID ) ); ?>">
                        <?php echo esc_html( $location->post_title ); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}
