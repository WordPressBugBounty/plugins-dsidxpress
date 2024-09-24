<?php
class dsSearchAgent_Shortcodes {
	static function Listing($atts, $content = null, $code = "") {
		$options = get_option(DSIDXPRESS_OPTION_NAME);
			if (!isset($options["Activated"]) || !$options["Activated"])
				return "";

		$atts = shortcode_atts(array(
			"mlsnumber"			=> "",
			"statuses"			=> "",
			"showall"			=> "false",
			"showpricehistory"	=> "false",
			"showschools"		=> "false",
			"showextradetails"	=> "false",
			"showfeatures"		=> "false",
			"showlocation"		=> "false"
		), $atts);
		$apiRequestParams = array();
		$apiRequestParams["responseDirective.ViewNameSuffix"] = "shortcode";
		$apiRequestParams["query.MlsNumber"] = str_replace(" ", "", $atts["mlsnumber"]);
		if(self::TranslateStatuses($atts["statuses"])){
			$apiRequestParams["query.ListingStatuses"] = self::TranslateStatuses($atts["statuses"]);
		} //else the api will use active and conditional by default
		$apiRequestParams["responseDirective.ShowSchools"] = $atts["showschools"];
		$apiRequestParams["responseDirective.ShowPriceHistory"] = $atts["showpricehistory"];
		$apiRequestParams["responseDirective.ShowAdditionalDetails"] = $atts["showextradetails"];
		$apiRequestParams["responseDirective.ShowFeatures"] = $atts["showfeatures"];
		$apiRequestParams["responseDirective.ShowLocation"] = $atts["showlocation"];

		if ($atts["showall"] == "true") {
			$apiRequestParams["responseDirective.ShowSchools"] = "true";
			$apiRequestParams["responseDirective.ShowPriceHistory"] = "true";
			$apiRequestParams["responseDirective.ShowAdditionalDetails"] = "true";
			$apiRequestParams["responseDirective.ShowFeatures"] = "true";
			$apiRequestParams["responseDirective.ShowLocation"] = "true";
		}
		dsidx_footer::ensure_disclaimer_exists();
		$isAdminPost = false;

		$referer = '';
		if(isset($_SERVER["HTTP_REFERER"])) {
			$referer = stripslashes($_SERVER["HTTP_REFERER"]);
		}

		if ((strpos($referer, '/wp-admin/') !== false) && ((strpos($referer, 'action') !== false) || (strpos($referer, 'post_type') !== false)) ) {
			$isAdminPost = true;
		}		
		if (!$isAdminPost && defined("DS_REQUEST_MULTI_AVAILABLE") && DS_REQUEST_MULTI_AVAILABLE==true)  {
			$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Details", $apiRequestParams,false,null,null,false,true);
			if (empty($apiHttpResponse['body'])) {
				$apiHttpResponse['body'] = '%%ds_Details|' . json_encode($apiHttpResponse) . '|ds_end%%';
			}	
			else {
				if(substr($apiHttpResponse['body'], 0, 10) === '%%MLSMSG%%') {
					$apiHttpResponse['body'] = substr($apiHttpResponse['body'], 10);
					$apiHttpResponse['body'] = '<p class="dsidx-error">'.$apiHttpResponse['body'].'</p>';
				}
			}	
			return $apiHttpResponse["body"];
		} else {
			$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Details", $apiRequestParams, false);
			if ($apiHttpResponse["response"]["code"] == "403") {
				return '<p class="dsidx-error">'.DSIDXPRESS_INACTIVE_ACCOUNT_MESSAGE.'</p>';
			}
			if ($apiHttpResponse["response"]["code"] == "404") {
				return '<p class="dsidx-error">'.sprintf(DSIDXPRESS_INVALID_MLSID_MESSAGE, $atts['mlsnumber']).'</p>';
			}
			else if (empty($apiHttpResponse["errors"]) && $apiHttpResponse["response"]["code"] == "200") {
				return $apiHttpResponse["body"];
			} else {
				return '<p class="dsidx-error">'.DSIDXPRESS_IDX_ERROR_MESSAGE.'</p>';
			}
		}
		
	}
	static function Listings($atts, $content = null, $code = "") {
		$options = get_option(DSIDXPRESS_OPTION_NAME);
			if (!isset($options["Activated"]) || !$options["Activated"])
				return "";

		$atts = shortcode_atts(array(
			"city"			=> "",
			"community"		=> "",
			"county"		=> "",
			"tract"			=> "",
			"zip"			=> "",
			"minprice"		=> "",
			"maxprice"		=> "",
			"minbeds"		=> "",
			"maxbeds"		=> "",
			"minbaths"		=> "",
			"maxbaths"		=> "",
			"mindom"		=> "",
			"maxdom"		=> "",
			"minyear"		=> "",
			"maxyear"		=> "",
			"minimpsqft"	=> "",
			"maximpsqft"	=> "",
			"minlotsqft"	=> "",
			"maxlotsqft"	=> "",
			"agentid"		=> "",
			"officeid"		=> "",
			"statuses"		=> "",
			"propertytypes"	=> "",
			"linkid"		=> "",
			"count"			=> "5",
			"orderby"		=> "DateAdded",
			"orderdir"		=> "DESC",
			"showlargerphotos"	=> "false",
			"listtabalignment"  => "Left",
			"shortcoderesultsview"  => "",
			"addressmasks"  => "",
			"mlsnumbers"  => "",
			"propertyfeatures"	=> ""
		), $atts);

		$apiRequestParams = array();
		$apiRequestParams["responseDirective.ViewNameSuffix"] = "shortcode";
		$apiRequestParams["responseDirective.IncludeMetadata"] = "true";
		$apiRequestParams["responseDirective.IncludeLinkMetadata"] = "true";
		$apiRequestParams["responseDirective.ShowLargerPhotos"] = $atts["showlargerphotos"];
		$apiRequestParams["responseDirective.ListTabAlignment"] = $atts["listtabalignment"];
		$apiRequestParams["responseDirective.ShortcodeResultsView"] = $atts["shortcoderesultsview"];		
		$apiRequestParams["query.Cities"] = htmlspecialchars_decode($atts["city"]);
		$apiRequestParams["query.Communities"] = htmlspecialchars_decode($atts["community"]);
		$apiRequestParams["query.Counties"] = htmlspecialchars_decode($atts["county"]);
		$apiRequestParams["query.TractIdentifiers"] = htmlspecialchars_decode($atts["tract"]);
		$apiRequestParams["query.ZipCodes"] = $atts["zip"];
		$apiRequestParams["query.PriceMin"] = $atts["minprice"];
		$apiRequestParams["query.PriceMax"] = $atts["maxprice"];
		$apiRequestParams["query.BedsMin"] = $atts["minbeds"];
		$apiRequestParams["query.BedsMax"] = $atts["maxbeds"];
		$apiRequestParams["query.BathsMin"] = $atts["minbaths"];
		$apiRequestParams["query.BathsMax"] = $atts["maxbaths"];
		$apiRequestParams["query.DaysOnMarketMin"] = $atts["mindom"];
		$apiRequestParams["query.DaysOnMarketMax"] = $atts["maxdom"];
		$apiRequestParams["query.YearBuiltMin"] = $atts["minyear"];
		$apiRequestParams["query.YearBuiltMax"] = $atts["maxyear"];
		$apiRequestParams["query.ImprovedSqFtMin"] = $atts["minimpsqft"];
		$apiRequestParams["query.ImprovedSqFtMax"] = $atts["maximpsqft"];
		$apiRequestParams["query.LotSqFtMin"] = $atts["minlotsqft"];
		$apiRequestParams["query.LotSqFtMax"] = $atts["maxlotsqft"];
		
		if ($atts["agentid"]) {
			$agentIds = explode(",", str_replace(" ", "", $atts["agentid"]));
			$agentIds = array_combine(range(0, count($agentIds) - 1), $agentIds);
			foreach ($agentIds as $key => $value)
				$apiRequestParams["query.ListingAgentID[{$key}]"] = $value;
		}		

		if ($atts["officeid"]) {
			$officeIds = explode(",", str_replace(" ", "", $atts["officeid"]));
			$officeIds = array_combine(range(0, count($officeIds) - 1), $officeIds);
			foreach ($officeIds as $key => $value)
				$apiRequestParams["query.ListingOfficeID[{$key}]"] = $value;
		}
		
		if(self::TranslateStatuses($atts["statuses"]))
			$apiRequestParams["query.ListingStatuses"] = self::TranslateStatuses($atts["statuses"]);
		else
			$apiRequestParams["query.ListingStatuses"] = 3;
		if ($atts["propertytypes"]) {
			$propertyTypes = explode(",", str_replace(" ", "", $atts["propertytypes"]));
			$propertyTypes = array_combine(range(0, count($propertyTypes) - 1), $propertyTypes);
			foreach ($propertyTypes as $key => $value)
				$apiRequestParams["query.PropertyTypes[{$key}]"] = $value;
		}

		if ($atts["propertyfeatures"]) {
			$propertyFeatures = explode(",", str_replace(" ", "", $atts["propertyfeatures"]));
			$propertyFeatures = array_combine(range(0, count($propertyFeatures) - 1), $propertyFeatures);
			foreach ($propertyFeatures as $key => $value)
				$apiRequestParams["query.PropertyFeatures[{$key}]"] = $value;
		}		

		if ($atts["addressmasks"]) {
			$addressMasks = explode(",", $atts["addressmasks"]);
			$addressMasks = array_combine(range(0, count($addressMasks) - 1), $addressMasks);
			foreach ($addressMasks as $key => $value)
				$apiRequestParams["query.AddressMasks[{$key}]"] = trim($value);
		}

		if ($atts["mlsnumbers"]) {
			$mlsNumbers = explode(",", str_replace(" ", "", $atts["mlsnumbers"]));
			$mlsNumbers = array_combine(range(0, count($mlsNumbers) - 1), $mlsNumbers);
			foreach ($mlsNumbers as $key => $value)
				$apiRequestParams["query.MlsNumbers[{$key}]"] = $value;
		}		
		
		if ($atts["linkid"]) {
			$apiRequestParams["query.LinkID"] = $atts["linkid"];
			$apiRequestParams["query.ForceUsePropertySearchConstraints"] = "true";
		}
		$apiRequestParams["directive.ResultsPerPage"] = $atts["count"];
		$apiRequestParams["directive.SortOrders[0].Column"] = $atts["orderby"];
		$apiRequestParams["directive.SortOrders[0].Direction"] = $atts["orderdir"];
		dsidx_footer::ensure_disclaimer_exists();
		$isAdminPost = false;

		$referer = '';
		if(isset($_SERVER["HTTP_REFERER"])) {
			$referer  =  stripslashes($_SERVER["HTTP_REFERER"]);
		}				
		if ((strpos($referer, '/wp-admin/') !== false) && ((strpos($referer, 'action') !== false) || (strpos($referer, 'post_type') !== false)) ) {
			$isAdminPost = true;
		}			
		if (!$isAdminPost &&  defined("DS_REQUEST_MULTI_AVAILABLE") && DS_REQUEST_MULTI_AVAILABLE==true)  {
			$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Results", $apiRequestParams, true,null, null, false, true);
			if (empty($apiHttpResponse['body'])) {					
					$apiHttpResponse['body'] = '%%ds_Results|' . json_encode($apiHttpResponse) . '|ds_end%%';
			} else {				
				$apiHttpResponse['body'] =  $apiHttpResponse['body'];
			}
			wp_reset_postdata();
			return $apiHttpResponse["body"];	
		} else {
				$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Results", $apiRequestParams);
				if (empty($apiHttpResponse["errors"]) && $apiHttpResponse["response"]["code"] == "200") {
					return $apiHttpResponse["body"];
				} else {
					if ($apiHttpResponse["response"]["code"] == "403") {
						return '<p class="dsidx-error">'.DSIDXPRESS_INACTIVE_ACCOUNT_MESSAGE.'</p>';
					}
					return '<p class="dsidx-error">'.DSIDXPRESS_IDX_ERROR_MESSAGE.'</p>';
				}
		}		
	}
	
	static function TranslateStatuses($ids) {
		$values = '';
		$ids = explode(',',$ids);
		foreach ($ids as $id) {
			switch($id) {
				case 1: $values .= 'Active,'; break;
				case 2: $values .= 'Conditional,'; break;
				case 3: $values .= 'Pending,'; break;
				case 4: $values .= 'Sold,'; break;
			}
		}
		return substr($values, 0, strlen($values) - 1);
	}

	static function IdxQuickSearch($atts, $content = null, $code = ""){
		$atts = shortcode_atts(array(
			"format"		=> "horizontal",
			"modernview" => "no",
			"viewtype" => "",
		), $atts);
		ob_start();
		dsSearchAgent_IdxQuickSearchWidget::shortcodeWidget(array('widgetType'=>$atts['format'], 'modernView'=>$atts['modernview'], 'viewType'=>$atts['viewtype'], 'class'=>'dsidx-inline-form'));
		$markup = ob_get_clean();
		return '<p>'.$markup.'</p>';
	}
	static function IdxRegistrationForm($atts, $content = null, $code = ""){
		$options = get_option(DSIDXPRESS_OPTION_NAME);
		$quickSearchAtts = shortcode_atts(array(
			"format"		=> "horizontal",
			"modernview" => "yes",
		), null);
		if(!isset($options["dsIDXPressPackage"]) || $options["dsIDXPressPackage"] != "pro"){ /* if not pro show quick search */
			return self::IdxQuickSearch($quickSearchAtts);
		}
		else if (isset($_COOKIE['dsidx-visitor-auth']) && sanitize_text_field($_COOKIE['dsidx-visitor-auth']) != '') { /* if logged in show quick search */
			return self::IdxQuickSearch($quickSearchAtts);
		}	
		else {	/* show registration form */
				$accountID = isset($options["AccountID"])?esc_html($options["AccountID"]):false;
				$searchSetupID = isset($options["SearchSetupID"])?esc_html($options["SearchSetupID"]):false;
				$redirectURL = isset($atts["redirecttourl"]) ? esc_url($atts["redirecttourl"]) : '';
				$socialLogin=false;
				$contentForm="col-12";
				$contentFormField="col-12 form-group";
				$contentSocial="d-none";
				$uniqueFormID = esc_html(sha1('dsidx-shortcode-registration-form'.$accountID.$searchSetupID.$redirectURL.$socialLogin));
				$currentURL = site_url();
				$regLinkDiv="col-12 col-md-8 text-md-right";	
				$socialDisplay="none";
				if(isset($atts['includesociallogin']) && strtolower($atts['includesociallogin'])=='yes') {
					$socialLogin = true;
					$socialDisplay="inline";
					$contentForm = "col-12 col-md";
					$contentSocial="col-12 col-md text-center pt-md-5";					
					$regButtonStyle = "dsidx-shortcode-registration-submit";	
					$regButtonDiv="col-8";
					$regLinkDiv="col-12 text-left";	
					
				}
				else {
					$regButtonDiv="col-8 col-md-4";
					$contentFormField="col-12 col-md-6 form-group";
					$regButtonStyle = "dsidx-shortcode-registration-submit-no-sso";
				}

				$account_options = dsSearchAgent_GlobalData::GetAccountOptions(false);
				$regConsentHTML="";
				$isConsent = "false";				
				if(isset($account_options->{'RegistrationShowConsent'}) && strtolower($account_options->{'RegistrationShowConsent'})=='true') {
					$isConsent = "true";	
					$regConsentHTML = <<<HTML
					<div class="row">
						<div class="col-12 mb-3">
							<label class="checkbox" for="dsidx-shortcode-register-consent" style="text-align:left;">
								<input type="checkbox" id="dsidx-shortcode-register-consent" name="newVisitor.Consent" class="checkbox" checked="checked">
								By submitting this form, you confirm that you have read and accept the <a href="https://www.diversesolutions.com/privacy-policy/" target="_blank">Data Privacy Policy</a>
							</label>						
						</div>
					</div>
HTML;
				}

    return <<<HTML
 <div id="dsidx-shortcode-registration" class="ds-bs dsidx-shortcode-registration-main">
		<p class="dsidx-shortcode-registration-header ml-1 mr-1 text-center font-italic">
			<em>Save your favorite listings, searches, and receive the latest listing alerts.</em>
    	</p>
        <div class="row">
            <div class="{$contentForm} registrationform">
                <form id={$uniqueFormID} action="" method="post">
                    <input type="hidden" id="dsidx-shortcode-registration-referral" name="newVisitor.Referral" value="{$currentURL}" />
                    <input type="hidden" id="dsidx-shortcode-registration-packagetypeid" name="newVisitor.PackageTypeID" value="2" />
                    <input type="hidden" id="dsidx-isoptin" name="newVisitor.isOptIn" value="false" />
					<input type="hidden" id="dsidx-shortcode-registration-redirectURL" name="dsidx-shortcode-registration-redirectURL" value="{$redirectURL}" />
					<div class="row">
						<div class="{$contentFormField}">
							<div>
								<label for="dsidx-login-first-name">First Name:</label>
							</div>
							<div>
								<input type="text" id="dsidx-shortcode-registration-first-name" class="text dsidx-shortcode-registration-field"  name="newVisitor.FirstName" />
							</div>
						</div>
						<div class="{$contentFormField}">
							<div>
								<label for="dsidx-login-last-name">Last Name:</label>
							</div>
							<div>
								<input type="text" id="dsidx-shortcode-registration-last-name" class="text dsidx-shortcode-registration-field" name="newVisitor.LastName" />
							</div>
						</div>
					</div>
					<div class="row">
						<div class="{$contentFormField}">
							<div>
								<label for="dsidx-login-last-name">Email:</label>
							</div>
							<div>
								<input type="text" id="dsidx-shortcode-registration-email" class="text dsidx-shortcode-registration-field" name="newVisitor.Email" />
							</div>
						</div>
						<div class="{$contentFormField}">
							<div>
								<label for="dsidx-shortcode-registration-phone-number">Phone Number:</label>
							</div>
							<div>
								<input type="text" id="dsidx-shortcode-registration-phone-number" class="text dsidx-shortcode-registration-field" name="newVisitor.PhoneNumber" />
							</div>
						</div>
					</div>
					<div class="row">
						<div class="{$contentFormField}">
							<div>
								<label for="dsidx-shortcode-registration-password">Password:</label>
							</div>
							<div>
								<input type="password" id="dsidx-shortcode-registration-password" class="text dsidx-shortcode-registration-field" name="newVisitor.Password" />
							</div>
						</div>
						<div class="{$contentFormField}">
							<div>
								<label for="dsidx-shortcode-registration-confirm-password">Confirm Password:</label>
							</div>
							<div>
								<input type="password" id="dsidx-shortcode-registration-confirm-password" class="text dsidx-shortcode-registration-field" name="newVisitor.Password_Confirm" />
							</div>
						</div>
					</div>

					{$regConsentHTML}

                    <div class="row mb-4">
                        <div class = "{$regButtonDiv}">
							<input type="submit" id="dsidx-shortcode-registration-submit" value="Register" 
							class="dsidx-auth-large-button {$regButtonStyle}" />
						</div>
						<div class = "{$regLinkDiv}"> 
							Already have an account? <a href="{$currentURL}/idx?promptLogin=1">Login</a>
						</div>
						<div class="col-12" >
                            <div class="dsidx-shortcode-registration-dialog-message" style="display:none"></div>
                        </div>
                    </div>
                </form>
			</div>
            <div class="col-12 col-md-1 mt-4 mb-4 mt-md-5 mb-md-5 ml-md-3 mr-md-3" style="display:{$socialDisplay}">
				<div class="dsidx-sso-separator-bar">&nbsp;</div>
            </div>
			<div class="{$contentSocial}" style="display:{$socialDisplay}">
				<div class="col-12">
					<div class="dsidx-shortcode-registration-sso-message mb-3">
						<em>Sign in to, or create an account using your existing social account</em>
					</div>
				</div>
				<div class="col-12">
					<button class="dsidx-loginBtn dsidx-loginBtn--facebook" onclick="javascript:  dsidx.auth.LaunchSocialLogin('facebook','{$accountID}','{$searchSetupID}','{$redirectURL}',{$isConsent});" formnovalidate="">
						Facebook
					</button>
				</div>
				<div style="clear:both;padding: 1% 0;"></div>
				<div class="col-12">
					<button class="dsidx-loginBtn dsidx-loginBtn--google" onclick="javascript: return dsidx.auth.LaunchSocialLogin('google','{$accountID}','{$searchSetupID}','{$redirectURL}',{$isConsent});" formnovalidate="">
						Google&nbsp;&nbsp;&nbsp;
					</button>
				</div>					
			</div>
        </div>
    </div>
HTML;
    }
	}
}

add_shortcode("idx-listing", array("dsSearchAgent_ShortCodes", "Listing"));
add_shortcode("idx-listings", array("dsSearchAgent_ShortCodes", "Listings"));
add_shortcode("idx-quick-search", array("dsSearchAgent_ShortCodes", "IdxQuickSearch"));
/*DIV-16050:Registration form shortcode will only be added for pro users*/
$userOptions = get_option(DSIDXPRESS_OPTION_NAME);
add_shortcode("idx-registration-form", array("dsSearchAgent_ShortCodes", "IdxRegistrationForm"));
?>