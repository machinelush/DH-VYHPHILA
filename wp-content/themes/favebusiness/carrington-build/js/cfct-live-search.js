var cfctModuleLiveImageSearch = function(searchId, searchContainer) {
	var _searchId = searchId || 'cfct-global-image-search';
	var _searchContainer = $(searchContainer || '.cfct-global-image-select');
	var _field = $('.cfct-global-image-search-field', _searchContainer);
	var _results = $('.cfct-global-image-search-results', _searchContainer);
	var _value = $('.cfct-global-image-select-value', _searchContainer);
	
	var _searchCache = {};
	var _term = null;
	
	var _this = this; // scope hack
	
	this.init = function() {
		this.bindKeyEvents();
	};
	
	this.bindKeyEvents = function() {	
		_field.keyup(function(e) {
			term = $(this).val();
			switch (e.which) {
				default:
					if (term == "") {
						_results.find("ul").remove();
					}
					else if (_term != term) {
						_this.search();
						_term = term;
					}
					break;
			}
		}).keydown(function(e) {
			// catch arrow up/down here
			if (_results.find("ul li").size()) {
				switch (e.which) {
					case 13: // enter
						_this.search();
						_term = term;
						if ($.browser.msie) {
							e.cancelBubble = true;
						}
						else {
							e.stopPropagation();
						}
						break;
				}
			}
		});
	};
	
	this.loading = function() {
		return "<div class=\"cfct-loading\">Loading...</div>";
	};
	
	this.search = function() {
		term = $.trim(_field.val()); // trim here by calling native jQuery trim to appease IE7
		img_size = _field.attr('data-image-size');
				
		if (term in (_searchCache)) {
			_results.html(_searchCache[term]);
			// this.bindSelectionClick();
		}
		else if (term.length > 0) {
			_results.html(this.loading());
			$.post(
				cfct_builder.opts.ajax_url,
				{
					term: term,
					image_size: img_size,
					action: 'cfct_module_ajax',
					cf_action: 'cf-global-image-search',
					cf_id_base: _searchId
				},
				function(response) {
					if (_field.val() == response.term) {
						_results.html(response.html);
					}
					_searchCache[term] = response.html;
				},
				"json"
			);
		}		
	};
	
	this.init();
};