<?php
class dsSearchAgent_SingleListingWidget extends WP_Widget {
	public function __construct() {
		parent::__construct('dsidx-single-listing', 'IDX Single Listing', array(
			'classname' => 'dsidx-widget-single-listing-wrap',
			'description' => 'Show a single real estate listing'
		));
	}
	
	public function widget($args, $instance) {
		if(!isset($instance) || empty($instance)) {
			return;
		}

		extract($args, EXTR_SKIP);
		
		$options = get_option(DSIDXPRESS_OPTION_NAME);

		if (!isset($options["Activated"]) || !$options["Activated"])
			return;
		
		wp_enqueue_script('jquery', false, array(), false, true);
				
		$mls_number = esc_html($instance['mls_number']);

		$apiRequestParams = array();
		$apiRequestParams['responseDirective.ViewNameSuffix'] = 'widget';
		$apiRequestParams['query.MlsNumber'] = $mls_number;
		if (defined("DS_REQUEST_MULTI_AVAILABLE") && DS_REQUEST_MULTI_AVAILABLE==true) {
			$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData("Details", $apiRequestParams,true,null,null,false,true);
			if (empty($apiHttpResponse['body'])) {
				$apiHttpResponse['body'] = '%%ds_Details|' . json_encode($apiHttpResponse) . '|ds_end%%';
			}	
			$data = $apiHttpResponse["body"];	
			echo $before_widget . $data . $after_widget;
		} else {
			$apiHttpResponse = dsSearchAgent_ApiRequest::FetchData('Details', $apiRequestParams);
			if (empty($apiHttpResponse['errors']) && $apiHttpResponse['response']['code'] == '200') {
				$data = $apiHttpResponse['body'];
			} else {
				switch ($apiHttpResponse["response"]["code"]) {
					case 403:
						$data = '<p class="dsidx-error">'.DSIDXPRESS_INACTIVE_ACCOUNT_MESSAGE.'</p>';
						break;
					case 404:
						$data = '<p class="dsidx-error">'.sprintf(DSIDXPRESS_INVALID_MLSID_MESSAGE, $mls_number).'</p>';
						break;
					default:
						$data = '<p class="dsidx-error">'.DSIDXPRESS_IDX_ERROR_MESSAGE.'</p>';
				}
			}
			echo $before_widget . $data . $after_widget;
		}
		
	}
	
	public function update($new_instance, $old_instance) {
		$new_instance["mls_number"] = sanitize_text_field($new_instance["mls_number"]);

		return $new_instance;
	}
	
	public function form($instance) {
		$instance = wp_parse_args($instance, array(
			'mls_number' => ''
		));

		$mls_number = esc_attr($instance['mls_number']);
		
		echo <<<HTML
				<p>
					<label for="{$this->get_field_id('mls_number')}">Enter a MLS Number</label>
					<input type="text" id="{$this->get_field_id('mls_number')}" name="{$this->get_field_name('mls_number')}" value="{$mls_number}" maxlength="30" class="widefat" />
				</p>
HTML;
	}
}
