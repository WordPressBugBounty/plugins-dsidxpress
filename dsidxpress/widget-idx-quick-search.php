<?php

add_action('wp_head', array('dsSearchAgent_IdxQuickSearchWidget', 'LoadScripts'), 100);

class dsSearchAgent_IdxQuickSearchWidget extends WP_Widget {
    var $widgetsCdn;

    public function __construct() {
        global $pagenow;

        parent::__construct("dsidx-quicksearch", "IDX Quick Search", array(
            "classname" => "dsidx-widget-quick-search",
            "description" => "Choose either horizontal or vertical format. A simple responsive search form. Allow users to type any location, select from available property types and filter by price range."
            ));

        if ($pagenow == 'widgets.php') {
            wp_enqueue_script('dsidxwidgets_widget_service_admin', DSIDXWIDGETS_PLUGIN_URL . 'js/widget-service-admin.js', array('jquery'), false, true);
        }

        $this->widgetsCdn = dsWidgets_Service_Base::$widgets_cdn;
    }
    public static function LoadScripts(){
        dsidxpress_autocomplete::AddScripts(true);
    }

    public static function shortcodeWidget($values){
        self::renderWidget(array(), $values);
    }

    function widget($args, $instance) { // public so we can use this on our shortcode as well
        self::renderWidget($args, $instance);
    }

    public static function renderWidget($args, $instance){
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
        $formAction = get_home_url() . "/idx/";

		$propertyTypes = dsSearchAgent_GlobalData::GetPropertyTypes();

        $widgetType = isset($instance["widgetType"]) ? esc_html($instance["widgetType"]) : '0';
        $viewType = isset($instance["viewType"]) ? strtolower(esc_html($instance["viewType"])) : '';

        if(empty($viewType)) {
            // Making "modernView" field compatible on latest version
            // By translating it into "viewType" field            
            $modernView = isset($instance["modernView"]) ? esc_html($instance["modernView"]) : '';
            $viewType = !empty($modernView) && strtolower($modernView) == 'yes' ? 'modern-view' : 'classic-view';
        }                
       
        $values =array();
        $values['idx-q-Locations'] = isset($_GET['idx-q-Locations']) ? stripslashes(sanitize_text_field($_GET['idx-q-Locations'])) : null;
        $values['idx-q-PropertyTypes'] = findArrayItems($_GET, 'idx-q-PropertyTypes');
        $values['idx-q-PriceMin'] = isset($_GET['idx-q-PriceMin']) ? formatPrice($_GET['idx-q-PriceMin']) : null;
        $values['idx-q-PriceMax'] = isset($_GET['idx-q-PriceMax']) ? formatPrice($_GET['idx-q-PriceMax']) : null;
		$values['idx-q-BedsMin'] = isset($_GET['idx-q-BedsMin']) ? sanitize_text_field($_GET['idx-q-BedsMin']) : null;
        $values['idx-q-BathsMin'] = isset($_GET['idx-q-BathsMin']) ? sanitize_text_field($_GET['idx-q-BathsMin']) : null;
        		
        $specialSlugs = array(
            'city'      => 'idx-q-Cities',
            'community' => 'idx-q-Communities',
            'tract'     => 'idx-q-TractIdentifiers',
            'zip'       => 'idx-q-ZipCodes'
        );

        $urlParts = explode('/', $_SERVER['REQUEST_URI']);
        $count = 0;
        foreach($urlParts as $p){
            if(array_key_exists($p, $specialSlugs) && isset($urlParts[$count + 1])){
                $values['idx-q-Locations'] = ucwords($urlParts[$count + 1]);
            }
            $count++;
        }

        if (isset($before_widget))
            echo $before_widget;
        if (isset($title))
            echo $before_title . $title . $after_title;

        $widgetClass = ($widgetType == 1 || $widgetType == 'vertical')?'dsidx-resp-vertical':'dsidx-resp-horizontal';
        
        if(isset($instance['class'])){ //Allows us to add custim class for shortcode etc.
            $widgetClass .= ' '.$instance['class'];
        }   
        $widgetId='';
        if(isset($args["widget_id"]))
            $widgetId = '-'.$args["widget_id"];

        $price_min = esc_attr($values['idx-q-PriceMin']);
        $price_max = esc_attr($values['idx-q-PriceMax']); 
        
        if($viewType == 'simple-search') {
            include(DSIDXPRESS_PLUGIN_PATH . 'widget-idx-quick-search-simple.php');
        }        
        else if($viewType == 'modern-view') {
            include(DSIDXPRESS_PLUGIN_PATH . 'widget-idx-quick-search-modern.php');
        }
        else {
            include(DSIDXPRESS_PLUGIN_PATH . 'widget-idx-quick-search-classic.php');
        }
        
        dsidx_footer::ensure_disclaimer_exists("search");
        if (isset($after_widget))
            echo $after_widget;
    }

    function update($new_instance, $old_instance) {
        if(isset($new_instance["title"]))
            $new_instance["quicksearchOptions"]["title"] = sanitize_text_field($new_instance["title"]);

        if(isset($new_instance["eDomain"]))
            $new_instance["quicksearchOptions"]["eDomain"] = sanitize_text_field($new_instance["eDomain"]);

        if(isset($new_instance["widgetType"]))
            $new_instance["quicksearchOptions"]["widgetType"] = sanitize_text_field($new_instance["widgetType"]);
        
        if(isset($new_instance["viewType"]))
            $new_instance["quicksearchOptions"]["viewType"] = sanitize_text_field($new_instance["viewType"]);
               
        $new_instance = $new_instance["quicksearchOptions"];
        return $new_instance;
    }
    function form($instance) {
        $instance = wp_parse_args($instance, array(
            "title" => "Real Estate Search",
            "eDomain" =>   "",
            "widgetType" => 1,
            "modernView" => 'no'
                    ));

        // Extracting fields values
        $title = esc_attr($instance["title"]);
        $widgetType = isset($instance["widgetType"]) ? esc_html($instance["widgetType"]) : '0';
        $viewType = isset($instance["viewType"]) ? esc_html($instance["viewType"]) : '';

        if(empty($viewType)) {
            // Making "modernView" field compatible on latest version
            // By translating it into "viewType" field            
            $modernView = isset($instance["modernView"]) ? esc_html($instance["modernView"]) : '';
            $viewType = !empty($modernView) && strtolower($modernView) == 'yes' ? 'modern-view' : 'classic-view';
        }        

        // Setting up fields names
        $widgetTypeFieldId = $this->get_field_id("widgetType");
        $widgetTypeFieldName = $this->get_field_name("widgetType");
        $titleFieldId = $this->get_field_id("title");
        $titleFieldName = $this->get_field_name("title");
        $baseFieldId = $this->get_field_id("quicksearchOptions");
        $baseFieldName = $this->get_field_name("quicksearchOptions");
        $viewTypeFieldId = $this->get_field_id("viewType");
        $viewTypeFieldName = $this->get_field_name("viewType");

        // Setting up HTML helpers
        $viewTypeList = array(
            'classic-view' => 'Classic View',
            'modern-view' => 'Modern View',
            'simple-search' => 'Simple Search'
        );

        $widgetTypeEnabled = $viewType && strtolower($viewType) == 'simple-search' ? "disabled=\"disabled\" " : "";

        $apiStub = dsWidgets_Service_Base::$widgets_admin_api_stub;

        $verticalSelected = $widgetType == '1' ? 'checked' : '';
        $horizontalSelected = $widgetType == '0' ? 'checked' : '';

        echo <<<HTML
        <p>
            <label for="{$titleFieldId}">Widget title</label>
            <input id="{$titleFieldId}" name="{$titleFieldName}" value="{$title}" class="widefat" type="text" />
        </p>
        <p>
            <label>Widget Aspect Ratio</label><br/><br/>
            <input type="radio" name="{$widgetTypeFieldName}" id="{$widgetTypeFieldId}" {$widgetTypeEnabled} {$verticalSelected} value="1"/> Vertical - <i>Recommended for side columns</i><br/>
            <input type="radio" name="{$widgetTypeFieldName}" {$widgetTypeEnabled} {$horizontalSelected} value="0"/> Horizontal - <i>Recommended for wider areas</i><br/>
        </p>
        <p>
            <label for="{$viewTypeFieldId}">View</label><br/><br/>
            <select id="{$viewTypeFieldId}" name="{$viewTypeFieldName}" onchange="zWidgetsAdmin.viewTypeChanged(this, '{$widgetTypeFieldName}');">
HTML;

foreach($viewTypeList as $key => $value) { 
    $viewTypeSelected = strtolower($viewType) == strtolower($key) ? "selected=\"selected\" " : "";
    echo '<option value="' . $key . '" '.$viewTypeSelected.'>' . $value . '</option>';
}

            echo <<<HTML
			</select>
        </p>        
HTML;
}

function findArrayItems($args, $searchKey) {
    $itemsFound = array();
    
    foreach($args as $key => $val) {
        if(strpos($key, $searchKey) === 0) {
            array_push($itemsFound, stripcslashes($val));
        }
    }
    
    return $itemsFound;
}

function formatPrice($price) {
    if(isset($price) && !empty($price)) {
        return number_format(str_replace(',', '', $price));
    }
    return "";
}

}?>