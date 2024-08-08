<?php

GFForms::include_addon_framework();


class GFZohoCRMAddOn extends GFAddOn {

	protected $_version = GF_SIMPLE_ADDON_VERSION;
	protected $_min_gravityforms_version = '1.9';
	protected $_slug = 'zohocrmaddon';
	protected $_path = 'gravityform-zohocrm-addon/gravityform-zohocrm-addon.php';
	protected $_full_path = __FILE__;
	protected $_title = 'ZOHO CRM Settings';
	protected $_short_title = 'ZOHO CRM Settings';
	protected $mysession;
	

	private static $_instance = null;

	/**
	 * Get an instance of this class.
	 *
	 * @return GFZohoCRMAddOn
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GFZohoCRMAddOn();
		}

		return self::$_instance;
	}

	/**
	 * Handles hooks and loading of language files.
	 */
	public function init() {
		parent::init();
		
		add_filter( 'gform_submit_button', array( $this, 'form_submit_button' ), 10, 2 );
		add_action( 'gform_after_submission', array( $this, 'after_submission' ), 10, 2 );
	}


	// # SCRIPTS & STYLES -----------------------------------------------------------------------------------------------

	/**
	 * Return the scripts which should be enqueued.
	 *
	 * @return array
	 */
	public function scripts() {
		$scripts = array(
			array(
				'handle'  => 'my_script_js',
				'src'     => $this->get_base_url() . '/js/my_script.js',
				'version' => $this->_version,
				'deps'    => array( 'jquery' ),
				'strings' => array(
					'first'  => esc_html__( 'First Choice', 'simpleaddon' ),
					'second' => esc_html__( 'Second Choice', 'simpleaddon' ),
					'third'  => esc_html__( 'Third Choice', 'simpleaddon' )
				),
				'enqueue' => array(
					array(
						'admin_page' => array( 'form_settings' ),
						'tab'        => 'simpleaddon'
					)
				)
			),

		);

		return array_merge( parent::scripts(), $scripts );
	}

	/**
	 * Return the stylesheets which should be enqueued.
	 *
	 * @return array
	 */
	public function styles() {
		$styles = array(
			array(
				'handle'  => 'my_styles_css',
				'src'     => $this->get_base_url() . '/css/my_styles.css',
				'version' => $this->_version,
				'enqueue' => array(
					array( 'field_types' => array( 'poll' ) )
				)
			)
		);

		return array_merge( parent::styles(), $styles );
	}


	// # FRONTEND FUNCTIONS --------------------------------------------------------------------------------------------

	/**
	 * Add the text in the plugin settings to the bottom of the form if enabled for this form.
	 *
	 * @param string $button The string containing the input tag to be filtered.
	 * @param array $form The form currently being displayed.
	 *
	 * @return string
	 */
	function form_submit_button( $button, $form ) {
		$settings = $this->get_form_settings( $form );
		if ( isset( $settings['enabled'] ) && true == $settings['enabled'] ) {
			$text   = $this->get_plugin_setting( 'mytextbox' );
			$button = "<div>{$text}</div>" . $button;
		}

		return $button;
	}


	// # ADMIN FUNCTIONS -----------------------------------------------------------------------------------------------

	/**
	 * Creates a custom page for this add-on.
	 */
	/*public function plugin_page() {
		echo 'This page appears in the Forms menu';
	}*/

	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {
		return array(
			
			array(
				'title'  => esc_html__( 'Zoho CRM Settings', 'zohocrmsettings' ),
				
				'fields' => array(
					array(
						'label'   => esc_html__( 'Zoho CRM Domain', 'zohocrmsettings' ),
						'type'    => 'select',
						'name'    => 'zohoCrmDomain',
						'tooltip' => esc_html__( 'You must use your domain-specific Zoho Accounts URL to generate access and refresh tokens', 'zohocrmsettings' ),
						'choices' => array(
							array(
								'label' => esc_html__( 'For US: https://accounts.zoho.com', 'zohocrmsettings' ),
								'value' => 'com',
							),
							array(
								'label' => esc_html__( 'For AU: https://accounts.zoho.com.au', 'zohocrmsettings' ),
								'value' => 'com.au',
							),
							array(
								'label' => esc_html__( 'For EU: https://accounts.zoho.eu', 'zohocrmsettings' ),
								'value' => 'eu',
							),
							array(
								'label' => esc_html__( 'For IN: https://accounts.zoho.in', 'zohocrmsettings' ),
								'value' => 'in',
							),
							array(
								'label' => esc_html__( 'For CN: https://accounts.zoho.com.cn', 'zohocrmsettings' ),
								'value' => 'com.cn',
							)
						),
					),
					array(
						'name'              => 'zohoCrmClientID',
						'tooltip'           => esc_html__( 'Specify client-id obtained from the connected app.', 'zohocrmsettings' ),
						'label'             => esc_html__( 'Zoho CRM Client ID', 'zohocrmsettings' ),
						'type'              => 'text',
						'class'             => 'small',
						'feedback_callback' => array( $this, 'is_valid_setting_required' ),
					),
					array(
						'name'              => 'zohoCrmClientSecret',
						'tooltip'           => esc_html__( 'Specify client-secret obtained from the connected app.', 'zohocrmsettings' ),
						'label'             => esc_html__( 'Zoho CRM Client Secret', 'zohocrmsettings' ),
						'type'              => 'text',
						'class'             => 'small',
						'feedback_callback' => array( $this, 'is_valid_setting_required' ),
					),
					array(
						'name'              => 'zohoCrmRefreshToken',
						'tooltip'           => esc_html__( 'Refresh token to obtain new access tokens.', 'zohocrmsettings' ),
						'label'             => esc_html__( 'Zoho CRM Refresh Token', 'zohocrmsettings' ),
						'type'              => 'text',
						'class'             => 'small',
						'feedback_callback' => array( $this, 'is_valid_setting_required' ),
					),
					array(
						'name'              => 'zohoOrganizationID',
						'tooltip'           => esc_html__( 'Zoho CRM Organization ID.', 'zohocrmsettings' ),
						'label'             => esc_html__( 'Zoho CRM Organization ID', 'zohocrmsettings' ),
						'type'              => 'text',
						'class'             => 'small',
						'feedback_callback' => array( $this, 'is_valid_setting_required' ),
					),
					
				)
			)
		);
	}

	/**
	 * Configures the settings which should be rendered on the Form Settings > Simple Add-On tab.
	 *
	 * @return array
	 */
	public function form_settings_fields( $form ) {

		
		return array(
			array(
				'title'  => esc_html__( 'Zoho Sync Settings', 'zohoSyncSettings' ),

				


				'fields' => array(
					
					array(
						'name'    => 'Module',
						'label'   => esc_html__( 'Select Module', 'gravityformszohocrm' ),
						'type'    => 'select',
						'tooltip'             => 'Modules present in Zoho', 
						'class'             => 'small',
						'choices' => $this->get_users_for_feed_setting(),
					),
					array(
						'name'                => 'field_mapping',
						'label'               => esc_html__( 'Field Mapping', 'zohoSyncSettings' ),
						'type'                => 'dynamic_field_map',
						'tooltip'             => 'Form fields mapping with Zoho fields' ,
						'class'               => 'small',
						'field_map' 		  => $this->get_field_map_for_module( 'Contacts', 'dynamic' ),
						/*'validation_callback' => array( $this, 'validate_custom_meta' ), */
					)


					
				),
			),
		);
	}

	/**
	 * Define the markup for the my_custom_field_type type field.
	 *
	 * @param array $field The field properties.
	 * @param bool|true $echo Should the setting markup be echoed.
	 */
	public function settings_my_custom_field_type( $field, $echo = true ) {
		echo '<div>' . esc_html__( 'My custom field contains a few settings:', 'simpleaddon' ) . '</div>';

		// get the text field settings from the main field and then render the text field
		$text_field = $field['args']['text'];
		$this->settings_text( $text_field );

		// get the checkbox field settings from the main field and then render the checkbox field
		$checkbox_field = $field['args']['checkbox'];
		$this->settings_checkbox( $checkbox_field );
	}


	// # SIMPLE CONDITION EXAMPLE --------------------------------------------------------------------------------------

	/**
	 * Define the markup for the custom_logic_type type field.
	 *
	 * @param array $field The field properties.
	 * @param bool|true $echo Should the setting markup be echoed.
	 */
	public function settings_custom_logic_type( $field, $echo = true ) {

		// Get the setting name.
		$name = $field['name'];

		// Define the properties for the checkbox to be used to enable/disable access to the simple condition settings.
		$checkbox_field = array(
			'name'    => $name,
			'type'    => 'checkbox',
			'choices' => array(
				array(
					'label' => esc_html__( 'Enabled', 'simpleaddon' ),
					'name'  => $name . '_enabled',
				),
			),
			'onclick' => "if(this.checked){jQuery('#{$name}_condition_container').show();} else{jQuery('#{$name}_condition_container').hide();}",
		);

		// Determine if the checkbox is checked, if not the simple condition settings should be hidden.
		$is_enabled      = $this->get_setting( $name . '_enabled' ) == '1';
		$container_style = ! $is_enabled ? "style='display:none;'" : '';

		// Put together the field markup.
		$str = sprintf( "%s<div id='%s_condition_container' %s>%s</div>",
			$this->settings_checkbox( $checkbox_field, false ),
			$name,
			$container_style,
			$this->simple_condition( $name )
		);

		echo $str;
	}

	/**
	 * Build an array of choices containing fields which are compatible with conditional logic.
	 *
	 * @return array
	 */
	public function get_conditional_logic_fields() {
		$form   = $this->get_current_form();
		$fields = array();
		foreach ( $form['fields'] as $field ) {
			if ( $field->is_conditional_logic_supported() ) {
				$inputs = $field->get_entry_inputs();

				if ( $inputs ) {
					$choices = array();

					foreach ( $inputs as $input ) {
						if ( rgar( $input, 'isHidden' ) ) {
							continue;
						}
						$choices[] = array(
							'value' => $input['id'],
							'label' => GFCommon::get_label( $field, $input['id'], true )
						);
					}

					if ( ! empty( $choices ) ) {
						$fields[] = array( 'choices' => $choices, 'label' => GFCommon::get_label( $field ) );
					}

				} else {
					$fields[] = array( 'value' => $field->id, 'label' => GFCommon::get_label( $field ) );
				}

			}
		}

		return $fields;
	}

	/**
	 * Evaluate the conditional logic.
	 *
	 * @param array $form The form currently being processed.
	 * @param array $entry The entry currently being processed.
	 *
	 * @return bool
	 */
	public function is_custom_logic_met( $form, $entry ) {
		if ( $this->is_gravityforms_supported( '2.0.7.4' ) ) {
			// Use the helper added in Gravity Forms 2.0.7.4.

			return $this->is_simple_condition_met( 'custom_logic', $form, $entry );
		}

		// Older version of Gravity Forms, use our own method of validating the simple condition.
		$settings = $this->get_form_settings( $form );

		$name       = 'custom_logic';
		$is_enabled = rgar( $settings, $name . '_enabled' );

		if ( ! $is_enabled ) {
			// The setting is not enabled so we handle it as if the rules are met.

			return true;
		}

		// Build the logic array to be used by Gravity Forms when evaluating the rules.
		$logic = array(
			'logicType' => 'all',
			'rules'     => array(
				array(
					'fieldId'  => rgar( $settings, $name . '_field_id' ),
					'operator' => rgar( $settings, $name . '_operator' ),
					'value'    => rgar( $settings, $name . '_value' ),
				),
			)
		);

		return GFCommon::evaluate_conditional_logic( $logic, $form, $entry );
	}

	/**
	 * Performing a custom action at the end of the form submission process.
	 *
	 * @param array $entry The entry currently being processed.
	 * @param array $form The form currently being processed.
	 */
	public function after_submission( $entry, $form ) {

		// Evaluate the rules configured for the custom_logic setting.
		$result = $this->is_custom_logic_met( $form, $entry );

		if ( $result ) {
			// Do something awesome because the rules were met.
		}
	}


	// # HELPERS -------------------------------------------------------------------------------------------------------

	/**
	 * The feedback callback for the 'mytextbox' setting on the plugin settings page and the 'mytext' setting on the form settings page.
	 *
	 * @param string $value The setting value.
	 *
	 * @return bool
	 */
	public function is_valid_setting( $value ) {
		return strlen( $value ) < 10;
	}
	
	/**
	 * is_valid_setting_required
	 *
	 * @param  mixed $value
	 * @return bool
	 */

	public function is_valid_setting_required( $value ) {
		return strlen( $value ) > 1;
	}

	
	
	/**
	 * Get field map fields for a Zoho CRM module.
	 *
	 * @since  1.7.4 Use api_name as field keys.
	 * @since  1.6 Updated per v2 API changes.
	 * @since  1.0
	 * @access public
	 *
	 * @param string $module         Module name.
	 * @param string $field_map_type Type of field map: standard or dynamic. Defaults to standard.
	 *
	 * @return array $field_map
	 */
	public function get_field_map_for_module( $module, $field_map_type = 'standard' ) {


		$form   = $this->get_current_form();
		$form_fields_settings = $this->get_form_settings($form);
		/*echo "<pre>";
		print_r($form_fields_settings);
		
		echo "</pre>";*/

		$modules = $this->getModuleFields($form_fields_settings["Module"]);
		/*echo "<pre>";
		print_r($modules);
		echo "</pre>";*/

		$fields_array = array();
		foreach($modules as $module){
			if(!$module["read_only"] && $module["visible"]) {
				array_push($fields_array, array(
					"name" => $module["api_name"],
					"label" => $module["field_label"],
					'value'      => $module["api_name"],
					'required'   => false,
					'field_type' => "text",
				));
			}
			
		}

		// Initialize field map.
		$field_map = array();

		// Define standard field labels.
		$standard_fields = array( 'Company', 'Email', 'First_Name', 'Last_Name' );

		if(count($fields_array)>0){
			return $fields_array;
		}
		return array(
			array(
				'name'       => "FirstName",
				'label'      => "First Name",
				'value'      => "First_Name",
				'required'   => false,
				'field_type' => "text",
			),
			array(
				'name'       => "Last_Name",
				'label'      => "Last Name",
				'value'      => "Last_Name",
				'required'   => false,
				'field_type' => "text",
			),
			array(
				'name'       => "Contact_Name",
				'label'      => "Contact Name",
				'value'      => "Contact_Name",
				'required'   => false,
				'field_type' => "text",
			)
		);

		

	}

	
	/**
	 * getModuleFields
	 *
	 * @param  mixed $module
	 * @return void
	 */
	public function getModuleFields($module){
		
		$zohoCRMSettings = $this->get_plugin_settings();
		$curl = curl_init();

		curl_setopt_array($curl, array(
		CURLOPT_URL => "https://www.zohoapis.".$zohoCRMSettings["zohoCrmDomain"]."/crm/v2/settings/fields?module=$module",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "GET",
		CURLOPT_HTTPHEADER => array(
			"authorization: Zoho-oauthtoken ".$this->getAccesstoken(),
			"cache-control: no-cache",
			"postman-token: 587c428a-4116-b5fc-feef-1c859882f891"
		),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		if ($err) {
		echo "cURL Error #:" . $err;
		} else {
		  $result = json_decode($response,true);
		  return $result["fields"];
		}
	}

	/**
	 * Fetches an access token for Zoho CRM. If the token stored in the cookie is still valid,
	 * it returns that token. Otherwise, it requests a new token using the refresh token.
	 *
	 * @return string The access token for Zoho CRM.
	 */
	public function getAccesstoken()
	{
		// Check if the current time is less than the token expiration time stored in the cookie
		if (time() < $_COOKIE["ZohoTokenExpiration"]) {
			// If the token is still valid, return the access token from the cookie
			return $_COOKIE["ZohoAccess"]["access_token"];
		}
		
		// Get Zoho CRM settings from the plugin's settings
		$zohoCRMSettings = $this->get_plugin_settings();
		
		// Build the URL to request a new access token using the refresh token
		$url = "https://accounts.zoho.".$zohoCRMSettings["zohoCrmDomain"]."/oauth/v2/token?refresh_token=".$zohoCRMSettings["zohoCrmRefreshToken"]."&client_id=".$zohoCRMSettings["zohoCrmClientID"]."&client_secret=".$zohoCRMSettings["zohoCrmClientSecret"]."&grant_type=refresh_token";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		$result = curl_exec($ch);
		curl_close($ch);
		$res = json_decode($result, true);
		
		return $res["access_token"];
	}

	/**
	 * Fetches all modules from Zoho CRM using the provided access token.
	 *
	 * @param string $access_token The access token for Zoho CRM.
	 * @return array The list of modules from Zoho CRM.
	 */
	public function getall_modules($access_token)
	{
		// Get Zoho CRM settings from the plugin's settings
		$zohoCRMSettings = $this->get_plugin_settings();
		
		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => "https://www.zohoapis.".$zohoCRMSettings["zohoCrmDomain"]."/crm/v2/settings/modules",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "GET",
			CURLOPT_HTTPHEADER => array(
				"authorization: Zoho-oauthtoken ".$access_token,
				"cache-control: no-cache",
				"postman-token: 180294c3-e26f-1b9c-b1a1-40de2f12d779"
			),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);

		curl_close($curl);

		$result = json_decode($response, true);
		if ($err) {
			echo "cURL Error #:" . $err;
		} else {
			return $result["modules"];
		}
	}

	/**
	 * Retrieves the list of modules from Zoho CRM and formats them for a feed setting.
	 *
	 * @return array The formatted list of modules with labels and values.
	 */
	public function get_users_for_feed_setting()
	{
		// Get the list of modules using the access token
		$modules = $this->getall_modules($this->getAccesstoken());

		$input_data = array(
			array(
				"label" => "Select Module",
				"value" => "",
			)
		);
		
		// Loop through each module and add it to the input data if it is API supported
		foreach ($modules as $module) {
			if ($module["api_supported"]) {
				array_push($input_data, array(
					"label" => $module["module_name"],
					"value" => $module["api_name"],
				));
			}
		}
		
		return $input_data;
	}

	

	
}
