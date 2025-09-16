# Cache Yamero - 変更履歴

## [1.0.0] - 2024-09-16

### 追加
- 初回リリース
- 人の操作時のみページ遷移URLに `?cache-yamero=YYYYMMDDHHmmss` を付与してキャッシュ無効化
- 管理画面での詳細設定機能
  - 有効/無効の切り替え
  - 適用範囲設定（管理者のみ/全訪問者）
  - 有効期間設定（開始日時・終了日時）
  - GETフォーム対応オプション
  - URLクリーンアップオプション
- JavaScript によるクライアントサイドでの動的パラメータ付与
- WordPressの標準的なプラグイン構造に準拠
- セキュリティ対策（直接アクセス防止、nonce検証、権限チェック）
- 国際化対応（翻訳ファイル準備）

### 技術仕様
- WordPress 6.0以上に対応
- PHP 7.4以上に対応
- GPL v2ライセンス
- DOMを汚さない設計
- 管理画面での直感的な設定UI

### ファイル構成
- `cache-yamero.php` - メインプラグインファイル
- `assets/js/cache-yamero.js` - フロントエンドスクリプト
- `languages/cache-yamero.pot` - 翻訳テンプレート
- `uninstall.php` - アンインストール処理
- 各種設定ファイル（.gitignore、.gitattributes、.distignore）