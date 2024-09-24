<?php

    $values['idx-q-ListingStatuses'] = isset($_GET['idx-q-ListingStatuses']) ? sanitize_text_field($_GET['idx-q-ListingStatuses']) : null;

    $capabilities = dsWidgets_Service_Base::getAllCapabilities();
    if (isset($capabilities['body'])) {
        $capabilities = json_decode($capabilities['body'], true);
    }

echo <<<HTML
    <div class="dsidx-resp-search-box-modern-view {$widgetClass}">
        <form  id="dsidx-quick-search-form{$widgetId}" class="dsidx-resp-search-form" action="{$formAction}" method="GET">
            <fieldset>
            <div class="dsidx-resp-area-container-row"> 
                <div class="dsidx-resp-area dsidx-resp-location-area">
                    <label for="dsidx-resp-location" class="dsidx-resp-location">Location</label>
                    <div class="dsidx-autocomplete-box">
                        <input placeholder="Address, City, Zip, MLS #, etc" 
                        type="text" class="text dsidx-search-omnibox-autocomplete" 
                        style="border:none;background:none;"
                        id="dsidx-resp-location-quick-search" name="idx-q-Locations<0>" />
                    </div> 
                    <div id="dsidx-autocomplete-spinner-quick-search" class="dsidx-autocomplete-spinner" style="display:none;"><img src="https://api-idx.diversesolutions.com/Images/dsIDXpress/loadingimage.gif"></div>
                </div>
                <div class="dsidx-resp-area dsidx-resp-type-area">
                    <label for="dsidx-resp-area-type" class="dsidx-resp-type">Property Types</label>                      
                    <select id="dsidx-resp-quick-search-box-type" multiple="multiple">
HTML;
                        if (is_array($propertyTypes)) {
                            $propertyTypesSelected = isset($values['idx-q-PropertyTypes']) && !empty($values['idx-q-PropertyTypes']);
                            foreach ($propertyTypes as $propertyType) {
                                $name = esc_html($propertyType->DisplayName);
                                $id = esc_attr($propertyType->SearchSetupPropertyTypeID);
                                if($propertyTypesSelected) {
                                    $selected = in_array($propertyType->SearchSetupPropertyTypeID, $values['idx-q-PropertyTypes'])?' selected="selected"':'';
                                }
                                else {
                                    $selected = isset($propertyType->IsSearchedByDefault) && $propertyType->IsSearchedByDefault == true ?' selected="selected"':'';
                                }
                                echo "<option value=\"{$id}\"{$selected}>{$name}</option>";
                            }
                        }

                        echo <<<HTML
                    </select>
                    <div class="dsidx-quick-search-type-hidden-inputs"></div>
                </div>
                <div class="dsidx-resp-area dsidx-resp-status-area">
                    <label for="dsidx-resp-quick-search-box-status" class="dsidx-resp-status">Property Status</label>                      
                    <select id="dsidx-resp-quick-search-box-status" multiple="multiple">
HTML;
                        $selectedStatus = $values['idx-q-ListingStatuses'];

                        $selectActive = ($selectedStatus == 1 || $selectedStatus == 3 || $selectedStatus == 5 || $selectedStatus == 7 || $selectedStatus == 9 || $selectedStatus == 11 || $selectedStatus == 13 || $selectedStatus == 15) ? ' selected' : '';
                        $selectConditional = ($selectedStatus == 2 || $selectedStatus == 3 || $selectedStatus == 6 || $selectedStatus == 7 || $selectedStatus == 10 || $selectedStatus == 11 || $selectedStatus == 14 || $selectedStatus == 15) ? ' selected' : '';
                        $selectPending = ($selectedStatus == 4 || $selectedStatus == 5 || $selectedStatus == 6 || $selectedStatus == 7 || $selectedStatus == 12 || $selectedStatus == 13 || $selectedStatus == 14 || $selectedStatus == 15) ? ' selected' : '';
                        $selectSold = ($selectedStatus == 8 || $selectedStatus == 9 || $selectedStatus == 10 || $selectedStatus == 11 || $selectedStatus == 12 || $selectedStatus == 13 || $selectedStatus == 14 || $selectedStatus == 15) ? ' selected' : '';

                        echo "<option value=\"1\"{$selectActive}>Active</option>";


                        if(isset($capabilities['HasConditionalData']) && $capabilities['HasConditionalData'] == true){
                            echo "<option value=\"2\"{$selectConditional}>Conditional</option>";
                        }                                
                        if(isset($capabilities['HasPendingData']) && $capabilities['HasPendingData'] == true){
                            echo "<option value=\"4\"{$selectPending}>Pending</option>";
                        }
                        if(isset($capabilities['HasSoldData']) && $capabilities['HasSoldData'] == true){
                            echo "<option value=\"8\"{$selectSold}>Sold</option>";
                        }
                        
                        echo <<<HTML
                    </select>
                    <input type="hidden" id="dsidx-quick-search-status-hidden" name="idx-q-ListingStatuses" />
                </div>
                </div>
                <div class="dsidx-resp-area-container-row"> 
                <div class="dsidx-resp-area dsidx-quick-resp-min-baths-area dsidx-resp-area-half dsidx-resp-area-left">
                    <label for="idx-q-BedsMin">Beds</label>
                    <select id="idx-q-BedsMin" name="idx-q-BedsMin" class="dsidx-beds">
                        <option value="">0+</option>
HTML;
                        for($i=1; $i<=9; $i++){
                            $selected = $i == $values['idx-q-BedsMin']?' selected="selected"':'';
                            echo '<option value="'.$i.'"'.$selected.'>'.$i.'+</option>';
                        }
                    echo <<<HTML
                    </select>
                </div>

                <div class="dsidx-resp-area dsidx-quick-resp-min-baths-area dsidx-resp-area-half dsidx-resp-area-right">
                    <label for="idx-q-BathsMin">Baths</label>
                    <select id="idx-q-BathsMin" name="idx-q-BathsMin" class="dsidx-baths">
                        <option value="">0+</option>
HTML;
                        for($i=1; $i<=9; $i++){
                            $selected = $i == $values['idx-q-BathsMin']?' selected="selected"':'';
                            echo '<option value="'.$i.'"'.$selected.'>'.$i.'+</option>';
                        }                                    
                    echo <<<HTML
                    </select>
                </div>

                <div class="dsidx-resp-area dsidx-quick-resp-price-area dsidx-resp-price-area-min dsidx-resp-area-half dsidx-resp-area-left">
                    <label for="dsidx-resp-price-min" class="dsidx-resp-price">Price Min</label>
                    <input id="idx-q-PriceMin" name="idx-q-PriceMin" type="text" class="dsidx-price" placeholder="No Min" value="{$price_min}" maxlength="15" onkeypress="return dsidx.isNumber(event,this.id)" />
                </div>
                <div class="dsidx-resp-area dsidx-quick-resp-price-area dsidx-resp-price-area-max dsidx-resp-area-half dsidx-resp-area-right">
                    <label for="dsidx-resp-price-max" class="dsidx-resp-price">Price Max</label>
                    <input id="idx-q-PriceMax" name="idx-q-PriceMax" type="text" class="dsidx-price" placeholder="No Max" value="{$price_max}" maxlength="15" onkeypress="return dsidx.isNumber(event,this.id)" />
                </div>
                <input type="hidden" name="idx-st" value="qs">
                <div class="dsidx-resp-area dsidx-resp-area-submit">
                    <label for="dsidx-resp-submit" class="dsidx-resp-submit">&nbsp;</label>
                    <input type="submit" class="dsidx-resp-submit" value="Search" onclick="return dsidx.compareMinMaxPrice_QuickSearch();"/>
                </div>
                </div>
            </fieldset>
        </form>
    </div>
HTML;
?>