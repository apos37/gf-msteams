<?php
/**
 * Plugin Name:         Add-On for Microsoft Teams and Gravity Forms
 * Plugin URI:          https://github.com/apos37/gf-msteams
 * Description:         Send Gravity Form entries to Microsoft Teams channel
 * Version:             1.0.4
 * Requires at least:   5.9.0
 * Tested up to:        6.1.1
 * Author:              Apos37
 * Author URI:          https://apos37.com/
 * Text Domain:         gf-msteams
 * License:             GPL v2 or later
 * License URI:         http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Defines
 */
define( 'MSTEAMS_NAME', 'Add-On for Microsoft Teams and Gravity Forms' );
define( 'MSTEAMS_TEXTDOMAIN', 'gf-msteams' );
define( 'MSTEAMS_VERSION', '1.0.4' );
define( 'MSTEAMS_PLUGIN_ROOT', plugin_dir_path( __FILE__ ) );                                                   // /home/.../public_html/wp-content/plugins/gf-msteams/
define( 'MSTEAMS_PLUGIN_DIR', plugins_url( '/'.MSTEAMS_TEXTDOMAIN.'/' ) );                                      // https://domain.com/wp-content/plugins/gf-msteams/
define( 'MSTEAMS_SETTINGS_URL', admin_url( 'admin.php?page=gf_settings&subview='.MSTEAMS_TEXTDOMAIN ) );        // https://domain.com/wp-admin/admin.php?page=gf_settings&subview=gf-msteams/


/**
 * Load the Bootstrap
 */
add_action( 'gform_loaded', [ 'GF_MicrosoftTeams_Bootstrap', 'load' ], 5 );


/**
 * GF_MicrosoftTeams_Bootstrap Class
 */
class GF_MicrosoftTeams_Bootstrap {

    // Load
    public static function load() {
        // print_r( 'load bootstrap bak' );

        // Make sure the framework exists
        if ( !method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
            return;
        }

        // Load main plugin class.
        require_once 'class-gf-msteams.php';

        // Register the addon
        GFAddOn::register( 'GF_MicrosoftTeams' );
    }
}


/**
 * Add string comparison function to earlier versions of PHP
 *
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
if ( version_compare( PHP_VERSION, 8.0, '<=' ) && !function_exists( 'str_starts_with' ) ) {
    function str_starts_with ( $haystack, $needle ) {
        return strpos( $haystack , $needle ) === 0;
    }
}
if ( version_compare( PHP_VERSION, 8.0, '<=' ) && !function_exists( 'str_ends_with' ) ) {
    function str_ends_with( $haystack, $needle ) {
        return $needle !== '' && substr( $haystack, -strlen( $needle ) ) === (string)$needle;
    }
}