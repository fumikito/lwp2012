<?php
/*
 * このファイルはLWP SDKに必要なファイルをすべて読み込みます。
 * テーマファイルのfunctions.phpから一度だけ読み込んでください。
 */

/**
 * LWPerSDKとして動くファイルです
 * @version 1.0
 */
class LWPer_Theme_SDK{
	
	/**
	 * 翻訳ドメイン名
	 * @var string
	 */
	private $domain = 'lwp-sdk';
	
	/**
	 * SDKのベースディレクトリ
	 * @var string
	 */
	public $base_dir;
	
	/**
	 * SDKのベースURL
	 * @var string
	 */
	public $base_url;
	
	/**
	 * 更新メッセージ
	 * @var array
	 */
	private $updated = array();
	
	/**
	 * エラーメッセージ
	 * @var array
	 */
	private $errors = array();
	
	/**
	 * コンストラクタ
	 */
	public function __construct() {
		//翻訳ドメインの読み込み
		load_theme_textdomain($this->domain, dirname(__FILE__).'/languages');
		//ベース情報の設定
		$this->base_dir = dirname(__FILE__);
		$this->base_url = get_theme_root_uri().'/'.basename(dirname(dirname(__FILE__)))."/lwp-sdk";
		//設定ファイルを読み込み
		if(file_exists(get_stylesheet_directory().'/lwp-config.php')){
			require_once get_stylesheet_directory().'/lwp-config.php';
		}else{
			require_once dirname(__FILE__).'/lwp-config-default.php';
		}
		//ユーティリティファイルの読み込み
		foreach(scandir($this->base_dir.'/functions') as $file){
			if(preg_match('/\.php$/', $file)){
				include_once $this->base_dir.'/functions/'.$file;
			}
		}
		//メニューの登録
		add_action('admin_menu', array($this, 'admin_menu'), 1000);
		//警告があれば表示する
		add_action('admin_notices', array($this, 'admin_notices'));
		//JSおよびCSSの読み込み
		add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
		//Ajaxのエンドポイント
		add_action('wp_ajax_lwper_update_theme', array($this, 'admin_ajax'));
	}
	
	/**
	 * 管理画面にJSおよびCSSを読み込む
	 */
	public function admin_enqueue_scripts(){
		if(isset($_GET['page']) && $_GET['page'] == 'lwper-status'){
			wp_enqueue_script('lwp-theme-updater', $this->base_url.'/js/theme-updator.js', array('jquery-form', 'jquery-effects-highlight'), lwp_theme_version());
			wp_localize_script('lwp-theme-updater', 'LWPUpdater', array(
				'nonce' => wp_create_nonce('lwper_update_theme'),
				'aciton' => 'lwper_update_theme',
				'labelConfirm' => $this->_('サイトをメンテナンスモードに移行し、テーマを更新します。よろしいですか？'),
				'labelStart' => $this->_('ファイルシステムの権限をチェックしています')
			));
			wp_enqueue_style('jquery-ui-theme', $this->base_url.'/jquery-smoothness/jquery-ui-1.9.2.custom.min.css', null, '1.9.2');
			wp_enqueue_style('lwp-admin-screen', $this->base_url.'/compass/stylesheets/admin-screen.css', null, lwp_theme_version());
		}
	}
	
	/**
	 * メニューを出力する
	 */
	public function admin_menu(){
		//このテーマはLWPを使ったアップデートシステムを持っている場合は、アップデートページを表示する
		if(defined('LWPER_PROVIDER_URL') && defined('LWPER_THEME_ID') && LWPER_PROVIDER_URL && LWPER_THEME_ID){
			//アップデートがある場合は表示
			if(current_user_can('update_themes') && $this->needs_update()){
				$this->updated[] = sprintf($this->_('ご利用中のテーマ<strong>%1$s</strong>に利用可能な更新があります。<a href="%2$s">テーマステータス</a>から更新してください。'), wp_get_theme()->display('Name'), admin_url('themes.php?page=lwper-status'));
			}
			//接続アクション
			if(isset($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'lwper_get_theme_token')){
				if($this->generate_token($_REQUEST['lwp_user_login'], $_REQUEST['lwp_user_pass'])){
					$this->updated[] = sprintf($this->_('%sとの接続に成功しました'), LWPER_PROVIDE_NAME);
				}else{
					$this->errors[] = sprintf($this->_('%1$sとの接続に失敗しました。ユーザー名、パスワードが間違っていないか、または%1$sがダウンしていないか確認してください。'), LWPER_PROVIDE_NAME);
				}
			}
			//メニューの登録
			$icon_string = $this->needs_update()
					? sprintf('<span class="update-plugins count-%1$d" title="%2$s"><span class="%3$s-count">%1$d</span></span>', 1, $this->_('テーマの更新があります'), 'theme')
					: '';
			add_theme_page($this->_('ステータス'), $this->_('ステータス').$icon_string, 'update_themes', 'lwper-status', array($this, 'load_admin_template'));
		}
	}
	
	/**
	 * 管理画面のテンプレートを読み込む
	 */
	public function load_admin_template(){
		if(isset($_GET["page"]) && (false !== strpos($_GET["page"], "lwper-"))){
			$slug = str_replace("lwper-", "", $_GET["page"]);
			echo '<div class="wrap lwper-wrap">';
			if(file_exists($this->base_dir.DIRECTORY_SEPARATOR."templates".DIRECTORY_SEPARATOR."{$slug}.php")){
				require_once $this->base_dir.DIRECTORY_SEPARATOR."templates".DIRECTORY_SEPARATOR."{$slug}.php";
			}else{
				printf('<div class="error"><p>%s</p></div>', $this->_('テンプレートがありません'));
			}
			echo "</div>\n<!-- .lwper-wrap ends -->";
		}
	}
	
	/**
	 * ファイルを直接アップロードできるか
	 * @return boolean
	 */
	private function can_upload_directly(){
		//Check if wp-content temporary directory is writable
		$tmp_dir = trailingslashit(WP_CONTENT_DIR).'/upgrade';
		if(file_exists($tmp_dir)){
			if(!is_writable($tmp_dir)){
				return false;
			}
		}else{
			if(!is_writable(WP_CONTENT_DIR)){
				return false;
			}
		}
		//Check if tamporary directory is writable
		$theme_dir = wp_get_theme()->get_theme_root();
		if(!is_writable($theme_dir)){
			return false;
		}
		//Check if theme root is writable
		if(!is_writable(dirname($theme_dir))){
			return false;
		}
		//All green.
		return true;
	}
	
	/**
	 * 最新のバージョンを取得
	 * @return string
	 */
	private function get_repository_head_version($need_id = false){
		$list = $this->get_file_lists();
		$version = false;
		$id = false;
		if(!empty($list)){
			foreach($list as $file){
				if(!$version){
					$version = $file['name'];
					$id = $file['ID'];
				}elseif(version_compare($version, $file['name']) < 0){
					$version = $file['name'];
					$id = $file['ID'];
				}
			}
		}
		if(!$need_id){
			return $version ? $version : $this->_('なし');
		}else{
			return $id;
		}
	}
	
	/**
	 * アップデートが必要か否か
	 * @return boolean
	 */
	private function needs_update(){
		$current_version = lwp_theme_version();
		$latest = $this->get_repository_head_version();
		return (version_compare($current_version, $latest) < 0);
	}
	
	/**
	 * Returns file list
	 * @return array
	 */
	private function get_file_lists(){
		$file_list = get_transient('lwper-file-lists');
		if(false === $file_list){
			$client = $this->get_client();
			$client->query('lwp.file.listFiles', LWPER_THEME_ID);
			$file_list = $client->getResponse();
			set_transient('lwper-file-lists', $file_list, 60 * 60);
		}
		return $file_list;
	}
	
	/**
	 * 
	 * @param int $file_id
	 * @return array
	 */
	private function get_file($file_id){
		$client = $this->get_client();
		if($client->query('lwp.file.getFile', $this->get_provider_token(), $file_id)){
			return $client->getResponse();
		}else{
			return false;
		}
	}
	
	/**
	 * プロバイダーと接続されている場合にトークンを返す
	 * @return string
	 */
	private function get_provider_token(){
		return get_option('_lwp_theme_provider_token', false);
	}
	
	/**
	 * トークンを取得して保存する
	 * @param string $user_login
	 * @param string $user_pass
	 * @return boolean
	 */
	private function generate_token($user_login, $user_pass){
		$client = $this->get_client();
		$client->query('lwp.file.getToken', $user_login, $user_pass);
		$token = $client->getResponse();
		if($token){
			return update_option('_lwp_theme_provider_token', $token);
		}else{
			return false;
		}
	}
	
	/**
	 * XML-RPCクライアントを返す
	 * @return \IXR_Client
	 */
	private function get_client(){
		require_once ABSPATH . WPINC . '/class-IXR.php';
		$endpoint = trailingslashit(LWPER_PROVIDER_URL).'xmlrpc.php';
		return new IXR_Client($endpoint);
	}
	
	/**
	 * テーマ更新のエンドポイント
	 */
	public function admin_ajax(){
		$json = array(
			'success' => false,
			'message' => $this->_('テーマを更新する権限がありません')
		);
		if(isset($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'lwper_update_theme') && current_user_can('update_themes') && isset($_REQUEST['currentStep'], $_REQUEST['connection_type'])){
			//共通。ファイルシステムが有効化否か
			if(!WP_Filesystem($_REQUEST)){
				$json['message'] = $this->_('アップロードをするために必要な権限が得られませんでした。ユーザー名やパスワードをチェックしてください。');
			}else{
				/* @var $wp_filesystem WP_Filesystem_ftpext */
				global $wp_filesystem;
				$upgrade_dir = $wp_filesystem->wp_content_dir().'upgrade';
				$maintenance_file = $wp_filesystem->abspath() . '.maintenance';
				//ステップごとに実行
				switch($_REQUEST['currentStep']){
					case 0:
						//認証があってたので、この段階で成功
						$json['success'] = true;
						$json['message'] = sprintf($this->_('ファイルを%sからダウンロードします'), LWPER_PROVIDE_NAME);
						break;
					case 1:
						//アップデートすべきファイルの情報を取得
						$file_id = $this->get_repository_head_version(true);
						if(!$file_id){
							$json['message'] = sprintf($this->_('ダウンロードできるバージョンがありません。%sに問題があるかもしれません。'), LWPER_PROVIDE_NAME);
							break;
						}
						//ファイルのIDがわかったので、ダウンロードを試みる
						ini_set('memory_limit', '128M');
						$response = $this->get_file($file_id);
						if(!$response){
							$json['message'] = sprintf($this->_('テーマをダウンロードできませんでした。購入を済ませているかどうか、<a href="%1$s" target="_blank">%2$s</a>でご確認ください。'), LWPER_PROVIDER_URL, LWPER_PROVIDE_NAME);
							break;
						}
						//ファイルが見つかったので、保存を試みる
						$tmp_name = tempnam(sys_get_temp_dir(), 'lwper-');
						if(!isset($response['file'], $response['name'], $response['hash']) || !@file_put_contents($tmp_name, $response['file'])
							|| $response['hash'] != md5_file($tmp_name) || !@unlink($tmp_name)){
							$json['message'] = $this->_('テーマファイルを保存できませんでした。');
							break;
						}
						//保存は完了し、ハッシュも問題ないので、wp-content/upgradeディレクトリに移動
						$file_name = sanitize_file_name($response['path']);
						if(
							(!file_exists($upgrade_dir) && $wp_filesystem->mkdir($upgrade_dir))
								||
							!$wp_filesystem->is_writable($upgrade_dir)
								||
							!$wp_filesystem->put_contents($upgrade_dir.'/'.$file_name, $response['file'])
						){
							$json['message'] = sprintf($this->_('ファイルをWordPressディレクトリ%sに移動できませんでした。'), $upgrade_dir);
							break;
						}
						$json['success'] = true;
						$json['zip_name'] = $file_name;
						$json['message'] = $this->_('ファイルを展開しています');
						break;
					case 2:
						if(!isset($_REQUEST['zip_name']) || !($zip_name = $upgrade_dir.'/'.$_REQUEST['zip_name']) || !file_exists($zip_name)){
							$json['message'] = sprintf($this->_('解凍すべきファイルが見つかりませんでした')).$zip_name;
							break;
						}
						if(is_wp_error(unzip_file($zip_name, $upgrade_dir))){
							$json['message'] = $this->_('Zipファイルを展開できませんでした');
							$wp_filesystem->delete($zip_name);
							break;
						}
						$wp_filesystem->delete($zip_name);
						$json['success'] = true;
						$json['message'] = $this->_('メンテナンスモードに移行し、テーマファイルを入れ替えています');
						break;
					case 3:
						//ディレクトリの確認
						$theme_dir = dirname(dirname(__FILE__));
						$new_dir = $upgrade_dir."/".basename($theme_dir);
						if(!file_exists($new_dir)){
							$json['message'] = sprintf($this->_('アップデート用のディレクトリ%1$sが存在しません。これはダウンロードしたテーマのディレクトリ名が%2$sから変更しているためと考えられます。配布元の<a href="%4$s" target="_blank">%3$s</a>に問い合わせてください。'), $new_dir, basename($theme_dir), LWPER_PROVIDE_NAME, LWPER_PROVIDER_URL);
							break;
						}
						//メンテナンスモードに移行
						$maintenance_string = '<?php $upgrading = ' . time() . '; ?>';
						if(!$wp_filesystem->put_contents($maintenance_file, $maintenance_string, FS_CHMOD_FILE)){
							$json['message'] = $this->_('メンテナンスモードに移行できませんでした');
							break;
						}
						//テーマディレクトリを消す
						$wp_filesystem->delete($theme_dir, true);
						//テーマディレクトリを移動する
						$wp_filesystem->move($new_dir, $theme_dir, true);
						//メンテナンスモード解除
						if(!$wp_filesystem->delete($maintenance_file)){
							$json['message'] = sprintf($this->_('メンテナンスモードを解除できませんでした。<code>%s</code>を削除してください。'), $maintenance_file);
							break;
						}
						//テーマのキャッシュを削除
						wp_get_theme()->cache_delete();
						$json['success'] = true;
						$json['message'] = $this->_('テーマのアップデートが完了しました。ページを再読み込みします。');
						break;
				}
			}
		}
		header('Content-Type: application/json');
		echo json_encode($json);
		die();
	}
	
	/**
	 * 管理画面にエラーメッセージを出す
	 */
	public function admin_notices(){
		if(!empty($this->updated)){
			printf('<div class="updated">%s</div>', implode('', array_map(create_function('$msg', 'return "<p>".$msg."</p>"; '), $this->updated)));
		}
		if(!empty($this->errors)){
			printf('<div class="error">%s</div>', implode('', array_map(create_function('$msg', 'return "<p>".$msg."</p>"; '), $this->errors)));
		}
	}
	
	/**
	 * __()のエイリアス
	 * @param string $string
	 * @return string
	 */
	public function _($string){
		return __($string, $this->domain);
	}
	
	/**
	 * _e()のエイリアス
	 * @param string $string
	 */
	public function e($string){
		_e($string, $this->domain);
	}
}
$lwper_sdk = new LWPer_Theme_SDK();