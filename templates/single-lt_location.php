<?php
/**
 * Single Template for Location
 *
 * This template can be overridden by copying it to:
 * yourtheme/liontrust-locations/single-lt_location.php
 *
 * Note: This is a minimal template designed to work with page builders.
 * For Divi/LiveCanvas/Elementor, the main content will come from the editor.
 * This template adds the interlinking and nearby sections.
 *
 * @package LionTrust_Locations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

while ( have_posts() ) :
    the_post();

    $post_id   = get_the_ID();
    $is_parent = lt_is_parent_location();
    $parent    = lt_get_parent_location();
    $region    = lt_get_region();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'lt-single-location' ); ?>>

    <?php
    // Breadcrumbs
    ?>
    <nav class="lt-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'liontrust-locations' ); ?>">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'liontrust-locations' ); ?></a>
        <span class="separator">/</span>
        <a href="<?php echo esc_url( get_post_type_archive_link( 'lt_location' ) ); ?>"><?php esc_html_e( 'Locations', 'liontrust-locations' ); ?></a>

        <?php if ( ! $is_parent && $parent ) : ?>
            <span class="separator">/</span>
            <a href="<?php echo esc_url( get_permalink( $parent->ID ) ); ?>"><?php echo esc_html( $parent->post_title ); ?></a>
        <?php endif; ?>

        <span class="separator">/</span>
        <span class="current"><?php the_title(); ?></span>
    </nav>

    <?php
    // Featured Image (optional - page builders usually handle this)
    if ( has_post_thumbnail() && apply_filters( 'lt_show_featured_image', false ) ) :
    ?>
        <div class="lt-featured-image">
            <?php the_post_thumbnail( 'large' ); ?>
        </div>
    <?php endif; ?>

    <header class="lt-entry-header">
        <h1 class="lt-entry-title"><?php the_title(); ?></h1>

        <?php if ( $region ) : ?>
            <p class="lt-location-region">
                <a href="<?php echo esc_url( get_term_link( $region ) ); ?>">
                    <?php echo esc_html( $region->name ); ?>
                </a>
            </p>
        <?php endif; ?>
    </header>

    <div class="lt-entry-content">
        <?php
        /**
         * Hook: lt_before_location_content
         */
        do_action( 'lt_before_location_content', $post_id );

        // The main content (from editor/page builder)
        the_content();

        /**
         * Hook: lt_after_location_content
         */
        do_action( 'lt_after_location_content', $post_id );
        ?>
    </div>

    <footer class="lt-entry-footer">
        <?php
        /**
         * Interlinking Section
         * Links to all pages in the location set (parent + children)
         */
        echo do_shortcode( '[lt_interlinking]' );

        /**
         * Nearby Locations Section
         * 3 rows x 4 columns = 12 nearby locations
         */
        echo do_shortcode( '[lt_nearby_locations count="12" columns="4"]' );

        /**
         * Hook: lt_after_location_sections
         */
        do_action( 'lt_after_location_sections', $post_id );
        ?>
    </footer>

</article>

<?php
endwhile;

get_footer();
