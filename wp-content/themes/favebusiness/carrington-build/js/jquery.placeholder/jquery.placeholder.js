/**
 * Placeholder
 * Crowd Favorite
 * @requires jQuery v1.2 or above
 *
 * Version: 1.2
 * Patches the HTML5 placeholder atttribute functionality for browsers that don't support it
 */
;(function($) {
	$.fn.placeholder = function(settings) {
		// Merge default options and user options
		var opts = $.extend({}, $.fn.placeholder.settings, settings);
		var previousElement = null;
		var placeholderClearOnReload = function() {
			$('[' + opts.attribute + '].' + opts.classname).val('');
		}

		/* Are we using the placholder attribute?
		 * Does the browser support placeholders?
		 * Should we run if it does?
		 * If no, exit out.
		 */
		if (opts.attribute == 'placeholder' && opts.disableIfSupported == true && 'placeholder' in document.createElement('input')) {
			return;
		};

		/**
		 * Refocusing previous element:
		 * jQuery >= 1.6 or a browser supporting document.activeElement is required 
		 **/
		if ('undefined' === typeof document.activeElement) {
			if ($().jquery.split('.') >= '1.6'.split('.')) {
				previousElement = $($('*:focus').get(0));
			}
		}
		else {
			previousElement = $(document.activeElement);
		}

		// Run placholders
		this.each(function() {
			var _this = $(this),
				isPassword = (_this.attr('type').toLowerCase() == 'password'),
				$placeholderInput;
				
			if (isPassword) {
				// We have to write a custom input element here to simulate placeholder since a password field will mask the characters.
				$placeholderInput = $('<input type="text" id="'+_this.attr('id')+'-placeholder" value="'+_this.attr('placeholder')+'" />');
				$placeholderInput.focus(function() {
					$placeholderInput.hide();
					_this.show().focus();
				});
				_this.blur(function() {
					if (_this.val() == '') {
						// Only do this if the field is empty.
						$placeholderInput.show().attr('class', _this.attr('class'));
						$placeholderInput.attr('style', _this.attr('style'));
						_this.hide();
					}
				});
				_this.after($placeholderInput);
			}
			else {
				_this.focus(function(){
					focusPlaceholder(_this, opts);
				})
				_this.blur(function(){
					blurPlaceholder(_this, opts);
				});
			}
			_this.blur();
			_this.parents('form').submit(function() {
				clearPlaceholdersOnSubmit(this, opts);
			});
			if (_this.filter($(previousElement)).length) {
				$(previousElement).focus();
			}
		});


		$(window).unbind('unload', placeholderClearOnReload);
		$(window).unload(placeholderClearOnReload);
	};

	/**
	 * Plugin settings defaults
	 * Set in separate object so they are public
	 */
	$.fn.placeholder.settings = {
		classname: 'cfp-placeholder',
		attribute: 'placeholder',
		disableIfSupported: true
	};

	/**
	 * Call this to enable standard-style HTML5 placeholders globally (on any element with a placeholder attribute)
	 */
	$.placeholders = function(settings) {
		var opts = $.extend({}, $.fn.placeholder.settings, settings);
		$('[' + opts.attribute + ']').placeholder(opts);
	};

	/* Private helper functions */

	function focusPlaceholder(el, opts) {
		el = $(el);
		if (el.hasClass(opts.classname)) {
			el.val('');
			el.removeClass(opts.classname);
		}
	};

	function blurPlaceholder(el, opts) {
		el = $(el);
		if (el.val() === '') {
			el.addClass(opts.classname);
			el.val(el.attr(opts.attribute));
		}
	};

	function clearPlaceholdersOnSubmit(form, opts) {
		$(form).find('[' + opts.attribute + ']').each(function(){
			var input = $(this);
			if(input.hasClass(opts.classname)) {
				input.val('');
			};
		});
	};

})(jQuery);
