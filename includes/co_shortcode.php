<?php

require_once (plugin_dir_path(__FILE__) . 'co_api.php');
require_once (plugin_dir_path(__FILE__) . 'co_render.php');
require_once (plugin_dir_path(__FILE__) . 'co_shortcode.php');
require_once (plugin_dir_path(__FILE__) . 'co_shortcode_detail.php');
require_once (plugin_dir_path(__FILE__) . 'co_shortcode_offer.php');
require_once (plugin_dir_path(__FILE__) . 'co_shortcode_teacher.php');
require_once (plugin_dir_path(__FILE__) . 'co_shortcode_qualification.php');
require_once (plugin_dir_path(__FILE__) . 'co_shortcode_companylist.php');
require_once (plugin_dir_path(__FILE__) . 'co_shortcode_filter.php');
require_once (plugin_dir_path(__FILE__) . 'co_shortcode_expertiselist.php');
require_once (plugin_dir_path(__FILE__) . 'co_shortcode_checkhtml5.php');
require_once (plugin_dir_path(__FILE__) . 'co_shortcode_test.php');
require_once (plugin_dir_path(__FILE__) . 'co_tracking.php');

/* Base Class for Shortcode functions */
class CartaOnlineShortcode {

    protected $id;
    protected $sc_attributes;
    protected $api;
    protected $content;

    function __construct($atts, $content = null) {
        $this -> parseAtts($atts);
        $this -> content = $content;
        $this -> api = new CartaOnlineApi();
        if ($this -> api == null or $this-> api->_apiKey == null or $this-> api ->_portalAddress == null) {
            $this -> api = null;
            $this -> lastError = co_config_error_message();
        }
	}
    
    function GetLastError() {
        if ($this->api != null) {
            return $this->api->lastError;
        } 
        return null;
    }

	function attribute($field, $defaultValue = null) {
	    if (isset($this -> sc_attributes)) {
	        if (array_key_exists($field, $this -> sc_attributes))
	            return $this -> sc_attributes[$field];
	            if (isset($this -> sc_attributes)) {
	                foreach($this -> sc_attributes as $key => $value) {
	                    if (is_numeric($key) && (substr($value, 0, strlen($field)) === $field)) {
	                        $result = substr($value, strlen($field) + 1);
	                        return trim($result, "'\"" );
	                    }
	                }
	            }
	    }
	    return $defaultValue;
	}

    function hasAttribute($field) {
        $result = false;
        if (isset($this -> sc_attributes)) {
            $result=array_key_exists($field, $this -> sc_attributes);
            if ($result === false) {
                $result=in_array($field, $this -> sc_attributes);
            }
        }
        return $result;

    }

    public function parseAtts($atts) {
        /* $this is needed, otherwise a local variable $sc_attributes is created */
        $this -> sc_attributes = $atts;
        if ($atts == "") {
            $this -> sc_attributes = [];
        }
        $this -> id = $this -> attribute('id');
    }

    // Check if status is Valid. Used to check if instance
    //  -
    public function statusValid($instance)
    {
        if (!isset($instance) || !isset($instance->status) || !isset($instance->hideInOfferings))
        {
            return false;
        }
        $status = $instance->status;
        $allowed = $this -> attribute("status","Open,AlmostFull");
        $showHidden = $this-> attribute("showHidden","false");
        $valid = ! $instance->hideInOfferings;
        if ( ($showHidden == "true") && ( $valid === false) )
        {
            $valid = true;
        }
        $validStatus = false;
        foreach(explode(",",$allowed) as $allowedStatus) {
            if ($status == $allowedStatus) {
                $validStatus = true;
            }
        }
        $valid = ( $valid && $validStatus );
        return $valid;
    }

    /// Get classID from parameters from query string
    public function classId() {
        return co_get_classId();
    }

    public function fieldname() {
        global $CO_GET;
    
        foreach ($CO_GET as $key => $value) {
            if ($key == "field")  {
                return($value);
            }
            if ($key == "vakgebied")  {
                return($value);
            }
        }
        return null;
    }


    /// Get filed from active couse
    public function getField() {
        $identifier = $this->classId();
        if (!isset($identifier)) {
            // Redirect to home?
            return "";
        }

        $data = $this-> api-> getOfferDetail($identifier);
        if ($data->status == 200) {
            return implode(';', $data->result->fields);
        }
        return "";
    }

    public function renderid() {
        if (isset($this->id) && ($this->id<>"")) {
            return 'id="' . $this->id . '"';
        }
        return "";
    }
}

function co_render_error($errormessage) {
    return "<div class='co-error'> " . __('Error:', "carta-online" ) . " " . $errormessage . "</div>";
}

add_shortcode('co-offerlist', 'co_offerlist_shortcode');
function co_offerlist_shortcode($atts, $content = null) {
    try {
        // Save Attributres of last offerlist for use in co_filter
        //  Might not work in combination with Widgets...
        $_SESSION["CO_ATTS"] = $atts;
        $_SESSION["CO_CONTENT"] = $content;
        $offerlist = new CartaOnlineShortcode_Offer($atts, $content);
        return $offerlist -> renderOfferlist();
    }
    catch(Exception $e) {
        return co_render_error($e->getMessage());
    }
}

add_shortcode('co-companylist', 'co_companylist_shortcode');
function co_companylist_shortcode($atts) {
    try {
        $companylist = new CartaOnlineShortcode_CompanyList($atts);
        return $companylist -> renderCompanyList();
    }
    catch(Exception $e) {
        return co_render_error($e->getMessage());
    }
}

add_shortcode('co-test', 'co_test');
function co_test($atts) {
    try {
        $testform = new CartaOnlineShortcode_Test($atts);
        return $testform -> render_form();
    }
    catch(Exception $e) {
        return co_render_error($e->getMessage());
    }
}

add_shortcode('co-check-html5', 'co_check_html5');
function co_check_html5($atts) {
    try {
        $htmlTester = new CartaOnlineShortcode_CheckHtml5($atts);
        return $htmlTester -> DoCheck();
    }
    catch(Exception $e) {
        return co_render_error($e->getMessage());
    }
}

add_shortcode('co-teacher', 'co_teacher_shortcode');
// [co-teacher id=teacherdiv post-id=<num> carta-id=<num>]
// post-id = id of the post containing teacher details
// carta-id = carta ID defined in the meta data of the post containing teacher details
// Example: [co-teacher id=teacherblok carta-id=1234]
function co_teacher_shortcode($atts) {
    try {
        $detailHandler = new CartaOnlineShortcode_Teacher($atts);
        return $detailHandler -> renderTeacher();
    }
    catch(Exception $e) {
        return co_render_error($e->getMessage());
    }
}


add_shortcode('co-detail', 'co_detail_shortcode');
function co_detail_shortcode($atts) {
    try {
        $detailHandler = new CartaOnlineShortcode_Detail($atts);
        return $detailHandler -> renderDetail();
    }
    catch(Exception $e) {
        return co_render_error($e->getMessage());
    }
}

// Shortcode co_expertise_list
// [co-expertiselist id="categorylist" extra_options="Nieuw;Populair" extra_labels="Nieuw;Populair" showall-enabled="true"]
add_shortcode( 'co-expertiselist', 'co_expertiselist_shortcode' );
function co_expertiselist_shortcode($atts) {
    try {
        $detailHandler = new CartaOnlineShortcode_expertiseList($atts);
        return $detailHandler -> renderExpertiselist();
    }
    catch(Exception $e) {
        return co_render_error($e->getMessage());
    }
}

//Shortcode generates form to check for qualification
//[co_form_check_qualification id="qualificationCheck" org="Coper"]
add_shortcode( 'co-form-check-qualification', 'co_check_qualification_shortcode' );
function co_check_qualification_shortcode($atts) {
    try {
        $detailHandler = new CartaOnlineShortcode_Check_Qualification($atts);
        return $detailHandler -> render_form();
    }
    catch(Exception $e) {
        return co_render_error($e->getMessage());
    }
}

//Shortcode generates selectboxzes for filtering offerlist
//[co_filter id=filter1]
add_shortcode( 'co-filter', 'co_filter_shortcode' );
function co_filter_shortcode($atts, $content = null) {
    try {
        $_SESSION["CO_FILTER_ATTS"] = $atts;
        $_SESSION["CO_FILTER_CONTENT"] = $content;
        // reset user defined filter
        $_SESSION['CO_CATEGORY'] = null;
        $_SESSION['CO_LOCATION'] = null;
        $_SESSION['CO_FIELD'] = null;
        $detailHandler = new CartaOnlineShortcode_Filter($atts, $content);
        return $detailHandler -> render_form();
    }
    catch(Exception $e) {
        return co_render_error($e->getMessage());
    }
}

