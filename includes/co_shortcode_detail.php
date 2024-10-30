<?php

require_once(plugin_dir_path(__FILE__) . 'co_shortcode.php');
/**
 * shortcode_co_detail short summary.
 *
 * shortcode_co_detail description.
 *
 * [co-detail field="<fieldname>" type="text|image|hyperlink|content" subdfield="<subfieldname>" option="options"]
 *
 * fieldname:
 *  -
 *
 * @version 1.0
 * @author Hans
 */

class CartaOnlineShortcode_Detail extends CartaOnlineShortcode
{
    public function __construct($atts)
    {
        parent::__construct($atts);
    }

    public function renderDetail()
    {
        return $this->getData();
    }

    public function getElementType()
    {
        $retval = $this->attribute("element-type");
        // Default elements are rendered as div
        if (!isset($retval)) {
            $retval = "div";
        }
        return $retval;
    }

    public function getData()
    {
        global $pagename;
        if (!isset($this->api))
            return $this->GetLastError();
        else {
            $identifier = $this->attribute("class");
            if (!isset($identifier)) {
                $identifier = co_get_classId();
                if (!isset($identifier)) {
                    return "<script> window.location.href = " . get_home_url() . "; </script>";
                }
            }
            $data = $this->api->getOfferDetail($identifier);
            if (!isset($data)) {
                // Probably an invalid identifier
                return "";
            }
            if (isset($data) && ($data->status === 404)) {
                $options = get_option('co_settings');
                $redirectPage = co_safe_array_get($options,'co_redirect_page');
                if (co_isset($redirectPage) && ($pagename !== $redirectPage)) {
                    //
                    $classname = co_get_className();

                    // cursusnaam is gedefinieerd in de URL? Zoek op huidige website.
                    $searchparam = "/?s=" . $classname;

                    if (!isset($classname)) {
                        // cursusnaam is onbekend, dus niet zoeken
                        $searchparam = '';
                    }

                    // insert script to redirect page to $redirectPage with optional search param
                    return "<script> window.location.href = '" . $redirectPage . $searchparam . "'; </script>";
                }
            }
            $field = $this->attribute("field");
            $subfield = $this->attribute("subfield");
            $renderedHtml = "";
            $renderType = $this->attribute("type");
            $asArray = ((array) $data->result);
            if (!isset($field)) {
                $field = "subject";
            }
            if ($this->hasAttribute("debug-dump")) {
                return var_dump($asArray);
            }

            if ($renderType == "documentlist") {
                return co_render_documentlist(
                    $identifier,
                    $this->attribute("inner-element-type", "span"),
                    $this->attribute("link-caption", ""),
                    $this->attribute("count", 0)
                );
            }

            if (isset($data) && isset($field) && isset($asArray)) {
                if ($renderType == "planning") {
                    $renderedHtml .= $this->renderPlanning($asArray, $field, $subfield, $this->hasAttribute("first-only"), $this->hasAttribute("max-one"));
                } else {
                    if (array_key_exists($field, $asArray)) {
                        if (isset($subfield)) {
                            // Dynamic fields are defined in a subarray.
                            // Convert to lowercase.
                            $asArray = array_change_key_case((array) $asArray[$field]);
                            $masterField = $field;
                            $field = strtolower($subfield);
                        }
                        $dynamicItems = null;
                        if (array_key_exists('dynamicItems', $asArray)) {
                            $dynamicItems = $asArray['dynamicItems'];
                        }
                        if (array_key_exists($field, $asArray)) {
                            if (!isset($renderType) || ($renderType == "text")) {
                                if (($field == "formattedPrice")  && (co_get_special_render_option('striped-zeroes', false) !== false)) {
                                    $priceHtml = $asArray[$field];
                                    $renderedHtml .= str_replace(",00", ",-", $priceHtml);
                                } else {
                                    $renderedHtml .= $asArray[$field];
                                }
                            } else if ($renderType == "image") {
                                if (isset($asArray[$field]) && !($asArray[$field] == "")) {
                                    $imageURL = $asArray[$field];
                                    if ($imageURL === "TRUE") {
                                        // Workaround for T5116
                                        $imageURL = co_get_option('co_portal_address') . '/image/' . $identifier . '/' . $field;
                                    }
                                    $renderedHtml .= "<img src=\"" . $imageURL . "\" alt='" . $field . "' />";
                                }
                            } else if ($renderType == "subscribe") {
                                // Buttontext may be passed as an option
                                $renderedHtml .= co_subscribeURL($asArray[$field], $this->attribute('option'), $dynamicItems);
                            } else if ($renderType == "teacherlist") {
                                $renderedHtml .= $this->renderTeacherlist($asArray[$field], $this->hasAttribute("mother-teachers"));
                            } else if ($renderType == "contact") {
                                $renderedHtml .= $this->renderContactList($asArray[$field], $this->hasAttribute("mother-contacts"));
                            } else if ($renderType == "credits") {
                                $renderedHtml .= $this->renderCredits($asArray[$field]);
                            } else if ($renderType == "creditscount") {
                                $renderedHtml .= $this->renderCreditsCount($asArray[$field]);
                            } else if ($renderType == "datelist") {
                                $renderedHtml .= $this->renderDatelist($asArray, $field, $this->hasAttribute("first-only"));
                            } else if ($renderType == "icon") {
                                if ($asArray[$field] == "True") {
                                    $iconname = $this->attribute("icon-name");
                                    if (!isset($iconname)) {
                                        $iconname = "none";
                                    }
                                    $renderedHtml .= "<span class='co-icon-" . $iconname . "'></span>";
                                }
                            } else if ($renderType == "money") {
                                $amount = $asArray[$field];
                                if (is_numeric($amount)) {
                                    $formattedAmount = "€ " . number_format($amount, 2, ',', '.');
                                    $renderedHtml .= "<span class='co-detail-money'>" . $formattedAmount . "</span>";
                                }
                            } 
                        }
                    }
                }
            }
        }
        if (isset($renderedHtml) && !($renderedHtml == "")) {
            // Get the requested element type. Defaults to 'div'. If element-type=none is specified, the content is rendered as raw html
            $elementType = $this->getElementType();
            if ($elementType != "none") {
                $html = '<' . $elementType . ' ' . $this->renderid();
                if (isset($field)) {
                    $html .= ' class="co-field co-field-' . str_replace(";", "-", $field) . '"';
                }
                $html .= '>' . $renderedHtml . '</' . $elementType . '>';
            } else {
                // Nothing is rendered except the dynamic value;
                $html = $renderedHtml;
            }
            return $html;
        } else {
            return "";
        }
    }

    public function co_dynamic($offerItem)
    {
        if (isset($offerItem)) {
            return '<div class="co-offer-dynamic">' . $offerItem . '</div>';
        }
        return "";
    }

    protected function renderTeacherlist($identifier, $useOnlyMotherTeachers = false)
    {
        /// Render a list of teachers basis on the course specified by $identifier
        if ($useOnlyMotherTeachers === true) {
            // Will be read from cache.
            $instance = $this->api->getOfferDetail($identifier);
            if (isset($instance) && isset($instance->result) && isset($instance->result->motherIdentifier)) {
                $identifier = $instance->result->motherIdentifier;
            }
        }
        $teachers = $this->api->getTeacherlist(null, $identifier)->result;
        $result = '';
        foreach ($teachers as $teacher) {
            $result .= co_renderTeacher('co-teacher-' . $teacher->id, null, $teacher->id);
        }
        return $result;
    }

    // Render a list of planningItems based on the given instances
    // Invalid input renders an empty string
    //
    // Example shortcode: [co-detail field=instance subfield=startdate,location type=planning]
    //
    protected function renderPlanning($asArray, $field, $subfield, $renderFirstOnly, $renderMaxOne)
    {
        // Sanity check parameters
        if (!isset($asArray) || !array_key_exists('instances', $asArray) || !isset($field) || !isset($subfield))
            return "";
        $instances = array_change_key_case((array) $asArray['instances']);
        $renderedHTML = "";

        foreach ($instances as $instance) {
            if ($this->statusValid($instance) === true) {
                $instanceHTML = "";
                $instanceAsArray = (array) ($instance);
                $instanceAsArray = array_change_key_case($instanceAsArray, CASE_LOWER);

                foreach (explode(";", $field) as $fieldname) 
                {
                    if (array_key_exists($fieldname, $instanceAsArray))
                    {
                        $instanceHTML = $instanceHTML . '<div class=' . $instance[$fieldname] . '>' .  $instanceAsArray[$fieldname] ;
                    }
                    else {
                        if ($fieldname == 'link') {
                            $instanceHTML = $instanceHTML . '<a href="www.lead.nl/"' . $instanceAsArray['identifier'] . '>inschrijven</a>' .  $instanceAsArray[$fieldname];
                        }
                    }
                }

                //
                // Sanity check data
                if (!isset($instance->planning) || (count($instance->planning) == 0))
                    return "";
                foreach ($instance->planning as $planningsItem) {
                    $itemAsArray = (array) ($planningsItem);
                    $itemAsArray = array_change_key_case($itemAsArray, CASE_LOWER);

                    foreach (explode(";", $subfield) as $fieldname) {
                        /// triple = required here!
                        /// Type checking value for 'date' values is not possible in PHP
                        $fieldname = strtolower($fieldname);
                        $datafield = str_replace('time', 'date', $fieldname);
                        if (array_key_exists($datafield, $itemAsArray)) {
                            if ((strpos($fieldname, 'date') !== false)) {
                                $instanceHTML .= "<" . $this->attribute("inner-element-type", "span") . " class='co-planning-" . $fieldname . "-date'>" . co_formatDate($itemAsArray[$fieldname]) . "</" . $this->attribute("inner-element-type", "span") . ">";
                            } else if ((strpos($fieldname, 'time') !== false)) {
                                $timevalue = $itemAsArray[$datafield]; //$offer -> dateStart
                                $instanceHTML .= "<" . $this->attribute("inner-element-type", "span") . " class='co-planning-" . $fieldname . "-time'>" . date_i18n(get_option('time_format'), strtotime($timevalue)) . "</" . $this->attribute("inner-element-type", "span") . ">";
                            } else {
                                $instanceHTML .= "<" . $this->attribute("inner-element-type", "span") . " class='co-planning-" . $fieldname . "'>" . $itemAsArray[$fieldname] . "</" . $this->attribute("inner-element-type", "span") . ">";
                            }
                        } else if ($fieldname == "startendtime") {
                            $starttimevalue = $itemAsArray["datestart"];
                            $endtimevalue = $itemAsArray["dateend"];
                            $instanceHTML .= "<" . $this->attribute("inner-element-type", "span") . " class='co-planning-" . $fieldname . "'>" . date_i18n(get_option('time_format'), strtotime($starttimevalue)) . " - " .
                                date_i18n(get_option('time_format'), strtotime($endtimevalue)) . "</" . $this->attribute("inner-element-type", "span") . ">";
                        }
                    }
                    if ($renderFirstOnly) {
                        break;
                    }
                }
                if ($renderedHTML == "") {
                    $renderedHTML = $instanceHTML;
                } else {
                    $renderedHTML = $renderedHTML . $this->attribute("seperator", "") . $instanceHTML;
                }
                if ($renderMaxOne) {
                    break;
                }
            }
        }
        return $renderedHTML;
    }

    // Render a list of planningItems based on the given instances
    // Invalid input renders an empty string
    //
    // Example shortcode: [co-detail field=instance type=datelist]
    //
    protected function renderDatelist($instances, $field, $renderFirstOnly)
    {
        // Sanity check parameters
        if (!isset($instances) || !isset($field))
            return "";
        $renderedHTML = "";
        foreach ($instances[$field] as $instance) {
            if ($this->statusValid($instance) === true) {
                $instanceHTML = "";
                //
                // Sanity check data
                if (!isset($instance->planning))
                    return "";
                $lastYear = -1;
                $lastMonth = -1;
                $lastDay = -1;
                $dayRendered = false;

                foreach ($instance->planning as $planningsItem) {
                    $itemAsArray = (array) ($planningsItem);
                    $date = $itemAsArray['dateStart'];
                    $currentDay = date_i18n('j', strtotime($date));
                    $currentMonth = date_i18n('F', strtotime($date));
                    $currentYear = date_i18n('Y', strtotime($date));
                    if ($lastDay != -1) {

                        $renderYear = ($lastYear != $currentYear);
                        $renderMonth = ($renderYear) || ($lastMonth != $currentMonth);
                        $renderDay = ($lastDay != $currentDay) || $renderMonth || $renderYear;

                        if ($renderMonth) {
                            $instanceHTML .= ' ' . $lastMonth;
                        }

                        if ($renderYear) {
                            $instanceHTML .= ' ' . $lastYear;
                        }

                        if ($renderDay) {
                            if ($dayRendered) {
                                $instanceHTML .= ', ';
                            }
                            $instanceHTML .= $currentDay;
                            $dayRendered = true;
                        }
                    } else {
                        $instanceHTML .= $currentDay;
                        $dayRendered = true;
                    }
                    $lastDay = $currentDay;
                    $lastMonth = $currentMonth;
                    $lastYear = $currentYear;
                    if ($renderFirstOnly) {
                        break;
                    }

                }

                if ($lastMonth != -1) {
                    $instanceHTML .= ' ' . $lastMonth . ' ' . $lastYear;
                }

                if ($instanceHTML) {
                    $instanceHTML = "<" . $this->attribute("inner-element-type", "span") . " class='co-datelist'>" . $instanceHTML . "</" . $this->attribute("inner-element-type", "span") . ">";
                }
                if ($renderedHTML == "") {
                    $renderedHTML = $instanceHTML;
                } else {
                    $renderedHTML = $renderedHTML . $this->attribute("seperator", "") . $instanceHTML;
                }
            }
        }
        return $renderedHTML;
    }

    protected function renderContactList($identifier, $useOnlyMotherContact = false)
    {
        /// Render a list of contact based on the course specified by $identifier
        if ($useOnlyMotherContact === true) {
            // Will be read from cache.
            $instance = $this->api->getOfferDetail($identifier);
            if (isset($instance) && isset($instance->result) && isset($instance->result->motherIdentifier)) {
                $identifier = $instance->result->motherIdentifier;
            }
        }
        $contacts = $this->api->getContactlist(null, $identifier)->result;
        $result = '';
        foreach ($contacts as $contact) {
            $result .= co_renderContact('co-contact-' . $contact->id, null, $contact->id);
        }
        return $result;
    }

    protected function renderCreditsCount($identifier)
    {
        $result = "";
        $hours = "0";
        $instance = $this->api->getCredits($identifier);
        if ($instance->status == 200) {
            $accList = $instance->result;
            foreach ($accList as $accreditatie) {
                $hours = $accreditatie->hours;
            }
            $result = '<span class="co_creditscount">' . $hours . '</span>';
        } else {
            $result = $instance->message;
        }
        return $result;
    }

    protected function renderCredits($identifier)
    {
        $result = "";
        $instance = $this->api->getCredits($identifier);
        if ($instance->status == 200) {
            $accList = $instance->result;
            foreach ($accList as $accreditatie) {
                $result .= '<div class="co_credits">' .
                    '<div class="co_credits_image co_accreditatie_image' . $accreditatie->scheme->id . '"></div>' .
                    '<span class="co_credits_description">' . $accreditatie->scheme->description . '</span>' .
                    '<span class="co_accreditatie_hours co_accreditatie_hours_' . $accreditatie->hours . '">' . $accreditatie->hours . '</span>' .
                    '<span class="co_accreditatie_points co_accreditatie_points_' . $accreditatie->points . '">' . $accreditatie->points . '</span>' .
                    '</div>';
            }
        } else {
            $result = $instance->message;
        }

        return $result;
    }

}