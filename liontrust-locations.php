<?php
/**
 * Plugin Name: Lion Trust Locations
 * Plugin URI: https://liontrust.com
 * Description: Location-based real estate pages with hierarchical parent-child structure, AJAX search, and SEO interlinking.
 * Version: 1.0.1
 * Author: Lion Trust
 * Author URI: https://liontrust.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: liontrust-locations
 * Domain Path: /languages
 *
 * @package LionTrust_Locations
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'LT_LOCATIONS_VERSION', '1.0.1' );
define( 'LT_LOCATIONS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LT_LOCATIONS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LT_LOCATIONS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoloader for plugin classes
 */
spl_autoload_register( function( $class ) {
    $prefix = 'LT_';

    if ( strpos( $class, $prefix ) !== 0 ) {
        return;
    }

    $class_map = array(
        'LT_Loader'         => 'includes/class-lt-loader.php',
        'LT_Activator'      => 'includes/class-lt-activator.php',
        'LT_Location_CPT'   => 'includes/cpt/class-lt-location-cpt.php',
        'LT_Taxonomies'     => 'includes/cpt/class-lt-taxonomies.php',
        'LT_Meta_Fields'    => 'includes/meta/class-lt-meta-fields.php',
        'LT_Rest_API'       => 'includes/api/class-lt-rest-api.php',
        'LT_Nearby'         => 'includes/query/class-lt-nearby.php',
        'LT_Schema'         => 'includes/seo/class-lt-schema.php',
        'LT_Admin'          => 'admin/class-lt-admin.php',
        'LT_Bulk_Creator'   => 'admin/class-lt-bulk-creator.php',
        'LT_Public'         => 'public/class-lt-public.php',
        'LT_Shortcodes'     => 'shortcodes/class-lt-shortcodes.php',
    );

    if ( isset( $class_map[ $class ] ) ) {
        require_once LT_LOCATIONS_PLUGIN_DIR . $class_map[ $class ];
    }
});

/**
 * Load helper functions
 */
require_once LT_LOCATIONS_PLUGIN_DIR . 'includes/helpers/functions.php';

/**
 * Activation hook
 */
function lt_locations_activate() {
    require_once LT_LOCATIONS_PLUGIN_DIR . 'includes/class-lt-activator.php';
    LT_Activator::activate();
}
register_activation_hook( __FILE__, 'lt_locations_activate' );

/**
 * Initialize the plugin
 */
function lt_locations_init() {
    // Load text domain
    load_plugin_textdomain( 'liontrust-locations', false, dirname( LT_LOCATIONS_PLUGIN_BASENAME ) . '/languages' );

    // Initialize loader
    $loader = new LT_Loader();

    // Register CPT and Taxonomies (with sitemap support)
    $cpt = new LT_Location_CPT();
    $loader->add_action( 'init', $cpt, 'init' );

    $taxonomies = new LT_Taxonomies();
    $loader->add_action( 'init', $taxonomies, 'register' );

    // Meta fields
    $meta = new LT_Meta_Fields();
    $loader->add_action( 'add_meta_boxes', $meta, 'add_meta_boxes' );
    $loader->add_action( 'save_post_lt_location', $meta, 'save_meta', 10, 2 );

    // REST API
    $api = new LT_Rest_API();
    $loader->add_action( 'rest_api_init', $api, 'register_routes' );

    // Schema
    $schema = new LT_Schema();
    $loader->add_action( 'wp_head', $schema, 'output_schema' );

    // Admin
    if ( is_admin() ) {
        $admin = new LT_Admin();
        $loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles' );
        $loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_scripts' );
        $loader->add_filter( 'manage_lt_location_posts_columns', $admin, 'custom_columns' );
        $loader->add_action( 'manage_lt_location_posts_custom_column', $admin, 'custom_column_content', 10, 2 );

        $bulk_creator = new LT_Bulk_Creator();
        $loader->add_action( 'admin_menu', $bulk_creator, 'add_menu_page' );
        $loader->add_action( 'admin_post_lt_bulk_create', $bulk_creator, 'handle_bulk_create' );
    }

    // Public
    $public = new LT_Public();
    $loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_styles' );
    $loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_scripts' );
    $loader->add_filter( 'template_include', $public, 'template_loader' );

    // Shortcodes
    $shortcodes = new LT_Shortcodes();
    $loader->add_action( 'init', $shortcodes, 'register' );

    // Run all hooks
    $loader->run();
}
add_action( 'plugins_loaded', 'lt_locations_init' );
