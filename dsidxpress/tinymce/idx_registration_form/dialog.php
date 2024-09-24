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
	<title>dsIDXpress: IDX Registration Form</title>
	
	<?php
		wp_enqueue_script('dsidxpress_tiny_mce_popup', $localJsUri . 'tinymce/tiny_mce_popup.js', array(), $tinymce_version);
		wp_enqueue_script('jquery');
		wp_enqueue_script('dsidxpress_idx_registration_form', DSIDXPRESS_PLUGIN_URL . 'tinymce/idx_registration_form/js/dialog.js', array('jquery'), DSIDXPRESS_PLUGIN_VERSION);
		
		wp_print_scripts();

		wp_enqueue_style('dsidxpress_admin_options_style', DSIDXPRESS_PLUGIN_URL . 'css/admin-options.css', array(), DSIDXPRESS_PLUGIN_VERSION);
		wp_enqueue_style('dsidxpress_wp_admin_style', $adminUri . 'css/wp-admin.css', array());
		wp_enqueue_style('dsidxpress_idx_registration_form_style', DSIDXPRESS_PLUGIN_URL . 'tinymce/idx_registration_form/css/dialog.css', array(), DSIDXPRESS_PLUGIN_VERSION);

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
					Choose to include or exclude social login options for Google and Facebook on your registration form.
				</p>
			</div>
		</div>
		<div class="postbox" id="ds-idx-dialog-notice">
			<div class="inside">
				<div>
					<label for="dsidx-login-last-name">Include Social Login</label> 
					<input id="includeSocialLogin" type="checkbox" name="includeSocialLogin" /><label for="modern-view"><br>
					<label for="dsidx-login-email">Enter a URL where visitors will be sent once registered</label> <br>
					<input type="text" id="dsidx-RedirectToURL" style="width:100% !important;" class="text" name="dsidx-RedirectToURL" />
				</div>
			<p class="button-controls">
				<span class="add-to-menu">
					<input type="button" id="dsidxpress-lb-cancel" name="cancel" value="Cancel" class="button-secondary" onclick="tinyMCEPopup.close();">
					<input type="button" id="dsidxpress-lb-insert" name="insert" value="Insert Registration Form" class="button-primary" style="text-transform: capitalize;" onclick="dsidxRegistartionForm.insert();">
				</span>
			</p>

			</div>
		</div>
	</div>
</body>
</html>
