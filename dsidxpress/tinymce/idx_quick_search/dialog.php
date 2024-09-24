<?php
if (!current_user_can("edit_posts"))
	wp_die("You can't do anything destructive in here, but you shouldn't be playing around with this anyway.");

global $wp_version, $tinymce_version;
$localJsUri = get_option("siteurl") . "/" . WPINC . "/js/";
if (is_ssl() && parse_url($localJsUri, PHP_URL_SCHEME)=="http") {
	$localJsUri = preg_replace('/http:/', 'https:', $localJsUri);
}
$adminUri = get_admin_url();
?>

<!DOCTYPE html>
<html>
<head>
	<title>dsIDXpress: IDX Search Form</title>

	<?php		
		wp_enqueue_script('dsidxpress_tiny_mce_popup', $localJsUri . 'tinymce/tiny_mce_popup.js', array(), $tinymce_version);
		wp_enqueue_script('jquery');		
		wp_enqueue_script('dsidxpress_idx_quick_search', DSIDXPRESS_PLUGIN_URL . 'tinymce/idx_quick_search/js/dialog.js', array('jquery'), DSIDXPRESS_PLUGIN_VERSION);

		wp_print_scripts();

		wp_enqueue_style('dsidxpress_admin_options_style', DSIDXPRESS_PLUGIN_URL . 'css/admin-options.css', array(), DSIDXPRESS_PLUGIN_VERSION);
		wp_enqueue_style('dsidxpress_wp_admin_style', $adminUri . 'css/wp-admin.css', array());
		wp_enqueue_style('dsidxpress_idx_quick_search_style', DSIDXPRESS_PLUGIN_URL . 'tinymce/idx_quick_search/css/dialog.css', array(), DSIDXPRESS_PLUGIN_VERSION);

		wp_print_styles();
	?>

	<style type="text/css">
		label {
			cursor: pointer;
		}
	</style>
</head>
<body class="wp-admin js admin-color-fresh">
	<div id="wpbody">

		<div class="postbox" id="ds-idx-dialog-notice">
			<div class="inside">
				<p>
					Choose either horizontal or vertical format. A simple responsive search form. Allow users to type any location, select from available property types and filter by price range.
				</p>
			</div>
		</div>
		<div class="postbox" id="ds-idx-dialog-notice">
			<div class="inside">

		    <strong>FORMAT:</strong>
		    <p>
		        <input id="format-vertical" type="radio" name="format" value="vertical"/> <label for="format-vertical">Vertical</label>
		        <br/>
		        <input id="format-horizontal" type="radio" name="format" value="horizontal" checked="checked"/><label for="format-horizontal">Horizontal</label>
		        <br/>
		    </p>

		    <strong>VIEW:</strong>
		    <p>
				<select name="view-type" onchange="dsidxSearchForm.viewTypeChanged();">
					<option value="classic-view">Classic View</option>
					<option value="modern-view">Modern View</option>
					<option value="simple-search">Simple Search</option>
				</select>
		    </p>

			<p class="button-controls">
				<span class="add-to-menu">
					<input type="button" id="dsidxpress-lb-cancel" name="cancel" value="Cancel" class="button-secondary" onclick="tinyMCEPopup.close();">
					<input type="button" id="dsidxpress-lb-insert" name="insert" value="Insert Search Form" class="button-primary" style="text-transform: capitalize;" onclick="dsidxSearchForm.insert();">
				</span>
			</p>

			</div>
		</div>
	</div>
</body>
</html>
