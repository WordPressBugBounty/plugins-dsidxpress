<?php

add_action("admin_init", array("dsSearchAgent_Admin", "Initialize"));
add_Action("admin_enqueue_scripts", array("dsSearchAgent_Admin", "Enqueue"));
add_action("admin_menu", array("dsSearchAgent_Admin", "AddMenu"), 40);
add_action("admin_notices", array("dsSearchAgent_Admin", "DisplayAdminNotices"));
add_action("wp_ajax_dsidxpress-dismiss-notification", array("dsSearchAgent_Admin", "DismissNotification"));
add_action("admin_notices", array("dsSearchAgent_Admin", "DisplayDeveloperNotice"));
add_action("wp_ajax_dsidxpress-dismiss-dev-notification", array("dsSearchAgent_Admin", "DismissDeveloperNotification"));
add_filter("manage_nav-menus_columns", array("dsSearchAgent_Admin", "CreateLinkBuilderMenuWidget"), 9);
add_action("admin_print_scripts", array("dsSearchAgent_Admin", "SetPluginUri"));

if (defined('ZPRESS_API') && ZPRESS_API != '') {
	add_filter('nav_menu_items_zpress-page', array('dsSearchAgent_Admin', 'NavMenus'));
}

class dsSearchAgent_Admin {
	static $HeaderLoaded = null;
	static $capabilities = array();
	static function AddMenu() {
		$options = get_option(DSIDXPRESS_OPTION_NAME);

		dsSearchAgent_Admin::GenerateAdminMenus(DSIDXPRESS_PLUGIN_URL . 'assets/idxpress_LOGOicon.png');
		dsSearchAgent_Admin::GenerateAdminSubMenus();

		add_filter("mce_external_plugins", array("dsSearchAgent_Admin", "AddTinyMcePlugin"));
		add_filter("mce_buttons", array("dsSearchAgent_Admin", "RegisterTinyMceButton"));
		// won't work until this <http://core.trac.wordpress.org/ticket/12207> is fixed
		//add_filter("tiny_mce_before_init", array("dsSearchAgent_Admin", "ModifyTinyMceSettings"));
	}
	static function GenerateAdminMenus($icon_url){
		add_menu_page('IDX', 'IDX', "manage_options", "dsidxpress", "", $icon_url);

		$activationPage = add_submenu_page("dsidxpress", "IDX Activation", "Activation", "manage_options", "dsidxpress", array("dsSearchAgent_Admin", "Activation"));
		add_action("admin_print_scripts-{$activationPage}", array("dsSearchAgent_Admin", "LoadHeader"));
	}

	static function GenerateAdminSubMenus() {
		$options = get_option(DSIDXPRESS_OPTION_NAME);

		if (isset($options["Activated"])) {
			$optionsPage = add_submenu_page("dsidxpress", "IDX Options", "General", "manage_options", "dsidxpress-options", array("dsSearchAgent_Admin", "EditOptions"));
			add_action("admin_print_scripts-{$optionsPage}", array("dsSearchAgent_Admin", "LoadHeader"));
		}

		if (isset($options["Activated"])) {
			$filtersPage = add_submenu_page("dsidxpress", "IDX Filters", "Filters", "manage_options", "dsidxpress-filters", array("dsSearchAgent_Admin", "FilterOptions"));
			add_action("admin_print_scripts-{$filtersPage}", array("dsSearchAgent_Admin", "LoadHeader"));
		}

		if (isset($options["Activated"])) {
			$seoSettingsPage = add_submenu_page("dsidxpress", "IDX SEO Settings", "SEO Settings", "manage_options", "dsidxpress-seo-settings", array("dsSearchAgent_Admin", "SEOSettings"));
			add_action("admin_print_scripts-{$seoSettingsPage}", array("dsSearchAgent_Admin", "LoadHeader"));
		}

		if (isset($options["Activated"])) {
			$xmlSitemapsPage = add_submenu_page("dsidxpress", "IDX XML Sitemaps", "XML Sitemaps", "manage_options", "dsidxpress-xml-sitemaps", array("dsSearchAgent_Admin", "XMLSitemaps"));
			add_action("admin_print_scripts-{$xmlSitemapsPage}", array("dsSearchAgent_Admin", "LoadHeader"));
		}

		if (isset($options["Activated"])) {
			$detailsPage = add_submenu_page("dsidxpress", "IDX Details", "More Options", "manage_options", "dsidxpress-details", array("dsSearchAgent_Admin", "DetailsOptions"));
			add_action("admin_print_scripts-{$detailsPage}", array("dsSearchAgent_Admin", "LoadHeader"));
		}
	}
	static function AddTinyMcePlugin($plugins) {
		$userOptions = get_option(DSIDXPRESS_OPTION_NAME);
		$plugins["idxlisting"] = DSIDXPRESS_PLUGIN_URL . "tinymce/single_listing/editor_plugin.js";
		$plugins["idxlistings"] = DSIDXPRESS_PLUGIN_URL . "tinymce/multi_listings/editor_plugin.js";
		$plugins["idxquicksearch"] = DSIDXPRESS_PLUGIN_URL . "tinymce/idx_quick_search/editor_plugin.js";
		/*DIV-16050:Registration form shortcode will only be added for pro users*/
		if(isset($userOptions["dsIDXPressPackage"]) && $userOptions["dsIDXPressPackage"] == "pro") {
			$plugins["idxregistrationform"] = DSIDXPRESS_PLUGIN_URL . "tinymce/idx_registration_form/editor_plugin.js";
		}	
		return $plugins;
	}
	static function RegisterTinyMceButton($buttons) {
		array_push($buttons, "separator", "idxlisting", "idxlistings", "idxquicksearch","idxregistrationform");
		return $buttons;
	}
	static function ModifyTinyMceSettings($settings) {
		$settings["wordpress_adv_hidden"] = 0;
		return $settings;
	}
	static function Initialize() {
		register_setting("dsidxpress_activation", DSIDXPRESS_OPTION_NAME, array("dsSearchAgent_Admin", "SanitizeOptions"));
		register_setting("dsidxpress_api_options", DSIDXPRESS_OPTION_NAME, array("dsSearchAgent_Admin", "SanitizeOptions"));
		register_setting("dsidxpress_options", DSIDXPRESS_OPTION_NAME, array("dsSearchAgent_Admin", "SanitizeOptions"));
		register_setting("dsidxpress_xml_sitemap", DSIDXPRESS_OPTION_NAME, array("dsSearchAgent_Admin", "SanitizeOptions"));
		register_setting("dsidxpress_api_options", DSIDXPRESS_API_OPTIONS_NAME, array("dsSearchAgent_Admin", "SanitizeApiOptions"));
		register_setting("dsidxpress_options", DSIDXPRESS_API_OPTIONS_NAME, array("dsSearchAgent_Admin", "SanitizeApiOptions"));
		$capabilities = dsSearchAgent_ApiRequest::FetchData('MlsCapabilities');
		if (isset($capabilities['body'])) {
			self::$capabilities = json_decode($capabilities['body'], true);
		}
    (new self)->getGoogleMapsAPIKey();
	}
  function getGoogleMapsAPIKey(){
    $apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("AccountOptions", array(), false, 0);
	
	if (empty($apiHttpResponse["errors"]) && $apiHttpResponse["response"]["code"] == "200") {
		$account_options = json_decode($apiHttpResponse["body"]);
		$googleMapAPIsAPIKey = isset($account_options->{'GoogleMapsAPIKey'})? esc_html($account_options->{'GoogleMapsAPIKey'}):'';

		if (!defined("DSIDXPRESS_GOOGLEMAP_API_KEY")) 
			define("DSIDXPRESS_GOOGLEMAP_API_KEY", $googleMapAPIsAPIKey);
	}
  }
	static function Enqueue($hook) {
		//every admin should have admin-options.js cept dsidx_footer-util
		if(!isset($_GET['page'])){
			wp_enqueue_script('dsidxpress_admin_options', DSIDXPRESS_PLUGIN_URL . 'js/admin-options.js', array(), DSIDXPRESS_PLUGIN_VERSION, true);
		}
		$post_type ='';

		if (isset($_GET['page'])) {
			$page  = sanitize_text_field($_GET['page']);
		}

		if (isset($_GET['action'])) {
			$action  = sanitize_text_field($_GET['action']);
		}

		if (isset($_GET['post_type'])) {
			$post_type  = sanitize_text_field($_GET['post_type']);
		}
		
		
		if (isset($page) && $page && ($page == 'dsidxpress-details' || $page == 'dsidxpress-seo-settings' || 
		 	$page == 'dsidxpress-options' || $page == 'dsidxpress-xml-sitemaps')) {
			wp_enqueue_script('dsidxpress_admin_options', DSIDXPRESS_PLUGIN_URL . 'js/admin-options.js', array(), DSIDXPRESS_PLUGIN_VERSION, true);
		}

		//We need the options script loaded in the header for this page
		if (isset($page) && $page && $page == 'dsidxpress-xml-sitemaps') {
			wp_enqueue_script('dsidxpress_admin_options', DSIDXPRESS_PLUGIN_URL . 'js/admin-options.js', array(), DSIDXPRESS_PLUGIN_VERSION);
		}

		if (isset($page) && $page && $page == 'dsidxpress-filters') {
			wp_enqueue_script('dsidxpress_admin_filters', DSIDXPRESS_PLUGIN_URL . 'js/admin-filters.js', array(), DSIDXPRESS_PLUGIN_VERSION);
		} 
		 
		if ($hook == 'nav-menus.php' || $hook == 'post.php' || $hook == 'post-new.php') {
			$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("AccountOptions", array(), false, 0);
	 
			if (empty($apiHttpResponse["errors"]) && $apiHttpResponse["response"]["code"] == "200") {
				if(isset($apiHttpResponse['body'])) {
					$account_options = json_decode($apiHttpResponse["body"]);
					$dsIDXPressPackage = isset($account_options->{'dsIDXPress-Package'})? esc_html($account_options->{'dsIDXPress-Package'}):'';
					$mapLatLangResponse = dsSearchAgent_ApiRequest::FetchData("GetMLSMapLatLang", array(), false, 0);
					if (empty($mapLatLangResponse["errors"]) && $mapLatLangResponse["response"]["code"] == "200") {						
						$mapLatLangData = json_decode($mapLatLangResponse["body"]);
					}	
				}
			}
			
			if(defined("DSIDXPRESS_GOOGLEMAP_API_KEY")) {
				wp_enqueue_script('dsidxpress_google_maps_geocode_api', '//maps.googleapis.com/maps/api/js?key='.DSIDXPRESS_GOOGLEMAP_API_KEY.'&libraries=drawing,geometry');
			}

			wp_enqueue_script('dsidxpress_admin_utilities', DSIDXPRESS_PLUGIN_URL . 'js/admin-utilities.js', array(), DSIDXPRESS_PLUGIN_VERSION, true);
			wp_localize_script('dsidxpress_admin_utilities', 'mlsCapabilities', self::$capabilities);

			if(isset($dsIDXPressPackage)) {
				wp_add_inline_script('dsidxpress_admin_utilities', 'var dsIDXPressPackage = "' . $dsIDXPressPackage . '";', 'before');
			}
			
			if(isset($mapLatLangData)) {
				wp_localize_script('dsidxpress_admin_utilities', 'dsIDXSeachSetupMapData', $mapLatLangData);
			}

			wp_enqueue_style('dsidxpress_admin_options_style', DSIDXPRESS_PLUGIN_URL . 'css/admin-options.css', array(), DSIDXPRESS_PLUGIN_VERSION);
			wp_enqueue_script( 'jquery-ui-autocomplete', '', array( 'jquery-ui-widget', 'jquery-ui-position' ), '1.10.4' );
		}

		if (($hook == 'post.php' && sanitize_text_field($_GET['action']) == 'edit') || $hook == 'post-new.php' && isset($_GET['post_type']) && sanitize_text_field($_GET['post_type']) == 'ds-idx-listings-page') {
			wp_enqueue_style('dsidxpress_admin_options_style', DSIDXPRESS_PLUGIN_URL . 'css/admin-options.css', array(), DSIDXPRESS_PLUGIN_VERSION);
		}
	}

	static function SetPluginUri(){
		$pluginUrl = DSIDXPRESS_PLUGIN_URL;
		echo <<<HTML
			<script type="text/javascript">
				var dsIdxPluginUri = "$pluginUrl";
			</script>
HTML;
	}
	static function LoadHeader() {
		if (self::$HeaderLoaded)
			return;

		wp_enqueue_style('dsidxpress_admin_options_style', DSIDXPRESS_PLUGIN_URL . 'css/admin-options.css', array(), DSIDXPRESS_PLUGIN_VERSION);

		self::$HeaderLoaded = true;
	}
	static function DisplayAdminNotices() {
		if (!current_user_can("manage_options") || (defined('ZPRESS_API') && ZPRESS_API != ''))
			return;

		$options = get_option(DSIDXPRESS_OPTION_NAME);
		if (isset($_GET["page"])) {
			$page = sanitize_text_field($_GET["page"]);
		}
		if (!isset($options["PrivateApiKey"])) { ?>
			<div class="error">
					<p style="line-height: 1.6;">
					<?php if(!isset($page) || esc_html($page)!="dsidxpress"){ ?>
						<a href="admin.php?page=dsidxpress" class="button-primary">Activate the dsIDXpress Plugin</a>	
					<?php }?>
					 <span> Enter your dsIDXpress activation key to start your free trial or paid subscription. </span>
					</p>
				</div>

		<?php	} else if (isset($options["PrivateApiKey"]) && empty($options["Activated"])) {
			echo <<<HTML
				<div class="error">
					<p style="line-height: 1.6;">
						It looks like there may be a problem with the dsIDXpress that's installed on this blog.
						Please take a look at the <a href="admin.php?page=dsidxpress">dsIDXpress diagnostics area</a>
						to find out more about any potential issues
					</p>
				</div>
HTML;
		} else if (isset($options["Activated"]) && empty($options["HideIntroNotification"])) {
			wp_nonce_field("dsidxpress-dismiss-notification", "dsidxpress-dismiss-notification", false);
			echo <<<HTML
				<script>
					function dsidxpressDismiss() {
						jQuery.post(ajaxurl, {
							action: 'dsidxpress-dismiss-notification',
							_ajax_nonce: jQuery('#dsidxpress-dismiss-notification').val()
						});
						jQuery('#dsidxpress-intro-notification').slideUp();
					}
				</script>
				<div id="dsidxpress-intro-notification" class="updated">
					<p style="line-height: 1.6;">Now that you have the <strong>dsIDXpress plugin</strong>
						activated, you'll probably want to start adding <strong>live MLS content</strong>
						to your site right away. The easiest way to get started is to use the three new IDX widgets that have
						been added to your <a href="widgets.php">widgets page</a> and the two new IDX icons
						(they look like property markers) that have been added to the visual editor for
						all of your <a href="post-new.php?post_type=page">pages</a> and <a href="post-new.php">posts</a>.
						You'll probably also want to check out our <a href="https://helpdesk.diversesolutions.com/ds-idxpress/dsidxpress-link-structure-for-loading-mls-content/"
							target="_blank">dsIDXpress virtual page link structure guide</a> so that you
						can start linking to the property listings and property details pages throughout
						your blog. Finally, you may also want to hop over to our
						<a href="https://helpdesk.diversesolutions.com/category/ds-idxpress/" target="_blank">help desk</a> or our
						<a href="https://forum.diversesolutions.com/" target="_blank">forum</a>.
					</p>
					<p style="line-height: 1.6; text-align: center; font-weight: bold;">Take a look at the
						<a href="https://helpdesk.diversesolutions.com/category/ds-idxpress/" target="_blank">dsIDXpress getting
						started guide</a> for more info.
					</p>
					<p style="text-align: right;">(<a href="javascript:void(0)" onclick="dsidxpressDismiss()">dismiss this message</a>)</p>
				</div>
HTML;
		}
	}
	static function DismissNotification() {
		if(isset($_POST["action"])) {
			$action = sanitize_text_field($_POST["action"]); 
			if(!empty($action)) {
				check_ajax_referer($action);
			}	
		}

		$options = get_option(DSIDXPRESS_OPTION_NAME);
		$options["HideIntroNotification"] = true;
		update_option(DSIDXPRESS_OPTION_NAME, $options);
		die();
	}
	/*DIV-16203:Feature: Admin notice to entice developers for partner program */
	static function DisplayDeveloperNotice() {
		if (!current_user_can("manage_options"))
			return;

		if (isset($_GET['page'])) {
			$page  = sanitize_text_field($_GET['page']);
		}

		if (isset($_GET['post_type'])) {
			$post_type  = sanitize_text_field($_GET['post_type']);
		}

		global $pagenow;
		if ($pagenow != "index.php")
			if (!isset($page) || (isset($page) && stripos($page, "dsidxpress") === false))
				if (!isset($post_type) || (isset($post_type) && stripos($post_type, "ds-idx") === false))
					return;
		$options = get_option(DSIDXPRESS_OPTION_NAME);
		if (!empty($options["HideDevIntroNotice"]) && $options["HideDevIntroNotice"] === true)
			return;

		echo <<<HTML
<script>
	function dsidxpressDismissDev() {
		jQuery.post(dsidxAjaxHandler.ajaxurl, {
			action: "dsidxpress-dismiss-dev-notification",
			_ajax_nonce: jQuery("#dsidxpress-dismiss-dev-notification").val()
		});
		jQuery("#dsidxpress-dev-notification").slideUp();
	}
</script>
<div id="dsidxpress-dev-notification" class="notice notice-info" style="position:relative">
HTML;
		wp_nonce_field("dsidxpress-dismiss-dev-notification", "dsidxpress-dismiss-dev-notification", false);
		echo <<<HTML
	<p style="line-height: 1.6;display: inline-block;position:relative;">
		<strong>Developers:</strong> Grow your business with the <a href="https://www.diversesolutions.com/developers/partner-program/" style="font-weight: 600;" target="_blank">Diverse Solutions Partner Program</a> by referring our IDX products to your clients.</p>
	<p style="text-align: right;float:right;display: inline-block;position:relative;">
		(<a href="javascript:void(0)" onclick="dsidxpressDismissDev()">dismiss this message</a>)</p>
</div>
HTML;
	}
	static function DismissDeveloperNotification() {
		
		if(isset($_POST["action"])) {
			$action =  sanitize_text_field($_POST["action"]);
			if($action ) {
				check_ajax_referer($action);
			}			
	 	}
		$options = get_option(DSIDXPRESS_OPTION_NAME);
		$options["HideDevIntroNotice"] = true;
		update_option(DSIDXPRESS_OPTION_NAME, $options);
		die();
	}
	static function EditOptions() {
		$options = get_option(DSIDXPRESS_OPTION_NAME);

		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("AccountOptions", array(), false, 0);
		if (!empty($apiHttpResponse["errors"]) || $apiHttpResponse["response"]["code"] != "200")
			wp_die("We're sorry, but we ran into a temporary problem while trying to load the account data. Please check back soon.", "Account data load error");
		else
			$account_options = json_decode($apiHttpResponse["body"]);

			$customTitleText = esc_html($account_options->CustomTitleText);
			$isResultsPageModernView = esc_html($account_options->IsResultsPageModernView);
			$mapOrientationInResultsPage = esc_html($account_options->MapOrientationInResultsPage);
			$showMapInResultsPage = esc_html($account_options->ShowMapInResultsPage);
			$useAcresInsteadOfSqFt = esc_html($account_options->UseAcresInsteadOfSqFt);
			$showMapInResultsPage = esc_html($account_options->ShowMapInResultsPage);
			$registrationShowConsent = esc_html($account_options->RegistrationShowConsent);
			$registrationConsentLastUpdatedDate = esc_html($account_options->RegistrationConsentLastUpdatedDate);
			$requiredPhone = esc_html($account_options->RequiredPhone);
			$allowedDetailViewsBeforeRegistration = esc_html($account_options->AllowedDetailViewsBeforeRegistration);
			$allowedSearchesBeforeRegistration = esc_html($account_options->AllowedSearchesBeforeRegistration);			
			$requireAuth_Details_Description = esc_html($account_options->{'RequireAuth-Details-Description'});
			$requireAuth_Property_Community = esc_html($account_options->{'RequireAuth-Property-Community'});
			$requireAuth_Property_Tract = esc_html($account_options->{'RequireAuth-Property-Tract'});
			$requireAuth_Details_Schools = esc_html($account_options->{'RequireAuth-Details-Schools'});
			$requireAuth_Details_AdditionalInfo = esc_html($account_options->{'RequireAuth-Details-AdditionalInfo'});
			$requireAuth_Details_AdditionalInfo = esc_html($account_options->{'RequireAuth-Details-AdditionalInfo'});
			$requireAuth_Details_PriceChanges = esc_html($account_options->{'RequireAuth-Details-PriceChanges'});
			$requireAuth_Details_Features = esc_html($account_options->{'RequireAuth-Details-Features'});
			$requireAuth_Property_DaysOnMarket = esc_html($account_options->{'RequireAuth-Property-DaysOnMarket'});
			$requireAuth_Property_LastUpdated = esc_html($account_options->{'RequireAuth-Property-LastUpdated'});
			$requireAuth_Property_YearBuilt = esc_html($account_options->{'RequireAuth-Property-YearBuilt'});
			
			$firstName = esc_html($account_options->FirstName);
			$lastName = esc_html($account_options->LastName);
			$email = esc_html($account_options->Email);
			$mobileSiteUrl = esc_html($account_options->MobileSiteUrl);
			$agentID = esc_html($account_options->AgentID);
			$officeID = esc_html($account_options->OfficeID);
			$enableMemcacheInDsIdxPress = esc_html($account_options->EnableMemcacheInDsIdxPress);
			$enableMemcacheInDsIdxPress = esc_html($account_options->EnableMemcacheInDsIdxPress);


		$urlBase = get_home_url();
		if (substr($urlBase, strlen($urlBase), 1) != "/") $urlBase .= "/";
		$urlBase .= dsSearchAgent_Rewrite::GetUrlSlug();

		if (isset($_REQUEST['settings-updated'])) {
			$settings_updated = sanitize_text_field($_REQUEST['settings-updated']);
		}
		
?>
	<div class="wrap metabox-holder">
		<h1>General Options</h1>
		<?php if (isset($settings_updated) &&  $settings_updated == 'true') : ?>
		<div class="updated"><p><strong><?php _e( 'Options saved' ); ?></strong></p></div>
		<?php endif; ?>
		<form method="post" action="options.php" onsubmit="return dsIDXpressOptions.FilterViews();">
		<?php settings_fields("dsidxpress_options"); ?>
		<h2>Display Settings</h2>
			<table class="form-table">
				<?php if(!defined('ZPRESS_API') || ZPRESS_API == '') : ?>
				<tr>
					<th>
						<label for="dsidxpress-DetailsTemplate">Template for details pages:</label>
					</th>
					<td>
						<select id="dsidxpress-DetailsTemplate" name="<?php echo esc_attr(DSIDXPRESS_OPTION_NAME) ; ?>[DetailsTemplate]">
							<option value="">- Default -</option>
							<?php
								$details_template = (isset($options["DetailsTemplate"])) ? $options["DetailsTemplate"] : '';
								page_template_dropdown($details_template);
							?>
						</select><br />
						<span class="description">Some themes have custom templates that can change how a particular page is displayed. If your theme does have multiple templates, you'll be able to select which one you want to use in the drop-down above.</span>
					</td>
				</tr>
				<tr>
					<th>
						<label for="dsidxpress-ResultsTemplate">Template for results pages:</label>
					</th>
					<td>
						<select id="dsidxpress-ResultsTemplate" name="<?php echo esc_attr(DSIDXPRESS_OPTION_NAME) ; ?>[ResultsTemplate]">
							<option value="">- Default -</option>
							<?php
								$results_template = (isset($options["ResultsTemplate"])) ? $options["ResultsTemplate"] : '';
								page_template_dropdown($results_template);
							?>
						</select><br />
						<span class="description">See above.</span>
					</td>
				</tr>
				<tr>
					<th>
						<label for="dsidxpress-AdvancedTemplate">Template for dsSearchAgent:</label>
					</th>
					<td>
						<select id="dsidxpress-AdvancedTemplate" name="<?php echo esc_attr(DSIDXPRESS_OPTION_NAME) ; ?>[AdvancedTemplate]">
							<option value="">- Default -</option>
							<?php
								$advanced_template = (isset($options["AdvancedTemplate"])) ? $options["AdvancedTemplate"] : '';
								page_template_dropdown($advanced_template);
							?>
						</select><br />
						<span class="description">See above.</span>
					</td>
				</tr>
				<tr>
					<th>
						<label for="dsidxpress-IDXTemplate">Template for IDX pages:</label>
					</th>
					<td>
						<select id="dsidxpress-IDXTemplate" name="<?php echo esc_attr(DSIDXPRESS_OPTION_NAME) ; ?>[IDXTemplate]">
							<option value="">- Default -</option>
							<?php
								$idx_template = (isset($options["IDXTemplate"])) ? $options["IDXTemplate"] : '';
								page_template_dropdown($idx_template);
							?>
						</select><br />
						<span class="description">See above.</span>
					</td>
				</tr>
				<tr>
					<th>
						<label for="dsidxpress-404Template">Template for error pages:</label>
					</th>
					<td>
						<select id="dsidxpress-404Template" name="<?php echo esc_attr(DSIDXPRESS_OPTION_NAME) ; ?>[404Template]">
							<option value="">- Default -</option>
							<optgroup label="Template">
							<?php
								$error_template = (isset($options["404Template"])) ? esc_html($options["404Template"]) : '';
								$error_404 = locate_template('404.php');
								if(!empty($error_404)){
							?>
							<option value="404.php"<?php echo ($error_template == '404.php' ? ' selected' : ''); ?>>404.php</option>
							<?php
								}
							?>
							<?php
								$error_template = (isset($options["404Template"])) ? esc_html($options["404Template"]) : '';
								page_template_dropdown($error_template);
							?>
							</optgroup>
							<optgroup label="Page">
							<?php
								$pages = get_posts(
									array(
										'post_type' => 'page',
										'posts_per_page' => -1
									)
								);
								foreach( $pages as $page ){
									$pageId = esc_attr($page->ID);
									$postTitle = esc_html($page->post_title);
									echo '<option value="' . $pageId . '"' . ( $error_template == $pageId ? ' selected' : '' ) . '>' . $postTitle . '</option>';
								}
								wp_reset_postdata();
							?>
							</optgroup>
						</select><br />
						<span class="description">See above.</span>
					</td>
				</tr>
				<?php endif; ?>
				<tr>
					<th>
						<label for="dsidxpress-CustomTitleText">Title for results pages:</label>
					</th>
					<td>
						<input type="text" id="dsidxpress-CustomTitleText" maxlength="49" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[CustomTitleText]" value="<?php echo esc_html($customTitleText); ?>" /><br />
						<span class="description">By default, the titles are auto-generated based on the type of area searched. You can override this above; use <code>%title%</code> to designate where you want the location title. For example, you could use <code>Real estate in the %title%</code>.</span>
					</td>
				</tr>
				<tr>
					<th>
						<label for="dsidxpress-ResultsMapDefaultState">Default view for Results pages:</label>
					</th>
					<td>
					<?php
							$ResultsDefaultState = isset($options["dsIDXPressPackage"]) &&  esc_html($options["dsIDXPressPackage"]) == "pro" ? "grid" : "list";
							if(isset($isResultsPageModernView) && !empty($isResultsPageModernView) && strtolower($isResultsPageModernView) == "true")
							{
								$ResultsDefaultStateClassicView = "";
								$ResultsDefaultStateModernView = !isset($options["ResultsDefaultStateModernView"]) ? $ResultsDefaultState : esc_html($options["ResultsDefaultStateModernView"]);
								$MapOrientationModernView = !isset($mapOrientationInResultsPage) ? "left" : strtolower(esc_html($mapOrientationInResultsPage));								
							}
							else
							{
								$ResultsDefaultStateClassicView = !isset($options["ResultsDefaultState"]) ? $ResultsDefaultState : $options["ResultsDefaultState"];
								$ResultsDefaultStateModernView = "";
								$MapOrientationModernView = "";
							}
						?>
						<input type="radio" class="dsidxpress-api-radio" id="dsidxpress-IsResultsPageModernView-ClassicView" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[IsResultsPageModernView]" onchange="ResultsPageViewChanged(this)" value="false" <?php echo @ !isset($isResultsPageModernView) || empty($isResultsPageModernViews) || strtolower($isResultsPageModernView) == "false" ? "checked=\"checked\"" : "" ?>/> <label for="dsidxpress-IsResultsPageModernView-ClassicView">Classic View</label><br />
						<div style="margin-top: 10px; margin-left: 20px;">
							<input type="radio" id="dsidxpress-ResultsDefaultState-List" name="<?php echo esc_attr(DSIDXPRESS_OPTION_NAME); ?>[ResultsDefaultState]" value="list" <?php echo @$ResultsDefaultStateClassicView == "list" ? "checked=\"checked\"" : "" ?> <?php echo @ empty($ResultsDefaultStateClassicView) ? "disabled=\"disabled\"" : "" ?> /> <label for="dsidxpress-ResultsDefaultState-List">List</label><br />
							<input type="radio" id="dsidxpress-ResultsDefaultState-ListMap" name="<?php echo esc_attr(DSIDXPRESS_OPTION_NAME); ?>[ResultsDefaultState]" value="listmap" <?php echo @$ResultsDefaultStateClassicView == "listmap" ? "checked=\"checked\"" : "" ?> <?php echo @ empty($ResultsDefaultStateClassicView) ? "disabled=\"disabled\"" : "" ?> /> <label for="dsidxpress-ResultsDefaultState-ListMap">List + Map</label>
							<?php if (defined('ZPRESS_API') || isset($options["dsIDXPressPackage"]) && $options["dsIDXPressPackage"] == "pro"): ?>
							<br /><input type="radio" id="dsidxpress-ResultsDefaultState-Grid" name="<?php echo esc_attr(DSIDXPRESS_OPTION_NAME); ?>[ResultsDefaultState]" value="grid" <?php echo @$ResultsDefaultStateClassicView == "grid" ? "checked=\"checked\"" : "" ?> <?php echo @ empty($ResultsDefaultStateClassicView) ? "disabled=\"disabled\"" : "" ?> /> <label for="dsidxpress-ResultsDefaultState-Grid">Grid</label>
							<?php endif ?>
						</div>
						<br/>
						<input type="radio" class="dsidxpress-api-radio" id="dsidxpress-IsResultsPageModernView-ModernView" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[IsResultsPageModernView]" onchange="ResultsPageViewChanged(this)" value="true" <?php echo @ strtolower($isResultsPageModernView) == "true" ? "checked=\"checked\"" : "" ?>/> <label for="dsidxpress-IsResultsPageModernView-ModernView">Modern View</label><br />
						<div style="margin-top: 10px; margin-left: 20px;">
							<input type="radio" id="dsidxpress-ResultsDefaultState-List-ModernView" name="<?php echo esc_attr(DSIDXPRESS_OPTION_NAME); ?>[ResultsDefaultStateModernView]" value="list" <?php echo @$ResultsDefaultStateModernView == "list" ? "checked=\"checked\"" : "" ?> <?php echo @ empty($ResultsDefaultStateModernView) ? "disabled=\"disabled\"" : "" ?> /> <label for="dsidxpress-ResultsDefaultState-List-ModernView">List</label>
							<?php if (defined('ZPRESS_API') || isset($options["dsIDXPressPackage"]) && $options["dsIDXPressPackage"] == "pro"): ?>
							<br /><input type="radio" id="dsidxpress-ResultsDefaultState-Grid-ModernView" name="<?php echo esc_attr(DSIDXPRESS_OPTION_NAME); ?>[ResultsDefaultStateModernView]" value="grid" <?php echo @$ResultsDefaultStateModernView == "grid" ? "checked=\"checked\"" : "" ?> <?php echo @ empty($ResultsDefaultStateModernView) ? "disabled=\"disabled\"" : "" ?> /> <label for="dsidxpress-ResultsDefaultState-Grid-ModernView">Grid</label>
							<?php endif ?>
							<br /><br />
							<label>Map Orientation</label>
							<div style="margin-top: 10px; margin-left: 20px;">
								<input type="radio" id="dsidxpress-MapOrientation-Left-ModernView" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[MapOrientationInResultsPage]" value="left" <?php echo @$MapOrientationModernView == "left" ? "checked=\"checked\"" : "" ?> <?php echo @ empty($ResultsDefaultStateModernView) ? "disabled=\"disabled\"" : "" ?> /> <label for="dsidxpress-MapOrientation-Left-ModernView">Left</label><br />
								<input type="radio" id="dsidxpress-MapOrientation-Right-ModernView" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[MapOrientationInResultsPage]" value="right" <?php echo @$MapOrientationModernView == "right" ? "checked=\"checked\"" : "" ?> <?php echo @ empty($ResultsDefaultStateModernView) ? "disabled=\"disabled\"" : "" ?> /> <label for="dsidxpress-MapOrientation-Right-ModernView">Right</label><br />
								<input type="radio" id="dsidxpress-MapOrientation-Top-ModernView" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[MapOrientationInResultsPage]" value="top" <?php echo @$MapOrientationModernView == "top" ? "checked=\"checked\"" : "" ?> <?php echo @ empty($ResultsDefaultStateModernView) ? "disabled=\"disabled\"" : "" ?> /> <label for="dsidxpress-MapOrientation-Top-ModernView">Top</label>
							</div>
							<br />
							<input type="checkbox" id="dsidxpress-ShowMapInResultsPage-check" class="dsidxpress-api-checkbox" <?php checked('true', strtolower($showMapInResultsPage)); ?> <?php echo @ empty($ResultsDefaultStateModernView) ? "disabled=\"disabled\"" : "" ?> /> <label for="dsidxpress-ShowMapInResultsPage-check">Show Map by default</label>
							<input type="hidden" id="dsidxpress-ShowMapInResultsPage" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[ShowMapInResultsPage]" value="<?php echo $showMapInResultsPage;?>" />
						</div>
						<input type="hidden" id="dsidxpress-IsResultsPageModernView" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[IsResultsPageModernView]" value="<?php echo $isResultsPageModernView ?>" />
					</td>
				</tr>
				<tr>
					<th>
						<label for="dsidxpress-UseAcresInsteadOfSqFt">Use Acres by default:</label>
					</th>
					<td>
						<input type="checkbox" id="dsidxpress-UseAcresInsteadOfSqFt-check" class="dsidxpress-api-checkbox" <?php checked('true', strtolower($useAcresInsteadOfSqFt)); ?> /><br />
						<input type="hidden" id="dsidxpress-UseAcresInsteadOfSqFt" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[UseAcresInsteadOfSqFt]" value="<?php echo $useAcresInsteadOfSqFt;?>" />
						<span class="description">Converts lot Sq. FT to Acres.</span>
					</td>
				</tr>
				<tr>
					<th>
						<label for="dsidxpress-ResultsMapDefaultState">Property Details Image Display:</label>
					</th>
					<td>
						<input type="radio" id="dsidxpress-ImageDisplay-Slideshow" name="<?php echo esc_attr(DSIDXPRESS_OPTION_NAME); ?>[ImageDisplay]" value="slideshow" <?php echo !isset($options["ImageDisplay"]) || esc_html(@$options["ImageDisplay"]) == "slideshow" ? "checked=\"checked\"" : "" ?>/> <label for="dsidxpress-ImageDisplay-Slideshow">Rotating Slideshow</label><br />
						<input type="radio" id="dsidxpress-ImageDisplay-Thumbnail" name="<?php echo esc_attr(DSIDXPRESS_OPTION_NAME); ?>[ImageDisplay]" value="thumbnail" <?php echo isset($options["ImageDisplay"]) && esc_html(@$options["ImageDisplay"]) == "thumbnail" ? "checked=\"checked\"" : "" ?> /> <label for="dsidxpress-ImageDisplay-Thumbnail">Thumbnail Display</label>
					</td>
				</tr>
			</table>
			<script>
				function ResultsPageViewChanged(ctrl) {
					if(ctrl.value == 'true') {
						// Modern View

						jQuery("input[type='radio'][name='<?php echo esc_attr(DSIDXPRESS_OPTION_NAME); ?>[ResultsDefaultStateModernView]']").attr('disabled', false);
						jQuery("input[type='radio'][name='<?php echo esc_attr(DSIDXPRESS_OPTION_NAME); ?>[ResultsDefaultStateModernView]']")[0].checked = true;

						jQuery("input[type='radio'][name='<?php echo esc_attr(DSIDXPRESS_OPTION_NAME); ?>[ResultsDefaultState]']").attr('disabled', true);
						jQuery("input[type='radio'][name='<?php echo esc_attr(DSIDXPRESS_OPTION_NAME); ?>[ResultsDefaultState]']").attr('checked', false);

						jQuery("#dsidxpress-ShowMapInResultsPage-check").attr('disabled', false);

						jQuery("input[type='radio'][name='<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[MapOrientationInResultsPage]']").attr('disabled', false);
						jQuery("input[type='radio'][name='<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[MapOrientationInResultsPage]']")[0].checked = true;
					}
					else {
						// Classic View

						jQuery("input[type='radio'][name='<?php echo esc_attr(DSIDXPRESS_OPTION_NAME); ?>[ResultsDefaultState]']").attr('disabled', false);
						jQuery("input[type='radio'][name='<?php echo esc_attr(DSIDXPRESS_OPTION_NAME); ?>[ResultsDefaultState]']")[0].checked = true;

						jQuery("input[type='radio'][name='<?php echo esc_attr(DSIDXPRESS_OPTION_NAME); ?>[ResultsDefaultStateModernView]']").attr('disabled', true);
						jQuery("input[type='radio'][name='<?php echo esc_attr(DSIDXPRESS_OPTION_NAME); ?>[ResultsDefaultStateModernView]']").attr('checked', false);

						jQuery("#dsidxpress-ShowMapInResultsPage-check").attr('disabled', true);
						jQuery("#dsidxpress-ShowMapInResultsPage-check").attr('checked', false);
						jQuery("#dsidxpress-ShowMapInResultsPage").val(false);

						jQuery("input[type='radio'][name='<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[MapOrientationInResultsPage]']").attr('disabled', true);
						jQuery("input[type='radio'][name='<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[MapOrientationInResultsPage]']").attr('checked', false);
					}
				}
			</script>
			<h2>Registration Options</h2>
			<table class="form-table">
				<tr>
					<th>
						<label for="dsidxpress-RegistrationShowConsent-check">Data Privacy Policy</label>
					</th>
					<td>
						<input type="hidden" id="dsidxpress-RegistrationShowConsent" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[RegistrationShowConsent]" value="<?php echo $registrationShowConsent; ?>" />
						<input type="checkbox" class="dsidxpress-api-checkbox" id="dsidxpress-RegistrationShowConsent-check" <?php checked('true', strtolower($registrationShowConsent)); ?> />
						<span class="description" >Requirement is by State Law. Current State(s) requiring this option include California. Check with your local board/MLS for the most up to date requirements if in another state. This option adds a checkbox to the registration form</span>
						<input type="hidden" id="dsidxpress-RegistrationShowConsent-Original" value="<?php echo $registrationShowConsent; ?>" />
						<input type="hidden" id="dsidxpress-RegistrationConsentLastUpdatedDate" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[RegistrationConsentLastUpdatedDate]" value="<?php echo $registrationConsentLastUpdatedDate; ?>" />
					</td>
				</tr>
			</table>
						<?php if (defined('ZPRESS_API') || isset($options["dsIDXPressPackage"]) && $options["dsIDXPressPackage"] == "pro"): ?>
			<table class="form-table">
				<tr>
					<th>
						<label for="dsidxpress-RequiredPhone-check">Require phone numbers for visitor registration and contact forms</label>
					</th>
					<td>
						<input type="hidden" id="dsidxpress-RequiredPhone" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[RequiredPhone]" value="<?php echo $requiredPhone; ?>" />
						<input type="checkbox" class="dsidxpress-api-checkbox" id="dsidxpress-RequiredPhone-check" <?php checked('true', strtolower($requiredPhone)); ?> />
					</td>
				</tr>
			</table>
			<h2>Forced Registration Settings</h2>
			<table class="form-table">
				<tr>
					<th>
						<label for="dsidxpress-NumofDetailsViews">Number of detail views before required registration</label>
					</th>
					<td>
						<input type="text" id="dsidxpress-NumOfDetailsViews" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[AllowedDetailViewsBeforeRegistration]" value="<?php echo $allowedDetailViewsBeforeRegistration; ?>" />
					</td>
				</tr>
				<tr>
					<th>
						<label for="dsidxpress-NumofResultsViews">Number of result views before required registration</label>
					</th>
					<td>
						<input type="text" id="dsidxpress-NumOfResultViews" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME);?>[AllowedSearchesBeforeRegistration]" value="<?php echo $allowedSearchesBeforeRegistration; ?>" />
					</td>
				</tr>
				<tr>
					<th>
						<label for="dsidxpress-RequireAuth-Details-Description-check">Require login to view description</label>
					</th>
					<td>
						<input type="hidden" id="dsidxpress-RequireAuth-Details-Description" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[RequireAuth-Details-Description]" value="<?php echo $requireAuth_Details_Description; ?>" />
						<input type="checkbox" class="dsidxpress-api-checkbox" id="dsidxpress-RequireAuth-Details-Description-check" <?php checked('true', strtolower($requireAuth_Details_Description)); ?> />
					</td>
				</tr>
				<tr>
					<th>
						<label for="dsidxpress-RequireAuth-Property-Community">Require login to view the community</label>
					</th>
					<td>
						<input type="hidden" id="dsidxpress-RequireAuth-Property-Community" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[RequireAuth-Property-Community]" value="<?php echo $requireAuth_Property_Community; ?>" />
						<input type="checkbox" class="dsidxpress-api-checkbox" id="dsidxpress-RequireAuth-Property-Community-check" <?php checked('true', strtolower($requireAuth_Property_Community)); ?> />
					</td>
				</tr>
				<tr>
					<th>
						<label for="dsidxpress-RequireAuth-Property-Tract-check">Require login to view the tract</label>
					</th>
					<td>
						<input type="hidden" id="dsidxpress-RequireAuth-Property-Tract" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[RequireAuth-Property-Tract]" value="<?php echo $requireAuth_Property_Tract; ?>" />
						<input type="checkbox" class="dsidxpress-api-checkbox" id="dsidxpress-RequireAuth-Property-Tract-check" <?php checked('true', strtolower($requireAuth_Property_Tract)); ?> />
					</td>
				</tr>
				<tr>
					<th>
						<label for="dsidxpress-RequireAuth-Details-Schools-check">Require login to view schools</label>
					</th>
					<td>
						<input type="hidden" id="dsidxpress-RequireAuth-Details-Schools" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[RequireAuth-Details-Schools]" value="<?php echo $requireAuth_Details_Schools; ?>" />
						<input type="checkbox" class="dsidxpress-api-checkbox" id="dsidxpress-RequireAuth-Details-Schools-check" <?php checked('true', strtolower($requireAuth_Details_Schools)); ?> />
					</td>
				</tr>
				<tr>
					<th>
						<label for="dsidxpress-RequireAuth-Details-AdditionalInfo-check">Require login to view additional info</label>
					</th>
					<td>
						<input type="hidden" id="dsidxpress-RequireAuth-Details-AdditionalInfo" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[RequireAuth-Details-AdditionalInfo]" value="<?php echo $requireAuth_Details_AdditionalInfo; ?>" />
						<input type="checkbox" class="dsidxpress-api-checkbox" id="dsidxpress-RequireAuth-Details-AdditionalInfo-check" <?php checked('true', strtolower($requireAuth_Details_AdditionalInfo)); ?> />
					</td>
				</tr>
				<tr>
					<th>
						<label for="dsidxpress-RequireAuth-Details-PriceChanges-check">Require login to view price changes</label>
					</th>
					<td>
						<input type="hidden" id="dsidxpress-RequireAuth-Details-PriceChanges" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[RequireAuth-Details-PriceChanges]" value="<?php echo $requireAuth_Details_PriceChanges; ?>" />
						<input type="checkbox" class="dsidxpress-api-checkbox" id="dsidxpress-RequireAuth-Details-PriceChanges-check" <?php checked('true', strtolower($requireAuth_Details_PriceChanges)); ?> />
					</td>
				</tr>
				<tr>
					<th>
						<label for="dsidxpress-RequireAuth-Details-Features-check">Require login to view features</label>
					</th>
					<td>
						<input type="hidden" id="dsidxpress-RequireAuth-Details-Features" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[RequireAuth-Details-Features]" value="<?php echo $requireAuth_Details_Features; ?>" />
						<input type="checkbox" class="dsidxpress-api-checkbox" id="dsidxpress-RequireAuth-Details-Features-check" <?php checked('true', strtolower($requireAuth_Details_Features)); ?> />
					</td>
				</tr>
				<tr>
					<th>
						<label for="dsidxpress-RequireAuth-Property-DaysOnMarket-check">Require login to view days on market</label>
					</th>
					<td>
						<input type="hidden" id="dsidxpress-RequireAuth-Property-DaysOnMarket" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[RequireAuth-Property-DaysOnMarket]" value="<?php echo $requireAuth_Property_DaysOnMarket; ?>" />
						<input type="checkbox" class="dsidxpress-api-checkbox" id="dsidxpress-RequireAuth-Property-DaysOnMarket-check" <?php checked('true', strtolower($requireAuth_Property_DaysOnMarket)); ?> />
					</td>
				</tr>
				<tr>
					<th>
						<label for="dsidxpress-RequireAuth-Property-LastUpdated-check">Require login to view last update date</label>
					</th>
					<td>
						<input type="hidden" id="dsidxpress-RequireAuth-Property-LastUpdated" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[RequireAuth-Property-LastUpdated]" value="<?php echo $requireAuth_Property_LastUpdated; ?>" />
						<input type="checkbox" class="dsidxpress-api-checkbox" id="dsidxpress-RequireAuth-Property-LastUpdated-check" <?php checked('true', strtolower($requireAuth_Property_LastUpdated)); ?> />
					</td>
				</tr>
				<tr>
					<th>
						<label for="dsidxpress-RequireAuth-Property-YearBuilt-check">Require login to view year built</label>
					</th>
					<td>
						<input type="hidden" id="dsidxpress-RequireAuth-Property-YearBuilt" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[RequireAuth-Property-YearBuilt]" value="<?php echo $requireAuth_Property_YearBuilt; ?>" />
						<input type="checkbox" class="dsidxpress-api-checkbox" id="dsidxpress-RequireAuth-Property-YearBuilt-check" <?php checked('true', strtolower($requireAuth_Property_YearBuilt)); ?> />
					</td>
				</tr>
			</table>
			<?php endif ?>
			<?php if(!defined('ZPRESS_API') || ZPRESS_API == '') : ?>
			<h2>Contact Information</h2>
			<span class="description">This information is used in identifying you to the website visitor. For example: Listing PDF Printouts, Contact Forms, and Dwellicious</span>
			<table class="form-table">
				<tr>
					<th>
						<label for="dsidxpress-FirstName">First Name:</label>
					</th>
					<td>
						<input type="text" id="dsidxpress-FirstName" maxlength="49" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[FirstName]" value="<?php echo $firstName; ?>" /><br />
						<span class="description"></span>
					</td>
				</tr>
				<tr>
					<th>
						<label for="dsidxpress-LastName">Last Name:</label>
					</th>
					<td>
						<input type="text" id="dsidxpress-LastName" maxlength="49" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[LastName]" value="<?php echo $lastName; ?>" /><br />
						<span class="description"></span>
					</td>
				</tr>
				<tr>
					<th>
						<label for="dsidxpress-Email">Email:</label>
					</th>
					<td>
						<input type="text" id="dsidxpress-Email" maxlength="49" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[Email]" value="<?php echo $email; ?>" /><br />
						<span class="description"></span>
					</td>
				</tr>
			</table>

			<h2>Copyright Settings</h2>
			<span class="description">This setting allows you to remove links to <a href="http://www.diversesolutions.com">Diverse Solutions</a> that are included in the IDX disclaimer.</span>
			<table class="form-table">
				<tr>
					<th>
						<label for="dsidxpress-RemoveDsDisclaimerLinks">Remove Diverse Solutions links</label>
					</th>
					<td>
						<input type="checkbox" id="dsidxpress-RemoveDsDisclaimerLinks" name="<?php echo esc_attr(DSIDXPRESS_OPTION_NAME); ?>[RemoveDsDisclaimerLinks]" value="Y"<?php if (isset($options['RemoveDsDisclaimerLinks']) && $options['RemoveDsDisclaimerLinks'] == 'Y'): ?> checked="checked"<?php endif ?> />
					</td>
				</tr>
			</table>
			<?php endif; ?>
			<h2>Mobile Settings</h2>
			<span class="description">To set up a custom mobile domain you must configure your DNS to point a domain, or subdomain, at app.dsmobileidx.com. Then enter the custom domain's full url here. Example: http://mobile.myrealestatesite.com</span>
			<table class="form-table">
				<tr>
					<th>
						<label for="dsidxpress-MobileSiteUrl">Custom Mobile Domain:</label>
					</th>
					<td>
						<input type="text" id="dsidxpress-MobileSiteUrl" maxlength="100" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[MobileSiteUrl]" value="<?php echo $mobileSiteUrl; ?>" />
					</td>
				</tr>
			</table>
			<h2>My Listings</h2>
			<span class="description">When filled in, these settings will make pages for "My Listings" and "My Office Listings" available in your navigation menus page list.</span>
			<table class="form-table">
				<tr>
					<th>
						<label for="dsidxpress-AgentID">Agent ID:</label>
					</th>
					<td>
						<input type="text" id="dsidxpress-AgentID" maxlength="35" name="<?php echo esc_attr(DSIDXPRESS_OPTION_NAME); ?>[AgentID]" value="<?php echo (!empty($options['AgentID']) ? esc_html($options['AgentID']) : $agentID); ?>" /><br />
						<span class="description">This is the Agent ID as assigned to you by the MLS you are using to provide data to this site.</span>
						<input type="hidden" id="dsidxpress-API-AgentID" maxlength="35" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[AgentID]" value="<?php echo (!empty($options['AgentID']) ? esc_html($options['AgentID']) : $agentID); ?>" /><br />
					</td>
				</tr>
				<tr>
					<th>
						<label for="dsidxpress-OfficeID">Office ID:</label>
					</th>
					<td>
						<input type="text" id="dsidxpress-OfficeID" maxlength="35" name="<?php echo esc_attr(DSIDXPRESS_OPTION_NAME); ?>[OfficeID]" value="<?php echo (!empty($options['OfficeID']) ? esc_html($options['OfficeID']) : $officeID); ?>" /><br />
						<span class="description">This is the Office ID as assigned to your office by the MLS you are using to provide data to this site.</span>
						<input type="hidden" id="dsidxpress-API-OfficeID" maxlength="35" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[OfficeID]" value="<?php echo (!empty($options['OfficeID']) ? esc_html($options['OfficeID']) : $officeID); ?>" /><br />
					</td>
				</tr>
			</table>
			<?php if((!defined('ZPRESS_API') || ZPRESS_API == '') && isset($enableMemcacheInDsIdxPress) && strtolower($enableMemcacheInDsIdxPress) == "true") {?>
			<h2>Memcache Options</h2>
			<?php if(!class_exists('Memcache') && !class_exists('Memcached')) {?>
			<span class="description">Warning PHP is not configured with a Memcache module. See <a href="http://www.php.net/manual/en/book.memcache.php" target="_blank">here</a> or <a href="http://www.php.net/manual/en/book.memcached.php" target="_blank">here</a> to implement one.</span>
			<?php }?>
			<table class="form-table">
				<tr>
					<th>
						<label for="dsidxpress-MemcacheHost">Host:</label>
					</th>
					<td>
						<input type="text" id="dsidxpress-MemcacheHost" maxlength="49" name="<?php echo esc_attr(DSIDXPRESS_OPTION_NAME); ?>[MemcacheHost]" value="<?php echo esc_html($options["MemcacheHost"]); ?>" /><br />
						<span class="description"></span>
					</td>
				</tr>
				<tr>
					<th>
						<label for="dsidxpress-MemcachePort">Port:</label>
					</th>
					<td>
						<input type="text" id="dsidxpress-MemcachePort" maxlength="49" name="<?php echo esc_attr(DSIDXPRESS_OPTION_NAME); ?>[MemcachePort]" value="<?php echo esc_html($options["MemcachePort"]); ?>" /><br />
						<span class="description"></span>
					</td>
				</tr>
			</table>
			<?php }?>
			<p class="submit">
				<input type="submit" class="button-primary" name="Submit" value="Save Options" />
			</p>
		</form>
	</div><?php
	}

	static function Activation() {
		$options = get_option(DSIDXPRESS_OPTION_NAME);

		if (isset($options["PrivateApiKey"]) && $options["PrivateApiKey"]) {
			$diagnostics = self::RunDiagnostics($options);

			$previous_options  = (isset($options["Activated"])) ? $options["Activated"] : '';
			$previous_options .= (isset($options["HasSearchAgentPro"])) ? '|'.$options["HasSearchAgentPro"] : '';
			$previous_options .= (isset($options["DetailsRequiresRegistration"])) ? '|'.$options["DetailsRequiresRegistration"] : '';
			$new_options = $diagnostics["DiagnosticsSuccessful"].'|'.$diagnostics["HasSearchAgentPro"].'|'.$diagnostics["DetailsRequiresRegistration"];

			$options["Activated"] = $diagnostics["DiagnosticsSuccessful"];
			$options["HasSearchAgentPro"] = $diagnostics["HasSearchAgentPro"];
			$options["DetailsRequiresRegistration"] = $diagnostics["DetailsRequiresRegistration"];

			if ($previous_options != $new_options)
				update_option(DSIDXPRESS_OPTION_NAME, $options);

			$formattedApiKey = $options["AccountID"] . "/" . $options["SearchSetupID"] . "/" . $options["PrivateApiKey"];
		}
?>

	<div class="wrap metabox-holder">
		<h1>IDX Activation</h1>
		<form method="post" action="options.php">
			<?php settings_fields("dsidxpress_activation"); ?>
			<h2>Activate dsIDXpress on Your Website </h2>
			<p>
				Add MLS listings and IDX search to your WordPress website with the dsIDXpress plugin. 
				Simply enter the activation key for your free trial or paid subscription below to get started.
			</p>
			<table class="form-table">
				<tr>
					<th style="width: 190px;">
						<label for="option-FullApiKey">Enter your activation key:</label>
					</th>
					<td>
						<input type="text" id="option-FullApiKey" maxlength="49" name="<?php echo esc_attr(DSIDXPRESS_OPTION_NAME); ?>[FullApiKey]" value="<?php echo isset($formattedApiKey) ? $formattedApiKey : '' ?>" />
						</td>
						</tr>
						</table>
						<p class="submit">
						<input type="submit" class="button-primary" name="Submit" value="Activate dsIDXpress" />
						</p>
					
					<p>
						<b>Please note:</b> An individual activation key will only work on one WordPress website at a time. 
						<a href="http://www.diversesolutions.com/contact/">Contact us</a> if you need your activation key to work on more than one website.
					</p>
						<div id="dsidx-activation-notice">
				<p>
					<strong>Don’t have an activation key?</strong> <br>
					Contact the Diverse Solutions team to get a dsIDXpress activation key for your free trial or paid subscription. 
					<br>
					<a href="http://www.diversesolutions.com/contact/" class="button-primary">Contact Us </a> <br>
					<a href="mailto:sales@diversesolutions.com">sales@diversesolutions.com</a> <br>
					<a href="tel:1-800-978-5177">(800) 978-5177</a>
				</p>
			</div>
						<?php
						if (isset($diagnostics)) {
						?>
			<h2>Diagnostics</h2>
<?php
if (isset($diagnostics["error"])) {
?>
			<p class="error">
				It seems that there was an issue while trying to load the diagnostics from Diverse Solutions' servers. It's possible that our servers
				are temporarily down, so please check back in just a minute. If this problem persists, please
				<a href="http://www.diversesolutions.com/support.htm" target="_blank">contact us</a>.
			</p>
<?php
} else {
?>
			<table class="form-table" style="margin-bottom: 15px;">
				<tr>
					<th style="width: 230px;">
						<a href="https://helpdesk.diversesolutions.com/ds-idxpress/installing-activating-dsidxpress-text?s[]=Account%20active#diagnostics" target="_blank">Account active?</a>
					</th>
					<td class="dsidx-status dsidx-<?php echo $diagnostics["IsAccountValid"] ? "success" : "failure" ?>">
					<?php echo $diagnostics["IsAccountValid"] ? "Yes" : "No" ?>
					</td>

					<th style="width: 290px;">
					<a href="https://helpdesk.diversesolutions.com/ds-idxpress/installing-activating-dsidxpress-text?s[]=Activation%20key%20active#diagnostics" target="_blank">Activation key active?</a>
					</th>
					<td class="dsidx-status dsidx-<?php echo $diagnostics["IsApiKeyValid"] ? "success" : "failure" ?>">
					<?php echo $diagnostics["IsApiKeyValid"] ? "Yes" : "No" ?>
					</td>
					</tr>
					<tr>
					<th>
					<a href="https://helpdesk.diversesolutions.com/ds-idxpress/installing-activating-dsidxpress-text?s[]=Account%20authorized%20for%20this%20MLS#diagnostics" target="_blank">Account authorized for this MLS?</a>
					</th>
					<td class="dsidx-status dsidx-<?php echo $diagnostics["IsAccountAuthorizedToMLS"] ? "success" : "failure" ?>">
					<?php echo $diagnostics["IsAccountAuthorizedToMLS"] ? "Yes" : "No" ?>
					</td>

					<th>
					<a href="https://helpdesk.diversesolutions.com/ds-idxpress/installing-activating-dsidxpress-text?s[]=Activation%20key%20authorized%20for%20this%20blog#diagnostics" target="_blank">Activation key authorized for this blog?</a>
					</th>
					<td class="dsidx-status dsidx-<?php echo $diagnostics["IsApiKeyAuthorizedToUri"] ? "success" : "failure" ?>">
					<?php echo $diagnostics["IsApiKeyAuthorizedToUri"] ? "Yes" : "No" ?>
					</td>
					</tr>
					<tr>
					<th>
					<a href="https://helpdesk.diversesolutions.com/ds-idxpress/installing-activating-dsidxpress-text?s[]=Clock%20accurate%20on%20this%20server#diagnostics" target="_blank">Clock accurate on this server?</a>
					</th>
					<td class="dsidx-status dsidx-<?php echo $diagnostics["ClockIsAccurate"] ? "success" : "failure" ?>">
					<?php echo $diagnostics["ClockIsAccurate"] ? "Yes" : "No" ?>
					</td>

					<th>
					<a href="https://helpdesk.diversesolutions.com/ds-idxpress/installing-activating-dsidxpress-text?s[]=Activation%20key%20authorized%20for%20this%20server#diagnostics" target="_blank">Activation key authorized for this server?</a>
					</th>
					<td class="dsidx-status dsidx-<?php echo $diagnostics["IsApiKeyAuthorizedToIP"] ? "success" : "failure" ?>">
					<?php echo $diagnostics["IsApiKeyAuthorizedToIP"] ? "Yes" : "No" ?>
					</td>
					</tr>
					<tr>
					<th>
					<a href="https://helpdesk.diversesolutions.com/ds-idxpress/installing-activating-dsidxpress-text?s[]=WordPress%20link%20structure%20ok#diagnostics" target="_blank">WordPress link structure ok?</a>
					</th>
					<td class="dsidx-status dsidx-<?php echo $diagnostics["UrlInterceptSet"] ? "success" : "failure" ?>">
					<?php echo $diagnostics["UrlInterceptSet"] ? "Yes" : "No" ?>
					</td>

					<th>
					<a href="https://helpdesk.diversesolutions.com/ds-idxpress/installing-activating-dsidxpress-text?s[]=Under%20monthly%20API%20call%20limit#diagnostics" target="_blank">Under monthly API call limit?</a>
					</th>
					<td class="dsidx-status dsidx-<?php echo $diagnostics["UnderMonthlyCallLimit"] ? "success" : "failure" ?>">
					<?php echo $diagnostics["UnderMonthlyCallLimit"] ? "Yes" : "No" ?>
					</td>
					</tr>
					<tr>
					<th>
					<a href="https://helpdesk.diversesolutions.com/ds-idxpress/installing-activating-dsidxpress-text?s[]=Server%20PHP%20version%20at%20least%205.2#diagnostics" target="_blank">Server PHP version at least 5.2?</a>
					</th>
					<td class="dsidx-status dsidx-<?php echo $diagnostics["PhpVersionAcceptable"] ? "success" : "failure" ?>">
					<?php echo $diagnostics["PhpVersionAcceptable"] ? "Yes" : "No" ?>
					</td>

					<th>
					<a href="https://helpdesk.diversesolutions.com/ds-idxpress/installing-activating-dsidxpress-text?s[]=Would%20you%20like%20fries%20with%20that#diagnostics" target="_blank">Would you like fries with that?</a>
					</th>
					<td class="dsidx-status dsidx-success">
					Yes <!-- you kidding? we ALWAYS want fries. mmmm, friessssss -->
					</td>
					</tr>
					</table>
					<?php
				}
			}
			?>


		</form>
	</div>




<?php
	}

	static function FilterOptions() {
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("AccountOptions", array(), false, 0);
		if (!empty($apiHttpResponse["errors"]) || $apiHttpResponse["response"]["code"] != "200")
			wp_die("We're sorry, but we ran into a temporary problem while trying to load the account data. Please check back soon.", "Account data load error");
		else
			$account_options = json_decode($apiHttpResponse["body"]);
		$urlBase = get_home_url();

		$restrictResultsToZipcode = esc_html($account_options->RestrictResultsToZipcode);
		$restrictResultsToCity = esc_html($account_options->RestrictResultsToCity);
		$restrictResultsToCounty = esc_html($account_options->RestrictResultsToCounty);
		$restrictResultsToState = esc_html($account_options->RestrictResultsToState);
		$restrictResultsToState = esc_html($account_options->RestrictResultsToState);

		$restrictResultsToPropertyType = esc_html($account_options->RestrictResultsToPropertyType);
		$dsIDXPress_Package = esc_html($account_options->{'dsIDXPress-Package'});
		$defaultListingStatusTypeIDs = esc_html($account_options->DefaultListingStatusTypeIDs);

		$wp_options = get_option(DSIDXPRESS_OPTION_NAME);

		$property_types = dsSearchAgent_ApiRequest::FetchData('AccountSearchSetupPropertyTypes', array(), false, 0);
		$default_types = dsSearchAgent_ApiRequest::FetchData('DefaultPropertyTypesNoCache', array(), false, 0);

		$property_types = json_decode($property_types["body"]);
		$default_types = json_decode($default_types["body"]);

		if (isset($_REQUEST['settings-updated'])) {
			$settings_updated = sanitize_text_field($_REQUEST['settings-updated']);
		}

		if (substr($urlBase, strlen($urlBase), 1) != "/") $urlBase .= "/";
			$urlBase .= dsSearchAgent_Rewrite::GetUrlSlug(); ?>
		<div class="wrap metabox-holder">
			<h1>Filters</h1>
			<?php if (isset($settings_updated) && $settings_updated == 'true') : ?>
			<div class="updated"><p><strong><?php _e( 'Options saved' ); ?></strong></p></div>
			<?php endif; ?>
			<form method="post" action="options.php">
				<?php settings_fields("dsidxpress_api_options"); ?>
				<span class="description">These settings will filter results.</span>
				<table class="form-table">
					<tr>
						<th>
							<label for="dsidxpress-FirstName">Restrict Results to a Zipcode:</label>
						</th>
						<td>
							<textarea class="linkInputTextArea" id="dsidxpress-RestrictResultsToZipcode" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[RestrictResultsToZipcode]"><?php echo preg_replace("/,/", "\n", $restrictResultsToZipcode); ?></textarea><br />
							<span class="description">If you need/want to restrict dsIDXpress to a specific zipcode, put the zipcode in this field. Separate a list of values by hitting the 'Enter' key after each entry.</span>
						</td>
					</tr>
					<tr>
						<th>
							<label for="dsidxpress-FirstName">Restrict Results to a City:</label>
						</th>
						<td>
							<textarea class="linkInputTextArea" id="dsidxpress-RestrictResultsToCity" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[RestrictResultsToCity]"><?php echo preg_replace('/,/', "\n", $restrictResultsToCity); ?></textarea><br />
							<span class="description">If you need/want to restrict dsIDXpress to a specific city, put the name in this field. Separate a list of values by hitting the 'Enter' key after each entry. </span>
						</td>
					</tr>
					<tr>
						<th>
							<label for="dsidxpress-FirstName">Restrict Results to a County:</label>
						</th>
						<td>
							<textarea class="linkInputTextArea" id="dsidxpress-RestrictResultsToCounty" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[RestrictResultsToCounty]"><?php echo preg_replace("/,/", "\n", $restrictResultsToCounty); ?></textarea><br />
							<span class="description">If you need/want to restrict dsIDXpress to a specific county, put the name in this field. Separate a list of values by hitting the 'Enter' key after each entry. </span>
						</td>
					</tr>
					<tr>
						<th>
							<label for="dsidxpress-FirstName">Restrict Results to a State:</label>
						</th>
						<td>
							<input type="hidden" class="linkInputTextArea" id="dsidxpress-RestrictResultsToState" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[RestrictResultsToState]" value="<?php echo $restrictResultsToState; ?>"></input>
							<select size="4" style="width:140px;" multiple="yes" class="linkInputTextArea"  id="dsidxpress-states" name="dsidxpress-states">
							<?php

							$states = array(
								"None"=>' ',
								"Alabama"=>'AL',
								"Alaska"=>'AK',
								"Arizona"=>'AZ',
								"Arkansas"=>'AR',
								"California"=>'CA',
								"Colorado"=>'CO',
								"Connecticut"=>'CT',
								"Delaware"=>'DE',
								"District of Columbia"=>'DC',
								"Florida"=>'FL',
								"Georgia"=>'GA',
								"Hawaii"=>'HI',
								"Idaho"=>'ID',
								"Illinois"=>'IL',
								"Indiana"=>'IN',
								"Iowa"=>'IA',
								"Kansas"=>'KS',
								"Kentucky"=>'KY',
								"Louisiana"=>'LA',
								"Maine"=>'ME',
								"Maryland"=>'MD',
								"Massachusetts"=>'MA',
								"Michigan"=>'MI',
								"Minnesota"=>'MN',
								"Mississippi"=>'MS',
								"Missouri"=>'MO',
								"Montana"=>'MT',
								"Nebraska"=>'NE',
								"Nevada"=>'NV',
								"New Hampshire"=>'NH',
								"New Jersey"=>'NJ',
								"New Mexico"=>'NM',
								"New York"=>'NY',
								"North Carolina"=>'NC',
								"North Dakota"=>'ND',
								"Ohio"=>'OH',
								"Oklahoma"=>'OK',
								"Oregon"=>'OR',
								"Pennsylvania"=>'PA',
								"Rhode Island"=>'RI',
								"South Carolina"=>'SC',
								"South Dakota"=>'SD',
								"Tennessee"=>'TN',
								"Texas"=>'TX',
								"Utah"=>'UT',
								"Vermont"=>'VT',
								"Virginia"=>'VA',
								"Washington"=>'WA',
								"West Virginia"=>'WV',
								"Wisconsin"=>'WI',
								"Wyoming"=>'WY');

							if(isset($restrictResultsToState)) $selected_states = explode(',', $restrictResultsToState);
							foreach ($states as $key => $value) {
								$opt_checked = "";
								$pKey = esc_html($key);
								if(isset($pKey) && !empty($pKey)) {
									$escapedValue = esc_attr($value);
									if (isset($selected_states)) {
										foreach ($selected_states as $selected_state) {
											if (!empty($escapedValue) && $selected_state == $escapedValue) {
												$opt_checked = "selected='selected'";
												break;
											}
										}
									}
									echo '<option class="dsidxpress-states-filter" '.$opt_checked.' value="' . $escapedValue . '">' . $pKey . '</option>';
								}
								
							}
							?>
							</select><br/>


							<span class="description">If you need/want to restrict dsIDXpress to a specific state, put the abbreviation in this field. Separate a list of values by hitting the 'Enter' key after each entry. <a href="http://en.wikipedia.org/wiki/List_of_U.S._state_abbreviations" target="_blank">List of U.S. State Abbreviations</a></span>
						</td>
					</tr>
					<tr>
						<th>
							<label for="dsidxpress-FirstName">Restrict Results to a Property Type:</label>
						</th>
						<?php
							$default_values = array();
							foreach ($default_types as $default_type) {
								array_push($default_values, $default_type->SearchSetupPropertyTypeID);
							}
						?>
						<td>
							<input type="hidden" class="linkInputTextArea" id="dsidxpress-RestrictResultsToPropertyType" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[RestrictResultsToPropertyType]" value="<?php echo $restrictResultsToPropertyType; ?>"></input>
							<input type="hidden" class="linkInputTextArea" id="dsidxpress-DefaultPropertyType" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[DefaultPropertyType]" value="<?php echo (count($default_values) > 0) ? implode(",", $default_values) : ""; ?>" />
							<table id="dsidxpress-property-types" name="dsidxpress-property-types">
									<tr>
										<td></td>
										<td>Filter</td>
										<td>Default</td>
									</tr>
									<?php
									$filter_types = explode(',', $restrictResultsToPropertyType);
									foreach ($property_types as $property_type) {
										$name = esc_html(htmlentities($property_type->DisplayName));
										$id = esc_html($property_type->SearchSetupPropertyTypeID);
										$filter_checked = "";
										$default_checked = "";
										foreach ($filter_types as $filter_type) {
											if ($filter_type == (string)$id) {
												$filter_checked = "checked";
												break;
											}
										}
										foreach ($default_types as $default_type) {
											if(esc_html(htmlentities($default_type->SearchSetupPropertyTypeID)) == (string)$id){
												$default_checked = "checked";
												break;
											}
										}
										?>
										<tr>
											<td><?php echo $name; ?></td>
											<td><input class="dsidxpress-proptype-filter" <?php echo $filter_checked; ?> type="checkbox" value="<?php echo $id; ?>"/></td>
											<td><input class="dsidxpress-proptype-default" <?php echo $default_checked; ?> type="checkbox" value="<?php echo $id; ?>"/></td>
										</tr>
										<?php
									}
								?>
							</table>
							<span class="description">If you need/want to restrict dsIDXpress to specific property types, select the types you would like to have return results.  This setting will also restrict the property types shown in search form options.  You may also choose which types are included in the default property type selection.</span>
						</td>
					</tr>
					<?php if ($dsIDXPress_Package == 'pro') : ?>
					<tr>
						<th>
							<label>Default Results by Status:</label>
						</th>
						<td>
							<input type="hidden" id="dsidxpress-DefaultListingStatusTypeIDs" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[DefaultListingStatusTypeIDs]" value="<?php echo $defaultListingStatusTypeIDs; ?>" />
							<table class="dsidxpress-status-types">
								<?php
								$listing_status_types = array('Active' => 1, 'Conditional' => 2, 'Pending' => 4, 'Sold' => 8);
								if (empty(self::$capabilities['HasSoldData'])) {
									unset($listing_status_types['Sold']);
								}
								if (empty(self::$capabilities['HasConditionalData'])) {
									unset($listing_status_types['Conditional']);
								}
								if (empty(self::$capabilities['HasPendingData'])) {
									unset($listing_status_types['Pending']);
								}
								foreach ($listing_status_types as $label => $value) :
									$status_checked = '';
									$pLabel  = esc_html($label);
									if(isset($pLabel) && !empty($pLabel)) {
										$escapedValue  = esc_attr($value);
										if (strpos($account_options->DefaultListingStatusTypeIDs, (string)$value) !== false) {
											$status_checked = 'checked';
										}	
									}									
									?>
									<tr>
										<td><?php echo $pLabel . ' '; ?></td>
										<td><input class="dsidxpress-statustype-filter" <?php echo $status_checked; ?> type="checkbox" value="<?php echo $escapedValue; ?>" /></td>
									</tr>
								<?php endforeach; ?>
							</table>
							<span class="description">If you need / want to restrict the properties shown on your website by property status, check the statuses you would like visitors to see by default in search results here</span>
						</td>
					</tr>
					<?php endif; ?>
				</table>
				<br />
				<p class="submit">
					<input type="submit" class="button-primary" name="Submit" value="Save Options" />
				</p>
			</form>
		</div><?php
	}

	static function SEOSettings() {
		$options = get_option(DSIDXPRESS_OPTION_NAME);

		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("AccountOptions", array(), false, 0);
		if (!empty($apiHttpResponse["errors"]) || $apiHttpResponse["response"]["code"] != "200")
			wp_die("We're sorry, but we ran into a temporary problem while trying to load the account data. Please check back soon.", "Account data load error");
		else
			$account_options = json_decode($apiHttpResponse["body"]);
		$urlBase = get_home_url();
		$linkTractName = '';
		$linkCommunityName ='';
		if(isset($account_options->dsIDXPressSEODetailsLinkTract)) {			
			if(esc_html($account_options->dsIDXPressSEODetailsLinkTract=='true'))
				$linkTractName = 'checked';
			else 
				$linkTractName = '';	
		}		
		if(isset($account_options->dsIDXPressSEODetailsLinkCommunity)) {
			if( esc_html($account_options->dsIDXPressSEODetailsLinkCommunity=='true'))
				$linkCommunityName ='checked';
			else 
				$linkCommunityName ='';
		}

		if (isset($_REQUEST['settings-updated'])) {
			$settings_updated = sanitize_text_field($_REQUEST['settings-updated']);
		}
		if (substr($urlBase, strlen($urlBase), 1) != "/") $urlBase .= "/";
			$urlBase .= dsSearchAgent_Rewrite::GetUrlSlug(); ?>
		<div class="wrap metabox-holder">
			<h1>SEO Settings</h1>
			<?php if (isset($settings_updated) && $settings_updated == 'true') : ?>
			<div class="updated"><p><strong><?php _e( 'Options saved' ); ?></strong></p></div>
			<?php endif; ?>
			<form method="post" action="options.php">
			<?php settings_fields("dsidxpress_api_options"); ?>
			<span class="description">These settings are used to improve the accuracy of how search engines find and list this site.<br/>When using a replacement field please include it using lowercase characters.</span>
			<div style="padding-left: 30px;">
			<h2>Details Page Settings</h2>
			<span class="description">These settings apply to any page holding details for a specific property. <br /><br />
				You may use <code>%full address%</code> (including City, State, ZIP), <code>%street address%</code>, <code>%mls number%</code>, <code>%city%</code>, <code>%state%</code>, <code>%zip%</code>, <code>%county%</code>, <code>%property type%</code>, <code>%tract%</code>, and/or <code>%community%</code> in any of the fields below and <br />
				they will display as the relevant value. For example: Homes for sale in %zip%. will appear as <code>Homes for sale in 92681</code>.
			</span>
			<br />
			<table class="form-table">
				<tr>
					<th><label for="dsidxpress-DescMetaTag">Description Meta Tag:</th>
					<td>
						<input type="text" id="dsidxpress-DescMetaTag" size="50" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[dsIDXPressSEODescription]"  value="<?php echo esc_html($account_options->dsIDXPressSEODescription); ?>" /><br />
						<span class="description">This text will be used as the summary displayed in search results.</span>
					</td>
				</tr>
				<tr>
					<th><label for="dsidxpress-KeywordMetaTag">Keyword Meta Tag:</th>
					<td>
						<input type="text" id="dsidxpress-KeywordMetaTag" size="50" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[dsIDXPressSEOKeywords]" value="<?php echo esc_html($account_options->dsIDXPressSEOKeywords); ?>" /><br />
						<span class="description">This value aids search engines in categorizing property pages.</span>
					</td>
				</tr>
				<tr>
					<th><label for="dsidxpress-DetailsTitle">Page Title:</th>
					<td>
						<input type="text" id="dsidxpress-DetailsTitle" size="50" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[dsIDXPressSEODetailsTitle]" value="<?php echo esc_html($account_options->dsIDXPressSEODetailsTitle); ?>" /><br />
						<span class="description">This option will override the default page title.</span>
					</td>
				</tr>
			</table>

			<h2>Tract & Community Links</h2>
			<span class="description">This will allow website visitors to click on the Tract/Community names on listings to view more properties from that location. <br />
			This may also help SEO, as it'll link to more pages
			</span>
			<br />
			<table class="form-table">
				<tr>
				<th><label for="dsidxpress-DetailsTitle">Tracts:</th>
					<td>
						<input type='hidden'  id="dsIDXPressSEODetailsLinkTract" value='<?php echo ($linkTractName!=''?'true':'false');?>'
						name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[dsIDXPressSEODetailsLinkTract]"/>
						<input type="checkbox"  
						 id="dsIDXPressSEODetailsLinkTractCB" class="dsidxpress-api-checkbox"  
						 onclick="dsIDXpressOptions.OptionCheckBoxClick(this);"
						<?php echo $linkTractName; ?> /> Link tract name on details pages. <br/>
					</td>
				</tr>
				<tr>
				<th><label for="dsidxpress-DetailsTitle">Communities:</th>
					<td>
					<input type='hidden'  id="dsIDXPressSEODetailsLinkCommunity" value='<?php echo ($linkCommunityName!=''?'true':'false');?>'
						name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[dsIDXPressSEODetailsLinkCommunity]"/>
					<input type="checkbox" id="dsIDXPressSEODetailsLinkCommunityCB"  class="dsidxpress-api-checkbox" onclick="dsIDXpressOptions.OptionCheckBoxClick(this);"					
					<?php echo $linkCommunityName; ?>/> Link community name on details pages.  <br/>
					</td>
				</tr>
			</table>

			<h2>Results Page Settings</h2>
			<span class="description">
				These settings apply to any page holding a list of properties queried through a url. You may use %location% in the fields and the relevant value will display.
			</span>
			<br />
			<table class="form-table">
				<tr>
					<th><label for="dsidxpress-DescMetaTag">Description Meta Tag:</th>
					<td>
						<input type="text" id="dsidxpress-DescMetaTag" size="50" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[dsIDXPressSEOResultsDescription]"  value="<?php echo esc_html($account_options->dsIDXPressSEOResultsDescription); ?>" /><br />
						<span class="description">This text will be used as the summary displayed in search results </span>
					</td>
				</tr>
				<tr>
					<th><label for="dsidxpress-KeywordMetaTag">Keyword Meta Tag:</th>
					<td>
						<input type="text" id="dsidxpress-KeywordMetaTag" size="50" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[dsIDXPressSEOResultsKeywords]" value="<?php echo esc_html($account_options->dsIDXPressSEOResultsKeywords); ?>" /><br />
						<span class="description">This value aids search engines in categorizing property result pages.</span>
					</td>
				</tr>
				<tr>
					<th><label for="dsidxpress-ResultsTitle" >Page Title:</th>
					<td>
						<input type="text" id="dsidxpress-ResultsTitle" size="50" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[dsIDXPressSEOResultsTitle]" value="<?php echo esc_html($account_options->dsIDXPressSEOResultsTitle); ?>" /><br />
						<span class="description">This option will override the default page title.</span>
					</td>
				</tr>
			</table>

			</div>
			<br />
			<p class="submit">
				<input type="submit" class="button-primary" name="Submit" value="Save Options" />
			</p>
		</form>
	</div><?php
	}

	static function XMLSitemaps() {
		$options = get_option(DSIDXPRESS_OPTION_NAME);
		$urlBase = get_home_url();
		if (substr($urlBase, strlen($urlBase), 1) != "/") $urlBase .= "/";
		$urlBase .= dsSearchAgent_Rewrite::GetUrlSlug();

		if (isset($_REQUEST['settings-updated'])) {
			$settings_updated = sanitize_text_field($_REQUEST['settings-updated']);
		}
	?>
		<div class="wrap metabox-holder">
			<h1>XML Sitemaps</h1>
			<?php if (isset($settings_updated) && $settings_updated == 'true') : ?>
			<div class="updated"><p><strong><?php _e( 'Options saved' ); ?></strong></p></div>
			<?php endif; ?>
			<span class="description">Here you can manage the MLS/IDX items you would like to feature in your XML Sitemap. XML Sitemaps help Google, and other search engines, index your site. Go <a href="http://en.wikipedia.org/wiki/Sitemaps" target="_blank">here</a> to learn more about XML Sitemaps</span>

			<form method="post" action="options.php">
			<?php settings_fields("dsidxpress_xml_sitemap"); ?>
			<h2>XML Sitemaps Locations</h2>
		<?php if ( is_plugin_active('google-sitemap-generator/sitemap.php') || is_plugin_active('bwp-google-xml-sitemaps/bwp-simple-gxs.php') || class_exists('zpress\admin\Theme')) {?>
			<span class="description">Add the Locations (City, Community, Tract, or Zip) to your XML Sitemap by adding them via the dialogs below.</span>
			<?php if (is_plugin_active('bwp-google-xml-sitemaps/bwp-simple-gxs.php')): ?>
				<p><span class="description">REQUIRED: In the BWP GXS Sitemap Generator settings page, ensure that the option to include external pages is checked</span></p>
			<?php endif; ?>
			<div class="dsidxpress-SitemapLocations stuffbox">
				<script type="text/javascript">jQuery(function() { xmlsitemap_page = true; dsIDXpressOptions.UrlBase = '<?php echo esc_url($urlBase); ?>'; dsIDXpressOptions.OptionPrefix = '<?php echo esc_attr(DSIDXPRESS_OPTION_NAME); ?>';});</script>
				<div class="inside">
					<ul id="dsidxpress-SitemapLocations">
					<?php
					if (isset($options["SitemapLocations"]) && is_array($options["SitemapLocations"])) {
						$location_index = 0;

						usort($options["SitemapLocations"], array("dsSearchAgent_Admin", "CompareListObjects"));

						foreach ($options["SitemapLocations"] as $key => $value) {
							$location_sanitized = urlencode(strtolower(str_replace(array("-", " "), array("_", "-"), $value["value"])));
					?>
								<li class="ui-state-default dsidxpress-SitemapLocation">
									<div class="action"><input type="button" value="Remove" class="button" onclick="dsIDXpressOptions.RemoveSitemapLocation(this)" /></div>
									<div class="priority">
										Priority: <select name="<?php echo esc_attr(DSIDXPRESS_OPTION_NAME) ; ?>[SitemapLocations][<?php echo $location_index; ?>][priority]">
											<option value="0.0"<?php echo ($value["priority"] == "0.0" ? ' selected="selected"' : '') ?>>0.0</option>
											<option value="0.1"<?php echo ($value["priority"] == "0.1" ? ' selected="selected"' : '') ?>>0.1</option>
											<option value="0.2"<?php echo ($value["priority"] == "0.2" ? ' selected="selected"' : '') ?>>0.2</option>
											<option value="0.3"<?php echo ($value["priority"] == "0.3" ? ' selected="selected"' : '') ?>>0.3</option>
											<option value="0.4"<?php echo ($value["priority"] == "0.4" ? ' selected="selected"' : '') ?>>0.4</option>
											<option value="0.5"<?php echo ($value["priority"] == "0.5" || !isset($value["priority"]) ? ' selected="selected"' : '') ?>>0.5</option>
											<option value="0.6"<?php echo ($value["priority"] == "0.6" ? ' selected="selected"' : '') ?>>0.6</option>
											<option value="0.7"<?php echo ($value["priority"] == "0.7" ? ' selected="selected"' : '') ?>>0.7</option>
											<option value="0.8"<?php echo ($value["priority"] == "0.8" ? ' selected="selected"' : '') ?>>0.8</option>
											<option value="0.9"<?php echo ($value["priority"] == "0.9" ? ' selected="selected"' : '') ?>>0.9</option>
											<option value="1.0"<?php echo ($value["priority"] == "1.0" ? ' selected="selected"' : '') ?>>1.0</option>
											</select>
									</div>
									<div class="type">
										<select name="<?php echo esc_attr(DSIDXPRESS_OPTION_NAME) ; ?>[SitemapLocations][<?php echo $location_index; ?>][type]">
											<option value="city"<?php echo ($value["type"] == "city" ? ' selected="selected"' : ''); ?>>City</option>
											<option value="community"<?php echo ($value["type"] == "community" ? ' selected="selected"' : ''); ?>>Community</option>
											<option value="tract"<?php echo ($value["type"] == "tract" ? ' selected="selected"' : ''); ?>>Tract</option>
											<option value="zip"<?php echo ($value["type"] == "zip" ? ' selected="selected"' : ''); ?>>Zip Code</option>
										</select>
									</div>
									<div class="value">
										<a href="<?php echo esc_url($urlBase . $value["type"] .'/'. $location_sanitized);?>" target="_blank"><?php echo esc_html($value["value"]); ?></a>
										<input type="hidden" name="<?php echo esc_attr(DSIDXPRESS_OPTION_NAME) ; ?>[SitemapLocations][<?php echo esc_html($location_index); ?>][value]" value="<?php echo esc_html($value["value"]); ?>" />
									</div>
									<div style="clear:both"></div>
								</li>
								<?php
								$location_index++;
							}
						}
						?>
					</ul>
				</div>
			</div>

			<div class="dsidxpress-SitemapLocations dsidxpress-SitemapLocationsNew stuffbox">
				<div class="inside">
					<h4>New:</h4>
					<div class="type">
						<select class="widefat ignore-changes" id="dsidxpress-NewSitemapLocationType">
							<option value="city">City</option>
							<option value="community">Community</option>
							<option value="tract">Tract</option>
							<option value="zip">Zip Code</option>
						</select>
					</div>
					<div class="value"><input type="text" id="dsidxpress-NewSitemapLocation" value="" /></div>
					<div class="action">
						<input type="button" class="button" id="dsidxpress-NewSitemapLocationAdd" value="Add" onclick="dsIDXpressOptions.AddSitemapLocation()" />
					</div>
				</div>
			</div>

			<span class="description">"Priority" gives a hint to the web crawler as to what you think the importance of each page is. <code>1</code> being highest and <code>0</code> lowest.</span>

			<h2>XML Sitemaps Options</h2>
			<table class="form-table">
				<tr>
					<th>
						<label for="<?php echo esc_attr(DSIDXPRESS_OPTION_NAME) ; ?>[SitemapFrequency]">Frequency:</label>
					</th>
					<td>
						<select name="<?php echo esc_attr(DSIDXPRESS_OPTION_NAME) ; ?>[SitemapFrequency]" id="<?php echo esc_attr(DSIDXPRESS_OPTION_NAME); ?>_SitemapFrequency">
							<!--<option value="always"<?php echo (@$options["SitemapFrequency"] == "always" ? ' selected="selected"' : '') ?>>Always</option> -->
							<option value="hourly"<?php echo (@$options["SitemapFrequency"] == "hourly" ? 'selected="selected"' : '') ?>>Hourly</option>
							<option value="daily"<?php echo (@$options["SitemapFrequency"] == "daily" || !isset($options["SitemapFrequency"]) ? 'selected="selected"' : '') ?>>Daily</option>
							<!--<option value="weekly"<?php echo (@$options["SitemapFrequency"] == "weekly" ? 'selected="selected"' : '') ?>>Weekly</option>
							<option value="monthly"<?php echo (@$options["SitemapFrequency"] == "monthly" ? 'selected="selected"' : '') ?>>Monthly</option>
							<option value="yearly"<?php echo (@$options["SitemapFrequency"] == "yearly" ? 'selected="selected"' : '') ?>>Yearly</option>
							<option value="never"<?php echo (@$options["SitemapFrequency"] == "never" ? 'selected="selected"' : '') ?>>Never</option> -->
						</select>
						<span class="description">The "hint" to send to the crawler. This does not guarantee frequency, crawler will do what they want.</span>
					</td>
				</tr>
			</table>
			<br />
			<p class="submit">
							<input id="xml-options-saved" type="submit" class="button-primary" name="Submit" value="Save Options" />
			</p>
			</form>
		</div>
		<?php } else { ?>
			<span class="description">To enable this functionality, install and activate one of these plugins: <br />
				<a class="thickbox onclick" title="Google XML Sitemaps" href="<?php echo admin_url('plugin-install.php?tab=plugin-information&plugin=google-sitemap-generator&TB_iframe=true&width=640')?>" target="_blank">Google XML Sitemaps</a><br />
				<a class="thickbox onclick" title="BWP Google XML Sitemaps" href="<?php echo admin_url('plugin-install.php?tab=plugin-information&plugin=bwp-google-xml-sitemaps&TB_iframe=true&width=640')?>" target="_blank">BWP Google XML Sitemaps</a> (for Multi-Site wordpress installs)
			</span>
		<?php }
	}

	static function DetailsOptions() {
		$options = get_option(DSIDXPRESS_OPTION_NAME);
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("AccountOptions", array(), false, 0);
		if (!empty($apiHttpResponse["errors"]) || $apiHttpResponse["response"]["code"] != "200")
			wp_die("We're sorry, but we ran into a temporary problem while trying to load the account data. Please check back soon.", "Account data load error");
		else
			$account_options = json_decode($apiHttpResponse["body"]);
		$urlBase = get_home_url();

		if (isset($_REQUEST['settings-updated'])) {
			$settings_updated = sanitize_text_field($_REQUEST['settings-updated']);
		}
		$showPanel_Features = esc_html($account_options->ShowPanel_Features);
		$allowScheduleShowingFeature = esc_html($account_options->AllowScheduleShowingFeature);
		$showAskAQuestion = esc_html($account_options->ShowAskAQuestion);
		if (isset($account_options->{'dsIDXPress-Package'}))
		{
			$dsIDXPress_Package = esc_html($account_options->{'dsIDXPress-Package'});
		}
		$showPanel_Schools = esc_html($account_options->ShowPanel_Schools);
		$showPanel_Map = esc_html($account_options->ShowPanel_Map);
		$showPanel_Contact = esc_html($account_options->ShowPanel_Contact);
		$showSimilarListingsOption = esc_html($account_options->{'ShowSimilarListings'});
		$showSimilarSoldListingsOption = esc_html($account_options->{'ShowSimilarSoldListings'});
		$showMortgageCalculatorOption = esc_html($account_options->{'ShowMortgageCalculator'});
		$defaultInterestRate = esc_html($account_options->{'DefaultInterestRate'});
		$defaultMonthlyInsuranceRateOption = esc_html($account_options->{'DefaultMonthlyInsuranceRate'});
		$enableThirdPartyLogins = esc_html($account_options->EnableThirdPartyLogins);
		$facebookAppID = esc_html($account_options->{'FacebookAppID'});
		$googleMapsAPIKey = esc_html($account_options->{'GoogleMapsAPIKey'});
		$sendNewVisitorEmails = esc_html($account_options->SendNewVisitorEmails);
		$deferJavaScriptCode = esc_html($account_options->DeferJavaScriptCode);

		if (substr($urlBase, strlen($urlBase), 1) != "/") $urlBase .= "/";
			$urlBase .= dsSearchAgent_Rewrite::GetUrlSlug(); ?>
		<div class="wrap metabox-holder">
			<h1>More Options</h1>
			<?php if (isset($settings_updated) && $settings_updated == 'true') : ?>
			<div class="updated"><p><strong><?php _e( 'Options saved' ); ?></strong></p></div>
			<?php endif; ?>
			<form method="post" action="options.php">
				<?php settings_fields("dsidxpress_api_options"); ?>
				<span class="description">These settings apply to any page holding details for a specific property.</span>
				<table class="form-table">
					<tr>
						<th>
							<label for="dsidxpress-ShowPanel_FeaturesCB">Show Features:</label>
						</th>
						<td>
							<input type="checkbox" id="dsidxpress-ShowPanel_FeaturesCB" size="50" <?php checked('true', strtolower($showPanel_Features)); ?> onclick="dsIDXpressOptions.OptionCheckBoxClick(this);" /><br />
							<input type="hidden" id="dsidxpress-ShowPanel_Features" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[ShowPanel_Features]" value="<?php echo $showPanel_Features; ?>" />
							<span class="description"></span>
						</td>
					</tr>
					<tr>
						<th>
							<label for="dsidxpress-AllowScheduleShowingFeatureCB">Show Schedule a Showing button:</label>
						</th>
						<td>
							<input type="checkbox" id="dsidxpress-AllowScheduleShowingFeatureCB" size="50" <?php checked('true', strtolower($allowScheduleShowingFeature)); ?> onclick="dsIDXpressOptions.OptionCheckBoxClick(this);" /><br />
							<input type="hidden" id="dsidxpress-AllowScheduleShowingFeature" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[AllowScheduleShowingFeature]" value="<?php echo $allowScheduleShowingFeature; ?>" />
							<span class="description"></span>
						</td>
					</tr>
					<tr>
						<th>
							<label for="dsidxpress-ShowAskAQuestionCB">Show Ask a Question button:</label>
						</th>
						<td>
							<input type="checkbox" id="dsidxpress-ShowAskAQuestionCB" size="50" <?php checked('true', strtolower($showAskAQuestion)); ?> onclick="dsIDXpressOptions.OptionCheckBoxClick(this);" /><br />
							<input type="hidden" id="dsidxpress-ShowAskAQuestion" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[ShowAskAQuestion]" value="<?php echo $showAskAQuestion; ?>" />
							<span class="description"></span>
						</td>
					</tr>
					<?php if (isset($dsIDXPress_Package) && $dsIDXPress_Package === "pro"): ?>
					<tr>
						<th>
							<label for="dsidxpress-ShowPanel_SchoolsCB">Show Schools:</label>
						</th>
						<td>
							<input type="checkbox" id="dsidxpress-ShowPanel_SchoolsCB" size="50" <?php checked('true', strtolower($showPanel_Schools)); ?> onclick="dsIDXpressOptions.OptionCheckBoxClick(this);" /><br />
							<input type="hidden" id="dsidxpress-ShowPanel_Schools" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[ShowPanel_Schools]" value="<?php echo $showPanel_Schools; ?>" />
							<span class="description"></span>
						</td>
					</tr>
					<?php endif; ?>
					<tr>
						<th>
							<label for="dsidxpress-ShowPanel_MapCB">Show Map:</label>
						</th>
						<td>
							<input type="checkbox" id="dsidxpress-ShowPanel_MapCB" size="50" <?php checked('true', strtolower($showPanel_Map)); ?> onclick="dsIDXpressOptions.OptionCheckBoxClick(this);" /><br />
							<input type="hidden" id="dsidxpress-ShowPanel_Map" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[ShowPanel_Map]" value="<?php echo $showPanel_Map; ?>" />
							<span class="description"></span>
						</td>
					</tr>
					<tr>
						<th>
							<label for="dsidxpress-ShowPanel_Contact">Show Contact Form:</label>
						</th>
						<td>
							<input type="checkbox" id="dsidxpress-ShowPanel_ContactCB" size="50" <?php checked('true', strtolower($showPanel_Contact)); ?> onclick="dsIDXpressOptions.OptionCheckBoxClick(this);" /><br />
							<input type="hidden" id="dsidxpress-ShowPanel_Contact" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[ShowPanel_Contact]" value="<?php echo $showPanel_Contact;?>" />
							<span class="description"></span>
						</td>
					</tr>
					<?php 
						$showSimilarListings = "checked";
						$showSimilarSoldListings = "checked";
						$showMortgageCalculator  = "checked";
						if(isset($showSimilarListingsOption) && strtolower($showSimilarListingsOption)=="false") 
							$showSimilarListings ="";						
						if(isset($showSimilarSoldListingsOption) &&  strtolower($showSimilarSoldListingsOption)=="false")
							$showSimilarSoldListings = "";
						if(isset($showMortgageCalculatorOption) &&  strtolower($showMortgageCalculatorOption)=="false")
							$showMortgageCalculator = "";
					?>
					<tr>
						<th>
							<label for="dsidxpress-ShowPanel_ShowSimilarListings">Show Similar Listings:</label>
						</th>
						<td>							
							<input type="checkbox" id="dsidxpress-ShowPanel_ShowSimilarListingsCB" size="50" <?php echo $showSimilarListings ?> onclick="dsIDXpressOptions.OptionCheckBoxClick(this);" /><br />
							<input type="hidden" id="dsidxpress-ShowPanel_ShowSimilarListings" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[ShowSimilarListings]" value="<?php echo $showSimilarListingsOption;?>" />
							<span class="description"></span>
						</td>
					</tr>
					<tr>
						<th>
							<label for="dsidxpress-ShowPanel_ShowSimilarSoldListings">Show Similar Sold Listings:</label>
						</th>
						<td>
							<input type="checkbox" id="dsidxpress-ShowPanel_ShowSimilarSoldListingsCB" size="50" <?php echo $showSimilarSoldListings?>  onclick="dsIDXpressOptions.OptionCheckBoxClick(this);" /><br />
							<input type="hidden" id="dsidxpress-ShowPanel_ShowSimilarSoldListings" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[ShowSimilarSoldListings]" value="<?php echo $showSimilarSoldListingsOption;?>" />
							<span class="description"></span>
						</td>
					</tr>
					<tr>
						<th>
							<label for="dsidxpress-ShowPanel_Contact">Show Mortgage Calculator:</label>
						</th>
						<td>
							<input type="checkbox" id="dsidxpress-ShowPanel_ShowMortgageCalculatorCB" size="50" <?php echo $showMortgageCalculator?> onclick="dsIDXpressOptions.OptionCheckBoxClick(this);" /><br />
							<input type="hidden" id="dsidxpress-ShowPanel_ShowMortgageCalculator" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[ShowMortgageCalculator]" value="<?php echo $showMortgageCalculatorOption;?>" />
							<span class="description"></span>
						</td>
					</tr>
					<?php $defaultInterestRate = isset($defaultInterestRate)? $defaultInterestRate:''; ?>
					<tr>
						<th>
							<label for="dsidxpress-ShowPanel_interestRate">Interest Rate:</label>
						</th>
						<td>
							<input type="text" id="dsidxpress-default-interest-rate" 
							name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[DefaultInterestRate]" 
							value="<?php echo $defaultInterestRate; ?>" /><br />
						</td>
					</tr>
					<?php $defaultMonthlyInsuranceRate = isset($defaultMonthlyInsuranceRateOption)? $defaultMonthlyInsuranceRateOption:''; ?>
					<tr>
						<th>
							<label for="dsidxpress-default-insurance-rate">Monthly Insurance Rate:</label>
						</th>
						<td>
							<input type="text" id="dsidxpress-default-insurance-rate" 
							name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[DefaultMonthlyInsuranceRate]" 
							value="<?php echo $defaultMonthlyInsuranceRate; ?>" /><br />
						</td>
					</tr>
					<tr>
						<th>
							<label for="dsidxpress-EnableThirdPartyLogins">Allow visitors to log in via Facebook, Google:</label>
						</th>
						<td>
							<input type="checkbox" id="dsidxpress-EnableThirdPartyLoginsCB" size="50" <?php checked('true', strtolower($enableThirdPartyLogins)); ?> onclick="dsIDXpressOptions.OptionCheckBoxClick(this);" /><br />
							<input type="hidden" id="dsidxpress-EnableThirdPartyLogins" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[EnableThirdPartyLogins]" value="<?php echo $enableThirdPartyLogins;?>" />
							<span class="description"></span>
						</td>
					</tr>
					<tr>
						<th>
							<label for="dsidxpress-SendNewVisitorEmailsCB">Send me an email when a Visitor registers:</label>
						</th>
						<td>
							<input type="checkbox" id="dsidxpress-SendNewVisitorEmailsCB" size="50" <?php checked('true', strtolower($sendNewVisitorEmails)); ?> onclick="dsIDXpressOptions.OptionCheckBoxClick(this);" /><br />
							<input type="hidden" id="dsidxpress-SendNewVisitorEmails" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[SendNewVisitorEmails]" value="<?php echo $sendNewVisitorEmails;?>" />
							<span class="description"></span>
						</td>
					</tr>
					<tr>
						<th>
							<label for="dsidxpress-DeferJavaScriptCodeCB">Support Frontend Optimization:</label>
						</th>
						<td>
							<input type="checkbox" id="dsidxpress-DeferJavaScriptCodeCB" size="50" <?php checked('true', strtolower($deferJavaScriptCode)); ?> onclick="dsIDXpressOptions.OptionCheckBoxClick(this);" /><br />
							<input type="hidden" id="dsidxpress-DeferJavaScriptCode" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[DeferJavaScriptCode]" value="<?php echo $deferJavaScriptCode;?>" />
							<span class="description">It defers inline JavaScript code and executes it when the Combo-JS is ready</span>
						</td>
					</tr>					
				</table>
				<h1>Sharing</h1>
				<table class="form-table">
					<?php $fbAppID = isset($facebookAppID)? $facebookAppID:''; ?>
					<tr>
						<th>
							<label for="dsidxpress-ShowPanel_ZillowCB">Facebook App ID:</label>
						</th>
						<td>
							<input type="text" id="dsidxpress-FacebookAppID" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[FacebookAppID]" value="<?php echo $fbAppID; ?>" /><br />
							<span class="description">
							If set, will be used when sharing individual property pages.<br />
							<strong>Please note:</strong> this is for advanced users / developers only.<br />
							This option can only be utilized if you have created a Facebook App (not Facebook page).<br /><br />
							Visit the <a href="https://developers.facebook.com/apps" target="_blank" rel="noopener noreferrer">Facebook Developers Apps Page</a> to create / find your App ID.
							</span>
						</td>
					</tr>
				</table>
				<h1>Maps</h1>
				<table class="form-table">
					<?php $mapsKey = isset($googleMapsAPIKey)? $googleMapsAPIKey:''; ?>
					<tr>
						<th>
							<label for="dsidxpress-ShowPanel_ZillowCB">Google Maps API Key:</label>
						</th>
						<td>
							<input type="text" id="dsidxpress-GoogleMapsAPIKey" name="<?php echo esc_attr(DSIDXPRESS_API_OPTIONS_NAME); ?>[GoogleMapsAPIKey]" value="<?php echo $mapsKey; ?>" /><br />
							<span class="description">
							Required by Google in some cases. <br /><br />
							If maps are not working on your site, visit the <a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank" rel="noopener noreferrer">Google Maps APIs Documentation</a> to create / find your key.
							</span>
						</td>
					</tr>
				</table>
				<br />
				<p class="submit">
					<input type="submit" class="button-primary" name="Submit" value="Save Options" />
				</p>
			</form>
		</div><?php
	}

	static function RunDiagnostics($options) {
		// it's possible for a malicious script to trick a blog owner's browser into running the Diagnostics which passes the PrivateApiKey which
		// could allow a bug on the wire to pick up the key, but 1) we have IP and URL restrictions, and 2) there are much bigger issues than the
		// key going over the wire in the clear if the traffic is being spied on in the first place
		global $wp_rewrite;

		$diagnostics = dsSearchAgent_ApiRequest::FetchData("Diagnostics", array("apiKey" => $options["PrivateApiKey"]), false, 0, $options);

		if (empty($diagnostics["body"]) || $diagnostics["response"]["code"] != "200")
			return array("error" => true);

		$diagnostics = (array)json_decode($diagnostics["body"]);
		$setDiagnostics = array();
		$timeDiff = time() - strtotime($diagnostics["CurrentServerTimeUtc"]);
		$secondsIn2Hrs = 60 * 60 * 2;
		$permalinkStructure = get_option("permalink_structure");

		$setDiagnostics["IsApiKeyValid"] = $diagnostics["IsApiKeyValid"];
		$setDiagnostics["IsAccountAuthorizedToMLS"] = isset($diagnostics["IsAccountAuthorizedToMLS"]) ? $diagnostics["IsAccountAuthorizedToMLS"] : '';
		$setDiagnostics["IsAccountValid"] = isset($diagnostics["IsAccountValid"]) ? $diagnostics["IsAccountValid"] : '';
		$setDiagnostics["IsApiKeyAuthorizedToUri"] = isset($diagnostics["IsApiKeyAuthorizedToUri"]) ? $diagnostics["IsApiKeyAuthorizedToUri"] : '';
		$setDiagnostics["IsApiKeyAuthorizedToIP"] = isset($diagnostics["IsApiKeyAuthorizedToIP"]) ? $diagnostics["IsApiKeyAuthorizedToIP"] : '';

		$setDiagnostics["PhpVersionAcceptable"] = version_compare(phpversion(), DSIDXPRESS_MIN_VERSION_PHP) != -1;
		$setDiagnostics["UrlInterceptSet"] = get_option("permalink_structure") != "" && !preg_match("/index\.php/", $permalinkStructure);
		$setDiagnostics["ClockIsAccurate"] = $timeDiff < $secondsIn2Hrs && $timeDiff > -1 * $secondsIn2Hrs;

		if(isset($diagnostics["AllowedApiRequestCount"])) {
			$setDiagnostics["UnderMonthlyCallLimit"] = $diagnostics["AllowedApiRequestCount"] === 0 || $diagnostics["AllowedApiRequestCount"] > $diagnostics["CurrentApiRequestCount"];
		}
		else {
			$setDiagnostics["UnderMonthlyCallLimit"] = '';
		}

		$setDiagnostics["HasSearchAgentPro"] = $diagnostics["HasSearchAgentPro"];
		$setDiagnostics["dsIDXPressPackage"] = isset($diagnostics["dsIDXPressPackage"]) ? $diagnostics["dsIDXPressPackage"] : '';
		$setDiagnostics["DetailsRequiresRegistration"] = $diagnostics["DetailsRequiresRegistration"];

		$setDiagnostics["DiagnosticsSuccessful"] = true;
		foreach ($setDiagnostics as $key => $value) {
			if (!$value && $key != "HasSearchAgentPro" && $key != "DetailsRequiresRegistration")
				$setDiagnostics["DiagnosticsSuccessful"] = false;
		}
		$wp_rewrite->flush_rules();

		return $setDiagnostics;
	}
	static function SanitizeOptions($options) {
		if(!isset($options) || !$options) 
			$options = array();
		else
			$options = array_map( 'wp_strip_all_tags', $options );

			if (!empty($options["FullApiKey"])) {
				$options["FullApiKey"] = trim($options["FullApiKey"]);
				$apiKeyParts = explode("/", $options["FullApiKey"]);
				unset($options["FullApiKey"]);
				if (sizeof($apiKeyParts) == 3) {
					$options["AccountID"] = $apiKeyParts[0];
					$options["SearchSetupID"] = $apiKeyParts[1];
					$options["PrivateApiKey"] = $apiKeyParts[2];
					dsSearchAgent_ApiRequest::FetchData("BindToRequester", array(), false, 0, $options);
					$diagnostics = self::RunDiagnostics($options);
					$options["HasSearchAgentPro"] = $diagnostics["HasSearchAgentPro"];
					$options["dsIDXPressPackage"] = $diagnostics["dsIDXPressPackage"];
					$options["Activated"] = $diagnostics["DiagnosticsSuccessful"];
					if (!$options["Activated"] && isset($options["HideIntroNotification"]))
						unset($options["HideIntroNotification"]);
				}
			}
			// different option pages fill in different parts of this options array, so we simply merge what's already there with our new data
			if ($full_options = get_option(DSIDXPRESS_OPTION_NAME)) {
				// clear out old ResultsMapDefaultState if its replacement, ResultsDefaultState is set
				if (isset($options['ResultsDefaultState']) && isset($full_options['ResultsMapDefaultState'])) {
					unset($full_options['ResultsMapDefaultState']);
				}
				// make sure the option to remove diverse solutions links is removed if unchecked
				if (isset($full_options['RemoveDsDisclaimerLinks'])) {
					unset($full_options['RemoveDsDisclaimerLinks']);
				}
				// merge existing data with new data
				$options = array_merge($full_options, $options);
			}
			// call the sitemap rebuild action since they may have changed their sitemap locations. the documentation says that the sitemap
			// may not be rebuilt immediately but instead scheduled into a cron job for performance reasons.
			do_action("sm_rebuild");
		return $options;
	}

	/*
	 * We're using the sanitize to capture the POST for these options so we can send them back to the diverse API
	 * since we save and consume -most- options there.
	 */
	static function SanitizeApiOptions($options) {
		if(!isset($options) || !$options) 
			$options = array();
		else
			$options = array_map( 'wp_strip_all_tags', $options );
			
			if (is_array($options)) {
				$options_text = "";
				foreach ($options as $key => $value) {
					if ($options_text != "") $options_text .= ",";
					if ($key == 'RestrictResultsToZipcode' || $key == 'RestrictResultsToCity' || $key == 'RestrictResultsToCounty') {
					$value = preg_replace("/\r\n|\r|\n/", ",", $value);//replace these values with new commas in api db
					}
					$options_text .= $key.'|'.urlencode($value);
					//unset($options[$key]);
				}
				$result = dsSearchAgent_ApiRequest::FetchData("SaveAccountOptions", array("options" => $options_text), false, 0);
				// this serves to flush the cache
				dsSearchAgent_ApiRequest::FetchData("AccountOptions", array(), false, 0);
			}

		if ($full_options = get_option(DSIDXPRESS_API_OPTIONS_NAME)) {
			$options = array_merge($full_options, $options);
		}

		return $options;
	}

	static function CompareListObjects($a, $b)
	{
		$al = strtolower($a["value"]);
		$bl = strtolower($b["value"]);
		if ($al == $bl) {
			return 0;
		}
		return ($al > $bl) ? +1 : -1;
	}
	public static function NavMenus($posts) {
		$options = get_option(DSIDXPRESS_OPTION_NAME);

		// offset the time to ensure we have a unique post id
		$post_id = time() + sizeof($posts);

		if (isset($options['AgentID']) && $options['AgentID'] != '') {
			$posts[] = (object) array(
				'ID'           => $post_id,
				'object_id'    => $post_id,
				'post_content' => '',
				'post_excerpt' => '',
				'post_parent'  => 0,
				'post_title'   => 'My Listings',
				'post_type'    => 'nav_menu_item',
				'type'         => 'custom',
				'url'          => get_home_url().'/idx/?'.urlencode('idx-q-ListingAgentID<0>') . '=' . $options['AgentID'],
				'zpress_page'  => true
			);
			$post_id++;

			$posts[] = (object) array(
				'ID'           => $post_id,
				'object_id'    => $post_id,
				'post_content' => '',
				'post_excerpt' => '',
				'post_parent'  => 0,
				'post_title'   => 'My Sold Properties',
				'post_type'    => 'nav_menu_item',
				'type'         => 'custom',
				'url'          => get_home_url().'/idx/?'.urlencode('idx-q-ListingAgentID<0>') . '=' . $options['AgentID'] .'&idx-q-ListingStatuses=8',
				'zpress_page'  => true
			);
			$post_id++;
		}

		if (isset($options['OfficeID']) && $options['OfficeID'] != '') {
			$posts[] = (object) array(
				'ID'           => $post_id,
				'object_id'    => $post_id,
				'post_content' => '',
				'post_excerpt' => '',
				'post_parent'  => 0,
				'post_title'   => 'My Office Listings',
				'post_type'    => 'nav_menu_item',
				'type'         => 'custom',
				'url'          => get_home_url().'/idx/?'.urlencode('idx-q-ListingOfficeID<0>') . '=' . $options['OfficeID'],
				'zpress_page'  => true
			);
			$post_id++;
		}

		$posts[] = (object) array(
			'ID'           => $post_id,
			'object_id'    => $post_id,
			'post_content' => '',
			'post_excerpt' => '',
			'post_parent'  => 0,
			'post_title'   => 'Real Estate Search',
			'post_type'    => 'nav_menu_item',
			'type'         => 'custom',
			'url'          => get_home_url().'/idx/search/',
			'zpress_page'  => true
		);

		return $posts;
	}
	static function CreateLinkBuilderMenuWidget()
	{
		add_meta_box( 'add-link-builder', __('Listings Page Builder'), array('dsSearchAgent_Admin', 'CreateLinkBuilder'), 'nav-menus', 'side', 'default' );
	}
	/**
	 * Displays a metabox for the link builder menu item.
	 */
	static function CreateLinkBuilder() {
		global $_nav_menu_placeholder, $nav_menu_selected_id;
		$_nav_menu_placeholder = 0 > $_nav_menu_placeholder ? $_nav_menu_placeholder - 1 : -1;
		
		if (isset($_REQUEST['customlink-tab'])) {
			$customlink_tab = sanitize_text_field($_REQUEST['customlink-tab']);
		}

		$current_tab = 'create';
		if ( isset( $customlink_tab) && in_array($customlink_tab, array('create', 'all') ) ) {
			$current_tab = $customlink_tab;
		}

		$removed_args = array(
			'action',
			'customlink-tab',
			'edit-menu-item',
			'menu-item',
			'page-tab',
			'_wpnonce',
		);

		dsSearchAgent_Admin::LinkBuilderHtml(false, $_nav_menu_placeholder, $nav_menu_selected_id);
	}

	public static function LinkBuilderHtml($in_post_dialog = false, $_nav_menu_placeholder = -1, $nav_menu_selected_id = 1, $in_idx_page_options=false, $preset_url='') {
		if (isset($_GET['selected_text'])) {
			$selected_text  = sanitize_text_field($_GET['selected_text']);
		}

		if (isset($_GET['selected_text'])) {
			$selected_text  = sanitize_text_field($_GET['selected_text']);
		}

		if (isset($_GET['selected_url'])) {
			$selected_url  = sanitize_text_field($_GET['selected_url']);
		}

		if (isset($_GET['idxlinkmode'])) {
			$idxlinkmode  = sanitize_text_field($_GET['idxlinkmode']);
		}

		$label_class = (!$in_post_dialog) ? ' input-with-default-title' : '';
		$label_value = ($in_post_dialog && isset($selected_text)) ? ' value="'.esc_attr($selected_text).'"' : '';
		$url_value   = ($in_post_dialog && isset($selected_url)) ? esc_url($selected_url) : 'https://';
		$link_mode   = (isset($idxlinkmode)) ? $idxlinkmode : '';
		if(!empty($preset_url)){
			$url_value = $preset_url;
		}

		$property_types_html = "";
		$property_features_html = "";
		$property_types = dsSearchAgent_ApiRequest::FetchData('AccountSearchSetupPropertyTypes', array(), false, 60 * 60 * 24);
		if(!empty($property_types) && is_array($property_types)){
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
		$property_features= dsSearchAgent_ApiRequest::FetchData('PropertyFeatures', array(), false, 60 * 60 * 24);
		
		if(!empty($property_features) && is_array($property_features)){
		    $property_features = json_decode($property_features["body"]);
		    foreach ($property_features as $property_feature) {
		        $checked_html = '';
		        $name = esc_html($property_feature->DisplayName);
				$id = esc_html($property_feature->SearchSetupFeatureID);
		        $property_features_html .= <<<HTML
{$id}: {$name},
HTML;
		    }
		}
		$property_types_html = substr($property_types_html, 0, strlen($property_types_html)-1);
		$property_features_html = substr($property_features_html, 0, strlen($property_features_html)-1);
?>
	<script> zpress_home_url = '<?php echo get_home_url() ?>';</script>
	<div id="dsidxpress-link-builder" class="customlinkdiv">
	    <input type="hidden" id="linkBuilderPropertyTypes" value="<?php echo esc_attr_e($property_types_html) ?>" />
		<input type="hidden" id="linkBuilderPropertyFeatures" value="<?php echo esc_attr_e($property_features_html) ?>" />
		<input type="hidden" value="custom" name="menu-item[<?php echo esc_attr_e($_nav_menu_placeholder); ?>][menu-item-type]" />
		<input type="hidden" value="<?php esc_attr_e($link_mode) ?>" id="dsidx-linkbuilder-mode" ?>
		<?php if(!$in_idx_page_options): ?>
		<p class="dsidxpress-item-wrap">
			<label class="howto" for="dsidxpress-menu-item-label" style="width: 100%;">
				<span><?php _e('Label'); ?></span>
				<input id="dsidxpress-menu-item-label" name="menu-item-label" type="text" class="regular-text menu-item-textbox<?php echo esc_attr_e($label_class) ?>" title="<?php esc_attr_e('Menu Item'); ?>"<?php echo esc_html($label_value); ?> />
			</label>
		</p>
		<?php endif; ?>
		<p class="dsidxpress-item-wrap">
			<label class="howto" for="dsidxpress-filter-menu" style="width: 100%;s">
				<span><?php _e('Add Filter'); ?></span>
				<select class="regular-text" id="dsidxpress-filter-menu" ></select>
			</label>
		</p>

		<div id="dsidxpress-editor-wrap" class="dsidxpress-item-wrap hidden">
			<div class="dsidxpress-filter-editor">
				<div class="dsidxpress-editor-header">
					<h4>Filter results by <b>Beds</b></h4>
					<span class="dsidx-editor-cancel"><a href="javascript:void(0)"></a></span>
				</div>
				<div class="dsidxpress-editor-main"></div>
				<div class="buttons">
					<input type="button" value="Update this Filter" class="button-primary" />
					<input type="button" value="Cancel" class="button-secondary dsidx-editor-cancel" />
				</div>
			</div>
		</div>

		<div id="dsidxpress-filters-wrap" class="dsidxpress-item-wrap hidden">
			<span><?php _e('Filters'); ?></span>
			<ul id="dsidxpress-filter-list"></ul>
		</div>

		<?php if(!$in_idx_page_options): ?>
		<p class="dsidxpress-item-wrap">
			<label class="howto dsidxpress-checkbox">
				<input id="dsidxpress-show-url" type="checkbox" />
				<span><?php _e('Display Generated URL'); ?></span>
			</label>
		</p>
		<?php endif; ?>

		<?php
		$inputName = 'menu-item['.$_nav_menu_placeholder.'][menu-item-url]';

		if($in_idx_page_options):
			$inputName = 'dsidxpress-assembled-url';
		endif; ?>

		<p id="dsidxpress-assembled-url-wrap" class="dsidxpress-item-wrap hidden">
			<label class="howto" for="dsidxpress-assembled-url">
				<span><?php _e('URL'); ?></span>
				<textarea id="dsidxpress-assembled-url" name="<?php echo esc_attr($inputName); ?>" type="text" rows="4" class="code menu-item-textbox"><?php echo $url_value; ?></textarea>
			</label>
		</p>

		<?php if(!$in_idx_page_options): ?>
		<p class="button-controls">
			<span class="add-to-menu">
				<?php if (!$in_post_dialog): ?>
				<img id="img-link-builder-waiting" style="display:none;" src="<?php echo admin_url( 'images/wpspin_light.gif' ); ?>" alt="" />
				<input type="submit"<?php disabled( $nav_menu_selected_id, 0 ); ?> class="button-secondary submit-add-to-menu" value="<?php esc_attr_e('Add to Menu'); ?>" name="add-custom-menu-item" id="submit-linkbuilderdiv" />
				<?php else: ?>
				<input type="button" id="dsidxpress-lb-cancel" name="cancel" value="Cancel" class="button-secondary" onclick="tinyMCEPopup.close();" />
				<input type="button" id="dsidxpress-lb-insert" name="insert" value="<?php esc_attr_e($link_mode); ?> Link" class="button-primary" style="text-transform: capitalize;" onclick="dsidxLinkBuilder.insert();" />
				<?php endif ?>
			</span>
		</p>
		<?php endif; ?>
	</div><!-- /#dsidxpress-link-builder -->
	<?php
	}
}
?>
