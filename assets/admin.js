/*!
 * Festive Snowfall – settings page: schedule cards, colour pickers, live preview.
 * License: GPL-2.0-or-later
 */
(function ($) {
	'use strict';

	var L = window.fesnAdmin || {};
	var $form = $('#fesn-form');
	var $list = $('#fesn-schedules');
	var tpl = document.getElementById('fesn-schedule-template');
	var stage = document.getElementById('fesn-preview');
	var header = document.getElementById('fesn-preview-header');
	if (!$form.length || !$list.length || !tpl || !stage || !window.FestiveSnowfall) {
		return;
	}

	var $active = null;
	var timer = null;
	var counter = 0;

	/* ---------- Helpers ---------- */

	function fields($card, key) {
		return $card.find('[data-fesn="' + key + '"]');
	}

	function readSchedule($card) {
		return {
			label: $.trim(fields($card, 'label').val() || ''),
			start: fields($card, 'start_date').val(),
			end: fields($card, 'end_date').val(),
			repeat: fields($card, 'repeat').is(':checked'),
			snow: fields($card, 'snow_enabled').is(':checked') ? {
				types: fields($card, 'flake_types').filter(':checked').map(function () { return this.value; }).get(),
				colors: fields($card, 'colors').map(function () { return $.trim(this.value); }).get().filter(Boolean),
				count: fields($card, 'count').val(),
				size_min: fields($card, 'size_min').val(),
				size_max: fields($card, 'size_max').val(),
				speed: fields($card, 'speed').val()
			} : null,
			banner: {
				type: fields($card, 'banner_type').val(),
				height: fields($card, 'banner_height').val()
			}
		};
	}

	function formatDay(iso, withYear) {
		var p = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso || '');
		if (!p) {
			return '?';
		}
		var d = new Date(+p[1], +p[2] - 1, +p[3]);
		var opts = { day: 'numeric', month: 'short' };
		if (withYear) {
			opts.year = 'numeric';
		}
		try {
			return d.toLocaleDateString(document.documentElement.lang || undefined, opts);
		} catch (e) {
			return iso;
		}
	}

	function titleOf($card, s) {
		return s.label || (L.untitled || 'Schedule') + ' ' + ($list.children('.fesn-schedule').index($card) + 1);
	}

	function refreshSummary($card) {
		var s = readSchedule($card);
		$card.find('.fesn-schedule-title').text(titleOf($card, s));
		$card.find('.fesn-schedule-dates').text(
			formatDay(s.start, !s.repeat) + ' → ' + formatDay(s.end, !s.repeat) + (s.repeat ? ' · ' + (L.everyYear || 'every year') : '')
		);
		$card.find('.fesn-badge-test').prop('hidden', !fields($card, 'test').is(':checked'));
		$card.find('.fesn-speed-out').text(fields($card, 'speed').val());
	}

	function refreshAll() {
		$list.children('.fesn-schedule').each(function () {
			refreshSummary($(this));
		});
		$('#fesn-add').prop('disabled', $list.children('.fesn-schedule').length >= (L.max || 20));
	}

	/* ---------- Preview ---------- */

	function render() {
		$(stage).find('.fesn-snow').remove();
		$(header).find('.fesn-banner').remove();
		if (!$active || !$active.closest('body').length) {
			$active = $list.children('.fesn-schedule').first();
		}
		if (!$active.length) {
			$('#fesn-preview-name').text('');
			return;
		}
		var s = readSchedule($active);
		$('#fesn-preview-name').text(titleOf($active, s));
		if (s.banner.type && s.banner.type !== 'none') {
			window.FestiveSnowfall.banner(s.banner, header);
		}
		if (s.snow) {
			window.FestiveSnowfall.snow(stage, s.snow, true);
		}
	}

	function scheduleRender() {
		clearTimeout(timer);
		timer = setTimeout(render, 150);
	}

	function setActive($card) {
		if ($active && $active.is($card)) {
			return;
		}
		$list.children('.fesn-schedule').removeClass('is-active');
		$active = $card.addClass('is-active');
		scheduleRender();
	}

	/* ---------- Cards ---------- */

	function initPickers($scope) {
		$scope.find('.fesn-color').wpColorPicker({
			change: function (event) {
				var $card = $(event.target).closest('.fesn-schedule');
				setTimeout(function () { refreshSummary($card); setActive($card); scheduleRender(); }, 0);
			},
			clear: scheduleRender
		});
	}

	function newCard() {
		counter += 1;
		var index = 'n' + Date.now().toString(36) + counter;
		var html = tpl.innerHTML.replace(/__i__/g, index);
		return $($.parseHTML($.trim(html)));
	}

	/** Copies values from one card to another (cards share the same template). */
	function copyValues($from, $to) {
		var src = $from.find('[data-fesn]').get();
		var dst = $to.find('[data-fesn]').get();
		for (var i = 0; i < src.length && i < dst.length; i++) {
			if (src[i].type === 'checkbox' || src[i].type === 'radio') {
				dst[i].checked = src[i].checked;
			} else {
				dst[i].value = src[i].value;
			}
		}
	}

	function addCard($after, $source) {
		if ($list.children('.fesn-schedule').length >= (L.max || 20)) {
			window.alert(L.maxReached || 'Maximum reached.');
			return;
		}
		var $card = newCard();
		if ($source) {
			copyValues($source, $card);
			fields($card, 'test').prop('checked', false);
			var label = $.trim(fields($source, 'label').val() || '');
			fields($card, 'label').val(label ? (L.copyOf || 'Copy of %s').replace('%s', label) : '');
		}
		if ($after) {
			$card.insertAfter($after);
		} else {
			$list.append($card);
		}
		initPickers($card);
		$list.children('.fesn-schedule').prop('open', false);
		$card.prop('open', true);
		refreshAll();
		setActive($card);
		fields($card, 'label').trigger('focus');
	}

	$('#fesn-add').on('click', function () {
		addCard(null, null);
	});

	$list.on('click', '[data-fesn-action]', function (e) {
		e.preventDefault();
		var $card = $(this).closest('.fesn-schedule');
		switch ($(this).data('fesn-action')) {
			case 'up':
				$card.prev('.fesn-schedule').before($card);
				break;
			case 'down':
				$card.next('.fesn-schedule').after($card);
				break;
			case 'duplicate':
				addCard($card, $card);
				return;
			case 'delete':
				if (window.confirm(L.confirmDelete || 'Delete?')) {
					if ($active && $active.is($card)) {
						$active = null;
					}
					$card.remove();
				}
				break;
		}
		refreshAll();
		scheduleRender();
	});

	// Only one schedule may be in testing mode.
	$list.on('change', '[data-fesn="test"]', function () {
		if (this.checked) {
			fields($list, 'test').not(this).prop('checked', false);
		}
		refreshAll();
	});

	$list.on('input change', 'input, select', function () {
		var $card = $(this).closest('.fesn-schedule');
		refreshSummary($card);
		setActive($card);
		scheduleRender();
	});

	$list.on('focusin click', '.fesn-schedule', function () {
		setActive($(this));
	});

	// The "toggle" event does not bubble: listen in capture phase.
	$list[0].addEventListener('toggle', function (e) {
		if (e.target.classList && e.target.classList.contains('fesn-schedule') && e.target.open) {
			setActive($(e.target));
		}
	}, true);

	$('#fesn-preview-bg').on('click', function () {
		$(stage).toggleClass('is-dark');
	});

	/* ---------- Start ---------- */

	initPickers($list);
	refreshAll();
	setActive($list.children('.fesn-schedule[open]').first().length ? $list.children('.fesn-schedule[open]').first() : $list.children('.fesn-schedule').first());
	render();
})(jQuery);
