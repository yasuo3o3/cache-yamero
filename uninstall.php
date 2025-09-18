<?php
/**
 * Cache Yamero アンインストール処理
 *
 * プラグインがアンインストールされる際に、プラグイン専用のオプションを削除します。
 *
 * @package CacheYamero
 */

// セキュリティ: アンインストール処理以外でのアクセスを防止
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// プラグイン専用オプションを削除
$options_to_delete = [
	'of_cache_yamero_enabled',
	'of_cache_yamero_scope',
	'of_cache_yamero_start_datetime',
	'of_cache_yamero_end_datetime',
	'of_cache_yamero_get_form_support',
	'of_cache_yamero_url_cleanup',
	'of_cache_yamero_apply_css',
	'of_cache_yamero_apply_js',
	'of_cache_yamero_apply_images',
];

foreach ( $options_to_delete as $option_name ) {
	delete_option( $option_name );
}

// マルチサイトの場合はサイト固有のオプションも削除
if ( is_multisite() ) {
	foreach ( $options_to_delete as $option_name ) {
		delete_site_option( $option_name );
	}
}