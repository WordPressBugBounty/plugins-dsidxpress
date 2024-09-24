<?php
class dsSearchAgent_ApiRequest {
	public static $ApiEndPoint = '';
	// do NOT change this value or you will be automatically banned from the API. since the data is only updated every two hours, and
	// since these API calls are computationally intensive on our servers, we need to set a reasonable cache duration.
	private static $CacheSeconds = 7200;
	private static $NumericValues = array(
		"query.PriceMin",
		"query.PriceMax",
		"query.ImprovedSqFtMin",
		"query.BedsMin",
		"query.BathsMin"
	);


	static function FetchData($action, $params = array(), $echoAssetsIfNotEnqueued = true, $cacheSecondsOverride = null, $options = null, $useGET = false, $bBuildOnly = false) {
		global $wp_query, $wp_version;
		$blocking = true;
		$options = $options ? $options : get_option(DSIDXPRESS_OPTION_NAME);
		$privateApiKey = isset($options["PrivateApiKey"])?$options["PrivateApiKey"]:false;
		$requestUri = self::$ApiEndPoint . $action;
		$compressCache = function_exists('gzdeflate') && function_exists('gzinflate');
    
		$params["SearchSetupID"] = isset($options["SearchSetupID"])?$options["SearchSetupID"]:false;
		$params["query.SearchSetupID"] = isset($options["SearchSetupID"])?$options["SearchSetupID"]:false;
		$params["requester.SearchSetupID"] = isset($options["SearchSetupID"])?$options["SearchSetupID"]:false;
		$params["requester.AccountID"] = isset($options["AccountID"])?$options["AccountID"]:false;
		if(is_array($wp_query->query) && isset($wp_query->query['ds-idx-listings-page'])){
			$params["requester.HideIdxUrlFilters"] = 'hide';
		}
		if(!isset($params["requester.ApplicationProfile"]))
			$params["requester.ApplicationProfile"] = "WordPressIdxModule";
		$params["requester.ApplicationVersion"] = $wp_version;
		$params["requester.PluginVersion"] = DSIDXPRESS_PLUGIN_VERSION;
		$params["requester.RequesterUri"] = get_home_url();
		
		if(isset($_COOKIE['dsidx-visitor-public-id']))
			$params["requester.VisitorPublicID"] = sanitize_text_field($_COOKIE['dsidx-visitor-public-id']);
		if(isset($_COOKIE['dsidx-visitor-auth']))
	    {
			$params["requester.VisitorAuth"] = sanitize_text_field($_COOKIE['dsidx-visitor-auth']);
			if(isset($_COOKIE['dsidx-visitor-auth-temp']))
			{
				setcookie('dsidx-visitor-auth-temp', '', time()-60*60*24*30, '/');	
			}
	    }

		if(isset($options["dsIDXPressPackage"]) && $options["dsIDXPressPackage"] == "lite")
			$params["requester.IsRegistered"] = current_user_can(dsSearchAgent_Roles::$Role_ViewDetails) ? "true" : "false";

		foreach (self::$NumericValues as $key) {
			if (array_key_exists($key, $params))
				$params[$key] = str_replace(",", "", $params[$key]);
		}

		ksort($params);
		$transientKey = "idx_" . sha1($action . "_" . http_build_query($params));
		if ($cacheSecondsOverride !== 0 && (!isset($options['DisableLocalCaching']) || $options['DisableLocalCaching'] != 'true') && !current_user_can( 'edit_posts' )) {
			$cachedRequestData = get_transient($transientKey);
			if ($cachedRequestData) {
				if(defined("DS_REQUEST_MULTI_AVAILABLE") && DS_REQUEST_MULTI_AVAILABLE == true)
				{
					if($action=="Results" || $action=="Details")
					{
						if($params["responseDirective.ViewNameSuffix"] == "widget" ||  $params["responseDirective.ViewNameSuffix"] == "shortcode")
						{
							$cachedResponse = array();
							$cachedRequestData = $compressCache ? unserialize(gzinflate(base64_decode($cachedRequestData))) : $cachedRequestData;				
							$cachedResponse["body"] = $cachedRequestData;
							$cachedResponse["body"] = self::ExtractAndEnqueueStyles($cachedResponse["body"], $echoAssetsIfNotEnqueued);
							$cachedResponse["body"] = self::ExtractAndEnqueueScripts($cachedResponse["body"]);

							if(!isset($cachedRequestData["body"]) || empty($cachedRequestData["body"]))
								return $cachedResponse;	
							else
								return $cachedResponse["body"];
						}
					}
				}

				$cachedRequestData = $compressCache ? unserialize(gzinflate(base64_decode($cachedRequestData))) : $cachedRequestData;
				$cachedRequestData["body"] = self::ExtractAndEnqueueStyles($cachedRequestData["body"], $echoAssetsIfNotEnqueued);
				$cachedRequestData["body"] = self::ExtractAndEnqueueScripts($cachedRequestData["body"]);
				return $cachedRequestData;
			}
		}

		// these params need to be beneath the caching stuff since otherwise the cache will be useless
		$params["requester.ClientIpAddress"] = $_SERVER["REMOTE_ADDR"];
		$params["requester.ClientUserAgent"] = $_SERVER["HTTP_USER_AGENT"];
		if(isset($_SERVER["HTTP_REFERER"]))
			$params["requester.UrlReferrer"] = $_SERVER["HTTP_REFERER"];
		$params["requester.UtcRequestDate"] = gmdate("c");
		
		if(!stristr($_SERVER["REQUEST_URI"], '/idx/')){
			$params["requester.PaginationUseActivationPath"] = 'true';
		}
		ksort($params);
		$stringToSign = "";
		foreach ($params as $key => $value) {
			$stringToSign .= "$key:$value\n";
			if (!isset($params[$key]))
				$params[$key] = "";
		}
		$stringToSign = rtrim($stringToSign, "\n");
		
		$params["requester.Signature"] = hash_hmac("sha1", $stringToSign, $privateApiKey);
		$response = null;


		if ($bBuildOnly === true) {
			return $params;			
		}

		if($action=='RecordVisit')
			$blocking = false;
		if ($useGET !== null && $useGET) {
			header('Location: ' . $requestUri . '?' . http_build_query($params));
		} 
		else
		{
			$response = (array)wp_remote_post($requestUri, array(
				"headers"		=> array('Accept-Encoding' => 'identity'),
				"body"			=> $params,
				"redirection"	=> "0",
				"timeout"		=> 180, // we look into anything that takes longer than 2 seconds to return - We had to increase the timeout to support queries with larger amount of data
				"reject_unsafe_urls" => false,
				"blocking"=> $blocking
			));
			if (empty($response["errors"]) && isset($response["response"]) && substr($response["response"]["code"], 0, 1) != "5") {
				$response["body"] = self::FilterData($response["body"]);
				if ($response["body"]){
					if ($cacheSecondsOverride !== 0 && (!isset($options['DisableLocalCaching']) || $options['DisableLocalCaching'] != 'true'))
						set_transient($transientKey, $compressCache ? base64_encode(gzdeflate(serialize($response))) : $response, $cacheSecondsOverride === null ? self::$CacheSeconds : $cacheSecondsOverride);
					else
						delete_transient($transientKey);
				}
				
				$response["body"] = self::ExtractAndEnqueueStyles($response["body"], $echoAssetsIfNotEnqueued);
				$response["body"] = self::ExtractAndEnqueueScripts($response["body"], $echoAssetsIfNotEnqueued);
			}
		}
		return $response;
	}
	public static function FilterData($data) {
		global $wp_version;

		$blog_url = get_home_url();
    if (strpos($blog_url, 'https://') !== false)
      $blogUrlWithoutProtocol = str_replace("https://", "", $blog_url);
    else 
		  $blogUrlWithoutProtocol = str_replace("http://", "", $blog_url);
		$blogUrlDirIndex = strpos($blogUrlWithoutProtocol, "/");
		$blogUrlDir = "";
		if ($blogUrlDirIndex) // don't need to check for !== false here since WP prevents trailing /'s
			$blogUrlDir = substr($blogUrlWithoutProtocol, strpos($blogUrlWithoutProtocol, "/"));

		$idxActivationPath = $blogUrlDir . "/" . dsSearchAgent_Rewrite::GetUrlSlug();

		$dsidxpress_options = get_option(DSIDXPRESS_OPTION_NAME);
		$dsidxpress_option_keys_to_output = array("ResultsDefaultState", "ResultsMapDefaultState", "ResultsDefaultStateModernView");
		$dsidxpress_options_to_output = array();

		if(!empty($dsidxpress_options)){
			foreach($dsidxpress_options as $key => $value)
			{
				if(in_array($key, $dsidxpress_option_keys_to_output))
					$dsidxpress_options_to_output[$key] = $value;
			}
		}

		$data = str_replace('{$pluginUrlPath}', self::MakePluginsUrlRelative(DSIDXPRESS_PLUGIN_URL), $data);
		$data = str_replace('{$pluginVersion}', DSIDXPRESS_PLUGIN_VERSION, $data);
		$data = str_replace('{$wordpressVersion}', $wp_version, $data);
		$data = str_replace('{$wordpressBlogUrl}', $blog_url, $data);
		$data = str_replace('{$wordpressBlogUrlEncoded}', urlencode($blog_url), $data);
		$data = str_replace('{$wpOptions}', json_encode($dsidxpress_options_to_output), $data);

		$data = str_replace('{$idxActivationPath}', $idxActivationPath, $data);
		$data = str_replace('{$idxActivationPathEncoded}', urlencode($idxActivationPath), $data);

		return $data;
	}
	public static function MakePluginsUrlRelative($url){
		$urlParts = explode($_SERVER['HTTP_HOST'], $url);
		foreach($urlParts as $p){
			if(stristr($p, '/' . strtolower(DSIDXPRESS_PLUGIN_NAME))){
				return $p;
			}
		}
		return $url;
	}
	public static function ExtractAndEnqueueStyles($data, $echoAssetsIfNotEnqueued) {
		// since we 100% control the data coming from the API, we can set up a regex to look for what we need. regex
		// is never ever ideal to parse html, but since neither wordpress nor php have a HTML parser built in at the
		// time of this writing, we don't really have another choice. in other words, this is super delicate!

		if(isset($data) && !empty($data) && is_string($data)) {
			preg_match_all('/<link\s*rel="stylesheet"\s*type="text\/css"\s*href="(?P<href>[^"]+)"\s*data-handle="(?P<handle>[^"]+)"\s*\/>/', $data, $styles, PREG_SET_ORDER);
			foreach ($styles as $style) {
				if (!$echoAssetsIfNotEnqueued || ($echoAssetsIfNotEnqueued && wp_style_is($style["handle"], 'registered')))
					$data = str_replace($style[0], "", $data);
	
				if ($echoAssetsIfNotEnqueued)
					wp_register_style($style["handle"], $style["href"], false, DSIDXPRESS_PLUGIN_VERSION);
				else
					wp_enqueue_style($style["handle"], $style["href"], false, DSIDXPRESS_PLUGIN_VERSION);
			}
		}

		return $data;
	}
	public static function ExtractAndEnqueueScripts($data) {
		// see comment in ExtractAndEnqueueStyles

		global $wp_scripts;

		if(isset($data) && !empty($data) && is_string($data)) {
			preg_match_all('/<script\s*src="(?P<src>[^"]+)"\s*data-handle="(?P<handle>[^"]+)"><\/script>/', $data, $scripts, PREG_SET_ORDER);
			foreach ($scripts as $script) {
				if(preg_match('/maps.google.com/i', $script["src"]) || preg_match('/maps.googleapis.com/i', $script["src"])){
					$parsedUrl = parse_url($script["src"]);
					$separator = (array_key_exists("query", $parsedUrl) && $parsedUrl['query'] != NULL) ? "&" : "?";
	
					$account_options = dsSearchAgent_GlobalData::GetAccountOptions(false);
	
					if(isset($account_options->{'GoogleMapsAPIKey'}) && !empty($account_options->{'GoogleMapsAPIKey'})){
						$script["src"] .= $separator . 'key='.$account_options->{'GoogleMapsAPIKey'};
					}
				}
				$alreadyIncluded = wp_script_is($script['handle']);
				if (!$alreadyIncluded) {
					wp_register_script($script["handle"], $script["src"], array('jquery'), DSIDXPRESS_PLUGIN_VERSION);
					wp_enqueue_script($script["handle"]);				
				}
				$data = str_replace($script[0], "", $data);
			}			
		}

		return $data;
	}

	public static function ProcessResponseBody($response,$criteria)
	{
	
		$cacheSecondsOverride = null;
		$re = '/%%ds_.*?\|(.*?)\|ds_end%%/';
		$re_Actions = '/%%ds_(.*?)\|/';	
		$matches = array();
		$actions = array();
		preg_match_all($re, $criteria, $matches);
		preg_match($re_Actions, $matches[0][0], $actions);
		$action = $actions[1];
		if($action=="RecordVisit")	// No need to echo response of record visit
			return;
		$apiParams = $matches[1];	
		$apiParams = stripslashes(json_encode($apiParams[0]));
		$apiParams = substr($apiParams,1,strlen($apiParams)-2);
		$apiParams = json_decode($apiParams,true);
		$apiParams["requester.RequesterUri"] = get_home_url();
		unset($apiParams["requester.UrlReferrer"]);
		unset($apiParams["requester.UtcRequestDate"]);
		unset($apiParams["requester.PaginationUseActivationPath"]);
		unset($apiParams["requester.Signature"]);
		unset($apiParams["requester.ClientIpAddress"]);
		unset($apiParams["requester.ClientUserAgent"]);
		$transientKey = "idx_" . sha1($action . "_" . http_build_query($apiParams));
		$response =	 self::FilterData($response);
		if ($cacheSecondsOverride !== 0) {
				$compressCache = function_exists('gzdeflate') && function_exists('gzinflate');
				set_transient($transientKey, $compressCache ? base64_encode(gzdeflate(serialize($response))) : $response, $cacheSecondsOverride === null ? self::$CacheSeconds : $cacheSecondsOverride);
		}
		else
				 delete_transient($transientKey);	
		$response  = self::ExtractAndEnqueueStyles($response , false);
		$response  = self::ExtractAndEnqueueScripts(	$response,false);
		return $response;
	}

}
?>