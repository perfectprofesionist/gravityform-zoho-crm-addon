<?php
   /*
    Plugin Name: Gravity Form Zoho CRM Addon
    Plugin URI: 
    description: Gravity Form Zoho CRM Addon to create link to Zoho CRM
    Version: 1.2
    Author URI:  
    License: GPL2
   */
    // Define the version of the Gravity Forms Simple Add-On.
    define('GF_SIMPLE_ADDON_VERSION', '2.1');

    // Hook into the 'gform_loaded' action, which is triggered when Gravity Forms is fully loaded.
    // The 'gform_loaded' action ensures that Gravity Forms is ready before executing the callback function.
    // The callback function is an array containing the class 'GF_Simple_AddOn_Bootstrap' and its method 'load'.
    // The priority is set to 5, meaning this action will run early.
    add_action('gform_loaded', array('GF_Simple_AddOn_Bootstrap', 'load'), 5);

    // Define the 'GF_Simple_AddOn_Bootstrap' class.
    class GF_Simple_AddOn_Bootstrap {

        // Static method to load the add-on.
        public static function load() {

            // Check if the 'GFForms' class has the 'include_addon_framework' method.
            // This ensures that the add-on framework is available before proceeding.
            // If the method does not exist, return early and do not continue loading the add-on.
            if (!method_exists('GFForms', 'include_addon_framework')) {
                return;
            }

            // Include the main add-on class file.
            // This file contains the implementation of the add-on.
            require_once('class-gfzohocrmaddon.php');

            // Register the add-on with Gravity Forms.
            // 'GFZohoCRMAddOn' is the class name of the add-on, which extends the Gravity Forms add-on framework.
            GFAddOn::register('GFZohoCRMAddOn');
        }

    }
    
    // Returns the instance of the GFZohoCRMAddOn class
    function gf_simple_addon() {
        return GFZohoCRMAddOn::get_instance();
    }

    // Retrieves Zoho CRM settings and access token
    function get_zoho_crm_settings() {
        $zohoCRMSettingsInstance = GFZohoCRMAddOn::get_instance();
        return [
            'accessToken' => $zohoCRMSettingsInstance->getAccesstoken(),
            'zohoCRMSettings' => $zohoCRMSettingsInstance->get_plugin_settings()
        ];
    }

    // Builds the data array for the Zoho CRM request
    function build_data_array($entry, $form) {
        $data_array = array("data" => array(array()));
        foreach ($form["zohocrmaddon"]["field_mapping"] as $field_mapping) {
            $data_array["data"][0][$field_mapping["key"]] = $entry[$field_mapping["value"]];
        }
        return json_encode($data_array);
    }

    // Sends data to Zoho CRM and returns the response
    function send_to_zoho_crm($data_string, $settings, $module) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://www.zohoapis.' . $settings["zohoCrmDomain"] . '/crm/v2/' . $module);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

        $headers = array(
            'Authorization: Zoho-oauthtoken ' . $settings['accessToken'],
            'Content-Type: application/x-www-form-urlencoded'
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        return json_decode($result, true);
    }

    // Displays an error message on the form confirmation page
    function display_error_message($message) {
        ?>
        <script>
            var gform_confirmation_message = document.getElementsByClassName('gform_confirmation_message');
            if (gform_confirmation_message.length > 0) {
                gform_confirmation_message[0].innerHTML = 'Error - ' + '<?php echo $message; ?>';
            }
        </script>
        <?php
    }

    // Main function that handles the form submission and sends data to Zoho CRM
    function custom_action_after_apc($entry, $form) {
        $zohoSettings = get_zoho_crm_settings();
        $data_string = build_data_array($entry, $form);
        $curl_response = send_to_zoho_crm($data_string, $zohoSettings['zohoCRMSettings'], $form["zohocrmaddon"]["Module"]);

        if ($curl_response["data"][0]["status"] == "error") {
            display_error_message($curl_response["data"][0]["message"]);
        } else {
            echo "<pre id='custom_response' style='display:none;'>";
            print_r($curl_response);
            echo "</pre>";
        }
    }

    // Adds an action that runs the custom function after form submission
    add_action('gform_after_submission', 'custom_action_after_apc', 10, 2);

  
?>