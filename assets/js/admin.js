/* global jQuery, fomozo_admin */
(function($){
	'use strict';
	$(document).on('click', '#fomozo-wipe-data', function(e){
		e.preventDefault();
		if (!confirm('This will permanently delete all FOMOZO data (campaigns, impressions, settings). Continue?')) { return; }
		var $btn = $(this).prop('disabled', true);
		$.post(fomozo_admin.ajax_url, { action: 'fomozo_wipe_data', nonce: fomozo_admin.nonce })
		 .done(function(){ alert('All FOMOZO data has been deleted.'); location.reload(); })
		 .fail(function(){ alert('Failed to delete data.'); })
		 .always(function(){ $btn.prop('disabled', false); });
	});
})(jQuery);

