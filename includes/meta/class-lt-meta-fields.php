<?php
/**
 * Meta Fields
 *
 * Native meta boxes without ACF dependency.
 *
 * @package LionTrust_Locations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LT_Meta_Fields {

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'lt_location_details',
            __( 'Location Details', 'liontrust-locations' ),
            array( $this, 'render_details_meta_box' ),
            'lt_location',
            'normal',
            'high'
        );

        add_meta_box(
            'lt_nearby_locations',
            __( 'Nearby Locations', 'liontrust-locations' ),
            array( $this, 'render_nearby_meta_box' ),
            'lt_location',
            'normal',
            'default'
        );

        add_meta_box(
            'lt_coordinates',
            __( 'Coordinates', 'liontrust-locations' ),
            array( $this, 'render_coordinates_meta_box' ),
            'lt_location',
            'side',
            'default'
        );
    }

    /**
     * Render location details meta box
     *
     * @param WP_Post $post Current post object.
     */
    public function render_details_meta_box( $post ) {
        wp_nonce_field( 'lt_meta_nonce', 'lt_meta_nonce_field' );

        $short_description = get_post_meta( $post->ID, '_lt_short_description', true );
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="lt_short_description"><?php esc_html_e( 'Short Description', 'liontrust-locations' ); ?></label>
                </th>
                <td>
                    <textarea
                        id="lt_short_description"
                        name="lt_short_description"
                        rows="3"
                        class="large-text"
                        placeholder="<?php esc_attr_e( 'Brief description shown on location cards...', 'liontrust-locations' ); ?>"
                    ><?php echo esc_textarea( $short_description ); ?></textarea>
                    <p class="description">
                        <?php esc_html_e( 'This text appears on location cards in search results and grids.', 'liontrust-locations' ); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render nearby locations meta box
     *
     * @param WP_Post $post Current post object.
     */
    public function render_nearby_meta_box( $post ) {
        // Only show for parent locations
        if ( $post->post_parent !== 0 ) {
            echo '<p class="description">' . esc_html__( 'Nearby locations can only be set on parent locations.', 'liontrust-locations' ) . '</p>';
            return;
        }

        $selected_nearby = get_post_meta( $post->ID, '_lt_nearby_locations', true );
        if ( ! is_array( $selected_nearby ) ) {
            $selected_nearby = array();
        }

        // Get all parent locations except current
        $locations = get_posts( array(
            'post_type'      => 'lt_location',
            'post_status'    => array( 'publish', 'draft' ),
            'posts_per_page' => -1,
            'post_parent'    => 0,
            'exclude'        => array( $post->ID ),
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );
        ?>
        <p class="description">
            <?php esc_html_e( 'Select nearby locations to display. If none selected, the system will show 12 random locations.', 'liontrust-locations' ); ?>
        </p>

        <div class="lt-nearby-locations-list" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin-top: 10px;">
            <?php if ( empty( $locations ) ) : ?>
                <p><?php esc_html_e( 'No other locations available.', 'liontrust-locations' ); ?></p>
            <?php else : ?>
                <?php foreach ( $locations as $location ) : ?>
                    <label style="display: block; margin-bottom: 5px;">
                        <input
                            type="checkbox"
                            name="lt_nearby_locations[]"
                            value="<?php echo esc_attr( $location->ID ); ?>"
                            <?php checked( in_array( $location->ID, $selected_nearby, true ) ); ?>
                        >
                        <?php echo esc_html( $location->post_title ); ?>
                        <?php
                        $regions = get_the_terms( $location->ID, 'lt_region' );
                        if ( $regions && ! is_wp_error( $regions ) ) {
                            echo '<span style="color: #666; font-size: 12px;"> - ' . esc_html( $regions[0]->name ) . '</span>';
                        }
                        ?>
                    </label>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <p style="margin-top: 10px;">
            <strong><?php esc_html_e( 'Selected:', 'liontrust-locations' ); ?></strong>
            <span id="lt-nearby-count"><?php echo count( $selected_nearby ); ?></span>
            <?php esc_html_e( 'locations', 'liontrust-locations' ); ?>
        </p>
        <?php
    }

    /**
     * Render coordinates meta box
     *
     * @param WP_Post $post Current post object.
     */
    public function render_coordinates_meta_box( $post ) {
        // Only show for parent locations
        if ( $post->post_parent !== 0 ) {
            echo '<p class="description">' . esc_html__( 'Coordinates can only be set on parent locations.', 'liontrust-locations' ) . '</p>';
            return;
        }

        $latitude  = get_post_meta( $post->ID, '_lt_latitude', true );
        $longitude = get_post_meta( $post->ID, '_lt_longitude', true );
        ?>
        <p>
            <label for="lt_latitude"><?php esc_html_e( 'Latitude', 'liontrust-locations' ); ?></label>
            <input
                type="number"
                id="lt_latitude"
                name="lt_latitude"
                value="<?php echo esc_attr( $latitude ); ?>"
                step="any"
                class="widefat"
                placeholder="36.5271"
            >
        </p>
        <p>
            <label for="lt_longitude"><?php esc_html_e( 'Longitude', 'liontrust-locations' ); ?></label>
            <input
                type="number"
                id="lt_longitude"
                name="lt_longitude"
                value="<?php echo esc_attr( $longitude ); ?>"
                step="any"
                class="widefat"
                placeholder="-4.8828"
            >
        </p>
        <p class="description">
            <?php esc_html_e( 'Optional. Used for structured data and potential distance calculations.', 'liontrust-locations' ); ?>
        </p>
        <?php
    }

    /**
     * Save meta fields
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     */
    public function save_meta( $post_id, $post ) {
        // Verify nonce
        if ( ! isset( $_POST['lt_meta_nonce_field'] ) || ! wp_verify_nonce( $_POST['lt_meta_nonce_field'], 'lt_meta_nonce' ) ) {
            return;
        }

        // Check autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save short description
        if ( isset( $_POST['lt_short_description'] ) ) {
            update_post_meta( $post_id, '_lt_short_description', sanitize_textarea_field( $_POST['lt_short_description'] ) );
        }

        // Save nearby locations (only for parent locations)
        if ( $post->post_parent === 0 ) {
            if ( isset( $_POST['lt_nearby_locations'] ) && is_array( $_POST['lt_nearby_locations'] ) ) {
                $nearby = array_map( 'absint', $_POST['lt_nearby_locations'] );
                update_post_meta( $post_id, '_lt_nearby_locations', $nearby );
            } else {
                delete_post_meta( $post_id, '_lt_nearby_locations' );
            }

            // Save coordinates with validation
            if ( isset( $_POST['lt_latitude'] ) && $_POST['lt_latitude'] !== '' ) {
                $latitude = floatval( $_POST['lt_latitude'] );
                // Validate latitude bounds (-90 to 90)
                if ( $latitude >= -90 && $latitude <= 90 ) {
                    update_post_meta( $post_id, '_lt_latitude', $latitude );
                }
            } else {
                delete_post_meta( $post_id, '_lt_latitude' );
            }

            if ( isset( $_POST['lt_longitude'] ) && $_POST['lt_longitude'] !== '' ) {
                $longitude = floatval( $_POST['lt_longitude'] );
                // Validate longitude bounds (-180 to 180)
                if ( $longitude >= -180 && $longitude <= 180 ) {
                    update_post_meta( $post_id, '_lt_longitude', $longitude );
                }
            } else {
                delete_post_meta( $post_id, '_lt_longitude' );
            }
        }
    }
}
