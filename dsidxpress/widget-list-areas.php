<?php
class dsSearchAgent_ListAreasWidget extends WP_Widget {
	public function __construct() {
		global $pagenow;

		parent::__construct("dsidx-list-areas", "IDX Areas", array(
			"classname" => "dsidx-widget-list-areas",
			"description" => "Lists of links for showing real estate"
		));

		if ($pagenow == 'widgets.php')
			wp_enqueue_script('dsidxpress_widget_list_areas', DSIDXPRESS_PLUGIN_URL . 'js/widget-list-areas.js', array('jquery'), DSIDXPRESS_PLUGIN_VERSION, true);

	}
	function widget($args, $instance) {
		if(!isset($instance) || empty($instance)) {
			return;
		}

		extract($args);
		extract($instance);
		$title = apply_filters("widget_title", esc_html($title));
		$options = get_option(DSIDXPRESS_OPTION_NAME);

		if (!isset($options["Activated"]) || !$options["Activated"])
			return;

		$urlBase = get_home_url() . "/idx/";

		if (empty($areaOptions["areas"]))
			return;

		echo $before_widget;
		if ($title)
			echo $before_title . $title . $after_title;

		echo "<ul class=\"dsidx-widget\">";
		foreach ($areaOptions["areas"] as $area) {
			$area = esc_html($area);
			$areaType = $areaOptions["areaType"];
			$areaPair = preg_split('/\|/', $area, -1);

			if (count($areaPair) == 2) {
				$displayTitle = $areaPair[0];
				$actualArea = $areaPair[1];
			} else {
				$displayTitle = $area;
				$actualArea = $area;
			}

			if (preg_match('/^[\w\d\s\-_]+$/', $actualArea)) {
				$encodedArea = urlencode(strtolower(str_replace(array("-", " "), array("_", "-"), $actualArea)));
				$fullAreaUrl = $urlBase . $areaType .'/'. $encodedArea . '/';
			} else if ($areaType == "city") {
				$fullAreaUrl = $urlBase . "?idx-q-Cities=" . urlencode($actualArea);
			} else if ($areaType == "community") {
				$fullAreaUrl = $urlBase . "?idx-q-Communities=" . urlencode($actualArea);
			} else if ($areaType == "tract") {
				$fullAreaUrl = $urlBase . "?idx-q-TractIdentifiers=" . urlencode($actualArea);
			}

			$fullAreaUrl = esc_url($fullAreaUrl);
			$displayTitle = esc_html($displayTitle);

			echo "<li><a href=\"{$fullAreaUrl}\">{$displayTitle}</a></li>";
		}
		echo "</ul>";
		echo $after_widget;
	}
	function update($new_instance, $old_instance) {
		$new_instance["title"] = sanitize_text_field($new_instance["title"]);

		if (is_string($new_instance["areaOptions"]["areas"])) {
			$new_instance["areaOptions"]["areas"] = sanitize_textarea_field($new_instance["areaOptions"]["areas"]);
			$new_instance["areaOptions"]["areas"] = explode("\n", $new_instance["areaOptions"]["areas"]);
		}

		if (isset($new_instance["areaOptions"]["sortAreas"]))
			$new_instance["areaOptions"]["sortAreas"] = sanitize_text_field($new_instance["areaOptions"]["sortAreas"]);

		if (isset($new_instance["areaOptions"]["sortAreas"]) && $new_instance["areaOptions"]["sortAreas"])
			sort($new_instance["areaOptions"]["areas"]);

		// we don't need to store this option
		unset($new_instance["areaOptions"]["sortAreas"]);

		foreach ($new_instance["areaOptions"]["areas"] as &$area)
			$area = trim($area);

		return $new_instance;
	}
	function form($instance) {
		$instance = wp_parse_args($instance, array(
			"title" => "Our Coverage Areas",
			"areaOptions" => array(
				"areas" => array(),
				"areaType" => "city"
			)
		));

		$title = esc_attr($instance["title"]);
		$areas = esc_textarea(implode("\n", (array)$instance["areaOptions"]["areas"]));

		$advancedId = $this->get_field_id("advanced");

		$titleFieldId = $this->get_field_id("title");
		$titleFieldName = $this->get_field_name("title");
		$areaOptionsFieldId = $this->get_field_id("areaOptions");
		$areaOptionsFieldName = $this->get_field_name("areaOptions");
		$selectedAreaType = array($instance["areaOptions"]["areaType"] => "selected=\"selected\"");
		$selectedAreaType['city'] = array_key_exists('city', $selectedAreaType) ? $selectedAreaType['city'] : "";
		$selectedAreaType['community'] = array_key_exists('community', $selectedAreaType) ? $selectedAreaType['community'] : "";
		$selectedAreaType['tract'] = array_key_exists('tract', $selectedAreaType) ? $selectedAreaType['tract'] : "";
		$selectedAreaType['zip'] = array_key_exists('zip', $selectedAreaType) ? $selectedAreaType['zip'] : "";
		$type_normalized = esc_html($instance["areaOptions"]["areaType"]);
		$pluginUrl = DSIDXPRESS_PLUGIN_URL;
		$ajaxUrl = admin_url( 'admin-ajax.php' );
		$ajaxUrlLocations = $ajaxUrl . '?action=dsidx_locations';

		echo <<<HTML
			<p>
				<label for="{$titleFieldId}">Widget title</label>
				<input id="{$titleFieldId}" name="{$titleFieldName}" value="{$title}" class="widefat" type="text" />
			</p>

			<p>
				<label for="{$areaOptionsFieldId}[areaType]">Area types</label>
				<select class="widefat" id="{$areaOptionsFieldId}_areaType" name="{$areaOptionsFieldName}[areaType]" onchange="dsWidgetListAreas.SwitchType(this, '{$areaOptionsFieldId}_link_title')">
					<option value="city" {$selectedAreaType['city']}>Cities</option>
					<option value="community" {$selectedAreaType['community']}>Communities</option>
					<option value="tract" {$selectedAreaType['tract']}>Tracts</option>
					<option value="zip" {$selectedAreaType['zip']}>Zip Codes</option>
				</select>
			</p>

			<h4>Areas (one per line)</h4>
			<p>
				<textarea id="{$areaOptionsFieldId}_areas" name="{$areaOptionsFieldName}[areas]" class="widefat" rows="10">{$areas}</textarea>
			</p>

			<div style="float: right">
				<a href="javascript:void(0);" onclick="dsWidgetListAreas.LaunchLookupList('{$ajaxUrlLocations}', '{$areaOptionsFieldId}_areaType')">See <span class="{$areaOptionsFieldId}_link_title">{$type_normalized}</span> names</a>
			</div>

			<p>
				<label for="{$areaOptionsFieldId}[sortAreas]" style="font-size: 11px">Sort areas?</label>
				<input id="{$areaOptionsFieldId}_sortAreas" name="{$areaOptionsFieldName}[sortAreas]" class="checkbox" type="checkbox" />
			</p>

			<div style="clear: both"></div>

			<p><a href="javascript:void(0);" onclick="jQuery('#{$advancedId}_advanced').slideDown(500); jQuery(this).hide()">Advanced</a></p>
			<div id="{$advancedId}_advanced" style="display:none">
				<hr />
				<h4>Add an Area w/ a Custom Title</h4>
				<p>
					<label for="{$advancedId}_title">Link Text</label>
					<input id="{$advancedId}_title" value="" class="widefat" type="text" />
				</p>
				<p>
					<label for="{$advancedId}_lookup">Actual Area Name</label>
					<input id="{$advancedId}_lookup" value="" class="widefat" type="text" />
					<span class="description">See all <span class="{$areaOptionsFieldId}_link_title">{$type_normalized}</span> Names <a href="javascript:void(0);" onclick="dsWidgetListAreas.LaunchLookupList('{$ajaxUrlLocations}', '{$areaOptionsFieldId}_areaType')">here</a></span>
				</p>

				<input type="button" class="button" value="Add This Area" onclick="dsWidgetListAreas.AddArea('{$advancedId}_title', '{$advancedId}_lookup', '{$areaOptionsFieldId}_areas')"/>
			</div>
HTML;
	}
}
?>
