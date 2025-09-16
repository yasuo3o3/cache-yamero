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
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            yasuo3o3
 * Author URI:        https://yasuo-o.xyz/
 * Text Domain:       cache-yamero
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// セキュリティ: 直接アクセス防止
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// プラグインの基本定数
define( 'CACHE_YAMERO_VERSION', '1.0.0' );
define( 'CACHE_YAMERO_PLUGIN_FILE', __FILE__ );
define( 'CACHE_YAMERO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CACHE_YAMERO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Cache Yamero メインクラス
 */
class Cache_Yamero {

	/**
	 * プラグイン初期化
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'of_init' ) );
		add_action( 'admin_menu', array( $this, 'of_add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'of_admin_init' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'of_enqueue_scripts' ) );
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
		);

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( 'of_cache_yamero_' . $key ) ) {
				add_option( 'of_cache_yamero_' . $key, $value );
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
			update_option('of_cache_yamero_scope', isset($_POST['of_cache_yamero_scope']) ? sanitize_text_field( wp_unslash( $_POST['of_cache_yamero_scope'] ) ) : '');
			update_option('of_cache_yamero_start_datetime', isset($_POST['of_cache_yamero_start_datetime']) ? sanitize_text_field( wp_unslash( $_POST['of_cache_yamero_start_datetime'] ) ) : '');
			update_option('of_cache_yamero_end_datetime', isset($_POST['of_cache_yamero_end_datetime']) ? sanitize_text_field( wp_unslash( $_POST['of_cache_yamero_end_datetime'] ) ) : '');
			update_option( 'of_cache_yamero_get_form_support', isset( $_POST['of_cache_yamero_get_form_support'] ) ? 1 : 0 );
			update_option( 'of_cache_yamero_url_cleanup', isset( $_POST['of_cache_yamero_url_cleanup'] ) ? 1 : 0 );

			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( '設定を保存しました。', 'cache-yamero' ) . '</p></div>';
		}

		$enabled           = get_option( 'of_cache_yamero_enabled', false );
		$scope             = get_option( 'of_cache_yamero_scope', 'admin_only' );
		$start_datetime    = get_option( 'of_cache_yamero_start_datetime', '' );
		$end_datetime      = get_option( 'of_cache_yamero_end_datetime', '' );
		$get_form_support  = get_option( 'of_cache_yamero_get_form_support', true );
		$url_cleanup       = get_option( 'of_cache_yamero_url_cleanup', true );
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
								<span class="description"><?php esc_html_e( 'GETフォーム送信時にもキャッシュ無効化パラメータを付与します。', 'cache-yamero' ); ?></span>
							</p>
							<p>
								<label>
									<input type="checkbox" name="of_cache_yamero_url_cleanup" value="1" <?php checked( $url_cleanup ); ?> />
									<?php esc_html_e( 'URLクリーンアップ', 'cache-yamero' ); ?>
								</label>
								<span class="description"><?php esc_html_e( '表示後にアドレスバーからcache-yameroパラメータを除去します。', 'cache-yamero' ); ?></span>
							</p>
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

		$current_time = current_time( 'timestamp' );

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
}

// プラグイン初期化
new Cache_Yamero();