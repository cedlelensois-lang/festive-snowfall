/*!
 * Festive Snowfall – renders the falling snowflakes and the decorative banner.
 * Used on the public site (config injected by wp_add_inline_script) and in the
 * settings page live preview.
 * License: GPL-2.0-or-later
 */
(function (window, document) {
	'use strict';

	var GLYPHS = {
		crystal: '❄',  // ❄
		ornate: '❅',   // ❅
		fine: '❆',     // ❆
		star: '✦',     // ✦
		asterisk: '✻'  // ✻
	};
	var FLAKE_TYPES = ['crystal', 'ornate', 'fine', 'star', 'asterisk', 'dot'];
	var BANNER_TYPES = [
		'holly', 'pine', 'lights', 'baubles', 'icicles', 'canes', 'stars', 'gingerbread',
		'mistletoe', 'tinsel', 'gifts', 'trees', 'snowmen', 'ribbon', 'poinsettia'
	];
	var BULB_COLORS = ['#E53935', '#FDD835', '#43A047', '#1E88E5', '#FB8C00', '#AB47BC'];
	var HEX = /^#(?:[0-9a-f]{3}){1,2}$/i;

	function rand(min, max) {
		return min + Math.random() * (max - min);
	}

	function pick(list) {
		return list[Math.floor(Math.random() * list.length)];
	}

	function clampInt(v, min, max, fallback) {
		v = parseInt(v, 10);
		if (isNaN(v)) {
			return fallback;
		}
		return Math.min(max, Math.max(min, v));
	}

	function prefersReducedMotion() {
		return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
	}

	/**
	 * Builds the snowflake layer.
	 *
	 * @param {Element} parent  Element receiving the layer.
	 * @param {Object}  cfg     Snow settings.
	 * @param {boolean} preview Preview mode (layer confined to its parent).
	 * @return {Element|null}
	 */
	function snow(parent, cfg, preview) {
		cfg = cfg || {};
		var types = (cfg.types || []).filter(function (t) { return FLAKE_TYPES.indexOf(t) !== -1; });
		var colors = (cfg.colors || []).filter(function (c) { return HEX.test(c); });
		if (!types.length) { types = ['crystal']; }
		if (!colors.length) { colors = ['#9FC3DA']; }

		var isMobile = window.innerWidth < 768;
		var count = clampInt(preview || !isMobile ? cfg.count : cfg.count_mobile, 0, 150, 35);
		if (!count) {
			return null;
		}

		var sizeMin = clampInt(cfg.size_min, 4, 60, 8);
		var sizeMax = Math.max(sizeMin, clampInt(cfg.size_max, 4, 60, 22));
		// Speed 1 (very slow) .. 10 (very fast) => average fall time 23.8 s .. 4 s.
		var speed = clampInt(cfg.speed, 1, 10, 5);
		var baseDuration = 26 - speed * 2.2;

		var layer = document.createElement('div');
		layer.className = 'fesn-snow' + (preview ? ' fesn-preview' : '');
		layer.setAttribute('aria-hidden', 'true');

		for (var i = 0; i < count; i++) {
			var type = pick(types);
			var size = rand(sizeMin, sizeMax);
			var duration = baseDuration * rand(0.7, 1.3);
			var flake = document.createElement('span');
			flake.className = 'fesn-flake';

			if (type === 'dot') {
				var px = Math.max(3, size * 0.45);
				flake.className += ' fesn-dot';
				flake.style.width = px + 'px';
				flake.style.height = px + 'px';
				flake.style.background = pick(colors);
			} else {
				flake.textContent = GLYPHS[type];
				flake.style.fontSize = size + 'px';
				flake.style.color = pick(colors);
			}
			flake.style.left = rand(0, 100).toFixed(2) + '%';
			flake.style.opacity = rand(0.45, 0.95).toFixed(2);
			flake.style.setProperty('--fesn-dx', Math.round(rand(-40, 40)) + 'px');
			flake.style.animationDuration = duration.toFixed(2) + 's';
			flake.style.animationDelay = (-rand(0, duration)).toFixed(2) + 's';
			layer.appendChild(flake);
		}
		parent.appendChild(layer);
		return layer;
	}

	/**
	 * Builds the banner.
	 *
	 * @param {Object}  cfg      Banner settings (type, height, position, selector).
	 * @param {Element} [target] Forced container (preview). Otherwise from cfg.position.
	 * @return {Element|null}
	 */
	function banner(cfg, target) {
		cfg = cfg || {};
		if (BANNER_TYPES.indexOf(cfg.type) === -1) {
			return null;
		}

		var height = clampInt(cfg.height, 16, 80, 34);
		var el = document.createElement('div');
		el.className = 'fesn-banner fesn-banner-' + cfg.type;
		el.setAttribute('aria-hidden', 'true');
		el.style.setProperty('--fesn-bh', height + 'px');

		if (cfg.type === 'lights') {
			var step = height * 44 / 34;
			var width = Math.max(window.screen ? window.screen.width : 0, window.innerWidth, 1920);
			var bulbs = Math.ceil(width / step) + 1;
			var animate = !prefersReducedMotion();
			for (var i = 0; i < bulbs; i++) {
				var b = document.createElement('span');
				b.className = 'fesn-bulb';
				b.style.left = (i * step + step / 2).toFixed(1) + 'px';
				b.style.color = BULB_COLORS[i % BULB_COLORS.length];
				if (animate) {
					var d = rand(1.1, 2.2);
					b.style.animationDuration = d.toFixed(2) + 's';
					b.style.animationDelay = (-rand(0, d)).toFixed(2) + 's';
				}
				el.appendChild(b);
			}
		}

		if (target) {
			target.insertBefore(el, target.firstChild);
			return el;
		}

		if (cfg.position === 'fixed') {
			el.className += ' fesn-fixed';
			document.body.appendChild(el);
			return el;
		}

		var parent = null;
		if (cfg.position === 'selector' && cfg.selector) {
			try {
				parent = document.querySelector(cfg.selector);
			} catch (e) {
				parent = null; // Invalid CSS selector: fall back to top of page.
			}
		}
		parent = parent || document.body;
		parent.insertBefore(el, parent.firstChild);
		return el;
	}

	window.FestiveSnowfall = {
		snow: snow,
		banner: banner,
		prefersReducedMotion: prefersReducedMotion
	};

	// Public site rendering.
	var config = window.festiveSnowfallConfig;
	if (!config) {
		return;
	}

	function start() {
		document.documentElement.classList.add('fesn-active');
		if (config.banner) {
			banner(config.banner);
		}
		if (config.snow && !prefersReducedMotion()) {
			snow(document.body, config.snow, false);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})(window, document);
