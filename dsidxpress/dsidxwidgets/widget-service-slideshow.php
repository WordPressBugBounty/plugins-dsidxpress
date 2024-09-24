<?php
class dsIDXWidgets_Slideshow extends WP_Widget {
    var $widgetsCdn;

    public function __construct() {
        global $pagenow;

        parent::__construct("dsidx-slideshow", "Property Slideshow", array(
            "classname" => "dsidx-widget-slideshow",
            "description" => "Show a slideshow of properties in a specific area"
            ));

        if ($pagenow == 'widgets.php')
            wp_enqueue_script('dsidxwidgets_widget_service_admin', DSIDXWIDGETS_PLUGIN_URL . 'js/widget-service-admin.js', array('jquery'), false, true);

        $this->widgetsCdn = dsWidgets_Service_Base::$widgets_cdn;
    }
    function widget($args, $instance) {
		if(!isset($instance) || empty($instance)) {
			return;
		}

        extract($args);
        extract($instance);

        $title = isset($title) ? esc_html($title) : '';
        $error_message = dsWidgets_Service_Base::getWidgetErrorMsg($before_widget . $before_title . $title . $after_title, $after_widget);
        if($error_message){
            echo $error_message;
            return;
        }

        $options = get_option(DSIDXWIDGETS_OPTION_NAME);
        $randString = dsWidgets_Service_Base::get_random_string('abcdefghijklmnopqrstuvwxyz1234567890', 5);
        $horzCount = esc_html($instance["horzCount"]);
        $maxPrice = esc_html($instance["maxPrice"]);
        $state = esc_html($instance["state"]);
        $city = esc_html($instance["city"]);
        $zip = esc_html($instance["zip"]);
        $modernView = isset($instance["modernView"]) && strtolower($instance["modernView"]) == "yes";
        $imagesStub = dsWidgets_Service_Base::$widgets_images_stub;
        $apiStub = dsWidgets_Service_Base::$widgets_api_stub;
        $curURL = get_home_url();

		$idxpress_options = get_option(DSIDXPRESS_OPTION_NAME);
		if(!empty($idxpress_options["AccountID"])){
			$aid = $idxpress_options["AccountID"];
		}
		else{
			$aid = $options["AccountID"];
		}
		if(!empty($idxpress_options["SearchSetupID"])){
			$ssid = $idxpress_options["SearchSetupID"];
		}
		else{
			$ssid = $options["SearchSetupID"];
		}

        echo $before_widget;
		
        if(defined('ZPRESS_API') && ZPRESS_API != ''){
            $widget_header = call_user_func('\zpress\themes\Options::GetOption', 'theme_widgets_require_header');
    		if(!empty($widget_header->meta) && $widget_header->meta == 'true'){
    			echo $before_title;
    			echo 'Slideshow';
    			echo $after_title;
    		}
        }

        if($modernView)
        {
            echo <<<HTML
            <script type="text/javascript" id="divLocal{$randString}_">
            var slideshowScriptV2, _ds_midx;
			window.slideshowHasDependencyV2 = true;
            LaunchBase{$randString} = function(){
                CreateObject{$randString} = function () { _ds_midx = { currentURL: '{$curURL}', productType: '0', curAPIStub: '{$apiStub}', curImageStub: '{$imagesStub}', targetDomain: window["zpress_widget_domain_token"],accountId: '{$aid}',searchSetupId: '{$ssid}',muteStyles: true,horzCount: '{$horzCount}',maxPrice: '{$maxPrice}',state: '{$state}',city: '{$city}',zip: '{$zip}',modernView: '{$modernView}',curDivID: 'divLocal{$randString}_',querySchema: '12dGTTViUjEzC1rrNlw6Lq6A6wZQlgBarlIcucpGTkQrUP3gCimYF6deRFaavu2IbPpaOkZ9I4K42QaAhLVEcA==' }; }
                AddJavaScriptToDOM{$randString}=function(c,d,e){if(1!=d){var a=document.createElement("script"),b=document.getElementsByTagName("script")[0];a.async=!0;a.src=c;a.onload=function(){ window[e] = 1;};b.parentNode.insertBefore(a,b)}return 1};
                CreateWidget{$randString} = function () {
                    (window.slideshowFinishedV2 == 1) ? (window["ds.widget.view.slideshowV2"].isProcessing = true, CreateObject{$randString}(), new window["ds.widget.view.slideshowV2"](_ds_midx), window["ds.widget.view.slideshowV2"].isProcessing = false, window.slideshowHasDependencyV2 = false) : window.setTimeout("CreateWidget{$randString}(false)", 20); 
                }
              if (slideshowScriptV2 != 1) { slideshowScriptV2 = AddJavaScriptToDOM{$randString}("{$this->widgetsCdn}/Scripts/PostCompile/Slideshow_v2_1.js", slideshowScriptV2, 'slideshowFinishedV2') }; 
                CreateWidget{$randString}();
            }


            GetToken{$randString}=function(){if(!window.zpress_widget_domain_token&&1!=window.zpress_widget_domain_token_progress){window.zpress_widget_domain_token_progress=1;var c=-1<navigator.userAgent.indexOf("MSIE 7.0")?!0:!1,d=-1<navigator.userAgent.indexOf("MSIE 8.0")||-1<navigator.userAgent.indexOf("MSIE 9.0")?!0:!1;if(c)rr=document.createElement("script"),rr.async=!0,rr.id="domainScript",rr.type="text/javascript",rr.src="{$apiStub}Encrypt/?targetString="+window.location.hostname+"&targetObject=domain&authType=Basic&curDomain="+
            window.location.hostname+"&objectName_=error",rr.onload=rr.onreadystatechange=function(){"undefined"!=typeof window.error?alert("We had a problem authenticating this domain"):(window.zpress_widget_domain_token=window.encrypted_domain,LaunchBase{$randString}())},document.getElementsByTagName("head")[0].appendChild(rr);else if(c="{$apiStub}Encrypt/?targetString="+window.location.hostname+"&targetObject=domain&authType=CORS&objectName_=error",d){var a=new XDomainRequest;a.onload=function(){window.zpress_widget_domain_token=
            eval(a.responseText);LaunchBase{$randString}()};a.onerror=function(){};a.onprogress=function(){};a.open("GET",c,!0);a.send(null)}else{var b;if(b=new XMLHttpRequest)b.onreadystatechange=function(){if(4==b.readyState)if(200==b.status){var a=eval(b.responseText);"undefined"!=typeof a.listingsError?alert(a.listingsError[0].Message):(window.zpress_widget_domain_token=a,LaunchBase{$randString}())}},b.open("GET",c,!0),b.send()}}else window.zpress_widget_domain_token?LaunchBase{$randString}():
            window.setTimeout("GetToken{$randString}()",20)};GetToken{$randString}();

        </script>
HTML;
        }
        else
        {
            echo <<<HTML
            <script type="text/javascript" id="divLocal{$randString}_">
            var slideshowScript, _ds_midx;
			window.slideshowHasDependency = true;
            LaunchBase{$randString} = function(){
                CreateObject{$randString} = function () { _ds_midx = { currentURL: '{$curURL}', productType: '0', curAPIStub: '{$apiStub}', curImageStub: '{$imagesStub}', targetDomain: window["zpress_widget_domain_token"],accountId: '{$aid}',searchSetupId: '{$ssid}',muteStyles: true,horzCount: '{$horzCount}',maxPrice: '{$maxPrice}',state: '{$state}',city: '{$city}',zip: '{$zip}',modernView: '{$modernView}',curDivID: 'divLocal{$randString}_',querySchema: '12dGTTViUjEzC1rrNlw6Lq6A6wZQlgBarlIcucpGTkQrUP3gCimYF6deRFaavu2IbPpaOkZ9I4K42QaAhLVEcA==' }; }
                AddJavaScriptToDOM{$randString}=function(c,d,e){if(1!=d){var a=document.createElement("script"),b=document.getElementsByTagName("script")[0];a.async=!0;a.src=c;a.onload=function(){ window[e] = 1;};b.parentNode.insertBefore(a,b)}return 1};
                CreateWidget{$randString} = function () {
                    (window.slideshowFinished == 1) ? (window["ds.widget.view.slideshow"].isProcessing = true, CreateObject{$randString}(), new window["ds.widget.view.slideshow"](_ds_midx), window["ds.widget.view.slideshow"].isProcessing = false, window.slideshowHasDependency = false) : window.setTimeout("CreateWidget{$randString}(false)", 20); 
                }
              if (slideshowScript != 1) { slideshowScript = AddJavaScriptToDOM{$randString}("{$this->widgetsCdn}/Scripts/PostCompile/Slideshow_v1_1.js", slideshowScript, 'slideshowFinished') }; 
                CreateWidget{$randString}();
            }


            GetToken{$randString}=function(){if(!window.zpress_widget_domain_token&&1!=window.zpress_widget_domain_token_progress){window.zpress_widget_domain_token_progress=1;var c=-1<navigator.userAgent.indexOf("MSIE 7.0")?!0:!1,d=-1<navigator.userAgent.indexOf("MSIE 8.0")||-1<navigator.userAgent.indexOf("MSIE 9.0")?!0:!1;if(c)rr=document.createElement("script"),rr.async=!0,rr.id="domainScript",rr.type="text/javascript",rr.src="{$apiStub}Encrypt/?targetString="+window.location.hostname+"&targetObject=domain&authType=Basic&curDomain="+
            window.location.hostname+"&objectName_=error",rr.onload=rr.onreadystatechange=function(){"undefined"!=typeof window.error?alert("We had a problem authenticating this domain"):(window.zpress_widget_domain_token=window.encrypted_domain,LaunchBase{$randString}())},document.getElementsByTagName("head")[0].appendChild(rr);else if(c="{$apiStub}Encrypt/?targetString="+window.location.hostname+"&targetObject=domain&authType=CORS&objectName_=error",d){var a=new XDomainRequest;a.onload=function(){window.zpress_widget_domain_token=
            eval(a.responseText);LaunchBase{$randString}()};a.onerror=function(){};a.onprogress=function(){};a.open("GET",c,!0);a.send(null)}else{var b;if(b=new XMLHttpRequest)b.onreadystatechange=function(){if(4==b.readyState)if(200==b.status){var a=eval(b.responseText);"undefined"!=typeof a.listingsError?alert(a.listingsError[0].Message):(window.zpress_widget_domain_token=a,LaunchBase{$randString}())}},b.open("GET",c,!0),b.send()}}else window.zpress_widget_domain_token?LaunchBase{$randString}():
            window.setTimeout("GetToken{$randString}()",20)};GetToken{$randString}();

        </script>
HTML;
        }

        echo $after_widget;
    }
    function update($new_instance, $old_instance) {
        // we need to do this first-line awkwardness so that the title comes through in the sidebar display thing
        $new_instance["slideshowOptions"]["horzCount"] = isset($new_instance["horzCount"]) ? sanitize_text_field($new_instance["horzCount"]) : null;
        $new_instance["slideshowOptions"]["maxPrice"] = sanitize_text_field($new_instance["maxPrice"]);
        $new_instance["slideshowOptions"]["state"] = sanitize_text_field($new_instance["state"]);
        $new_instance["slideshowOptions"]["city"] = sanitize_text_field($new_instance["city"]);
        $new_instance["slideshowOptions"]["zip"] = sanitize_text_field($new_instance["zip"]);

        if(isset($new_instance["modernView"]) && (sanitize_text_field($new_instance["modernView"]) == "on" || sanitize_text_field($new_instance["modernView"]) == "yes")) $new_instance["slideshowOptions"]["modernView"] = "yes";
        else $new_instance["slideshowOptions"]["modernView"] = "no";

        $new_instance["slideshowOptions"]["eDomain"] = isset($new_instance["eDomain"]) ? sanitize_text_field($new_instance["eDomain"]) : null;
        $new_instance = $new_instance["slideshowOptions"];
        return $new_instance;
    }
    function form($instance) {
        $personal_info = stripslashes_deep(get_option('personal_info')); 
        $city = empty($personal_info['city']) ? 'Irvine' : $personal_info['city'];
        $state = empty($personal_info['state']) ? 'CA' : $personal_info['state'];
        $instance = wp_parse_args($instance, array(
            "horzCount"           => "4",
            "maxPrice"          => "350000",
            "state"                 => $state,
            "city"                  => $city,
            "zip"                   => "",
            "modernView"                   => "no",
            "eDomain" =>   ""
            ));
        $maxPrice = esc_attr($instance["maxPrice"]);
        $maxPriceFieldId = $this->get_field_id("maxPrice");
        $maxPriceFieldName = $this->get_field_name("maxPrice");

        $horzCount = esc_attr($instance["horzCount"]);
        $horzCountFieldId = $this->get_field_id("horzCount");
        $horzCountFieldName = $this->get_field_name("horzCount");
        $horzCountState = $instance["modernView"] == "yes" ? "disabled=\"disabled\" " : "";

        $state = esc_attr($instance["state"]);
        $stateFieldId = $this->get_field_id("state");
        $stateFieldName = $this->get_field_name("state");

        $city = esc_attr($instance["city"]);
        $cityFieldId = $this->get_field_id("city");
        $cityFieldName = $this->get_field_name("city");

        $zip = esc_attr($instance["zip"]);
        $zipFieldId = $this->get_field_id("zip");
        $zipFieldName = $this->get_field_name("zip");

        $modernView = $instance["modernView"] == "yes" ? "checked=\"checked\" " : "";
        $modernViewFieldId = $this->get_field_id("modernView");
        $modernViewFieldName = $this->get_field_name("modernView");

        $baseFieldId = $this->get_field_id("slideshowOptions");
        $baseFieldName = $this->get_field_name("slideshowOptions");

        $apiStub = dsWidgets_Service_Base::$widgets_admin_api_stub;
        echo <<<HTML
        	<p>
				<label for="{$maxPriceFieldId}">Max. Price</label>
				<input id="{$maxPriceFieldId}" name="{$maxPriceFieldName}" value="{$maxPrice}" class="widefat" type="text" />
			</p>
            <p>
				<label for="{$horzCountFieldId}">Number of Columns</label>
				<input id="{$horzCountFieldId}" name="{$horzCountFieldName}" value="{$horzCount}" class="widefat" type="text" {$horzCountState} />
			</p>
            <p>
				<label for="{$cityFieldId}">City</label>
				<input id="{$cityFieldId}" name="{$cityFieldName}" value="{$city}" class="widefat" type="text" />
			</p>
            <p>
				<label for="{$stateFieldId}">State</label>
				<input id="{$stateFieldId}" name="{$stateFieldName}" value="{$state}" class="widefat" type="text" />
			</p>
            <p>
				<label for="{$zipFieldId}">Zip Code</label>
				<input id="{$zipFieldId}" name="{$zipFieldName}" value="{$zip}" class="widefat" type="text" />
            </p>
            <p>
                <input id="{$modernViewFieldId}" name="{$modernViewFieldName}" class="checkbox" type="checkbox" {$modernView} onclick="zWidgetsAdmin.dsToggleSlideshowView(this, $('#{$horzCountFieldId}'))" />
                <label for="{$modernViewFieldId}">Modern View</label>
            </p>
HTML;
}}?>