
var dsidxSearchForm = (function() {
	var nodeEditing;
	var returnObj;
	
	returnObj = {
		init: function() {
			var startNode = tinyMCEPopup.editor.selection.getStart();
			var nodeTextContent = startNode ? startNode.textContent || startNode.innerText : '';
			var showAllIsSet;
			
			if (/^\[idx-quick-search /.test(nodeTextContent) && startNode.tagName == 'P') {
				nodeEditing = startNode;
				tinyMCEPopup.editor.execCommand('mceSelectNode', false, nodeEditing);

				formatContent = /^[^\]]+ format=['"]?([^ "']+)/.exec(nodeTextContent);
				format = formatContent && formatContent[1] ? formatContent[1] : 'horizontal';

				jQuery("input[name=format][value=" + format + "]").prop('checked', true);

				viewTypeContent = /^[^\]]+ viewType=['"]?([^ "']+)/.exec(nodeTextContent);
				viewType = viewTypeContent && viewTypeContent[1] ? viewTypeContent[1] : '';

				if(!viewType) {
        			// Making "modernView" field compatible on latest version
        			// By translating it into "viewType" field
					moderViewContent = /^[^\]]+ modernView=['"]?([^ "']+)/.exec(nodeTextContent);
					modernView = moderViewContent && moderViewContent[1] ? moderViewContent[1] : '';					
					viewType = modernView && modernView.toLowerCase() == 'yes' ? 'modern-view' : 'classic-view';
				}

				jQuery('select[name=view-type] option[value="' + viewType + '"]').prop('selected', true);
				this.viewTypeChanged();
			}
		},
		insert: function() {
			format = jQuery('input:radio[name=format]:checked').val();
			viewType = jQuery('select[name=view-type]').val();

			formatCode = '';
			if(viewType.toLowerCase() != "simple-search") {
				formatCode = ' format="' + format+ '"';
			}

			viewTypeCode= '';
			if(viewType) {
				viewTypeCode = ' viewType="' + viewType + '"';
			}
			
			shortcode = '<p>[idx-quick-search' + formatCode + viewTypeCode + ']</p>';
			
			tinyMCEPopup.editor.execCommand(nodeEditing ? 'mceReplaceContent' : 'mceInsertContent', false, shortcode);
			tinyMCEPopup.close();
		},
		viewTypeChanged: function() {
			viewType = jQuery('select[name=view-type]').val();
			if(viewType.toLowerCase() == "simple-search") {
				jQuery('input[name=format]').prop('disabled', true);
			}
			else {
				jQuery('input[name=format]').prop('disabled', false);
			}
		}
	};
	
	return returnObj;
})();

tinyMCEPopup.onInit.add(dsidxSearchForm.init, dsidxSearchForm);
