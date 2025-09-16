/**
 * Cache Yamero - フロントエンドスクリプト
 * 人の操作時のみページ遷移URLに ?cache-yamero=YYYYMMDDHHmmss を付与
 * ES5互換・DOM非改変・BFCache対応
 */
(function() {
	'use strict';

	// 設定チェック
	if (typeof cacheYamero === 'undefined' || !cacheYamero.enabled) {
		return;
	}

	var config = {
		enabled: cacheYamero.enabled,
		getFormSupport: cacheYamero.getFormSupport,
		urlCleanup: cacheYamero.urlCleanup
	};

	// 現在時刻をYYYYMMDDHHmmss形式で取得
	function getCurrentTimestamp() {
		var now = new Date();
		var year = now.getFullYear();
		var month = ('0' + (now.getMonth() + 1)).slice(-2);
		var day = ('0' + now.getDate()).slice(-2);
		var hours = ('0' + now.getHours()).slice(-2);
		var minutes = ('0' + now.getMinutes()).slice(-2);
		var seconds = ('0' + now.getSeconds()).slice(-2);
		return year + month + day + hours + minutes + seconds;
	}

	// URLにcache-yameroパラメータを追加
	function addCacheParameter(url) {
		try {
			var parsedUrl = new URL(url, window.location.origin);
			parsedUrl.searchParams.set('cache-yamero', getCurrentTimestamp());
			return parsedUrl.href;
		} catch (e) {
			return url;
		}
	}

	// 同一オリジンかチェック
	function isSameOrigin(url) {
		try {
			var parsedUrl = new URL(url, window.location.origin);
			return parsedUrl.origin === window.location.origin;
		} catch (e) {
			return false;
		}
	}

	// 除外すべきリンクかチェック
	function shouldExcludeLink(element, event) {
		if (!element || element.tagName !== 'A') {
			return true;
		}

		var href = element.getAttribute('href');
		if (!href || href === '' || href.charAt(0) === '#') {
			return true;
		}

		// 外部オリジン・特殊スキーマの除外
		if (href.indexOf('mailto:') === 0 || href.indexOf('tel:') === 0) {
			return true;
		}

		// download属性がある場合は除外
		if (element.hasAttribute('download')) {
			return true;
		}

		// target="_blank"の場合は除外
		var target = element.getAttribute('target');
		if (target === '_blank') {
			return true;
		}

		// 修飾キーが押されている場合は除外
		if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) {
			return true;
		}

		// 左クリック以外は除外
		if (event.button !== 0) {
			return true;
		}

		// デフォルト動作がキャンセルされている場合は除外
		if (event.defaultPrevented) {
			return true;
		}

		return false;
	}

	// クリックハンドラ
	function handleClick(event) {
		try {
			if (shouldExcludeLink(event.target, event)) {
				return;
			}

			var href = event.target.getAttribute('href');
			if (!href || !isSameOrigin(href)) {
				return;
			}

			// デフォルトの遷移をキャンセル
			event.preventDefault();

			// cache-yameroパラメータを付与して遷移
			var newUrl = addCacheParameter(href);
			window.location.href = newUrl;

		} catch (e) {
			// 例外時は何もしない（遷移を壊さない）
		}
	}

	// location オブジェクトのプロキシ設定
	function setupLocationProxy() {
		try {
			var originalAssign = window.location.assign;
			var originalReplace = window.location.replace;

			// location.assign のプロキシ
			if (originalAssign) {
				window.location.assign = function(url) {
					if (isSameOrigin(url)) {
						url = addCacheParameter(url);
					}
					return originalAssign.call(this, url);
				};
			}

			// location.replace のプロキシ
			if (originalReplace) {
				window.location.replace = function(url) {
					if (isSameOrigin(url)) {
						url = addCacheParameter(url);
					}
					return originalReplace.call(this, url);
				};
			}

		} catch (e) {
			// プロキシ設定に失敗した場合は何もしない
		}
	}

	// GETフォーム対応
	function handleFormSubmit(event) {
		try {
			if (!config.getFormSupport) {
				return;
			}

			var form = event.target;
			if (!form || form.tagName !== 'FORM') {
				return;
			}

			// GETメソッドでない場合は除外
			var method = (form.getAttribute('method') || 'get').toLowerCase();
			if (method !== 'get') {
				return;
			}

			var action = form.getAttribute('action') || window.location.href;
			if (!isSameOrigin(action)) {
				return;
			}

			// cache-yamero用のhiddenフィールドを追加
			var existingField = form.querySelector('input[name="cache-yamero"]');
			if (existingField) {
				existingField.value = getCurrentTimestamp();
			} else {
				var hiddenField = document.createElement('input');
				hiddenField.type = 'hidden';
				hiddenField.name = 'cache-yamero';
				hiddenField.value = getCurrentTimestamp();
				form.appendChild(hiddenField);
			}

		} catch (e) {
			// 例外時は何もしない
		}
	}

	// BFCache対策
	function handleBFCache(event) {
		try {
			if (!config.enabled || !event.persisted) {
				return;
			}

			// ページ単位のリロード防止フラグをチェック
			var storageKey = 'cy_bfcache_reloaded_' + window.location.pathname;
			if (sessionStorage.getItem(storageKey)) {
				return;
			}

			// フラグを設定してリロード
			sessionStorage.setItem(storageKey, '1');
			window.location.reload();

		} catch (e) {
			// 例外時は何もしない
		}
	}

	// URLクリーンアップ
	function cleanupUrl() {
		try {
			if (!config.urlCleanup) {
				return;
			}

			var url = new URL(window.location.href);
			if (url.searchParams.has('cache-yamero')) {
				url.searchParams.delete('cache-yamero');
				var newUrl = url.pathname + url.search + url.hash;
				if (window.history && window.history.replaceState) {
					window.history.replaceState(null, '', newUrl);
				}
			}

		} catch (e) {
			// 例外時は何もしない
		}
	}

	// イベントリスナー設定
	function setupEventListeners() {
		try {
			// クリックイベント（キャプチャフェーズ）
			document.addEventListener('click', handleClick, {
				capture: true,
				passive: false
			});

			// フォーム送信イベント
			document.addEventListener('submit', handleFormSubmit, {
				capture: true,
				passive: false
			});

			// BFCacheイベント
			window.addEventListener('pageshow', handleBFCache);

		} catch (e) {
			// イベントリスナー設定に失敗した場合は何もしない
		}
	}

	// DOM読み込み完了後の処理
	function onDOMReady() {
		try {
			setupLocationProxy();
			setupEventListeners();

			// URLクリーンアップは少し遅延させて実行
			setTimeout(cleanupUrl, 100);

		} catch (e) {
			// 初期化に失敗した場合は何もしない
		}
	}

	// DOM読み込み状態チェック
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', onDOMReady);
	} else {
		onDOMReady();
	}

})();