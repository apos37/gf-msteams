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

		// Add the media uploader script
		// TODO: Will work on this for next version
		// add_action( 'admin_enqueue_scripts', [ $this, 'media_uploader' ] );

		// Add a meta box to the entries
        add_filter( 'gform_entry_detail_meta_boxes', [ $this, 'entry_meta_box' ], 10, 3 );
	} // End init()


	/**
	 * Media uploader script
	 *
	 * @return void
	 */
	public function media_uploader() {
		if ( is_admin() ) {
			wp_enqueue_media();
			wp_register_script( 'msteams-media-uploader-js', MSTEAMS_PLUGIN_DIR.'media-uploader.js', [ 'jquery' ], '1.0.1', true );
			wp_enqueue_script( 'msteams-media-uploader-js' );
		}
	} // End media_uploader()


	/**
	 * Enqueue needed scripts.
	 *
	 * @return array
	 */
	// public function scripts() {
	// 	$scripts = [
	// 		[
	// 			'handle'    => 'gf_msteams_media_uploader',
	// 			'src'       => MSTEAMS_PLUGIN_DIR.'media-uploader.js',
	// 			'version'   => $this->_version,
	// 			'deps'      => [ 'jquery', 'media' ],
	// 			'in_footer' => true,
	// 			'enqueue'   => [
	// 				[
	// 					'admin_page' => [ 'form_settings', 'plugin_page' ],
	// 					'tab'        => MSTEAMS_TEXTDOMAIN,
	// 					// 'query' 	 => 'page=gf_settings&subview='.MSTEAMS_TEXTDOMAIN
	// 				],
	// 			],
	// 		],
	// 	];
	// 	return array_merge( parent::scripts(), $scripts );
	// } // End scripts()


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
                'title'         => esc_html__( 'Microsoft Teams', 'gravityforms' ),
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

		// If there are feeds
		if ( !empty( $feeds ) ) {

			// Send the form entry if query string says so
			if ( isset( $_GET[ 'msteams' ] ) && sanitize_text_field( $_GET[ 'msteams' ] )  == 'true' &&
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
				$results .= '<strong>'.$feed[ 'meta' ][ 'feedName' ].':</strong><br><br>';

				// The current url
				$current_url = '/wp-admin/admin.php?page=gf_entries&view=entry&id='.$form[ 'id' ].'&lid='.$entry[ 'id' ];

				// Resend button
				$results .= '<a class="button" href="'.$current_url.'&feed_id='.$feed[ 'id' ].'&msteams=true">Send to Teams</a>';

				// Space between
				$results .= $br;
			}

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
		return file_get_contents( $this->get_base_path().'/img/msteams-icon.svg' );
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
						'tooltip'           => esc_html__( 'The site name displayed on the messages.', 'gf-msteams' ),
						'label'             => esc_html__( 'Site Name', 'gf-msteams' ),
						'type'              => 'text',
						'class'             => 'medium',
						'default_value' 	=> get_bloginfo( 'name' ),
						'feedback_callback' => [ $this, 'is_valid_setting' ],
                    ],
					[
						'name'              => 'site_logo',
						'tooltip'           => esc_html__( 'Upload a logo to be used on the messages. For best results, use an image with the same width and height.', 'gf-msteams' ),
						'label'             => esc_html__( 'Site Logo', 'gf-msteams' ),
						'type'              => 'text',
						'class'             => 'medium',
						'default_value' 	=> MSTEAMS_PLUGIN_DIR.'img/wordpress-logo.png',
						'feedback_callback' => [ $this, 'validate_image' ],
                    ],
					// [
					// 	'name'              => 'upload_image_button',
					// 	'type'              => 'media_upload',
					// 	'class'             => 'medium',
					// 	'args'  => [
                    //         'button' => [
                    //             'label'   => esc_html__( '', 'gf-msteams' ),
                    //             'name'    => 'upload_image_button',
					// 			'class'   => 'button',
					// 			'value'   => 'Upload Image'
					// 		],
					// 	],
                    // ],
                ],
            ],
			[
				'title'       => esc_html__( 'Instructions', 'gf-msteams' ),
				'description' => '<p>'.esc_html__( 'How to add a webhook to Microsoft Teams and setting up feeds.', 'gf-msteams' ).'</p><br>',
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
        // get the button settings from the main field and then render the button
        $button = $field[ 'args' ][ 'button' ];
        printf(
            '<input type="button" id="%s" class="%s" value="%s"/>',
            $button[ 'name' ],
            $button[ 'class' ],
            $button[ 'value' ],
        );
    } // End settings_media_upload()


	/**
	 * Instructions field
	 *
	 * @param array $field
	 * @param boolean $echo
	 * @return void
	 */
	public function settings_instructions( $field, $echo = true ) {
        echo '</pre>
		<div><h2>'.esc_html__( 'On Microsoft Teams:', 'gf-msteams' ).'</h2><ol>
			<li>'.esc_html__( 'Go to Apps', 'gf-msteams' ).'</li>
			<li>'.esc_html__( 'Search for Incoming Webhook', 'gf-msteams' ).'</li>
			<li>'.esc_html__( 'Click on the Incoming Webhook app', 'gf-msteams' ).'</li>
			<li>'.esc_html__( 'Click on "Add to a team"', 'gf-msteams' ).'</li>
			<li>'.esc_html__( 'Choose a channel to add the messages to', 'gf-msteams' ).'</li>
			<li>'.esc_html__( 'Click on "Set up connector"', 'gf-msteams' ).'</li>
			<li>'.esc_html__( 'Name your webhook (this will be used as the name that the messages are posted by)', 'gf-msteams' ).'</li>
			<li>'.esc_html__( 'Upload a logo for your webhook', 'gf-msteams' ).'</li>
			<li>'.esc_html__( 'Click on "Create"', 'gf-msteams' ).'</li>
			<li>'.esc_html__( 'Copy the webhook URL and save it; you will be needing this to add to your form feed', 'gf-msteams' ).'</li>
			<li>'.esc_html__( 'Click on "Done"', 'gf-msteams' ).'</li>
		</ol></div>
		<br><br>
		<div><h2>'.esc_html__( 'On Gravity Forms:', 'gf-msteams' ).'</h2><ol>
			<li>'.esc_html__( 'Go to your form settings', 'gf-msteams' ).'</li>
			<li>'.esc_html__( 'Click on Microsoft Teams', 'gf-msteams' ).'</li>
			<li>'.esc_html__( 'Add a new feed', 'gf-msteams' ).'</li>
			<li>'.esc_html__( 'Choose a title', 'gf-msteams' ).'</li>
			<li>'.esc_html__( 'Enter the webhook URL you copied from Microsoft Teams', 'gf-msteams' ).'</li>
			<li>'.esc_html__( 'Select the fields you need to map', 'gf-msteams' ).'</li>
			<li>'.esc_html__( 'Save the settings', 'gf-msteams' ).'</li>
			<li>'.esc_html__( 'Complete the form and see your entry appear!', 'gf-msteams' ).'</li>
		</ol></div>
		<pre>';
    } // End settings_instructions()


	/**
	 * Configures the settings which should be rendered on the Form Settings > Zoom Webinar tab.
	 *
	 * @return array
	 */
	public function feed_settings_fields() {
		return [
			[
				'title'  => esc_html__( 'Microsoft Teams Integration Settings', 'gf-msteams' ),
				'fields' => [
					[
						'name'     => 'feedName',
						'label'    => esc_html__( 'Title', 'gf-msteams' ),
						'type'     => 'text',
						'required' => true,
						'class'    => 'medium',
						'tooltip'  => esc_html__( 'Enter a title to uniquely identify this message. This will be used as your feed name as well as the title of your Teams message.', 'gf-msteams' ),
					],
					[
						'name'     => 'webhook',
						'label'    => esc_html__( 'Incoming Webhook URL', 'gf-msteams' ),
						'type'     => 'text',
						'required' => true,
						'tooltip'  => esc_html__( 'Add the Incoming Webhook URL. You will find this in your Microsoft Teams Incoming Webhook App setup.', 'gf-msteams' ),
					],
					[
						'name'     => 'channel',
						'label'    => esc_html__( 'Channel Name (Optional)', 'gf-msteams' ),
						'type'     => 'text',
						'required' => false,
						'tooltip'  => esc_html__( 'The Microsoft Teams channel name. For reference only.', 'gf-msteams' ),
					],
					[
						'name'     => 'color',
						'label'    => esc_html__( 'Accent Color (Optional)', 'gf-msteams' ),
						'type'     => 'text',
						'required' => false,
						'tooltip'  => esc_html__( 'Enter a hex color code for the accent color on the message. Default is red (#FF0000).', 'gf-msteams' ),
						'default_value' => '#FF0000',
					],
				],
			],
			[
				'title'  => esc_html__( 'Field Mapping', 'gf-msteams' ),
				'fields' => [
					[
						'name'      => 'mappedFields',
						'label'     => esc_html__( 'Match fields', 'gf-msteams' ),
						'type'      => 'field_map',
						'field_map' => $this->merge_vars_field_map(),
						'tooltip'   => esc_html__( 'Setup the message values by selecting the appropriate form field from the list.', 'gf-msteams' ),
					],
				],
			],
			[
				'title'  => 'Feed Conditions',
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
	 * Return an array of Zoom Webinar list fields which can be mapped to the Form fields/entry meta.
	 *
	 * @return array
	 */
	public function merge_vars_field_map() {

		// Initialize field map array.
		$field_map = [];

		// Get merge fields.
		$merge_fields = $this->get_list_merge_fields();

		// If merge fields exist, add to field map.
		if ( ! empty( $merge_fields ) && is_array( $merge_fields ) ) {

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
	 * Process the feed: register user with Zoom Webinar.
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

		// Retrieve the name => value pairs for all fields mapped in the 'mappedFields' field map.
		$field_map = $this->get_field_map_fields( $feed, 'mappedFields' );

		// Get mapped email address.
		$email = $this->get_field_value( $form, $entry, $field_map[ 'email' ] );

		// If email address is invalid, log error and return.
		if ( GFCommon::is_invalid_or_empty_email( $email ) ) {
			$this->add_feed_error( esc_html__( 'A valid Email address must be provided.', 'gf-msteams' ), $feed, $entry, $form );
			return $entry;
		}

		// Loop through the fields from the field map setting building an array of values to be passed to the third-party service.
		$merge_vars = [];
		foreach ( $field_map as $name => $field_id ) {

			// If no field is mapped, skip it.
			if ( rgblank( $field_id ) ) {
				continue;
			}

			// Get field value.
			$field_value = $this->get_field_value( $form, $entry, $field_id );

			// If field value is empty, skip it.
			if ( empty( $field_value ) ) {
				continue;
			}

			// Get the field value for the specified field id
			$merge_vars[ $name ] = $field_value;
		}

		// Check if there are empty mapped fields
		if ( empty( $merge_vars ) ) {
			$this->add_feed_error( esc_html__( 'Aborted: Empty merge fields', 'gf-msteams' ), $feed, $entry, $form );
			return $entry;
		}

		// If sending failed
		if ( !$this->send_form_entry( $feed, $entry, $form, $email ) ) {

			// Log that registration failed.
			$this->add_feed_error( esc_html__( $this->_short_title.' error when trying to send message to channel', 'gf-msteams' ), $feed, $entry, $form ); // phpcs:ignore
			return false;

		// If we sent the form entry successfully
		} else {

			// Succcesful 
            $note = 'Entry sent successfully to Microsoft Teams';
            $sub_type = 'success';
            
            // Log that the registrant was added.
            RGFormsModel::add_note( $entry[ 'id' ], 0, __( $this->_short_title, 'gf-msteams' ), $note, 'msteams', $sub_type );
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
    public function send_form_entry( $feed, $entry, $form, $email ) {
        // Store the messsage facts
        $facts = [];

        // Iter the fields
        foreach ( $form[ 'fields' ] as $field ) {
    
            // Get the field label
            $label = $field->label;

            // Get the field ID
            $field_id = $field->id;

            // Store the value here
            $value = '';

            // Skip HTML fields
            if ( $field->type == 'html' ) {
                continue;
            }

            // Check if the field type is a survey
            if ( $field->type == 'survey' ) {
                
                // Get the choices
                $choices = $field->choices;

                // Store selected
                $selected = 0;

                // Iter the choices
                foreach ( $choices as $choice ) {
                    
                    // Get the choice
                    if ( strpos( $entry[ $field_id ], $choice[ 'value' ] ) !== false ) {

                        // Increase selected
                        $selected++;

                        // Get the value
                        $value = $choice[ 'text' ];
                    }
                }
              
            // Consent fields
            } elseif ( $field->type == 'consent' ) {

                // If they selected the consent checkbox
                if ( isset( $entry[ $field_id ] ) && $entry[ $field_id ] == 1 ) {
                    $value = 'True';
                }
            
            // Quiz questions
            } elseif ( $field->type != 'quiz' && $field->choices && !empty( $field->choices ) ) {
                $value = self::get_gf_checkbox_values( $form, $entry, $field_id );

            // Otherwise just return the field value    
            } elseif ( isset( $entry[ $field_id ] ) ) {
                $value = $entry[ $field_id ];
			}

            // Add the fact
            $facts[] = [
                'name'  => $label.': ',
                'value' => $value
            ];

            // Check if the field type is a survey
            if ( !$email && $field->type == 'email' && isset( $entry[ $field_id ] ) ) {
                $email = $entry[ $field_id ];
            }
        }

        // Check for a user id
        $user_id = $entry[ 'created_by' ];

        // Add the user id as a fact
        $facts[] = [
            'name'  => 'User ID: ',
            'value' => $user_id
        ];

        // Add the source url as a fact
        $facts[] = [
            'name'  => 'Source URL: ',
            'value' => $entry[ 'source_url' ]
        ];

        // Did we not find an email?
        if ( $email == '' && $user_id > 0 ) {

            // Check if the user exists
            if ( $user = get_userdata( $user_id ) ) {

                // Get the email
                $email = $user->user_email;
            }
        }

        // Put the message args together
        $args = [
            'form_id'  => $form[ 'id' ],
            'entry_id' => $entry[ 'id' ],
			'user_id'  => $user_id,
            'email'    => $email,
			'webhook'  => $feed[ 'meta' ][ 'webhook' ],
			'date'     => $entry[ 'date_created' ]
        ];

        // Send the message
        if ( $this->send_msg( $args, $facts, $feed ) ) {

            // Return true
            return true;

        } else {

            // Return false
            return false;
        }
    } // End send_form_entry()


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
     * Send a message to MS Teams channel
     *
     * @return void
     */
    public function send_msg( $args, $facts, $feed ) {
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

        // Get the accent color
		$get_color = sanitize_hex_color( $feed[ 'meta' ][ 'color' ] );
        if ( $get_color && $get_color != '' ) {
            $color = $get_color;
        } else {
            $color = '#FF0000'; // Red
        }

        // Put the message card together
        $data = [
            '@type'      => 'MessageCard',
            '@context'   => 'https://schema.org/extensions',
            'summary'    => 'Gravity Forms Microsoft Teams Integration',
            'themeColor' => $color,
            'title'      => $title,
            'sections'   => [
                [
                    'activityTitle'    => $site_name,
                    'activitySubtitle' => home_url(),
                    'activityImage'    => $image,
                    'text'             => $this->convert_timezone( date( 'Y-m-d H:i:s', strtotime( $args[ 'date' ] ) ) ),
                    'facts'            => $facts,
                ]
            ],
            'potentialAction' => [
                [
                    '@type'   => 'OpenUri',
                    'name'    => 'Visit Site',
                    'targets' => [
                        [
                            'os'  => 'default',
                            'uri' => home_url()
                        ]
                    ]
                ],
                [
                    '@type'   => 'OpenUri',
                    'name'    => 'View Entry',
                    'targets' => [
                        [
                            'os'  => 'default',
                            'uri' => home_url().'/wp-admin/admin.php?page=gf_entries&view=entry&id='.$args[ 'form_id' ].'&lid='.$args[ 'entry_id' ]
                        ]
                    ]
                ],
            ]
        ];

		// View User Button
		if ( $args[ 'user_id' ] > 0 ) {
			$data[ 'potentialAction' ][] = [
				'@type'   => 'OpenUri',
				'name'    => 'View User',
				'targets' => [
					[
						'os'  => 'default',
						'uri' => home_url().'/wp-admin/users.php?s='.$args[ 'email' ]
					]
				]
			];
		}

        // Encode
        $json_data = json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

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
	 * Get Zoom Webinar registration merge fields for list.
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

}