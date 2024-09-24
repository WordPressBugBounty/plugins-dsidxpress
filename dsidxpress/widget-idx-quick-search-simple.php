<?php

if(!isset($widgetId) || empty($widgetId))
    $widgetId = rand(1,100);

echo <<<HTML

<div class="ds-bs dsidx-resp-search-box-simple-view">
    <form  id="dsidx-quick-search-form{$widgetId}" class="dsidx-resp-search-form" action="{$formAction}" method="GET">
        <div class="input-group">
            <div class="form-control p-0 dsidx-autocomplete-box">
                <input placeholder="Address, City, Zip, MLS #, etc" type="text" 
                       class="text dsidx-search-omnibox-autocomplete" style="border:none;background:none;" 
                       id="dsidx-resp-location-quick-search" name="idx-q-Locations<0>" />
            </div>            
            <span class="input-group-btn">
                <button type="submit" class="btn btn-primary rounded-0 pt-2 pb-2" aria-label="Search">&#128270;</button>
            </span>
        </div>      
        <div class="row mt-2 mb-2">
            <div class="col">
                <div class="custom-control custom-switch">
                    <input type="checkbox" id="dsidx-simple-search-switch{$widgetId}" class="custom-control-input">
                    <label class="custom-control-label" for="dsidx-simple-search-switch{$widgetId}">SEARCH OPTIONS</label>
                </div>                
            </div>
        </div>
        <div class="dsidx-simple-search-expanded">
            <div class="row">
                <div class="col-12 col-md-6">
                    <label for="idx-q-PriceMin">Price</label>
                    <div class="row">
                        <div class="col-6">
                            <input id="idx-q-PriceMin" name="idx-q-PriceMin" type="text" class="form-control" placeholder="Min" value="{$price_min}" maxlength="15" onkeypress="return dsidx.isNumber(event,this.id)" />
                        </div>
                        <div class="col-6">
                            <input id="idx-q-PriceMax" name="idx-q-PriceMax" type="text" class="form-control" placeholder="Max" value="{$price_max}" maxlength="15" onkeypress="return dsidx.isNumber(event,this.id)" />
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="row">
                        <div class="col-6">
                            <label for="idx-q-BedsMin">Beds</label>
                        </div>
                        <div class="col-6">
                            <label for="idx-q-BathsMin">Baths</label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <select id="idx-q-BedsMin" name="idx-q-BedsMin" class="form-control">
                                <option value="">Any</option>
HTML;
                                for($i=1; $i<=9; $i++) {
                                    $selected = $i == $values['idx-q-BedsMin']?' selected="selected"':'';
                                    echo '<option value="'.$i.'"'.$selected.'>'.$i.'+</option>';
                                }
                                echo <<<HTML
                            </select>
                        </div>
                        <div class="col-6">
                            <select id="idx-q-BathsMin" name="idx-q-BathsMin" class="form-control">
                                <option value="">Any</option>
HTML;
                                for($i=1; $i<=9; $i++) {
                                    $selected = $i == $values['idx-q-BathsMin']?' selected="selected"':'';
                                    echo '<option value="'.$i.'"'.$selected.'>'.$i.'+</option>';
                                }
                                echo <<<HTML
                            </select>
                        </div>
                    </div>
                </div>        
            </div>
            <div class="row">
                <div class="col-12 col-md-6">
                    <label for="dsidx-resp-quick-search-box-type">Type</label>
                    <div class="row">
                        <div class="col-12">
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
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <label for="idx-q-YearBuiltMin">Year Built</label>
                    <div class="row">
                        <div class="col-6">
                            <input id="idx-q-YearBuiltMin" name="idx-q-YearBuiltMin" type="text" class="form-control" placeholder="Min" value="{$price_min}" maxlength="15" onkeypress="return dsidx.isNumber(event,this.id)" />
                        </div>
                        <div class="col-6">
                            <input id="idx-q-YearBuiltMax" name="idx-q-YearBuiltMax" type="text" class="form-control" placeholder="Max" value="{$price_max}" maxlength="15" onkeypress="return dsidx.isNumber(event,this.id)" />
                        </div>
                    </div>
                </div>                    
            </div>
            <div class="row mt-2 mb-2">
                <div class="col text-center">
                    <input type="submit" value="Search" class="btn btn-primary pl-5 pr-5" ?>
                </div>
            </div>        
        </div>
    </form>
</div>

HTML;

?>