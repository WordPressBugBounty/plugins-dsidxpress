<?php
class dsSearchAgent_ApiMultiRequest {
	public static function GetBufferData($buffer) { 
		$re = '/%%ds_.*?\|(.*?)\|ds_end%%/';
		$matches = array();
		$re_Actions = '/%%ds_(.*?)\|/';	
		$aValidPosts = array();
		$actionsArray = array();
		$tempAction = array();
		$timeout = 30;
		$now = new DateTime();
		preg_match_all($re, $buffer, $matches);
		$matchCount = sizeof($matches[0]);			
		if($matchCount>=1)
		{
			
			for($actionCount=0;$actionCount<$matchCount;$actionCount++)
			{
				preg_match($re_Actions, $matches[0][$actionCount], $tempAction);		
				array_push($actionsArray,$tempAction[1]);				
			}				
		}

		// WP 6.2 support: Requests/Response classes has been replaced into the another namespace in the core
		if ( class_exists('\WpOrg\Requests\Requests') ) {
			$requests_class = '\WpOrg\Requests\Requests';
			$response_class = '\WpOrg\Requests\Response';
		} else {
			$requests_class = '\Requests';
			$response_class = '\Requests_Response';
		}

		$i = 0;
		/*Since SSL certificate on ENV02 is not valid so curl will throw exception if we use https url 
		so for initial testing we are using http url , it will be replaced later with proper url 
		 */
		foreach ($matches[1] as $parameters){
			if(isset($actionsArray[$i])) {
				$aPost[0] = array(
					"url"		=> "http://api-idx.diversesolutions.com/api/".$actionsArray[$i],
					"headers"	=> array('Accept-Encoding' => 'identity'),
					"data"		=> json_decode($parameters, true),
					"type"		=> $requests_class::POST
				);
				$aValidPosts[$i] = $aPost[0];
			}
			$i++;
		}
		$responses = $requests_class::request_multiple($aValidPosts, array("timeout" => $timeout, "redirects" => 0) );
		$responsesCount = count($responses);
		if($responsesCount>0) { 
			for ($i = 0; $i <= $responsesCount - 1; $i++) {
			$body='';
			$criteria = $matches[0][$i];

				if ( is_a( $responses[$i], $response_class ) ) {
						if ( (!empty($responses[$i]->status_code) && $responses[$i]->status_code === 200) ) {
							$body= dsSearchAgent_ApiRequest::ProcessResponseBody($responses[$i]->body,$criteria);
						} else if (!empty($responses[$i]->status_code) && $responses[$i]->status_code === 403) {
							$body= "<p class='dsidx-error'>".DSIDXPRESS_INACTIVE_ACCOUNT_MESSAGE."</p>";
						}	else if (!empty($responses[$i]->status_code) && $responses[$i]->status_code === 404) {
							$responseContent= $responses[$i]->body;
							var_dump($responses[$i]);
							if(isset($responseContent)){
								if(substr($responseContent, 0, 10) === '%%MLSMSG%%') 
										$responseContent = substr($responseContent, 10);
								else 
										$responseContent= 'Not found';					
							}							
							$body= "<p class='dsidx-error'>".$responseContent."</p>";					
						}	else {
							$body=  "<p class='dsidx-error'>".DSIDXPRESS_IDX_ERROR_MESSAGE."</p>";							
						}

						$body = isset($body) ? $body : '';
						$buffer = str_replace($criteria,$body,$buffer);
				}
			}	
			$buffer = dsSearchAgent_ApiRequest::FilterData($buffer);
			
			$pos = strpos($buffer,"</head>");
			if(isset($pos) && !empty($pos)) {
				$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("EnqueueGlobalAssets", array(), false, 3600);
				$ajaxHandler = "<script type='text/javascript'>var dsidxAjaxHandler = {'ajaxurl':'" . admin_url( 'admin-ajax.php' ) . "'};</script>";
				$final  = $apiHttpResponse["body"].$ajaxHandler;
				$buffer= substr_replace( $buffer, $final, $pos, 0 ); 
			}
		}		
		return $buffer;	
	}
	
    public static function EnableMultiRequest(){
		global $wp_version;
		if ( is_admin() || current_user_can( 'edit_posts' ) ) 
			return;
        if (version_compare($wp_version, '4.5.0') !== -1) {
            if (!defined("DS_REQUEST_MULTI_AVAILABLE")) 
                define("DS_REQUEST_MULTI_AVAILABLE", true);
        } else {
            if (defined("DS_REQUEST_MULTI_AVAILABLE")) 
                define("DS_REQUEST_MULTI_AVAILABLE", false);
        }
        if (DS_REQUEST_MULTI_AVAILABLE === true) {
            ob_start("dsSearchAgent_ApiMultiRequest::GetBufferData");
            $wp_ob_end_flush_all = has_filter( 'shutdown', 'wp_ob_end_flush_all' );
            if ($wp_ob_end_flush_all === false) {
                add_action( 'shutdown', 'dsIDXShutDown',0 );
            } else {               
                remove_action('shutdown', 'wp_ob_end_flush_all', $wp_ob_end_flush_all);
                add_action( 'shutdown', 'wp_ob_end_flush_all', $wp_ob_end_flush_all + 1 );                
                add_action( 'shutdown', 'dsIDXShutDown',0 );
            }             
        }          
	}

	
}
?>