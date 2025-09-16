# Cache Yamero

Stop cache from messing with your CSS/HTML reloads during development.
Add a unique timestamp query (`?cache-yamero=YYYYMMDDHHmmss`) to links on click, forcing browsers to always fetch the fresh page.

開発時のキャッシュ問題を解決するWordPressプラグイン。人の操作時のみページ遷移URLにタイムスタンプクエリを付与し、常に新しいHTML/CSSを取得します。

## Features / 主な機能

- **Click-only query appending** / **クリック時のみクエリ付与** - Only affects human navigation, not crawlers
- **DOM non-destructive** / **DOM非改変** - Clean URLs for SEO, dynamic URLs for navigation
- **Admin-only or All visitors** / **管理者限定 or 全訪問者** - Flexible scope control
- **Time window support** / **期間限定対応** - Optional start/end datetime
- **BFCache handling** / **BFCache対応** - Handles browser back/forward cache
- **GET form support** / **GETフォーム対応** - Works with search forms
- **URL cleanup** / **URLクリーンアップ** - Optional removal from address bar

## Requirements / 動作環境

- WordPress 6.0+
- PHP 7.4+

## Installation / インストール

1. Upload to `/wp-content/plugins/cache-yamero/`
2. Activate via WordPress admin
3. Configure at "Settings → Cache Yamero"

1. `/wp-content/plugins/cache-yamero/` にアップロード
2. WordPress管理画面でプラグインを有効化
3. 「設定」→「Cache Yamero」で設定

## Usage / 使い方

### Basic Setup / 基本設定

1. Enable the plugin / プラグインを有効化
2. Choose scope: "Admins only" or "All visitors" / 適用範囲を選択：「管理者のみ」または「すべての訪問者」
3. Optionally set time window / 必要に応じて有効期間を設定

### Options / オプション

- **GET Form Support** / **GETフォーム対応** - Adds timestamp to GET form submissions
- **URL Cleanup** / **URLクリーンアップ** - Removes query parameter from address bar after page load

## How It Works / 動作原理

### On Click / クリック時
```javascript
// Original link: https://example.com/page/
// Becomes: https://example.com/page/?cache-yamero=20240916120000
```

### Excluded Links / 除外されるリンク
- Anchor links (`#section`)
- External domains
- `mailto:` and `tel:` links
- `target="_blank"` links
- Downloads (`download` attribute)
- Modified key clicks (Ctrl/Cmd/Shift/Alt)

### BFCache Handling / BFCache対応
When users navigate back via browser history, the plugin detects cached page restoration and performs a single reload to ensure fresh content.

ブラウザの戻るボタンでキャッシュが復元された場合、1回だけリロードして新しいコンテンツを確実に取得します。

## File Structure / ファイル構成

```
cache-yamero/
├── cache-yamero.php          # Main plugin file / メインプラグインファイル
├── assets/js/
│   └── cache-yamero.js       # Frontend JavaScript / フロントエンドJS
├── languages/
│   └── cache-yamero.pot      # Translation template / 翻訳テンプレート
├── uninstall.php             # Cleanup on uninstall / アンインストール処理
├── readme.txt                # WordPress.org format / WordPress.org形式
└── README.md                 # This file / このファイル
```

## Development Notes / 開発メモ

### Security / セキュリティ
- All inputs sanitized with `sanitize_text_field()`
- All outputs escaped with `esc_html()`, `esc_attr()`, `esc_url()`
- Nonce verification for form submissions
- Capability checks with `current_user_can('manage_options')`

### Coding Standards / コーディング規約
- WordPress Coding Standards (WPCS) compliant
- ES5 compatible JavaScript
- IIFE pattern with strict mode
- Comprehensive error handling

### Internationalization / 国際化
- Text domain: `cache-yamero`
- All strings wrapped in translation functions
- POT file included for translators

## License / ライセンス

GPLv2 or later

## Author / 作者

yasuo3o3
https://yasuo-o.xyz/

---

**Note:** This plugin is intended for development and pre-launch use. Don't leave it active on production sites long-term as it disables browser caching benefits.

**注意:** このプラグインは開発・公開前の用途を想定しています。本番サイトでの長期利用は、ブラウザキャッシュの恩恵を無効化するため推奨しません。