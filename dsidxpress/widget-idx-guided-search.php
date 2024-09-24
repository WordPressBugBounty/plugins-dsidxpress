<?php
add_action('wp_head', array('dsSearchAgent_IdxGuidedSearchWidget', 'LoadScripts'), 100);

class dsSearchAgent_IdxGuidedSearchWidget extends WP_Widget {
	public function __construct() {
		global $pagenow;

		parent::__construct("dsidx-search", "IDX Guided Search", array(
			"classname" => "dsidx-widget-guided-search",
			"description" => "Allow users to search from a curated list of cities, communites, tracts and/or zip codes."
		));

		if ($pagenow == 'widgets.php')
			wp_enqueue_script('dsidxpress_widget_search', DSIDXPRESS_PLUGIN_URL . 'js/widget-search.js', array('jquery'), DSIDXPRESS_PLUGIN_VERSION, true);	
	}
	public static function LoadScripts(){
		dsidxpress_autocomplete_mls_number::AddScripts(true);
    }
	function widget($args, $instance) {
		if(!isset($instance) || empty($instance)) {
			return;
		}
				
		extract($args);
		extract($instance);
		if (isset($title))
			$title = apply_filters("widget_title", esc_html($title));
		$options = get_option(DSIDXPRESS_OPTION_NAME);

		if (!isset($options["Activated"]) || !$options["Activated"])
			return;

		$pluginUrl = DSIDXPRESS_PLUGIN_URL;
		$ajaxUrl = admin_url( 'admin-ajax.php' );

		$formAction = get_home_url() . "/idx/";
		$capabilities = dsWidgets_Service_Base::getAllCapabilities();
		$capabilities = json_decode($capabilities['body'], true);
		
		$defaultSearchPanels = dsSearchAgent_ApiRequest::FetchData("AccountSearchPanelsDefault", array(), false, 60 * 60 * 24);
		$defaultSearchPanels = $defaultSearchPanels["response"]["code"] == "200" ? json_decode($defaultSearchPanels["body"]) : null;

		$propertyTypes = dsSearchAgent_GlobalData::GetPropertyTypes();

		$values =array();
		if (isset($_GET['idx-q-Locations'])) {
			$idx_q_Locations  = sanitize_text_field($_GET['idx-q-Locations']);
		}
		if (isset($_GET['idx-q-PriceMin'])) {
			$idx_q_PriceMin  = sanitize_text_field($_GET['idx-q-PriceMin']);
		}
		if (isset($_GET['idx-q-PriceMax'])) {
			$idx_q_PriceMax  = sanitize_text_field($_GET['idx-q-PriceMax']);
		}
		if (isset($_GET['idx-q-BedsMin'])) {
			$idx_q_BedsMin  = sanitize_text_field($_GET['idx-q-BedsMin']);
		}
		if (isset($_GET['idx-q-BathsMin'])) {
			$idx_q_BathsMin  = sanitize_text_field($_GET['idx-q-BathsMin']);
		}

		if (isset($_GET['idx-q-ImprovedSqFtMin'])) {
			$idx_q_ImprovedSqFtMin  = sanitize_text_field($_GET['idx-q-ImprovedSqFtMin']);
		}
		
		$values['idx-q-Locations'] = isset($idx_q_Locations) ? $idx_q_Locations : null;
		$values['idx-q-PropertyTypes'] = findArrayItems($_GET, 'idx-q-PropertyTypes');
		$values['idx-q-Cities'] = findArrayItems($_GET, 'idx-q-Cities');
		$values['idx-q-Communities'] = findArrayItems($_GET, 'idx-q-Communities');
		$values['idx-q-TractIdentifiers'] = findArrayItems($_GET, 'idx-q-TractIdentifiers');
		$values['idx-q-ZipCodes'] = findArrayItems($_GET, 'idx-q-ZipCodes');
		$values['idx-q-PriceMin'] = isset($idx_q_PriceMin) ? formatPrice($idx_q_PriceMin) : null;
		$values['idx-q-PriceMax'] = isset($idx_q_PriceMax) ? formatPrice($idx_q_PriceMax) : null;
		$values['idx-q-BedsMin'] = isset($idx_q_BedsMin) ? $idx_q_BedsMin : null;
		$values['idx-q-BathsMin'] = isset($idx_q_BathsMin) ?$idx_q_BathsMin : null;
		$values['idx-q-ImprovedSqFtMin'] = isset($idx_q_ImprovedSqFtMin) ? $idx_q_ImprovedSqFtMin : null;

		$specialSlugs = array(
			'city' 		=> 'idx-q-Cities',
			'community' => 'idx-q-Communities',
			'tract' 	=> 'idx-q-TractIdentifiers',
			'zip' 		=> 'idx-q-ZipCodes'
		);

		$urlParts = explode('/', $_SERVER['REQUEST_URI']);
		$count = 0;
		foreach($urlParts as $p){
			if(array_key_exists($p, $specialSlugs) && isset($urlParts[$count + 1])){
				array_push($values[$specialSlugs[$p]], $urlParts[$count + 1]);
			}
			$count++;
		}

		if (isset($before_widget))
			echo $before_widget;
		if ($title)
			echo $before_title . $title . $after_title;

		echo <<<HTML
			<div class="dsidx-resp-search-box dsidx-widget dsidx-resp-vertical">
			<form class="dsidx-resp-search-form" action="{$formAction}" method="get" onsubmit="return dsidx_w.searchWidget.validate();" >
				<fieldset>
				<div class="dsidx-resp-area">
				<label>Property Type</label>
				<select id="dsidx-resp-search-box-type" multiple="multiple">
HTML;

		if (is_array($propertyTypes)) {
			foreach ($propertyTypes as $propertyType) {
				$name = esc_html($propertyType->DisplayName);
				$id = esc_attr($propertyType->SearchSetupPropertyTypeID);
				$selected = in_array($propertyType->SearchSetupPropertyTypeID, $values['idx-q-PropertyTypes'])?' selected="selected"':'';
				echo "<option value=\"{$id}\"{$selected}>{$name}</option>";
			}
		}

		echo <<<HTML
				</select>
				<div class="dsidx-search-type-hidden-inputs">
HTML;
		
		echo <<<HTML
				</div>
				</div>

				<div class="dsidx-resp-area">
HTML;
		if ($searchOptions['show_cities'] == 'yes' && !empty($searchOptions['cities'])) {
			echo <<<HTML
				<label>City</label>
				<select id="idx-q-Cities" class="idx-q-Location-Filter" multiple="multiple">
HTML;
			foreach ($searchOptions["cities"] as $city) {
				$selected = in_array(strtolower(trim($city)), array_map('strtolower', $values['idx-q-Cities']))?' selected="selected"':'';
				// there's an extra trim here in case the data was corrupted before the trim was added in the update code below
				$city = trim($city);				
				echo "<option value=\"" . esc_attr($city) . "\"{$selected}>" . esc_html($city) . "</option>";
			}
		echo <<<HTML
				</select>
				<div class="dsidx-search-city-hidden-inputs">
HTML;
		echo <<<HTML
				</div>
				</div>
HTML;
		}
		if($searchOptions['show_communities'] == 'yes' && !empty($searchOptions['communities'])) {
			echo <<<HTML
				<div class="dsidx-resp-area">
				<label>Community</label>
				<select id="idx-q-Communities" class="idx-q-Location-Filter" multiple="multiple">
HTML;
			foreach ($searchOptions['communities'] as $community) {
				$selected = in_array(strtolower(trim($community)), array_map('strtolower', $values['idx-q-Communities']))?' selected="selected"':'';
				// there's an extra trim here in case the data was corrupted before the trim was added in the update code below
				$community = trim($community);
				echo "<option value=\"" . esc_attr($community) . "\"{$selected}>" . esc_html($community) . "</option>";
			}
		echo <<<HTML
				</select>
				<div class="dsidx-search-community-hidden-inputs">
HTML;
		echo <<<HTML
				</div>
				</div>
HTML;
		}
		if($searchOptions['show_tracts'] == 'yes' &&  !empty($searchOptions['tracts'])) {
			echo <<<HTML
				<div class="dsidx-resp-area">
				<label>Tract</label>
				<select id="idx-q-TractIdentifiers" class="idx-q-Location-Filter" multiple="multiple">
HTML;
			foreach ($searchOptions["tracts"] as $tract) {
				$selected = in_array(strtolower(trim($tract)), array_map('strtolower', $values['idx-q-TractIdentifiers']))? ' selected="selected"' : '';
				// there's an extra trim here in case the data was corrupted before the trim was added in the update code below
				$tract = trim($tract);
				echo "<option value=\"" . esc_attr($tract) . "\"{$selected}>" . esc_html($tract) . "</option>";
			}
		echo <<<HTML
				</select>
				<div class="dsidx-search-tract-hidden-inputs">
HTML;
		echo <<<HTML
				</div>
				</div>
HTML;
		}
		if($searchOptions['show_zips'] == 'yes' && !empty($searchOptions['zips'])) {
			echo <<<HTML
				<div class="dsidx-resp-area">
				<label>Zip</label>
				<select id="idx-q-ZipCodes" class="idx-q-Location-Filter" multiple="multiple">
HTML;
			foreach ($searchOptions["zips"] as $zip) {
				$selected = in_array(trim($zip), $values['idx-q-ZipCodes'])? ' selected="selected"' : '';
				// there's an extra trim here in case the data was corrupted before the trim was added in the update code below
				$zip = trim($zip);
				echo "<option value=\"" . esc_attr($zip) . "\"{$selected}>" . esc_html($zip) . "</option>";
			}
		echo <<<HTML
				</select>
				<div class="dsidx-search-zip-hidden-inputs">
HTML;
		echo <<<HTML
				</div>
				</div>
HTML;
		}
		if($searchOptions["show_mlsnumber"] == "yes") {
			echo <<<HTML
				<div class="dsidx-resp-area">
					<label for="dsidx-search-mls-number">MLS #</label>					
					<div id="dsidx-selected-search-mls-number" style="width:100%">
						<input id="dsidx-search-mls-number" type="text" class="dsidx-mlsnumber dsidx-search-omnibox-autocomplete-mls-number ui-autocomplete-input" data-role="tagsinput" placeholder="MLS #" value="" />
					</div>
					<div id="dsidx-autocomplete-spinner-guided-search" class="dsidx-autocomplete-mls-number-spinner" style="display:none;"><img src="https://api-idx.diversesolutions.com/Images/dsIDXpress/loadingimage.gif"></div>
				</div>
HTML;
		}

		$price_min = esc_attr($values['idx-q-PriceMin']);
		$price_max = esc_attr($values['idx-q-PriceMax']);

		echo <<<HTML
				<div class="dsidx-resp-area dsidx-resp-area-half dsidx-resp-area-half dsidx-resp-area-left">
				<label for="idx-q-PriceMin">Price From</label>
				<input id="idx-q-PriceMin" name="idx-q-PriceMin" type="text" class="dsidx-price" placeholder="Any" value="{$price_min}" maxlength="15" onkeypress="return dsidx.isDigitOnly(event,this.id)" onpaste="dsidx.validateDigitOnlyOnPaste(event)" />
</div>
				<div class="dsidx-resp-area dsidx-resp-area-half dsidx-resp-area-half dsidx-resp-area-right">
				<label for="idx-q-PriceMin">To</label>
				<input id="idx-q-PriceMax" name="idx-q-PriceMax" type="text" class="dsidx-price" placeholder="Any" value="{$price_max}" maxlength="15" onkeypress="return dsidx.isDigitOnly(event,this.id)" onpaste="dsidx.validateDigitOnlyOnPaste(event)" />
				</div>
HTML;
		
		echo <<<HTML
				<div class="dsidx-resp-area dsidx-resp-min-baths-area dsidx-resp-area-half dsidx-resp-area-left">
				<label for="idx-q-BedsMin">Beds</label>
				<select id="idx-q-BedsMin" name="idx-q-BedsMin" class="dsidx-beds">
					<option value="">Any</option>
HTML;
					for($i=1; $i<=9; $i++){
						$selected = $i == $values['idx-q-BedsMin']?' selected="selected"':'';
						echo '<option value="'.$i.'"'.$selected.'>'.$i.'+</option>';
					}
				echo <<<HTML
				</select>
				</div>

				<div class="dsidx-resp-area dsidx-resp-min-baths-area dsidx-resp-area-half dsidx-resp-area-right">
				<label for="idx-q-BathsMin">Baths</label>
				<select id="idx-q-BathsMin" name="idx-q-BathsMin" class="dsidx-baths">
					<option value="">Any</option>
HTML;
					for($i=1; $i<=9; $i++){
						$selected = $i == $values['idx-q-BathsMin']?' selected="selected"':'';
						echo '<option value="'.$i.'"'.$selected.'>'.$i.'+</option>';
					}
				echo <<<HTML
				</select>
				</div>
HTML;
				if(isset($defaultSearchPanels)){
				foreach ($defaultSearchPanels as $key => $value) {
				if ($value->DomIdentifier == "search-input-home-size" && isset($capabilities['MinImprovedSqFt']) && $capabilities['MinImprovedSqFt'] > 0) {
					$sqft_min = esc_attr($values['idx-q-ImprovedSqFtMin']);
					echo <<<HTML
						<div class="dsidx-resp-area">
						<label for="idx-q-ImprovedSqFtMin">Min Sqft</label>
						<input id="idx-q-ImprovedSqFtMin" name="idx-q-ImprovedSqFtMin" type="text" class="dsidx-improvedsqft" placeholder="Any" value="{$sqft_min}" onkeypress="return dsidx.isDigitOnly(event, this.id)" onpaste="dsidx.validateDigitOnlyOnPaste(event)" />
						</div>
HTML;
					break;
				}
			}
		}

		echo <<<HTML
				<div class="dsidx-resp-area dsidx-resp-area-submit">
					<label for="idx-q-PriceMin">&nbsp;</label>
					<input type="submit" class="submit" value="Search" onclick="return dsidx.compareMinMaxPrice_GuidedSearch();" />
				</div>
HTML;
		if($options["HasSearchAgentPro"] == "yes" && $searchOptions["show_advanced"] == "yes"){
			echo <<<HTML
					<div style="float: right;">
					try our&nbsp;<a href="{$formAction}advanced/"><img src="{$pluginUrl}assets/adv_search-16.png" /> Advanced Search</a>
					</div>
HTML;
		}
		echo <<<HTML
			</fieldset>
			</form>
			</div>
HTML;
		
		if (isset($after_widget))
			echo $after_widget;
		dsidx_footer::ensure_disclaimer_exists("search");
	}
	function update($new_instance, $old_instance) {
		$new_instance["title"] = sanitize_text_field($new_instance["title"]);

		if (is_string($new_instance["searchOptions"]["cities"])) {
			$new_instance["searchOptions"]["cities"] = sanitize_textarea_field($new_instance["searchOptions"]["cities"]);
			$new_instance["searchOptions"]["cities"] = explode("\n", $new_instance["searchOptions"]["cities"]);
		}

		if (is_string($new_instance["searchOptions"]["zips"])) {
			$new_instance["searchOptions"]["zips"] = sanitize_textarea_field($new_instance["searchOptions"]["zips"]);
			$new_instance["searchOptions"]["zips"] = explode("\n", $new_instance["searchOptions"]["zips"]);
		}
		
		if (is_string($new_instance["searchOptions"]["tracts"])) {
			$new_instance["searchOptions"]["tracts"] = sanitize_textarea_field($new_instance["searchOptions"]["tracts"]);
			$new_instance["searchOptions"]["tracts"] = explode("\n", $new_instance["searchOptions"]["tracts"]);
		}
		
		if (is_string($new_instance["searchOptions"]["communities"])) {
			$new_instance["searchOptions"]["communities"] = sanitize_textarea_field($new_instance["searchOptions"]["communities"]);
			$new_instance["searchOptions"]["communities"] = explode("\n", $new_instance["searchOptions"]["communities"]);
		}

		if (isset($new_instance["searchOptions"]["sortCities"]))
			$new_instance["searchOptions"]["sortCities"] = sanitize_text_field($new_instance["searchOptions"]["sortCities"]);
		
		if (isset($new_instance["searchOptions"]["sortTracts"]))
			$new_instance["searchOptions"]["sortTracts"] = sanitize_text_field($new_instance["searchOptions"]["sortTracts"]);
		
		if (isset($new_instance["searchOptions"]["sortCommunities"]))
			$new_instance["searchOptions"]["sortCommunities"] = sanitize_text_field($new_instance["searchOptions"]["sortCommunities"]);
		
		if (isset($new_instance["searchOptions"]["sortZips"]))
			$new_instance["searchOptions"]["sortZips"] = sanitize_text_field($new_instance["searchOptions"]["sortZips"]);
		
		if (isset($new_instance["searchOptions"]["show_cities"]))
			$new_instance["searchOptions"]["show_cities"] = sanitize_text_field($new_instance["searchOptions"]["show_cities"]);
		
		if (isset($new_instance["searchOptions"]["show_communities"]))
			$new_instance["searchOptions"]["show_communities"] = sanitize_text_field($new_instance["searchOptions"]["show_communities"]);
		
		if (isset($new_instance["searchOptions"]["show_tracts"]))
			$new_instance["searchOptions"]["show_tracts"] = sanitize_text_field($new_instance["searchOptions"]["show_tracts"]);
		
		if (isset($new_instance["searchOptions"]["show_zips"]))
			$new_instance["searchOptions"]["show_zips"] = sanitize_text_field($new_instance["searchOptions"]["show_zips"]);
		
		if (isset($new_instance["searchOptions"]["show_mlsnumber"]))
			$new_instance["searchOptions"]["show_mlsnumber"] = sanitize_text_field($new_instance["searchOptions"]["show_mlsnumber"]);
		
		if (isset($new_instance["searchOptions"]["show_advanced"]))
			$new_instance["searchOptions"]["show_advanced"] = sanitize_text_field($new_instance["searchOptions"]["show_advanced"]);

		if (isset($new_instance["searchOptions"]["sortZips"]) && $new_instance["searchOptions"]["sortZips"])
			sort($new_instance["searchOptions"]["zips"]);

		if (isset($new_instance["searchOptions"]["sortCities"]) && $new_instance["searchOptions"]["sortCities"])
			sort($new_instance["searchOptions"]["cities"]);

		if (isset($new_instance["searchOptions"]["sortTracts"]) && $new_instance["searchOptions"]["sortTracts"])
			sort($new_instance["searchOptions"]["tracts"]);

		if (isset($new_instance["searchOptions"]["sortCommunities"]) && $new_instance["searchOptions"]["sortCommunities"])
			sort($new_instance["searchOptions"]["communities"]);

		// we don't need to store this option
		unset($new_instance["searchOptions"]["sortCities"]);
		unset($new_instance["searchOptions"]["sortTracts"]);
		unset($new_instance["searchOptions"]["sortCommunities"]);
		unset($new_instance["searchOptions"]["sortZips"]);

		foreach ($new_instance["searchOptions"]["cities"] as &$area)
			$area = trim($area);
		foreach ($new_instance["searchOptions"]["tracts"] as &$area)
			$area = trim($area);
		foreach ($new_instance["searchOptions"]["communities"] as &$area)
			$area = trim($area);
		foreach ($new_instance["searchOptions"]["zips"] as &$area)
			$area = trim($area);

		/* we're doing this conversion from on/null to yes/no so that we can detect if the show_cities has never been
		 * set, because if it's never been set we want it to show */

		if(isset($new_instance["searchOptions"]["show_cities"]) && ($new_instance["searchOptions"]["show_cities"] == "on" || $new_instance["searchOptions"]["show_cities"] == "yes")) $new_instance["searchOptions"]["show_cities"] = "yes";
		else $new_instance["searchOptions"]["show_cities"] = "no";

		if(isset($new_instance["searchOptions"]["show_communities"]) && ($new_instance["searchOptions"]["show_communities"] == "on" || $new_instance["searchOptions"]["show_communities"] == "yes")) $new_instance["searchOptions"]["show_communities"] = "yes";
		else $new_instance["searchOptions"]["show_communities"] = "no";

		if(isset($new_instance["searchOptions"]["show_tracts"]) && ($new_instance["searchOptions"]["show_tracts"] == "on" || $new_instance["searchOptions"]["show_tracts"] == "yes")) $new_instance["searchOptions"]["show_tracts"] = "yes";
		else $new_instance["searchOptions"]["show_tracts"] = "no";

		if(isset($new_instance["searchOptions"]["show_zips"]) && ($new_instance["searchOptions"]["show_zips"] == "on" || $new_instance["searchOptions"]["show_zips"] == "yes")) $new_instance["searchOptions"]["show_zips"] = "yes";
		else $new_instance["searchOptions"]["show_zips"] = "no";

		if(isset($new_instance["searchOptions"]["show_mlsnumber"]) && ($new_instance["searchOptions"]["show_mlsnumber"] == "on" || $new_instance["searchOptions"]["show_mlsnumber"] == "yes")) $new_instance["searchOptions"]["show_mlsnumber"] = "yes";
		else $new_instance["searchOptions"]["show_mlsnumber"] = "no";

		if(isset($new_instance["searchOptions"]["show_advanced"]) && ($new_instance["searchOptions"]["show_advanced"] == "on" || $new_instance["searchOptions"]["show_advanced"] == "yes")) $new_instance["searchOptions"]["show_advanced"] = "yes";
		else $new_instance["searchOptions"]["show_advanced"] = "no";

		return $new_instance;
	}
	function form($instance) {
		$pluginUrl = DSIDXPRESS_PLUGIN_URL;
		$ajaxUrl = admin_url( 'admin-ajax.php' );

		$ajaxUrlCity = $ajaxUrl . '?action=dsidx_locations&type=city';
		$ajaxUrlCommunity = $ajaxUrl . '?action=dsidx_locations&type=community';
		$ajaxUrlTract = $ajaxUrl . '?action=dsidx_locations&type=tract';
		$ajaxUrlZip = $ajaxUrl . '?action=dsidx_locations&type=zip';
		
		$options = get_option(DSIDXPRESS_OPTION_NAME);

		$instance = wp_parse_args($instance, array(
			"title" => "Real Estate Search",
			"searchOptions" => array(
				"cities" => array(),
				"communities" => array(),
				"tracts" => array(),
				"zips" => array(),
				"show_cities" => 'yes',
				"show_communities" => 'no',
				"show_tracts" => 'no',
				"show_zips" => 'no',
				"show_mlsnumber" => 'no',
				"show_advanced" => 'no'
			)
		));

		$title = esc_attr($instance["title"]);
		$cities = esc_textarea(implode("\n", (array)$instance["searchOptions"]["cities"]));
		$communities = esc_textarea(implode("\n", (array)$instance["searchOptions"]["communities"]));
		$tracts = esc_textarea(implode("\n", (array)$instance["searchOptions"]["tracts"]));
		$zips = esc_textarea(implode("\n", (array)$instance["searchOptions"]["zips"]));

		$titleFieldId = $this->get_field_id("title");
		$titleFieldName = $this->get_field_name("title");
		$searchOptionsFieldId = $this->get_field_id("searchOptions");
		$searchOptionsFieldName = $this->get_field_name("searchOptions");

		$show_cities = $instance["searchOptions"]["show_cities"] == "yes" || !isset($instance["searchOptions"]["show_cities"]) ? "checked=\"checked\" " : "";
		$show_communities = $instance["searchOptions"]["show_communities"] == "yes" ? "checked=\"checked\" " : "";
		$show_tracts = $instance["searchOptions"]["show_tracts"] == "yes" ? "checked=\"checked\" " : "";
		$show_zips = $instance["searchOptions"]["show_zips"] == "yes" ? "checked=\"checked\" " : "";
		$show_mlsnumber = $instance["searchOptions"]["show_mlsnumber"] == "yes" ? "checked=\"checked\" " : "";
		$show_advanced = $instance["searchOptions"]["show_advanced"] == "yes" ? "checked=\"checked\" " : "";

		echo <<<HTML
			<p>
				<label for="{$titleFieldId}">Widget title</label>
				<input id="{$titleFieldId}" name="{$titleFieldName}" value="{$title}" class="widefat" type="text" />
			</p>

			<p>
				<h4>Fields to Display</h4>
				<div id="{$searchOptionsFieldId}-show_checkboxes" class="search-widget-searchOptions">
					<input type="checkbox" id="{$searchOptionsFieldId}-show_cities" name="{$searchOptionsFieldName}[show_cities]" {$show_cities} onclick="dsWidgetSearch.ShowBlock(this);"/>
					<label for="{$searchOptionsFieldId}-show_cities">Cities</label><br />
					<input type="checkbox" id="{$searchOptionsFieldId}-show_communities" name="{$searchOptionsFieldName}[show_communities]" {$show_communities} onclick="dsWidgetSearch.ShowBlock(this);"/>
					<label for="{$searchOptionsFieldId}-show_communities">Communities</label><br />
					<input type="checkbox" id="{$searchOptionsFieldId}-show_tracts" name="{$searchOptionsFieldName}[show_tracts]" {$show_tracts} onclick="dsWidgetSearch.ShowBlock(this);"/>
					<label for="{$searchOptionsFieldId}-show_tracts">Tracts</label><br />
					<input type="checkbox" id="{$searchOptionsFieldId}-show_zips" name="{$searchOptionsFieldName}[show_zips]" {$show_zips} onclick="dsWidgetSearch.ShowBlock(this);"/>
					<label for="{$searchOptionsFieldId}-show_zips">Zips</label><br />
					<input type="checkbox" id="{$searchOptionsFieldId}-show_mlsnumber" name="{$searchOptionsFieldName}[show_mlsnumber]" {$show_mlsnumber} onclick="dsWidgetSearch.ShowBlock(this);"/>
					<label for="{$searchOptionsFieldId}-show_mlsnumber">MLS #'s</label><br />
HTML;
		if($options["HasSearchAgentPro"] == "yes") {
			echo <<<HTML
					<input id="{$searchOptionsFieldId}-show-advanced" name="{$searchOptionsFieldName}[show_advanced]" class="checkbox" type="checkbox" {$show_advanced} onclick="dsWidgetSearch.ShowBlock(this);"/>
					<label for="{$searchOptionsFieldId}-show-advanced">Show Advanced Option</label>
HTML;
		}
			echo <<<HTML
				</div>
			</p>

			<div id="{$searchOptionsFieldId}-cities_block">
				<h4>Cities (one per line)</h4>
				<p>
					<textarea id="{$searchOptionsFieldId}[cities]" name="{$searchOptionsFieldName}[cities]" class="widefat" rows="10">{$cities}</textarea>
				</p>
				<p>
					<label for="{$searchOptionsFieldId}[sortCities]">Sort Cities</label>
					<input id="{$searchOptionsFieldId}[sortCities]" name="{$searchOptionsFieldName}[sortCities]" class="checkbox" type="checkbox" />
				</p>
				<p>
					<span class="description">See all City Names <a href="javascript:void(0);" onclick="dsWidgetSearch.LaunchLookupList('{$ajaxUrlCity}')">here</a></span>
				</p>
				<hr noshade="noshade" />
			</div>
			<div id="{$searchOptionsFieldId}-communities_block">
				<h3>Communities (one per line)</h3>
				<p>
					<textarea id="{$searchOptionsFieldId}[communities]" name="{$searchOptionsFieldName}[communities]" class="widefat" rows="10">{$communities}</textarea>
				</p>
				<p>
					<label for="{$searchOptionsFieldId}[sortCommunities]">Sort Communities</label>
					<input id="{$searchOptionsFieldId}[sortCommunities]" name="{$searchOptionsFieldName}[sortCommunities]" class="checkbox" type="checkbox" />
				</p>
				<p>
					<span class="description">See all Community Names <a href="javascript:void(0);" onclick="dsWidgetSearch.LaunchLookupList('{$ajaxUrlCommunity}')">here</a></span>
				</p>
				<hr noshade="noshade" />
			</div>

			<div id="{$searchOptionsFieldId}-tracts_block">
				<h3>Tracts (one per line)</h3>
				<p>
					<textarea id="{$searchOptionsFieldId}[tracts]" name="{$searchOptionsFieldName}[tracts]" class="widefat" rows="10">{$tracts}</textarea>
				</p>
				<p>
					<label for="{$searchOptionsFieldId}[sortTracts]">Sort Tracts</label>
					<input id="{$searchOptionsFieldId}[sortTracts]" name="{$searchOptionsFieldName}[sortTracts]" class="checkbox" type="checkbox" />
				</p>
				<p>
					<span class="description">See all Tract Names <a href="javascript:void(0);" onclick="dsWidgetSearch.LaunchLookupList('{$ajaxUrlTract}')">here</a></span>
				</p>
				<hr noshade="noshade" />
			</div>

			<div id="{$searchOptionsFieldId}-zips_block">
				<h3>Zips (one per line)</h3>
				<p>
					<textarea id="{$searchOptionsFieldId}[zips]" name="{$searchOptionsFieldName}[zips]" class="widefat" rows="10">{$zips}</textarea>
				</p>
				<p>
					<label for="{$searchOptionsFieldId}[sortZips]">Sort Zips</label>
					<input id="{$searchOptionsFieldId}[sortZips]" name="{$searchOptionsFieldName}[sortZips]" class="checkbox" type="checkbox" />
				</p>
				<p>
					<span class="description">See all Zips <a href="javascript:void(0);" onclick="dsWidgetSearch.LaunchLookupList('{$ajaxUrlZip}')">here</a></span>
				</p>
			</div>
			<script> jQuery(document).ready(function() { if(typeof dsWidgetSearch != "undefined") { dsWidgetSearch.InitFields(); } }); </script>
HTML;
	}
}
	function findArrayItems($args, $searchKey) {
		$itemsFound = array();		
		foreach($args as $key => $val) {
			$pKey = sanitize_key($key); 
			if(isset($pKey)) {
					if(strpos($key, $searchKey) === 0) {
						array_push($itemsFound, stripcslashes(sanitize_text_field($val)));
				}
			}			
		}
		
		return $itemsFound;
	}

	function formatPrice($price) {
		$sanitizedValue = sanitize_text_field($price);
		if(isset($sanitizedValue) && preg_match('/^[0-9]+(.[0-9]+)?$/', $price)) {			
			return number_format(str_replace(',', '', $price));
		}    
   		return "";
	}
?>