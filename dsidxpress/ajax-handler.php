<?php
add_action( 'wp_ajax_dsidx_client_assist', array('dsSearchAgent_AjaxHandler', 'handleAjaxRequest') );
add_action( 'wp_ajax_nopriv_dsidx_client_assist', array('dsSearchAgent_AjaxHandler', 'handleAjaxRequest') );
add_action( 'init', array('dsSearchAgent_AjaxHandler', 'localizeScripts') );

class dsSearchAgent_AjaxHandler {
	static public function handleAjaxRequest(){
		if(!empty($_REQUEST['dsidx_action'])){
			$action = sanitize_text_field($_REQUEST['dsidx_action']);
			if(!empty($action)) {
				dsSearchAgent_AjaxHandler::call($action);
			}
			else{
				wp_die();
			}
		}
		else{
			wp_die();
		}
	}
    static function call($method){ 
        if(method_exists('dsSearchAgent_AjaxHandler', $method)) { 
			call_user_func(array('dsSearchAgent_AjaxHandler', $method));
        }else{ 
        	die();
        } 
    } 
    static function localizeScripts(){
		wp_localize_script( 'dsidx', 'dsidxAjaxHandler', array('ajaxurl'=>admin_url( 'admin-ajax.php' )) );
	}
	static function SlideshowXml(){
		$uriSuffix = '';
		if (array_key_exists('uriSuffix', $_GET)) {
			$uriSuffix =sanitize_text_field( $_GET['uriSuffix']);
		}

		if (isset($_GET['uriBase'])) {
			$urlBase  = sanitize_text_field($_GET['uriBase']);
		}
	
    if (!preg_match("/^https:\/\//", $urlBase))
      $urlBase = "https://" . $urlBase;
		else if (!preg_match("/^http:\/\//", $urlBase))
			$urlBase = "http://" . $urlBase;
		$urlBase = esc_url(str_replace(array('&', '"'), array('&amp;', '&quot;'), $urlBase));

		header('Content-Type: text/xml');
		echo '<?xml version="1.0"?><gallery><album lgpath="' . esc_attr($urlBase) . '" tnpath="' . esc_attr($urlBase) . '">';
		if (isset($_GET['count'])) {
			$count  = sanitize_text_field($_GET['count']);
		}

		for($i = 0; $i < (int)$count; $i++) {
			echo '<img src="' . esc_attr($i . '-full.jpg' . $uriSuffix) . '" tn="' . esc_attr($i . '-medium.jpg' . $uriSuffix) . '" link="javascript:dsidx.details.LaunchLargePhoto('. esc_attr($i .','. $count .',\''. $urlBase .'\',\''. $uriSuffix) .'\')" target="_blank" />';
		}
		echo '</album></gallery>';
		exit;
	}
	static function SlideshowParams(){
		if (isset($_GET['count'])) {
			$count = sanitize_text_field($_GET['count']);
		}

		if (isset($_GET['uriSuffix'])) {
			$uriSuffix = sanitize_text_field($_GET['uriSuffix']);
		}

		if (isset($_GET['uriBase'])) {
			$uriBase = sanitize_text_field($_GET['uriBase']);
		}

		$slideshow_xml_url = admin_url( 'admin-ajax.php' )."?action=dsidx_client_assist&dsidx_action=SlideshowXml&count=$count&uriSuffix=$uriSuffix&uriBase=$uriBase";
		$param_xml = file_get_contents(plugin_dir_path(__FILE__).'assets/slideshowpro-generic-params.xml');
		$param_xml = str_replace("{xmlFilePath}", esc_url($slideshow_xml_url), $param_xml);
		$param_xml = str_replace("{imageTitle}", "", $param_xml);

		header('Content-Type: text/xml');
		echo($param_xml);
		exit;
	}
	static function EmailFriendForm(){
		$referring_url = esc_url_raw($_SERVER['HTTP_REFERER']);
		$action = "";
		$propertyID = 0;
		$yourEmail ="";
		$friendsEmail =  "";
		$note = "";
		$captchaAnswer = "";
		$dsidx_action = ""; 

		if(isset($_POST["action"])) {
			 $action =  sanitize_text_field($_POST["action"]);
		}

		if(isset($_POST["propertyID"]) && ctype_digit($_POST["propertyID"])) {
			$propertyID = sanitize_text_field($_POST["propertyID"]);
		}

		if(!isset($_POST["yourEmail"]) || !is_email($_POST['yourEmail'])) {
				header('Content-type: application/json');
				echo '{ "Error": true, "Message": "YOUR EMAIL IS INVALID" }';
				die();
		} else {
				$yourEmail = sanitize_email($_POST["yourEmail"]);				
		}

		if(!isset($_POST["friendsEmail"]) || !is_email($_POST['friendsEmail'])) {
			header('Content-type: application/json');
			echo '{ "Error": true, "Message": "FRIEND\'S EMAIL IS INVALID" }';
			die();
		} else {
				$friendsEmail = sanitize_email($_POST["friendsEmail"]);				
		}
		
		if(isset($_POST["note"])) {
			$note = sanitize_textarea_field($_POST["note"]);
		}
		
		if(isset($_POST["captchaAnswer"]) &&  ctype_digit($_POST["captchaAnswer"])) {
			$captchaAnswer =	sanitize_text_field($_POST["captchaAnswer"]);
		}

		if(isset($_POST["dsidx_action"])) {
			$dsidx_action = sanitize_text_field($_POST["dsidx_action"]);
		}

		$post_vars = array(
				'action' => $action,
				'propertyID' => $propertyID,
				'yourEmail' => $yourEmail,
				'friendsEmail' => $friendsEmail,
				'note' => $note,
				'captchaAnswer' => $captchaAnswer,		
				'dsidx_action' => $dsidx_action
			);
	
		$post_vars["referringURL"] = $referring_url;

		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("EmailFriendForm", $post_vars, false, 0);

		echo $apiHttpResponse["body"];
		die();
	}
	static function LoginRecovery(){
		global $curent_site, $current_blog, $blog_id;
		
		$referring_url = esc_url_raw($_SERVER['HTTP_REFERER']);
		if(!isset($_POST["emailAddress"]) || !is_email($_POST['emailAddress'])) {
			header('Content-type: application/json');
			echo '{ "Error": true, "Message": "You must use a valid email address" }';
			die();
		} else {
				$emailAddress = sanitize_email($_POST["emailAddress"]);				
		}

		
		$post_vars	= array(
		 		'emailAddress' => $emailAddress
		);
		$post_vars["referringURL"] = $referring_url;
		$post_vars["domain"] = $current_blog->domain;
		$post_vars["path"] = $current_blog->path;
		
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("LoginRecovery", $post_vars, false, 0);
		
		echo $apiHttpResponse["body"];
		die();
	}
	static function ResetPassword(){
		$referring_url = esc_url_raw($_SERVER['HTTP_REFERER']);
		$passwordReset_Referral = esc_url_raw($_POST['passwordReset.Referral']);
		$passwordReset_DomainName = esc_url_raw($_POST['passwordReset.DomainName']);
		$password = sanitize_text_field($_POST['password']);
		$confirmpassword = sanitize_text_field($_POST['confirmpassword']);
		$resetToken = sanitize_text_field($_POST['resetToken']);	
		
		$post_vars = array(
			'passwordReset.Referral' => 	$passwordReset_Referral,
			'passwordReset.DomainName' => $passwordReset_DomainName,
			'password' => $password,
			'confirmpassword' => $confirmpassword,
			'resetToken' => $resetToken,
			'referringURL' => $referring_url			
			);

		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("ResetPassword", $post_vars, false, 0);
		
		echo $apiHttpResponse["body"];
		die();	
	}
	static function ContactForm(){
		$referring_url = @$_SERVER['HTTP_REFERER'];
		
		$post_vars = array();	
		$referring_url =esc_url_raw($referring_url);

		foreach($_POST as $key => $value) {
			$pKey = sanitize_key($key); 
			if($pKey) {
						$pKey = strtolower($pKey);
						if(strpos($pKey, 'email') !== false) {
							$santizedValue = sanitize_email($value);
						}  else if(strpos($pKey, 'comments') !== false) {
							$santizedValue = sanitize_textarea_field($value);
						} 
						else {
							$santizedValue = sanitize_text_field($value);
						}	
						$post_vars[$key] = $santizedValue;
				}
			}
		
		$dsidx_action = isset($post_vars['dsidx_action']) ? $post_vars['dsidx_action'] : '';
		$name = isset($post_vars['name']) ? $post_vars['name'] : '';
		$firstName = isset($post_vars['firstName']) ? $post_vars['firstName'] : '';
		$lastName = isset($post_vars['lastName']) ? $post_vars['lastName'] : '';
		$emailAddress = isset($post_vars['emailAddress']) ? $post_vars['emailAddress'] : '';
		$phoneNumber = isset($post_vars['phoneNumber']) ? $post_vars['phoneNumber'] : '';
		$scheduleYesNo = isset($post_vars['scheduleYesNo']) ? $post_vars['scheduleYesNo'] : '';
		$scheduleDateMonth = isset($post_vars['scheduleDateMonth']) ? $post_vars['scheduleDateMonth'] : '';
		$scheduleDateDay = isset($post_vars['scheduleDateDay']) ? $post_vars['scheduleDateDay'] : '';
		$propertyStreetAddress = isset($post_vars['propertyStreetAddress']) ? $post_vars['propertyStreetAddress'] : '';
		$propertyCity = isset($post_vars['propertyCity']) ? $post_vars['propertyCity'] : '';
		$propertyZip = isset($post_vars['propertyZip']) ? $post_vars['propertyZip'] : '';
		$propertyState = isset($post_vars['propertyState']) ? $post_vars['propertyState'] : '';
		$returnToReferrer = isset($post_vars['returnToReferrer']) ? $post_vars['returnToReferrer'] : '';
		$propertyID = isset($post_vars['propertyID']) ? $post_vars['propertyID'] : '';
		$PackageTypeID = isset($post_vars['PackageTypeID']) ? $post_vars['PackageTypeID'] : '';

		if($dsidx_action !== "ContactForm") {
			header('Content-type: application/json');
			echo '{ "Error": true, "Message": "Failed to submit." }';
			die();
		}
		
		if(isset($propertyID) && !empty($PackageTypeID) && !ctype_digit($propertyID)) {
			header('Content-type: application/json');
			echo '{ "Error": true, "Message": "Failed to submit." }';
			die();
		}

		if(isset($PackageTypeID) && !empty($PackageTypeID) && !ctype_digit($PackageTypeID)) {
			header('Content-type: application/json');
			echo '{ "Error": true, "Message": "Failed to submit." }';
			die();
		}
		
		//Fix up post vars for Beast ContactForm API
		if (isset($name) && !isset($firstName)) {
			if(empty($name) || !is_email($emailAddress)){
					header('Content-type: application/json');
					echo '{ "Error": true, "Message": "Failed to submit." }';
					die();
	      }
			
			$name_split = preg_split('/[\s]+/', 	$name, 2, PREG_SPLIT_NO_EMPTY);
			$firstName = count($name_split) > 0 ? $name_split[0] : '';
			$lastName = count($name_split) > 1 ? $name_split[1] : '';
		}
		if (isset($firstName) && !isset($name)) {
			if(empty($firstName) || empty($lastName) || !is_email($emailAddress)){
					header('Content-type: application/json');
					echo '{ "Error": true, "Message": "Failed to submit." }';
					die();
	      }
		}
		
		if (!isset($phoneNumber)) {
			$phoneNumber = '';
		}
		
		$message = (!empty($scheduleYesNo) && $scheduleYesNo == 'on' ? "Schedule showing on {$scheduleDateMonth} / {$scheduleDateDay} " : "Request info ") . 
						@"for ".(!empty($propertyStreetAddress) ? $propertyStreetAddress:"")." ".(!empty($propertyCity) ? $propertyCity : "").", 
						".(!empty($propertyState) ? $propertyState : "")." ".(!empty($propertyZip) ? $propertyZip : "").
						@". ".$post_vars['comments'];

		
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("ContactForm", $post_vars, false, 0);
		

		if (false && $returnToReferrer == "1") {
			$post_response = json_decode($apiHttpResponse["body"]);

			if ($post_response->Error == 1)
				$redirect_url = $referring_url .'?dsformerror='. $post_response->Message;
			else
				$redirect_url = $referring_url;

			header( 'Location: '. $redirect_url ) ;
			die();
		} else {
			echo $apiHttpResponse["body"];
			die();
		}
		header('Content-type: application/json');
		echo '{ "Error": false, "Message": "" }';
		die();
	}

	static function PrintListing(){
		
		if (isset($_REQUEST['PropertyID'])) {
			$propertyID = sanitize_text_field($_REQUEST['PropertyID']);
			if($propertyID) {
				$apiParams["query.PropertyID"] = $propertyID;
			}			
		}
		
		if(isset($_REQUEST["MlsNumber"])){
			$mlsNumber = sanitize_text_field($_REQUEST['MlsNumber']);
			if($mlsNumber) {
				$apiParams["query.MlsNumber"] = $mlsNumber;
			}
			
		}
		$apiParams["responseDirective.ViewNameSuffix"] = "print";
		$apiParams["responseDirective.IncludeDisclaimer"] = "true";
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Details", $apiParams, false);

		header('Cache-control: private');
		header('Pragma: private');
		header('X-Robots-Tag: noindex');

		echo($apiHttpResponse["body"]);
		die();
	}
	static function OnBoard_GetAccessToken(){
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("OnBoard_GetAccessToken");
		echo $apiHttpResponse["body"];
		die();
	}
	static function Login(){
		if(isset($_POST["email"])) {
			$email = sanitize_email($_POST["email"]);	
		}
		
		if(!$email || !is_email($email)) {
			header('Content-type: application/json');
			echo '{ "Error": true, "Message": "Invalid email or password." }';
			die();
		}

		if(isset($_POST["password"])) {
			$password =  sanitize_text_field($_POST["password"]);
		}
		
		if(!$password) {
			header('Content-type: application/json');
			echo '{ "Error": true, "Message": "Invalid email or password." }';
			die();
		}
		
		if(isset($_POST["remember"])) {
			$rememberOption =  sanitize_text_field($_POST["remember"]);
		}
		
	 	$post_vars = array(
		'email' => $email,
		'password' => $password
		);

		
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Login", $post_vars, false, 0);

		$response = json_decode($apiHttpResponse["body"]);
		
		if($response->Success){		
				
			$remember = !empty($rememberOption) && $rememberOption == "on" ? time()+60*60*24*30 : 0;
			
			setcookie('dsidx-visitor-public-id', $response->Visitor->PublicID, $remember, '/');
			setcookie('dsidx-visitor-auth', $response->Visitor->Auth, $remember, '/');
		}

		echo $apiHttpResponse["body"];
		die();
	}
	/* Removed validate logout , will need to verify if it is being used anywhere */
	static function Logout(){
		if(isset($_GET["action"])) {
			$action =  sanitize_text_field($_GET["action"]);
		 }
		 
		 if(isset($_GET["dsidx_action"])) {
			$dsidx_action =  sanitize_text_field($_GET["dsidx_action"]);
		 }
		 
		 if(isset($_GET["checkExpiration"])) {
			$checkExpiration =  sanitize_text_field($_GET["checkExpiration"]);
			}
			
			$post_vars = array(
				'action' => $action,
				'dsidx_action' => $dsidx_action,
				'checkExpiration' => $checkExpiration
				);

		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Logout", $post_vars, false, 0);
		echo $apiHttpResponse["body"];
		die();
	}
	static function LoginOrRegister(){
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("LoginOrRegister", array(), 	false, 0);
		echo $apiHttpResponse["body"];
		die();
	}
	static function GetVisitor(){		
		if(isset($_POST["email"])) {
			$email =  sanitize_email($_POST["email"]);
			if($email && is_email($email)) {
				$post_vars = array(
					'email' => $email
					);
				$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("GetVisitor", $post_vars, false, 0);
				echo $apiHttpResponse["body"];
			}
	 	}		
		die();
	}
	static function isOptIn(){
		$post_vars = array();
		foreach($_GET as $key => $value) {
			$pKey = sanitize_key($key); 
			if($pKey) {
				$pKey = strtolower($pKey);
				if(strpos($pKey, 'email') !== false) {
					$santizedValue = sanitize_email($value);
				} else {
					$santizedValue = sanitize_text_field($value);
				}
				$post_vars[$pKey] = $santizedValue;
			}
		}
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("isOptIn", $post_vars, false, 0, null);
		echo $apiHttpResponse["body"];
		die();	
	}
	static function SsoAuthenticated (){
		$post_vars = array();
		foreach($_GET as $key => $value) {
			$pKey = sanitize_key($key); 
			if($pKey) {
				$pKey = strtolower($pKey);
				if(strpos($pKey, 'email') !== false) {
					$santizedValue = sanitize_email($value);
				} else {
					$santizedValue = sanitize_text_field($value);
				}
				$post_vars[$pKey] = $santizedValue;
			}
		}
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("SSOAuthenticated", $post_vars, false, 0, null);
		$response = json_decode($apiHttpResponse["body"]);
		
		if($response->Success){			
			$remember = !empty($_POST["remember"]) && $_POST["remember"] == "on" ? time()+60*60*24*30 : 0;
			
			setcookie('dsidx-visitor-public-id', $response->Visitor->PublicID, $remember, '/');
			setcookie('dsidx-visitor-auth', $response->Visitor->Auth, $remember, '/');
		} else {
			if (isset($_COOKIE['dsidx-visitor-auth']) && sanitize_text_field($_COOKIE['dsidx-visitor-auth'] != '')) {
				// This means the user is no longer logged in globally.
				// So log out of the current session by removing the cookie.
				setcookie('dsidx-visitor-public-id', '', time()-60*60*24*30, '/');
				setcookie('dsidx-visitor-auth', '', time()-60*60*24*30, '/');
			}
		}

		header('Location: ' . $response->Origin);
	}

	static function Register(){
		foreach($_POST as $key => $value) {
			$pKey = sanitize_key($key); 
			if($pKey) {
				$pKey = strtolower($pKey);
				if(strpos($pKey, 'email') !== false) {
					$santizedValue = sanitize_email($value);
				} else if(
							(strpos($pKey, 'referral') !== false) || 
							(strpos($pKey, 'listingurl') !== false)
						) {
							$santizedValue = esc_url_raw($value);
				} else if( 
									(strpos($pKey, 'packagetypeid') !== false) ||
									(strpos($pKey, 'mlsnumber') !== false)
								){
								$santizedValue = $value;
				} else if(strpos($pKey, 'phonenumber') !== false) {
					$santizedValue = sanitize_text_field($value);
					$temp = preg_replace("/[^0-9]/", "",	$santizedValue );
					if(strlen($temp) !== 10) {
						header('Content-type: application/json');
						echo '{ "Error": true, "Message": "Invalid Phone Number." }';
						die();
					}
				} 
				else {
					$santizedValue = sanitize_text_field($value);
				}	
				$post_vars[str_replace('newVisitor_', 'newVisitor.', $key)] = $santizedValue;
			}
		}

	
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Register", $post_vars, false, 0);

		$response = json_decode($apiHttpResponse["body"]);
		
		if($response->Success){		
			$remember =  isset($_POST["remember"]) ? sanitize_text_field($_POST["remember"]) : '';
			$remember = $remember == "on" ? time()+60*60*24*30 : 0;			
			setcookie('dsidx-visitor-public-id', $response->Visitor->PublicID, $remember, '/');
			setcookie('dsidx-visitor-auth', $response->Visitor->Auth, $remember, '/');		
		}

		echo $apiHttpResponse["body"];
		die();
	}
	static function UpdatePersonalInfo(){
		foreach($_POST as $key => $value) { 
			$pKey = sanitize_key($key);
			if($pKey) {
				if(strpos($pKey, 'Email') !== false) {
					$santizedValue = sanitize_email($value);
				} else 	if(strpos($pKey, 'EmailUpdateType') !== false) {
					if(ctype_digit($value)) {
						$santizedValue = $value;
					}					
				} else {
					$santizedValue = sanitize_text_field($value);
				}	

				$post_vars[str_replace('personalInfo_', 'personalInfo.', $key)] = $santizedValue;
			}
		}
	
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("UpdatePersonalInfo", $post_vars, false, 0);
		echo $apiHttpResponse["body"];
		die();
	}
	static function Searches(){				
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Searches", null, false, 0);
		echo $apiHttpResponse["body"];
		die();
	}

	static function UpdateSavedSearchTitle(){
		if(isset($_POST["propertySearchID"]) && ctype_digit($_POST["propertySearchID"])) {
			$propertySearchID =  sanitize_text_field($_POST["propertySearchID"]);
		}
		 
		if(isset($_POST["propertySearchTitle"])) {
			$propertySearchTitle =  sanitize_text_field($_POST["propertySearchTitle"]);
		}

		$post_vars = array(
			'propertySearchID' => $propertySearchID,
			'propertySearchTitle' => $propertySearchTitle
			);

		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("UpdateSavedSearchTitle", $post_vars, false, 0);

		$response = json_decode($apiHttpResponse["body"]);
				
		header('Content-Type: application/json');
		echo $apiHttpResponse["body"];
		die();
	}

	static function ToggleSearchAlert(){
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("ToggleSearchAlert", $_POST, false, 0);
		echo $apiHttpResponse["body"];
		die();
	}
	static function DeleteSearch(){		
		if(isset($_POST["propertySearchID"]) && ctype_digit($_POST["propertySearchID"])) {
			$propertySearchID =  sanitize_text_field($_POST["propertySearchID"]);

			if($propertySearchID) {
				$post_vars = array(
					'propertySearchID' => $propertySearchID
				);
				$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("DeleteSearch", $post_vars, false, 0);		
				echo $apiHttpResponse["body"];
			}
		}		
		die();
	}
	static function FavoriteStatus(){
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("FavoriteStatus", $_POST, false, 0);
		echo $apiHttpResponse["body"];
		die();
	}
	static function Favorite(){
			$propertyId = 0; 
			if(isset($_POST["propertyId"]) && ctype_digit($_POST["propertyId"])) {
				$propertyId =  sanitize_text_field($_POST["propertyId"]);

				if($propertyId  && $propertyId != 0) {
					if(isset($_POST["favorite"])) {
						$favorite =  sanitize_text_field($_POST["favorite"]);
					}

					$post_vars = array(
						'propertyId' => $propertyId,
						'favorite' => ($favorite === 'true'? 'true': 'false')
						);
				
					$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Favorite", $post_vars, false, 0);
					
					echo $apiHttpResponse["body"];
				}	
			}
			die();
	}
	static function UpdateEmailType(){
		
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("UpdateEmailType", $_POST, false, 0);
		echo $apiHttpResponse["body"];
		die();
	}
	static function EmailAlerts(){
	
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("EmailAlerts", $_POST, false, 0);
		header('Content-Type: text/html');
		echo $apiHttpResponse["body"];
		die();
	}
	static function VisitorListings(){
		if(isset($_POST["dsidx_action"])) {
			$page =0; 
			$dsidx_action =  sanitize_text_field($_POST["dsidx_action"]);
			if($dsidx_action && $dsidx_action === 'VisitorListings') {
				
				if(isset($_POST["action"])) {
					$action =  sanitize_text_field($_POST["action"]);
				}

				if(isset($_POST["type"])) {
					$type =  sanitize_text_field($_POST["type"]);
					$type = ($type === 'visited'? 'visited': 'favorited');
				}

				if(isset($_POST["page"])) {
					$page =  sanitize_text_field($_POST["page"]);
				}

				$post_vars = array(
					'action' => $action,
					'dsidx_action' => $dsidx_action,
					'type' => $type,
					'page' => $page
					);

				$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("VisitorListings", $post_vars, false, 0);
				header('Content-Type: text/html');
				echo $apiHttpResponse["body"];
			}
		}
		die();
	}
	static function ReviewListings(){
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("ReviewListings", $_POST, false, 0);
		header('Content-Type: text/html');
		echo $apiHttpResponse["body"];
		die();
	}
	static function LoadAreasByType(){
		$minListingCount = 1;
		$dataField = null; 
		
		if (isset($_REQUEST['dsidx_action'])) {
			$dsidx_action = sanitize_text_field($_REQUEST['dsidx_action']);
		}	

		if(empty($dsidx_action) || $dsidx_action !== "LoadAreasByType") {
			header('Content-type: application/json');
			echo '{ "Error": true, "Message": "Failed To Load Data." }';
			die();
		}

		if (isset($_REQUEST['action'])) {
			$action = sanitize_text_field($_REQUEST['action']);
		}

		if (isset($_REQUEST['searchSetupID'])) {
			$searchSetupID = sanitize_text_field($_REQUEST['searchSetupID']);
		}	

		if (isset($_REQUEST['type'])) {
			$type = sanitize_text_field($_REQUEST['type']);
		}	

		if (isset($_REQUEST['minListingCount'])) {
			$minListingCount = sanitize_text_field($_REQUEST['minListingCount']);
		}	

		if (isset($_REQUEST['dataField'])) {
			$dataField = sanitize_text_field($_REQUEST['dataField']);
		}	
		
		$request_vars = array(
			'action' => $action,
			'dsidx_action' => $dsidx_action,
			'searchSetupID' => $searchSetupID,
			'type' => $type,
			'minListingCount' => $minListingCount
			);

		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("LocationsByType", $request_vars, false, 0);
		
		if(!isset($dataField)){
			echo $apiHttpResponse["body"];
		}
		else{
			$response = json_decode($apiHttpResponse["body"], true);
			$r = array();
			foreach($response as $item){
				if(isset($item[$dataField])){
					$r[] = $item[$dataField];
				}
			}
			echo json_encode($r);
		}
		die();
	}
	static function LoadSimilarListings() {
		$propertyId = 0;
		$apiParams = array();
		if(isset($_POST["PropertyID"]) && ctype_digit($_POST["PropertyID"])) {			
			$propertyId =  sanitize_text_field($_POST["PropertyID"]);
			if($propertyId && $propertyId  !==0) {
				$apiParams["query.SimilarToPropertyID"] = $propertyId;
				$apiParams["query.ListingStatuses"] = '1';
				$apiParams['responseDirective.ViewNameSuffix'] = 'Similar';
				$apiParams['responseDirective.IncludeDisclaimer'] = 'true';
				$apiParams['directive.ResultsPerPage'] = '6';
				$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Results", $apiParams, false, 0);
				echo $apiHttpResponse["body"];
			}		
		}
		die();
	}
	static function LoadSoldListings(){
		$apiParams = array();
		$propertyId = 0;
		if(isset($_POST["PropertyID"]) && ctype_digit($_POST["PropertyID"])) {	
			$propertyId =  sanitize_text_field($_POST["PropertyID"]);
			if($propertyId && $propertyId  !==0) {
				$apiParams["query.SimilarToPropertyID"] = $propertyId;
				$apiParams["query.ListingStatuses"] = '8';
				$apiParams['responseDirective.ViewNameSuffix'] = 'Sold';
				$apiParams['directive.ResultsPerPage'] = '6';
				
				$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Results", $apiParams, false, 0);
				echo $apiHttpResponse["body"];
			}
		}
		die();
	}
	static function LoadSchools() {
		$apiParams = array();
		$propertyId = 0;
		if(isset($_POST["PropertyID"]) && ctype_digit($_POST["PropertyID"])) {	
			$propertyId =  sanitize_text_field($_POST["PropertyID"]);
			if($propertyId && $propertyId  !==0) {	
				$city = sanitize_text_field($_POST["city"]);
				$state = sanitize_text_field($_POST["state"]);
				$zip = sanitize_text_field($_POST["zip"]);
				$spatial = sanitize_text_field($_POST["spatial"]);
				$apiParams['responseDirective.ViewNameSuffix'] = 'Schools';
				$apiParams['query.City'] = $city? $city: '';
				$apiParams['query.State'] = $state ? $state : '';
				$apiParams['query.Zip'] = $zip ? $zip : '';
				$apiParams['query.Spatial'] = $spatial ? $spatial : 'true';
				$apiParams['query.PropertyID'] = $propertyId;
			
				$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Schools", $apiParams, false);
				echo $apiHttpResponse["body"];
			}
		}
		die();
	}
	static function LoadDistricts() {
		$apiParams = array();
		$propertyId = 0;
		if(isset($_POST["PropertyID"]) && ctype_digit($_POST["PropertyID"])) {	
			$propertyId =  sanitize_text_field($_POST["PropertyID"]);
			if($propertyId && $propertyId  !==0) {
				$city = sanitize_text_field($_POST["city"]);
				$state = sanitize_text_field($_POST["state"]);
				$spatial = sanitize_text_field($_POST["spatial"]);

				$apiParams['responseDirective.ViewNameSuffix'] = 'Districts';
				$apiParams['query.City'] =  $city? $city: '';
				$apiParams['query.State'] = $state ? $state : '';
				$apiParams['query.Spatial'] = $spatial ? $spatial :'';
				$apiParams['query.PropertyID'] = $propertyId;
			
				$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Districts", $apiParams, false);
				echo $apiHttpResponse["body"];
			}
		}
		die();
	}
	static function AutoComplete() {
		$apiParams = array();
		if(isset($_POST["term"])) {
			$term =  sanitize_text_field($_POST["term"]);
			if($term) {
				$apiParams['query.partialLocationTerm'] =	$term ;		
				$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData('AutoCompleteOmniBox', $apiParams, false, 0);
				echo $apiHttpResponse['body'];
			}
	 	}	
		die();
	}
	static function AutoCompleteMlsNumber() {
		$apiParams = array();
		if(isset($_POST["term"])) {
			$term =  sanitize_text_field($_POST["term"]);
			if($term) {
				$apiParams['query.partialLocationTerm'] =	$term ;		
				$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData('AutoCompleteMlsNumberOmniBox', $apiParams, false, 0);
				echo $apiHttpResponse['body'];
			}
		}
		die();
	}
	
	 static function LoadDisclaimerAsync() {
		$apiParams = array();		 
		 try {
					if(isset($_POST["params"])) {
						$params =  sanitize_text_field($_POST["params"]);
						$apiParams = json_decode(stripcslashes($params),true);
						$disclaimer = dsSearchAgent_ApiRequest::FetchData("Disclaimer", $apiParams);
						if(isset($disclaimer['response']['code']) && $disclaimer['response']['code'] == '200'){
								echo $disclaimer["body"];
							}		
					}									 	
		 }
		 catch(Exception $e) {
			 var_dump($e->getMessage());
		 }
		 die();		
	}

	static function GetPhotosXML() {
		$get_vars = array();
		foreach($_GET as $key => $value) {
			$pKey = sanitize_key($key); 
			if($pKey) {
				$pKey = strtolower($pKey);
				$santizedValue = sanitize_text_field($value);
				$get_vars[$key] = $santizedValue;

			}
		}
		$post_vars = array_map("stripcslashes", $get_vars);
		$apiRequestParams = array();
		$apiRequestParams['propertyid'] = sanitize_text_field($post_vars['pid']);
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData('Photos', $apiRequestParams, false);
		echo $apiHttpResponse['body'];
		die();
	}

	//tinymce dialogs for shortcodes
	static function MultiListingsDialog(){
		require_once(DSIDXPRESS_PLUGIN_PATH.'tinymce/multi_listings/dialog.php');
		exit;
	}
	static function SingleListingDialog(){
		require_once(DSIDXPRESS_PLUGIN_PATH.'tinymce/single_listing/dialog.php');
		exit;
	}
	static function LinkBuilderDialog(){
		require_once(DSIDXPRESS_PLUGIN_PATH.'tinymce/link_builder/dialog.php');
		exit;
	}
	static function IdxQuickSearchDialog(){
		require_once(DSIDXPRESS_PLUGIN_PATH.'tinymce/idx_quick_search/dialog.php');
		exit;
	}
	static function IdxRegistartionDialog(){
		require_once(DSIDXPRESS_PLUGIN_PATH.'tinymce/idx_registration_form/dialog.php');
		exit;
	}
}