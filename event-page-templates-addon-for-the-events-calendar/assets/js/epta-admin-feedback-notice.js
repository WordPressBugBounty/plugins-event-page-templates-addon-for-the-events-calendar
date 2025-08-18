jQuery(document).ready(function ($) {
	$('.tecset_dismiss_notice').on('click', function (event) {
		var $this = $(this);
		var wrapper=$this.parents('.cool-feedback-notice-wrapper');
		var ajaxURL=wrapper.data('ajax-url');
		var ajaxCallback=wrapper.data('ajax-callback');
		var wpNonce = (typeof adminNotice !== 'undefined' && adminNotice.nonce) ? adminNotice.nonce : (wrapper.data('wp-nonce') || wrapper.data('nonce'));
		if (!ajaxURL || !ajaxCallback || !wpNonce) {
			return;
		}
		
		$.post(ajaxURL, { 'action':ajaxCallback, '_nonce': wpNonce}, function( data ) {
			wrapper.slideUp('fast');
		  }, "json");

	});
});