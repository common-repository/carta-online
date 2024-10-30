<?php

if (!defined('ABSPATH')) {
    //If wordpress isn't loaded load it up.
    include_once '../../../../wp-load.php';
}

require_once(plugin_dir_path(__FILE__) . 'co_utils.php');
require_once(plugin_dir_path(__FILE__) . 'co_widget.php');
require_once(plugin_dir_path(__FILE__) . 'co_shortcode.php');
require_once(plugin_dir_path(__FILE__) . 'co_custom_pages.php');

// Initializing the WordPress engine in a stanalone PHP file
include('../../../../wp-blog-header.php');

header("HTTP/1.1 200 OK"); // Forcing the 200 OK header as WP can return 404 otherwise

$atts = array();
$atts["max-count"] = 999;

$filter = $CO_GET["search"] ?? '';
if (co_isset($filter)) {
    $atts["search"] = $filter;
}

$filter = $CO_GET["type"] ?? '';

if (co_isset($filter)) {
    $atts["type"] = $filter;
} else {
    $atts["type"] = "ClassSingle;ClassDaughter";
}

$filter = $CO_GET["status"] ?? '';

if (co_isset($filter)) {
    $atts["status"] = $filter;
} else {
    $atts["status"] = "Open;AlmostFull";
}

$content = null;

$last_data = co_get_datatable_cache($atts);
try {

    $offerlist = new CartaOnlineShortcode_Offer($atts, $content);

    $data = $offerlist->renderOfferData();
    $return_array = array(); // Initializing the array that will be used for the table

    $row_data = array();

    // $nextStart = array();

    // foreach ($data as $row) {
    //     $mother = $row->motherIdentifier;
    //     $start = $row->dateStart;
    //     $status = $row->status;
    //     if ($mother != null && $status == "Open") {
    //         if (array_key_exists($row->motherIdentifier, $nextStart)) {
    //             if ($start < $nextStart[$row->motherIdentifier]) {
    //                 $nextStart[$row->motherIdentifier] = $start;
    //             }
    //         } else {
    //             $nextStart[$row->motherIdentifier] = $start;
    //         }
    //     }
    // }

    $columns = array();

    $weekdag = array("0" => "zo", "1" => "ma", "2" => "di", "3" => "wo", "4" => "do", "5" => "vr", "6" => "za");

    foreach ($data as $row) {
        $row_data = array();
        foreach ($row as $label => $value) {
            if (is_null($value)) {
                $columns = co_addcol($columns, $label);
                $row_data[$label] = "";
            } else if ($label == "identifier") {
                $columns = co_addcol($columns, $label);
                $row_data[$label] = $value;
                $columns = co_addcol($columns, "planninginfo");
                $planningInfo = get_site_url() . '/' . co_get_special_render_option("planningInfoPage", "planning") . "?class=" . $value;
                $row_data["planninginfo"] = $planningInfo;
            } else if ($label == "dateStart") {
                $columns = co_addcol($columns, $label);
                $row_data[$label] = $value;
                $dt = new DateTime($value);
                $columns = co_addcol($columns, "dagdeel");
                $startuur = (int) ($dt->Format("H"));
                $row_data["dagdeel"] = ($startuur < 12 ? "ochtend" : ($startuur < 17 ? "middag" : "avond"));
                $columns = co_addcol($columns, "weekdag");
                $row_data["weekdag"] = $weekdag[$dt->format('w')] ?? '?';
            } else if ($label == "dynamicItems") {
                foreach ($value as $dynlabel => $dynvalue) {
                    $columns = co_addcol($columns, "_" . $dynlabel);

                    $row_data["_" . $dynlabel] = $dynvalue;
                }
            } else if (is_string($value)) {
                $columns = co_addcol($columns, $label);
                $row_data[$label] = $value;
            } else if (is_array($value)) {
                $columns = co_addcol($columns, $label);
                $row_data[$label] = implode(',', $value);
            } else if (is_bool($value)) {
                $columns = co_addcol($columns, $label);
                $row_data[$label] = $value ? "true" : "false";
            } else if (is_numeric($value)) {
                $columns = co_addcol($columns, $label);
                $row_data[$label] = strval($value);
            } else{
                $columns = co_addcol($columns, $label);
                $row_data[$label] = "?";
            }
        }
        // co_addcol($columns, "nextStart");
        // if (co_isset($row->motherIdentifier) && array_key_exists($row->motherIdentifier, $nextStart)) {
        //     $row_data["nextStart"] = $nextStart[$row->motherIdentifier];
        // }

        // Add row to array
        $return_array[] = $row_data;
    }

    $data_present = 0;
    foreach ($return_array as &$row) {
        foreach ($columns as $column) {
            $data_present = $data_present + 1;
            if (!array_key_exists($column, $row)) {
                $row[$column] = "";
            }
        }
    }
    unset($row); // prevent pass by reference problems

    if ($data_present > 20) {
        $last_data = serialize($return_array);
        co_save_datatable_cache($atts, $last_data);
    } else {
        co_report_error("co_datatables: No data available");
    }

    echo $last_data;
} catch (Exception $e) {
    co_report_error("co_datatables: Error reading API: " . $e->getMessage());
    echo serialize($last_array);
}

function co_save_datatable_cache($atts, $result)
{
    $hash = md5(serialize($atts));
    $filename = ABSPATH . 'wp-content/plugins/carta-online/cache/co_dt_' . $hash . '.cache';
    try {
        $fp = @fopen($filename, 'w');
        if ($fp) {
            fwrite($fp, json_encode($result));
        } else {
            throw new Exception(__("carta-online/cache folder is not writable", "carta-online"));
        }
    } catch (Exception $exception) {
        echo __("Carta Online Plugin configuration error:") . " " . $exception->getMessage();
    }
}

// Get cache. Returns null if cache is invalid (older than grace-period)
function co_get_datatable_cache($atts)
{
    $hash = md5(serialize($atts));
    $filename = ABSPATH . 'wp-content/plugins/carta-online/cache/co_dt_' . $hash . '.cache';
    try {
        if (file_exists($filename)) {
            $json = json_decode(file_get_contents($filename));

            if (isset($json)) {
                // cache is still valid. Return contents.
                return $json;
            }
        }
    } catch (Exception $exception) {
        echo __("Carta Online Plugin configuration error:") . " " . $exception->getMessage();
    }

    return null;
}

function co_addcol($array, $element)
{
    if (!in_array($element, $array)) {
        $array[] = $element;
    }
    return $array;
}


?>