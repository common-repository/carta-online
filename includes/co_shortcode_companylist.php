<?php

require_once (plugin_dir_path(__FILE__) . 'co_shortcode.php');
require_once (plugin_dir_path(__FILE__) . 'co_utils.php');
/**
 * co_shortcode_companylist short summary.
 *
 * co_shortcode_companylist description.
 *
 * @version 1.0
 * @author Hans
 *
 * [co-companylist fields="<list of field names>" headers="<list of corresponding headers>" ]
 *
 * List-elements are seperated by a semicolon (;)
 * Supported fields:
 *              - name
 *              - nameSupplement
 *              - postalAddress
 *              - postalAddressResidence
 *              - postalAddressPostalCode
 *              - visitingAddress
 *              - visitingAddressResidence
 *              - visitingAddressPostalCode
 *              - telephone
 *              - qualifications
 *              - email
 *              - homepage
 */

class CartaOnlineShortcode_CompanyList extends CartaOnlineShortcode
{
    function __construct($atts) {
        parent::__construct($atts);
    }

    public function renderCompanyList() {
		$api = new CartaOnlineApi();
        if ($api == null or $api->_apiKey == null or $api->_portalAddress == null) {
            $renderedHtml = co_config_error_message();
        }
        else {
    		$data = $api -> getCompanyList();
            $renderedHtml = "";
            if ($data == null) {
                $renderedHtml = co_config_error_message();
            }
            else if ($data -> result == null) {
                $renderedHtml = __("No data","carta-online");
            }
            else {
                $collection = $data -> result;
                $fields = array_filter(explode(';', $this -> attribute('fields')));
                $headers = array_filter(explode(';', $this -> attribute('headers')));
                if (! isset($headers)) {
                    $headers = $fields;
                }
                if (sizeof($fields) != sizeof($headers)) {
                    $renderedHtml .= "<div class='co-error'>" . __("Error in shortcode co_companylist: size of headers and fields lists differ.",'carta-online') . "</div>";
                    $fields = null;
                }
                if ((sizeof($collection) > 0) &&  isset($fields)) {

                    $headerHtml = "";
                    foreach($headers as $header) {
                        $headerHtml .= co_kop($header);
                    }
                    $renderedHtml .=  "<table class='co-companylist'><thead>" . co_rij($headerHtml, "co_companyrij" ) . "</thead>\r\n";

                    foreach($collection as $company) {
                        $rijinhoud = "";

                        foreach($fields as $field)  {
                            $fieldname = $field;
                            $start  = strpos($fieldname, '(');
                            $end    = strpos($fieldname, ')', $start + 1);
                            $length = $end - $start;
                            if ($start === false || $end === false) {
                                $filter = null;
                                $fieldname = $field;
                            } else {
                                $filter = substr($fieldname, $start + 1, $length - 1);
                                $fieldname = substr($fieldname, 0, $start);
                            }

                            if ($fieldname == "name")
                                $rijinhoud .= co_veld( $company -> name );

                            if ($fieldname == "nameSupplement")
                                $rijinhoud .= co_veld( $company -> nameSupplement );

                            if ($fieldname == "postalAddress")
                                $rijinhoud .= co_veld( $company -> postalAddress );

                            if ($fieldname == "postalAddressResidence")
                                $rijinhoud .= co_veld( $company -> postalAddressResidence );

                            if ($fieldname == "postalAddressPostalCode")
                                $rijinhoud .= co_veld( $company -> postalAddressPostalCode );

                            if ($fieldname == "visitingAddress")
                                $rijinhoud .= co_veld( $company -> visitingAddress );

                            if ($fieldname == "visitingAddressResidence")
                                $rijinhoud .= co_veld( $company -> visitingAddressResidence );

                            if ($fieldname == "visitingAddressPostalCode")
                                $rijinhoud .= co_veld( $company -> visitingAddressPostalCode );

                            if ($fieldname == "telephone")
                                $rijinhoud .= co_veld( $company -> telephone );

                            if ($fieldname == "dynamic") {

                                $kwalificationCollection = array();
                                $kwalificationText = "";

                                // Bedrijfscertificeringen staan in de AdHoc items van het bedrijf
                                if (isset($company->dynamicJson)) {
                                    $asArray = (array)($company -> dynamicItems);
                                    $kwalificationText .= str_replace(",","<br />", $asArray[$filter]) . "<br />";
                                }

                                $rijinhoud .= co_veld($kwalificationText);
                            }
                            if ($fieldname == "qualifications") {

                                $kwalificationCollection = array();
                                $kwalificationText = "";

                                foreach($company -> persons as $person) {
                                    if ($person -> hasQualifications) {
                                        foreach($person -> organizations as $organzation) {
                                            foreach ($organzation -> schemes as $scheme) {
                                                $qualification = $scheme -> description;
                                                if (co_SchemeIsValid($scheme)) {
                                                    if ( ! in_array($qualification , $kwalificationCollection)) {
                                                        $kwalificationText .= $qualification . "<br />";
                                                        array_push($kwalificationCollection, $qualification);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                $rijinhoud .= co_veld($kwalificationText);
                            }

                            if ($field == "email")
                                $rijinhoud .= co_veld( $company -> email );

                            if ($field == "homepage") {
                                $companyURL = $company -> homepage;
                                $link = "";
                                if (isset($companyURL)) {
                                    if (! co_startsWith(mb_strtolower($companyURL),'http'))  {
                                        $companyURL = 'http://' . $companyURL;
                                    }
                                    $link = "<a href='" . $companyURL . "'>" . $companyURL . "</a>";
                                }
                                $rijinhoud .= co_veld( $link );
                            }
                        }
                        $renderedHtml .= co_rij($rijinhoud) . "\r\n";
                    }
                    $renderedHtml .= "</table>";
                }
            }
        }
        $html = '<div ' . $this->renderid() . ' class="co-offer-list">' . $renderedHtml . '</div>';
		return $html;
    }
}