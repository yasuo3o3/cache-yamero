# Cache Yamero - 変更履歴

## [1.1.0] - 2024-09-18

### 追加
- **リソース種別ターゲティング機能** - CSS、JavaScript、画像を個別に制御可能
- 画像ファイルのキャッシュ無効化対応（srcset・遅延読み込み属性対応）
- 管理画面でのリソース種別別有効/無効設定

### 改良
- 画像処理の拡張（srcset属性、lazy loading属性への対応強化）
- コードベースの簡素化

### 削除
- フォント機能の完全削除（技術的制約のため）
  - 管理画面からフォントチェックボックスを削除
  - フォント関連の設定オプション削除
  - フォント向けフィルター処理削除

### 技術的変更
- 新規設定オプション追加: `of_cache_yamero_apply_css`, `of_cache_yamero_apply_js`, `of_cache_yamero_apply_images`
- アンインストール処理の更新（新オプションのクリーンアップ対応）
- WordPressフィルターフックの拡張対応

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
