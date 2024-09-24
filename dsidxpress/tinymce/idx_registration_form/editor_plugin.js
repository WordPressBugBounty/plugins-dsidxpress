tinymce.create('tinymce.plugins.dsidxRegistrationForm', {
	init : function(ed, url) {
		ed.addCommand('dsidx-registration-form', function() {
			/* Add some condition here to hide button for non pro users */
			ed.windowManager.open({
				file : dsidxAjaxHandler.ajaxurl + '?action=dsidx_client_assist&dsidx_action=IdxRegistartionDialog',
				width : 450,
				height : 295,
				inline : 1
			}, {
				plugin_url : url
			});
		});
		ed.addButton('idxregistrationform', {
			title : 'Insert IDX Registration Form',
			cmd : 'dsidx-registration-form',
			image : url + '/img/idxRegistration.png'
		});
		ed.onNodeChange.add(function(ed, cm, n) {
			cm.setActive('idxregistrationform', !tinymce.isIE && /^\[idx-registrationform /.test(n.innerHTML));
		});
		body_class: 'dsidx-dialog'
	},
	createControl : function(n, cm) {
		return null;
	},
	getInfo : function() {
		return {
			longname : 'Insert IDX Registration Form',
			author : 'Diverse Solutions',
			authorurl : 'http://www.diversesolutions.com',
			infourl : 'javascript:void(0)',
			version : "1.0"
		};
	}
});
tinymce.PluginManager.add('idxregistrationform', tinymce.plugins.dsidxRegistrationForm);