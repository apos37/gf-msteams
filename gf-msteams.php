<?php
/**
 * Plugin Name:         Add-On for Microsoft Teams and Gravity Forms
 * Plugin URI:          https://apos37.com/wordpress-addon-for-ms-teams-gravity-forms/
 * Description:         Send Gravity Form entries to Microsoft Teams channel
 * Version:             1.1.2
 * Requires at least:   5.9.0
 * Tested up to:        6.6.2
 * Author:              Apos37
 * Author URI:          https://apos37.com/
 * Text Domain:         gf-msteams
 * License:             GPLv2 or later
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
define( 'MSTEAMS_VERSION', '1.1.2' );
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
 * Filter plugin action links
 */
add_filter( 'plugin_row_meta', 'msteams_plugin_row_meta' , 10, 2 );


/**
 * Add links to our website and Discord support
 *
 * @param array $links
 * @return array
 */
function msteams_plugin_row_meta( $links, $file ) {
    // Only apply to this plugin
    if ( MSTEAMS_TEXTDOMAIN.'/'.MSTEAMS_TEXTDOMAIN.'.php' == $file ) {

        // Add the link
        $row_meta = [
            'docs'    => '<a href="'.esc_url( 'https://apos37.com/wordpress-addon-for-ms-teams-gravity-forms/' ).'" target="_blank" aria-label="'.esc_attr__( 'Plugin Website Link', 'gf-msteams' ).'">'.esc_html__( 'Website', 'gf-msteams' ).'</a>',
            'discord' => '<a href="'.esc_url( 'https://discord.gg/3HnzNEJVnR' ).'" target="_blank" aria-label="'.esc_attr__( 'Plugin Support on Discord', 'gf-msteams' ).'">'.esc_html__( 'Discord Support', 'gf-msteams' ).'</a>'
        ];
        return array_merge( $links, $row_meta );
    }

    // Return the links
    return (array) $links;
} // End plugin_row_meta()


/**
 * Add string comparison function to earlier versions of PHP
 *
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
if ( version_compare( PHP_VERSION, 8.0, '<=' ) && !function_exists( 'str_starts_with' ) ) {
    function str_starts_with( $haystack, $needle ) {
        return strpos( $haystack , $needle ) === 0;
    } // End str_starts_with()
}
if ( version_compare( PHP_VERSION, 8.0, '<=' ) && !function_exists( 'str_ends_with' ) ) {
    function str_ends_with( $haystack, $needle ) {
        return $needle !== '' && substr( $haystack, -strlen( $needle ) ) === (string)$needle;
    } // End str_ends_with()
}


/**
 * Require notice
 */
require MSTEAMS_PLUGIN_ROOT . 'class-notice.php';