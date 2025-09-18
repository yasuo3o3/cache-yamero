=== Cache Yamero ===
Contributors: yasuo3o3
Tags: cache, development, debug, refresh
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Stop cache from interfering with CSS/JS/image reloads by appending a timestamp query to links during development.


== Description ==

**Cache Yamero** is a lightweight WordPress plugin for developers who need to *force a fresh reload* during site checks.

Instead of rewriting your theme or touching .htaccess, this plugin dynamically appends a query parameter  
`?cache-yamero=YYYYMMDDHHmmss` **only at the moment of navigation (click, form submit, location redirect)**.  

- DOM is not modified → SEO and crawlers see clean URLs.
- Humans clicking links will always fetch the newest HTML/CSS/JS/images.
- Resource-specific targeting: CSS, JavaScript, and images can be individually controlled.
- Useful during **pre-launch checks**, **staging reviews**, or when browsers hold on to cached resources.  

**Key features:**
* **Resource targeting**: Choose which resources to apply cache-busting - CSS, JavaScript, and/or images.
* Apply to **Admins only** (default) or to **All visitors** (optional).
* Timestamp is always updated (to the second).
* Supports normal link clicks and GET form submissions.
* Excludes anchors, external domains, `mailto:`, `tel:`, `download`, `_blank`, modifier-key clicks.
* Includes **Back/Forward Cache (BFCache) handling**: when coming back via browser history, a single reload ensures fresh resources.
* Optional automatic cleanup of the query from the address bar (so shared URLs stay clean).  

== Installation ==

1. Upload the plugin folder `cache-yamero` to the `/wp-content/plugins/` directory,  
   or install via the WordPress admin "Plugins → Add New".  
2. Activate the plugin through the 'Plugins' menu in WordPress.  
3. Open "Settings → Cache Yamero" and enable it.  
4. Choose scope (Admins only / All visitors), and optional date window.  

== Frequently Asked Questions ==

= Does this affect SEO? =  
No. Since the plugin appends queries **only on user interaction (clicks)**, crawlers see normal clean links.  
Optionally, it also strips the query from the address bar immediately after load.

= Can I use this on production sites? =  
Yes, but the intended use is for development/staging or short-term pre-launch checks.  
Don’t leave it on for all visitors long-term — it disables browser caching.

= Does it work with forms? =  
Yes, GET forms will have the query appended. POST forms are excluded.

= Does it work with SPA / AJAX navigation? =  
The plugin patches `location.assign/replace` and `location.href` navigations.  
It does not hook into SPA routers (`history.pushState`) — by design, to avoid breaking themes.

= What about Back/Forward Cache? =  
When users return to a page via browser history, BFCache might restore old CSS.  
Cache Yamero reloads once automatically in that case.

= Can non-logged-in users use it? =  
Yes. By switching the scope to "All visitors", even non-logged-in users such as **clients or stakeholders**  
can review the site with fresh reloads, without having to log in. This is useful for pre-launch client checks.

== Screenshots ==

1. Settings screen: toggle ON/OFF, scope, and options.

== Changelog ==

= 1.1.0 =
* **Added**: Resource-specific targeting for CSS, JavaScript, and images.
* **Added**: Individual control over which resource types receive cache-busting parameters.
* **Improved**: Enhanced image handling with support for srcset and lazy loading attributes.
* **Streamlined**: Simplified codebase by removing experimental font support.

= 1.0.0 =
* Initial release.
* Link click + GET form cache busting with `cache-yamero` query.
* Admin-only mode and All-visitors mode.
* BFCache reload handling.
* Optional URL cleanup.  

== Upgrade Notice ==

= 1.1.0 =
New resource-specific targeting! Now you can choose which resource types (CSS, JS, images) to apply cache-busting to.

= 1.0.0 =
First release. Enable only when you need to force fresh reloads during development or pre-launch checks.


------------------------------------------------------------
【日本語説明】

== 説明 ==

**Cache Yamero** は、開発時のキャッシュ問題を解決する WordPress プラグインです。
人の操作（クリックやフォーム送信）時のみ、ページ遷移URLに `?cache-yamero=YYYYMMDDHHmmss` パラメータを自動付与し、常に新しいHTML/CSS/JS/画像を取得します。

**主な特徴:**
* **リソース種別ターゲティング** - CSS、JavaScript、画像を個別に制御可能
* **クリック時だけクエリ付与** - 自動的な遷移や検索エンジンのクローリングには影響しません
* **DOM非改変** - ページのHTMLを直接書き換えることなく、遷移時のみURLを動的に変更
* **SEO影響最小化** - 検索エンジンには通常のURLが見えるため、SEOへの悪影響を防ぎます
* **BFCache対応** - ブラウザの戻るボタン使用時も適切にキャッシュを無効化
* **期間限定対応** - 開始・終了日時を設定して一時的な運用が可能
* **権限ベース制御** - 管理者のみまたはすべての訪問者への適用を選択可能  

== インストール ==

1. プラグインファイルを `/wp-content/plugins/cache-yamero/` ディレクトリにアップロード  
2. WordPress管理画面の「プラグイン」メニューからプラグインを有効化  
3. 「設定」→「Cache Yamero」で設定を行う  

== よくある質問 ==

= SEO に影響しますか？ =  
いいえ。Cache Yamero はクリック時のみURLにパラメータを付与し、ページのHTMLは一切変更しません。検索エンジンのクローラーには通常のURLが見えるため、SEOへの影響はありません。  

= どのような遷移に対応していますか？ =  
- 通常のリンククリック（同一オリジン）  
- location.assign() / location.replace() / location.href の使用  
- GETフォームの送信（設定で有効化時）  

以下は除外されます：  
- アンカーリンク (#で始まるリンク)  
- 外部サイトへのリンク  
- mailto: や tel: リンク  
- target="_blank" のリンク  
- Ctrl/Cmd + クリックなどの修飾キー使用時  

= BFCache（戻るボタン）対応について =  
ブラウザの戻るボタンでページがキャッシュから復元された場合、自動的に1回だけリロードしてキャッシュを無効化します。ループを防ぐためページごとにフラグを管理し、過度なリロードは発生しません。  

= 有効期間の設定は？ =  
開始日時と終了日時を設定することで、指定期間内のみプラグインを動作させることができます。空白の場合は期間制限なしで動作します。  

= GETフォーム対応とは？ =  
検索フォームなどのGETメソッドを使用するフォーム送信時にも、遷移先URLにキャッシュ無効化パラメータを自動で追加します。POSTフォームは対象外です。  

= ログインしていない確認者（クライアントなど）にも効きますか？ =  
はい。「全訪問者」モードに切り替えれば、未ログインの人でもキャッシュ無効状態で確認できます。クライアントや客先でのチェックに便利です。  

== スクリーンショット ==

1. 管理画面設定ページ - 有効化、適用範囲、有効期間の設定  
2. オプション設定 - GETフォーム対応とURLクリーンアップの設定  

== 変更履歴 ==

= 1.0.0 =  
* 初回リリース  
* クリック時キャッシュ無効化機能  
* 管理者のみ/全訪問者の適用範囲選択  
* 有効期間設定機能  
* GETフォーム対応  
* BFCache対策機能  
* URLクリーンアップ機能  
* 多言語対応（日本語/英語）  

== アップグレード通知 ==

= 1.0.0 =  
初回リリース。開発時のキャッシュ問題を解決する必須ツールです。  
