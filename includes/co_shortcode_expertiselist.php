<?php

require_once (plugin_dir_path(__FILE__) . 'co_shortcode.php');
require_once (plugin_dir_path(__FILE__) . 'co_utils.php');
/**
 * co_shortcode_expertiselist generates a list of specialities and the possibility to filter the offerings based on user selection
 *
 * // Shortcode co-expertise-list
 *  [co-expertiselist id="categorylist" extra-options="Nieuw;Populair" extra-labels="Nieuw;Populair" showall-enabled]
 *
 * @version 1.0
 * @author Hans
 */
class CartaOnlineShortcode_expertiseList extends CartaOnlineShortcode
{
    function __construct($atts) {
        parent::__construct($atts);
    }

    public function renderExpertiselist() {
        if ($this->api == null) {
            return $this->GetLastError();
        } else {
    		$data = $this->api -> getexpertiseList();
    		
            $renderedHtml = "<hr />";
            if ($data == null) {
                if ($this -> GetLastError() != null) {
                    $renderedHtml = $this -> GetLastError();
                }
                else {
                    $renderedHtml = __("No data", "carta-online");
                }   
            }
            else {
                $extra_options = $this->attribute('extra-options');
                $extra_labels = $this->attribute('extra-labels');
                
                $extra_options = is_string($extra_options) ? array_filter(explode(';', $extra_options)) : [];
                $extra_labels = is_string($extra_labels) ? array_filter(explode(';', $extra_labels)) : [];
                
                $renderedHtml .= '<form method="post" name="co_expertise_check">';
                
                if (!isset($extra_labels) || !is_array($extra_labels)) {
                    $extra_labels = [];
                }
                if (!isset($extra_options) || !is_array($extra_options)) {
                    $extra_options = [];
                }
                
                if (count($extra_labels) != count($extra_options)) {
                    $renderedHtml .= "<div class='co-error'>" . __("Error in shortcode co_companylist: size of extra labels and options lists differ. Extra labels and options ignored", 'carta-online') . "</div>";
                    $extra_labels = [];
                    $extra_options = [];
                }
                
                $fieldList = [];
                
                
                for( $i=0; $i<sizeof($extra_options); $i++) {
                    $chceked = "";
                    $currentOptie = $extra_options[$i];
                    
                    if ($currentOptie != "?" ) {
                        $checked = $checked = $this -> isChecked('dynamic_' . $extra_options[$i]);
                        if ($checked === "checked") {
                            array_push($fieldList,strtolower($currentOptie));
                        }
                    }
                    $renderedHtml .= $this->checkBox( str_replace(' ', '',strtolower('dynamic_' . $extra_options[$i])), $extra_labels[$i], $checked);
                }
                $renderedHtml .= '<hr />';

                uasort($data, 'co_compareExpertiseValue');

                // Het is mogelijk om alle vakgebieden een 'binaire' filterwaarde mee te geven.
                //  Werd gebruikt door BP om de vakgebieden site-specifiek te maken, maar dat wordt nu nergens meer gebruikt
                $filter = co_get_special_render_option('field-filter', 0);

                foreach($data as $choice) {
                    if ( ($filter == 0) || ( ($choice->filter & $filter) > 0) ) {
                        if ($choice->description != "?") {
                            $checked = $checked = $this -> isChecked(strtolower($choice->description));
                            if ($checked === "checked") {
                                array_push($fieldList,strtolower($choice->description));
                            }
                            $renderedHtml .= $this->checkBox( str_replace(' ', '_',strtolower($choice->description)), $choice->description, $checked);
                        }
                    }
                }
                $_SESSION["co_checkedfields"] = implode(";", $fieldList);

                $renderedHtml .= '</form>';
            }
        }
        $html = '<div ' . $this->renderid() . ' class="co-offer-list">' . $renderedHtml . '</div>';
		return $html;
    }

    function checkBox($fieldname, $label, $checked) {
        $result = '<input type="checkbox" class="co-checkbox" name="' . $fieldname . '" value="' . $fieldname . '" onclick="submit();" ' . $checked .'>' . $label . "<br />";
        return $result;
    }

    public function isChecked($fieldname) {
        global $CO_POST;
        if (isset($CO_POST["dynamic_all"])) {
            return ""; // Emoty all checkboxes, so no filtering is done
        }
        if (isset($CO_POST[str_replace(' ', '_',$fieldname)])) {
            return "checked";
        }
        return "";
    }

}