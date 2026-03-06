<?php
/**
 * Archive Template for Locations
 *
 * This template can be overridden by copying it to:
 * yourtheme/liontrust-locations/archive-lt_location.php
 *
 * @package LionTrust_Locations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main id="main" class="site-main">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 40px 20px;">

        <header class="lt-archive-header">
            <h1 class="lt-archive-title">
                <?php
                echo esc_html( apply_filters( 'lt_archive_title', __( 'Popular Locations', 'liontrust-locations' ) ) );
                ?>
            </h1>
            <?php
            $archive_description = apply_filters( 'lt_archive_description', '' );
            if ( $archive_description ) :
            ?>
                <p class="lt-archive-description">
                    <?php echo esc_html( $archive_description ); ?>
                </p>
            <?php endif; ?>
        </header>

        <?php
        // Display the search shortcode
        echo do_shortcode( '[lt_search per_page="12" columns="3"]' );
        ?>

    </div>
</main>

<?php
get_footer();
