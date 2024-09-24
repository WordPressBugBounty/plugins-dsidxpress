<?php
class dsSearchAgent_Roles {
	static $Role_Name = "dsidxpress_visitor";
	static $Role_ViewDetails = "dsidxpress_view_details";
	static function Init() {
		$options = get_option(DSIDXPRESS_OPTION_NAME);
		
		if(!empty($options["DetailsRequiresRegistration"]) && $options["DetailsRequiresRegistration"]){
			$visitor_role = get_role(dsSearchAgent_Roles::$Role_Name);
			
			if(true || $visitor_role == FALSE){
				global $wp_roles;
				$wp_roles->add_role(dsSearchAgent_Roles::$Role_Name, "dsIDXpress Visitor", array());

				$wp_roles->add_cap(dsSearchAgent_Roles::$Role_Name, dsSearchAgent_Roles::$Role_ViewDetails);
				$wp_roles->add_cap(dsSearchAgent_Roles::$Role_Name, 'read');

				$wp_roles->add_cap('administrator', dsSearchAgent_Roles::$Role_ViewDetails);
				$wp_roles->add_cap('editor', dsSearchAgent_Roles::$Role_ViewDetails);
				$wp_roles->add_cap('author', dsSearchAgent_Roles::$Role_ViewDetails);
				$wp_roles->add_cap('contributor', dsSearchAgent_Roles::$Role_ViewDetails);
				$wp_roles->add_cap('subscriber', dsSearchAgent_Roles::$Role_ViewDetails);
			}
			
			add_action("user_register", array("dsSearchAgent_Roles", "ProcessNewUser"));
		}
	}
	
	static function ProcessNewUser($user_id){		
		if (sanitize_text_field($_POST["dsidxpress"]) != "1")
			return;
			
		$new_user = new WP_User($user_id);
		$new_user->add_role(dsSearchAgent_Roles::$Role_Name);
		
		$propertyId= 0; 
		if(isset($_POST["propertyID"])) {
			$propertyId = sanitize_text_field($_POST["propertyID"]);
			if(!ctype_digit($propertyId)) {
				$propertyId =0;
			}
		}

		$referring_url = esc_url_raw($_SERVER['HTTP_REFERER']);
		$post_vars = array();
		$post_vars["propertyID"] = $propertyId;
		$post_vars["firstName"] = (isset($_POST["first_name"])? sanitize_text_field($_POST["first_name"]):"");
		$post_vars["lastName"] = (isset($_POST["last_name"])? sanitize_text_field($_POST["last_name"]):"");
		$post_vars["phoneNumber"] =(isset($_POST["phone_number"])? sanitize_text_field($_POST["phone_number"]):""); 
		$post_vars["emailAddress"] = (isset($_POST["user_email"])? sanitize_email($_POST["user_email"]):"");
		$post_vars["scheduleYesNo"] = "";
		$post_vars["scheduleDateDay"] = "1";
		$post_vars["scheduleDateMonth"] = "1";
		$post_vars["comments"] = "";
		$post_vars["referringURL"] = $referring_url;
		
		$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("ContactForm", $post_vars, false, 0);
		
		wp_set_auth_cookie( $user_id, true, is_ssl() );
	}
}

dsSearchAgent_Roles::Init();
?>