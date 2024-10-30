<?php

require_once (plugin_dir_path(__FILE__) . 'co_shortcode.php');
/**
 * co_shortcode_teacher short summary.
 *
 * co_shortcode_teacher description.
 *
 * @version 1.0
 * @author Hans
 */
class CartaOnlineShortcode_Teacher extends CartaOnlineShortcode {
    function __construct($atts) {
        parent::__construct($atts);
    }

    function renderTeacher() {
        return co_renderTeacher($this->id, $this->attribute('post-id'), $this->attribute('carta-id'));
    }
}

