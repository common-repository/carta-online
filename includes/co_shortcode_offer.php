<?php

require_once (plugin_dir_path(__FILE__) . 'co_shortcode.php');
/**
 * co_shortcode_offer short summary.
 *
 * co_shortcode_offer description.
 *
 * @version 1.0
 * @author Hans
 */
class CartaOnlineShortcode_Offer extends CartaOnlineShortcode
{
    function __construct($atts, $content)
    {
        parent::__construct($atts, $content);
    }

    function createOfferFilter($filter_loc = null, $filter_cat = null, $filter_fld = null)
    {
        // Dynamic items for custom filtering
        //  dynamic-filter="dyn1=1;dyn2=TRUE"
        //

        $dynamicItems = [];
        if ($this->hasAttribute('dynamic-filter')) {
            $dynamicFilterItems = explode(';', $this->attribute('dynamic-filter'));
            foreach ($dynamicFilterItems as $filterItem) {
                if (strpos($filterItem, '=') !== FALSE) {
                    list($k, $v) = explode("=", $filterItem);
                    $dynamicItems[$k] = $v;
                }
            }
        }

        // 
        // Filter based on identifier
        //
        $classFilter = null;
        if ($this->hasAttribute('class')) {
            $classFilter = $this->attribute('class');
        }
        //
        // auto-class attribute will filter on class parameter or variable.
        // can be used on detail pages to filter based on mother class
        //
        if ($this->hasAttribute('auto-class')) {
            $classFilter = co_get_classId();
        }

        // Fields specified in filter
        $fieldFilter = $this->attribute('field');
        if ($this->hasAttribute('filter-by-field')) {
            $fieldFilter = $this->getField();
        }

        //
        // Filter based on fields checked in co_shortcode_expertiselist
        //
        if ($this->hasAttribute('dynamic-fields')) {
            list($fieldFilter, $dynamicItems) = $this->extract_dynamic_items($_SESSION["co_checkedfields"] ?? '', $dynamicItems);
        }
        //
        // No filter set by user. If filter is set in plugin, then filter fields by plugin-setting.
        //

        $filteroption = co_get_special_render_option('field-filter', 0);
        if (!co_isset($fieldFilter) && ($filteroption > 0)) {
            $fields_data = $this->api->getexpertiseList();
            if ($fields_data != null) {
                foreach ($fields_data as $choice) {
                    if (($filteroption == 255) || (($choice->filter & $filteroption) > 0)) {
                        $fieldFilter = co_addOption($fieldFilter, $choice->description, ';');
                    }
                }
            }
        }

        $metaKey = co_get_special_render_option('carta-field-id', 'carta-field-id');
        $post_type = co_get_special_render_option('field-info', 'field-info');

        $post = get_post();
        if (!isset($post)) {
            // $post is leeg. Dat kan alleen als het een ajax call is.
            // Als dat het geval is, kan het zijn dat we van een vakgebied pagina komen.
            // Herstel het vakgebied filter.
            if (isset($_SESSION['CO_AJAX_FIELD'])) {
                $fieldFilter = $_SESSION['CO_AJAX_FIELD'];
            }
        } else {
            // Clear filter on Field
            $_SESSION['CO_AJAX_FIELD'] = null;
            if ($post->post_type == $post_type) {
                $meta = get_post_meta($post->ID, $metaKey);
                if (isset($meta) && (count($meta) > 0)) {
                    $fieldFilter = co_addOption($fieldFilter, $meta[0], ';');
                    // Save Field Filter in a session variable. This enables Ajax support for filtering
                    $_SESSION['CO_AJAX_FIELD'] = $fieldFilter;
                }
            }
        }

        //
        // Filter based on current URL http://blabla.bla.bla/someurl/page_name
        //  will filter content on "page name"
        //
        $searchFilter = $this->attribute('search');
        if ($this->hasAttribute('url-based-filtering')) {
            $searchFilter = co_GetFilterFromURL();
            if ($searchFilter == "\"admin ajax.php\"") {
                // async ajax call. Use previously stored URL
                $searchFilter = $_SESSION['CO_LASTSEARCHURL'] ?? '';
            }
            $_SESSION['CO_LASTSEARCHURL'] = $searchFilter;
        }

        $identifier = $this->attribute("class");
        if (!isset($identifier)) {
            $identifier = $this->attribute("identifier");
        }
        if (!isset($identifier)) {
            $identifier = co_get_classId();
        }

        if ($this->hasAttribute('identifier-filter')) {
            $searchFilter = "identifier=" . $identifier;
        }
        if ($this->hasAttribute('mother-identifier-filter')) {
            $searchFilter = "identifier=" . $identifier;
        }


        $locationFilter = $this->attribute('location');
        $categoryFilter = $this->attribute('category');

        if ($this->hasAttribute('use-filter-selection')) {
            global $CO_POST;

            $filter_loc = $_SESSION['CO_LOCATION'] ?? '';
            $filter_cat = $_SESSION['CO_CATEGORY'] ?? '';
            $filter_fld = $_SESSION['CO_FIELD'] ?? '';

            if (co_isNullOrEmpry($filter_loc)) {
                $filter_loc = (isset($CO_POST["cf-location"]) ? esc_attr($CO_POST["cf-location"]) : '');
            }
            if (co_isNullOrEmpry($filter_cat)) {
                $filter_cat = (isset($CO_POST["cf_category"]) ? esc_attr($CO_POST["cf_category"]) : '');
            }
            if (co_isNullOrEmpry($filter_fld)) {
                $filter_fld = (isset($CO_POST["cf_field"]) ? esc_attr($CO_POST["cf_field"]) : '');
            }
        }
        if (co_isset($filter_loc))
            $locationFilter = $filter_loc;
        if (co_isset($filter_cat))
            $categoryFilter = $filter_cat;
        if (co_isset($filter_fld))
            $fieldFilter = $filter_fld;
        if ($dynamicItems == []) {
            $dynamicItems = null;
        }

        return co_createFilter(
            $this->attribute('status'),
            $this->attribute('type'),
            $locationFilter,
            $categoryFilter,
            $fieldFilter,
            $searchFilter,
            true,
            $dynamicItems,
            $classFilter
        );
    }

    public function renderOfferData()
    {
        if ($this->api == null)
            return $this->GetLastError();

        $maxcount = $this->attribute('max-count');

        if (isset($maxcount) && $this->hasAttribute('filter-by-field'))
            $maxcount = $maxcount + 1;

        $data = $this->api->getOfferings($maxcount, $this->createOfferFilter(), null);
        if ($data == null) {
            return null;
        }


        if ($this->hasAttribute('filter-by-field')) {
            // Remove the currently selected course
            for ($index = 0; $index < sizeof($data); $index++) {
                if ($data[$index]->identifier == $this->classId()) {
                    $elementIndex = $index;
                }
            }
            if (!isset($elementIndex) && (sizeof($data) > 0))
                $elementIndex = sizeof($data) - 1;
            if (isset($elementIndex))
                array_splice($data, $elementIndex, 1);
        }
        return $data;
    }

    public function renderOfferlist()
    {
        if ($this->api == null)
            return $this->GetLastError();

        $data = $this->renderOfferData();

        if (!isset($data) && ($this->GetLastError() != null)) {
            return $this->GetLastError();
        } else {
            $renderedHtml = co_renderOfferings($this->api, $data, $this->attribute('detail'), $this->attribute('template'), $this->content, $this->attribute('submit-caption'), $this->attribute('planning-template'), $this->id, $this->attribute('price-component-template'));
            if ($renderedHtml == "") {
                $renderedHtml = "<article class='co_no_data'>" . __('No offer found', "carta-online") . "</article>";
            }
            $html = '<div ' . $this->renderid() . ' class="co-offer-list">' . $renderedHtml . '</div>';

            return $html;
        }
    }

    public function extract_dynamic_items($fieldFilter, $dynamicItems)
    {
        if (!isset($fieldFilter)) {
            return null;
        }
        $result = [];
        $fields = explode(";", $fieldFilter);
        foreach ($fields as $field) {
            if (co_startsWith($field, "dynamic_")) {
                $dynamicItems[substr($field, 8, strlen($field) - 8)] = "TRUE";
            } else {
                $result[] = str_replace('_', ' ', $field);
            }
        }
        $fields = (implode(";", $result));
        return array($fields, $dynamicItems);
    }
}