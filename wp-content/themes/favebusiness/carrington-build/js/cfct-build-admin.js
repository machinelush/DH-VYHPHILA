// Carrington Build Admin Functions
;(function($) {

// Admin Init	
	cfctAdminEditInit = function() {
		// process UI tabs
		var cfctInitialActiveTab = null;
		
		// init sortables
		$('#cfct-sortables').sortable({
			handle:cfct_builder.opts.row_handle,
			items:'.cfct-row',
			placeholder:'cfct-row-draggable-placeholder',
			forcePlaceholderSize:true,
			opacity:0.5,
			axis:'y',
			cancel:'.cfct-row-delete',
			update:cfct_builder.updateRowOrder
		});
				
		// insert in to DOM
		$('#titlediv').after(cfct_build);
		cfct_builder.$rowTrigger = $(cfct_builder.opts.rowTriggerSelector);
		cfct_builder.bindClickables();
		cfct_builder.bindLiveClickables();
		
		// Handle the display behavior after this has been placed in the DOM in its correct location.
		$('#cfct-build-tabs li a',cfct_build).each(function() {
			var _this = $(this);
			// add click event to tab
			_this.click(function() {
				cfctTabSwitch(_this);
				return false;
			});

			// handle visual editor disabled
			var contentDiv = _this.attr('href') == '#postdiv'
				? '#postdivrich'
				: _this.attr('href');
			var _thisHref = $(contentDiv);
			wpActiveEditor = contentDiv.replace('#', '');

			// hide tab content if its not the active tab
			if (_this.parents('li').hasClass('active')) {
				_thisHref.show();
				cfctInitialActiveTab = _this.attr('href');
			}
		});
		cfctPrepMediaGallery(cfctInitialActiveTab == '#cfct-build-data' ? 'build' : 'wordpress');
		
		// dialog helper for window resize
		$(window).resize(function() {
			cfct_builder.resizeDOMWindow();
		});
		
		// init options box
		$('.cfct-build-options-header').click(function() {
			cfct_builder.toggleOptions();
			return false;
		});
	};

// Tab Switching
	cfctTabSwitch = function(clicked) {
		$('body').trigger('click');
		var _this = $(clicked);
		var _tgt = _this.attr('href');
		
		if ( $(_tgt).is(':visible') ) {
			return false;
		}
		
		var switchMessage;
		switch (true) {
			case ((_tgt == '#postdivrich' || _tgt == '#postdiv') && !cfct_builder.hasRows()):
			case (_tgt == '#cfct-build-data' && $('#content').val().length == 0):
				switchMessage = null;
				break;
			case (_tgt == '#postdivrich' || _tgt == '#postdiv'):
				switchMessage = 'Edits made in the WordPress editor will not be saved until the Save/Update button is used. Build data will not be affected.';
				break;
			case (_tgt == '#cfct-build-data'):
				switchMessage = 'Edits made in the Build area will be saved immediately and will overwrite the Post Content. Content in the WordPress editor will not be destroyed until you navigate away from this page or Save/Update button is used.';
				break;
		}
		
		var do_tab_switch = true;
		
		if (switchMessage != null) {
			if (confirm(switchMessage)) {
				cfctDoTabSwitch(_this);
			}
		}
		else {
			cfctDoTabSwitch(_this);
		}
		
		return false;
	};
	
	cfctDoTabSwitch = function(_this) {
		_this.parents('li').addClass('active').siblings().each(function() {
			_l = $(this);
			_l.removeClass('active');
			$(_l.children('a').attr('href')).hide();
		});
		
		var tgt = _this.attr('href');
		var edit_state = null;

		switch (tgt) {
			case '#postdivrich':
			case '#postdiv':
				cfct_builder.toggleOptionsMenu('hide');
				cfctPrepMediaGallery('wordpress');
				$(tgt).show();
				edit_state = 'wordpress';
				break;
			case '#cfct-build-data':
				cfctPrepMediaGallery('build');
				cfct_builder.showWelcome();
				$(tgt).show();
				cfct_builder.toggleOptionsMenu('show');
				edit_state = 'build';
				break;
		}
		cfct_builder.fetch('set_active_state', { 'active_state':edit_state }, 'set-edit-state');
		
		// force WordPress content save notice
		autosaveLast = '';		
		var mce = typeof(tinyMCE) != 'undefined' ? tinyMCE.activeEditor : false, title, content;
		if (mce) {
			mce.setContent(mce.getContent() + '&nbsp;'); // bitch slap tinyMCE in to thinking that it's been modified
		}
	};
	
	cfctPrepMediaGallery = function(moveTo) {
		var mediaButtons = $('#media-buttons,#wp-content-media-buttons');
		switch (moveTo) {
			case 'wordpress':
				$('#editor-toolbar,#wp-content-editor-tools').append(mediaButtons);
				break;
			case 'build':
				$('#build-editor-toolbar').append(mediaButtons);
				$('#editor-toolbar,#wp-content-editor-tools').show();
				break;
		}
	};

// Messages
	window.cfct_messenger = {};

	cfct_messenger.opts = {
		messages:{},
		current_message:'',
		last_message:'',
		message_div:'#cfct-build-messages',
		message_timeout:'5000',
		message_timeout_id:'',
		message_type_classes:{
			info:'cfct-message-info',
			warning:'cfct-message-warning',
			error:'cfct-message-error',
			confirm:'cfct-message-confirm',
			loading:'cfct-message-loading'
		}
	};

	cfct_messenger.setMessage = function(message,type,expire) {
		clearTimeout(this.message_timeout_id);
		
		$(this.opts.message_div)
			.attr('class',this.opts.message_type_classes[type || 'info'])
			.html('<span class="cfct-message-content">' + message + '</span>');
		
		if (expire !== false) {
			this.setExpire(this.opts.message_timeout);
		}
	};

	cfct_messenger.setLoading = function(message) {
		var _message_text = message || 'Loading.';
		this.setMessage(_message_text,'loading',false);
	};

	cfct_messenger.clearMessage = function() {
		var _tgt = $(cfct_messenger.opts.message_div);
		_tgt.children('span.cfct-message-content').fadeOut('fast',function(){
			_tgt.attr('class','').html('');
		});
	};

	cfct_messenger.setExpire = function(timeout) {
		this.opts.message_timeout_id = setTimeout(this.clearMessage, timeout || this.opts.message_timeout);
	};
	
// Builder
	window.cfct_builder = {};
	
	cfct_builder.opts = {
		ajax_url:ajaxurl, // ajaxurl is pre-defined by wp-admin for autosave purposes... we may or may not want to define this ourselves
		dialogs:{},
		sortables:{
			sender:null,
			receiver:null
		},
		moduleSortables:null,
		DOMWindow_defaults:{
			windowSourceID:'#cfct-popup-placeholder',
			overlay:0,
			borderSize:0,
			windowBGColor:'none',
			windowPadding: 0,
			positionType:'centered',
			width:800, // static at 800
			height:650, // gets updated at box generation
			overlay:1,
			overlayOpacity:'65',
			modal:1
		},
		row_handle:'.cfct-row-handle',
		module_save_callbacks:{},
		module_load_callbacks:{},
		rich_text:true,
		popup_content_width:0,
		popup_content_height:0,
		sortableDefaults:{
			items:'.cfct-module',
			opacity:0.4,
			placeholder:'cfct-module-draggable-placeholder',
			helper: 'clone',
			revert: 150,
			forcePlaceholderSize:true
		},
		rowTriggerSelector: '#cfct-sortables-add',
		dialog_selectors: {
			popup: '#cfct-popup',
			delete_row: '#cfct-delete-row',
			delete_module: '#cfct-delete-module',
			edit_module: '#cfct-edit-module',
			add_module: '.cfct-popup.cfct-add-module',
			error_dialog: '#cfct-error-notice',
			reset_build: '#cfct-reset-build',
			save_template: '#cfct-save-template'
		}
	};
	
// DOMWindow Helpers
	// recalculate the DOMWindow width, no smaller than 500
	// cfct_builder.DOMWindowWidth = function() {
	// 	var w = $(window).width()*0.6;
	// 	return w < 500 ? 500 : w;
	// };

	// recalculate DOMWindow height
	cfct_builder.DOMWindowHeight = function() {
		return $(window).height()*0.8;
	};

	cfct_builder.resizeDOMWindow = function() {
		//this.opts.DOMWindow_defaults.width = this.DOMWindowWidth();
		this.opts.DOMWindow_defaults.height = this.DOMWindowHeight();
	};
	cfct_builder.resizeDOMWindow();
	
	cfct_builder.showLoadingDialog = function() {
		if ($('#DOMWindow').not(':visible')) {
			$(this.opts.dialogs.popup_wrapper).html(cfct_builder.spinner());
			$.openDOMWindow(this.opts.DOMWindow_defaults);
		}
	};
	
// Welcome
	cfct_builder.showWelcome = function() {
		var _rowchooser = $('#cfct-sortables-add-container');

		if (!cfct_builder.hasRows()) {
			cfct_builder.opts.welcome_removed = false;
		
			var _welcome = $('#cfct-welcome-chooser');
		
			$('#cfct-welcome-faux-bottom-rows,#cfct-welcome-splash', _welcome).show();
			_rowchooser.hide();
			_welcome.show();

			// choose build
			$('#cfct-start-build').unbind().click(function() {
				$('body').trigger('click');
				$('#cfct-welcome-splash').hide();
				$('#cfct-welcome-faux-bottom-rows').slideUp('normal', function() {
					_rowchooser.fadeIn('fast', function () {
						cfct_builder.$rowTrigger.data('popover').show();
					});
				});
				return false;
			});
		
			// choose template
			$('#cfct-start-template-chooser').unbind().click(function() {
				$('body').trigger('click');
				$('#cfct-welcome-splash').hide();
				$('#cfct-welcome-templates').show();
				return false;
			});
			$('#cfct-choose-template-cancel').unbind().click(function() {
				$('body').trigger('click');
				$('#cfct-welcome-templates').hide();
				$('#cfct-welcome-splash').show();
				return false;
			});
		
			// add template
			$('#cfct-welcome-templates li a.cfct-template-name').click(function() {
				$('body').trigger('click');
				var _this = $(this);
				cfct_builder.insertTemplate(_this.attr('href').slice(_this.attr('href').indexOf('#')+1));
				return false;
			});
		}
		else {
			_rowchooser.show();
		}
		return true;
	};
	
	cfct_builder.hideWelcome = function() {
		var _welcome = $('#cfct-welcome-chooser');
		_welcome.slideUp('fast');
		cfct_builder.opts.welcome_removed = true;
	};
	
// Template insert
	cfct_builder.insertTemplate = function(template_id) {
		if (jQuery('#post_ID').val() < 0) {
			cfct_builder.initPost('insertTemplate',template_id);
			return false;
		}		
		cfct_builder.fetch('insert_template', { 'template_id':template_id }, 'insert-template-response');
		return true;
	};

	$(cfct_builder).bind('insert-template-response', function(evt, res) {
		if (!res.success) {
			return false;
		}

		if (!cfct_builder.opts.welcome_removed) {
			cfct_builder.hideWelcome();
		}

		$('#cfct-sortables', cfct_build).append($(res.html)).sortable('refresh');
		$('#cfct-choose-template').slideUp('normal', function() {
			cfct_builder.$rowTrigger.data('popover').show();
			$('#cfct-sortables-add-container').slideDown();
		});
		cfct_builder.bindClickables();
		cfct_builder.toggleOptionsMenu('show');
		return true;
	});
	
// Editing helper
	cfct_builder.editing_items = {
		row_id:null,
		block_id:null,
		module_id:null,
		module_name:null,
		parent_module_id:null,
		parent_module_id_base:null
	};

	cfct_builder.editing = function(params) {
		if (params === 0) {
			// reset
			for (i in this.editing_items) {
				this.editing_items[i] = null;
			}
		}
		else {
			// add to
			for (i in params) {
				this.editing_items[i] = params[i];
			}
		}
		return true;
	};
	
// Builder Ajax	
	cfct_builder.fetch = function(fn, data, successTrigger, beforeTrigger, successCallback) {
		data.post_id = $('#post_ID').val();
		opts = {
			url:this.opts.ajax_url,
			type:'POST',
			async:true,
			cache:false,
			dataType:'json',
			data:{
				action:'cfbuild_fetch',
				func:fn,
				args:(typeof Prototype == 'object' ? Object.toJSON(data) : JSON.stringify(data)) // prototype.js fix - use Prototype's JSON encoder if present - it conflicts with json2.js
			},
			beforeSend: function(request) {
				$(cfct_builder).trigger(beforeTrigger || 'ajaxDoBefore',request);
				return; 
			},
			success: function(response) {
				$(cfct_builder).trigger(successTrigger || 'ajaxSuccess',response);
				if (typeof successCallback == 'function') {
					successCallback.call(this, response);
				}
				return; 
			},
			error: function(xhr,textStatus,e) {
				switch(textStatus) {
					case 'parsererror':
						var _errstring = $('<pre />').text(xhr.responseText);
						var _html = '<p><b>Parse Error in data returned from server</b>' +
									' <a href="#" onclick="cfct_builder.toggleAjaxErrorString(); return false">toggle</a></p>' +
									'<pre class="cfct-ajax-error-string" style="display: none;">' + _errstring.html() + '</pre>';
						cfct_builder.doError({
							html:_html,
							message: 'parsererror'
						});
						break;
					default:
						cfct_builder.doError({
							html:'<b>Invalid response from server during Ajax Request</b>',
							message:'invalidajax'
						});
				}
				return; 
			}
		};
		$.ajax(opts);
	};

// Error Processing	
	cfct_builder.doError = function(ret, callback) {
		$('#cfct-error-notice-message',this.opts.dialogs.error_dialog).html(ret.html);
		this.opts.dialogs.popup_wrapper.html(this.opts.dialogs.error_dialog);
		
		if ($('#DOMWindow').not(':visible')) {
			$.openDOMWindow(this.opts.DOMWindow_defaults);
		}		
		this.prepErrorActions(callback);
				
		return true;
	};
	
	cfct_builder.prepErrorActions = function(callback) {
		$('#cfct-error-notice-close',this.opts.dialogs.error_dialog).click(function() {
			if (callback) {
				callback.apply();
			}
			
			$.closeDOMWindow();
			return false;
		});
		return true;
	};
	
	cfct_builder.toggleAjaxErrorString = function() {
		$('.cfct-ajax-error-string').slideToggle();
	};

// Reordering Rows	
	cfct_builder.updateRowOrder = function(event,ui) {
		var items = $('#cfct-sortables').sortable('toArray');
		cfct_builder.fetch('reorder_rows',{
			order:items.toString()
		},'reorder-rows-response');
	};
	
	$(cfct_builder).bind('reorder-rows-response',function(evt,result) {
		if (!result.success) {
			cfct_builder.doError(ret);
			return false;
		}
		cfct_messenger.setMessage('Row Order Updated','confirm');
		return true;
	});

// Reordering Modules
	cfct_builder.initBlockSortables = function() {
		this.opts.moduleSortables = $('.cfct-block-modules').each(function() {
			var _this = $(this);
			if (_this.hasClass('ui-sortable')) {
				_this.sortable('destroy');
			}
			_this.sortable($.extend(
				cfct_builder.opts.sortableDefaults, 
				{
					remove:function() {
						cfct_builder.opts.sortables.sender = this;
					},
					receive:function() {
						cfct_builder.opts.sortables.receiver = this;
					},
					stop:cfct_builder.updateModuleOrderEnd,
					connectWith:'#cfct-sortables .cfct-block-modules'
				}
			));
		});
		this.enableSortables();
	};
	
	cfct_builder.updateModuleOrderEnd = function() {
		cfct_builder.disableSortables();
		
		var blocks = {};
		$('.cfct-block-modules.ui-sortable').each(function(){
			var _this = $(this);
			blocks[_this.parents('td').attr('id')] = _this.sortable('toArray');
		});

		cfct_builder.fetch('reorder_modules',{
			order:blocks
		},'reorder-modules-response');

	};
	
	$(cfct_builder).bind('cfctAjaxError', function(responseText) {
		cfct_builder.enableSortables();
	});
	
	$(cfct_builder).bind('reorder-modules-response',function(evt,result) {
		cfct_builder.enableSortables();

		if (!result.success) {
			cfct_builder.doError(result, function() {
				$(cfct_builder.opts.sortables.sender).sortable('cancel');
			});
			return false;
		}
		
		cfct_messenger.setMessage('Module Order Updated','confirm');
		return true;
	});

	cfct_builder.disableSortables = function() {
		cfct_builder.opts.moduleSortables.sortable('disable');
		$('#cfct-sortables, .cfct-popup .multi-module-form').append(
			$('<div class="cfct-reorder-status">')
				.append($('<div class="cfct-reorder-status-wrapper" />')
					.css({
						'padding-top':($('#cfct-sortables').height() / 3) + 'px',
						'height':$('#cfct-sortables').height() + 'px'
					})
					.append('<div class="cfct-reorder-overlay" />', cfct_builder.spinner())
				)
		);
	};
	
	cfct_builder.enableSortables = function() {
		cfct_builder.opts.moduleSortables.sortable('enable');
		$('#cfct-sortables .cfct-reorder-status, .cfct-popup .cfct-reorder-status').remove();
	};

// Add Row Functions
	cfct_builder.insertRow = function(row_type) {
		if (!cfct_builder.insertingRow) {
			cfct_builder.insertingRow = true;
			// this.$rowTrigger.data('popover').hide();
			if (!cfct_builder.opts.welcome_removed) {
				cfct_builder.hideWelcome();
			}
		
			return $('#cfct-loading-row').slideDown('fast',function() {
				if ($('#post_ID').val() < 0) {
					cfct_builder.initPost('insertRow',row_type);
					return false;
				}
				cfct_builder.fetch('new_row',{type:row_type},'do-insert-row');
				cfct_builder.insertingRow = false;
				return true;
			});
		}
	};
	
	$(cfct_builder).bind('do-insert-row',function(evt,row) {
		if (!row.success) {
			cfct_builder.doError(row);
			return false;
		}
		
		$('#cfct-loading-row').hide();
		$('#cfct-sortables',cfct_build).append($(row.html)).sortable('refresh');
		
		cfct_builder.bindClickables();
		$('#cfct-build').removeClass('new');
	
		cfct_messenger.setMessage('Row Saved','confirm');
		$(cfct_builder).trigger('new-row-inserted', row);
		return true;	
	});

// Remove Row Functions	
	cfct_builder.confirmRemoveRow = function(row) {
		$('#cfct-delete-row-id',this.opts.dialogs.delete_row).val(row.attr('id'));
		
		// pop dialog
		this.opts.dialogs.popup_wrapper.html(this.opts.dialogs.delete_row);
		$.openDOMWindow(this.opts.DOMWindow_defaults);

		// bind actions
		$('#cfct-delete-row-confirm',this.opts.dialogs.delete_row).click(function() {
			cfct_builder.doRemoveRow( $('#'+$('#cfct-delete-row-id').val()) );
		});
		$('#cfct-delete-row-cancel',this.opts.dialogs.delete_row).click(function() {
			$.closeDOMWindow();
			return false;
		});
		$(cfct_builder).trigger('confirm-remove-row');
	};
	
	cfct_builder.doRemoveRow = function(row) {
		if (!cfct_builder.removingRow) {
			cfct_builder.removingRow = true;
			var _row = $(row);
			cfct_builder.editing({
				'row_id':_row.attr('id')
			});
			cfct_builder.showPopupActivityDiv(cfct_builder.opts.dialogs.delete_row);
		
			var data = {
				row_id:_row.attr('id')
			};
			cfct_builder.fetch('delete_row',data,'do-remove-row-response');
		}
	};
	
	$(cfct_builder).bind('do-remove-row-response',function(evt,ret) {
		if (!ret.success) {
			cfct_builder.doError(ret);
			return false;
		}
		$('#cfct-sortables #' + cfct_builder.editing_items.row_id,cfct_build).slideUp('fast',function() {
			$(this).remove();			
			$.closeDOMWindow();
			cfct_builder.hidePopupActivityDiv(cfct_builder.opts.dialogs.delete_row);
			$(cfct_builder).trigger('row-removed');
		});
		
		cfct_messenger.setMessage('Row Deleted','confirm');
		cfct_builder.editing(0);
		cfct_builder.removingRow = false;
		return true;
	});

// Template Save
	cfct_builder.saveAsTemplate = function() {
		$(this.opts.dialogs.save_template).find('input[type="text"],textarea').val('');
		$(this.opts.dialogs.popup_wrapper).html(this.opts.dialogs.save_template);
		$.openDOMWindow(this.opts.DOMWindow_defaults);

		$('.cancel',this.opts.dialogs.save_template).click(function() {
			cfct_builder.editing(0);
			$.closeDOMWindow();
			return false;
		});
		$('input[type="submit"]',cfct_builder.opts.dialogs.popup_wrapper).unbind().click(function(){
			$(this).parents('form').submit();
			return false;
		});
		$('#cfct-save-template-form').unbind().submit(function(){
			cfct_builder.submitTemplateForm($(this));
			return false;
		});
		
		return true;
	};
	
	cfct_builder.submitTemplateForm = function(form) {
		var _formdata = $(form).serialize();
		cfct_builder.fetch('save_as_template',{
			'data':_formdata
		},'save-template-response');	
	};
	
	$(cfct_builder).bind('save-template-response',function(evt,ret) {
		if (!ret.success) {
			cfct_builder.doError(ret);
			return false;
		}

		cfct_builder.bindClickables();
		cfct_builder.editing(0);
		$.closeDOMWindow();
		cfct_messenger.setMessage('Template Saved','confirm');
		return true;		
	});

// Add Module functions	
	cfct_builder.selectModule = function() {
		// IE has a habit of wiping this out, reload if necessary, this is a big WTF?!?!
		if (cfct_builder.opts.dialogs.add_module.html().length == 0) {
			cfct_builder.loadDialog('add_module', true);
		}
		
		this.opts.dialogs.popup_wrapper.html(this.opts.dialogs.add_module);
		this.opts.dialogs.add_module.find('div.cfct-popup-content').css({'max-height':parseInt(this.DOMWindowHeight()-(45+25+14+20), 10),'overflow':'auto'});
		
		if ($('#DOMWindow').not(':visible')) {
			$.openDOMWindow(this.opts.DOMWindow_defaults);
		}
		$('#DOMWindow').css({'overflow':'visible'});
		
	};
	
// Edit Module Functions
	cfct_builder.editModule = function(extra_data, callback) {
		var data = {
			'module_type': this.editing_items.module_name,
			'module_id': this.editing_items.module_id,
			'block_id': this.editing_items.block_id,
			'row_id': this.editing_items.row_id,
			'parent_module_id': this.editing_items.parent_module_id, // used for sideloading, will be null if not sideloading
			'parent_module_id_base': this.editing_items.parent_module_id_base, // used for sideloading, will be null if not sideloading
			'max-height': parseInt(this.DOMWindowHeight()-(45+25+14+70), 10) // subtract fudged header, fudged footer, border width, plus safety number to get max body height
		};
		$.extend(data, extra_data || {});

		$(cfct_builder).trigger('edit-module', data);
		
		cfct_builder.fetch('edit_module', data, callback || 'edit-module-response');
		return true;
	};
	
	$(cfct_builder).bind('edit-module-response', function(evt, ret) {		
		if (!ret.success) {
			cfct_builder.doError(ret);
			return false;
		}
	
		if ($('#DOMWindow').not(':visible')) {
			$.openDOMWindow(cfct_builder.opts.DOMWindow_defaults);
		}
		
		$('#DOMWindow').css({'overflow':'visible'});		
		
		cfct_builder.hidePopupActivityDiv(cfct_builder.opts.dialogs.add_module);
		cfct_messenger.clearMessage();
			
		$(cfct_builder.opts.dialogs.popup_wrapper).html(ret.html);
		
		var _form = cfct_builder.getFormForModuleLoadCallback(cfct_builder.opts.dialogs.popup_wrapper);

		cfct_builder.doModuleLoadCallback(_form);
		cfct_builder.setFormEditing(_form);
		
		return true;
	});
	
	cfct_builder.setFormEditing = function(form, editing_data) {
		form.find('.cfct-button-action').data('editing', $.extend({}, editing_data || cfct_builder.editing_items));
		cfct_builder.editing(0);
	};
	
	cfct_builder.getFormEditing = function(form) {
		return form.find('.cfct-button-action').data('editing') || {};
	};
	
	cfct_builder.submitModuleForm = function(form, extra_data, callback) {
		if (new Date().getTime() < this.submitted_at + 500) {
			return false;
		}
		this.submitted_at = new Date().getTime();

		cfct_messenger.clearMessage();
		cfct_builder.showPopupActivityDiv(form);

		// Protect against saving if we didn't successfully store the module location data
		if (cfct_builder.editing_items.row_id != null || cfct_builder.editing_items.block_id != null) {
			var _form_editing = this.getFormEditing(form);
			for (key in cfct_builder.editing_items) {
				if (cfct_builder.editing_items.hasOwnProperty(key)) {
					_form_editing[key] = _form_editing[key] || cfct_builder.editing_items[key];
				}
			}
			this.setFormEditing(form, _form_editing);
		}

		if (false === cfct_builder.doModuleSaveCallback(form)) {
			cfct_builder.hidePopupActivityDiv(this.opts.dialogs.popup_wrapper);
			return false;
		}
		
		var _form = $(form);
		var _formdata = _form.serialize();
		var _editing = this.getFormEditing(_form);
		
		var data = {
			'data': _formdata,
			'row_id': _editing.row_id,
			'block_id': _editing.block_id,
			'module_type': $(':input[name=module_type]', _form).val(),
			'module_id': _editing.module_id,
			'parent_module_id': _editing.parent_module_id,
			'parent_module_id_base': _editing.parent_module_id_base
		};
		$.extend(data, extra_data || {});
		
		this.fetch('save_module', data, callback || 'submit-module-form-response');
		return true;
	};
	
	$(cfct_builder).bind('submit-module-form-response',function(evt,ret) {
		if (!ret.success) {
			cfct_builder.doError(ret);
			return false;
		}
		
		this.insertModule(ret.html, $('#' + ret.block_id + ' .cfct-block-modules'), ret.module_id);
		
		this.bindClickables();
		this.editing(0);
		cfct_messenger.setMessage('Module Saved','confirm');
		$.closeDOMWindow();
		
		cfct_builder.hidePopupActivityDiv(this.opts.dialogs.popup_wrapper);
		return true;
	});
	
	/** 
	 * insert a module `html` in to a block `target`
	 * if `module_id` not empty then an existing module will be replaced
     */
	cfct_builder.insertModule = function(html, target, module_id) {
		if (module_id === null || $('#' + module_id, target).size() < 1) {
			$(target).append(html);
		}
		else {
			$('#' + module_id, target).replaceWith(html);
		}
		
		var _row = target.closest('.cfct-row');
		if (_row.hasClass('cfct-row-empty')) {
			_row.removeClass('cfct-row-empty');
		}
		
		target.trigger('add-module', [module_id]);
		
		return true;
	};
	
// Sideload Module functions

	// request the module chooser in to the sideload div
	cfct_builder.sideloadSelectModule = function() {
		// Already loaded? Show it.
		if ($('.cfct-module-sideload ul#cfct-module-list', this.opts.dialogs.popup_wrapper).size() > 0) {
			cfct_builder.sideloadOpen();
		}
		// Otherwise AJAX it!
		else {
			this.sideloadSetLoading();
			data = {
				'module_type': this.editing_items.module_name,
				'module_id': this.editing_items.module_id,
				'sideload': true
			};
			this.fetch('sideload_module_chooser', data, 'sideload-module-chooser-response');
		}
		return false;
	};
	
	// load the module chooser in to the sideload and display
	$(cfct_builder).bind('sideload-module-chooser-response', function(evt, ret) {
		if (!ret.success) {
			cfct_builder.doError(ret);
			return false;
		}
		
		cfct_builder.sideloadSetContent(ret.html);
		cfct_builder.sideloadOpen();
		
		return true;
	});
	
	// cancel selecting a module
	cfct_builder.sideloadCancelSelectModule = function() {
		cfct_builder.sideloadClose();
	};
	
	// load in response for a module admin form
	$(cfct_builder).bind('sideload-edit-module-response', function(evt, ret) {
		// handle error
		if (!ret.success) {
			cfct_builder.doError(ret);
			return false;
		}
		
		cfct_messenger.clearMessage();
		cfct_builder.sideloadSetContent(ret.html);
		
		var sideloader = $('.cfct-module-sideload', cfct_builder.opts.dialogs.popup_wrapper);
		
		var _form = cfct_builder.getFormForModuleLoadCallback(sideloader);
		
		cfct_builder.doModuleLoadCallback(_form);
		cfct_builder.setFormEditing(_form);
		
		if (cfct_builder.sideloadIsVisible() == false) {
			cfct_builder.sideloadOpen();
		}
		
		return false;
	});
	
	// act on save-module response data
	$(cfct_builder).bind('sideload-submit-module-form-response', function(evt, ret) {
		if (!ret.success) {
			cfct_builder.doError(ret);
			return false;
		}

		this.insertModule(ret.html, $('.cfct-block-modules', this.opts.dialogs.popup_wrapper), ret.module_id);
		
		cfct_builder.sideloadClose();
		return true;
	});
	
	// make sure the user really wants to delete the module
	cfct_builder.sideloadConfirmRemoveModule = function() {
		cfct_builder.sideloadSetContent(this.opts.dialogs.delete_module);
		cfct_builder.sideloadOpen();
	};
	
	// set the sideload status to display a loading spinner
	cfct_builder.sideloadSetLoading = function() {
		$('.cfct-module-sideload', this.opts.dialogs.popup_wrapper).html($('<div class="cfct-loading">Loading&hellip;</div>'));	
	};
	
	// set the contents of the sideload div
	cfct_builder.sideloadSetContent = function(content) {
		var sideloader = $('.cfct-module-sideload', this.opts.dialogs.popup_wrapper);
		
		if ($('.cfct-popup', content).size() > 0 || $(content).hasClass('cfct-popup')) {
			content = $('.cfct-popup-inner-wrap', content).html();
		}

		sideloader.html(content);
		
		var _height = this.sideloadGetContentAreaHeight() + 'px';
		$('.cfct-popup-content', sideloader).css({
			'height': _height,
			'max-height': _height
		});
	};
	
	// get the available content area height based on the current dialog size
	cfct_builder.sideloadGetContentAreaHeight = function() {
		var popup_height = $('.cfct-popup-inner-wrap:first', this.opts.dialogs.popup_wrapper).height();
		var sideloadr = $('.cfct-module-sideload', this.opts.dialogs.popup_wrapper);
		var module_content = $('.cfct-module-form .cfct-popup-content:first', this.opts.dialogs.popup_wrapper);

		// doing this 'cause height() & friends weren't cooperating with padding
		var module_content_padding = parseInt(module_content.css('padding-top').replace('px', ''), 10) + parseInt(module_content.css('padding-bottom').replace('px', ''), 10);
		var sideload_items_heights = sideloadr.find('.cfct-popup-header').outerHeight(true) + sideloadr.find('.cfct-popup-actions').outerHeight(true);
		
		return popup_height - module_content_padding - sideload_items_heights;
	};
	
	// open the sideload div
	cfct_builder.sideloadOpen = function(callback) {
		var sideloadr = $('.cfct-module-sideload', this.opts.dialogs.popup_wrapper);
		
		// // make sure our dimensions are correct
		var popup_height = $('.cfct-popup-inner-wrap', this.opts.dialogs.popup_wrapper).height();
		var popup_width = $('.cfct-popup-inner-wrap', this.opts.dialogs.popup_wrapper).width();
		
		// display
		sideloadr.css({'height': popup_height, 'width': popup_width, 'left': -popup_width})
			.find('.cfct-popup-content')
			.end()
			.animate({left: 0}, 'fast', callback || function() {});
	};
	
	// slide close the sideload div
	cfct_builder.sideloadClose = function(callback) {
		var popup_width = $('.cfct-popup-inner-wrap', cfct_builder.opts.dialogs.popup_wrapper).width();
		$('.cfct-module-sideload', cfct_builder.opts.dialogs.popup_wrapper).animate({left: -popup_width}, 'normal', callback || function(){});
	};
	
	// sniff wether the sideloader is off to the side or not
	cfct_builder.sideloadIsVisible = function() {
		if ($('.cfct-module-sideload', this.opts.dialogs.popup_wrapper).position().left < 0) {
			return false;
		}
		else {
			return true;
		}
	};
	
// Module Advanced Options

	cfct_builder.toggleModuleOptions = function(clicked) {
		var mo_prnt = $(clicked).closest('.cfct-popup-header .cfct-build-module-options');
		if (mo_prnt.hasClass('cfct-build-options-active')) {
			mo_prnt.removeClass('cfct-build-options-active');
		}
		else {
			mo_prnt.addClass('cfct-build-options-active');
		}
	};
	
	cfct_builder.moduleOptionsSliderShowHide = function(clicked) {
		var _this = $(clicked);
		var _wrapper = _this.closest('.cfct-popup-header').next('.cfct-module-form').find('div.cfct-popup-advanced-actions');
		var _tgt = $('#' + _this.attr('href').slice(_this.attr('href').indexOf('#')+1), _wrapper);

		if (_wrapper.is(':visible')) {
			if (!_tgt.is(':visible')) {
				_wrapper.slideUp(function() {
					cfct_builder.moduleOptionsItemShowHide(_tgt, _wrapper);				
				});
			}
		}
		else {
			cfct_builder.moduleOptionsItemShowHide(_tgt, _wrapper);
		}
	};
	
	cfct_builder.moduleOptionsSlideClose = function() {
		$('.cfct-module-edit-form').find('.cfct-popup-advanced-actions').slideUp();
	};

	cfct_builder.moduleOptionsItemShowHide = function(_tgt, _wrapper) {
		_tgt.css({'display':'block'}).siblings().css({'display':'none'});
		_wrapper.slideDown();
	};

// Remove Module Functions
	cfct_builder.confirmRemoveModule = function() {
		this.opts.dialogs.popup_wrapper.html(this.opts.dialogs.delete_module);
		$.openDOMWindow(this.opts.DOMWindow_defaults);
	};
	
	$(cfct_builder).bind('remove-module-response', function(evt, ret) {
		if (!ret.success) {
			cfct_builder.doError(ret);
			return false;
		}

		$('#' + ret.module_id).slideUp(function() {
			var _this = $(this);
			var _block = _this.closest('.cfct-block');
			var _module_id = _this.attr('id');
			_this.remove();
			_block.trigger('remove-module', [_module_id]);
			if ($('.cfct-module-sideload', cfct_builder.opts.dialogs.popup_wrapper).size() > 0) {
				cfct_builder.sideloadClose();
			}
			else {
				cfct_builder.hidePopupActivityDiv(cfct_builder.opts.dialogs.delete_module);
				$.closeDOMWindow();
			}
		});

		cfct_messenger.setMessage('Module Deleted', 'confirm');
		return true;
	});

// Reset Build Functions
	cfct_builder.confirmResetTemplate = function() {
		this.opts.dialogs.popup_wrapper.html(this.opts.dialogs.reset_build);
		$.openDOMWindow(this.opts.DOMWindow_defaults);
		
		$('#cfct-reset-build-confirm', this.opts.dialogs.reset_build).click(function() {
			cfct_builder.doResetBuild();
		});

		$('.cancel', this.opts.dialogs.reset_build).click(function() {
			$.closeDOMWindow();
			return false;
		});
	};
	
	cfct_builder.doResetBuild = function() {
		cfct_builder.showPopupActivityDiv(cfct_builder.opts.dialogs.reset_build);
		
		$.closeDOMWindow();
		$('#cfct-sortables').slideUp('normal',function(){
			cfct_builder.fetch('reset_build', {}, 'reset-template-response');
		});
	};
	
	$(cfct_builder).bind('reset-template-response', function(evt,ret) {
		if (!ret.success) {
			cfct_builder.doError(ret);
			return false;
		}
		
		$('#cfct-sortables').children('.cfct-row').remove().end().slideDown('normal', function() {
			$(cfct_builder).trigger('row-removed');
			cfct_builder.showWelcome();
		});

		return true;
	});
// Module enable/disable callback
	$(cfct_builder).bind('toggle-render-response', function(evt, ret) {
		$('#' + ret.module_id).find('.cfct-module-status').remove();
		if (!ret.success) {
			ret.html = ret.html + "<p>Could not toggle render state.</p>";
			cfct_builder.doError(ret);
			return false;
		}
		var _this = $('#' + ret.module_id + ' a.cfct-module-toggle-render');
		_this.html(ret.html);
		return true;
	});

// Module Callbacks
	cfct_builder.doModuleSaveCallback = function(form) {
		var _form = $(form);
		if (cfct_builder.hasModuleSaveCallback(_form.attr('name'))) {
			$.each(
				cfct_builder.opts.module_save_callbacks[_form.attr('name')],
				function(i, func) {
					try {
						var _ret = func.call(null, _form);
						if (!_ret) {
							return false;
						}
					}
					catch (e) {
						if (window.console && console.log) {
							console.log(e);
						}
						return false;
					}
				}
			);
		}
		return true;
	};
	
	cfct_builder.addModuleSaveCallback = function(id,func) {
		if (!(cfct_builder.opts.module_save_callbacks[id] instanceof Array)) {
			cfct_builder.opts.module_save_callbacks[id] = [];
		}
		cfct_builder.opts.module_save_callbacks[id].push(func);
	};
	
	cfct_builder.hasModuleSaveCallback = function(formName) {
		return (formName in cfct_builder.opts.module_save_callbacks);
	};
	
	cfct_builder.doModuleLoadCallback = function(form) {
		// do module specific callbacks
		var _form = $(form);
		if (_form.attr('name') in cfct_builder.opts.module_load_callbacks) {
			$.each(
				cfct_builder.opts.module_load_callbacks[_form.attr('name')],
				function(i, func) {
					try {
						func.call(null, _form);
					}
					catch (e) {
						if (window.console && console.log) {
							console.log(e);
						}
						return false;
					}
				}
			);
		}
		
		// do the "all module" callbacks
		if ('*' in cfct_builder.opts.module_load_callbacks) {
			$.each(
				cfct_builder.opts.module_load_callbacks['*'],
				function(i, func) {
					try {
						func.call(null, _form);
					}
					catch (e) {
						if (window.console && console.log) {
							console.log(e);
						}
						return false;
					}
				}
			);
		}
		
		// init sortables for multi-modules
		_form.find('.cfct-block-modules').each(function() {
			var _this = $(this);
			if (_this.hasClass('ui-sortable')) {
				_this.sortable('destroy');
			}
			_this.sortable($.extend(
				cfct_builder.opts.sortableDefaults,
				{
					'remove':null,
					'receive':null,
					'stop':cfct_builder.updateModuleOrderEnd,
					'connectWith':false
				}
			));
		});
		return true;
	};
	
	cfct_builder.addModuleLoadCallback = function(id, func) {
		if (!(cfct_builder.opts.module_load_callbacks[id] instanceof Array)) {
			cfct_builder.opts.module_load_callbacks[id] = [];
		}
		cfct_builder.opts.module_load_callbacks[id].push(func);
	};
	
	// helper function to make sure that it gets done constently
	cfct_builder.getFormForModuleLoadCallback = function(html) {
		return $('form', html);
	};
	
// module activity display
	cfct_builder.showPopupActivityDiv = function(tgt) {
		$('.cfct-dialog-activity',tgt).show();
	};
	
	cfct_builder.hidePopupActivityDiv = function(tgt) {
		$('.cfct-dialog-activity',tgt).hide();
	};
	
// Build Options
	cfct_builder.toggleOptions = function() {
		var _prnt = $('#cfct-build-header .cfct-build-options');
		if (_prnt.hasClass('cfct-build-options-active')) {
			_prnt.removeClass('cfct-build-options-active');
		}
		else {
			_prnt.addClass('cfct-build-options-active');
		}
	};
	
// Dialogs
	cfct_builder.initDialogs = function() {
		// main popup & wrapper
		this.loadDialog('popup');
		this.opts.dialogs.popup_wrapper = $('#cfct-popup-inner',this.opts.dialogs.popup);
		// delete row
		this.loadDialog('delete_row');
		// delete module
		this.loadDialog('delete_module');
		// edit module
		this.loadDialog('edit_module');
		// add module, clone because it needs to be loaded multiple times because of a weird IE bug
		this.loadDialog('add_module', true);
		// error dialog
		this.loadDialog('error_dialog');
		// reset build
		this.loadDialog('reset_build');
		// save template dialog
		this.loadDialog('save_template');
		$('.cfct-module-form',this.opts.dialogs.save_template).wrap('<form id="cfct-save-template-form" name="cfct-save-template-form" />');
	};
	
	// generic dialog loader
	cfct_builder.loadDialog = function(dialog_key, clone) {
		if (dialog_key in cfct_builder.opts.dialog_selectors) {
			if (clone == true) {
				this.opts.dialogs[dialog_key] = $(cfct_builder.opts.dialog_selectors[dialog_key]).clone();
			}
			else {
				this.opts.dialogs[dialog_key] = $(cfct_builder.opts.dialog_selectors[dialog_key]);
			}
		}
	};
	
// Actions
	cfct_builder.bindClickables = function() {
		// init block sortables
		cfct_builder.initBlockSortables();
		return true;
	};
	
	cfct_builder.bindLiveClickables = function() {
		var _that = this;
		if (!this.$rowTrigger.data('popover')) {
			this.$rowTrigger.popover({
				my: 'center top',
				at: 'center bottom',
				offset: '0 1px'
			});
		}
		// Rows
			// select row to insert buttons
			$('#cfct-select-new-row .cfct-il-a').live('click', function(e) {
				_that.$rowTrigger.data('popover').hide();
				cfct_builder.insertRow($(this).attr('rel'));
				return false;
			});
			// delete row buttons
			$('#cfct-sortables .cfct-row-delete').live('click', function() {
				var _this = $(this);
				if (_this.parents('.cfct-row').find('div.cfct-module').length == 0) {
					cfct_builder.doRemoveRow(_this.parents('.cfct-row'));
				}
				else {
					cfct_builder.confirmRemoveRow(_this.parents('.cfct-row'));
				}
				return false;
			});
			
		// new module
			// standard new button
			$('a.cfct-add-new-module').live('click', function() {
				var _this = $(this);

				cfct_builder.editing({
					'block_id':_this.attr('href').slice(_this.attr('href').indexOf('#')+1),
					'row_id':_this.parents('.cfct-row').attr('id')
				});
								
				if (_this.closest('.multi-module-form').size() > 0) {
					cfct_builder.sideloadSelectModule();					
				}
				else {			
					cfct_builder.selectModule();
				}
				return false;
			});
			
			// cancel new module
			$('#cfct-add-module-cancel').live('click', function() {
				if ($(this).closest('.cfct-module-sideload').size() > 0) {
					cfct_builder.sideloadClose(function() {
						cfct_builder.sideloadSetContent('');
					});
				}
				else {
					cfct_builder.editing(0);
					$.closeDOMWindow();
				}
				return false;
			});
		
		// remove module actions
			$('a.cfct-module-clear').live('click', function() {
				var _this = $(this);
				var clear_module_data = {
					'module_id':_this.attr('href').slice(_this.attr('href').indexOf('#')+1),
					'block_id':_this.parents('.cfct-block').attr('id'),
					'row_id':_this.parents('.cfct-row').attr('id')
				};
				
				if (_this.closest('.multi-module-form').size() > 0) {
					cfct_builder.editing({
						'parent_module_id': $('.multi-module-form input[name="module_id"]', cfct_builder.opts.dialogs.popup_wrapper).val(),
						'parent_module_id_base': $('.multi-module-form input[name="module_id_base"]', cfct_builder.opts.dialogs.popup_wrapper).val()
					});
					cfct_builder.sideloadConfirmRemoveModule();
					cfct_builder.setFormEditing($('.cfct-module-sideload', cfct_builder.opts.dialogs.popup_wrapper), clear_module_data);					
				}
				else {
					cfct_builder.confirmRemoveModule();
					cfct_builder.setFormEditing(cfct_builder.opts.dialogs.popup_wrapper, clear_module_data);
				}
				return false;
			});
		// Enable / disable module rendering actions
			$('a.cfct-module-toggle-render').live('click', function() {
				var _this = $(this);
				cfct_builder.module_spinner(_this);
				var module_data = {
					'module_id':_this.attr('href').slice(_this.attr('href').indexOf('#')+1),
					'block_id':_this.parents('.cfct-block').attr('id'),
					'row_id':_this.parents('.cfct-row').attr('id')
				};
				
				cfct_builder.fetch('toggle_render',
					module_data,
					'toggle-render-response'
				);
				return false;
			});

				
		// Delete Module Confirmation
			// standard delete module button
			$('#cfct-delete-module-confirm').live('click', function() {
				var _wrapper = $(this).closest('.cfct-module-form');
				var callback = 'remove-module-response';

				if (_wrapper.closest('.cfct-module-sideload').size() < 1) {
					cfct_builder.showPopupActivityDiv(cfct_builder.opts.dialogs.delete_module);
				}

				cfct_builder.fetch('delete_module', cfct_builder.getFormEditing(_wrapper), callback);
				
				return false;
			});
			
			// standard cancel delete action
			$('#cfct-delete-module-cancel').live('click', function() {
				if ($(this).closest('.cfct-module-sideload').size() > 0) {
					cfct_builder.editing({
						'sideload_module_id': null
					});
					cfct_builder.sideloadClose(function() {
						$('.cfct-module-sideload', cfct_builder.opts.dialogs.popup_wrapper).html('');
					});					
				}
				else {
					cfct_builder.editing(0);
					$.closeDOMWindow();
				}
				return false;
			});
		
		// edit module cancel	
			$('.cfct-module-form a.cancel').live('click', function() {			
				if ($(this).closest('.cfct-module-sideload').size() > 0) {
					cfct_builder.sideloadCancelSelectModule();
				}
				else {
					cfct_builder.editing(0);
					$.closeDOMWindow();
				}
					
				return false;
			});
			
		// new module selection
			$('.cfct-module-list li a.cfct-il-a').live('click', function() {
				var _this = $(this);

				if (new Date().getTime() < this.submitted_at + 500) {
					return false;
				}
				this.submitted_at = new Date().getTime();
				// block & row IDs were added by the add-module button action
				cfct_builder.editing({
					'module_name': _this.attr('href').slice(_this.attr('href').indexOf('#')+1)
				});
			
				if (_this.closest('.cfct-module-sideload').size() > 0) {
					cfct_builder.sideloadSetLoading();
					cfct_builder.editing({
						'parent_module_id': $('.multi-module-form input[name="module_id"]', cfct_builder.opts.dialogs.popup_wrapper).val(),
						'parent_module_id_base': $('.multi-module-form input[name="module_id_base"]', cfct_builder.opts.dialogs.popup_wrapper).val()
					});
					cfct_builder.editModule({'sideload': true}, 'sideload-edit-module-response');
				}
				else {
					cfct_builder.showPopupActivityDiv(cfct_builder.opts.dialogs.add_module);
					cfct_builder.editModule();
				}
				
				return false;
			});
			
		// edit module actions
			$('a.cfct-module-edit').live('click', function() {
				var _this = $(this);


				cfct_builder.editing({
					'module_id':_this.attr('href').slice(_this.attr('href').indexOf('#')+1),
					'block_id':_this.parents('.cfct-block').attr('id'),
					'row_id':_this.parents('.cfct-row').attr('id')
				});

				if (_this.closest('.multi-module-form').size() > 0) {
					cfct_builder.editing({
						'parent_module_id': $('.multi-module-form input[name="module_id"]', cfct_builder.opts.dialogs.popup_wrapper).val(),
						'parent_module_id_base': $('.multi-module-form input[name="module_id_base"]', cfct_builder.opts.dialogs.popup_wrapper).val()
					});
					cfct_builder.editModule({'sideload': true}, 'sideload-edit-module-response');					
				}
				else {
					$('body').trigger('click');
					cfct_builder.showLoadingDialog();
					cfct_builder.editModule();
				}

				return false;
			});
			
		// module form submit
			// handle form submit
			$('form.cfct-module-edit-form').live('submit', function(){
				var _this = $(this);
				if (_this.closest('.cfct-module-sideload').size() > 0) {
					callback = 'sideload-submit-module-form-response';
				}
				else {
					callback = 'submit-module-form-response';
				}
				cfct_builder.submitModuleForm(_this, {}, callback);
				return false;
			});
		
			// add action to module form submit button
			$('.cfct-module-edit-form input[type="submit"]').live('click', function(){
				$(this).closest('form').submit();
				return false;
			});
			
		// module options
			// actions menu action
			$('.cfct-build-module-options .cfct-build-options-header a').live('click', function() {
				cfct_builder.toggleModuleOptions(this);
				return false;
			});

			// take the link ID as a reference to the ID of the item that needs to be displayed
			$('.cfct-build-module-options .cfct-build-module-options-list a').live('click', function() {
				cfct_builder.toggleModuleOptions(this);
				cfct_builder.moduleOptionsSliderShowHide(this);
				return false;
			});
			
			$('div#cfct-popup-advanced-actions a.close').live('click', function() {
				cfct_builder.moduleOptionsSlideClose();
				return false;
			});
			
		// new module list toggle
		$('.cfct-module-list-toggle').live('click', function() {
			var _this = $(this);
			_tgt = $('ul.cfct-module-list');
			
			switch (_this.attr('id')) {
				case 'cfct-module-list-toggle-detail':
					_tgt.removeClass('cfct-il-mini');
					$('ul.cfct-module-list', cfct_builder.opts.dialogs.add_module).removeClass('cfct-il-mini');
					state = 'column';
					break;
				case 'cfct-module-list-toggle-compact':
					_tgt.addClass('cfct-il-mini');
					$('ul.cfct-module-list', cfct_builder.opts.dialogs.add_module).addClass('cfct-il-mini');
					state = 'icon';
					break;
			}
			// this could be more elegant, but its not
			_this.addClass('active').siblings().removeClass('active');
			$('a#' + _this.attr('id'), cfct_builder.opts.dialogs.add_module).addClass('active').siblings().removeClass('active');
			cfct_builder.fetch('content_chooser_state',{ 'state':state });
			return false;			
		});
		
		// reset layout
		$('#cfct-reset-build-data').live('click', function() {
			cfct_builder.editing(0);
			cfct_builder.toggleOptions();
			cfct_builder.confirmResetTemplate();
			return false;
		});
		
		// save layout as template
		$('#cfct-save-as-template').live('click', function() {
			cfct_builder.editing(0);
			cfct_builder.saveAsTemplate();
			cfct_builder.toggleOptions();
			return false;
		});
			
		// global "click off" handler
		$('body').click(function(e){
			if ($('#cfct-build-header .cfct-build-options .cfct-build-options-list').is(':visible')) {
				cfct_builder.toggleOptions();
			}
		});
		
		// global keypress handler
		$(document).bind('keyup', function(evt) {
			switch (evt.which) {
				case 27:
					// bind escape key to closing the DOMWindow or closing the sideloaer
					if ($('.cfct-module-sideload').size() > 0 && cfct_builder.sideloadIsVisible()) {
						cfct_builder.sideloadClose();
					}
					else if ($('#DOMWindow').is(':visible')) {
						$.closeDOMWindow();
					}
					break;
			}
			return true;
		});
		
		// proof of concept block change listeners
		// $('.cfct-block').live('add-module', function(evt, module_id) {
		// 	console.log(this);
		// 	console.log('added: ' + module_id);
		// });
		// $('.cfct-block').live('remove-module', function(evt, module_id) {
		// 	console.log(this);
		// 	console.log('removed: ' + module_id);
		// });
		
		// Global Image Search boxes
		$('.cfct-global-image-select .cfct-image-select-items-list-item').live('click', function() {
			var _this = $(this);
			_this.addClass('active').siblings().removeClass('active');

			var _wrapper = _this.parents('.cfct-global-image-select');
			
			$('input:hidden', _wrapper).val(_this.attr('data-image-id'));
			$('.cfct-global-image-search-current-image .cfct-image-select-items-list-item > div', _wrapper)
				.css( 'backgroundImage', _this.children(':first').css('backgroundImage') )
				.children(':first').text(_this.children(':first').children(':first').text());
				
			return false;
		});

		// Post image select: account for single select boxes
		$('.cfct-post-image-select.cfct-post-image-select-single .cfct-image-select-items-list-item').live('click', function() {
			var _this = $(this);

			_this.addClass('active').siblings().removeClass('active');
			_this.parents('.cfct-image-select-items-list').find('input:hidden').val(_this.attr('data-image-id'));
			
			return false;
		});

		// Post image select: account for multi-select boxes
		$('.cfct-post-image-select.cfct-post-image-select-multiple .cfct-image-select-items-list-item').live('click', function() {
			var _this = $(this);
                   
			var val = _this.closest('.cfct-image-select-items-list').find('input:hidden').val();
			var selected_images = new Array();
			if (val != 0) {
			    selected_images = val.split(',');
			}
			var key = jQuery.inArray(_this.attr('data-image-id'), selected_images);
                  
			if (_this.hasClass('active')) {
			    _this.removeClass('active');
			    if (key != -1) {
			        selected_images.splice(key, 1);
			    }
			}
			else {
			    _this.addClass('active');
			    if (key == -1) {
			        selected_images.push(parseInt(_this.attr('data-image-id'), 10));
			    }
			}
			_this.parents('.cfct-image-select-items-list').find('input:hidden').val(selected_images);

			return false;
		});
	};

// init post handler - trigger autosave and continue when post_ID has been updated
	cfct_builder.initPost = function(callback,data) {
		jQuery('#title').val($('#cfct-autosave-title').val()).blur();
		setTimeout(function() { cfct_builder.continueInitPost(callback,data); },500);
	};

	cfct_builder.continueInitPost = function(callback,data) {
		if (jQuery('#post_ID').val() < 0) {
			setTimeout(function() { cfct_builder.continueInitPost(callback,data); },500);
		}
		else {
			cfct_builder[callback](data);
		}
		return;
	};
	
// Utility
	cfct_builder.toggleOptionsMenu = function(dir) {
		var _optionsmenu = $('#cfct-build-header .cfct-build-header-group-secondary .cfct-build-options');
		switch(true) {
			case _optionsmenu.is(':visible') && dir == undefined:
			case dir == 'hide':
				func = 'hide';
				break;
			case _optionsmenu.is(':hidden') && dir == undefined:
			case dir == 'show':
				func = 'show';
				break;
		}
		_optionsmenu[func]();		
	};

	cfct_builder.hasRows = function() {
		return $('#cfct-sortables .cfct-row').size() > 0;
	};
	
	cfct_builder.spinner = function(message) {
		var _message = message || 'Loading&hellip;';
		return '<div id="cfct-spinner-dialog" class="cfct-popup"><div class="cfct-popup-spinner">' + _message + '</div></div>';
	};
	
	cfct_builder.module_spinner = function(module, message) {
		$(module).append(
			$('<div class="cfct-module-status">')
				.append($('<div class="cfct-module-status-wrapper" />')
					.css({
						'padding-top':($(module).height() / 3) + 'px',
						'height':$(module).height() + 'px'
					})
					.append('<div class="cfct-module-status-overlay" />', cfct_builder.spinner(message))
				)
		);
	};

// Triggered Row Responses 

	$(cfct_builder).bind('row-removed', function(evt) {
		if ($('#cfct-sortables .cfct-row, cfct_build').length == 0) {
			cfct_builder.toggleOptionsMenu('hide');
		};
	});

	$(cfct_builder).bind('new-row-inserted', function(evt, row) {
		cfct_builder.toggleOptionsMenu('show');
	});
	
// Utility

	// ugly, I know, but it works.
	cfct_array_intersect = function(a, b) {
		result = new Array();
		for (i in a) {
			for (j in b) {
				if (a[i] == b[j]) {
					result.push(a[i]);
				}
			}
		}
		return result;
	};
	
// Get started
	$(function() {
		cfct_build = $('#cfct-build');
		cfct_builder.opts.build = cfct_build;
		cfct_builder.opts.rich_text = ($('#postdivrich').length > 0 ? true : false);
		cfct_builder.initDialogs();
		cfctAdminEditInit();
		cfct_build.show();
	});

})(jQuery);	

