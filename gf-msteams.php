<?php
/**
 * Plugin Name:         Add-On for Microsoft Teams and Gravity Forms
 * Plugin URI:          https://github.com/apos37/gf-msteams
 * Description:         Send Gravity Form entries to Microsoft Teams channel
 * Version:             1.3.0
 * Requires at least:   5.9
 * Tested up to:        6.8
 * Author:              PluginRx
 * Author URI:          https://pluginrx.com/
 * Discord URI:         https://discord.gg/3HnzNEJVnR
 * Text Domain:         gf-msteams
 * License:             GPLv2 or later
 * License URI:         http://www.gnu.org/licenses/gpl-2.0.txt
 * Created on:          March 19, 2025
 */


/**
 * Exit if accessed directly.
 */
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Defines
 */
$plugin_data = get_file_data( __FILE__, [
    'name'         => 'Plugin Name',
    'version'      => 'Version',
    'textdomain'   => 'Text Domain',
    'author_uri'   => 'Author URI',
    'discord_uri'  => 'Discord URI',
] );


/**
 * Defines
 */
define( 'MSTEAMS_NAME', $plugin_data[ 'name' ] );
define( 'MSTEAMS_TEXTDOMAIN', $plugin_data[ 'textdomain' ] );
define( 'MSTEAMS_VERSION', $plugin_data[ 'version' ] );
define( 'MSTEAMS_PLUGIN_ROOT', plugin_dir_path( __FILE__ ) );                                                   // /home/.../public_html/wp-content/plugins/gf-msteams/
define( 'MSTEAMS_PLUGIN_DIR', plugins_url( '/'.MSTEAMS_TEXTDOMAIN.'/' ) );                                      // https://domain.com/wp-content/plugins/gf-msteams/
define( 'MSTEAMS_SETTINGS_URL', admin_url( 'admin.php?page=gf_settings&subview='.MSTEAMS_TEXTDOMAIN ) );        // https://domain.com/wp-admin/admin.php?page=gf_settings&subview=gf-msteams/
define( 'MSTEAMS_AUTHOR_URL', $plugin_data[ 'author_uri' ] );
define( 'MSTEAMS_GUIDE_URL', MSTEAMS_AUTHOR_URL . 'guide/plugin/' . MSTEAMS_TEXTDOMAIN . '/' );
define( 'MSTEAMS_DOCS_URL', MSTEAMS_AUTHOR_URL . 'docs/plugin/' . MSTEAMS_TEXTDOMAIN . '/' );
define( 'MSTEAMS_SUPPORT_URL', MSTEAMS_AUTHOR_URL . 'support/plugin/' . MSTEAMS_TEXTDOMAIN . '/' );
define( 'MSTEAMS_DISCORD_URL', $plugin_data[ 'discord_uri' ] );


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
    $text_domain = MSTEAMS_TEXTDOMAIN;
    if ( $text_domain . '/' . $text_domain . '.php' == $file ) {

        $guide_url = MSTEAMS_GUIDE_URL;
        $docs_url = MSTEAMS_DOCS_URL;
        $support_url = MSTEAMS_SUPPORT_URL;
        $plugin_name = MSTEAMS_NAME;

        $our_links = [
            'guide' => [
                // translators: Link label for the plugin's user-facing guide.
                'label' => __( 'How-To Guide', 'gf-msteams' ),
                'url'   => $guide_url
            ],
            'docs' => [
                // translators: Link label for the plugin's developer documentation.
                'label' => __( 'Developer Docs', 'gf-msteams' ),
                'url'   => $docs_url
            ],
            'support' => [
                // translators: Link label for the plugin's support page.
                'label' => __( 'Support', 'gf-msteams' ),
                'url'   => $support_url
            ],
        ];

        $row_meta = [];
        foreach ( $our_links as $key => $link ) {
            // translators: %1$s is the link label, %2$s is the plugin name.
            $aria_label = sprintf( __( '%1$s for %2$s', 'gf-msteams' ), $link[ 'label' ], $plugin_name );
            $row_meta[ $key ] = '<a href="' . esc_url( $link[ 'url' ] ) . '" target="_blank" aria-label="' . esc_attr( $aria_label ) . '">' . esc_html( $link[ 'label' ] ) . '</a>';
        }

        // Require Gravity Forms Notice
        if ( ! is_plugin_active( 'gravityforms/gravityforms.php' ) ) {
            echo '<div class="gravity-forms-required-notice" style="margin: 5px 0 15px; border-left-color: #d63638 !important; background: #FCF9E8; border: 1px solid #c3c4c7; border-left-width: 4px; box-shadow: 0 1px 1px rgba(0, 0, 0, .04); padding: 10px 12px;">';
            /* translators: 1: Plugin name, 2: Gravity Forms link */
            printf( __( 'This plugin requires the %s plugin to be activated!', 'gf-msteams' ),
                '<a href="https://www.gravityforms.com/" target="_blank">Gravity Forms</a>'
            );
            echo '</div>';
        }
        
        // Merge the links
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