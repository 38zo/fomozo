/* global jQuery, fomozo_ajax */
(function ($) {
	'use strict';

	var state = {
		queue: [],
		isShowing: false,
		settings: (typeof fomozo_ajax === 'object' && fomozo_ajax.settings) ? fomozo_ajax.settings : {},
		refetchMs: 45000, // how often to refetch when idle
		betweenMsMin: 3000,
		betweenMsMax: 7000,
		recent: [],
		cooldownMs: 5 * 60 * 1000 // do not repeat the same item within 5 minutes
	};

	function now() { return Date.now(); }

	function itemKey(item) {
		// Prefer message content to distinguish items; fall back to id
		return (item && item.message ? String(item.message) : ('id:' + (item && item.id ? item.id : '0')));
	}

	function pruneRecent() {
		var cutoff = now() - state.cooldownMs;
		state.recent = state.recent.filter(function (r) { return r.t >= cutoff; });
	}

	function isRecentlyShown(key) {
		pruneRecent();
		for (var i = 0; i < state.recent.length; i++) {
			if (state.recent[i].k === key) { return true; }
		}
		return false;
	}

	function markShown(key) {
		state.recent.push({ k: key, t: now() });
		pruneRecent();
	}

	function effectiveGap() {
		if (state.settings && typeof state.settings.gap_ms === 'number') {
			return Math.max(0, state.settings.gap_ms);
		}
		return nextGap();
	}

	function fetchNotifications() {
		if (typeof fomozo_ajax !== 'object') { return; }
		$.post(fomozo_ajax.ajax_url, {
			action: 'fomozo_get_notifications',
			nonce: fomozo_ajax.nonce
		}).done(function (res) {
			if (res && res.success && Array.isArray(res.data)) {
				var incoming = res.data.slice();
				// Filter out items recently shown
				var filtered = [];
				for (var i = 0; i < incoming.length; i++) {
					var key = itemKey(incoming[i]);
					if (!isRecentlyShown(key)) { filtered.push(incoming[i]); }
				}
				state.queue = shuffle(filtered);
				if (!state.isShowing) {
					// If still empty, schedule another try later
					if (state.queue.length === 0) { scheduleRefetch(); return; }
					showNext();
				}
			} else {
				scheduleRefetch();
			}
		}).fail(scheduleRefetch);
	}

	function scheduleRefetch() {
		setTimeout(fetchNotifications, state.refetchMs);
	}

	function nextGap() {
		var min = state.betweenMsMin, max = state.betweenMsMax;
		return Math.floor(Math.random() * (max - min + 1)) + min;
	}

	function showNext() {
		if (state.isShowing) { return; }
		var item = state.queue.shift();
		if (!item) { scheduleRefetch(); return; }
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
			markShown(itemKey(item));
		}, item.delay || state.settings.animation_speed || 300);

		var duration = item.duration || 5000;
		setTimeout(function () {
			$popup.removeClass('fomozo-visible');
			setTimeout(function () {
				$popup.remove();
				state.isShowing = false;
				// when queue empties, refetch to get fresh data
				if (state.queue.length === 0) {
					fetchNotifications();
				} else {
					setTimeout(showNext, effectiveGap());
				}
			}, 300);
		}, duration);
	}

	function renderPopup(item) {
		var templateClass = 'fomozo-template-' + (item.template || 'bottom-left');
		var html = '' +
			'<div class="fomozo-popup ' + templateClass + '">' +
			'\t<div class="fomozo-popup-inner">' +
			'\t\t<div class="fomozo-message">' + escapeHtml(item.message || '') + '</div>' +
			'\t\t<div class="fomozo-popup-branding"><a href="https://example.com" target="_blank" rel="nofollow noopener">Powered by FOMOZO</a></div>' +
			'\t</div>' +
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

	function shuffle(arr) {
		for (var i = arr.length - 1; i > 0; i--) {
			var j = Math.floor(Math.random() * (i + 1));
			var t = arr[i];
			arr[i] = arr[j];
			arr[j] = t;
		}
		return arr;
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


