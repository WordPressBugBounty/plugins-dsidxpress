<?php

// Global Variables Declarations
$dsIdxOptions = array();

// Global Variables Assignment
$dsIdxOptions = get_option(DSIDXPRESS_OPTION_NAME);


class dsSearchAgent_GlobalData {

	static function GetPropertyTypes() { 
		STATIC $propertyTypes;

		if(empty($propertyTypes)) {
			$propertyTypes = dsSearchAgent_ApiRequest::FetchData("AccountSearchSetupFilteredPropertyTypes", array(), false);
			$propertyTypes = $propertyTypes["response"]["code"] == "200" ? json_decode($propertyTypes["body"]) : null;
		}

		return $propertyTypes;
	}

	static function GetAccountOptions($dieOnError) { 
		STATIC $accountOptions;

		if(empty($accountOptions)) {
			$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("AccountOptions");
			if (!empty($apiHttpResponse["errors"]) || $apiHttpResponse["response"]["code"] != "200"){
				if($dieOnError) {
					switch ($apiHttpResponse["response"]["code"]) {
						case 403:
							wp_die(
								"We're sorry, but there’s nothing to display here; MLS data service is not activated for this account.");
						break;
						default:
							wp_die("We're sorry, but we ran into a temporary problem while trying to load the account data. Please check back soon.", "Account data load error");
					}
				}
			} else {
				$accountOptions = json_decode($apiHttpResponse["body"]);
			}
		}

		return $accountOptions;
	}

}

?>