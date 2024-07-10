<?php
/**
 * My plugin notice
 * // TODO: REMOVE THIS AFTER ENOUGH TIME HAS PASSED
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Initiate the class
 */
new GF_MSTEAMS_NOTICE;


/**
 * Main plugin class.
 */
class GF_MSTEAMS_NOTICE {

    /**
	 * Constructor
	 */
	public function __construct() {

        // Add a temporary notice for those that have not moved to new method using workflows
		add_action( 'admin_notices', [ $this, 'plugin_notice' ] );

	} // End __construct()


    /**
	 * Display a notice at the top of the plugins page
	 *
	 * @return void
	 */
	public function plugin_notice() {
		// Check if we are on the plugins page
		$screen = get_current_screen();
		if ( $screen->id != 'plugins' ) {
			return;
		}

		// Only if workflow is not enabled
		if ( class_exists( 'GF_MicrosoftTeams' ) ) {
			$gf_microsoft_teams = GF_MicrosoftTeams::get_instance();
			$workflow_setting = $gf_microsoft_teams->get_plugin_setting( 'workflow' );
		} else {
			$workflow_setting = false;
		}
		if ( !filter_var( $workflow_setting, FILTER_VALIDATE_BOOLEAN ) ) {

			// Display the notice
			echo '<div class="notice notice-warning is-dismissible gf-msteams-plugin-notice">
				<p><strong>Add-On for Microsoft Teams and Gravity Forms:</strong> <span style="font-weight: bold; color: red;">** IMPORTANT UPDATE **</span> If you installed the plugin prior to v1.1.0, you would have had to set up an Incoming Webhook app on MS Teams. If you still have it set up this way, you might be seeing a message from Microsoft at the bottom of your channel messages that says "<code>Action Required: O365 connectors... will be deprecated...</code>" <strong>Prior to August 15th, 2024</strong>, you will need to remove the Incoming Webhook app on Teams and use a Workflow instead. <a href="'.esc_url( MSTEAMS_SETTINGS_URL ).'">See Instructions under plugin settings.</a></p>
			</div>';
		}
	} // End plugin_notice()
}