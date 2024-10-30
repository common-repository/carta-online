<?php
require_once (plugin_dir_path(__FILE__) . 'co_api.php');
require_once (plugin_dir_path(__FILE__) . 'co_render.php');

add_action('widgets_init', 'co_load_widget');
function co_load_widget() {
	register_widget('co_widget');
    // wp_register_style() example
    wp_register_style(
        'co-widget-extension', // handle name
        get_template_directory_uri() . '/css/style.css'  );
}

class co_widget extends WP_Widget {

	function __construct() {
		parent::__construct('co_widget', __('Carta Online'), array('description' => __('Carta Online small offerings widget', 'carta-online')));
	}

	public function widget($args, $instance) {
        try {
            $title = apply_filters('widget_title', $instance['title'] ?? '');

            echo $args['before_widget'] ?? '';

            $searchFilter = "";
            if (isset($instance['url_based_filter']) && ($instance['url_based_filter']=='on')) {
                $searchFilter = co_GetFilterFromURL();
            }

            $filter = co_createFilter(  $instance['status_filter'] ?? null,
                                        $instance['type_filter'] ?? null,
                                        $instance['location_filter'] ?? null,
                                        $instance['category_filter'] ?? null,
                                        $instance['field_filter'] ?? null,
                                        $searchFilter );

            $co_api = new CartaOnlineApi();
            $data = $co_api -> getOfferings($instance['number_of_items'] ?? '0', $filter);

            echo '<div id="' . ($instance['widget_id'] ?? '') . '">';
            if (!empty($title))
                echo $args['before_title'] . $title . $args['after_title'];

                echo co_renderOfferings( $co_api, $data, $instance['detail_page']  ?? null, $instance['template'] ?? null,
                 null, $instance['submit_caption'] ?? null, $instance['planning_template'] ?? null, $instance['widget_id'] ?? null) . '</div>';


            echo $args['after_widget'] ?? '';
        }
        catch(Exception $e) {
            echo "<div class='co-error'> Error: " . $e->getMessage() . "</div>";
        }
	}

    protected function paragraaf($content) {
        return '<p>' . $content . '</p>';
    }

    protected function label($fieldname, $content, $hint = "") {
        return '<label for="' . $this->get_field_id($fieldname) . '">' . $content . '</label>' . $hint ;
    }

    protected function hint($hinttext) {
        // Ik krijg die style sheet hier niet aan de praat.
        return '<span class="co-small" style="font-size:80%">' . $hinttext .  '</span>';
    }

    protected function textField($fieldname, $fieldValue) {
        return '<input class="widefat" id="' .  $this->get_field_id($fieldname) . '" name="' .
                $this->get_field_name($fieldname) . '" type="text" value="' . attribute_escape($fieldValue) . '" />';
    }

    protected function checkboxField($fieldname, $fieldValue) {
        $checkedState =  (esc_attr($fieldValue));
        return '<input id="' .  $this->get_field_id($fieldname) . '" name="' .
                $this->get_field_name($fieldname) . '" type="checkbox" ' . checked( $checkedState, 'on', false) . ' />';
    }

	public function form($instance) {
        $instance = wp_parse_args( (array)$instance, array('title' => '', 'number_of_items' => '5', 'widget_id' => '') );
		$title = strip_tags($instance['title'] ?? '');
		$number_of_items = $instance['number_of_items'] ?? 10;
		$status_filter = $instance['status_filter'] ?? 'open';
		$type_filter = $instance['type_filter'] ?? '';
		$location_filter = $instance['location_filter'] ?? '';
		$category_filter = $instance['category_filter'] ?? '';
        $field_filter = $instance['field_filter'] ?? '';
        $url_based_filter = $instance['url_based_filter'] ?? '';
		$widget_id = $instance['widget_id'] ?? '';
		$detail_page= $instance['detail_page'] ?? '';
        $template = $instance['template'] ?? '';
        $submit_caption = $instance['submit_caption'] ?? '';
        $planning_template = $instance['planning_template'] ?? '';

        echo '<h4>' . __('General','carta-online') . '</h4>';

        echo $this->Paragraaf($this->label('title', __('Title:','carta-online') . $this->textField('title', $title) ));

        echo $this->Paragraaf($this->label('number_of_items', __('Number of items to show:','carta-online') . $this->textField('number_of_items', $number_of_items)));

        echo $this->Paragraaf($this->label('widget_id', __('Widget ID:','carta-online') . $this->textField('widget_id', $widget_id)));

        echo $this->Paragraaf($this->label('detail_page', __('Detail page:','carta-online') . $this->textField('detail_page', $detail_page)));

        echo '<hr />' . '<h4>' . __('Filter','carta-online') . '</h4>';

        echo $this->Paragraaf($this->label('status_filter', __('Status:','carta-online') . $this->textField('status_filter', $status_filter) .
               $this->hint( __('Available:','carta-online') . '<em>' .  __('canceled; finished; started; closed; almostfull; full; open','carta-online') . '</em>')));

        echo $this->Paragraaf($this->label('type_filter', __('Type:','carta-online') . $this->textField('type_filter', $type_filter) ,
               $this->hint( __('Available:','carta-online') . '<em>' . __('ClassDaughter; ClassMother; ClassSingle; CourseSingle;','carta-online') . '</em>' )));

        echo $this->Paragraaf($this->label('location_filter', __('Location:','carta-online') . $this->textField('location_filter', $location_filter) ,
               $this->hint( __('Enter locations seperated by a \';\'. Locations are dynamic, please refer to Carta for an overview.','carta-online'))));

        echo $this->Paragraaf($this->label('category_filter', __('Category:','carta-online') . $this->textField('category_filter', $category_filter) ,
               $this->hint( __('Enter categories seperated by a \';\'. Locations are dynamic, please refer to Carta for an overview.','carta-online'))));

        echo $this->Paragraaf($this->label('field_filter', __('Field:','carta-online') . $this->textField('field_filter', $field_filter) ,
               $this->hint( __('Enter fields seperated by a \';\'. Locations are dynamic, please refer to Carta for an overview.','carta-online')) ));

        echo $this->Paragraaf(
              $this->checkboxField('url_based_filter', $url_based_filter) . $this->label('ulr_based_filter', __('URL based filter','carta-online')) . '<br />' .
              $this->hint( __('URL based filtering can be used to add the plugin to your existing offering pages. See <a href="https://www.cartaonline.nl/carta-wordpress-plugin/" target=blank>Carta Online</a> for more info.','carta-online')));

        echo '<hr />' . '<h4>' . __('Rendering','carta-online') .'</h4>';

        echo $this->Paragraaf($this->label('template', __('Template:','carta-online') . $this->textField('template', $template) ,
               $this->hint( __('Enter template for rendering offer item. See <a href="https://www.cartaonline.nl/carta-wordpress-plugin/" target=blank>Carta Online</a> for a list of possible fields.','carta-online'))));


        echo $this->Paragraaf($this->label('planning_template', __('Planning Template:','carta-online') . $this->textField('planning_template', $planning_template) ,
               $this->hint( __('Enter template for rendering planning details. Leave empty if not used. See <a href="https://www.cartaonline.nl/carta-wordpress-plugin/" target=blank>Carta Online</a> for a list of possible fields.','carta-online'))));

        echo $this->Paragraaf($this->label('submit_caption', __('Submit Caption:','carta-online') . $this->textField('submit_caption', $submit_caption) ,
               $this->hint( __('Enter caption to be used on the submit button.','carta-online'))));

        echo "<br />";
	}

public function update($new_instance, $old_instance) {
		$instance = array();
		$instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
		$instance['number_of_items'] = (!empty($new_instance['number_of_items'])) ? strip_tags($new_instance['number_of_items']) : '5';
		$instance['status_filter'] = strip_tags($new_instance['status_filter']);
		$instance['type_filter'] = strip_tags($new_instance['type_filter']);
		$instance['location_filter'] = strip_tags($new_instance['location_filter']);
		$instance['category_filter'] = strip_tags($new_instance['category_filter']);
		$instance['url_based_filter'] = strip_tags($new_instance['url_based_filter']);
		$instance['field_filter'] = strip_tags($new_instance['field_filter']);
		$instance['widget_id'] = strip_tags($new_instance['widget_id']);
		$instance['detail_page'] = strip_tags($new_instance['detail_page']);
		$instance['template'] = strip_tags($new_instance['template']);
		$instance['submit_caption'] = strip_tags($new_instance['submit_caption']);
		$instance['planning_template'] = strip_tags($new_instance['planning_template']);
		return $instance;
	}
}
