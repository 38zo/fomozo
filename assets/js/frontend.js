/* global jQuery, fomozo_ajax */
(function ($) {
	'use strict';

	var state = {
		queue: [],
		isShowing: false,
		settings: (typeof fomozo_ajax === 'object' && fomozo_ajax.settings) ? fomozo_ajax.settings : {}
	};

	function fetchNotifications() {
		if (typeof fomozo_ajax !== 'object') { return; }
		$.post(fomozo_ajax.ajax_url, {
			action: 'fomozo_get_notifications',
			nonce: fomozo_ajax.nonce
		}).done(function (res) {
			if (res && res.success && Array.isArray(res.data)) {
				state.queue = res.data;
				showNext();
			}
		});
	}

	function showNext() {
		if (state.isShowing) { return; }
		var item = state.queue.shift();
		if (!item) { return; }
		state.isShowing = true;

		var $root = $('#fomozo-root');
		if ($root.length === 0) {
			$('body').append('<div id="fomozo-root" class="fomozo-root" aria-live="polite" aria-atomic="true"></div>');
			$root = $('#fomozo-root');
		}

		var $popup = renderPopup(item);
		$root.append($popup);

		setTimeout(function () {
			$popup.addClass('fomozo-visible');
			sendImpression(item);
		}, item.delay || state.settings.animation_speed || 300);

		var duration = item.duration || 5000;
		setTimeout(function () {
			$popup.removeClass('fomozo-visible');
			setTimeout(function () {
				$popup.remove();
				state.isShowing = false;
				showNext();
			}, 300);
		}, duration);
	}

	function renderPopup(item) {
		var templateClass = 'fomozo-template-' + (item.template || 'bottom-left');
		var html = '' +
			'<div class="fomozo-popup ' + templateClass + '">' +
			'	<div class="fomozo-popup-inner">' +
			'		<div class="fomozo-message">' + escapeHtml(item.message || '') + '</div>' +
			'	</div>' +
			'</div>';
		return $(html);
	}

	function sendImpression(item) {
		if (!item || !item.id || typeof fomozo_ajax !== 'object') { return; }
		$.post(fomozo_ajax.ajax_url, {
			action: 'fomozo_track_impression',
			nonce: fomozo_ajax.nonce,
			campaign_id: item.id,
			page_url: window.location.href
		});
	}

	function escapeHtml(str) {
		return String(str)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	$(function () {
		fetchNotifications();
	});

})(jQuery);


