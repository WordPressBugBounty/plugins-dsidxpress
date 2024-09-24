<?php
class dsWidgets_Service_Base {
    static $widgets_api_stub = 'https://api-b.idx.diversesolutions.com/api/';
    static $widgets_admin_api_stub = 'https://api-b.idx.diversesolutions.com/api/';
    static $widgets_images_stub = 'https://widgets.diverse-cdn.com/Content/Images/widgets';
    static $widgets_cdn = 'https://widgets.diverse-cdn.com';

    static function get_random_string($valid_chars, $length)
    {
        $random_string = "";
        $num_valid_chars = strlen($valid_chars);
        for ($i = 0; $i < $length; $i++)
        {
            $random_pick = mt_rand(1, $num_valid_chars);
            $random_char = $valid_chars[$random_pick-1];
            $random_string .= $random_char;
        }
        return $random_string;
    }

    static function getCapabilities() {
        $capabilities = self::getAllCapabilities();
        if (isset($capabilities['response']['code'])) {
            switch($capabilities['response']['code']){
                case 200:
                    return $capabilities['response']['message'];
                    break;
                default:
                    return $capabilities['response']['code'];
            }   
        } else {
            return false;
        }
    }

    static function getAllCapabilities() { 
        STATIC $capabilities;
        if(empty($capabilities)) {
            $capabilities = dsSearchAgent_ApiRequest::FetchData('MlsCapabilities');
        }
        return $capabilities;
    }
    static function getWidgetErrorMsg($before='', $after='') {
        $capabilities = self::getCapabilities();
        if(!$capabilities || (is_int($capabilities) && $capabilities == 500)){
            return $before.'<p class="dsidx-error">'.DSIDXPRESS_IDX_ERROR_MESSAGE.'</p>'.$after;
        }
        if(is_int($capabilities) && $capabilities == 403){
            return $before.'<p class="dsidx-error">'.DSIDXPRESS_INACTIVE_ACCOUNT_MESSAGE.'</p>'.$after;
        }
        return false;
    }
}
?>