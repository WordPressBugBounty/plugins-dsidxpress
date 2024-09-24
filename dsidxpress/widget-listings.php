<?php
class dsSearchAgent_ListingsWidget extends WP_Widget {
	public function __construct() {
		global $pagenow;
		
		parent::__construct("dsidx-listings", "IDX Listings", array(
			"classname" => "dsidx-widget-listings",
			"description" => "Show a list of real estate listings"
		));

		if ($pagenow == 'widgets.php') {
			wp_enqueue_script('dsidxpress_widget_listings', DSIDXPRESS_PLUGIN_URL . 'js/widget-listings.js', array('jquery'), DSIDXPRESS_PLUGIN_VERSION, true);
		}			
	}
	function widget($args, $instance) {
		if(!isset($instance) || empty($instance)) {
			return;
		}

		extract($args);
		extract($instance);
		$title = apply_filters("widget_title", $title);
		$sort = isset($instance['sort'])?$instance['sort']:'';
		$options = get_option(DSIDXPRESS_OPTION_NAME);

		if (!isset($options["Activated"]) || !$options["Activated"])
			return;
			
		wp_enqueue_script('jquery', false, array(), false, true);

		echo $before_widget;
		if ($title)
			echo $before_title . $title . $after_title;

		$apiRequestParams = array();
		$apiRequestParams["directive.ResultsPerPage"] = $listingsToShow;
		$apiRequestParams["responseDirective.ViewNameSuffix"] = "widget";
		$apiRequestParams["responseDirective.DefaultDisplayType"] = $defaultDisplay;
		$apiRequestParams['responseDirective.IncludeDisclaimer'] = 'true';
		$sort = explode('|', $sort);
		$apiRequestParams["directive.SortOrders[0].Column"] = $sort[0];
		$apiRequestParams["directive.SortOrders[0].Direction"] = isset($sort[1])?$sort[1]:'';

		if ($querySource == "area") {
			switch ($areaSourceConfig["type"]) {
				case "city":
					$typeKey = "query.Cities";
					break;
				case "community":
					$typeKey = "query.Communities";
					break;
				case "tract":
					$typeKey = "query.TractIdentifiers";
					break;
				case "zip":
					$typeKey = "query.ZipCodes";
					break;
			}
			$apiRequestParams[$typeKey] = $areaSourceConfig["name"];
		} else if ($querySource == "link") {
			$apiRequestParams["query.ForceUsePropertySearchConstraints"] = "true";
			$apiRequestParams["query.LinkID"] = $linkSourceConfig["linkId"];
		} else if ($querySource == "agentlistings") {
			if (isset($options['AgentID']) && !empty($options['AgentID'])) $apiRequestParams["query.ListingAgentID"] = $options['AgentID'];
		} else if ($querySource == "officelistings") {
			if (isset($options['OfficeID']) && !empty($options['OfficeID'])) $apiRequestParams["query.ListingOfficeID"] = $options['OfficeID'];
		} 
		if (defined("DS_REQUEST_MULTI_AVAILABLE") && DS_REQUEST_MULTI_AVAILABLE==true)  {
			$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Results", $apiRequestParams,true,null,null,false,true);
			if (empty($apiHttpResponse['body'])) {
				$apiHttpResponse['body'] = '%%ds_Results|' . json_encode($apiHttpResponse) . '|ds_end%%';
			}	
			$data = $apiHttpResponse["body"];	
			$data = str_replace('{$pluginUrlPath}', dsSearchAgent_ApiRequest::MakePluginsUrlRelative(DSIDXPRESS_PLUGIN_URL), $data);
			echo $data;
			echo $after_widget;
		} else {
					$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Results", $apiRequestParams);
					if (empty($apiHttpResponse["errors"]) && $apiHttpResponse["response"]["code"] == "200") {
						$data = $apiHttpResponse["body"];
					} else {
						switch ($apiHttpResponse["response"]["code"]) {
							case 403:
								$data = '<p class="dsidx-error">'.DSIDXPRESS_INACTIVE_ACCOUNT_MESSAGE.'</p>';
							break;
							default:
								$data = '<p class="dsidx-error">'.DSIDXPRESS_IDX_ERROR_MESSAGE.'</p>';
						}
					}
					$data = str_replace('{$pluginUrlPath}', dsSearchAgent_ApiRequest::MakePluginsUrlRelative(DSIDXPRESS_PLUGIN_URL), $data);
					echo $data;
					echo $after_widget;
		}
		dsidx_footer::ensure_disclaimer_exists();
			
	}
	function update($new_instance, $old_instance) {
		// we need to do this first-line awkwardness so that the title comes through in the sidebar display thing
		$new_instance["title"] = sanitize_text_field($new_instance["title"]);
		$new_instance["listingsToShow"] = sanitize_text_field($new_instance["listingsToShow"]);
		$new_instance["sort"] = sanitize_text_field($new_instance["sort"]);
		$new_instance["defaultDisplay"] = sanitize_text_field($new_instance["defaultDisplay"]);
		$new_instance["querySource"] = sanitize_text_field($new_instance["querySource"]);			

		if (isset($new_instance['areaSourceConfig']['type']))
			$new_instance['areaSourceConfig']['type'] = sanitize_text_field($new_instance['areaSourceConfig']['type']);

		if (isset($new_instance['areaSourceConfig']['name']))
			$new_instance['areaSourceConfig']['name'] = sanitize_text_field($new_instance['areaSourceConfig']['name']);
		
		if (isset($new_instance['areaSourceConfig']['title']))
			$new_instance['areaSourceConfig']['title'] = sanitize_text_field($new_instance['areaSourceConfig']['title']);

		if (isset($new_instance['linkSourceConfig']['linkId']))
			$new_instance['linkSourceConfig']['linkId'] = sanitize_text_field($new_instance['linkSourceConfig']['linkId']);

		return  $new_instance;
	}
	function form($instance) {
		$options = get_option(DSIDXPRESS_OPTION_NAME);
		$instance = wp_parse_args($instance, array(
			"title"				=> "Latest Real Estate",
			"listingsToShow"	=> "25",
			"defaultDisplay"	=> "listed",
			"sort"				=> "DateAdded|DESC",
			"querySource"		=> "area",
			"areaSourceConfig"	=> array(
				"type"			=> "city",
				"name"			=> ""
			),
			"linkSourceConfig"	=> array(
				"linkId"		=> ""
			)
		));
		$titleFieldId = $this->get_field_id("title");
		$titleFieldName = $this->get_field_name("title");

		$listingsToShowFieldId = $this->get_field_id("listingsToShow");
		$listingsToShowFieldName = $this->get_field_name("listingsToShow");
		
		$sortFieldId = $this->get_field_id("sort");
		$sortFieldName = $this->get_field_name("sort");

		$defaultDisplayFieldId = $this->get_field_id("defaultDisplay");
		$defaultDisplayFieldName = $this->get_field_name("defaultDisplay");

		$querySourceFieldId = $this->get_field_id("querySource");
		$querySourceFieldName = $this->get_field_name("querySource");

		$areaSourceConfigFieldId = $this->get_field_id("areaSourceConfig");
		$areaSourceConfigFieldName = $this->get_field_name("areaSourceConfig");

		$linkSourceConfigFieldId = $this->get_field_id("linkSourceConfig");
		$linkSourceConfigFieldName = $this->get_field_name("linkSourceConfig");

		$checkedDefaultDisplay = array(
			"listed"		=> "",
			"slideshow" => "",
			"expanded"	=> "",
			"map"				=> "",
			$instance["defaultDisplay"] => "checked=\"checked\""
		);
		
		$checkedQuerySource = array(
			"area"						=> "",
			"officelistings"	=> "",
			"agentlistings"		=> "",
			"link"						=> "",
			$instance["querySource"] => "checked=\"checked\""
		);
		$selectedAreaType = array(
			"city"			=> "",
			"community"	=> "",
			"tract"			=> "",
			"zip"				=> "",
			$instance["areaSourceConfig"]["type"] => "selected=\"selected\""
		);
		$selectedAreaTypeNormalized = esc_html(ucwords($instance["areaSourceConfig"]["type"]));

		$selectedSortOrder = array(
			"DateAddedDESC"								=> "",
			"PriceDESC"										=> "",
			"PriceASC"										=> "",
			"OverallPriceDropPercentDESC" => "",
			"ImprovedSqFtDESC"						=> "",
			"LotSqFtDESC"									=> "",
			str_replace("|", "", $instance["sort"]) => "selected=\"selected\""
		);
		
		$selectedLink = array($instance["linkSourceConfig"]["linkId"] => "selected=\"selected\"");

		$availableLinks = dsSearchAgent_ApiRequest::FetchData("AccountAvailableLinks", array(), true, 0);
		$availableLinks = json_decode($availableLinks["body"]);
		$pluginUrl = DSIDXPRESS_PLUGIN_URL;
		$ajaxLocationsUrl = admin_url( 'admin-ajax.php' ) . '?action=dsidx_locations';

		$agentListingsNote = null;
		$officeListingsNote = null;
		if (!isset($options['AgentID']) || empty($options['AgentID'])) {
			$agentListingsNote = "There are no listings to show with your current settings.  Please make sure you have provided your Agent ID on the IDX > General page of your site dashboard, or change this widget's settings to show other listings.";
		}
		if (!isset($options['OfficeID']) || empty($options['OfficeID'])) {
			$officeListingsNote = "There are no listings to show with your current settings.  Please make sure you have provided your Office ID on the IDX > General page of your site dashboard, or change this widget's settings to show other listings.";
		}

		$title = esc_attr($instance['title']);
		$listingsToShow = esc_attr($instance['listingsToShow']);
		$areaSourceConfigName = esc_attr($instance['areaSourceConfig']['name']);

		echo <<<HTML
			<p>
				<label for="{$titleFieldId}">Widget title</label>
				<input id="{$titleFieldId}" name="{$titleFieldName}" value="{$title}" class="widefat" type="text" />
			</p>
			<p>
				<label for="{$listingsToShowFieldId}"># of listings to show (max 50)</label>
				<input id="{$listingsToShowFieldId}" name="{$listingsToShowFieldName}" value="{$listingsToShow}" class="widefat" type="text" />
			</p>
			<p>
				<label for="{$sortFieldId}">Sort order</label>
				<select id="{$sortFieldId}" name="{$sortFieldName}" class="widefat">
					<option value="DateAdded|DESC" {$selectedSortOrder['DateAddedDESC']}>Time on market, newest first</option>
					<option value="Price|DESC" {$selectedSortOrder['PriceDESC']}>Price, highest first</option>
					<option value="Price|ASC" {$selectedSortOrder['PriceASC']}>Price, lowest first</option>
					<option value="OverallPriceDropPercent|DESC" {$selectedSortOrder['OverallPriceDropPercentDESC']}>Price drop %, largest first</option>
					<option value="ImprovedSqFt|DESC" {$selectedSortOrder['ImprovedSqFtDESC']}>Improved size, largest first</option>
					<option value="LotSqFt|DESC" {$selectedSortOrder['LotSqFtDESC']}>Lot size, largest first</option>
				</select>
			</p>
			<p>
				<input type="radio" name="{$defaultDisplayFieldName}" id="{$defaultDisplayFieldId}[listed]" value="listed" {$checkedDefaultDisplay['listed']}/>
				<label for="{$defaultDisplayFieldId}[listed]">Show in list by default</label>
				<br />
				<input type="radio" name="{$defaultDisplayFieldName}" id="{$defaultDisplayFieldId}[slideshow]" value="slideshow" {$checkedDefaultDisplay['slideshow']}/>
				<label for="{$defaultDisplayFieldId}[slideshow]">Show slideshow details by default</label>
				<br />
				<input type="radio" name="{$defaultDisplayFieldName}" id="{$defaultDisplayFieldId}[expanded]" value="expanded" onclick="document.getElementById('{$listingsToShowFieldId}').value = 4;" {$checkedDefaultDisplay['expanded']}/>
				<label for="{$defaultDisplayFieldId}[expanded]">Show expanded details by default</label>
				<br />
				<input type="radio" name="{$defaultDisplayFieldName}" id="{$defaultDisplayFieldId}[map]" value="map" {$checkedDefaultDisplay['map']}/>
				<label for="{$defaultDisplayFieldId}[map]">Show on map by default</label>
			</p>

			<div class="widefat" style="border-width: 0 0 1px; margin: 20px 0;"></div>

			<table>
				<tr>
					<td style="width: 20px;"><p><input type="radio" name="{$querySourceFieldName}" id="{$querySourceFieldId}[area]" value="area" {$checkedQuerySource['area']}/></p></td>
					<td><p><label for="{$querySourceFieldId}[area]">Pick an area</label></p></td>
				</tr>
				<tr>
					<td></td>
					<td>
						<p>
							<label for="{$areaSourceConfigFieldId}_type">Area type</label>
							<select id="{$areaSourceConfigFieldId}_type" name="{$areaSourceConfigFieldName}[type]" class="widefat" onchange="dsWidgetListings.SwitchType(this, '{$areaSourceConfigFieldId}_title')">
								<option value="city" {$selectedAreaType['city']}>City</option>
								<option value="community" {$selectedAreaType['community']}>Community</option>
								<option value="tract" {$selectedAreaType['tract']}>Tract</option>
								<option value="zip" {$selectedAreaType['zip']}>Zip Code</option>
							</select>
						</p>

						<p>
							<label for="{$areaSourceConfigFieldId}[name]">Area name</label>
							<input id="{$areaSourceConfigFieldId}[name]" name="{$areaSourceConfigFieldName}[name]" class="widefat" type="text" value="{$areaSourceConfigName}" />
						</p>

						<p>
							<span class="description">See all <span id="{$areaSourceConfigFieldId}_title">{$selectedAreaTypeNormalized}</span> Names <a href="javascript:void(0);" onclick="dsWidgetListings.LaunchLookupList('{$ajaxLocationsUrl}', '{$areaSourceConfigFieldId}_type')">here</a></span>
						</p>
					</td>
				</tr>
				<tr>
					<th colspan="2"><p> - OR - </p></th>
				</tr>
				<tr>
					<td valign="top"><p><input type="radio" name="{$querySourceFieldName}" id="{$querySourceFieldId}[agentlistings]" value="agentlistings" {$checkedQuerySource['agentlistings']}/></p></td>
					<td>
						<p><label for="{$querySourceFieldId}[agentlistings]">My own listings (via agent ID, newest listings first)</label></p>
						<p><i>{$agentListingsNote}</i></p>
					</td>
				</tr>
				<tr>
					<th colspan="2"><p> - OR - </p></th>
				</tr>
				<tr>
					<td valign="top"><p><input type="radio" name="{$querySourceFieldName}" id="{$querySourceFieldId}[officelistings]" value="officelistings" {$checkedQuerySource['officelistings']}/></p></td>
					<td>
						<p><label for="{$querySourceFieldId}[officelistings]">My office's listings (via office ID, newest listings first)</label></p>
						<p><i>{$officeListingsNote}</i></p>
					</td>
				</tr>
HTML;
		if (!defined('ZPRESS_API')) {
			echo <<<HTML
		
				<tr>
					<th colspan="2"><p> - OR - </p></th>
				</tr>
				<tr>
					<td><p><input type="radio" name="{$querySourceFieldName}" id="{$querySourceFieldId}[link]" value="link" {$checkedQuerySource['link']}/></p></td>
					<td><p><label for="{$querySourceFieldId}[link]">Use a link you created in your website control panel</label></p></td>
				</tr>
				<tr>
					<td></td>
					<td>
						<p>
							<select name="{$linkSourceConfigFieldName}[linkId]" class="widefat">
HTML;
			if(isset($availableLinks)) {
				foreach ($availableLinks as $link) {
					$linkID = esc_attr($link->LinkID);
					$linkTitle = esc_html($link->Title);
					$linkSelected = array_key_exists($linkID, $selectedLink) ? "selected" : "";
					
					echo "<option value=\"{$linkID}\" {$linkSelected}>{$linkTitle}</option>";
				}
			}
			echo <<<HTML
							</select>
						</p>
					</td>
				</tr>
HTML;
		}
		echo <<<HTML
			</table>
HTML;
	}
}
?>