<?php
/**
 * Gravity Forms Feed
 */

// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Include the addon framework
 */
GFForms::include_feed_addon_framework();


/**
 * The class.
 */
class GF_MicrosoftTeams extends GFFeedAddOn {

	/**
	 * Plugin Version
	 *
	 * @var string $_version
	 */
	protected $_version = MSTEAMS_VERSION;


	/**
	 * Minimum required version of Gravity Forms
	 *
	 * @var string $_min_gravityforms_version
	 */
	protected $_min_gravityforms_version = '2.2';


	/**
	 * Plugin Slug
	 *
	 * @var string $_slug
	 */
	protected $_slug = MSTEAMS_TEXTDOMAIN;


	/**
	 * Plugin Path
	 *
	 * @var string $_path
	 */
	protected $_path = MSTEAMS_TEXTDOMAIN.'/'.MSTEAMS_TEXTDOMAIN.'.php';


	/**
	 * Plugin Full Path
	 *
	 * @var [type]
	 */
	protected $_full_path = __FILE__;


	/**
	 * Title of Add-On
	 *
	 * @var string $_title
	 */
	protected $_title = 'Gravity Forms/Microsoft Teams Integration';


	/**
	 * Short Title of Add-On
	 *
	 * @var string $_short_title
	 */
	protected $_short_title = 'Microsoft Teams';


	/**
	 * Nonce
	 *
	 * @var string $nonce
	 */
	private $nonce = 'msteams_nonce';


	/**
	 * Default accent color
	 *
	 * @var string
	 */
	public $default_accent_color = '#FF0000';

	
	/**
	 * Core singleton class
	 *
	 * @var self - pattern realization
	 */
	private static $_instance;


	/**
	 * Get an instance of this class.
	 *
	 * @return GF_MicrosoftTeams
	 */
	public static function get_instance() {
		if ( null === self::$_instance ) {
			self::$_instance = new GF_MicrosoftTeams();
		}

		return self::$_instance;
	} // End get_instance()


	/**
	 * Handles hooks
	 */
	public function init() {
		parent::init();

		// Add plugin settings
		$plugin_settings = GFCache::get( 'msteams_plugin_settings' );
		if ( empty( $plugin_settings ) ) {
			$plugin_settings = $this->get_plugin_settings();
			GFCache::set( 'msteams_plugin_settings', $plugin_settings );
		}

		// Add a meta box to the entries
        add_filter( 'gform_entry_detail_meta_boxes', [ $this, 'entry_meta_box' ], 10, 3 );
	} // End init()


	/**
	 * Enqueue needed scripts.
	 *
	 * @return array
	 */
	public function scripts() {
		$scripts = [
			[
				'handle'    => 'gf_msteams_media_uploader',
				'src'       => MSTEAMS_PLUGIN_DIR.'media-uploader.js',
				'version'   => $this->_version,
				'deps'      => [ 'jquery' ],
				'callback'  => 'wp_enqueue_media',
				'in_footer' => true,
				'enqueue'   => [
					[
						// 'admin_page' => [ 'plugin_page' ],
						// 'tab'        => 'gf-msteams',
						'query' => 'page=gf_settings&subview='.MSTEAMS_TEXTDOMAIN
					],
				],
			],
		];
		return array_merge( parent::scripts(), $scripts );
	} // End scripts()


	/**
     * Add entry meta box
     *
     * @param array $meta_boxes
     * @param array $entry
     * @param array $form
     * @return array
     */
    public function entry_meta_box( $meta_boxes, $entry, $form ) {
        // Link to Debug Form and Entry
        if ( !isset( $meta_boxes[ 'msteams' ] ) ) {
            $meta_boxes[ 'msteams' ] = [
                'title'         => esc_html__( 'Microsoft Teams', 'gf-msteams' ),
                'callback'      => [ $this, 'entry_meta_box_content' ],
                'context'       => 'side',
                'callback_args' => [ $entry, $form ],
            ];
        }
     
        // Return the boxes
        return $meta_boxes;
    } // End entry_meta_box()


    /**
     * The content of the meta box
     *
     * @param array $args
     * @return void
     */
    public function entry_meta_box_content( $args ) {
        // Get the form and entry
        $form  = $args[ 'form' ];
        $entry = $args[ 'entry' ];

		// Get the feeds
		$feeds = GFAPI::get_feeds( null, $form[ 'id' ], MSTEAMS_TEXTDOMAIN );

		// Start the container
		$results = '<div>';

		// Check for a wp error
		if ( !is_wp_error( $feeds ) ) {

			// If there are feeds
			if ( !empty( $feeds ) ) {

				// Send the form entry if query string says so
				if ( isset( $_GET[ '_wpnonce' ] ) && wp_verify_nonce( $_GET[ '_wpnonce' ], $this->nonce ) &&
					isset( $_GET[ 'msteams' ] ) && sanitize_text_field( $_GET[ 'msteams' ] )  == 'true' &&
					isset( $_GET[ 'feed_id' ] ) && absint( $_GET[ 'feed_id' ] ) != '' ) {

					// The feed id
					$feed_id = absint( $_GET[ 'feed_id' ] );

					// Get the feed
					$feed = GFAPI::get_feed( $feed_id );

					// Process the feed
					$this->process_feed( $feed, $entry, $form );

					// Remove the query strings
					$this->redirect_without_qs( [ 'msteams', 'feed_id' ] );
				}

				// Multiple feeds?
				if ( count( $feeds ) > 1 ) {
					$br = '<br><br>';
				} else {
					$br = '';
				}

				// Iter the feeds
				foreach ( $feeds as $feed ) {

					// The feed title
					$results .= '<strong><a href="'.$this->feed_settings_url( $form[ 'id' ], $feed[ 'id' ] ).'">'.$feed[ 'meta' ][ 'feedName' ].'</a>:</strong><br><br>';

					// The current url
					$current_url = '/wp-admin/admin.php?page=gf_entries&view=entry&id='.$form[ 'id' ].'&lid='.$entry[ 'id' ];

					// Resend button
					$resent_url = wp_nonce_url( $current_url.'&feed_id='.$feed[ 'id' ].'&msteams=true', $this->nonce );
					$results .= '<a class="button" href="'.$resent_url.'">Resend</a>';

					// Space between
					$results .= $br;
				}

			} else {

				// The feed url
				$feed_url = '/wp-admin/admin.php?page=gf_edit_forms&view=settings&subview='.MSTEAMS_TEXTDOMAIN.'&id='.$form[ 'id' ];

				// Resend button
				$results .= '<a class="button" href="'.$feed_url.'">Add New Feed</a>';
				
			}

		// If there is an error
		} else {
			
			// The feed url
			$feed_url = '/wp-admin/admin.php?page=gf_edit_forms&view=settings&subview='.MSTEAMS_TEXTDOMAIN.'&id='.$form[ 'id' ];

			// Resend button
			$results .= '<a class="button" href="'.$feed_url.'">Add New Feed</a>';
		}

		// Start the container
		$results .= '</div>';
    
        // Return everything
        echo wp_kses_post( $results );
    } // End entry_meta_box_content()


	/**
	 * Form settings icon
	 *
	 * @return string
	 */
	public function get_menu_icon() {
		global $wp_filesystem;
		if ( !function_exists( 'request_filesystem_credentials' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}
		if ( !WP_Filesystem() ) {
			return '';
		}

		$file_path = $this->get_base_path().'/img/msteams-icon.svg';

		// Ensure the file exists and is readable
		if ( $wp_filesystem->exists( $file_path ) && $wp_filesystem->is_readable( $file_path ) ) {
			$icon = $wp_filesystem->get_contents( $file_path );
	
			// Escape the SVG output
			return $icon;
		} else {
			return '';
		}
	} // End get_menu_icon()


	/**
	 * Note avatar
	 *
	 * @return string
	 */
	public function note_avatar() {
		return MSTEAMS_PLUGIN_DIR.'img/msteams-logo.png';
	} // End note_avatar()


	/**
	 * Remove unneeded settings.
	 */
	public function uninstall() {
		parent::uninstall();
		GFCache::delete( 'msteams_plugin_settings' );
	} // End uninstall()


	/**
	 * Prevent feeds being listed or created if an api key isn't valid.
	 *
	 * @return bool
	 */
	public function can_create_feed() {
        return true;
	} // End can_create_feed()


	/**
	 * Configures which columns should be displayed on the feed list page.
	 *
	 * @return array
	 */
	public function feed_list_columns() {
		return [
			'feedName' => esc_html__( 'Feed Name', 'gf-msteams' ),
			'channel'  => esc_html__( 'Channel', 'gf-msteams' ),
        ];
	} // End feed_list_columns()


	/**
	 * Get the feed settings url
	 *
	 * @param int $form_id
	 * @param int $feed_id
	 * @return string
	 */
	public function feed_settings_url( $form_id, $feed_id ) {
		return admin_url( 'admin.php?page=gf_edit_forms&view=settings&subview='.MSTEAMS_TEXTDOMAIN.'&id='.$form_id.'&fid='.$feed_id );
	} // End feed_settings_url()


	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {
		return [
			[
				'title'       => esc_html__( 'Microsoft Teams Integration Settings', 'gf-msteams' ),
				'description' => '<p>'.esc_html__( 'Send form entries to a Microsoft Teams channel.', 'gf-msteams' ).'</p>',
				'fields'      => [
					[
						'name'              => 'site_name',
						'tooltip'           => esc_html__( 'The site name displayed on the messages. Limited to 50 characters.', 'gf-msteams' ),
						'label'             => esc_html__( 'Site Name', 'gf-msteams' ),
						'type'              => 'text',
						'class'             => 'medium',
						'default_value' 	=> get_bloginfo( 'name' ),
						'feedback_callback' => [ $this, 'is_valid_setting' ],
                    ],
					[
						'name'              => 'site_logo',
						'tooltip'           => esc_html__( 'Upload a logo to be used on the messages. For best results, use a small image with the same width and height around 100x100px.', 'gf-msteams' ),
						'label'             => esc_html__( 'Site Logo', 'gf-msteams' ),
						'type'              => 'text',
						'class'             => 'medium',
						'default_value' 	=> MSTEAMS_PLUGIN_DIR.'img/wordpress-logo.png',
						'feedback_callback' => [ $this, 'validate_image' ],
                    ],
					[
						'name'              => 'upload_image_button',
						'type'              => 'media_upload',
						'class'             => 'medium',
						'args'  => [
                            'button' => [
                                // 'label'   => esc_html__( '', 'gf-msteams' ),
                                'name'    => 'upload_image_button',
								'class'   => 'button',
								'value'   => 'Upload Image'
							],
						],
                    ],
					[
						'name'              => 'msteams_preview',
						'type'              => 'msteams_preview',
						'class'             => 'medium',
                    ],
                ],
            ],
			[
				'title'       => esc_html__( 'My Feeds', 'gf-msteams' ),
				'fields'      => [
					[
						'name'              => 'my_feeds',
						'type'              => 'my_feeds',
						'class'             => 'medium',
                    ],
                ],
            ],
			[
				'title'       => esc_html__( 'Instructions', 'gf-msteams' ),
				'description' => '',
				'fields'      => [
					[
						'name'              => 'instructions',
						'type'              => 'instructions',
						'class'             => 'medium',
                    ],
                ],
            ],
        ];
	} // End form_settings_fields()


	/**
	 * Media Uploader Button
	 *
	 * @param array $field
	 * @param boolean $echo
	 * @return void
	 */
	public function settings_media_upload( $field, $echo = true ) {
        $button = $field[ 'args' ][ 'button' ];
		printf(
			'<input type="button" id="%s" class="%s" value="%s"/>',
			esc_attr( $button[ 'name' ] ),
			esc_attr( $button[ 'class' ] ),
			esc_attr( $button[ 'value' ] )
		);
    } // End settings_media_upload()


	/**
	 * Site logo preview
	 *
	 * @param array $field
	 * @param boolean $echo
	 * @return void
	 */
	public function settings_msteams_preview( $field, $echo = true ) {
		// Get the site name
		$get_site_name = sanitize_text_field( $this->get_plugin_setting( 'site_name' ) );
        if ( $get_site_name && $get_site_name != '' ) {
            $site_name = $get_site_name;
        } else {
            $site_name = get_bloginfo( 'name' ); // Blog Name
        }

		// Get the current logo
		$get_site_logo = esc_url_raw( $this->get_plugin_setting( 'site_logo' ) );
		if ( $get_site_logo && $get_site_logo != '' ) {
            $url = $get_site_logo;
        } else {
            $url = MSTEAMS_PLUGIN_DIR.'img/wordpress-logo.png'; // WP Logo
        }

		// Add some css
		echo '</pre>
		<style>
		#msteams-preview { margin-top: 30px; }
		#site-logo-preview {
			background: center / contain no-repeat url('.esc_url_raw( $url ).'); 
			width: 5.2rem; 
			height: 5.2rem; 
			border-radius: 50%; 
			display: inline-block;
		}
		#site-info-preview { display: inline-block; margin: 5px 0 0 10px; vertical-align: top; }
		#site-name-preview { 
			display: block;
			font-size: 1.4rem;
			font-weight: 600;
		}
		#site-url-preview { display: block; font-size: 1rem; color: #7074D0; }
		#mode-preview { float: right; }
		#mode-preview a:active, #mode-preview a:focus, #mode-preview a:hover { color: #7074D0; }
		.gform-settings-panel__content,
		.gform-settings-panel__content label,
		.gform-settings-panel__content input {
			transition: all 1s ease;
		}
		</style>';

		// Display it
        echo '<div id="msteams-preview">
			<div id="site-logo-preview"></div>
			<div id="site-info-preview">
				<span id="site-name-preview">'.esc_html( $site_name ).'</span><br>
				<span id="site-url-preview">'.esc_url( home_url() ).'</span>
			</div>
			<div id="mode-preview"><a href="#" onclick="msteamsLightMode(); return false;">Light</a> | <a href="#" onclick="msteamsDarkMode(); return false;">Dark</a></div>
		</div>';

		// Add JS to update immediately
		echo '<script>
		// Switch to light mode
		function msteamsLightMode() {
			let panel = document.querySelector( ".gform-settings-panel__content" );
			panel.style.background = "transparent";
			panel.style.color = "#1d2327";

			let panelLabels = document.querySelectorAll( ".gform-settings-panel__content label" );
			panelLabels.forEach( label => {
				label.style.color = "#1d2327";
			} );

			let panelInputs = document.querySelectorAll( ".gform-settings-panel__content input" );
			panelInputs.forEach( input => {
				input.style.backgroundColor = "revert";
				input.style.color = "revert";
			} );
		}

		// Switch to dark mode
		function msteamsDarkMode() {
			let panel = document.querySelector( ".gform-settings-panel__content" );
			panel.style.background = "#2E2E2E";
			panel.style.color = "white";

			let panelLabels = document.querySelectorAll( ".gform-settings-panel__content .gform-settings-label" );
			panelLabels.forEach( label => {
				label.style.color = "white";
			} );

			let panelInputs = document.querySelectorAll( ".gform-settings-panel__content input" );
			panelInputs.forEach( input => {
				input.style.backgroundColor = "#292929";
				input.style.color = "white";
			} );
		}

		// Update the site name in real time
		updateSiteName();
		function updateSiteName() {
			// Get the site logo value
			let nameField = document.getElementById( "site_name" );
			let preview = document.getElementById( "site-name-preview" );

			// The max limit
			let maxChars = 50;
			
			// Limit character count on FeedName
			nameField.addEventListener( "keydown", ( e ) => {
				if ( nameField.value.length > maxChars ) {
					nameField.value = nameField.value.substr( 0, maxChars );
				}
			} );

			// Listen for change
			nameField.addEventListener( "keyup", ( event ) => {
				if ( nameField.value.length > maxChars ) {
					nameField.value = nameField.value.substr( 0, maxChars );
				}

				let name = nameField.value;
				if ( name == "" ) {
					name = "'.esc_html( get_bloginfo( 'name' ) ).'";
				}
				preview.innerHTML = name;
			} );
		}

		// Update the logo in real time
		updateSiteLogoPreview();
		function updateSiteLogoPreview() {
			// Get the site logo value
			let logoField = document.getElementById( "site_logo" );
			let preview = document.getElementById( "site-logo-preview" );

			// Listen for change
			logoField.addEventListener( "keyup", ( event ) => {
				let url = logoField.value;
				if ( url == "" ) {
					url = "'.esc_attr( MSTEAMS_PLUGIN_DIR ).'img/wordpress-logo.png";
				}
				preview.style.background = "center / contain no-repeat url(" + url + ")";
			} );
		}
		</script>
		<pre>';
    } // End settings_msteams_preview()


	/**
	 * Your Feeds field
	 *
	 * @param array $field
	 * @param boolean $echo
	 * @return void
	 */
	public function settings_my_feeds( $field, $echo = true ) {
		// Start the container
		echo '</pre>
		<div>';

		// Get all the feeds
		$feeds = $this->get_feeds( null, null, null, false );

		// Make sure we have forms
		if ( !empty( $feeds ) ) {

			// Start the table
			echo '<table class="wp-list-table widefat fixed striped table-view-list feeds">
				<thead>
					<tr>
						<th scope="col" id="feedName" class="manage-column column-feedName column-primary">Feed Name</th>
						<th scope="col" id="feedStatus" class="manage-column column-feedStatus">Feed Status</th>
						<th scope="col" id="channel" class="manage-column column-channel">Channel</th>	
						<th scope="col" id="form" class="manage-column column-form column-primary">Form</th>
						<th scope="col" id="formStatus" class="manage-column column-formStatus">Form Status</th>
					</tr>
				</thead>
				<tbody id="the-list" data-wp-lists="list:feed">';

			// Iter the feeds
			foreach ( $feeds as $feed ) {

				// Get the form id
				$form_id = $feed[ 'form_id' ];

				// Get the form
				$form = GFAPI::get_form( $form_id );

				// Get the form feeds link
				$form_feeds_url = admin_url( 'admin.php?page=gf_edit_forms&view=settings&subview='.MSTEAMS_TEXTDOMAIN.'&id='.$form_id );

				// Check if the form is active
				if ( $form[ 'is_active' ] ) {
					$is_form_active = 'Active';
				} else {
					$is_form_active = 'Inactive';
				}

				// Get the form feeds link
				$feed_url = admin_url( 'admin.php?page=gf_edit_forms&view=settings&subview='.MSTEAMS_TEXTDOMAIN.'&id='.$form_id.'&fid='.$feed[ 'id' ] );

				// Check if the form is active
				if ( $feed[ 'is_active' ] ) {
					$is_feed_active = '<span class="active" style="">Active</span>';
				} else {
					$is_feed_active = '<span class="inactive" style="color: red; font-style: italic;">Inactive</span>';
				}

				// Include channel
				if ( isset( $feed[ 'meta' ][ 'channel' ] ) && sanitize_text_field( $feed[ 'meta' ][ 'channel' ] ) != '' ) {
					$channel = sanitize_text_field( $feed[ 'meta' ][ 'channel' ] );
				} else {
					$channel = '';
				}

				// Echo the form title and count
				echo '<tr>
					<td class="feedName column-feedName column-primary" data-colname="Feed Name" style="font-weight: bold;"><a href="'.esc_url( $feed_url ).'">'.esc_html( $feed[ 'meta' ][ 'feedName' ] ).'</a></td>
					<td class="feedStatus column-feedStatus column-primary" data-colname="Feed Status">'.wp_kses_post( $is_feed_active ).'</td>
					<td class="channel column-channel" data-colname="Channel">'.esc_html( $channel ).'</td>
					<td class="form column-form column-primary" data-colname="Form"><a href="'.esc_url( $form_feeds_url ).'">'.esc_html( $form[ 'title' ] ).'</a></td>
					<td class="formStatus column-formStatus column-primary" data-colname="Form Status">'.wp_kses_post( $is_form_active ).'</td>
				</tr>';
			}

			// End the list
			echo '</tbody>
				<tfoot>
					<tr>
						<th scope="col" id="feedName" class="manage-column column-feedName column-primary">Feed Name</th>
						<th scope="col" id="feedStatus" class="manage-column column-feedStatus">Feed Status</th>
						<th scope="col" id="channel" class="manage-column column-channel">Channel</th>	
						<th scope="col" id="form" class="manage-column column-form column-primary">Form</th>
						<th scope="col" id="formStatus" class="manage-column column-formStatus">Form Status</th>
					</tr>
				</tfoot>
			</table>';
		}

		// End the container
        echo '</div>
		<pre>';
    } // End settings_my_feeds()


	/**
	 * Instructions field
	 *
	 * @param array $field
	 * @param boolean $echo
	 * @return void
	 */
	public function settings_instructions( $field, $echo = true ) {
		// Add the instructions
        echo '</pre>
		<div>
			<h2>'.esc_html__( 'Connecting to Microsoft Teams:', 'gf-msteams' ).'</h2><ol>
				<li>'.esc_html__( 'From Teams, go to Apps > Workflows > Manage Workflows (or from Power Automate, go to My flows)', 'gf-msteams' ).'</li>
				<li>'.esc_html__( 'Add a New Flow using a template', 'gf-msteams' ).'</li>
				<li>'.esc_html__( 'Search templates for "webhook" and choose "Post to a channel when a webhook request is received"', 'gf-msteams' ).'</li>
				<li>'.esc_html__( 'Connect the flow to Microsoft Teams if it is not already connected', 'gf-msteams' ).'</li>
				<li>'.esc_html__( 'Click on Next (or Continue)', 'gf-msteams' ).'</li>
				<li>'.esc_html__( 'Click on Create', 'gf-msteams' ).'</li>
				<li>'.esc_html__( 'If setting up on Teams, your HTTP POST URL should be given right away; copy and save for later', 'gf-msteams' ).'</li>
				<li>'.esc_html__( 'If setting up on Power Automate, go to Edit > click on the "When a Teams webhook request is received" trigger, then copy the HTTP URL and save for later', 'gf-msteams' ).'</li>
			</ol></div>
			<br><br>
			<div><h2>'.esc_html__( 'On Gravity Forms:', 'gf-msteams' ).'</h2><ol>
				<li>'.esc_html__( 'Go to your form settings', 'gf-msteams' ).'</li>
				<li>'.esc_html__( 'Click on Microsoft Teams', 'gf-msteams' ).'</li>
				<li>'.esc_html__( 'Add a new feed', 'gf-msteams' ).'</li>
				<li>'.esc_html__( 'Choose a title', 'gf-msteams' ).'</li>
				<li>'.esc_html__( 'Enter the HTTP POST URL you copied from Microsoft Teams', 'gf-msteams' ).'</li>
				<li>'.esc_html__( 'Select the fields you need to map', 'gf-msteams' ).'</li>
				<li>'.esc_html__( 'Save the settings', 'gf-msteams' ).'</li>
				<li>'.esc_html__( 'Complete the form and see your entry appear!', 'gf-msteams' ).'</li>
			</ol>
		</div>
		<pre>';
    } // End settings_instructions()


	/**
	 * Configures the settings which should be rendered on the Form Settings > Microsoft Teams tab.
	 *
	 * @return array
	 */
	public function feed_settings_fields() {
		// Get the current feed
		if ( $feed =  $this->get_current_feed() ) {

			// Get the form
			$form = GFAPI::get_form( $feed[ 'form_id' ] );
			
		// Or else get the id from the query string
		} elseif ( isset( $_GET[ 'id' ] ) && absint( $_GET[ 'id' ] ) != '' ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			// Get the form
			$form = GFAPI::get_form( absint( $_GET[ 'id' ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Or no form
		} else {
			$form = false;
		}

		// Return the fields
		return [
			[
				'title'  => esc_html__( 'Feed Settings', 'gf-msteams' ),
				'fields' => [
					[
						'name'     => 'feedName',
						'label'    => esc_html__( 'Title', 'gf-msteams' ),
						'type'     => 'text',
						'required' => true,
						'class'    => 'medium',
						'limit'	   => 60,
						'tooltip'  => esc_html__( 'Enter a title to uniquely identify this message. This will be used as your feed name as well as the title of your Teams message.', 'gf-msteams' ),
					],
					[
						'name'     => 'webhook',
						'label'    => esc_html__( 'HTTP POST URL', 'gf-msteams' ),
						'type'     => 'text',
						'required' => true,
						'tooltip'  => esc_html__( 'Add the HTTP POST URL. You will find this in your Workflow trigger.', 'gf-msteams' ),
						'validation_callback' => [ $this, 'validate_request_url' ],
					],
					[
						'name'     => 'channel',
						'label'    => esc_html__( 'Channel Name (Optional)', 'gf-msteams' ),
						'type'     => 'text',
						'required' => false,
						'tooltip'  => esc_html__( 'The Microsoft Teams channel name. For reference only.', 'gf-msteams' ),
					],
					[
						'name'    => 'message',
						'label'   => esc_html__( 'Message (Optional)', 'gf-msteams' ),
						'type'    => 'textarea',
						'class'   => 'medium merge-tag-support mt-position-right',
					],
					[
						'name'  	=> 'top_section_footer',
						'type'  	=> 'top_section_footer',
						'class' 	=> 'medium',
                    ],
				],
			],
			[
				'title'  => esc_html__( 'Fields', 'gf-msteams' ),
				'fields' => [
					// [
					// 	'name'      => 'mappedFields',
					// 	'label'     => esc_html__( 'Match required fields', 'gf-msteams' ),
					// 	'type'      => 'field_map',
					// 	'field_map' => $this->merge_vars_field_map(),
					// 	'tooltip'   => esc_html__( 'Setup the message values by selecting the appropriate form field from the list.', 'gf-msteams' ),
					// ],
					[
						'name'    => 'checkboxgroup',
						'label'   => esc_html__( 'Include the following fields and additional information in Teams Message' ),
						'type'    => 'checkbox',
						'tooltip' => esc_html__( 'Select which fields should be included in the Teams message.' ),
						'choices' => $this->get_list_facts( $form )
					],
					[
						'name'    => 'hideblankgroup',
						'label'   => esc_html__( 'Hide fields with blank values' ),
						'type'    => 'checkbox',
						'tooltip' => esc_html__( 'Removes fields from Teams message if the values are empty.' ),
						'choices' => [
							[
								'label' => 'Yes',
								'name'  => 'hide_blank'
							]
						]
					],
				],
			],
			[
				'title'  => esc_html__( 'Buttons', 'gf-msteams' ),
				'fields' => [
					[
						'name'    => 'buttonsgroup',
						'label'   => esc_html__( 'Include the following buttons in Teams Message' ),
						'type'    => 'checkbox',
						'tooltip' => esc_html__( 'Select which buttons should be included in the Teams message. Users are people that have an account on this website.' ),
						'choices' => [
							[
								'label'         => 'Visit Site',
								'name'          => 'visit_site',
								'default_value' => true,
							],
							[
								'label'         => 'View Entry',
								'name'          => 'view_entry',
								'default_value' => true,
							],
							[
								'label'         => 'View User',
								'name'          => 'view_user',
								'default_value' => true,
							]
						]
					],
					[
						'name'    => 'add_custom_button',
						'type'    => 'checkbox',
						'choices' => [
							[
								'label' => 'Add a Custom Button Link',
								'name'  => 'custom_button'
							]
						]
					],
					[
						'name'    => 'custom_button_text',
						'label'   => esc_html__( 'Button Text' ),
						'type'    => 'text',
						'class'   => 'medium merge-tag-support mt-position-right',
						'dependency' =>  [
							'live'   => true,
							'fields' => [
								[
									'field' => 'add_custom_button',
								],
							],
						]
					],
					[
						'name'    => 'custom_button_url',
						'label'   => esc_html__( 'Button URL' ),
						'type'    => 'text',
						'class'   => 'medium merge-tag-support mt-position-right',
						'default_value' => 'https://',
						'dependency' => [
							'live'   => true,
							'fields' => [
								[
									'field' => 'add_custom_button',
								],
							],
						]
					],
					[
						'name'    => 'custom_button_show_users_only',
						'type'    => 'checkbox',
						'dependency' => [
							'live'   => true,
							'fields' => [
								[
									'field' => 'add_custom_button',
								],
							],
						],
						'choices' => [
							[
								'label' => 'Show custom button for user entries only',
								'name'  => 'custom_button_users_only'
							]
						]
					],
				],
			],
			[
				'title'  => esc_html__( 'Feed Conditions', 'gf-msteams' ),
				'fields' => [
					[
						'name'           => 'condition',
						'label'          => esc_html__( 'Condition', 'gf-msteams' ),
						'type'           => 'feed_condition',
						'checkbox_label' => esc_html__( 'Enable Condition', 'gf-msteams' ),
						'instructions'   => esc_html__( 'Process this feed if', 'gf-msteams' ),
					],
				],
			],
		];
	} // End feed_settings_fields()


	/**
	 * Color "field"
	 *
	 * @param array $field
	 * @param boolean $echo
	 * @return void
	 */
	public function settings_color( $field ) {
		// Get the color
		$color = $this->get_setting( 'color' );
		printf(
			'<input type="color" id="%s" name="%s" class="%s" value="%s" style="width: 10rem;"/>',
			esc_attr( $field[ 'name' ] ),
			esc_attr( $field[ 'name' ] ),
			esc_attr( $field[ 'class' ] ),
			esc_attr( $color ),
		);
    } // End settings_color()


	/**
	 * Plugin settings link "field"
	 *
	 * @param array $field
	 * @param boolean $echo
	 * @return void
	 */
	public function settings_top_section_footer( $field ) {
		// Get the color
		$color = $this->sanitize_and_validate_color( $this->get_setting( 'color' ), $this->default_accent_color );

		// Add CSS
		echo '</pre>
		<style>
		#gform-settings-section-feed-settings { 
			border-top: 3px solid '.esc_attr( $color ).' !important; 
		}
		</style>';

		// Color div and Link to plugin settings page
        echo '<div id="plugin-settings-link" style="margin-top: 30px;"><a href="'.esc_url( MSTEAMS_SETTINGS_URL ).'">Plugin Settings</a></div>
		<pre>';
    } // End settings_plugin_link()


	/**
	 * Return an array of list fields which can be mapped to the Form fields/entry meta.
	 *
	 * @return array
	 */
	public function merge_vars_field_map() {

		// Initialize field map array.
		$field_map = [];

		// Get merge fields.
		$merge_fields = $this->get_list_merge_fields();

		// If merge fields exist, add to field map.
		if ( !empty( $merge_fields ) && is_array( $merge_fields ) ) {

			// Loop through merge fields.
			foreach ( $merge_fields as $field => $config ) {

				// Define required field type.
				$field_type = null;

				switch ( strtolower( $config['type'] ) ) {
					case 'name':
						$field_type = [ 'name', 'text' ];
						break;

					case 'email':
						$field_type = [ 'email' ];
						break;

					case 'textarea':
						$field_type = [ 'textarea' ];
						break;

					default:
						$field_type = [ 'text', 'hidden' ];
						break;
				}

				// Add to field map.
				$field_map[ $field ] = [
					'name'       => $field,
					'label'      => $config[ 'name' ],
					'required'   => $config[ 'required' ],
					'field_type' => $field_type,
					'tooltip'	 => isset( $config[ 'description' ] ) ? $config[ 'description' ] : '',
				];
			}
		}

		return $field_map;
	} // End merge_vars_field_map()


	// # FEED PROCESSING -----------------------------------------------------------------------------------------------


	/**
	 * Process the feed
	 *
	 * @param array $feed  The feed object to be processed.
	 * @param array $entry The entry object currently being processed.
	 * @param array $form  The form object currently being processed.
	 *
	 * @return array
	 */
	public function process_feed( $feed, $entry, $form ) {
		// Log that we are processing feed.
		$this->log_debug( __METHOD__ . '(): Processing feed.' );

		// Get the webhook
		$webhook = filter_var( $feed[ 'meta' ][ 'webhook' ], FILTER_SANITIZE_URL );

		// Check if the webhook is empty
		if ( !$webhook || $webhook == '' || empty( $webhook ) ) {
			$this->add_feed_error( esc_html__( 'Aborted: Empty Incoming Webhook URL', 'gf-msteams' ), $feed, $entry, $form );
			return $entry;
		}

		// // Retrieve the name => value pairs for all fields mapped in the 'mappedFields' field map.
		// $field_map = $this->get_field_map_fields( $feed, 'mappedFields' );

		// // Loop through the fields from the field map setting building an array of values to be passed to the third-party service.
		// $merge_vars = [];
		// foreach ( $field_map as $name => $field_id ) {

		// 	// If no field is mapped, skip it.
		// 	if ( rgblank( $field_id ) ) {
		// 		continue;
		// 	}

		// 	// Get field value.
		// 	$field_value = $this->get_field_value( $form, $entry, $field_id );

		// 	// If field value is empty, skip it.
		// 	if ( empty( $field_value ) ) {
		// 		continue;
		// 	}

		// 	// Get the field value for the specified field id
		// 	$merge_vars[ $name ] = $field_value;
		// }

		// // Check if there are empty mapped fields
		// if ( empty( $merge_vars ) ) {
		// 	$this->add_feed_error( esc_html__( 'Aborted: Empty merge fields', 'gf-msteams' ), $feed, $entry, $form );
		// 	return $entry;
		// }

		// If sending failed
		if ( !$this->send_form_entry( $feed, $entry, $form ) ) {

			// Log that registration failed.
			$this->add_feed_error( esc_html__( 'Microsoft Teams error when trying to send message to channel', 'gf-msteams' ), $feed, $entry, $form ); // phpcs:ignore
			return false;

		// If we sent the form entry successfully
		} else {

			// Add channel?
			if ( isset( $feed[ 'meta' ][ 'channel' ] ) && $feed[ 'meta' ][ 'channel' ] != '' ) {
				$incl_channel = ' | Channel: '.$feed[ 'meta' ][ 'channel' ];
			} else {
				$incl_channel = '';
			}

			// Succcesful 
            $note = 'Entry sent successfully to Microsoft Teams<br>Feed: '.$feed[ 'meta' ][ 'feedName' ].$incl_channel;
            $sub_type = 'success';
            
            // Log that the registrant was added.
            RGFormsModel::add_note( $entry[ 'id' ], 0, __( 'Microsoft Teams', 'gf-msteams' ), $note, 'msteams', $sub_type );
			$this->log_debug( __METHOD__ . '(): Message sent successfully.' ); // phpcs:ignore
		}

		// Return the entry
		return $entry;
	} // End process_feed()


    /**
     * Send form entry as a message to Teams
     *
     * @param array $entry
     * @param array $form
     * @return array
     */
    public function send_form_entry( $feed, $entry, $form ) {
		// Are we hiding empty values?
		if ( isset( $feed[ 'meta' ][ 'hide_blank' ] ) && $feed[ 'meta' ][ 'hide_blank' ] ) {
			$hiding = true;
		} else {
			$hiding = false;
		}

        // Store the messsage facts
        $facts = [];
		$files = [];
		$email = false;

		// Fact key
		$fact_key = 'title';

        // Iter the fields
        foreach ( $form[ 'fields' ] as $field ) {
    
            // Skip HTML fields
            if ( $field->type == 'html' || $field->type == 'section' ) {
                continue;
            }

            // Get the field ID
            $field_id = $field->id;

			// Skip field if not enabled
			if ( isset( $feed[ 'meta' ][ $field_id ] ) && !$feed[ 'meta' ][ $field_id ] ) {
				continue;
			}

			// Get the field label
            $label = ( isset( $field->adminLabel ) && $field->adminLabel != '' ) ? $field->adminLabel : $field->label;

            // Store the value here
            $value = '';

            // Consent fields
            if ( $field->type == 'consent' ) {

                // If they selected the consent checkbox
                if ( isset( $entry[ $field_id . '.1' ] ) && $entry[ $field_id . '.1' ] == 1 ) {
                    $value = 'True';
                } else {
					$value = 'False';
				}
            
            // Checkbox
            } elseif ( $field->type == 'checkbox' ) {
                
				// Get the choices
                $value = $this->get_gf_checkbox_values( $form, $entry, $field_id );

			// Images
            } elseif ( $field->type == 'fileupload' ) {
                
				// Get the file data from the entry
				$file_arr = $entry[ $field_id ];

				// Decode if it's a JSON string representing an array
				if ( is_string( $file_arr ) ) {
					$decoded = json_decode( $file_arr, true );
					// Use decoded only if it's a proper array
					if ( is_array( $decoded ) ) {
						$file_arr = $decoded;
					}
				}

				// Ensure it's always an array for processing
				if ( !is_array( $file_arr ) ) {
					$file_arr = [ $file_arr ];
				}

				if ( !empty( $file_arr ) ) {
					$files[ $field_id ] = [
						'label' => $label
					];

					foreach ( $file_arr as $num => $file_url ) {
						$num++;

						// Clean up malformed data just in case
						if ( !is_string( $file_url ) || empty( $file_url ) ) {
							continue;
						}

						// Check if the URL ends with a common image extension
						$extension = strtolower( pathinfo( parse_url( $file_url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
						$is_image = in_array( $extension, [ 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg' ] );

						if ( $is_image ) {
							$files[ $field_id ][ 'images' ][] = [
								'type'    => 'Image',
								'url'     => $file_url,
								'altText' => 'Image ' . $num . ' for ' . $label
							];
						} else {
							$files[ $field_id ][ 'files' ][] = $file_url;
						}
					}
				}
            
            // Radio/survey/select
            } elseif ( $field->type != 'quiz' && $field->choices && !empty( $field->choices ) ) {

				// Get the choices
                $choices = $field->choices;

                // Iter the choices
                foreach ( $choices as $choice ) {
                    
                    // Get the choice
                    if ( isset( $choice[ 'value' ] ) && strpos( $entry[ $field_id ], $choice[ 'value' ] ) !== false ) {

                        // Get the value
                        $value = $choice[ 'text' ];
                    }
                }

			// Otherwise just return the field value    
            } elseif ( $field->type == 'name' ) {
                $value = $entry[ $field_id.'.3' ].' '.$entry[ $field_id.'.6' ];

            // Otherwise just return the field value    
            } elseif ( isset( $entry[ $field_id ] ) ) {
                $value = $entry[ $field_id ];
			}

			// Does the label end with ? or :
			if ( !str_ends_with( $label, '?' ) && !str_ends_with( $label, ':' ) ) {
				$label = $label.':';
			}

            // Add the fact
			if ( ( !$hiding || ( $hiding && $value != '' ) ) && $field->type !== 'fileupload' ) {
				$facts[] = [
					$fact_key => $label,
					'value'   => $value,
				];
			}

            // Check if the field type is a survey
            if ( $field->type == 'email' && isset( $entry[ $field_id ] ) ) {
                $email = $entry[ $field_id ];
            }
        }

        // Check for a user id
        $user_id = $entry[ 'created_by' ];

        // Did we not find an email?
        if ( ( !$email || $email == '' ) && $user_id > 0 ) {

            // Check if the user exists
            if ( $user = get_userdata( $user_id ) ) {

                // Get the email
                $email = $user->user_email;
            }
        }

		// Last resort user id
		if ( $email && $email != '' && ( !$user_id || $user_id == 0 || $user_id == '' ) ) {
			
			// Attempt to find user by email
			if ( $user = get_user_by( 'email', $email ) ) {
				
				// Set the user id
				$user_id = $user->ID;
			}
		}

		// Add the user id as a fact
		if ( isset( $feed[ 'meta' ][ 'user_id' ] ) && $feed[ 'meta' ][ 'user_id' ] && $user_id ) {
			$facts[] = [
				$fact_key => 'User ID: ',
				'value'   => $user_id,
			];
		}

        // Add the source url as a fact
		if ( !$hiding && isset( $feed[ 'meta' ][ 'source_url' ] ) && $feed[ 'meta' ][ 'source_url' ] ) {
			$facts[] = [
				$fact_key => 'Source URL: ',
				'value'   => $entry[ 'source_url' ],
			];
		}

        // Put the message args together
        $args = [
			'user_id'  => $user_id,
            'email'    => $email,
			'webhook'  => $feed[ 'meta' ][ 'webhook' ],
			'date'     => $entry[ 'date_created' ]
        ];

        // Send the message
        if ( $this->send_msg( $args, $facts, $files, $form, $entry, $feed ) ) {

            // Return true
            return true;

        } else {

            // Return false
            return false;
        }
    } // End send_form_entry()


    /**
     * Send a message to MS Teams channel
     *
     * @return void
     */
    public function send_msg( $args, $facts, $files, $form, $entry, $feed ) {
        // Get the site name
		$get_site_name = sanitize_text_field( $this->get_plugin_setting( 'site_name' ) );
        if ( $get_site_name && $get_site_name != '' ) {
            $site_name = $get_site_name;
        } else {
            $site_name = get_bloginfo( 'name' ); // Blog Name
        }

        // Get the site logo
		$get_site_logo = esc_url_raw( $this->get_plugin_setting( 'site_logo' ) );
		if ( $get_site_logo && $get_site_logo != '' ) {
            $image = $get_site_logo;
        } else {
            $image = MSTEAMS_PLUGIN_DIR.'img/wordpress-logo.png'; // WP Logo
        }

		// Get the title
		$get_title = sanitize_text_field( $feed[ 'meta' ][ 'feedName' ] );
        if ( $get_title && $get_title != '' ) {
            $title = $get_title;
        } else {
            $title = 'New Form Entry';
        }

		// Get the webhook
		$get_webhook = $args[ 'webhook' ];
		if ( $get_webhook && $get_webhook != '' ) {
            $webhook = $get_webhook;
        } else {
            return false;
        }

		// Get the message
		$get_message = sanitize_textarea_field( $feed[ 'meta' ][ 'message' ] );
        if ( $get_message && $get_message != '' ) {
			$message = GFCommon::replace_variables( $get_message, $form, $entry, false, true, false, 'text' );
            $message = $message;
        } else {
            $message = '';
        }

		// Put the data together
		$data = [
			'attachments' => [
				[
					'contentType' => 'application/vnd.microsoft.card.adaptive',
					'content'     => [
						'type'    => 'AdaptiveCard',
						'body'    => [
							[
								'type'   => 'TextBlock',
								'size'   => 'ExtraLarge',
								'weight' => 'Bolder',
								'text'   => $title
							],
							[
								'type'    => 'ColumnSet',
								'columns' => [
									[
										'type'  => 'Column',
										'items' => [
											[
												'type'    => 'Image',
												'url'     => $image,
												'altText' => $site_name.' logo',
												'size'    => 'Small'
											]
										],
										'width' => 'auto'
									],
									[
										'type'  => 'Column',
										'items' => [
											[
												'type'   => 'TextBlock',
												'weight' => 'Bolder',
												'text'   => $site_name,
												'wrap'   => true
											],
											[
												'type'     => 'TextBlock',
												'spacing'  => 'None',
												'text'     => $this->convert_timezone( gmdate( 'Y-m-d H:i:s', strtotime( $args[ 'date' ] ) ) ),
												'isSubtle' => true,
												'wrap'     => true
											]
										],
										'width' => 'stretch'
									]
								]
							],
							[
								'type' => 'TextBlock',
								'text' => $message,
								'wrap' => true
							],
							[
								'type'  => 'FactSet',
								'facts' => $facts
							]
						],
						'actions' => [],
						'msteams' => [
							'width' => 'Full'
						],
						'$schema' => 'http://adaptivecards.io/schemas/adaptive-card.json',
						'version' => '1.4'
					]
				]
			]
		];

		// Add image thumbnails if there are any fileuploads
		if ( !empty( $files ) ) {
			foreach ( $files as $file_data ) {
				$data[ 'attachments' ][0][ 'content' ][ 'body' ][] = [
					'type' => 'TextBlock',
					'weight' => 'Bolder',
					'text' => $file_data[ 'label' ] . ':',
					'wrap' => true
				];

				if ( isset( $file_data[ 'images' ] ) ) {
					$data[ 'attachments' ][0][ 'content' ][ 'body' ][] = [
						'type'      => 'ImageSet',
						'imageSize' => 'large',
						'images'    => $file_data[ 'images' ]
					];
				}

				// Create links for each
				$file_links = [];
				foreach ( $file_data[ 'images' ] as $img ) {
					$file_links[] = [
						'type'  => 'Action.OpenUrl',
						'title' => basename( $img[ 'url' ] ),
						'url'   => $img[ 'url' ]
					];
				}
				foreach ( $file_data[ 'files' ] as $file_url ) {
					$file_links[] = [
						'type'  => 'Action.OpenUrl',
						'title' => basename( $file_url ),
						'url'   => $file_url
					];
				}
				
				// Add clickable button to open full-size image
				$data[ 'attachments' ][0 ][ 'content' ][ 'body' ][] = [
					'type'  => 'ActionSet',
					'actions' => $file_links
				];
			}
		}
		
		// Visit Site Button
		if ( isset( $feed[ 'meta' ][ 'visit_site' ] ) && $feed[ 'meta' ][ 'visit_site' ] ) {
			$data[ 'attachments' ][0][ 'content' ][ 'actions' ][] = [
				'type'  => 'Action.OpenUrl',
				'title' => esc_html__( 'Visit Site', 'gf-msteams' ),
				'url'   => home_url()
			];
		}

		// View Entry Button
		if ( isset( $feed[ 'meta' ][ 'view_entry' ] ) && $feed[ 'meta' ][ 'view_entry' ] ) {
			$data[ 'attachments' ][0][ 'content' ][ 'actions' ][] = [
				'type'  => 'Action.OpenUrl',
				'title' => esc_html__( 'View Entry', 'gf-msteams' ),
				'url'   => home_url().'/wp-admin/admin.php?page=gf_entries&view=entry&id='.$form[ 'id' ].'&lid='.$entry[ 'id' ]
			];
		}

		// View User Button
		if ( isset( $feed[ 'meta' ][ 'view_entry' ] ) && $feed[ 'meta' ][ 'view_user' ] && $args[ 'user_id' ] > 0 ) {
			$data[ 'attachments' ][0][ 'content' ][ 'actions' ][] = [
				'type'  => 'Action.OpenUrl',
				'title' => esc_html__( 'View User', 'gf-msteams' ),
				'url'   => admin_url( 'user-edit.php?user_id='.$args[ 'user_id' ] )
			];
		}

		// Custom Button
		if ( isset( $feed[ 'meta' ][ 'custom_button' ] ) && $feed[ 'meta' ][ 'custom_button' ] &&
			 isset( $feed[ 'meta' ][ 'custom_button_text' ] ) && sanitize_text_field( $feed[ 'meta' ][ 'custom_button_text' ] ) != '' && 
			 isset( $feed[ 'meta' ][ 'custom_button_url' ] ) && filter_var( $feed[ 'meta' ][ 'custom_button_url' ] , FILTER_SANITIZE_URL ) != '' ) {

			// Let's check if we are showing this to users only
			if ( isset( $feed[ 'meta' ][ 'custom_button_users_only' ] ) && $feed[ 'meta' ][ 'custom_button_users_only' ] ) {
				$users_only = true;
			} else {
				$users_only = false;
			}
			if ( $users_only && $args[ 'user_id' ] > 0 || !$users_only ) {

				// Replace merge tag variables
				$text = sanitize_text_field( $feed[ 'meta' ][ 'custom_button_text' ] );
				$text = GFCommon::replace_variables( $text, $form, $entry, false, true, false, 'text' );
				$url = filter_var( $feed[ 'meta' ][ 'custom_button_url' ] , FILTER_SANITIZE_URL );
				$url = GFCommon::replace_variables( $url, $form, $entry, true, true, false, 'text' );
				
				// The button
				$data[ 'attachments' ][0][ 'content' ][ 'actions' ][] = [
					'type'  => 'Action.OpenUrl',
					'title' => $text,
					'url'   => $url
				];
			}
		}
		
		// Encode
		$json_data = wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		
		// Remote post options
		$options = [
			'body'        => $json_data,
			'headers'     => [
				'Content-Type' => 'application/json',
			],
			'timeout'     => 60,
			'redirection' => 5,
			'blocking'    => true,
			'httpversion' => '1.0',
			'sslverify'   => false,
			'data_format' => 'body',
		];
		
		// Send the message
		$response = wp_remote_post( $webhook, $options );		

		// Did we get a response?
		if ( $response ) {
            return true;
        } else {
            return false;
        }
    } // End send_msg()


	// # HELPERS -------------------------------------------------------------------------------------------------------


	/**
     * Get Gravity Form checkbox values
     *
     * @param array $form
     * @param array $entry
     * @param int|float $field_id
     * @return mixed
     */
    public static function get_gf_checkbox_values( $form, $entry, $field_id ) {
        $field = GFAPI::get_field( $form, $field_id );
        return $field->get_value_export( $entry );
    } // End get_gf_checkbox_values()


	/**
	 * Sanitize image
	 *
	 * @param string $input
	 * @return string
	 */
	public function validate_image( $image_url ) {
		// Default output
		$output = '';
	 
		// Check file type
		$filetype = wp_check_filetype( $image_url );
		$mime_type = $filetype[ 'type' ];
	 
		// Only mime type "image" allowed
		if ( strpos( $mime_type, 'image' ) !== false ){
			$output = $image_url;
		}
	 
		// Return the output
		return esc_url_raw( $output );
	} // End validate_image()


	/**
	 * Get merge fields for list.
	 *
	 * @return array
	 */
	public function get_list_merge_fields() {
		// Our mapped fields
		$fields = [
			'email'  => [
				'type' 		  => 'email',
				'name'		  => 'Email',
				'required'	  => true,
			],
		];

		// Return the fields
		return $fields;
	} // End get_list_merge_fields()


	/**
	 * Get the list of form fields to include as facts
	 *
	 * @param array $form
	 * @return array
	 */
	public function get_list_facts( $form ) {
		// Store the messsage facts
        $facts = [];

        // Iter the fields
        foreach ( $form[ 'fields' ] as $field ) {
    
            // Skip HTML fields
            if ( $field->type == 'html' || $field->type == 'section' ) {
                continue;
            }

			// Get the field label
            $label = $field->label;

            // Get the field ID
            $field_id = $field->id;

            // Add the fact
            $facts[] = [
                'label' 		=> $label,
                'name'  		=> $field_id,
				'default_value' => true,
            ];
        }

		// Add the user id
		$facts[] = [
			'label' 		=> 'User ID',
        	'name' 			=> 'user_id',
			'default_value' => true,
		];

		// Add the source
		$facts[] = [
			'label' 		=> 'Source URL',
        	'name' 			=> 'source_url',
			'default_value' => true,
		];

		// Return the array
		return $facts;
	} // End get_list_facts()


	/**
	 * Convert timezone
	 * 
	 * @param string $date
	 * @param string $format
	 * @param string|null $timezone
	 * 
	 * @return string
	 */
	public function convert_timezone( $date, $format = 'F j, Y g:i A', $timezone = null ) {
		// Get the date
		$date = new DateTime( $date, new DateTimeZone( 'UTC' ) );

		// Get the timezone string
		if ( !is_null( $timezone ) ) {
			$timezone_string = $timezone;
		} else {
			$timezone_string = wp_timezone_string();
		}

		// Set the timezone
		$date->setTimezone( new DateTimeZone( $timezone_string ) );

		// Format
		$new_date = $date->format( $format );

		// Return the new date/time
		return $new_date;
	} // End convert_timezone()


	/**
	 * Remove query strings from url without refresh
	 * 
	 * @param string
	 * @return string
	 */
	public function redirect_without_qs( $qs ) {
		// Get the current url without the query string
		$new_url = home_url( remove_query_arg( $qs ) );

		// Redirect
		wp_safe_redirect( $new_url );
		exit();
	} // End remove_qs_without_refresh()


	/**
	 * Sanitize a hex color and force hash
	 *
	 * @param string $color
	 * @param string $default
	 * @return string|void
	 */
	public function sanitize_and_validate_color( $color, $default ) {
		// Check if color exists and if it's still not blank after sanitation
		if ( $color && ( sanitize_hex_color( $color ) != '' || sanitize_hex_color_no_hash( $color ) != '' ) ) {
			
			// If it has hash
			if ( str_starts_with( $color, '#' ) ) {
				$color = sanitize_hex_color( $color );

			// If it does not have hash
			} else {
				$color = '#'.sanitize_hex_color_no_hash( $color );
			}

		// Otherwise return the sanitized default
		} else {
			$color = sanitize_hex_color( $default );
		}

		// Return the color
		return $color;
	} // End sanitize_and_validate_color()


	/**
	 * Validate the webhook url
	 *
	 * @param object $field
	 * @return void
	 */
	public function validate_request_url( $field, $setting_value ) {  
		// Make sure it's required
		if ( rgar( $field, 'required' ) && rgblank( $setting_value ) ) {
			$this->set_field_error( $field, rgar( $field, 'error_message' ) );
			return;
		}

		// Sanitize and validate url; return early if valid.
		$sanitized_value = filter_var( $setting_value, FILTER_SANITIZE_URL );
		if ( $setting_value == $sanitized_value ) {
			return;
		}

		// Error message
		$this->set_field_error( $field, esc_html__( 'Invalid URL', 'gf-msteams' ) );
	} // End validate_request_url()
}