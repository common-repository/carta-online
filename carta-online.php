<?php
/*
 * Plugin Name: Carta Online
 * Plugin URI: https://www.cartaonline.nl/carta-wordpress-plugin/
 * Description: Met deze plugin wordt het publiek aanbod van uw Carta Online portaal weergegeven op uw website. Bezoekers hebben de mogelijkheid om uw aanbod te doorzoeken middels een filter om zich vervolgens direct in te schrijven!
 * Version: 2.12.1
 * Author: Carta Online | LEAD Solutions B.V.
 * Author URI: https://www.cartaonline.nl
 * Text Domain: cartaonline
 * Domain Path: /lang
 */

require_once(plugin_dir_path( __FILE__ ).'includes/co_utils.php');
require_once(plugin_dir_path( __FILE__ ).'includes/co_widget.php');
require_once(plugin_dir_path( __FILE__ ).'includes/co_shortcode.php');
require_once(plugin_dir_path( __FILE__ ).'includes/co_custom_pages.php');
define( 'CO_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

add_action('admin_init', 'co_settings_init');
add_action('wp_enqueue_scripts', 'co_styles_init');
add_action('admin_menu', 'co_menu_init');
/**
 * Load plugin textdomain.
 */
add_action( 'init', 'co_load_textdomain' );
/* Add plugin fields to pages */
add_action( 'init', 'co_add_custom_post_types' );
add_action( 'add_meta_boxes', 'co_add_custom_meta_box');
add_action( 'save_post', 'co_save_custom_meta_box', 10, 3);
add_action ('admin_notices', 'co_custom_admin_notice');

function co_load_textdomain() {

    if (get_locale() == 'nl_NL')
    {
        // First load my own translations for the textdomain
        unload_textdomain('carta-online');
        $filename = plugin_dir_path( __FILE__ ) . 'lang/carta-online-nl_NL.mo';
        load_textdomain('carta-online', $filename );
    }
    // Load plugin translations
    load_plugin_textdomain( 'carta-online', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
    return true;
}

function co_styles_init() {
	wp_enqueue_style(
		'co-styles',
		plugin_dir_url( __FILE__ ).'css/style.css'
	);
	$options = get_option( 'co_settings' );
	wp_add_inline_style( 'co-styles', co_safe_array_get($options,'co_style'));
}

function co_menu_init(){
    get_option( 'co_settings' );
    add_menu_page(
	'Carta Online settings',
	'Carta Online',
	'manage_options',
	'carta-online/admin/cartaonline-admin.php',
	'',
	plugins_url( 'carta-online/images/admin_menu_icon.png' ),
	'2.025'
);
}

/**
 * Display a custom admin notice in the WordPress dashboard.
 */
function co_custom_admin_notice() {
    // Initialize an empty message.
    $infoMessage = '';

    // Check if we are on the specific admin page.
    if (isset($_GET['page']) && ($_GET['page'] == 'carta-online/admin/cartaonline-admin.php')) {

        // Check if the settings have been updated.
        if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == "true") {

            // Get the settings options.
            $options = get_option('co_settings');

            // Append message about saving settings.
            $infoMessage .= 'Carta Online: Settings saved';

            // If the option to clear cache is set, append to the message.
            if (isset($options) && isset($options['co_clear_cache']) && ($options['co_clear_cache'] == "on")) {
                $infoMessage .= ', cache cleared';
            }

			// If the option to clear cache is set, append to the message.
            if (isset($options) && isset($options['co_send_error_test']) && ($options['co_send_error_test'] == "on")) {
                $infoMessage .= ', error report sent to ' . $options['co_error_email'] . ' using wp_mail()';
            }
        }

        // If there's no message, exit the function.
        if ($infoMessage == '') {
            return;
        }

        // Display the notice message in the admin dashboard.
        echo '<div class="notice notice-success is-dismissible"><p>' . $infoMessage . '</p></div>';
    }
}

/**
 * Check form submission for specific actions.
 */
function co_check_submit() {
    // If the clear cache option is set in the form submission, clear the API cache.
    if (isset($_POST['co_settings']['co_clear_cache']) && ($_POST['co_settings']['co_clear_cache'] == 'on')) {
        // Clear API Cache.
        (new CartaOnlineAPI())->clear_cache();

		// Reset the co_clear_cache option after clearing the cache.
        $options = get_option('co_settings');
        $options['co_clear_cache'] = 'off';  // Assuming 'off' is the default value. Adjust if different.
        update_option('co_settings', $options);
    }

	// Send test error report
    if (isset($_POST['co_settings']['co_send_error_test']) && ($_POST['co_settings']['co_send_error_test'] == 'on')) {
		co_report_error('Test error report.', true);
        $options = get_option('co_settings');
        $options['co_send_error_test'] = 'off';  // Assuming 'off' is the default value. Adjust if different.
        update_option('co_settings', $options);
	}
}


function co_settings_init(  ) {
	$options = get_option( 'co_settings' );

    // Check if our settings form is being submitted
	if (isset($_POST['option_page']) && $_POST['option_page'] == 'co_pluginPage') {
		co_check_submit();
	}

	register_setting( 'co_pluginPage', 'co_settings' );
	add_settings_section(
		'co_connection_section',
		__( 'Connection' , 'carta-online' ),
		'co_connection_section_callback',
		'co_pluginPage'
	);
	add_settings_field(
		'co_portal_address',
		__( 'Portal Address (URL)', 'carta-online' ),
		'co_portal_address_render',
		'co_pluginPage',
		'co_connection_section'
	);
	add_settings_field(
		'co_api_key',
		__( 'API Key', 'carta-online'),
		'co_api_key_render',
		'co_pluginPage',
		'co_connection_section'
	);
	add_settings_field(
		'co_cache_timeout',
		__( 'Cache duration', 'carta-online'),
		'co_cache_timeout_render',
		'co_pluginPage',
		'co_connection_section'
	);
	add_settings_field(
		'co_apicall_timeout2',
		__( 'API call timeout', 'carta-online'),
		'co_api_timeout_render',
		'co_pluginPage',
		'co_connection_section'
	);
	add_settings_field(
		'co_cache_timeout3',
		__( 'Grace period timeout', 'carta-online'),
		'co_grace_period_timeout_render',
		'co_pluginPage',
		'co_connection_section'
	);
	add_settings_field(
		'co_clear_cache',
		__( 'Clear cache', 'carta-online' ),
		'co_clear_cache_render',
		'co_pluginPage',
		'co_connection_section'
	);	

	add_settings_section(
		'co_browser_behavior_section',
		__( 'Browser behavior' , 'carta-online'),
		'co_browser_behavior_section_callback',
		'co_pluginPage'
	);
	add_settings_field(
		'co_session_cache_limiter',
		__( 'Session cache limiter', 'carta-online' ),
		'co_session_cache_limiter_render',
		'co_pluginPage',
		'co_browser_behavior_section'
	);	
	add_settings_section(
		'co_content_section',
		__( 'Content Rendering' ),
		'co_content_section_callback',
		'co_pluginPage'
	);
	add_settings_field(
		'co_detail_pagename',
		__( 'Page name of detail page', 'carta-online' ),
		'co_detail_pagename_render',
		'co_pluginPage',
		'co_content_section'
	);
	add_settings_field(
		'co_detail_redirect_name',
		__( 'Redirect base', 'carta-online' ),
		'co_detail_redirect_name_render',
		'co_pluginPage',
		'co_content_section'
	);
	add_settings_field(
		'co_special_pages',
		__( 'Enable special pages', 'carta-online' ),
		'co_special_pages_render',
		'co_pluginPage',
		'co_content_section'
	);
	add_settings_field(
		'co_alternate_subscription_page',
		__( 'Alternate subscription page', 'carta-online' ),
		'co_alternate_subscription_page_render',
		'co_pluginPage',
		'co_content_section'
	);
	add_settings_field(
	    'co_google_ads_id',
	    __( 'Your Google Ads ID:', 'carta-online' ),
	    'co_google_ads_id_render',
	    'co_pluginPage',
	    'co_content_section'
	    );

	add_settings_field(
		'co_inject_google_tagmanager_tag',
		__( 'Inject code for Google Tagmanager', 'carta-online' ),
		'co_inject_google_tagmanager_tag_render',
		'co_pluginPage',
		'co_content_section'
		);
	add_settings_field(
		'co_google_tagmanager_tag',
		__( 'Your Google Tagmanager ID:', 'carta-online' ),
		'co_google_tagmanager_id_render',
		'co_pluginPage',
		'co_content_section'
		);

	add_settings_field(
	    'co_redirect_page',
	    __( 'Redirect page', 'carta-online' ),
	    'co_redirect_page_render',
	    'co_pluginPage',
	    'co_content_section'
	    );
	add_settings_field(
		'co_special_render_options',
		__( 'Special render options', 'carta-online' ),
		'co_special_render_options_render',
		'co_pluginPage',
		'co_content_section'
	);
	add_settings_field(
		'co_branding',
		__( 'Branding', 'carta-online' ),
		'co_branding_render',
		'co_pluginPage',
		'co_content_section'
	);
	add_settings_section(
		'co_error_reporting_section',
		__('Error reporting' , 'carta-online'),
		'co_error_reporting_callback',
		'co_pluginPage'
	);
	add_settings_field(
		'co_error_reporting',
		__('Send e-mail if error' , 'carta-online' ),
		'co_error_reporting_render',
		'co_pluginPage',
		'co_error_reporting_section'
	);	
	add_settings_field(
		'co_error_email',
		__('Error report E-mail' , 'carta-online' ),
		'co_error_email_render',
		'co_pluginPage',
		'co_error_reporting_section'
	);	
	add_settings_field(
		'co_send_error_test',
		__( 'Send test e-mail', 'carta-online' ),
		'co_send_error_test_render',
		'co_pluginPage',
		'co_error_reporting_section'
	);

	add_settings_section(
		'co_styling_section',
		__( 'Styling' , 'carta-online'),
		'co_styling_section_callback',
		'co_pluginPage'
	);
	add_settings_field(
		'co_style',
		__( 'Styling' , 'carta-online'),
		'co_style_render',
		'co_pluginPage',
		'co_styling_section'
	);
}
function co_style_render(  ) {
	$options = get_option( 'co_settings' );
?>

<textarea style="width: 100%;" rows="13" name='co_settings[co_style]'>
<?php echo co_safe_array_get($options,'co_style'); ?>
</textarea>
<p class="description" id="co-portal-address-description">
    <?php echo __("Please provide custom styling."); ?>
</p>
<?php
}

function co_error_reporting_render(  ) {
	$options = get_option( 'co_settings' ); 
?>
<input name='co_settings[co_error_reporting]' <?php checked(co_safe_array_get($options, 'co_error_reporting'),'on'); ?> type="checkbox" />
<p class="description" id="co_error_reporting-description">
    <?php echo __("Send error reports",'carta-online'); ?>
</p>
<?php
}

function co_error_email_render() {
	$options = get_option( 'co_settings');
	?>
	<input type='text' class='regular-text' name='co_settings[co_error_email]' value='<?php echo co_safe_array_get($options ,'co_error_email'); ?>' />
	<p class="description" id="co_error_email-description">
		<?php echo __('This is the e-mail address where error messages are sent to' , 'carta-online'); ?>
	</p>
	<?php
}

function co_send_error_test_render(  ) {
?>
<input name='co_settings[co_send_error_test]' <?php checked('0','1'); ?> type="checkbox" />
<p class="description" id="co_send_error_test-description">
    <?php echo __("Send test error report",'carta-online'); ?>
</p>
<?php
}

function co_portal_address_render(  ) {
	$options = get_option( 'co_settings' );

?>
<input type='text' class='regular-text' name='co_settings[co_portal_address]' value='<?php echo co_safe_array_get($options ,'co_portal_address'); ?>' />
<p class="description" id="co-portal-address-description">
    <?php echo __("This is the address where your Carta Online portal is hosted, please use HTTPS. (Example: https://portal.cartaonline.nl)", 'carta-online'); ?>
</p>
<?php
}

function co_cache_timeout_render(  ) {
	$options = get_option( 'co_settings' );
?>
<input name='co_settings[co_cache_timeout]' value='<?php echo co_safe_array_get($options, 'co_cache_timeout'); ?>' step="1" min="1" class="small-text" type="number" />
min
<p class="description" id="co-session-cache-limiter-description">
    <?php echo __("The maximum number of minutes that a cache file is stored..",'carta-online'); ?>
</p>
<?php
}

function co_api_timeout_render(  ) {
	$options = get_option( 'co_settings' );
?>
<input name='co_settings[co_api_timeout]' value='<?php echo co_safe_array_get($options, 'co_api_timeout'); ?>' step="1" min="1" class="small-text" type="number" />
sec
<p class="description" id="co-session-cache-limiter-description">
    <?php echo __("The maximum number of seconds that an API call may take.",'carta-online'); ?>
</p>
<?php
}

function co_grace_period_timeout_render(  ) {
	$options = get_option( 'co_settings' );
?>
<input name='co_settings[co_grace_period_timeout]' value='<?php echo co_safe_array_get($options, 'co_grace_period_timeout'); ?>' step="1" min="1" class="small-text" type="number" />
min
<p class="description" id="co-session-cache-limiter-description">
    <?php echo __("The maximum number of minutes which a cache file is considered usable.",'carta-online'); ?>
</p>
<?php
}

function co_special_pages_render(  ) {
	$options = get_option( 'co_settings' );
?>
<input name='co_settings[co_special_pages]' <?php checked(co_safe_array_get($options, 'co_special_pages'),'on'); ?> type="checkbox" />
<p class="description" id="co-special-pages-description">
    <?php echo __("Enable special content pages for teacher profiles and offer contents.",'carta-online'); ?>
</p>
<?php
}

function co_clear_cache_render(  ) {
?>
<input name='co_settings[co_clear_cache]' <?php checked('0','1'); ?> type="checkbox" />
<p class="description" id="co-co-clear-cache-description">
    <?php echo __("Clears the contents of the cache after saving configuration.",'carta-online'); ?>
</p>
<?php
}
function co_inject_google_ads_tag_render(  ) {
    $options = get_option( 'co_settings' );
    ?>
<input name='co_settings[co_inject_google_ads_tag]' <?php checked(co_safe_array_get($options, 'co_inject_google_ads_tag'),'on'); ?> type="checkbox" />
<p class="description" id="co_inject_google_ads_tag-description">
    <?php echo __("Enable Google Ads injection on page containing course details.",'carta-online'); ?>
</p>
<?php
}

function co_google_ads_id_render(  ) {
    $options = get_option( 'co_settings' );
    ?>
<input type="text" class='regular-text' name='co_settings[co_google_ads_id]' value='<?php echo co_safe_array_get($options, 'co_google_ads_id'); ?>' />
<p class="description" id="co_google_ads_id-description"><?php echo __("Enter your Google Ads ID (e.g. AW-123456789).",'carta-online'); ?>
</p>
<?php
}

function co_inject_google_tagmanager_tag_render(  ) {
    $options = get_option( 'co_settings' );
    ?>
<input name='co_settings[co_inject_google_tagmanager_tag]' <?php checked(co_safe_array_get($options, 'co_inject_google_tagmanager_tag'),'on'); ?> type="checkbox" />
<p class="description" id="co_inject_google_tagmanager_tag-description">
    <?php echo __("Enable Google Tagmanager injection on page containing course details.",'carta-online'); ?>
</p>
<?php
}
         
function co_google_tagmanager_id_render(  ) {
    $options = get_option( 'co_settings' );
    ?>
<input type="text" class='regular-text' name='co_settings[co_google_tagmanager_id]' value='<?php echo co_safe_array_get($options, 'co_google_tagmanager_id'); ?>' />
<p class="description" id="co_google_tagmanager_id-description"><?php echo __("Enter your Google Tagmanager ID (e.g. AW-123456789).",'carta-online'); ?>
</p>
<?php
}

function co_session_cache_limiter_render(  ) {
	$options = get_option( 'co_settings' );
?>
<input name='co_settings[co_session_cache_limiter]' <?php checked(co_safe_array_get($options, 'co_session_cache_limiter'),'on'); ?> type="checkbox" />
<p class="description" id="co-session-cache-limiter-description">
    <?php echo __("The cache limiter defines which cache control HTTP headers are sent to the client. These headers determine the rules by which the page content may be cached by the client and intermediate proxies.",'carta-online'); ?>
</p>
<?php
}

function co_special_render_options_render(  ) {
	$options = get_option( 'co_settings' );
?>
<input type="text" class='regular-text' name='co_settings[co_special_render_options]' value='<?php echo co_safe_array_get($options, 'co_special_render_options'); ?>' />
<p class="description" id="co-special-render-description"><?php echo __("Enter special render options as supplied by your consultant.",'carta-online'); ?>
</p>
<?php
}

function co_branding_render(  ) {
	$options = get_option( 'co_settings' );
?>
<input type="text" class='regular-text' name='co_settings[co_branding]' value='<?php echo co_safe_array_get($options, 'co_branding'); ?>' />
<p class="description" id="co-branding-description"><?php echo __("Enter the name of a brand. Only content for this brand is shown on the website.",'carta-online'); ?>
</p>
<?php
}

function co_alternate_subscription_page_render(  ) {
	$options = get_option( 'co_settings' );
?>
<input type="text" class='regular-text' name='co_settings[co_alternate_subscription_page]' value='<?php echo co_safe_array_get($options, 'co_alternate_subscription_page'); ?>' />
<p class="description" id="co_alternate_subscription_page"><?php echo __("Special subscription page if the default Carta Online subscription page is not used. %identifier% wil be substituted by the correct identifier.",'carta-online'); ?>
</p>
<?php
}

function co_redirect_page_render(  ) {
    $options = get_option( 'co_settings' );
    ?>
<input type="text" class='regular-text' name='co_settings[co_redirect_page]' value='<?php echo co_safe_array_get($options , 'co_redirect_page'); ?>' />
<p class="description" id="co_redirect_page"><?php echo __("Redirect url if the course doesn't exist.",'carta-online'); ?>
</p>
<?php
}

function co_detail_pagename_render(  ) {
	$options = get_option( 'co_settings' );
?>

<input name='co_settings[co_detail_pagename]' value='<?php echo co_safe_array_get($options, 'co_detail_pagename'); ?>'  type="text" />
<p class="description" id="co-portal-address-description">
    <?php echo __("Only enter the name of the page as specified in the permalink of the page. Do not enter the full path.",'carta-online'); ?>
</p>
<?php
}

function co_detail_redirect_name_render(  ) {
	$options = get_option( 'co_settings' );
?>

<input name='co_settings[co_detail_redirect_name]' value='<?php echo co_safe_array_get($options, 'co_detail_redirect_name'); ?>'  type="text" />
<p class="description" id="co-portal-address-description">
    <?php echo __("Enter base page name to render redirect structure next to parameterized structure for detail pages. /redirect/xxx/yyy => /details/?class=xxx. <br />",'carta-online'); ?>
</p>
<?php
}
function co_api_key_render(  ) {
	$options = get_option( 'co_settings' );
?>
<input type='text' class='regular-text' name='co_settings[co_api_key]' value='<?php echo co_safe_array_get($options, 'co_api_key'); ?>' />
<p class="description" id="co-portal-address-description">
    <?php echo __("You can find your API Key at your Carta Online portal under Menu -> Api -> Programs, if this menu-item is not visible, please contact Carta Online at", 'carta-online'); ?>
    <a href="mailto:info@lead.nl">info@cartaonline.nl</a>
    .
</p>
<?php
}
function co_connection_section_callback(  ) {
	echo __('Enter the connection details to connect to Carta Online', 'carta-online');
}
function co_browser_behavior_section_callback(  ) {
	echo __('Enter the desired browser behavior', 'carta-online');
}
function co_styling_section_callback(  ) {
	echo __('Provide custom styling for the plugin', 'carta-online');
}
function co_content_section_callback(  ) {
	echo __('Specify your prefered content rendering options for the plugin', 'carta-online');
}
function co_error_reporting_callback(  ) {
	echo __('Error reporting', 'carta-online');
}
function co_rewrite_rules($rules){
    $redirect_name = co_get_option('co_detail_redirect_name');
    if (isset($redirect_name)) {
        $newrules = array();
        $pagename = co_get_option('co_detail_pagename');
        if (isset($pagename)) {
			$detailPage = null;
			$query = new WP_Query(
					array(
							'post_type'              => 'page',
							'title'                  => $pagename,
							'posts_per_page'         => 1,
							'no_found_rows'          => true,
							'ignore_sticky_posts'    => true,
							'update_post_term_cache' => false,
							'update_post_meta_cache' => false,
					)
			);

			if ( ! empty( $query->post ) ) {
					$detailPage = $query->post;
			}			
            if (!isset($detailPage)) $detailPage = get_page_by_path( $pagename );
            if (isset($detailPage)) {
                // add_rewrite_rule('^product/([0-9]{1,})/?','index.php?p=4&mycustomvar=$matches[1]','top')
                $pagename = 'index.php?page_id=' . $detailPage -> ID . '&';
            }
            else {
                $pagename = $pagename . '/?';
            }
        }

        $newrules[ '^' . $redirect_name . '/([^/]+)/([^/]+)/?' ] =  $pagename . 'class=$matches[1]&cn=$matches[2]';
        return $newrules + $rules;
    }
    return $rules;
}
add_action('rewrite_rules_array', 'co_rewrite_rules');

function co_flush_new_rule() {
   
    $redirect_name = co_get_option('co_detail_redirect_name');
    if (isset($redirect_name)) {
        
        global $wp_rewrite;
        $rules = get_option('rewrite_rules');
	
		if (!is_array($rules)) {
			$rules = [];
		}
		
        $b = !array_key_exists('^' . $redirect_name . '/([^/]+)/([^/]+)/?' , $rules );
        if ( is_array($rules) && $b ) {
            $wp_rewrite->flush_rules( true );
        }
    }
    return true;
}
add_action('init', 'co_flush_new_rule');

function co_add_query_vars_filter($vars){
  $vars[] = "class";
  $vars[] = "cn";
  return $vars;
}
add_filter('query_vars', 'co_add_query_vars_filter');

