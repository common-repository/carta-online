<?php

/**
 * co_shortcode_filter short summary.
 *      Class voor het renderen van een filter dat samenwerkt met co_offerlist
 *
 * co_shortcode_filter description.
 *      Rendert een <select> control dat gebruikt kan worden om het aanbod te filteren.
 *      Gebruik use-filter-selection in co_offerlist om de controls aan elkaar te linken.
 *      Vereist het gebruik van functionele COOKIES
 *
 * @author Hans
 */
require_once (plugin_dir_path(__FILE__) . 'co_shortcode.php');
require_once (plugin_dir_path(__FILE__) . 'co_utils.php');
require_once (plugin_dir_path(__FILE__) . 'co_shortcode_offer.php');

class CartaOnlineShortcode_Filter extends CartaOnlineShortcode_Offer
{
    function __construct($atts, $contents) {
        parent::__construct($atts, $contents);
        $this->labelCat       = __("Category:", 'carta-online');
        $this->placeholderCat = __("Any", 'carta-online');
        $this->labelLoc       = __("Location:", 'carta-online');
        $this->placeholderLoc = __("Any", 'carta-online');
        $this->labelFld       = __("Field:", 'carta-online');
        $this->placeholderFld = __("Any", 'carta-online');
        $this->formid         = 'co-filter-form';
    }
    
    protected $labelCat;
    protected $labelLoc;
    protected $labelFld;
    protected $placeholderCat;
    protected $placeholderLoc;
    protected $placeholderFld;
    protected $formid;
    
    function html_form_code($errormessage, $ajax, $template) {
        global $CO_POST;
        
        if ($this -> api == null) {
            $errormessage = $this -> GetLastError();
        }
        
        if (isset($errormessage))  {
            return '<div class="co-error"><p>' . $errormessage . '</p></div>';
        }
        
        // Retrieve filter from session
        $filter_cat = $_SESSION['CO_CATEGORY'] ?? null;
        $filter_loc = $_SESSION['CO_LOCATION'] ?? null;
        $filter_fld = $_SESSION['CO_FIELD'] ?? null;
        
        if (co_isNullOrEmpry($filter_cat)) {
            $filter_cat = ( isset( $CO_POST["cf_category"] ) ? esc_attr( $CO_POST["cf_category"] ) : '' );
        }
        if (co_isNullOrEmpry($filter_loc)) {
            $filter_loc = ( isset( $CO_POST["cf_location"] ) ? esc_attr( $CO_POST["cf_location"] ) : '' );
        }
        if (co_isNullOrEmpry($filter_fld)) {
            $filter_fld = ( isset( $CO_POST["cf_field"] ) ? esc_attr( $CO_POST["cf_field"] ) : '' );
        }
        // Clear unused filters
        if ((strpos($template, 'c') === false) && (strpos($template, 'C') === false)) {
            $filter_cat = '';
            $_SESSION['CO_CATEGORY'] = null;
        }
        if ((strpos($template, 'l') === false) && (strpos($template, 'L') === false)) {
            $filter_loc = '';
            $_SESSION['CO_LOCATION'] = null;
        }
        if ((strpos($template, 'f') === false) && (strpos($template, 'F') === false)) {
            $filter_fld = '';
            $_SESSION['CO_FIELD'] = null;
        }
        
        $submit = 'onchange="submit()"';
        if ($ajax === true) {
            $submit = '';
        }
        
        $dataFilter = $this -> createOfferFilter(($filter_loc == "" ? null : $filter_loc ),        
                                                 ($filter_cat == "" ? null : $filter_cat ),        
                                                 ($filter_fld == "" ? null : $filter_fld ));

        
//         $dataFilter = co_createFilter(
//             null,   //  $filterStatus = null,
//             null,   //  $filterType = null,
//             ($filter_loc == "" ? null : $filter_loc ),        //  $filterLocation = null,
//             ($filter_cat == "" ? null : $filter_cat ),        //  $filterCategory = null,
//             ($filter_fld == "" ? null : $filter_fld ));        //  $filterField = null,       
        $data = $this -> api -> getOfferingsV2('ALL', $dataFilter);
        if (!isset($data)) {
            return "<p>" . _("no data") . "</p>";              
        }
        
        // Render form with filter options.
        $html = '<form id="' . $this->formid . '" action="' . esc_url( $_SERVER['REQUEST_URI'] ) . '" method="post">';
        foreach(str_split($template) as $char) {
            if ( ($char == "c") || ($char == "C") ) {
                
                $html = $html . '<p>' . $this->labelCat . '<br/>' . '<select ' . $this -> selectsize(count($data->categories)) . ' id="co-category-filter" class="co-category-filter co-filter-combo" name="cf_category" ' . $submit;
                $html = $html . '>' . $this -> RenderCategories($filter_cat, $data) . '</select></p>';
            } 
            if ( ($char == "l") || ($char == "L") ) {
                $html = $html . '<p>' . $this->labelLoc . '<br/>' . '<select  ' . $this -> selectsize(count($data->locations)) . '" id="co-location-filter" class="co-location-filter co-filter-combo" name="cf_location" ' . $submit;
                $html = $html . '>' . $this -> RenderLocations($filter_loc, $data) . '</select></p>';
            } 
            if ( ($char == "f") || ($char == "F") ) {
                $html = $html . '<p>' . $this-> labelFld . '<br/>' . '<select  ' . $this -> selectsize(count($data->fields)) . '" id="co-field-filter" class="co-field-filter co-filter-combo" name="cf_field" ' . $submit;
                $html = $html . '>' . $this -> RenderFields($filter_fld, $data) . '</select></p>';
            } 
        }
        
        if ($ajax !== true) {
            $html = $html . '<input type="submit" name="btnSubmit" value="' . __('search',"carta-online") .  '">'; 
        }
        $html = $html . '</form>' . co_session_info("<br/> Form: ");
        
        return $html;
    }
    
    function selectsize($numitems) {
        if (co_get_special_render_option('selectsize', false) === true) {
            return 'size="' . ($numitems+1) . '"';
        }
        return '';
    }
    
    function process_request() {
        global $CO_POST;
       
        $filter_cat = $_SESSION['CO_CATEGORY'] ?? null;
        $filter_loc = $_SESSION['CO_LOCATION'] ?? null;
        $filter_fld = $_SESSION['CO_FIELD'] ?? null;
             
        // if the submit button is the form is posted
        if ( isset( $CO_POST["cf_category"] ) ) {
            $filter_cat    = sanitize_text_field( $CO_POST["cf_category"] ?? '');
            $filter_loc    = sanitize_text_field( $CO_POST["cf_location"] ?? '');
            $filter_fld    = $CO_POST["cf_field"] ?? '';
        }
        
        $_SESSION['CO_CATEGORY'] = $filter_cat;
        $_SESSION['CO_LOCATION'] = $filter_loc;
        $_SESSION['CO_FIELD'] = $filter_fld;

        return "";
    }
    
    function render_form() {
        return $this -> cf_form($this->attribute("ajax", "true"), $this->attribute("template", "clf"));
    }
    
    //
    function cf_form($ajax,$template) {
        global $CO_POST;
        
        ob_start();

        if ($this -> api == null) {
            echo $this -> GetLastError();
        } 
        else
        {
            if (isset( $CO_POST["cf_category"] ) || isset( $CO_POST["cf_location"]) || isset( $CO_POST["cf_field"])) {
                $this -> process_request();
            }
            echo $this -> html_form_code(null, $ajax == "true", $template);
        }
        
        return ob_get_clean();
    }
    
    function render_select($data, $selected, $placeholder) {
        $renderedHtml = '';
        $selectedFound = false;
        if (isset($data)) {
            foreach($data as $choice) {
                if ($choice->name != "?") {
                    $select = '';
                    if ($choice->name == $selected) { 
                        $select = 'selected'; 
                        $selectedFound = true; 
                    }
                    $renderedHtml .= '<option ' . $select . ' value="'. $choice -> name . '">' . $choice -> name . '</option>';
                }
            }
        }
        $select = '';
        if ($selectedFound === false) $select = 'selected';
        return "<option " . $select . " value='' >". $placeholder . "</option>" . $renderedHtml;
    }
    
    function RenderCategories($selected, $data) {
        $categories = $data -> categories;
        
        return $this -> render_select($categories, $selected, $this->placeholderCat) ;
    }

    function RenderLocations($selected, $data) {
        $locations = $data -> locations;
        
        return $this -> render_select($locations, $selected,  $this->placeholderLoc);
    }
    
    function RenderFields($selected, $data) {
        $fields = $data -> fields;
        
        return $this -> render_select($fields, $selected,  $this->placeholderFld);
    }
}

add_action( 'wp_enqueue_scripts', 'co_ajax_enqueue' );
function co_ajax_enqueue( $hook ) {
    // if( 'myplugin_settings.php' != $hook ) return;
    wp_enqueue_script( 'co_ajax_script',
        plugins_url( 'js/co_ajax.js', dirname(__FILE__) ),
        array( 'jquery' )
        );
    $title_nonce = wp_create_nonce( 'co_filter' );
    wp_localize_script( 'co_ajax_script', 'co_ajax_obj', array(
        'offerlist' => 'co-ajax-offerlist',
        'filter'    => 'co-filter-form',
        'ajax_url'  => admin_url('admin-ajax.php'), 
        'nonce'     => $title_nonce,
    ) );
}

add_action( 'wp_ajax_nopriv_co_filter_ajax', 'co_filter_ajax_handler' );
add_action( 'wp_ajax_co_filter_ajax', 'co_filter_ajax_handler' );
function co_filter_ajax_handler() {
    $response = [];
    try {
        if(session_status() !== PHP_SESSION_ACTIVE)
        {
            session_start();
        }
        
        check_ajax_referer( 'co_filter' );
        
        co_update_filter();
        
        $atts = $_SESSION["CO_ATTS"] ?? '';
        $content = $_SESSION["CO_CONTENT"] ?? '';
        $filter_atts = $_SESSION["CO_FILTER_ATTS"] ?? '';
        
        $count = $_SESSION["COUNT"] ?? 0;
        try
        {
            $count = $count + 1;
        }
        catch(exception $e) 
        {
            $count = 1;
        }
        $_SESSION["COUNT"] = $count; 
        
        $offerlist = new CartaOnlineShortcode_Offer($atts, $content);
        $offerHtml = $offerlist -> renderOfferlist(); 
        //$offerHtml = do_shortcode("[co-offerlist use-filter-selection id=co-ajax-offerlist count=ALL]");
        
        $detailHandler = new CartaOnlineShortcode_Filter($filter_atts, null);
        $filterHtml = $detailHandler -> render_form();
        
        $response["offerlist"] = $offerHtml . co_session_info("<br/> Ajax: ");
        $response["filter"] = $filterHtml;
    
    }
    catch(Exception $e)
    {
        $response["offerlist"] = $e->getMessage();
        $response["filter"] = "<h3>" . __('Error','carta-online') . "</h3>";
    }
    echo json_encode($response);
    // Handle the ajax request
    wp_die(); // All ajax handlers die when finished
    
}

// Update cookies after form post. Used for co-test
function co_update_filter() {
    global $CO_POST;

    $filter_cat = $_SESSION['CO_CATEGORY'];
    $filter_loc = $_SESSION['CO_LOCATION'];
    $filter_fld = $_SESSION['CO_FIELD'];
    
    // if the submit button is the form is posted
    if ( isset( $CO_POST["cf_category"] ) ) {
        $filter_cat    = sanitize_text_field( $CO_POST["cf_category"] ?? '' );
        $filter_loc    = sanitize_text_field( $CO_POST["cf_location"] ?? '' );
        $filter_fld    = $CO_POST["cf_field"] ?? '';
    }
    
    $_SESSION['CO_CATEGORY'] = $filter_cat;
    $_SESSION['CO_LOCATION'] = $filter_loc;
    $_SESSION['CO_FIELD'] = $filter_fld;
}


function co_session_info($prefix) {
    if (co_get_special_render_option('debug', false) !== false) {
        $html = "";
        if (session_status() == PHP_SESSION_NONE) {
            $html = $html .  "No session active.";
        }
        if (session_status() == PHP_SESSION_DISABLED) {
            $html = $html .  "Sessions disabled";
        }
        if (session_status() == PHP_SESSION_ACTIVE ) {
            $html = $html .  "Session active [" . session_id() . '] ';
        }
        return $prefix . ($html . htmlspecialchars(json_encode($_SESSION["CO_FILTER_ATTS"] ?? '')));
    }
    return "";
}