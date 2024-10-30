<?php

/**
 * CartaOnlineShortcode_Test short summary.
 *   Generate test form for ad-hoc testing shortcode functionality
 *
 * CartaOnlineShortcode_Test description.
 *   Form: Key, URL, and shortcode
 *   Action: shortcode will be executed against given key and URL
 *   Settings will be saved in cookies.
 * @version 1.0
 * @author Hans
 */

require_once (plugin_dir_path(__FILE__) . 'co_shortcode.php');
require_once (plugin_dir_path(__FILE__) . 'co_utils.php');


class CartaOnlineShortcode_Test extends CartaOnlineShortcode
{
    function __construct($atts) {
        parent::__construct($atts);
        $this->label1       =  __("API Key:", 'carta-online');
        $this->placeholder1 = 'ACF....';
        $this->label2       =  __("URL:", 'carta-online');
        $this->placeholder2 = 'https://....';
        $this->label3       =  __("Request:", 'carta-online');
        $this->placeholder3 = '[co-...]';
        $this->formid       = 'cf-test';
    }

    protected $label1;
    protected $placeholder1;
    protected $label2;
    protected $placeholder2;
    protected $formid;

    function html_form_code($errormessage) {
        global $CO_POST;
        global $CO_COOKIE;
        
        if (isset($errormessage))  {
            echo '<div class="co-error"><p>'. $errormessage . '</p></div>';
        }
        $api_key = $CO_COOKIE['CO_API_KEY'] ?? '';
        $api_url = $CO_COOKIE['CO_API_URL'] ?? '';
        $request = $CO_COOKIE['CO_REQUEST'] ?? '';

        if (co_isNullOrEmpry($api_key)) {
            $api_key = ( isset( $CO_POST["cf-api-key"] ) ? esc_attr( $CO_POST["cf-api-key"] ) : '' );
        }
        if (co_isNullOrEmpry($api_url)) {
            $api_url = ( isset( $CO_POST["cf-api-url"] ) ? esc_attr( $CO_POST["cf-api-url"] ) : '' );
        }
        if (co_isNullOrEmpry($request)) {
            $request = ( isset( $CO_POST["cf-request"] ) ? esc_attr( $CO_POST["cf-request"] ) : '' );
        }
	    echo '<form action="' . esc_url( $_SERVER['REQUEST_URI'] ) . '" method="post">';
	    echo '<p>' . $this->label1 . '<br/>' . '<input type="text" name="cf-api-key" placeholder="' . $this->placeholder1 . '"';
        echo ' value="' .  $api_key . '" size="50" /></p>';
	    echo '<p>' . $this->label2 . '<br/>' . '<input type="text" name="cf-api-url" placeholder="' . $this->placeholder2 . '"';
        echo ' value="' .  $api_url . '" size="50" /></p>';
	    echo '<p>' . $this->label3 . '<br/>' . '<input type="text" name="cf-request" placeholder="' . $this->placeholder3 . '"';
	    echo ' value="' .  htmlspecialchars(urldecode($request)) . '" size="50" /></p>';
        $buttonText = $this->attribute('buttontext', __('Send','carta-online'));

        echo '<p><input type="submit" name="' . $this->formid . '" value="' . $buttonText . '" /></p>';
        echo '</form>';
    }

    function html_back() {
        if (isset($errormessage))  {
            echo '<div class="co-error"><p>'. $errormessage . '</p></div>';
        }
	    echo '<form action="' . esc_url( $_SERVER['REQUEST_URI'] ) . '" method="get">';

        $buttonText = $this -> attribute('backbuttontext', __('try again...','carta-online'));

        echo '<p><input type="submit" name="' .  $this->formid . '" value="' . $buttonText . '"></p>';
	    echo '</form>';
    }

    function process_request() {
        global $CO_POST;
        
	    // if the submit button is the form is posted
	    if ( isset( $CO_POST[ $this->formid ] ) ) {
	        
	        $_SESSION["co_test"] = true;
        
            $api_key    = sanitize_text_field( $CO_POST["cf-api-key"] );
            $api_url    = sanitize_text_field( $CO_POST["cf-api-url"] );
            $sc_request = $CO_POST["cf-request"];

            if (co_isNullOrEmpry($sc_request)) {
                $sc_request='[co-offerlist]';
            }

            if ($api_key == "") {
                $this -> html_form_code(__("Invalid input. Please enter a valid API Key.", 'carta-online'));
            } elseif ($api_url == "") {
                $this -> html_form_code(__("Invalid input. Please enter a valid API URL.", 'carta-online'));
            } elseif ($sc_request == "") {
                $this -> html_form_code(__("Invalid input. Please enter a valid request.", 'carta-online'));
            } else {
                // Settings will be saved in cookie. See add_action( 'wp', 'co_update_cookies') in co_utils.php
                echo do_shortcode($sc_request);
                
                $_SESSION["co_test"] = false;
                return "";
            }
	    }
        return "";
    }

    function render_form() {
        return $this -> cf_form();
    }

    //
    function cf_form() {
        global $CO_POST;
        
        ob_start();       
        
        if (isset( $CO_POST[$this->formid] ) ) {
	        $this -> process_request();
            $this -> html_back('Again');
        } else {
    	    $this -> html_form_code(null);
        }
        
	    return ob_get_clean();
    }
}

// Update cookies after form post. Used for co-test
function co_update_cookies() {
    global $CO_POST;
    global $CO_COOKIE;    
    
    $api_key = null;
    $api_url = null;
    $sc_request = null;
    if (array_key_exists("cf-api-key", $CO_POST)) {
        $api_key    = sanitize_text_field( $CO_POST["cf-api-key"] );
    }
    if (array_key_exists("cf-api-url", $CO_POST)) {
        $api_url    = sanitize_text_field( $CO_POST["cf-api-url"] );
    }
    if (array_key_exists("cf-request", $CO_POST)) {
        $sc_request = $CO_POST["cf-request"];
    }
    if (co_isset($api_key)) {
        setcookie('CO_API_KEY', $api_key, time() + (86400 * 30), "/");
        $CO_COOKIE['CO_API_KEY'] = $api_key;
        setcookie('CO_API_URL', $api_url, time() + (86400 * 30), "/");
        $CO_COOKIE['CO_API_URL'] = $api_url;
        setcookie('CO_REQUEST', urlencode($sc_request), time() + (86400 * 30), "/");
        $CO_COOKIE['CO_REQUEST'] = urlencode($sc_request);
    }
}
add_action( 'wp', 'co_update_cookies');
