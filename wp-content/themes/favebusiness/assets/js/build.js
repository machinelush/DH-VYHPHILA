// explicitly look for CFCT in the window scope to avoid possible "undefined" error reporting by different browsers.
cfct = window.CFCT || {url: '/'};

cfct.loading = function() {
	return '<div class="loading"><span>Loading...</span></div>';
};

cfct.ajax_post_content = function() {
	jQuery('body:not(.search-results) .entry-excerpt.post .entry-header .entry-title a, body:not(.search-results) .entry-excerpt.cfct-news .entry-header .entry-title a').unbind().click(function() {
		// Get post ID from list template ID
		var postID = jQuery(this).parents('.entry.entry-excerpt').attr('id').replace('post-excerpt-', '');
		var $excerpt = jQuery('#post-excerpt-' + postID);
		var $target = jQuery('#post-target-' + postID);
		// Append target container if it doesn't exist already.
		if ($target.length == 0) {
			$target = $excerpt.after('<div class="ajax-loaded" id="post-target-' + postID + '"/>');
			$target = jQuery('#post-target-' + postID);
		};
		
		$excerpt.hide();
		$target.html(cfct.loading()).show().load(cfct.url + 'index.php?cfct_action=post_content&id=' + postID, function() {
			jQuery('#post_close_' + postID + ' a').click(function() {
				$target.slideUp(function() {
					$excerpt.show();
				});
				return false;
			});
			jQuery(this).hide().slideDown();
		});
		return false;
	});
};

// Run on DOMReady
jQuery(function($){
	// :has selector workaround
	$('.nav ul li:has(ul)').addClass('has-ul');
	
	cfct.ajax_post_content();
});