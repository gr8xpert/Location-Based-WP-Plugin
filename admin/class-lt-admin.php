<?php
/**
 * Admin Functionality
 *
 * @package LionTrust_Locations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LT_Admin {

    /**
     * Enqueue admin styles
     *
     * @param string $hook Current admin page.
     */
    public function enqueue_styles( $hook ) {
        $screen = get_current_screen();

        if ( ! $screen || $screen->post_type !== 'lt_location' ) {
            return;
        }

        wp_enqueue_style(
            'lt-admin',
            LT_LOCATIONS_PLUGIN_URL . 'admin/css/lt-admin.css',
            array(),
            LT_LOCATIONS_VERSION
        );
    }

    /**
     * Enqueue admin scripts
     *
     * @param string $hook Current admin page.
     */
    public function enqueue_scripts( $hook ) {
        $screen = get_current_screen();

        if ( ! $screen || $screen->post_type !== 'lt_location' ) {
            return;
        }

        wp_enqueue_script(
            'lt-admin',
            LT_LOCATIONS_PLUGIN_URL . 'admin/js/lt-admin.js',
            array( 'jquery' ),
            LT_LOCATIONS_VERSION,
            true
        );

        wp_localize_script( 'lt-admin', 'ltAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'lt_admin_nonce' ),
            'i18n'    => array(
                'selectLocations' => __( 'Select locations', 'liontrust-locations' ),
                'noLocations'     => __( 'No locations found', 'liontrust-locations' ),
            ),
        ) );
    }

    /**
     * Add custom columns to locations list
     *
     * @param array $columns Existing columns.
     * @return array Modified columns.
     */
    public function custom_columns( $columns ) {
        $new_columns = array();

        foreach ( $columns as $key => $label ) {
            // Add Featured Image column before title
            if ( $key === 'title' ) {
                $new_columns['lt_featured_image'] = __( 'Image', 'liontrust-locations' );
            }

            $new_columns[ $key ] = $label;

            // Add Parent column after title
            if ( $key === 'title' ) {
                $new_columns['lt_parent'] = __( 'Parent', 'liontrust-locations' );
            }
        }

        return $new_columns;
    }

    /**
     * Render custom column content
     *
     * @param string $column  Column name.
     * @param int    $post_id Post ID.
     */
    public function custom_column_content( $column, $post_id ) {
        switch ( $column ) {
            case 'lt_featured_image':
                if ( has_post_thumbnail( $post_id ) ) {
                    $thumb = get_the_post_thumbnail( $post_id, array( 50, 50 ), array(
                        'style' => 'width:50px;height:50px;object-fit:cover;border-radius:4px;',
                    ) );
                    echo '<a href="' . esc_url( get_edit_post_link( $post_id ) ) . '">' . $thumb . '</a>';
                } else {
                    echo '<span class="dashicons dashicons-format-image" style="color:#ccc;font-size:30px;width:50px;height:50px;line-height:50px;text-align:center;"></span>';
                }
                break;

            case 'lt_parent':
                $post = get_post( $post_id );
                if ( $post->post_parent !== 0 ) {
                    $parent = get_post( $post->post_parent );
                    if ( $parent ) {
                        echo '<a href="' . esc_url( get_edit_post_link( $parent->ID ) ) . '">';
                        echo esc_html( $parent->post_title );
                        echo '</a>';
                    }
                } else {
                    echo '<span class="lt-badge lt-badge-parent">' . esc_html__( 'Parent', 'liontrust-locations' ) . '</span>';
                }
                break;
        }
    }
}
