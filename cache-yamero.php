<?php

/**
 * Cache Yamero
 *
 * @package           CacheYamero
 * @author            yasuo3o3
 * @copyright         2024 yasuo3o3
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Cache Yamero
 * Plugin URI:        https://yasuo-o.xyz/
 * Description:       人の操作時だけページ遷移URLに ?cache-yamero=YYYYMMDDHHmmss を付与してキャッシュを無効化する開発補助ツール。DOMは汚さない。公開前後の一時運用にも対応。
 * Version:           1.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            yasuo3o3
 * Author URI:        https://yasuo-o.xyz/
 * Text Domain:       cache-yamero
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// セキュリティ: 直接アクセス防止
if (! defined('ABSPATH')) {
    exit;
}
// プラグインの基本定数
define('CACHE_YAMERO_VERSION', '1.1.0');
define('CACHE_YAMERO_PLUGIN_FILE', __FILE__);
define('CACHE_YAMERO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CACHE_YAMERO_PLUGIN_URL', plugin_dir_url(__FILE__));
// クラスファイルを読み込み
require_once CACHE_YAMERO_PLUGIN_DIR . 'class-of-cache-yamero.php';
// プラグイン初期化
new OF_Cache_Yamero();
