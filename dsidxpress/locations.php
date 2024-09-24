<?php
add_action( 'wp_ajax_dsidx_locations', array('dsSearchAgent_Locations', 'handleAjaxRequest') );
add_action( 'wp_ajax_nopriv_dsidx_locations', array('dsSearchAgent_Locations', 'handleAjaxRequest') );

class dsSearchAgent_Locations {
	static function handleAjaxRequest(){
		if (!current_user_can("edit_pages"))
			wp_die("You can't do anything destructive in here, but you shouldn't be playing around with this anyway.");

		$options = get_option(DSIDXPRESS_OPTION_NAME);
		$requestUri = dsSearchAgent_ApiRequest::$ApiEndPoint . "LocationsByType";
		if (isset($_REQUEST['type'])) {
			$type = sanitize_text_field($_REQUEST['type']);
		}
		$apiHttpResponse = (array)wp_remote_post($requestUri, array(
			"body"			=> array(
				"searchSetupID"	=> $options["SearchSetupID"],
				"type"			=> $type
			),
			"httpversion"	=> "1.1",
			"redirection"	=> "0",
			"reject_unsafe_urls" => false
		));
		$locations = json_decode($apiHttpResponse["body"]);
		?>
		<!DOCTYPE html>
		<html>
			<head>
				<style>* { font-family:Verdana; } h2 { font-size: 14px; } body { font-size: 12px; }</style>
			</head>
			<body>
				<h2>Possible <?php echo esc_html(ucwords($type)); ?> Locations</h2>
			<?php
			if(is_array($locations)){
				foreach ($locations as $location) {
					$locationName = html_entity_decode($location->Name, ENT_QUOTES | ENT_HTML5);
					?><div><?php echo esc_html($locationName); ?></div><?php
				}
			}
			?>
			</body>
		</html>
		<?php
	}
}