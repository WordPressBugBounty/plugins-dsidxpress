var $ = jQuery;
var dsidxMultiListings = (function() {
	var nodeEditing;
	var returnObj;
	var multiListingType = 'quick-search';
	
	returnObj = {
		init: function() {
			var startNode = tinyMCEPopup.editor.selection.getStart();
			var nodeTextContent = startNode ? startNode.textContent || startNode.innerText : '';
			var linkId, area, addressMasks, mlsNumbers, minPrice, maxPrice, checkedListingStatuses, checkedPropertyTypes, checkedPropertyFeatures, sortColumn, sortDirection, count, listTabAlignment, shortcodeResultsView;
			
			if (/^\[idx-listings /.test(nodeTextContent) && startNode.tagName == 'P') {
				nodeEditing = startNode;
				tinyMCEPopup.editor.execCommand('mceSelectNode', false, nodeEditing);
				
				linkId = /^[^\]]+ linkid=['"]?(\d+)/.exec(nodeTextContent);
				count = /^[^\]]+ count=['"]?(\d+)/.exec(nodeTextContent);
				showlargerphotos = /^[^\]]+ showlargerphotos=['"]?true/.exec(nodeTextContent);
				listTabAlignment = /^[^\]]+ listtabalignment=['"]?([^'" ]+)/.exec(nodeTextContent);
				shortcodeResultsView = /^[^\]]+ shortcoderesultsview=['"]?([^'" ]+)/.exec(nodeTextContent);
				
				if (linkId) {
					$('#saved-link').val(linkId[1]);
				} else {
					area = /^[^\]]+ (city|community|county|tract|zip|agentid|officeid)=['"]([^'"]+)/.exec(nodeTextContent);
					agentid = /^[^\]]+ agentid=['"]?([^'"]+)/.exec(nodeTextContent);
					officeid = /^[^\]]+ officeid=['"]?([^'"]+)/.exec(nodeTextContent);
					addressMasks = /^[^\]]+ addressmasks=['"]?([^'"]+)/.exec(nodeTextContent);
					mlsNumbers = /^[^\]]+ mlsnumbers=['"]?([^'"]+)/.exec(nodeTextContent);
					minPrice = /^[^\]]+ minprice=['"]?(\d+)/.exec(nodeTextContent);
					maxPrice = /^[^\]]+ maxprice=['"]?(\d+)/.exec(nodeTextContent);
					minBeds = /^[^\]]+ minbeds=['"]?(\d+)/.exec(nodeTextContent);
					maxBeds = /^[^\]]+ maxbeds=['"]?(\d+)/.exec(nodeTextContent);
					minBaths = /^[^\]]+ minbaths=['"]?(\d+)/.exec(nodeTextContent);
					maxBaths = /^[^\]]+ maxbaths=['"]?(\d+)/.exec(nodeTextContent);
					minDOM = /^[^\]]+ mindom=['"]?(\d+)/.exec(nodeTextContent);
					maxDOM = /^[^\]]+ maxdom=['"]?(\d+)/.exec(nodeTextContent);
					minYear = /^[^\]]+ minyear=['"]?(\d+)/.exec(nodeTextContent);
					maxYear = /^[^\]]+ maxyear=['"]?(\d+)/.exec(nodeTextContent);
					minImpSqFt = /^[^\]]+ minimpsqft=['"]?(\d+)/.exec(nodeTextContent);
					maxImpSqFt = /^[^\]]+ maximpsqft=['"]?(\d+)/.exec(nodeTextContent);
					minLotSqFt = /^[^\]]+ minlotsqft=['"]?(\d+)/.exec(nodeTextContent);
					maxLotSqFt = /^[^\]]+ maxlotsqft=['"]?(\d+)/.exec(nodeTextContent);
					checkedListingStatuses = /^[^\]]+ statuses=['"]?([\d,]+)/.exec(nodeTextContent);
					checkedPropertyTypes = /^[^\]]+ propertytypes=['"]?([\d,]+)/.exec(nodeTextContent);
					checkedPropertyFeatures = /^[^\]]+ propertyfeatures=['"]?([\d,]+)/.exec(nodeTextContent);
					sortColumn = /^[^\]]+ orderby=['"]?([^'" ]+)/.exec(nodeTextContent);
					sortDirection = /^[^\]]+ orderdir=['"]?([^'" ]+)/.exec(nodeTextContent);
					if (area) 
						$('#area-type').val(area[1]);
					if (agentid)
						$('#agentid').val(agentid[1]);
					if (officeid)
						$('#officeid').val(officeid[1]);								
					if (addressMasks)
						$('#address-masks').val(addressMasks[1]);
					if (mlsNumbers)
						$('#mls-numbers').val(mlsNumbers[1]);				
					if (minPrice)
						$('#min-price').val(minPrice[1]);
					if (maxPrice)
						$('#max-price').val(maxPrice[1]);
					if (minBeds) 
						$('#min-beds').val(minBeds[1]);
					if (maxBeds)
						$('#max-beds').val(maxBeds[1]);
					if (minBaths)
						$('#min-baths').val(minBaths[1]);
					if (maxBaths)
						$('#max-baths').val(maxBaths[1]);
					if (minDOM)
						$('#min-dom').val(minDOM[1]);
					if (maxDOM)
						$('#max-dom').val(maxDOM[1]);
					if (minYear)
						$('#min-year').val(minYear[1]);
					if (maxYear)
						$('#max-year').val(maxYear[1]);
					if (minImpSqFt)
						$('#min-impsqft').val(minImpSqFt[1]);
					if (maxImpSqFt)
						$('#max-impsqft').val(maxImpSqFt[1]);
					if (minLotSqFt)
						$('#min-lotsqft').val(minLotSqFt[1]);
					if (maxLotSqFt)
						$('#max-lotsqft').val(maxLotSqFt[1]);
					if (checkedListingStatuses) {
						checkedListingStatuses = checkedListingStatuses[1].split(',');
						for (var j = 0, k = checkedListingStatuses.length; j < k; ++j)
							$('#status-' + checkedListingStatuses[j]).each(function() { this.checked = true; });
					}
					if (checkedPropertyTypes) {
						checkedPropertyTypes = checkedPropertyTypes[1].split(',');
						for (var i = 0, l = checkedPropertyTypes.length; i < l; ++i)
							$('#property-type-' + checkedPropertyTypes[i]).each(function() { this.checked = true; });
					}
					if (checkedPropertyFeatures) {
						checkedPropertyFeatures = checkedPropertyFeatures[1].split(',');
						for (var i = 0, l = checkedPropertyFeatures.length; i < l; ++i)
							$('#property-feature-' + checkedPropertyFeatures[i]).each(function() { this.checked = true; });
					}					
					if (sortColumn) {
						sortDirection = sortDirection ? sortDirection[1] : 'DESC';
						$('#display-order-column').val(sortColumn[1] + '|' + sortDirection);
					}
				}

				if (count)
					$('#number-to-display').val(count[1]);
				if (showlargerphotos)
					$('#larger-photos').attr("checked", "checked");
				if (listTabAlignment)
					$('#list-tab-alignment').val(listTabAlignment[1]);
				if (shortcodeResultsView)
					$('#shortcode-results-view').val(shortcodeResultsView[1]);
				
				if (tabsEnabled) //error occurs when trying to switch tabs for zpress sites so check first
					this.changeTab(linkId ? 'pre-saved-links' : 'quick-search');
			}
			
			$('#area-type').change(this.loadAreasByType);
			this.loadAreasByType(area ? escape(area[2]) : null);
		},
		loadAreasByType: function(areaToSetAfterLoad) {
			switch ($('#area-type').val()) {
				case 'city':
				case 'community':
				case 'county':
				case 'zip':
				case 'tract':
					document.getElementById('result-label').innerText = 'Area name';
				
					document.getElementById('agentid').style.display = 'none';
					document.getElementById('officeid').style.display = 'none';
					document.getElementById("area-name").style.display = 'inline-block';
					
					$("#area-name").empty();
					$("#area-name").html('<option value="">Loading...</option>');
					break;
				case 'agentid':
					document.getElementById('result-label').innerText = 'Agent ID';
					
					document.getElementById('agentid').style.display = 'inline-block';
					document.getElementById('officeid').style.display = 'none';
					document.getElementById("area-name").style.display = 'none';
					break;
				case 'officeid':
					document.getElementById('result-label').innerText = 'Office ID';
					
					document.getElementById('agentid').style.display = 'none';
					document.getElementById('officeid').style.display = 'inline-block';
					document.getElementById("area-name").style.display = 'none';
					break;
				default:
					console.log("We're in 'default' for some reason, not sure what's happening...");
			};
			$.ajax({
				url: ApiRequest.uriBase + '&dsidx_action=LoadAreasByType',
				type: 'POST',
				dataType: 'json',
				cache: true,
				data: {
					searchSetupID: ApiRequest.searchSetupID,
					type: $('#area-type').val(),
					minListingCount: 5
				},
				success: function(data) {
					var options = [];
					var areaName, urlEscapedAreaName, printableAreaName;
					
					for (var i = 0, j = data.length; i < j; ++i) {
						areaName = data[i].Name;
						urlEscapedAreaName = escape(areaName);
						
						if (/"/.test(areaName))
							continue;
						
						if (areaName.length > 30)
							printableAreaName = $('<div/>').text(areaName.substr(0, 30) + '...').html();
						else
							printableAreaName = $('<div/>').text(areaName).html();
						options.push('<option value="' + urlEscapedAreaName + '">' + printableAreaName + '</option>');
					}
					$('#area-name').html(options.join(''));
					
					if (areaToSetAfterLoad)
						$('#area-name').val(areaToSetAfterLoad);
				}
			});
		},
		changeTab: function(type) {
			multiListingType = type;
			if (multiListingType == 'quick-search')
				mcTabs.displayTab('custom_search_tab', 'custom_search_panel');
			else if (multiListingType == 'pre-saved-links')
				mcTabs.displayTab('saved_links_tab', 'saved_links_panel');
		},
		insert: function() {
			var shortcode = '<p>[idx-listings';
			var minPrice, maxPrice, minBeds, maxBeds, minBaths, maxBaths, minDOM, maxDOM, minYear, maxYear, minImpSqFt, maxImpSqFt, minLotSqFt, maxLotSqFt, agentid, officeid, checkedListingStatuses, checkedPropertyTypes, checkedPropertyFeatures, sortOrder, count, listTabAlignment, shortcodeResultsView, addressMasks, mlsNumbers;
			
			agentid = $('#agentid').val();
			officeid = $('#officeid').val();
			minPrice = parseInt($('#min-price').val());
			maxPrice = parseInt($('#max-price').val());
			minBeds = parseInt($('#min-beds').val());
			maxBeds = parseInt($('#max-beds').val());
			minBaths = parseInt($('#min-baths').val());
			maxBaths = parseInt($('#max-baths').val());
			minDOM = $('#min-dom').val();
			maxDOM = $('#max-dom').val();
			minYear = $('#min-year').val();
			maxYear = $('#max-year').val();
			minImpSqFt = $('#min-impsqft').val();
			maxImpSqFt = $('#max-impsqft').val();
			minLotSqFt = $('#min-lotsqft').val();
			maxLotSqFt = $('#max-lotsqft').val();
			checkedListingStatuses = $('#listing-status-container input:checked').map(function() { return this.value; }).get().join(',');
			checkedPropertyTypes = $('#property-type-container input:checked').map(function() { return this.value; }).get().join(',');
			checkedPropertyFeatures = $('#property-feature-container input:checked').map(function() { return this.value; }).get().join(',');
			sortOrder = $('#display-order-column').val().split('|');
			count = parseInt($('#number-to-display').val());
			largerPhotos = !!$('#larger-photos:checked').length;
			listTabAlignment = $('#list-tab-alignment').val();
			shortcodeResultsView = $('#shortcode-results-view').val();
			addressMasks = $('#address-masks').val();
			mlsNumbers = $('#mls-numbers').val();
			
			if (multiListingType == 'quick-search') {
				if (document.getElementById('area-name').style.display !== 'none')
					shortcode += ' ' + $('#area-type').val() + '="' + unescape($('#area-name').val()) + '"';
				if (agentid.length > 0 && $('#area-type').val() == 'agentid')
					shortcode += ' agentid="' + agentid + '"';
				if (officeid.length > 0 && $('#area-type').val() == 'officeid')
					shortcode += ' officeid="' + officeid + '"';
				if (addressMasks && addressMasks.length > 0)
					shortcode += ' addressmasks="' + addressMasks + '"';
				if (mlsNumbers && mlsNumbers.length > 0)
					shortcode += ' mlsnumbers="' + mlsNumbers + '"';					
				if (!isNaN(minPrice) && minPrice > 0)
					shortcode += ' minprice="' + minPrice + '"';
				if (!isNaN(maxPrice) && maxPrice > 0)
					shortcode += ' maxprice="' + maxPrice + '"';
				if (!isNaN(minBeds) && minBeds > 0) 
					shortcode += ' minbeds="' + minBeds + '"';
				if (!isNaN(maxBeds) && maxBeds > 0)
					shortcode += ' maxbeds="' + maxBeds + '"';
				if (!isNaN(minBaths) && minBaths > 0) 
					shortcode += ' minbaths="' + minBaths + '"';
				if (!isNaN(maxBaths) && maxBaths > 0)
					shortcode += ' maxbaths="' + maxBaths + '"';
				if (minDOM.length > 0)
					shortcode += ' mindom="' + minDOM + '"';
				if (maxDOM.length > 0)
					shortcode += ' maxdom="' + maxDOM + '"';
				if (!isNaN(minYear) && minYear > 0) 
					shortcode += ' minyear="' + minYear + '"';
				if (!isNaN(maxYear) && maxYear > 0)
					shortcode += ' maxyear="' + maxYear + '"';
				if (!isNaN(minImpSqFt) && minImpSqFt > 0)
					shortcode += ' minimpsqft="' + minImpSqFt + '"';
				if (!isNaN(maxImpSqFt) && maxImpSqFt > 0)
					shortcode += ' maximpsqft="' + maxImpSqFt + '"';
				if (!isNaN(minLotSqFt) && minLotSqFt > 0)
					shortcode += ' minlotsqft="' + minLotSqFt + '"';
				if (!isNaN(maxLotSqFt) && maxLotSqFt > 0)
					shortcode += ' maxlotsqft="' + maxLotSqFt + '"';
				if (checkedListingStatuses)
					shortcode += ' statuses="' + checkedListingStatuses + '"';
				if (checkedPropertyTypes)
					shortcode += ' propertytypes="' + checkedPropertyTypes + '"';
				if (checkedPropertyFeatures)
					shortcode += ' propertyfeatures="' + checkedPropertyFeatures + '"';					
				shortcode += ' orderby="' + sortOrder[0] + '" orderdir="' + sortOrder[1] + '"';
			} else if (multiListingType == 'pre-saved-links') {
				shortcode += ' linkid="' + $('#saved-link').val() + '"';
			}

			if (!isNaN(count) && count > 0)
				shortcode += ' count="' + count + '"';
			if (largerPhotos)
				shortcode += ' showlargerphotos="true"';
			
			if(listTabAlignment)
				shortcode += ' listtabalignment="' + listTabAlignment + '"';
			
			if(shortcodeResultsView)
				shortcode += ' shortcoderesultsview="' + shortcodeResultsView + '"';			
			
			shortcode += ']</p>';
			
			tinyMCEPopup.editor.execCommand(nodeEditing ? 'mceReplaceContent' : 'mceInsertContent', false, shortcode);
			tinyMCEPopup.close();
		}
	};
	
	return returnObj;
})();

tinyMCEPopup.onInit.add(dsidxMultiListings.init, dsidxMultiListings);
