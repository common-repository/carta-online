<?php

require_once (plugin_dir_path(__FILE__) . 'co_shortcode.php');
require_once (plugin_dir_path(__FILE__) . 'co_utils.php');
/**
 * CartaOnlineShortcode_Check_Qualification short summary.
 *
 * CartaOnlineShortcode_Check_Qualification description.
 *
 * @version 1.0
 * @author Hans
 */

class CartaOnlineShortcode_Check_Qualification extends CartaOnlineShortcode
{
    function __construct($atts) {
        parent::__construct($atts);
    }

    function html_form_code($Label,$FormID,$errormessage) { 
        global $CO_POST;
        if (isset($errormessage))  {
            echo '<div class="co-error"><p>'. $errormessage . '</p></div>';
        }
	    echo '<form action="' . esc_url( $_SERVER['REQUEST_URI'] ?? '' ) . '" method="post">';
	    echo '<p>' . $Label . '<br/>' . '<input type="text" name="cf-id"';
        $placeholder = $this->attribute('placeholder');
        if (isset($placeholder))
            echo ' placeholder="' . $placeholder . '"';
        echo ' value="' . ( isset( $CO_POST["cf-id"] ) ? esc_attr( $CO_POST["cf-id"] ) : '' ) . '" size="50" /></p>';
        $buttonText = $this->attribute('buttontext', __('Send','carta-online'));

        echo '<p><input type="submit" name="' . $FormID . '" value="' . $buttonText . '"></p>';
	    echo '</form>';
    }

    function html_back($FormID) {
	    echo '<form action="' . esc_url( $_SERVER['REQUEST_URI'] ?? '' ) . '" method="get">';

        $buttonText = $this -> attribute('backbuttontext', __('try again...','carta-online'));

        echo '<p><input type="submit" name="' . $FormID . '" value="' . $buttonText . '"></p>';
	    echo '</form>';
    }

    function process_request($Label,$FormID) {
        global $CO_POST;

        $errortext = "";
        $resulttext = "";
	    // if the submit button is clicked, send the email
	    if ( isset( $CO_POST[$FormID] ) ) {

		    // sanitize form values
		    $user_input = sanitize_text_field( $CO_POST["cf-id"] );

            // call the API
            $co_api = new CartaOnlineApi();

            if ($FormID == 'cf-check-qualification')  {
                if ($user_input == "") {
                    $data = null;
                    $this -> html_form_code($Label,$FormID,__("Invalid input. Please enter a valid registration number.", 'carta-online'));   //"Onjuiste invoer. Voer een geldig registratienummer in."
                }
                else {
                    $data = $co_api -> getIsRegistered(null, $user_input, null, null);
                    if (isset($data))
                    {
                        // Dump($data,"Onderbouwing");
                        $resulttext = "";

                        $collection = $data -> result -> collection;
                        if (sizeof($collection) > 0) {
                            $resulttext .=  "<p><label>" . __("Registrationnumber:", 'carta-online') . "</label>" . $user_input . "</p>"; //"Registratienummer:"
                            $resulttext .=  "<p><label>" . __("Name:", 'carta-online') . "</label>" . $collection[0] -> nameComplete . "</p>"; //"Naam"
                            $schemes = $data -> result -> collection[0] -> organizations[0] -> schemes;
                            if (sizeof($schemes)>0) {

                                $schemeText = "";
                                foreach($schemes as $scheme) {
                                    if (co_SchemeIsValid($scheme)) {
                                        $schemeText .=  "<p><label>" . __("Qualification:", 'carta-online') . "</label>" . $scheme -> description . "</p>"; //Kwalificatie:
                                    }
                                }
                                if ($schemeText == "") {
                                    $errortext = "<p>" . __("No active registration found.", 'carta-online') . "</p>"; //"Geen actieve registratie gevonden."
                                    $data = null;
                                }
                                else {
                                    $resulttext .= $schemeText;
                                }
                            }
                            else {
                                $errortext = "<p>" . __("No scheme found.", 'carta-online') . "</p>"; // "Geen regeling gevonden."
                                $data=null;
                            }
                        }
                        else {
                            $errortext = "<p>" . __("No registrations found.", 'carta-online'). "</p>"; //Geen registraties gevonden.
                            $data=null;
                        }
                    }
                    else {
                        $errortext = "<p>" . __("Service unavailable", 'carta-online') . "</p>"; // Service niet beschikbaar.
                    }
                    if ($data == null) {
                        echo '<div class="co-error">' . $errortext . '</div>';
                    }
                    else {
                        echo '<div class="co-ok">' . $resulttext . '</div>';
                    }
                }
            }
	    }
    }

    function render_form() {
        $label = $this->attribute('label', __("Registration number:", 'carta-online'));
        return $this -> cf_form( $label,'cf-check-qualification'); // Registratienummer:
    }

    function cf_form($Label,$FormID) {
        global $CO_POST;
        ob_start();
        if (isset( $CO_POST[$FormID] ) ) {
	        $this -> process_request($Label, $FormID);
            $this -> html_back('Again');
        } else {
    	    $this -> html_form_code($Label, $FormID, null);
        }
	    return ob_get_clean();
    }
}