<?php
if (!current_user_can("edit_posts"))
	wp_die("You can't do anything destructive in here, but you shouldn't be playing around with this anyway.");

global $wp_version, $tinymce_version;

$localUri = get_option("siteurl") . "/" . WPINC . "/js/";
if (is_ssl() && parse_url($localJsUri, PHP_URL_SCHEME)=="http") {
	$localUri = preg_replace('/http:/', 'https:', $localUri);
}
$adminUri = get_admin_url();
$property_types_html = "";
$property_types = dsSearchAgent_ApiRequest::FetchData('AccountSearchSetupPropertyTypes', array(), false, 60 * 60 * 24);
if(!empty($property_types)){
    $property_types = json_decode($property_types["body"]);
    foreach ($property_types as $property_type) {
        $checked_html = '';
        $name = esc_html($property_type->DisplayName);
		$id = esc_html($property_type->SearchSetupPropertyTypeID);
        $property_types_html .= <<<HTML
{$id}: {$name},
HTML;
    }
}
$property_types_html = substr($property_types_html, 0, strlen($property_types_html)-1); 
$idxPagesUrl = get_admin_url().'edit.php?post_type=ds-idx-listings-page';
$pluginUrl = DSIDXPRESS_PLUGIN_URL; 

$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("AccountOptions", array(), false, 0);
if (empty($apiHttpResponse["errors"]) && $apiHttpResponse["response"]["code"] == "200") {
	$account_options = json_decode($apiHttpResponse["body"]);
	$googleMapAPIsAPIKey = isset($account_options->{'GoogleMapsAPIKey'})? $account_options->{'GoogleMapsAPIKey'}:'';

	if (!defined("DSIDXPRESS_GOOGLEMAP_API_KEY")) 
		define("DSIDXPRESS_GOOGLEMAP_API_KEY", $googleMapAPIsAPIKey);
}

?>

<!DOCTYPE html>
<html>
<head>
	<title>dsIDXpress: Build Link</title>
	<?php
	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-core');
	wp_enqueue_script('jquery-ui-widget');
	wp_enqueue_script('jquery-ui-mouse');
	wp_enqueue_script('jquery-ui-position');
	wp_enqueue_script('jquery-ui-menu');
	wp_enqueue_script('jquery-ui-autocomplete');

	wp_enqueue_script('dsidxpress_tiny_mce_popup', $localUri . 'tinymce/tiny_mce_popup.js', array(), $tinymce_version);
	wp_enqueue_script('dsidxpress_tiny_mce_mctabs', $localUri . 'tinymce/utils/mctabs.js', array(), $tinymce_version);	
	wp_enqueue_script('dsidxpress_google_maps_geocode_api', '//maps.googleapis.com/maps/api/js?v=3&key=' . $googleMapAPIsAPIKey . '&libraries=drawing,geometry');
	wp_enqueue_script('dsidxpress_admin_utilities', DSIDXPRESS_PLUGIN_URL . 'js/admin-utilities.js', array(), DSIDXPRESS_PLUGIN_VERSION);
	wp_enqueue_script('dsidxpress_link_builder', DSIDXPRESS_PLUGIN_URL . 'tinymce/link_builder/js/dialog.js', array('jquery'), DSIDXPRESS_PLUGIN_VERSION);

	wp_add_inline_script('dsidxpress_link_builder', 'var dsIdxPluginUri = "' . $pluginUrl . '";');	

	wp_print_scripts();

	wp_enqueue_style('dsidxpress_admin_options_style', DSIDXPRESS_PLUGIN_URL . 'css/admin-options.css', array(), DSIDXPRESS_PLUGIN_VERSION);
	wp_enqueue_style('dsidxpress_wp_admin_style', $adminUri . 'css/wp-admin.css', array());
	wp_enqueue_style('dsidxpress_link_builder_style', DSIDXPRESS_PLUGIN_URL . 'tinymce/link_builder/css/link_builder.css', array(), DSIDXPRESS_PLUGIN_VERSION);

	wp_print_styles();		
	?>	
</head>

<body class="wp-admin js admin-color-fresh">
	<div id="wpbody">
		<div class="postbox" id="ds-idx-dialog-notice">
			<div class="inside">
					<strong>NOTICE:</strong>
					<p>
						This tool is scheduled for removal. For future link insertion, please use the following steps:
						<ol>
						<li>Build your listings pages using the <a href="<?php echo $idxPagesUrl; ?>" target="_top">IDX Pages</a> section found in the left-hand navigation.</li>
						<li>Select the "Insert/edit link" button <img src="<?php echo DSIDXPRESS_PLUGIN_URL . 'images/hyperlink-icon.png'; ?>" alt="" style="position:relative; top:4px; width:20px;" /> from the text editor tool.</li>
						<li>Expand the "Or link to existing content" section and select from your available IDX Pages.</li>
						</ol>
					</p>
					<a href="#" style="float:right;" onclick="jQuery('#ds-idx-dialog-notice').remove(); return false;">close</a>
			</div>
		</div>
		<div class="postbox">
			<div class="inside">
                <input type="hidden" id="linkBuilderPropertyTypes" value="<?php echo esc_attr($property_types_html) ?>" />
				<?php dsSearchAgent_Admin::LinkBuilderHtml(true) ?>
			</div>
		</div>
	</div>
</body>
</html>
