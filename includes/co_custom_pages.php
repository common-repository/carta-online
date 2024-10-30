<?php

require_once(plugin_dir_path(__FILE__) . 'co_utils.php');

/// Function to add a special post type
function co_add_custom_post_types()
{
    $value = co_get_option('co_special_pages', 'off');
    if ($value == 'on') {
        $labels = array(
            'name' => __('Teacher Profiles', 'carta-online'),
            'singular_name' => __('Teacher Profile', 'carta-online'),
            'menu_name' => __('Teacher', 'carta-online'),
            'add_new' => __('Add New', 'carta-online'),
            'add_new_item' => __('Add New Teacher Profile', 'carta-online'),
            'new_item' => __('New Teacher Profile', 'carta-online'),
            'edit_item' => __('Edit Teacher Profile', 'carta-online'),
            'view_item' => __('View Teacher Profile', 'carta-online'),
            'all_items' => __('All Teacher Profile', 'carta-online'),
            'search_items' => __('Search Teacher Profile', 'carta-online'),
        );
        $slug = __('teacher-profile', 'carta-online');
        $args = array(
            'labels' => $labels,
            'public' => true,
            'show_ui' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'rewrite' => array('slug' => $slug),
            'query_var' => true,
            'menu_icon' => 'dashicons-id-alt',
            //  'dashicons-welcome-learn-more',
            'supports' => array(
                'title',
                'editor',
                //'excerpt',
                //'revisions',
                //'thumbnail',
                //'author',
                //'page-attributes'
            )
        );
        register_post_type('teacher-profile', $args);
        flush_rewrite_rules();

        $labels = array(
            'name' => __('Fields', 'carta-online'),
            'singular_name' => __('Field', 'carta-online'),
            'menu_name' => __('Field', 'carta-online'),
            'add_new' => __('Add New', 'carta-online'),
            'add_new_item' => __('Add New Field', 'carta-online'),
            'new_item' => __('New Field', 'carta-online'),
            'edit_item' => __('Edit Field', 'carta-online'),
            'view_item' => __('View Field', 'carta-online'),
            'all_items' => __('All Fields', 'carta-online'),
            'search_items' => __('Search Fields', 'carta-online'),
        );
        $slug = __('field-info', 'carta-online');

        // Page linked to field
        $args = array(
            'labels' => $labels,
            'public' => true,
            'show_ui' => true,
            'capability_type' => 'post',
            'hierarchical' => false,
            'rewrite' => array('slug' => $slug),
            'query_var' => true,
            'menu_icon' => 'dashicons-id-alt',
            //  'dashicons-welcome-learn-more',
            'supports' => array(
                'title',
                'editor',
                'excerpt',
                'revisions',
                'thumbnail',
                'author',
                'page-attributes'
            )
        );
        register_post_type('field-info', $args);
        flush_rewrite_rules();
    }
}

// Define Carta Online meta box for attaching contents in post to Carta Backoffice
function co_add_custom_meta_box()
{
    if (co_get_option('co_special_pages', 'off') == 'on') {
        add_meta_box('co-teacher-meta-box', __('Carta Online Link', 'carta-online'), 'co_teacher_meta_box_markup', 'teacher-profile', 'side', 'high', null);
        add_meta_box('co-field-meta-box', __('Carta Online Link', 'carta-online'), 'co_field_meta_box_markup', 'field-info', 'side', 'high', null);
    }
}

/// Carta Online meta box HTML
function co_teacher_meta_box_markup($object)
{
    wp_nonce_field(basename(__FILE__), 'meta-box-nonce');

    echo '<div ><img src="' . plugins_url("carta-online/images/admin_menu_icon.png") . '"></div>';
    echo '<label for="carta-teacher-id">' . __('Carta ID', 'carta-online') . '</label>';
    echo '<input name="carta-teacher-id" type="text" value="' . get_post_meta($object->ID, 'carta-teacher-id', true) . '"/>';
    echo '<div class="small-text">' . __('Enter the online ID of the Teacher', 'carta-online') . '</div>';
    echo '<br></div>';
}

/// Carta Online meta box HTML
function co_field_meta_box_markup($object)
{
    wp_nonce_field(basename(__FILE__), 'meta-box-nonce');
    echo '<div ><img src="' . plugins_url("carta-online/images/admin_menu_icon.png") . '"></div>';
    echo '<div><label for="carta-field-id">' . __('Field', 'carta-online') . '</label>';
    echo '<input name="carta-field-id" type="text" value="' . get_post_meta($object->ID, 'carta-field-id', true) . '"/>';
    echo '<div class="small-text">' . __('Enter the complete name of the Field as found in Carta.', 'carta-online') . '</div>';
    echo '<br></div>';
}

/// Handle save of meta box data
function co_save_custom_meta_box($post_id, $post, $update)
{
    global $CO_POST;

    if (!isset($CO_POST['meta-box-nonce']) || !wp_verify_nonce($CO_POST['meta-box-nonce'], basename(__FILE__)))
        return $post_id;

    if (!current_user_can('edit_post', $post_id))
        return $post_id;

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return $post_id;

    $slug = 'teacher-profile';
    if ($slug == $post->post_type) {
        if (isset($CO_POST['carta-teacher-id'])) {
            $meta_box_text_value = $CO_POST['carta-teacher-id'];
        }
        update_post_meta($post_id, 'carta-teacher-id', $meta_box_text_value);
        return null;
    }

    $postType = 'field-info';
    if ($postType == $post->post_type) {
        if (isset($CO_POST['carta-field-id'])) {
            $meta_box_text_value = $CO_POST['carta-field-id'];
        }
        update_post_meta($post_id, 'carta-field-id', $meta_box_text_value);
        return null;
    }

    return $post_id;
}

////
/// Meta box data usage:
///     see co_render for usage of meta box data to find a post and render its contents
///

/// Change page tile for detail page
///  This only works for the detail page as specified in the Carta Online settings page
///
function co_custom_title($title_parts)
{
    $classId = co_get_classId();
    // Check if parameter classID is specified. This must be a detail page.
    if (co_isset($classId)) {
        // Assume this is a detail page.
        $co_api = new CartaOnlineApi();
        $offer_data = $co_api->getOfferDetail($classId)->result;
        if (isset($offer_data)) {
            $title_parts['title'] = $offer_data->subject;
        }
    }
    return $title_parts;
}
add_filter('document_title_parts', 'co_custom_title');

/// Change meta tags for detail page
///  This only works for the detail page as specified in the Carta Online settings page
///
function co_add_meta_tags()
{
    $classId = co_get_classId();
    if (co_isset($classId)) {
        // Assume this is a detail page.
        $co_api = new CartaOnlineApi();
        $data = $co_api->getOfferDetail($classId)->result;
        if (isset($data)) {
            if (isset($data->summary)) {
                $description = str_replace("\n", " ", $data->summary);
                echo '<meta name="description" content="' . $description . '" />';
            }
            if (isset($data->subject)) {
                $keywords = str_replace(" ", ",", $data->subject);
                echo '<meta name="keywords" content="' . $keywords . '" />';
            }
        }
    }
}
add_action('wp_head', 'co_add_meta_tags', 2);