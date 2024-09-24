<?php

add_action('init', array('dsIdxGlobals', 'enqueueGlobals'));
add_action('wp_print_scripts', array('dsIdxGlobals', 'fixGoogleMapsConflict'));

//Hooking in to wp_footer as a fallback. For some reason wp_print_footer_scripts doesn't pick this up
add_action('wp_footer', array('dsIdxGlobals', 'fixGoogleMapsConflict'));


class dsIdxGlobals {
	public static function enqueueGlobals(){
		if (defined('DOING_CRON') && DOING_CRON){
			return;
		}
		global $pagenow;
		$options = get_option(DSIDXPRESS_OPTION_NAME);

		wp_enqueue_style('dsidxpress-icons', DSIDXPRESS_PLUGIN_URL . 'css/dsidx-icons.css');
		
		if (!isset($options["Activated"]) || !$options["Activated"])
			return;

		$account_options = dsSearchAgent_GlobalData::GetAccountOptions(false);
		$resourceParams = array( "IsResultsPageModernView" => $account_options->IsResultsPageModernView );
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("EnqueueGlobalAssets", $resourceParams, false, 3600);

		if( is_admin() ||  in_array( $pagenow, array( 'wp-login.php', 'wp-register.php' ) ) ){
			return;
		}

		if(isset($options["dsIDXPressPackage"]) && $options["dsIDXPressPackage"] == "pro" && defined('DSIDXPRESS_GOOGLEMAP_API_KEY'))	{
			wp_enqueue_script('dsidxpress_google_maps_geocode_api', '//maps.googleapis.com/maps/api/js?key='.DSIDXPRESS_GOOGLEMAP_API_KEY.'&libraries=drawing,geometry');
		}
		
		wp_enqueue_style('dsidxpress-unconditional', DSIDXPRESS_PLUGIN_URL . 'css/client.css');
		wp_enqueue_style('dsidxwidgets-unconditional', DSIDXWIDGETS_PLUGIN_URL . 'css/client.css');

		wp_enqueue_script("jquery-ui-dialog", false, array(), false, true);
		wp_enqueue_script('dsidxpress_widget_search_view', DSIDXPRESS_PLUGIN_URL . 'js/widget-client.js', array('jquery'), DSIDXPRESS_PLUGIN_VERSION, true);
	}

	public static function fixGoogleMapsConflict(){
		global $wp_scripts;
		foreach($wp_scripts->registered as $r){
			if($r->handle == DSIDXPRESS_MAPS_JS_HANDLE){
				continue;
			}
			if(preg_match('/maps.google.com/i', $r->src) || preg_match('/maps.googleapis.com/i', $r->src)){
				if(in_array($r->handle, $wp_scripts->queue) || in_array($r->handle, $wp_scripts->done)){
					wp_dequeue_script(DSIDXPRESS_MAPS_JS_HANDLE);
				}
			}
		}
	}

}