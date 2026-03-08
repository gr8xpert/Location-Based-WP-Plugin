<?php
/**
 * Bulk Location Creator
 *
 * Admin page for creating location sets (parent + property type children).
 *
 * @package LionTrust_Locations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LT_Bulk_Creator {

    /**
     * Add submenu page
     */
    public function add_menu_page() {
        add_submenu_page(
            'edit.php?post_type=lt_location',
            __( 'Bulk Create', 'liontrust-locations' ),
            __( 'Bulk Create', 'liontrust-locations' ),
            'edit_posts',
            'lt-bulk-create',
            array( $this, 'render_page' )
        );
    }

    /**
     * Render the bulk create page
     */
    public function render_page() {
        // Get property types
        $property_types = get_terms( array(
            'taxonomy'   => 'lt_property_type',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ) );

        // Get regions
        $regions = get_terms( array(
            'taxonomy'   => 'lt_region',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ) );

        // Check for success message
        $created = isset( $_GET['created'] ) ? absint( $_GET['created'] ) : 0;
        $location_name = isset( $_GET['location'] ) ? sanitize_text_field( $_GET['location'] ) : '';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Bulk Create Location Set', 'liontrust-locations' ); ?></h1>

            <?php if ( $created > 0 ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <?php
                        printf(
                            /* translators: %1$d: number of pages, %2$s: location name */
                            esc_html__( 'Successfully created %1$d pages for "%2$s".', 'liontrust-locations' ),
                            $created,
                            esc_html( $location_name )
                        );
                        ?>
                    </p>
                </div>
            <?php endif; ?>

            <div class="lt-bulk-creator-wrap">
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <input type="hidden" name="action" value="lt_bulk_create">
                    <?php wp_nonce_field( 'lt_bulk_create_nonce', 'lt_bulk_nonce' ); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="location_name"><?php esc_html_e( 'Location Name', 'liontrust-locations' ); ?> <span class="required">*</span></label>
                            </th>
                            <td>
                                <input
                                    type="text"
                                    id="location_name"
                                    name="location_name"
                                    class="regular-text"
                                    placeholder="<?php esc_attr_e( 'e.g., Marbella', 'liontrust-locations' ); ?>"
                                    required
                                >
                                <p class="description">
                                    <?php esc_html_e( 'The main location name. Child pages will be named "[Location] [Property Type]".', 'liontrust-locations' ); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="region"><?php esc_html_e( 'Region', 'liontrust-locations' ); ?></label>
                            </th>
                            <td>
                                <select id="region" name="region" class="regular-text">
                                    <option value=""><?php esc_html_e( '&mdash; Select Region &mdash;', 'liontrust-locations' ); ?></option>
                                    <?php foreach ( $regions as $region ) : ?>
                                        <option value="<?php echo esc_attr( $region->term_id ); ?>">
                                            <?php echo esc_html( $region->name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">
                                    <?php esc_html_e( 'Region will be assigned to all created pages.', 'liontrust-locations' ); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <?php esc_html_e( 'Property Types', 'liontrust-locations' ); ?>
                            </th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><?php esc_html_e( 'Property Types', 'liontrust-locations' ); ?></legend>
                                    <?php if ( ! empty( $property_types ) && ! is_wp_error( $property_types ) ) : ?>
                                        <?php foreach ( $property_types as $type ) : ?>
                                            <label style="display: block; margin-bottom: 8px;">
                                                <input
                                                    type="checkbox"
                                                    name="property_types[]"
                                                    value="<?php echo esc_attr( $type->term_id ); ?>"
                                                    checked
                                                >
                                                <?php echo esc_html( $type->name ); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <p class="description">
                                            <?php esc_html_e( 'No property types found. Please add some property types first.', 'liontrust-locations' ); ?>
                                        </p>
                                    <?php endif; ?>
                                </fieldset>
                                <p class="description">
                                    <?php esc_html_e( 'Select which property type child pages to create.', 'liontrust-locations' ); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <?php esc_html_e( 'Coordinates (Optional)', 'liontrust-locations' ); ?>
                            </th>
                            <td>
                                <label>
                                    <?php esc_html_e( 'Latitude:', 'liontrust-locations' ); ?>
                                    <input
                                        type="number"
                                        name="latitude"
                                        step="any"
                                        class="small-text"
                                        placeholder="36.5271"
                                    >
                                </label>
                                <br><br>
                                <label>
                                    <?php esc_html_e( 'Longitude:', 'liontrust-locations' ); ?>
                                    <input
                                        type="number"
                                        name="longitude"
                                        step="any"
                                        class="small-text"
                                        placeholder="-4.8828"
                                    >
                                </label>
                                <p class="description">
                                    <?php esc_html_e( 'Coordinates will be assigned to the parent location.', 'liontrust-locations' ); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <?php esc_html_e( 'Post Status', 'liontrust-locations' ); ?>
                            </th>
                            <td>
                                <select name="post_status">
                                    <option value="draft"><?php esc_html_e( 'Draft', 'liontrust-locations' ); ?></option>
                                    <option value="publish"><?php esc_html_e( 'Published', 'liontrust-locations' ); ?></option>
                                    <option value="pending"><?php esc_html_e( 'Pending Review', 'liontrust-locations' ); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>

                    <div class="lt-preview-box" style="background: #f9f9f9; padding: 15px; margin: 20px 0; border-left: 4px solid #0073aa;">
                        <h3 style="margin-top: 0;"><?php esc_html_e( 'Pages to be created:', 'liontrust-locations' ); ?></h3>
                        <div id="lt-preview-list">
                            <p class="description"><?php esc_html_e( 'Enter a location name to see preview.', 'liontrust-locations' ); ?></p>
                        </div>
                    </div>

                    <?php submit_button( __( 'Create Location Set', 'liontrust-locations' ), 'primary', 'submit', true ); ?>
                </form>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var $nameInput = $('#location_name');
            var $preview = $('#lt-preview-list');
            var $checkboxes = $('input[name="property_types[]"]');

            function escapeHtml(text) {
                var div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function updatePreview() {
                var name = $nameInput.val().trim();

                if (!name) {
                    $preview.html('<p class="description"><?php esc_html_e( 'Enter a location name to see preview.', 'liontrust-locations' ); ?></p>');
                    return;
                }

                var safeName = escapeHtml(name);
                var html = '<ol style="margin: 0;">';
                html += '<li><strong>' + safeName + '</strong> (Parent)</li>';

                $checkboxes.filter(':checked').each(function() {
                    var typeName = escapeHtml($(this).parent().text().trim());
                    html += '<li>' + safeName + ' ' + typeName + '</li>';
                });

                html += '</ol>';
                $preview.html(html);
            }

            $nameInput.on('input', updatePreview);
            $checkboxes.on('change', updatePreview);

            updatePreview();
        });
        </script>
        <?php
    }

    /**
     * Handle bulk create form submission
     */
    public function handle_bulk_create() {
        // Verify nonce
        if ( ! isset( $_POST['lt_bulk_nonce'] ) || ! wp_verify_nonce( $_POST['lt_bulk_nonce'], 'lt_bulk_create_nonce' ) ) {
            wp_die( __( 'Security check failed.', 'liontrust-locations' ) );
        }

        // Check permissions
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( __( 'You do not have permission to perform this action.', 'liontrust-locations' ) );
        }

        // Get form data
        $location_name  = isset( $_POST['location_name'] ) ? sanitize_text_field( $_POST['location_name'] ) : '';
        $region         = isset( $_POST['region'] ) ? absint( $_POST['region'] ) : 0;
        $property_types = isset( $_POST['property_types'] ) ? array_map( 'absint', $_POST['property_types'] ) : array();
        $post_status    = isset( $_POST['post_status'] ) ? sanitize_text_field( $_POST['post_status'] ) : 'draft';

        // Validate and sanitize coordinates
        $latitude = '';
        if ( isset( $_POST['latitude'] ) && $_POST['latitude'] !== '' ) {
            $lat_val = floatval( $_POST['latitude'] );
            if ( $lat_val >= -90 && $lat_val <= 90 ) {
                $latitude = $lat_val;
            }
        }

        $longitude = '';
        if ( isset( $_POST['longitude'] ) && $_POST['longitude'] !== '' ) {
            $lon_val = floatval( $_POST['longitude'] );
            if ( $lon_val >= -180 && $lon_val <= 180 ) {
                $longitude = $lon_val;
            }
        }

        // Validate
        if ( empty( $location_name ) ) {
            wp_safe_redirect( add_query_arg( 'error', 'name_required', admin_url( 'edit.php?post_type=lt_location&page=lt-bulk-create' ) ) );
            exit;
        }

        $created_count = 0;

        // Create parent location
        $parent_id = wp_insert_post( array(
            'post_type'   => 'lt_location',
            'post_title'  => $location_name,
            'post_status' => $post_status,
            'post_name'   => sanitize_title( $location_name ),
        ) );

        if ( is_wp_error( $parent_id ) ) {
            wp_safe_redirect( add_query_arg( 'error', 'create_failed', admin_url( 'edit.php?post_type=lt_location&page=lt-bulk-create' ) ) );
            exit;
        }

        $created_count++;

        // Assign region to parent
        if ( $region > 0 ) {
            wp_set_object_terms( $parent_id, array( $region ), 'lt_region' );
        }

        // Save coordinates
        if ( $latitude !== '' ) {
            update_post_meta( $parent_id, '_lt_latitude', $latitude );
        }
        if ( $longitude !== '' ) {
            update_post_meta( $parent_id, '_lt_longitude', $longitude );
        }

        // Create child pages for each property type
        foreach ( $property_types as $type_id ) {
            $term = get_term( $type_id, 'lt_property_type' );

            if ( ! $term || is_wp_error( $term ) ) {
                continue;
            }

            $child_title = $location_name . ' ' . $term->name;

            $child_id = wp_insert_post( array(
                'post_type'   => 'lt_location',
                'post_title'  => $child_title,
                'post_status' => $post_status,
                'post_parent' => $parent_id,
                'post_name'   => sanitize_title( $term->slug ),
            ) );

            if ( ! is_wp_error( $child_id ) ) {
                $created_count++;

                // Assign property type
                wp_set_object_terms( $child_id, array( $type_id ), 'lt_property_type' );

                // Assign same region
                if ( $region > 0 ) {
                    wp_set_object_terms( $child_id, array( $region ), 'lt_region' );
                }
            }
        }

        // Redirect with success message
        wp_safe_redirect( add_query_arg(
            array(
                'created'  => $created_count,
                'location' => urlencode( $location_name ),
            ),
            admin_url( 'edit.php?post_type=lt_location&page=lt-bulk-create' )
        ) );
        exit;
    }
}
