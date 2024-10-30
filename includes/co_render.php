<?php
/**
 * Renders offerings based on given offerings, template and texts
 * Returns rendered Html.
 *
 * @param array $offerings An array containing the offerigs to be rendered.
 * @param string $detailPageName The page-name of the detail page. Page must exist, or no <a> tag will be rendered
 * @param string $template rendertemplate if different from default rendering e.g. "The page %detailOpen%%thumb%%detailClose%"
 * @param string $submitText Tekst to show on submit-button
 *
 * @return string Rendered Html
 */
function co_renderOfferings(
    $api,
    $offerings,
    $detailPageName = null,
    $template = null,
    $content = null,
    $submitText = null,
    $planningTemplate = null,
    $uniqueId = null,
    $priceComponentTemplate = null
) {
    $html = '';
    if (!isset($uniqueId)) {
        $uniqueId = "";
    }
    global $CO_COOKIE;

    if ($offerings == null) {
        return "<h3>" . __('no data', "carta-online") . "</h3>";
    }
    if (isset($_SESSION["co_test"]) && ($_SESSION["co_test"] == true)) {
        if (co_isset($CO_COOKIE['CO_API_URL']))
            $html = '<h3>' . __('Test mode: ', "carta-online") . $CO_COOKIE['CO_API_URL'] . '</h3>';
    }

    if (isset($planningTemplate)) {
        // Haal detailinformatie uit Carta Online om de planning te vullen.
        $offerings = co_addDetails($offerings);
    }

    if ($template === null) {
        $template = $content;
    } else {
        if ($content !== null) {
            $template = $template . $content;
        }
    }

    if (!isset($detailPageName) || ($detailPageName == "")) {
        $detailPageName = co_get_option('co_detail_pagename');
    }

    $redirect_base = co_get_option('co_detail_redirect_name');

    if (isset($detailPageName) && !($detailPageName == "")) {
        $detailPage = null;
        $query = new WP_Query(
            array(
                'post_type' => 'page',
                'title' => $detailPageName,
                'posts_per_page' => 1,
                'no_found_rows' => true,
                'ignore_sticky_posts' => true,
                'update_post_term_cache' => false,
                'update_post_meta_cache' => false,
            )
        );

        if (!empty($query->post)) {
            $detailPage = $query->post;
        }
        if (!isset($detailPage))
            $detailPage = get_page_by_path($detailPageName);
    }

    foreach ($offerings as $offer) {
        // Handig voor debugging:
        //var_dump($offer);
        $subject = $offer->subject;
        $description = $offer->description;
        if (!isset($subject)) {
            $subject = $description;
        }
        if (!isset($template) || ($template == "")) {
            $template = "%detailOpen%%thumb%%subject%%description%%dateStart%%timeStart%%location%%summary%%detailClose%%submit%";
        }

        $substitutions = [];

        #region fill substitution table
        //
        $substitutions["%identifier%"] = "" . $offer->identifier;
        $detailOpenHtml = "";
        if (isset($detailPage)) {
            if (isset($redirect_base)) {
                $base = co_replace_pagename(get_permalink($detailPage->ID), $redirect_base);
                $detailURL = $base . '/' . $offer->identifier . '/' . co_clean_param($offer->description) . '/';
                $detailOpenHtml = '<a href="' . $detailURL . '">';
            } else {
                $detailURL = get_permalink($detailPage->ID) . '?cn=' . co_clean_param($offer->description) . '&class=' . $offer->identifier;
                $detailOpenHtml = '<a href="' . $detailURL . '">';
            }
        }
        $substitutions["%detailOpen%"] = $detailOpenHtml;

        $fieldOpenHtml = "";
        if (strpos($template, '%field') !== false) {
            if (isset($offer->fields)) {
                $fieldsHtml = "";
                $fieldLinksHtml = "";
                $fieldOpenHtml = "";
                foreach ($offer->fields as $field) {
                    $detailURL = co_getFieldURL($field);
                    if (co_isset($detailURL)) {
                        // subject may contain prefered field. This is a temporary hack for UNLP
                        if (($fieldOpenHtml == "") || ($field == $offer->subject)) {
                            $fieldOpenHtml = '<a href="' . $detailURL . '">';
                        }
                        if ($fieldLinksHtml == "") {
                            $fieldLinksHtml = "<div class='co-offer-fieldlinks'>";
                        }
                        $fieldLinksHtml = $fieldLinksHtml . "<a href='$detailURL'>" . $field . "</a>";
                    }

                    if ($fieldsHtml == "") {
                        $fieldsHtml = "<span class='co-offer-fields'>";
                    } else {
                        $fieldsHtml = $fieldsHtml . ",";
                    }
                    $fieldsHtml = $fieldsHtml . $field;
                }
                if (co_isset($fieldsHtml)) {
                    $fieldsHtml = $fieldsHtml . "</span>";
                }
                if (co_isset($fieldLinksHtml)) {
                    $fieldLinksHtml = $fieldLinksHtml . "</div>";
                }
                $substitutions["%fieldOpen%"] = $fieldOpenHtml;
                $substitutions["%fields%"] = $fieldsHtml;
                $substitutions["%fieldLinks%"] = $fieldLinksHtml;
            }
        }
        $thumbHtml = '<img src="' . $offer->thumbnail . '" alt="thumbnail" /> ';
        $substitutions["%thumb%"] = $thumbHtml;

        $bannerHtml = "";
        if (isset($offer->banner)) {
            $bannerHtml = '<img src="' . $offer->banner . '" alt="banner" /> ';
        }
        $substitutions["%banner%"] = $bannerHtml;

        $infoLinkHtml = "";
        if (isset($offer->infoLink)) {
            $infoLinkHtml = $offer->infoLink;
        }
        $substitutions["%infoLink%"] = $infoLinkHtml;

        $subjectHtml = '<div class="co-offer-subject">' . $offer->subject . '</div>';
        $substitutions["%subject%"] = $subjectHtml;

        $descriptionHtml = '<div class="co-offer-description">' . $offer->description . '</div>';
        $substitutions["%description%"] = $descriptionHtml;

        $seoName = $offer->subject;
        if (!isset($seoName)) {
            $seoName = $offer->description;
        }
        $seoName = co_toSeo($seoName);

        global $wp_locale;
        $dateStartHtml = "";
        $dateStartDayHtml = "";
        $dateStartMonthHtml = "";
        $dateStartYearHtml = "";
        $timeStartHtml = "";
        $timesHtml = "";
        $dateStartYearHtml = "";
        $dateStartDayNameShortHtml = "";
        $dateStartDayNameFullHtml = "";

        if (isset($offer->dateStart)) {
            $dateStartHtml = '<div class="co-offer-next-start-date">' . co_FormatDate($offer->dateStart) . '</div>';
            $dateStartDayHtml = '<span class="co-offer-datestartday">' . wp_date("j", strtotime($offer->dateStart)) . '</span>';
            $dateStartMonthHtml = '<span class="co-offer-datestartmonth">' . $wp_locale->get_month(wp_date("m", strtotime($offer->dateStart))) . '</span>';
            $dateStartYearHtml = '<span class="co-offer-datestartyear">' . wp_date("Y", strtotime($offer->dateStart)) . '</span>';
            $timeStartHtml = '<div class="co-offer-next-start-time">' . date_i18n(get_option('time_format'), strtotime($offer->dateStart)) . '</div>';
            $timesHtml = '<div class="co-offer-next-start-time">' . date_i18n(get_option('time_format'), strtotime($offer->dateStart)) . ' - ';

            $dateStartDayNameShortHtml = '<span class="co-offer-datestartdaynameshort">' . co_formatDateDayNameShort($offer->dateStart) . '</span>';
            $dateStartDayNameFullHtml = '<span class="co-offer-datestartdaynamefull">' . co_formatDateDayNameFull($offer->dateStart) . '</span>';
        }

        $substitutions["%dateStartDayNameShort%"] = $dateStartDayNameShortHtml;
        $substitutions["%dateStartDayNameFull%"] = $dateStartDayNameFullHtml;

        $substitutions["%timeStart%"] = $timeStartHtml;
        $substitutions["%dateStart%"] = $dateStartHtml;
        $substitutions["%dateStartDay%"] = $dateStartDayHtml;
        $substitutions["%dateStartMonth%"] = $dateStartMonthHtml;
        $substitutions["%dateStartYear%"] = $dateStartYearHtml;
        $dateEndHtml = "";
        $timeEndHtml = "";
        if (isset($offer->dateEnd)) {
            $dateEndHtml = '<div class="co-offer-next-end-date">' . co_FormatDate($offer->dateEnd) . '</div>';
            $timeEndHtml = '<div class="co-offer-next-end-time">' . date_i18n(get_option('time_format'), strtotime($offer->dateEnd)) . '</div>';
            $timesHtml = $timesHtml . date_i18n(get_option('time_format'), strtotime($offer->dateEnd)) . '</div>';
        }
        $substitutions["%timeEnd%"] = $timeEndHtml;
        $substitutions["%dateEnd%"] = $dateEndHtml;
        $substitutions["%times%"] = $timesHtml;

        $substitutions["%seoName%"] = $seoName;

        $fieldsHtml = "";
        if (isset($offer->fields)) {
            $fieldsHtml = '<div class="co-offer-fields">' . implode(" - ", $offer->fields) . '</div>';
        }
        $substitutions["%fields%"] = $fieldsHtml;

        $locationHtml = "";
        if (isset($offer->location)) {
            $locationHtml = '<div class="co-offer-location">' . implode(" - ", $offer->location) . '</div>';
        }
        $substitutions["%location%"] = $locationHtml;

        $summaryHtml = "";
        if (isset($offer->summary)) {
            $summaryHtml = '<div class="co-offer-summary">' . $offer->summary . '</div>';
        }
        $substitutions["%summary%"] = $summaryHtml;

        $categoryHtml = "";
        if (isset($offer->category)) {
            $categoryHtml = '<div class="co-offer-category">' . $offer->category . '</div>';
        }
        $substitutions["%category%"] = $categoryHtml;

        $studyLoadTotalHtml = "";
        if (isset($offer->studyLoadTotal)) {
            $studyLoadTotalHtml = '<div class="co-offer-studyload-total">' . $offer->studyLoadTotal . '</div>';
        }
        $substitutions["%studyLoadTotal%"] = $studyLoadTotalHtml;

        $studyLoadPerWeekHtml = "";
        if (isset($offer->studyLoadPerWeek)) {
            $studyLoadPerWeekHtml = '<div class="co-offer-studyload-per-week">' . $offer->studyLoadPerWeek . '</div>';
        }
        $substitutions["%studyLoadPerWeek%"] = $studyLoadPerWeekHtml;

        $detailCloseHtml = "";
        if (isset($detailPage)) {
            $detailCloseHtml = '</a>';
        }
        $substitutions["%detailClose%"] = $detailCloseHtml;

        $fieldCloseHtml = "";
        if (isset($fieldOpenHtml) && ($fieldOpenHtml !== "")) {
            $fieldCloseHtml = '</a>';
        }
        $substitutions["%fieldClose%"] = $fieldCloseHtml;

        if (!isset($submitText) || ($submitText == "")) {
            $submitText = __("Subscribe", "carta-online");
        }
        $submitHtml = '<div class="co-offer-submit">' . co_subscribeURL($offer->identifier, $submitText, $offer->dynamicItems) . '</div>';
        $substitutions["%submit%"] = $submitHtml;

        if (strpos($template, '%detail_') !== false) {
            $detailInfo = $api->getOfferDetail($offer->identifier);
            // $api->get
            if (strpos($template, 'Teacher') !== false) {
                $substitutions["%detail_MainTeacher%"] = co_render_mainTeacher($api, $detailInfo->result, $uniqueId);
                $substitutions["%detail_Teacherlist%"] = co_render_teacherList($api, $detailInfo->result, $uniqueId);
            }
            $substitutions["%detail_ContactDays%"] = co_render_contactDays($detailInfo->result, $uniqueId);

            if ((strpos($template, 'Price') !== false) || (strpos($template, 'Components') !== false)) {
                $priceComponentInfo = co_render_priceComponents($api, $detailInfo->result, $uniqueId, $priceComponentTemplate);
                $substitutions["%detail_Price%"] = co_render_price($priceComponentInfo, $uniqueId, 0);
                // Check minimal version requirement
                $minVersion = '24.4.0.0';
                $newPortal = version_compare($api->getPortalVersion(), $minVersion, ">=");
                if ($newPortal) {
                    $substitutions["%detail_OfferPrice%"] = co_render_price($priceComponentInfo, $uniqueId, 1);
                    $substitutions["%detail_CalculatedPrice%"] = co_render_price($priceComponentInfo, $uniqueId, 2);
                    $substitutions["%detail_TotalPrice%"] = co_render_price($priceComponentInfo, $uniqueId, 3);
                } else {
                    $substitutions["%detail_OfferPrice%"] = "<span>" . __('detail_OfferPrice not available. Please upgrade portal.', 'carta-online') . "</span>";
                    $substitutions["%detail_CalculatedPrice%"] = "<span>" . __('detail_CalculatedPrice not available. Please upgrade portal.', 'carta-online') . "</span>";
                    $substitutions["%detail_TotalPrice%"] = "<span>" . __('detail_TotalPrice not available. Please upgrade portal.', 'carta-online') . "</span>";
                }
                $substitutions["%detail_PriceComponents%"] = $priceComponentInfo["unconditionalComponents"];
                $substitutions["%detail_CondComponents%"] = $priceComponentInfo["conditionalComponents"];
                $substitutions["%detail_CondModules%"] = $priceComponentInfo["conditionalModules"];

                $substitutions["%detail_PriceInfo%"] = co_render_priceInfo($detailInfo->result, $uniqueId);
            }
        }
        #endregion

        if (isset($planningTemplate)) {
            $planningHtml = co_render_planning($offer, $planningTemplate, $uniqueId);
            $substitutions["%planning%"] = $planningHtml;
        }

        $articleOpenHtml = '<article id="co-offer-' . $offer->identifier . '" class="co-offer-item co-offer-item-cat-' . str_replace(' ', '-', strtolower($offer->category)) . ' co-offer-type-' . str_replace(' ', '-', strtolower($offer->type)) . ' co-offer-status-' . str_replace(' ', '-', strtolower($offer->status)) . '"> ';
        // Dubbele substitutie geeft meer mogelijkheden.
        $html .= strtr($articleOpenHtml . strtr($template, $substitutions) . '</article>', $substitutions);
    }
    return $html;
}

function co_get_name($api, $id)
{
    $resultaat = ($api->getPerson($id));

    return $resultaat->result->nameComplete;
}

function co_render_teachers($api, $teachers)
{
    $html = '';
    if (is_array($teachers)) {
        foreach ($teachers as $teacher) {
            if ($teacher->isOption === false) {
                $profileurl = co_getProfile($teacher->id, true);
                if (co_isset($profileurl)) {
                    $html = $html . '<span><a href="' . $profileurl . '">' . co_get_name($api, $teacher->id) . '</a></span>';
                } else {
                    $html = $html . '<span>' . co_get_name($api, $teacher->id) . '</span>';
                }
            }
        }
    }
    return $html;
}

function co_render_mainTeacher($api, $data, $uniqueId)
{
    $teacherList = $api->getTeacherlist(5, $data->motherIdentifier);
    if ($teacherList != null) {
        $result = co_render_teachers($api, $teacherList->result);
        if ($result != "") {
            return '<div class="co-offer-main-teacher ' . $uniqueId . '" >' . $result . '</div>';
        }
    }
    return "";
}

function co_compare_order($a, $b)
{
    if ($a->order == $b->order) {
        return 0;
    }
    if ($a->order == $b->order) {
        return 1;
    }
    return -1;
}

function co_render_priceComponents($api, $data, $uniqueId, $priceComponentTemplate)
{
    $retval["price"] = $data->price;               // 0 == price
    $retval["calculatedPrice"] = $data->price;     // 1 == calculatedPrice
    $retval["totalPrice"] = $data->price;          // 2 == totalPrice
    $retval["hasReducedPrice"] = false;            // 3 == hasReducedPrice
    $retval["unconditionalComponents"] = "";       // unconditionalComponents
    $retval["conditionalComponents"] = "";         // conditionalComponents
    $retval["conditionalModules"] = "";            // conditionalModules

    if (isset($data->priceComponents)) {
        usort($data->priceComponents, "co_compare_order");
        $result = "";
        $resultUncond = "";
        $resultCond = "";
        $resultCondModules = "";
        foreach ($data->priceComponents as $priceComponent) {
            $isConditional = co_isset($priceComponent->conditionField) || co_isset($priceComponent->conditionValue);
            $isModule = false;
            if (isset($priceComponent->classModuleId)) {
                if (isset($priceComponent->isMandatory)) {
                    $isConditional = !$priceComponent->isMandatory;
                }
                $isModule = true;
            }
            $result = $resultUncond;
            if ($isConditional) {
                if ($isModule) {
                    $result = $resultCondModules;
                } else {
                    $result = $resultCond;
                }
            }
            if ($result == "") {
                $result = '<div class="co-pricecomponents">';
            }
            $text = '<span class="co-pc-description">' . $priceComponent->description . '</span>';
            $prefix = "";
            $money = "";
            $date = "";
            $validTill = "";

            $pc_template = co_get_special_render_option("pc-template", "tmpd");
            if (isset($priceComponent->dateValidTill)) {
                $prefix = '<span class="co-pc-valid-till-prefix">' . __('before ', "carta-online") . '</span>';
                $validTill = '<span class="co-pc-valid-till">' . co_FormatDate($priceComponent->dateValidTill) . '</span>';
            }
            if ($priceComponent->type == "DiscountPercentage") {
                $money = '<span class="co-pc-value-percentage">' . $priceComponent->value . '%</span>';
                if (!$isConditional) {
                    $retval["calculatedPrice"] = $retval["calculatedPrice"] - ($data->price * ($priceComponent->value) / 100);
                    $retval["hasReducedPrice"] = true;
                }
            }
            if ($priceComponent->type == "SurchargePercentage") {
                $money = '<span class="co-pc-value-percentage">' . $priceComponent->value . '%</span>';
                if (!$isConditional) {
                    $retval["calculatedPrice"] = $retval["calculatedPrice"] + ($data->price * ($priceComponent->value) / 100);
                    $retval["totalPrice"] = $retval["totalPrice"] + ($data->price * ($priceComponent->value) / 100);
                }
            }
            if ($priceComponent->type == "DiscountAmount") {
                $money = '<span class="co-pc-value-amount">' . co_render_amount($priceComponent->value) . '</span>';
                if (!$isConditional) {
                    $retval["calculatedPrice"] = $retval["calculatedPrice"] - ($priceComponent->value);
                    $retval["hasReducedPrice"] = true;
                }
            }
            if ($priceComponent->type == "SurchargeAmount") {
                $money = '<span class="co-pc-value-amount">' . co_render_amount($priceComponent->value) . '</span>';
                if (!$isConditional) {
                    $retval["calculatedPrice"] = $retval["calculatedPrice"] + ($priceComponent->value);
                    $retval["totalPrice"] = $retval["totalPrice"] + +($priceComponent->value);
                }
            }

            if ($priceComponentTemplate == null) {
                foreach (str_split($pc_template) as $template_char) {
                    // t = text
                    // m = money/percentage
                    // p = prefix (before )
                    // d = valid till
                    if ($template_char == "t") {
                        $result = $result . ($result === "" ? '' : ' ') . $text;
                    } else if ($template_char == "m") {
                        $result = $result . ($result === "" ? '' : ' ') . $money;
                    } else if ($template_char == "p") {
                        $result = $result . ($result === "" ? '' : ' ') . $prefix;
                    } else if ($template_char == "d") {
                        $result = $result . ($result === "" ? '' : ' ') . $validTill;
                    } else {
                        $result = $result . $template_char;
                    }
                }
            } else {
                // template based substitution
                $subst = [];
                $subst["%text%"] = $text;
                $subst["%money%"] = $money;
                $subst["%prefix%"] = $prefix;
                $subst["%validTill%"] = $validTill;
                $result = $result . '<div class="co-pricecomponent">' . strtr($priceComponentTemplate, $subst) . '</div>';
            }

            if ($isConditional) {
                if ($isModule) {
                    $resultCondModules = $result;
                } else {
                    $resultCond = $result;
                }
            } else {
                $resultUncond = $result;
            }
        }
        if ($resultUncond != "") {
            $resultUncond = $resultUncond . "</div>";
        }
        if ($resultCond != "") {
            $resultCond = $resultCond . "</div>";
        }
        if ($resultCondModules != "") {
            $resultCondModules = $resultCondModules . "</div>";
        }
        $retval["unconditionalComponents"] = $resultUncond;
        $retval["conditionalComponents"] = $resultCond;
        $retval["conditionalModules"] = $resultCondModules;
    }
    return $retval;
}

function co_render_teacherList($api, $data, $uniqueId)
{
    $teacherList = $api->getTeacherlist(5, $data->identifier);
    $result = "";
    if (count($teacherList->result) > 0) {
        $html = co_render_teachers($api, $teacherList->result);
        $buttonId = "co-offer-teacherlist-btn-$uniqueId" . $data->identifier;
        $dataDivId = "co-offer-teacherlist-data-$uniqueId" . $data->identifier;
        // function-name
        $buttonIdClick = "teacherlist" . str_ireplace("-", "_", $uniqueId) . "_" . $data->identifier;
        $result = co_render_expand_collapse_script("teacherlist", $buttonId, $buttonIdClick, $dataDivId, $html);
    } else {
        $result = "";
    }
    return $result;
}

function co_render_priceInfo($data, $uniqueId)
{
    $html = $data->priceDescription;

    $buttonId = "co-offer-priceinfo-btn-" . $uniqueId . "-" . $data->identifier;
    $dataDivId = "co-offer-priceinfo-data-" . $uniqueId . $data->identifier;
    // function-name
    $buttonIdClick = "priceinfo" . str_ireplace("-", "_", $uniqueId) . "_" . $data->identifier;
    $result = co_render_expand_collapse_script("priceinfo", $buttonId, $buttonIdClick, $dataDivId, $html);
    return $result;

}

function co_render_amount($value)
{
    $sign = co_get_special_render_option("money-sign", "€ ");
    $decimals = (int) co_get_special_render_option("money-decimals", 2);
    $thousandSeparator = co_get_special_render_option("money-thousand-separator", null);
    $decimalSeparator = co_get_special_render_option("money-decimal-separator", null);

    if (isset($thousandSeparator) && isset($decimalSeparator)) {
        return $sign . number_format($value, $decimals, $decimalSeparator, $thousandSeparator);
    }

    return $sign . number_format($value, $decimals);
}

function co_render_price($priceComponentInfo, $uniqueId, $mode)
{
    // Base CSS class and initial content based on the mode
    $baseCssClass = 'co-offer-price';
    $contentKey = 'price';

    // rendering based on mode
    switch ($mode) {
        case 0:
            // legacy
            if ($priceComponentInfo["hasReducedPrice"] === true) {
                $html = '<div class="co-offer-strikeout ' . $uniqueId . '">' .
                co_render_amount($priceComponentInfo["price"]) . '</div>' .
                    '<div class="co-offer-price ' . $uniqueId . '">' .
                co_render_amount($priceComponentInfo["calculatedPrice"]) . '</div>';
                return $html;
            }
            break;
        case 1:
            $baseCssClass = 'co-offer-offerprice';
            break;
        case 2:
            $baseCssClass = 'co-offer-calculatedprice';
            $contentKey = 'calculatedPrice';
            break;
        case 3:
            $baseCssClass = 'co-offer-totalprice';
            $contentKey = 'totalPrice';
            break;
    }

    // Default return if no specific conditions are met
    return '<div class="' . $baseCssClass . ' ' . $uniqueId . '">' .
    co_render_amount($priceComponentInfo[$contentKey]) . '</div>';
}

function co_render_contactDays($data, $uniqueId)
{
    $minContact = 9999999;
    $maxContact = 0;
    // Reken uit hoeveel contactdagen er zijn
    foreach ($data->instances as $instance) {
        $contactDays = [];
        foreach ($instance->planning as $meeting) {
            $dateinfo = substr($meeting->dateStart, 0, 10);
            if (!array_key_exists($dateinfo, $contactDays)) {
                $contactDays[$dateinfo] = 1;
            }
        }
        $contacts = count($contactDays);
        if ($contacts > $maxContact)
            $maxContact = $contacts;
        if ($contacts < $minContact)
            $minContact = $contacts;
    }
    $contactDays = "";
    if ($maxContact > 0) {
        if ($maxContact == $minContact) {
            if ($maxContact == 1) {
                $contactDays = $maxContact . ' ' . __('day', "carta-online");
            } else {
                $contactDays = $maxContact . ' ' . __('days', "carta-online");
            }
        } else {
            $contactDays = $minContact . ' - ' . $maxContact . ' ' . __('days', "carta-online");
        }

    }
    return '<div class="co-offer-duration ' . $uniqueId . '">' . $contactDays . '</div>';
}

function co_render_planning($offer, $template, $uniqueId = null)
{
    if (!isset($uniqueId))
        $uniqueId = "";

    $planningHtml = "";
    if (!isset($offer->planning)) {
        $planningHtml = __('No dates planned', 'carta-online');
    } else {
        foreach ($offer->planning as $planningsItem) {
            $substitutions = [];

            $subjectHtml = '<span class="co-offer-planning-subject">' . $planningsItem->subject . '</span>';
            $substitutions["%subject%"] = $subjectHtml;

            $dateStartHtml = "";
            $dateStartDayNameShortHtml = "";
            $dateStartDayNameFullHtml = "";

            $timeStartHtml = "";
            if (isset($planningsItem->dateStart)) {
                $dateStartHtml = '<span class="co-offer-planning-start-date">' . co_FormatDate($planningsItem->dateStart) . '</span>';
                $timeStartHtml = '<span class="co-offer-planning-start-time">' . date_i18n(get_option('time_format'), strtotime($planningsItem->dateStart)) . '</span>';

                $year = date("Y", strtotime($planningsItem->dateStart));
                $month = date("m", strtotime($planningsItem->dateStart));
                $day = date("d", strtotime($planningsItem->dateStart));

                $isUnknown = co_get_special_render_option("dateunknownvalue", "");
                if (($day . "-" . $month) == $isUnknown) {
                    $isUnknownText = __('Dates in YYYY will be announced later', "carta-online");  // Data in 2022 worden nog nader bekend gemaakt!
                    $isUnknownText = str_replace("YYYY", $year, $isUnknownText);

                    $dateStartHtml = $isUnknownText;
                } else {
                    $dateStartDayNameShortHtml = '<span class="co-offer-planning-datestartdaynameshort">' . co_formatDateDayNameShort($planningsItem->dateStart) . '</span>';
                    $dateStartDayNameFullHtml = '<span class="co-offer-planning-datestartdaynamefull">' . co_formatDateDayNameFull($planningsItem->dateStart) . '</span>';
                }
            }

            $substitutions["%timeStart%"] = $timeStartHtml;
            $substitutions["%dateStart%"] = $dateStartHtml;

            $substitutions["%dateStartDayNameShort%"] = $dateStartDayNameShortHtml;
            $substitutions["%dateStartDayNameFull%"] = $dateStartDayNameFullHtml;

            $dateEndHtml = "";
            $timeEndHtml = "";
            if (isset($planningsItem->dateEnd)) {
                $dateEndHtml = '<span class="co-offer-planning-end-date">' . co_FormatDate($planningsItem->dateEnd) . '</span>';
                $timeEndHtml = '<span class="co-offer-planning-end-time">' . date_i18n(get_option('time_format'), strtotime($planningsItem->dateEnd)) . '</span>';
            }
            $substitutions["%timeEnd%"] = $timeEndHtml;
            $substitutions["%dateEnd%"] = $dateEndHtml;

            $locationHtml = "";
            if (isset($planningsItem->location)) {
                $locationHtml = '<span class="co-offer-planning-location">' . $planningsItem->location . '</span>';
            }
            $substitutions["%location%"] = $locationHtml;

            $moduleHtml = "";
            if (isset($planningsItem->module)) {
                $moduleHtml = '<span class="co-offer-planning-module">' . $planningsItem->module . '</span>';
            }
            $substitutions["%module%"] = $moduleHtml;

            // Dubbele substitutie geeft meer mogelijkheden.
            $planningHtml .= strtr($template, $substitutions);
        }
    }

    $buttonId = "co-offer-planning-btn-" . $uniqueId . "-" . $offer->identifier;
    $dataDivId = "co-offer-planning-data-" . $uniqueId . $offer->identifier;
    // function-name
    $buttonIdClick = "co_offer_planning_btn_clk_" . str_ireplace("-", "_", $uniqueId) . "_" . $offer->identifier;
    $result = co_render_expand_collapse_script("planning", $buttonId, $buttonIdClick, $dataDivId, $planningHtml);
    return $result;
}

function co_render_expand_collapse_script($module, $buttonId, $buttonIdClick, $dataDivId, $html)
{
    $expand_text = co_get_special_render_option($module . '-expand-text', '+');
    $collapse_text = co_get_special_render_option($module . '-collapse-text', '-');
    if ($html == "") {
        $html = __('No information', 'carta-online');
    }
    $outerclass = "co-offer-$module-detail";
    $result = "<div class=\"$outerclass co-up\" >     
 <div id=\"$buttonId\" class=\"co-offer-$module-button\" onclick=\"$buttonIdClick();\">$expand_text</div>
 <div id=\"$dataDivId\" class=\"co-offer-$module-data\" style=\"display:none\"> 
 $html
 </div></div>
 <script type=\"text/javascript\">
  function $buttonIdClick() {
  if (document.getElementById(\"$dataDivId\").style.display == \"none\") {
		document.getElementById(\"$dataDivId\").style.display = \"\";
		document.getElementById(\"$buttonId\").innerHTML = \"$collapse_text\";
		document.getElementById(\"$buttonId\").classList.add(\"co-expanded\");
		document.getElementById(\"$buttonId\").classList.remove(\"co-collapsed\");
	} else {
		document.getElementById(\"$dataDivId\").style.display = \"none\";
		document.getElementById(\"$buttonId\").innerHTML = \"$expand_text\";
		document.getElementById(\"$buttonId\").classList.add(\"co-collapsed\");
		document.getElementById(\"$buttonId\").classList.remove(\"co-expanded\");
	}
  };
  </script>";
    return $result;
}
//

///
/// renderTeacher
///  <div class='co_teacher' id='$id'>
///     In case postID is defined, retrieve contents from standaard post
///     In case cartaID is defined, retrieve contents from special post 'teacher-profile' based on meta-key carta-teacher-id
///  </div>
function co_renderTeacher($id = null, $postID = null, $cartaID = null)
{

    $content = '<div class=\'co-teacher\' ';
    if (isset($id)) {
        $content = $content . 'id=\'' . $id . '\' ';
    }
    $content .= '>';
    if (isset($cartaID)) {
        $content .= co_getProfile($cartaID);
    }
    if (isset($postID)) {
        $post = get_post($postID, ARRAY_A);
        if (isset($post)) {
            $content .= do_shortcode($post['post_content']);
        } else {
            $content .= "<span class='co-error'>" . __("Teacher with postID", "carta-online") . " " . $postID . " " . __("not found.", "carta-online") . "</span>";
        }
    }
    $content .= '</div>';
    return $content;
}


///
/// Retrieve special post (teacher-profile) content base on meta-tag carta-teacher-id
///
function co_getProfile($cartaID, $render_link = false)
{
    // find any page with teacher information. If no page is found, an error is returned
    $metaKey = co_get_special_render_option('carta-teacher-id', 'carta-teacher-id');
    $post_type = co_get_special_render_option('teacher-profile', 'teacher-profile');

    $args = array(
        'posts_per_page' => 1,
        'offset' => 0,
        'meta_key' => $metaKey,
        'meta_value' => $cartaID,
        'post_status' => 'publish',
        'suppress_filters' => true,
        'post_type' => $post_type
    );
    $posts_array = get_posts($args);
    $content = "";
    if ($render_link === false) {
        if (isset($posts_array) && (count($posts_array) > 0)) {
            foreach ($posts_array as $post) {
                $content .= do_shortcode($post->post_content);
            }
        } else {
            $content .= "<span class='co-error'>" . __("Teacher profile with cartaID", "carta-online") . " " . $cartaID . " " . __("not found.", "carta-online") . "</span>";
        }
    } else {
        if (isset($posts_array) && (count($posts_array) > 0)) {
            foreach ($posts_array as $post) {
                $content = get_post_permalink($posts_array[0]->ID);
            }
        }
    }

    return $content;
}

///
/// Retrieve special post (field-info) content base on meta-tag carta-field-id
///
function co_getFieldURL($field)
{
    // find any page with field information. If no page is found, an empty string is returned
    $metaKey = co_get_special_render_option('carta-field-id', 'carta-field-id');
    $post_type = co_get_special_render_option('field-info', 'field-info');

    $args = array(
        'posts_per_page' => 1,
        'offset' => 0,
        'meta_key' => $metaKey,
        'meta_value' => $field,
        'post_status' => 'publish',
        'post_type' => $post_type,
        'suppress_filters' => true
    );
    $posts_array = get_posts($args);
    // Check if a post for the field type exists;
    if (isset($posts_array) && (count($posts_array) > 0)) {
        return get_post_permalink($posts_array[0]->ID);
    }
    return "";
}

///
/// renderContact
///  <div class='co_Contact' id='$id'>
///     In case postID is defined, retrieve contents from standaard post
///     In case cartaID is defined, retrieve contents from special post 'Contact-profile' based on meta-key carta-Contact-id
///  </div>
function co_renderContact($id = null, $postID = null, $cartaID = null)
{
    $content = '<div class=\'co-Contact\' ';
    if (isset($id)) {
        $content = $content . 'id=\'' . $id . '\' ';
    }
    $content .= '>';
    if (isset($cartaID)) {
        $content .= co_getProfile($cartaID);
    }
    if (isset($postID)) {
        $post = get_post($postID, ARRAY_A);
        if (isset($post)) {
            $content .= do_shortcode($post['post_content']);
        } else {
            $content .= "<span class='co-error'>" . __("Contact with postID", "carta-online") . " " . $postID . " " . __("not found.", "carta-online") . "</span>";
        }
    }
    $content .= '</div>';
    return $content;
}

///
/// Maak een lijst met publieke documenten aan
///
function co_render_documentlist($identifier, $elementType, $linkCaption, $numberOfDocuments)
{
    $data = (new CartaOnlineApi())->getDocuments($identifier);
    if (!isset($data) || sizeof($data->result) == 0) {
        return "";  // No content rendered
    }
    $contentCount = 0;
    $content = "<" . $elementType . " id=documentlist>";
    foreach ($data->result as $document) {
        if ($numberOfDocuments == 0 || $contentCount < $numberOfDocuments) {
            $content = $content . "<a href=" . $document->url . ">";
            if (isset($linkCaption) && ($linkCaption <> "")) {
                $content = $content . $linkCaption;
            } else {
                if (isset($document->description)) {
                    $content = $content . $document->description;
                } else {
                    $content = $content . $document->url;
                }
            }
            $content = $content . "</a>";
        }
        $contentCount = $contentCount + 1;
    }
    $content = $content . "</" . $elementType . ">";
    return $content;
}

/// Add detail information to all offerable items (daughters and singles) of given offerlist.
function co_addDetails($data)
{
    $result = [];
    if (!isset($data))
        return $data;
    
    foreach ($data as $offer) {
        if (isset($offer)) {
            // Alleen van uitvoeringen kan een zinvolle planning opgehaald worden
            if (($offer->type == "ClassDaughter") || ($offer->type == "ClassSingle")) {
                $details = (new CartaOnlineApi())->getOfferDetail($offer->identifier);
                if (isset($details->result) && isset($details->result->instances) && isset($details->result->instances[0]->planning))
                    $offer->planning = $details->result->instances[0]->planning;
            }
            array_push($result, $offer);
        }
    }
    return $result;
}

