<?php

// Table helpers
function co_rij($inhoud, $classname = null)
{
    if (!isset($classname)) {
        $classname = "co-row";
    }
    return "<tr class='" . $classname . "'>" . $inhoud . "</tr>";
}

function co_veld($inhoud, $classname = null)
{
    if (!isset($classname)) {
        $classname = "co-td";
    }
    return "<td class='" . $classname . "'>" . $inhoud . "</td>";
}

function co_kop($inhoud)
{
    return "<th class='co-th-" . $inhoud . "'>" . $inhoud . "</th>";
}

function co_safe_array_get($array, $key)
{
    if ($array != null && is_array($array) && array_key_exists($key, $array)) {
        return $array[$key];
    }
    return null;
}

// option list helper
function co_addOption($value, $valueToAdd, $seperator = ',')
{
    if (co_isset($value)) {
        $value = $value . $seperator . $valueToAdd;
    } else {
        $value = $valueToAdd;
    }
    return $value;
}

// Check if class is defined in URL. Redirect to home if not defined
function co_gotoClassOrGoHome()
{
    $identifier = co_get_classId();
    if (!isset($identifier)) {
        header("Location: " . co_siteUrl(), true, 301);
        die();
    }
}

function co_get_classId()
{
    global $CO_GET;

    $value = get_query_var('class');
    if (co_isset($value)) {
        return $value;
    }
    foreach ($CO_GET as $key => $value) {
        if ($key == "class") {
            return ($value);
        }
        if ($key == "cursus") {
            return ($value);
        }
    }
    return null;
}

function co_get_identifier($searchkey)
{
    global $CO_GET;

    $value = get_query_var($searchkey);

    if (co_isset($value)) {
        return $value;
    }
    foreach ($CO_GET as $key => $value) {
        if ($key == $searchkey) {
            return ($value);
        }
    }

    return null;
}

function co_get_className()
{
    global $CO_GET;

    $value = get_query_var('cn');
    if (co_isset($value)) {
        return $value;
    }
    foreach ($CO_GET as $key => $value) {
        if ($key == "cn") {
            return ($value);
        }
    }
    return null;
}

function co_report_error($message, $force = false)
{
    try {
        if ((co_get_option("co_report_error", "false") == "true") || ($force === true)) {
            $email = co_get_option("co_report_email", "escalatie@cartaonline.nl");
            wp_mail($email, 'Foutmelding plugin', $message);
        }
    } catch (Exception $e) {
    }
}

function co_get_option($variable, $default = null)
{
    $options = get_option('co_settings');
    if (!isset($options[$variable])) {
        $options[$variable] = $default;
    }
    return ($options[$variable]);
}

// Replace page name in url
function co_replace_pagename($url, $new_page)
{
    $pos = strrpos($url, '/');
    if ($pos == (strlen($url) - 1)) {
        // In case $url ends in '/'
        $url = substr($url, 0, $pos);
        $pos = strrpos($url, '/');
    }
    $url = substr($url, 0, $pos + 1) . $new_page;
    return $url;
}

// Clean all special accented characters and return a readable string
function co_toAscii($str, $replace = array(), $delimiter = '-')
{
    if (!empty($replace)) {
        $str = str_replace((array) $replace, ' ', $str);
    }
    // Attempt to transliterate to ASCII
    $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
    if ($clean === false) {
        // Remove non-ASCII characters, including invisible ones like non-breaking spaces
        $clean = preg_replace('/[^\x20-\x7E]/', '', $str); // This removes characters outside the range of visible ASCII characters
    }
    $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
    $clean = strtolower(trim($clean, '-'));
    $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);

    return $clean;
}

// Verwijder rotzooi uit de parameter
function co_clean_param($text)
{
    return co_toAscii($text);
}

////////////////////////////////////////////////////////
// Function:         dump
// Inspired from:     PHP.net Contributions
// Description: Helps with php debugging

function co_dump(&$var, $info = FALSE)
{
    $prefix = 'unique';
    $suffix = 'value';

    $vals = $GLOBALS;

    $old = $var;
    $var = $new = $prefix . rand() . $suffix;
    $vname = FALSE;
    foreach ($vals as $key => $val)
        if ($val === $new)
            $vname = $key;
    $var = $old;

    echo "<pre style='margin: 0px 0px 10px 0px; display: block; background: white; color: black; font-family: Verdana; border: 1px solid #cccccc; padding: 5px; font-size: 10px; line-height: 13px;'>";
    if ($info != FALSE)
        echo "<b style='color: red;'>$info:</b><br>";
    co_do_dump($var, '$' . $vname);
    echo "</pre>";
}

////////////////////////////////////////////////////////
// Function:         do_dump
// Inspired from:     PHP.net Contributions
// Description: Better GI than print_r or var_dump

function co_do_dump(&$var, $var_name = NULL, $indent = NULL, $reference = NULL)
{
    $do_dump_indent = "<span style='color:#eeeeee;'>|</span> &nbsp;&nbsp; ";
    $reference = $reference . $var_name;
    $keyvar = 'the_do_dump_recursion_protection_scheme';
    $keyname = 'referenced_object_name';

    if (is_array($var) && isset($var[$keyvar])) {
        $real_var = &$var[$keyvar];
        $real_name = &$var[$keyname];
        $type = ucfirst(gettype($real_var));
        echo "$indent$var_name <span style='color:#a2a2a2'>$type</span> = <span style='color:#e87800;'>&amp;$real_name</span><br>";
    } else {
        $var = array($keyvar => $var, $keyname => $reference);
        $avar = &$var[$keyvar];

        $type = ucfirst(gettype($avar));
        if ($type == "String")
            $type_color = "<span style='color:green'>";
        elseif ($type == "Integer")
            $type_color = "<span style='color:red'>";
        elseif ($type == "Double") {
            $type_color = "<span style='color:#0099c5'>";
            $type = "Float";
        } elseif ($type == "Boolean")
            $type_color = "<span style='color:#92008d'>";
        elseif ($type == "NULL")
            $type_color = "<span style='color:black'>";

        if (is_array($avar)) {
            $count = count($avar);
            echo "$indent" . ($var_name ? "$var_name => " : "") . "<span style='color:#a2a2a2'>$type ($count)</span><br>$indent(<br>";
            $keys = array_keys($avar);
            foreach ($keys as $name) {
                $value = &$avar[$name];
                co_do_dump($value, "['$name']", $indent . $do_dump_indent, $reference);
            }
            echo "$indent)<br>";
        } elseif (is_object($avar)) {
            echo "$indent$var_name <span style='color:#a2a2a2'>$type</span><br>$indent(<br>";
            foreach ($avar as $name => $value)
                co_do_dump($value, "$name", $indent . $do_dump_indent, $reference);
            echo "$indent)<br>";
        } elseif (is_int($avar))
            echo "$indent$var_name = <span style='color:#a2a2a2'>$type(" . strlen($avar) . ")</span> $type_color$avar</span><br>";
        elseif (is_string($avar))
            echo "$indent$var_name = <span style='color:#a2a2a2'>$type(" . strlen($avar) . ")</span> $type_color\"$avar\"</span><br>";
        elseif (is_float($avar))
            echo "$indent$var_name = <span style='color:#a2a2a2'>$type(" . strlen($avar) . ")</span> $type_color$avar</span><br>";
        elseif (is_bool($avar))
            echo "$indent$var_name = <span style='color:#a2a2a2'>$type(" . strlen($avar) . ")</span> $type_color" . ($avar == 1 ? "TRUE" : "FALSE") . "</span><br>";
        elseif (is_null($avar))
            echo "$indent$var_name = <span style='color:#a2a2a2'>$type(" . strlen($avar) . ")</span> {$type_color}NULL</span><br>";
        else
            echo "$indent$var_name = <span style='color:#a2a2a2'>$type(" . strlen($avar) . ")</span> $avar<br>";

        $var = $var[$keyvar];
    }
}

function co_IsAfterNow($dateToTest)
{
    if ($dateToTest == null) {
        return true;
    }
    $today = date("Y-m-d");

    $today_time = strtotime($today);
    $test_time = strtotime($dateToTest);

    return ($test_time > $today_time);
}

function co_IsBeforeNow($dateToTest)
{
    if ($dateToTest == null) {
        return true;
    }
    return (!co_IsAfterNow($dateToTest));
}

function co_config_error_message()
{
    return __("Error: Plugin Carta-Online is not correctly configured. Did you specify a valid key and portal address? See ", 'cartaonline') . "<a href='https://www.cartaonline.nl/carta-wordpress-plugin/'>" . __("plugin help page.", 'cartaonline') . "</a>";
}

///
/// SchemeIsValid:  Returns true if any valid support is present for this scheme
function co_SchemeIsValid($scheme)
{
    foreach ($scheme->foundations as $foundation) {
        if ((co_IsBeforeNow($foundation->dateValidity)) and co_IsAfterNow($foundation->dateValidityEnd) and (!$foundation->isRejected)) {
            return true;
        }
    }
    foreach ($scheme->registrations as $registration) {
        if ((co_IsBeforeNow($registration->dateValidity)) and co_IsAfterNow($registration->dateValidityEnd)) {
            return true;
        }
    }
    return false;
}

function co_subscribeURL($identifier, $subscribeText, $dynamicItems)
{
    $portalAddress = co_get_option('co_portal_address');
    $brand = co_get_option('co_branding');
    $brandInfo = "";
    if (co_isset($brand)) {
        $brandInfo = "?referrer=" . $brand;
    }
    $alternateSubscribe = co_get_option('co_alternate_subscription_page');
    if ( $dynamicItems != null) {
        if ( property_exists($dynamicItems, 'InschrijfLink') ) {
            $alternateSubscribe = $dynamicItems -> InschrijfLink ;
        }
    }
    if (isset($alternateSubscribe) && ($alternateSubscribe != "")) {
        $subscribeUrl = str_replace("%identifier%", $identifier, $alternateSubscribe);
    }
    else {
        $subscribeUrl = $portalAddress . '/aanbod/inschrijven/' . $identifier;
    }
    return '<a class="co-offer-register-link" href="' . $subscribeUrl . $brandInfo . '">' . $subscribeText . '</a>';
}

// $date is string date, typically in JSON format
// date-format is defined in WordPress Settings->Generic
function co_formatDate($date)
{
    return date_i18n(get_option('date_format'), strtotime($date));
}

function co_formatDateDayNameShort($date)
{
    return date_i18n('D', strtotime($date));
}

function co_formatDateDayNameFull($date)
{
    return date_i18n('l', strtotime($date));
}

// $date is string date, typically in JSON format
function co_formatDay($date)
{
    return date_i18n('j', strtotime($date));
}

// $date is string date, typically in JSON format
function co_formatMonth($date)
{
    return "";  //$wp_locale->get_month( strtotime( $date )->format( 'm' ) );
}

// $date is string date, typically in JSON format
function co_formatYear($date)
{
    return date_i18n('Y', strtotime($date));
}


// $date is string date, typically in JSON format
// date-format is defined in WordPress Settings->Generic
function co_formatTime($date)
{
    return date_i18n(get_option('time_format'), strtotime($date));
}

/// Check if string starts with substring
function co_startsWith($tekst, $starttext)
{
    $length = strlen($starttext);
    return (substr($tekst, 0, $length) === $starttext);
}

///
/// - Het laatste deel van de URL moet voorkomen in het onderwerp van de moedercursus. Hoofdletters en kleine letters worden daarbij gelijkgesteld.
/// - Hierbij worden de tekens '-' en '_' uit de URL vervangen door spaties
///  o   Bijv: Op de pagina http://www.dentalbestpractice.nl/cursus-bleken_2/ wordt dan het aanbod voor de cursussen met het onderwerp �cursus bleken 2� getoond
///
function co_GetFilterFromURL()
{
    // Indien meta attribuut co-search gezet is op deze pagina, dan overruled dat de standaard zoektekst
    $page = get_post();
    if (isset($page)) {
        $page = get_post_meta(get_post()->ID, 'co_search', true);
    }
    if (!co_isset($page)) {
        $page = filter_input(INPUT_SERVER, 'REQUEST_URI');
        $page = rtrim($page, '/');
        if (isset($page)) {
            $lastSlash = strrpos($page, "/");
            if ($lastSlash >= 0)
                $page = substr($page, $lastSlash + 1);
            $page = '"' . str_replace(array('-', '_'), ' ', $page) . '"';
        }
    }
    return $page;
}

/// Compare two values containing a description element
function co_compareExpertiseValue($a, $b)
{
    if ($a->description == $b->description) {
        return 0;
    }
    return ($a->description < $b->description) ? -1 : 1;
}

/// Look for a special setting
///  Settings are either just present, or are set to a value
///   "just-present;also-present;set-to-a-text=atext;set-to-123=123"
/// Special_render_options
///
function co_get_special_render_option($option, $default_value)
{

    $specialData = explode(';', co_get_option("co_special_render_options", ""));
    foreach ($specialData as $pair) {
        if (strpos($pair, '=') !== false) {
            list($k, $v) = explode('=', $pair);
            if ($k == $option)
                return trim($v, '"');
        } else {
            if ($pair == $option)
                return true;
        }
    }

    return $default_value;
}

function co_siteUrl()
{
    return 'http' . (empty($_SERVER['HTTPS']) ? '' : 's') . '://' . $_SERVER['HTTP_HOST'] . '/';
}

function co_isNullOrEmpry($string)
{
    return (!isset($string) || ($string == null) || trim($string) === '');
}

/// co_isset will return true is string contains data
function co_isset($string)
{
    return (!co_isNullOrEmpry($string));
}

/// Create filter object to filter offerings
function co_createFilter(
    $filterStatus = null,
    $filterType = null,
    $filterLocation = null,
    $filterCategory = null,
    $filterField = null,
    $filterSearch = null,
    $includePromo = true,
    $dynamicItems = null,
    $classFilter = null
) {
    if (!isset($filterSearch)) {
        $filterSearch = "";
    }

    $brand = co_get_option('co_branding', null);

    $filter = array(
        'status' => array_map('trim', co_array_filter( $filterStatus)),
        'type' => array_map('trim', co_array_filter( $filterType)),
        'location' => array_map('trim', co_array_filter(  $filterLocation)),
        'category' => array_map('trim', co_array_filter(  $filterCategory)),
        'field' => array_map('trim', co_array_filter(  $filterField)),
        'search' => $filterSearch,
        'identifier' => $classFilter,
        'includePromo' => $includePromo,
        'dynamicItems' => $dynamicItems,
        'brand' => $brand
    );

    return (json_encode($filter));
}

function co_array_filter( $filter )
{
    if ($filter == null)    {
        return [];
    }
    return array_filter(explode(';', $filter));
}

function co_endsWith($haystack, $needle)
{
    $length = strlen($needle);
    if (!$length) {
        return true;
    }
    return substr($haystack, -$length) === $needle;
}
/// <summary>
/// Produce an SEO friendly string.
/// </summary>
/// <param name="str">The string.</param>
/// <returns></returns>
function co_toSeo($text)
{
    if (!isset($text)) {
        return "";
    }
    $seoText = $text;
    // remove entities
    $seoText = preg_replace("/&\w+;/", "", $seoText);
    // remove anything that is not letters, numbers, dash, or space
    $seoText = preg_replace("/[^A-Za-z0-9\-\s]/", "", $seoText);
    // remove any leading or trailing spaces left over
    $seoText = trim($seoText);
    // replace spaces with single dash
    $seoText = preg_replace("/\s+/", "-", $seoText);
    // if we end up with multiple dashes, collapse to single dash
    $seoText = preg_replace("/\-{2,}/", "-", $seoText);
    // make it all lower case
    $seoText = strtolower($seoText);

    // if it's too long, clip it
    if (strlen($seoText) > 80) {
        $seoText = substr($seoText, 0, 79);
    }
    // remove trailing dash, if there is one
    if (co_endsWith($seoText, '-')) {
        $seoText = substr($seoText, 0, strlen($seoText) - 1);
    }
    return $seoText;
}



