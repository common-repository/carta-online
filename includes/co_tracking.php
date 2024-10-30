<?php
/**
 * Renders google trackingmanager and information
 *
 */

//require_once (plugin_dir_path(__FILE__) . 'co_api.php');
//require_once (plugin_dir_path(__FILE__) . 'co_utils.php');

function co_inject_google_tagmanager()
{
    $inject = co_get_option('co_inject_google_tagmanager_tag', false);
    if ( $inject === "on" ) 
    {  
        $tagManagerID = co_get_option('co_google_tagmanager_id', 'AW-NOT-DEFINED');

        $result = "
        <!-- Google Tag Manager -->
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','" . $tagManagerID . "');</script>
        <!-- End Google Tag Manager -->";

        echo $result;
    }
}
    
add_action('wp_head', 'co_inject_google_tagmanager', -1000);