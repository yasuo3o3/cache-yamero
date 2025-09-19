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
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// プラグインの基本定数
define( 'CACHE_YAMERO_VERSION', '1.1.0' );
define( 'CACHE_YAMERO_PLUGIN_FILE', __FILE__ );
define( 'CACHE_YAMERO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CACHE_YAMERO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
/**
 * Cache Yamero メインクラス
 */
class OF_Cache_Yamero {
	/**
	 * プラグイン初期化
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'of_init' ) );
		add_action( 'admin_menu', array( $this, 'of_add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'of_admin_init' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'of_enqueue_scripts' ) );
		$this->of_init_resource_hooks();
	}
	/**
	 * プラグイン初期化処理
	 */
	public function of_init() {
		// デフォルトオプションを設定
		$this->of_set_default_options();
	}
	/**
	 * デフォルトオプションを設定
	 */
	private function of_set_default_options() {
		$defaults = array(
			'enabled'           => false,
			'scope'             => 'admin_only',
			'start_datetime'    => '',
			'end_datetime'      => '',
			'get_form_support'  => true,
			'url_cleanup'       => true,
			'apply_css'         => true,
			'apply_js'          => true,
			'apply_images'      => true,
		);
		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( 'of_cache_yamero_' . $key ) ) {
				add_option( 'of_cache_yamero_' . $key, $value, '', false );
			}
		}
	}
	/**
	 * 管理メニューに設定ページを追加
	 */
	public function of_add_admin_menu() {
		add_options_page(
			__( 'Cache Yamero 設定', 'cache-yamero' ),
			__( 'Cache Yamero', 'cache-yamero' ),
			'manage_options',
			'cache-yamero',
			array( $this, 'of_admin_page' )
		);
		// メニュー装飾とCSS注入のフックを追加
		add_action( 'admin_menu', array( $this, 'of_decorate_admin_menu' ), 1000 );
		add_action( 'admin_head', array( $this, 'of_admin_head_styles' ) );
	}
	/**
	 * 管理画面初期化
	 */
	public function of_admin_init() {
		register_setting( 'of_cache_yamero_settings', 'of_cache_yamero_enabled', array(
			'sanitize_callback' => 'absint',
		) );
		register_setting( 'of_cache_yamero_settings', 'of_cache_yamero_scope', array(
			'sanitize_callback' => 'sanitize_text_field',
		) );
		register_setting( 'of_cache_yamero_settings', 'of_cache_yamero_start_datetime', array(
			'sanitize_callback' => 'sanitize_text_field',
		) );
		register_setting( 'of_cache_yamero_settings', 'of_cache_yamero_end_datetime', array(
			'sanitize_callback' => 'sanitize_text_field',
		) );
		register_setting( 'of_cache_yamero_settings', 'of_cache_yamero_get_form_support', array(
			'sanitize_callback' => 'absint',
		) );
		register_setting( 'of_cache_yamero_settings', 'of_cache_yamero_url_cleanup', array(
			'sanitize_callback' => 'absint',
		) );
		register_setting( 'of_cache_yamero_settings', 'of_cache_yamero_apply_css', array(
			'sanitize_callback' => 'absint',
		) );
		register_setting( 'of_cache_yamero_settings', 'of_cache_yamero_apply_js', array(
			'sanitize_callback' => 'absint',
		) );
		register_setting( 'of_cache_yamero_settings', 'of_cache_yamero_apply_images', array(
			'sanitize_callback' => 'absint',
		) );
	}
	/**
	 * 設定値を一括取得（キャッシュ対応）
	 */
	private function of_get_cached_options() {
		static $options = null;
		if ( null === $options ) {
			$options = array(
				'enabled'           => get_option( 'of_cache_yamero_enabled', false ),
				'scope'             => get_option( 'of_cache_yamero_scope', 'admin_only' ),
				'start_datetime'    => get_option( 'of_cache_yamero_start_datetime', '' ),
				'end_datetime'      => get_option( 'of_cache_yamero_end_datetime', '' ),
				'get_form_support'  => get_option( 'of_cache_yamero_get_form_support', true ),
				'url_cleanup'       => get_option( 'of_cache_yamero_url_cleanup', true ),
				'apply_css'         => get_option( 'of_cache_yamero_apply_css', true ),
				'apply_js'          => get_option( 'of_cache_yamero_apply_js', true ),
				'apply_images'      => get_option( 'of_cache_yamero_apply_images', true ),
			);
		}
		return $options;
	}

	/**
	 * 管理画面設定ページ
	 */
	public function of_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'このページにアクセスする権限がありません。', 'cache-yamero' ) );
		}
		if ( isset( $_POST['submit'] ) ) {
			check_admin_referer( 'of_cache_yamero_settings' );
			update_option( 'of_cache_yamero_enabled', isset( $_POST['of_cache_yamero_enabled'] ) ? 1 : 0 );
			$scope = isset( $_POST['of_cache_yamero_scope'] ) ? sanitize_key( wp_unslash( $_POST['of_cache_yamero_scope'] ) ) : '';
			update_option( 'of_cache_yamero_scope', $this->validate_scope( $scope ) );
			$start_datetime = isset( $_POST['of_cache_yamero_start_datetime'] ) ? sanitize_text_field( wp_unslash( $_POST['of_cache_yamero_start_datetime'] ) ) : '';
			update_option( 'of_cache_yamero_start_datetime', $this->validate_datetime( $start_datetime ) );
			$end_datetime = isset( $_POST['of_cache_yamero_end_datetime'] ) ? sanitize_text_field( wp_unslash( $_POST['of_cache_yamero_end_datetime'] ) ) : '';
			update_option( 'of_cache_yamero_end_datetime', $this->validate_datetime( $end_datetime ) );
			update_option( 'of_cache_yamero_get_form_support', isset( $_POST['of_cache_yamero_get_form_support'] ) ? 1 : 0 );
			update_option( 'of_cache_yamero_url_cleanup', isset( $_POST['of_cache_yamero_url_cleanup'] ) ? 1 : 0 );
			update_option( 'of_cache_yamero_apply_css', isset( $_POST['of_cache_yamero_apply_css'] ) ? 1 : 0 );
			update_option( 'of_cache_yamero_apply_js', isset( $_POST['of_cache_yamero_apply_js'] ) ? 1 : 0 );
			update_option( 'of_cache_yamero_apply_images', isset( $_POST['of_cache_yamero_apply_images'] ) ? 1 : 0 );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( '設定を保存しました。', 'cache-yamero' ) . '</p></div>';
		}
		$options = $this->of_get_cached_options();
		$enabled           = $options['enabled'];
		$scope             = $options['scope'];
		$start_datetime    = $options['start_datetime'];
		$end_datetime      = $options['end_datetime'];
		$get_form_support  = $options['get_form_support'];
		$url_cleanup       = $options['url_cleanup'];
		$apply_css         = $options['apply_css'];
		$apply_js          = $options['apply_js'];
		$apply_images      = $options['apply_images'];
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p><?php esc_html_e( '開発時のキャッシュを無効化するためのプラグインです。人の操作時のみ ?cache-yamero=YYYYMMDDHHmmss をURLに付与します。', 'cache-yamero' ); ?></p>
			<form method="post" action="">
				<?php wp_nonce_field( 'of_cache_yamero_settings' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( '有効化', 'cache-yamero' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="of_cache_yamero_enabled" value="1" <?php checked( $enabled ); ?> />
								<?php esc_html_e( 'Cache Yamero を有効にする', 'cache-yamero' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( '適用範囲', 'cache-yamero' ); ?></th>
						<td>
							<label>
								<input type="radio" name="of_cache_yamero_scope" value="admin_only" <?php checked( $scope, 'admin_only' ); ?> />
								<?php esc_html_e( '管理者のみ', 'cache-yamero' ); ?>
							</label><br />
							<label>
								<input type="radio" name="of_cache_yamero_scope" value="all_visitors" <?php checked( $scope, 'all_visitors' ); ?> />
								<?php esc_html_e( 'すべての訪問者', 'cache-yamero' ); ?>
							</label>
							<p class="description"><?php esc_html_e( '「管理者のみ」は管理権限を持つユーザーのみに適用されます。', 'cache-yamero' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( '有効期間', 'cache-yamero' ); ?></th>
						<td>
							<p>
								<label><?php esc_html_e( '開始日時:', 'cache-yamero' ); ?>
								<input type="datetime-local" name="of_cache_yamero_start_datetime" value="<?php echo esc_attr( $start_datetime ); ?>" /></label>
							</p>
							<p>
								<label><?php esc_html_e( '終了日時:', 'cache-yamero' ); ?>
								<input type="datetime-local" name="of_cache_yamero_end_datetime" value="<?php echo esc_attr( $end_datetime ); ?>" /></label>
							</p>
							<p class="description"><?php esc_html_e( '空白の場合は期間制限なしです。', 'cache-yamero' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'オプション', 'cache-yamero' ); ?></th>
						<td>
							<p>
								<label>
									<input type="checkbox" name="of_cache_yamero_get_form_support" value="1" <?php checked( $get_form_support ); ?> />
									<?php esc_html_e( 'GETフォーム対応', 'cache-yamero' ); ?>
								</label>
								<span class="description">
									<?php esc_html_e( 'GETフォーム送信時にもキャッシュ無効化パラメータを付与します。', 'cache-yamero' ); ?>
									<br>
									<?php esc_html_e( '（フォーム送信後にキャッシュ済みページを返されるのを避け、必ず最新の結果を取得）', 'cache-yamero' ); ?>
								</span>
							</p>
							<p>
								<label>
									<input type="checkbox" name="of_cache_yamero_url_cleanup" value="1" <?php checked( $url_cleanup ); ?> />
									<?php esc_html_e( 'URLクリーンアップ', 'cache-yamero' ); ?>
								</label>
								<span class="description">
									<?php esc_html_e( '表示後にアドレスバーからcache-yameroパラメータを除去します。', 'cache-yamero' ); ?>
									<br>
									<?php esc_html_e( '（ユーザーには美しいURLを表示、開発者には確実なキャッシュ無効化を提供する）', 'cache-yamero' ); ?>
								</span>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( '対象リソース', 'cache-yamero' ); ?></th>
						<td>
							<p>
								<label>
									<input type="checkbox" name="of_cache_yamero_apply_css" value="1" <?php checked( $apply_css ); ?> />
									<?php esc_html_e( 'CSS', 'cache-yamero' ); ?>
								</label>
								<label style="margin-left: 15px;">
									<input type="checkbox" name="of_cache_yamero_apply_js" value="1" <?php checked( $apply_js ); ?> />
									<?php esc_html_e( 'JavaScript', 'cache-yamero' ); ?>
								</label>
								<label style="margin-left: 15px;">
									<input type="checkbox" name="of_cache_yamero_apply_images" value="1" <?php checked( $apply_images ); ?> />
									<?php esc_html_e( '画像', 'cache-yamero' ); ?>
								</label>
							</p>
							<p class="description"><?php esc_html_e( 'チェックした種類のリソースにキャッシュ無効化パラメータを付与します。', 'cache-yamero' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
	/**
	 * フロントエンドスクリプトを読み込む
	 */
	public function of_enqueue_scripts() {
		if ( is_admin() || ! $this->of_should_load_script() ) {
			return;
		}
		wp_enqueue_script(
			'cache-yamero',
			CACHE_YAMERO_PLUGIN_URL . 'assets/js/cache-yamero.js',
			array(),
			CACHE_YAMERO_VERSION,
			true
		);
		// PHP設定をJavaScriptに渡す
		wp_localize_script(
			'cache-yamero',
			'cacheYamero',
			array(
				'enabled'         => $this->of_is_enabled(),
				'getFormSupport'  => (bool) get_option( 'of_cache_yamero_get_form_support', true ),
				'urlCleanup'      => (bool) get_option( 'of_cache_yamero_url_cleanup', true ),
			)
		);
	}
	/**
	 * スクリプトを読み込むべきかチェック
	 */
	private function of_should_load_script() {
		$enabled = get_option( 'of_cache_yamero_enabled', false );
		if ( ! $enabled ) {
			return false;
		}
		// 適用範囲チェック
		$scope = get_option( 'of_cache_yamero_scope', 'admin_only' );
		if ( 'admin_only' === $scope && ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		// 日時範囲チェック
		if ( ! $this->of_is_within_datetime_range() ) {
			return false;
		}
		return true;
	}
	/**
	 * 現在有効かどうか
	 */
	private function of_is_enabled() {
		return $this->of_should_load_script();
	}
	/**
	 * 日時範囲内かチェック
	 */
	private function of_is_within_datetime_range() {
		$start_datetime = get_option( 'of_cache_yamero_start_datetime', '' );
		$end_datetime   = get_option( 'of_cache_yamero_end_datetime', '' );
		if ( empty( $start_datetime ) && empty( $end_datetime ) ) {
			return true;
		}
		// time()を使用してUnixエポック時刻で比較（タイムゾーン影響を排除）
		$current_time = time();
		if ( ! empty( $start_datetime ) ) {
			$start_time = strtotime( $start_datetime );
			if ( $current_time < $start_time ) {
				return false;
			}
		}
		if ( ! empty( $end_datetime ) ) {
			$end_time = strtotime( $end_datetime );
			if ( $current_time > $end_time ) {
				return false;
			}
		}
		return true;
	}
	/**
	 * リソースフックの初期化
	 */
	private function of_init_resource_hooks() {
		// 旧フック名の互換性サポート（非推奨）
		$legacy_priority = apply_filters_deprecated(
			'cache_yamero_loader_priority',
			array( 10 ),
			'1.1.0',
			'of_cache_yamero_loader_priority'
		);
		$priority = apply_filters( 'of_cache_yamero_loader_priority', $legacy_priority );
		add_filter( 'style_loader_src', array( $this, 'of_add_cache_param_to_style' ), $priority );
		add_filter( 'script_loader_src', array( $this, 'of_add_cache_param_to_script' ), $priority );
		add_filter( 'wp_get_attachment_url', array( $this, 'of_add_cache_param_to_attachment_url' ), $priority );
		add_filter( 'wp_get_attachment_image_src', array( $this, 'of_add_cache_param_to_attachment_image_src' ), $priority );
		add_filter( 'wp_get_attachment_image_url', array( $this, 'of_add_cache_param_to_attachment_url' ), $priority );
		add_filter( 'wp_get_attachment_image_attributes', array( $this, 'of_add_cache_param_to_attachment_image_attributes' ), $priority, 3 );
		add_filter( 'wp_calculate_image_srcset', array( $this, 'of_add_cache_param_to_image_srcset' ), $priority );
		add_filter( 'the_content', array( $this, 'of_filter_content_images' ), $priority );
		add_filter( 'post_thumbnail_html', array( $this, 'of_filter_thumbnail_images' ), $priority );
	}
	/**
	 * 現在のユーザーに対して有効かチェック
	 */
	private function of_is_active_for_current_user() {
		$enabled = get_option( 'of_cache_yamero_enabled', false );
		if ( ! $enabled ) {
			return false;
		}
		$scope = get_option( 'of_cache_yamero_scope', 'admin_only' );
		if ( 'admin_only' === $scope && ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		return $this->of_is_within_datetime_range();
	}
	/**
	 * リクエスト共通のタイムスタンプを取得
	 */
	private function of_get_request_timestamp() {
		static $timestamp = null;
		if ( null === $timestamp ) {
			$timestamp = gmdate( 'YmdHis' );
		}
		return $timestamp;
	}
	/**
	 * URLにキャッシュパラメータを追加
	 */
	private function of_add_cache_param_to_url( $url, $resource_type = null ) {
		if ( empty( $url ) || ! $this->of_is_active_for_current_user() ) {
			return $url;
		}
		if ( $resource_type ) {
			$defaults = array(
				'css' => true,
				'js' => true,
				'images' => true,
			);
			$default_value = isset( $defaults[ $resource_type ] ) ? $defaults[ $resource_type ] : true;
			if ( ! get_option( "of_cache_yamero_apply_{$resource_type}", $default_value ) ) {
				return $url;
			}
		}
		if ( preg_match( '/^(data:|blob:|about:)/', $url ) ) {
			return $url;
		}
		$parsed = wp_parse_url( $url );
		if ( ! $parsed ) {
			return $url;
		}
		$query_args = array();
		if ( ! empty( $parsed['query'] ) ) {
			wp_parse_str( $parsed['query'], $query_args );
		}
		$query_args['cache-yamero'] = $this->of_get_request_timestamp();
		$new_url = '';
		if ( ! empty( $parsed['scheme'] ) ) {
			$new_url .= $parsed['scheme'] . '://';
		}
		if ( ! empty( $parsed['host'] ) ) {
			$new_url .= $parsed['host'];
		}
		if ( ! empty( $parsed['port'] ) ) {
			$new_url .= ':' . $parsed['port'];
		}
		if ( ! empty( $parsed['path'] ) ) {
			$new_url .= $parsed['path'];
		}
		$new_url = add_query_arg( $query_args, $new_url );
		if ( ! empty( $parsed['fragment'] ) ) {
			$new_url .= '#' . $parsed['fragment'];
		}
		return $new_url;
	}
	/**
	 * srcset文字列にキャッシュパラメータを追加
	 */
	private function of_add_cache_param_to_srcset( $srcset, $resource_type = null ) {
		if ( empty( $srcset ) || ! $this->of_is_active_for_current_user() ) {
			return $srcset;
		}
		$sources = explode( ',', $srcset );
		$updated_sources = array();
		foreach ( $sources as $source ) {
			$source = trim( $source );
			if ( empty( $source ) ) {
				continue;
			}
			$parts = preg_split( '/\s+/', $source, 2 );
			if ( ! empty( $parts[0] ) ) {
				$url = $this->of_add_cache_param_to_url( $parts[0], $resource_type );
				$descriptor = isset( $parts[1] ) ? ' ' . $parts[1] : '';
				$updated_sources[] = $url . $descriptor;
			}
		}
		return implode( ', ', $updated_sources );
	}
	/**
	 * HTMLコンテンツ内のLazy属性をフィルタ
	 */
	private function of_filter_lazy_attributes( $html ) {
		if ( empty( $html ) || ! $this->of_is_active_for_current_user() ) {
			return $html;
		}
		$lazy_attrs = array( 'data-src', 'data-srcset', 'data-original', 'data-lazy', 'data-lazy-src' );
		foreach ( $lazy_attrs as $attr ) {
			$html = preg_replace_callback(
				'/(<(?:img|source)[^>]*\s' . preg_quote( $attr, '/' ) . '=")([^"]+)(")/i',
				function( $matches ) use ( $attr ) {
					$url = $matches[2];
					if ( 'data-srcset' === $attr ) {
						$new_url = $this->of_add_cache_param_to_srcset( $url, 'images' );
					} else {
						$new_url = $this->of_add_cache_param_to_url( $url, 'images' );
					}
					return $matches[1] . $new_url . $matches[3];
				},
				$html
			);
		}
		$html = preg_replace_callback(
			'/(<(?:img|source)[^>]*\ssrc=")([^"]+)(")/i',
			function( $matches ) {
				return $matches[1] . $this->of_add_cache_param_to_url( $matches[2], 'images' ) . $matches[3];
			},
			$html
		);
		$html = preg_replace_callback(
			'/(<(?:img|source)[^>]*\ssrcset=")([^"]+)(")/i',
			function( $matches ) {
				return $matches[1] . $this->of_add_cache_param_to_srcset( $matches[2], 'images' ) . $matches[3];
			},
			$html
		);
		return $html;
	}
	/**
	 * スタイルローダーのURLをフィルタ
	 */
	public function of_add_cache_param_to_style( $src ) {
		return $this->of_add_cache_param_to_url( $src, 'css' );
	}
	/**
	 * スクリプトローダーのURLをフィルタ
	 */
	public function of_add_cache_param_to_script( $src ) {
		return $this->of_add_cache_param_to_url( $src, 'js' );
	}
	/**
	 * 添付ファイルURLをフィルタ
	 */
	public function of_add_cache_param_to_attachment_url( $url ) {
		return $this->of_add_cache_param_to_url( $url, 'images' );
	}
	/**
	 * 添付ファイル画像srcをフィルタ
	 */
	public function of_add_cache_param_to_attachment_image_src( $image ) {
		if ( is_array( $image ) && isset( $image[0] ) ) {
			$image[0] = $this->of_add_cache_param_to_url( $image[0], 'images' );
		}
		return $image;
	}
	/**
	 * 添付ファイル画像属性をフィルタ
	 */
	public function of_add_cache_param_to_attachment_image_attributes( $attr, $attachment, $size ) {
		if ( isset( $attr['src'] ) ) {
			$attr['src'] = $this->of_add_cache_param_to_url( $attr['src'], 'images' );
		}
		if ( isset( $attr['srcset'] ) ) {
			$attr['srcset'] = $this->of_add_cache_param_to_srcset( $attr['srcset'], 'images' );
		}
		return $attr;
	}
	/**
	 * コンテンツ内の画像をフィルタ
	 */
	public function of_filter_content_images( $content ) {
		return $this->of_filter_lazy_attributes( $content );
	}
	/**
	 * サムネイル画像をフィルタ
	 */
	public function of_filter_thumbnail_images( $html ) {
		return $this->of_filter_lazy_attributes( $html );
	}
	/**
	 * wp_calculate_image_srcsetの配列にキャッシュパラメータを追加
	 */
	public function of_add_cache_param_to_image_srcset( $sources ) {
		if ( ! is_array( $sources ) || ! $this->of_is_active_for_current_user() ) {
			return $sources;
		}
		foreach ( $sources as $width => $source ) {
			if ( isset( $source['url'] ) ) {
				$sources[ $width ]['url'] = $this->of_add_cache_param_to_url( $source['url'], 'images' );
			}
		}
		return $sources;
	}
	/**
	 * スコープ値の検証
	 *
	 * @param string $scope スコープ値
	 * @return string 検証済みスコープ値
	 */
	private function validate_scope( $scope ) {
		$allowed_scopes = array( 'admin_only', 'all_visitors' );
		return in_array( $scope, $allowed_scopes, true ) ? $scope : 'admin_only';
	}
	/**
	 * 日時入力の検証
	 *
	 * @param string $datetime 日時文字列
	 * @return string 検証済み日時文字列
	 */
	private function validate_datetime( $datetime ) {
		if ( empty( $datetime ) ) {
			return '';
		}

		// PHP 5.3+ DateTime::createFromFormat の安全なフォールバック
		if ( class_exists( 'DateTime' ) && method_exists( 'DateTime', 'createFromFormat' ) ) {
			$parsed = DateTime::createFromFormat( 'Y-m-d\TH:i', $datetime );
			if ( false !== $parsed ) {
				return $parsed->format( 'Y-m-d\TH:i' );
			}
		}

		// フォールバック: strtotime での基本検証
		$timestamp = strtotime( $datetime );
		if ( false !== $timestamp ) {
			return gmdate( 'Y-m-d\TH:i', $timestamp );
		}

		return '';
	}

	/**
	 * 管理メニューの状態を取得
	 *
	 * @return array 状態情報配列 ['bg' => 'red'|'black', 'label' => 'ラベル', 'status' => 'active'|'pending'|'disabled'|'ended']
	 */
	private function of_get_admin_menu_state() {
		$enabled = get_option( 'of_cache_yamero_enabled', false );
		// time()を使用してUnixエポック時刻で比較（タイムゾーン影響を排除）
		$now = time();
		$start_datetime = get_option( 'of_cache_yamero_start_datetime', '' );
		$end_datetime = get_option( 'of_cache_yamero_end_datetime', '' );

		$start_ts = ! empty( $start_datetime ) ? strtotime( $start_datetime ) : null;
		$end_ts = ! empty( $end_datetime ) ? strtotime( $end_datetime ) : null;

		// 言語判定（get_locale()がen_で始まるかどうか）
		$locale = get_locale();
		$is_english = ( 0 === strpos( $locale, 'en_' ) || 'en' === $locale );

		// 状態判定の優先順位
		if ( ! $enabled ) {
			// 有効チェックOFF
			return array(
				'bg' => 'black',
				'label' => $is_english ? 'Disabled' : '無効',
				'status' => 'disabled'
			);
		}

		if ( $end_ts && $now >= $end_ts ) {
			// 有効チェックON かつ 終了時刻を過ぎている
			return array(
				'bg' => 'black',
				'label' => $is_english ? 'Ended' : '終了',
				'status' => 'ended'
			);
		}

		if ( $start_ts && $now < $start_ts ) {
			// 有効チェックON かつ 開始時刻前
			return array(
				'bg' => 'red',
				'label' => $is_english ? 'Pending' : '待機',
				'status' => 'pending'
			);
		}

		// 上記以外（有効状態）
		return array(
			'bg' => 'red',
			'label' => $is_english ? 'Active' : '有効',
			'status' => 'active'
		);
	}

	/**
	 * 管理メニューDOM装飾用JS出力
	 */
	public function of_decorate_admin_menu() {
		// 管理画面以外では何もしない
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_footer', array( $this, 'of_admin_footer_menu_decorator' ) );
	}

	/**
	 * admin_footerでDOM装飾用JavaScriptを出力
	 */
	public function of_admin_footer_menu_decorator() {
		$state = $this->of_get_admin_menu_state();

		// 翻訳対応のスクリーンリーダー文字列
		$screen_reader_text = sprintf(
			/* translators: %s: Cache Yamero status label */
			__( 'Cache Yamero: %s', 'cache-yamero' ),
			$state['label']
		);
		?>
		<script type="text/javascript">
		(function() {
			'use strict';

			var state = <?php echo wp_json_encode( $state ); ?>;
			var screenReaderText = <?php echo wp_json_encode( $screen_reader_text ); ?>;

			// 親メニュー「設定」にドット追加（active/pending時のみ）
			if (state.status === 'active' || state.status === 'pending') {
				var settingsMenu = document.querySelector('#adminmenu .menu-top a[href="options-general.php"]');
				if (settingsMenu && !settingsMenu.querySelector('.cy-state-dot')) {
					var dotHtml = '<span class="cy-state-dot" data-status="' + state.status + '"></span>';
					var srHtml = '<span class="screen-reader-text">' + screenReaderText + '</span>';
					settingsMenu.insertAdjacentHTML('beforeend', dotHtml + srHtml);
				}
			}

			// 子メニュー「Cache Yamero」にバッジ追加
			var cacheYameroMenu = document.querySelector('#adminmenu .settings_page_cache-yamero a');
			if (cacheYameroMenu && !cacheYameroMenu.querySelector('.cy-badge')) {
				var badgeHtml = '<span class="cy-badge" data-status="' + state.status + '">' +
					'<span class="cy-badge-text">' + state.label + '</span></span>';
				cacheYameroMenu.insertAdjacentHTML('beforeend', badgeHtml);
			}
		})();
		</script>
		<?php
	}

	/**
	 * 管理画面ヘッダーにスタイルを追加
	 */
	public function of_admin_head_styles() {
		// 管理画面以外では何もしない
		if ( ! is_admin() ) {
			return;
		}

		$state = $this->of_get_admin_menu_state();

		echo '<style type="text/css">';

		// バッジのベースCSS（常時出力）
		echo '.cy-badge{display:inline-block;margin-left:.4em;padding:.08em .45em;border-radius:1em;font-size:11px;line-height:1.9;}';
		echo '#adminmenu .settings_page_cache-yamero .cy-badge{vertical-align:middle;}';

		// 親ドットのベースCSS
		echo '#adminmenu .menu-top > a .cy-state-dot{display:inline-block;width:9px;height:9px;border-radius:50%;margin-left:.5em;vertical-align:middle;}';

		// 新仕様の配色マップ（バッジ）
		echo '.cy-badge[data-status="active"]{background:#d63638;color:#fff;}';
		echo '.cy-badge[data-status="pending"]{background:#dba617;color:#fff;}';
		echo '.cy-badge[data-status="ended"]{background:#8a8f98;color:#fff;}';
		echo '.cy-badge[data-status="disabled"]{background:#1d2327;color:#fff;border:1px solid rgba(255,255,255,.15);}';

		// 新仕様の配色マップ（親ドット）
		echo '.cy-state-dot[data-status="active"]{background:#d63638;}';
		echo '.cy-state-dot[data-status="pending"]{background:#dba617;}';
		echo '.cy-state-dot[data-status="ended"]{background:#8a8f98;}';
		echo '.cy-state-dot[data-status="disabled"]{background:#1d2327;box-shadow:inset 0 0 0 1px rgba(255,255,255,.15);}';

		// 背景が赤の場合のみメニュー背景色を出力
		if ( 'red' === $state['bg'] ) {
			echo '#adminmenu .settings_page_cache-yamero > a{background:#d63638 !important;color:#fff !important;}';
			echo '#adminmenu .settings_page_cache-yamero > a:hover{background:#b32d2e !important;}';
		}

		echo '</style>';
	}
}
// プラグイン初期化
new OF_Cache_Yamero();
