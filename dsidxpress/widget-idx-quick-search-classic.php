<?php

echo <<<HTML
            <div class="dsidx-resp-search-box {$widgetClass}">
                <form  id="dsidx-quick-search-form{$widgetId}" class="dsidx-resp-search-form" action="{$formAction}" method="GET">
                    <fieldset>
                        <div class="dsidx-resp-area dsidx-resp-location-area">
                            <label for="dsidx-resp-location" class="dsidx-resp-location">Location</label>
                            <div class="dsidx-autocomplete-box">
                                <input placeholder="Address, City, Community, ZIP, MLS #" 
                                type="text" class="text dsidx-search-omnibox-autocomplete" 
                                style="border:none;background:none;"
                                id="dsidx-resp-location-quick-search" name="idx-q-Locations<0>" />
                            </div> 
                            <div id="dsidx-autocomplete-spinner-quick-search" class="dsidx-autocomplete-spinner" style="display:none;"><img src="https://api-idx.diversesolutions.com/Images/dsIDXpress/loadingimage.gif"></div>
                        </div>
                        <div class="dsidx-resp-area dsidx-resp-type-area">
                            <label for="dsidx-resp-area-type" class="dsidx-resp-type">Type</label>                      
                            <select id="dsidx-resp-quick-search-box-type" multiple="multiple">
HTML;
                                if (is_array($propertyTypes)) {
                                    foreach ($propertyTypes as $propertyType) {
                                        $name = esc_html($propertyType->DisplayName);
                                        $id = esc_attr($propertyType->SearchSetupPropertyTypeID);                                            
                                        $selected = in_array($propertyType->SearchSetupPropertyTypeID, $values['idx-q-PropertyTypes'])?' selected="selected"':'';
                                        echo "<option value=\"{$id}\"{$selected}>{$name}</option>";
                                    }
                                }

                                echo <<<HTML
                            </select>
                            <div class="dsidx-quick-search-type-hidden-inputs"></div>
                        </div>
                        <div class="dsidx-resp-area dsidx-quick-resp-min-baths-area dsidx-resp-area-half dsidx-resp-area-left">
                            <label for="idx-q-BedsMin">Beds</label>
                            <select id="idx-q-BedsMin" name="idx-q-BedsMin" class="dsidx-beds">
                                <option value="">Any</option>
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
                                <option value="">Any</option>
HTML;
                                for($i=1; $i<=9; $i++){
                                    $selected = $i == $values['idx-q-BathsMin']?' selected="selected"':'';
                                    echo '<option value="'.$i.'"'.$selected.'>'.$i.'+</option>';
                                }
                            echo <<<HTML
                            </select>
                        </div>

                        <div class="dsidx-resp-area dsidx-quick-resp-price-area dsidx-resp-price-area-min dsidx-resp-area-half dsidx-resp-area-left">
                            <label for="dsidx-resp-price-min" class="dsidx-resp-price">Price</label>
                            <input id="idx-q-PriceMin" name="idx-q-PriceMin" type="text" class="dsidx-price" placeholder="Any" value="{$price_min}" maxlength="15" onkeypress="return dsidx.isNumber(event,this.id)" />
                        </div>
                        <div class="dsidx-resp-area dsidx-quick-resp-price-area dsidx-resp-price-area-max dsidx-resp-area-half dsidx-resp-area-right">
                            <label for="dsidx-resp-price-max" class="dsidx-resp-price">To</label>
                            <input id="idx-q-PriceMax" name="idx-q-PriceMax" type="text" class="dsidx-price" placeholder="Any" value="{$price_max}" maxlength="15" onkeypress="return dsidx.isNumber(event,this.id)" />
                        </div>
                        <input type="hidden" name="idx-st" value="qs">
                        <div class="dsidx-resp-area dsidx-resp-area-submit">
                            <label for="dsidx-resp-submit" class="dsidx-resp-submit">&nbsp;</label>
                            <input type="submit" class="dsidx-resp-submit" value="Search" onclick="return dsidx.compareMinMaxPrice_QuickSearch();"/>
                        </div>
                    </fieldset>
                </form>
            </div>
HTML;

?>