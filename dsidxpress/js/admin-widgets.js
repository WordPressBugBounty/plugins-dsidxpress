jQuery(function($) {
    $('body').on('click', '.widget-control-save', function(event) {
		var saveButtonId = $( this ).attr( 'id' );
		var baseId = saveButtonId.replace( /-savewidget/, '' );

        var errors = '';

		if(baseId.indexOf('dsidx-listings') !== -1)
		{
			errors = validateIdxListingsWidget(baseId);
		}
		else if (baseId.indexOf('dsidx-recentstatus') !== -1)
		{
			errors = validateRecentPropertiesWidget(baseId);
		}
		else if (baseId.indexOf('dsidx-slideshow') !== -1)
		{
			errors = validatePropertySlideshowWidget(baseId);
		}
		else if (baseId.indexOf('dsidx-mapsearch') !== -1)
		{
			errors = validateMapSearchWidget(baseId);
		}		

        if (errors) {
			event.preventDefault();
			event.stopPropagation();
			
			errors = 'Please fix following errors:\n' + errors;
			alert(errors);
        } 
    })
});

function validateIdxListingsWidget(baseId) {
	var errors = '';
	baseId += '-listingsOptions';
	
	var listingsToShow = $('#' + baseId + '\\[listingsToShow\\]').val();
	if(listingsToShow && !/^([1-9]|[1234]\d|5[0])$/.test(listingsToShow)) {
		errors += 'Provide a valid \'Listings to Show\' value from 1 to 50\n';
	}

	return errors;
}

function validateRecentPropertiesWidget(baseId) {
	var errors = '';

	var width = $('#' + baseId + '-width').val();
	if(width && !/^[1-9]\d*$/.test(width)) {
		errors += 'Provide a valid numeric value greater than 0 for \'Width\'\n';
	}

	var rowCount = $('#' + baseId + '-rowCount').val();
	if(rowCount && !/^[1-9]\d*$/.test(rowCount)) {
		errors += 'Provide a valid numeric value greater than 0 for \'Number of Rows\'\n';
	}

	return errors;
}

function validatePropertySlideshowWidget(baseId) {
	var errors = '';	

	var maxPrice = $('#' + baseId + '-maxPrice').val();
	if(maxPrice && !/^\d+(?:[.,]\d+)*$/.test(maxPrice)) {
		errors += 'Provide a valid numeric value for \'Max. Price\'\n';
	}

	var horzCount = $('#' + baseId + '-horzCount').val();
	if(horzCount && !/^[1-9]\d*$/.test(horzCount)) {
		errors += 'Provide a valid numeric value greater than 0 for \'Number of Columns\'\n';
	}

	return errors;
}

function validateMapSearchWidget(baseId) {
	var errors = '';	

	var width = $('#' + baseId + '-width').val();
	if(width && !/^[1-9]\d*$/.test(width)) {
		errors += 'Provide a valid numeric value greater than 0 for \'Width\'\n';
	}

	var height = $('#' + baseId + '-height').val();
	if(height && !/^[1-9]\d*$/.test(height)) {
		errors += 'Provide a valid numeric value greater than 0 for \'Height\'\n';
	}

	var priceFloor = $('#' + baseId + '-priceFloor').val();
	if(priceFloor && !/^\d+(?:[.,]\d+)*$/.test(priceFloor)) {
		errors += 'Provide a valid numeric value for \'Min. Searchable Price\'\n';
	}

	var priceCeiling = $('#' + baseId + '-priceCeiling').val();
	if(priceCeiling && !/^\d+(?:[.,]\d+)*$/.test(priceCeiling)) {
		errors += 'Provide a valid numeric value for \'Max. Searchable Price\'\n';
	}
	
	var bedsMin = $('#' + baseId + '-bedsMin').val();
	if(bedsMin && !/^\d+(?:[.,]\d+)*$/.test(bedsMin)) {
		errors += 'Provide a valid numeric value for \'Min. Beds\'\n';
	}
	
	var bathsMin = $('#' + baseId + '-bathsMin').val();
	if(bathsMin && !/^\d+(?:[.,]\d+)*$/.test(bathsMin)) {
		errors += 'Provide a valid numeric value for \'Min. Baths\'\n';
	}	

	return errors;
}