<?php

require_once (plugin_dir_path(__FILE__) . 'co_shortcode.php');
/**
 * co_shortcode_html5 short summary.
 *
 * co_shortcode_html5 description.
 *
 * @version 1.0
 * @author Hans
 */
class CartaOnlineShortcode_CheckHtml5 extends CartaOnlineShortcode {
    function __construct($atts) {
        parent::__construct($atts);
    }

    function DoCheck() {
        $textFail = $this->attribute('text-fail',__('Helaas u kunt Carta365 niet gebruiken in deze browser.'));
        $textSuccess = $this->attribute('text-success',__('Gefeliciteerd. Uw browser ondersteund HTML5 en u kunt Carta365 gebruiken in deze browser!'));
        $script = $this->Html5Javascript($textFail,$textSuccess);
        return $script;
    }

    function Html5Javascript($text_fail,$text_success)
    {
            return
"<script type='text/javascript'>
var canvasEl = document.createElement('canvas');
if(!canvasEl.getContext)
{
  document.write('$text_fail');
}
else
{
  document.write('$text_success');
}
</script>";

    }
}

