<?php

/**
 * Literally WordPressのフォームに表示する
 */
function _lwper_show_form($slug, $args){
	$message = null;
	switch($slug){
		case 'selection':
			$message = <<<EOS
PayPalの場合は以下の決済情報を使ってください。<br />
メールアドレス: <strong>test1_1299444313_per@yahoo.co.jp</strong>
パスワード: <strong>299444216</strong>
EOS;
			break;
		case 'payment':
			if(isset($_REQUEST['lwp-method'])){
				switch($_REQUEST['lwp-method']){
					case 'gmo-cc':
						$message = <<<EOS
有効なカードとして認識されるのは次の情報です。
カード番号：<strong>4111111111111111</strong>
有効期限：<strong>2013年1月</strong>
セキュリティコード：<strong>3~4桁の任意の番号</strong>
EOS;
						break;
					case 'sb-cc':
						$message = <<<EOS
カードのバリデーション（カード番号、有効期限、セキュリティコード）が通れば、決済は完了します。
ただし、実際に送信しているのはサンドボックス専用のカードです。
EOS;
						break;
				}
			}
			break;
		case 'payment-info':
			$message = <<<EOS
コンビニ決済、PayEasyはユーザーが支払いを行うとLWPが通知を受け取り、決済ステータスを完了に変更します。これはテスト環境なので、コンビニに行っても払うことはできません。
EOS;
			break;
	}
	if($message){
		printf('<p style="clear:both;" class="message">%s<br /><small>※デモサイトなので、実際には課金されません。</small></p>', nl2br($message));
	}
}
add_action('lwp_after_form', '_lwper_show_form', 10, 2);

/**
 * ヘッダーの画像を返す
 * @param string $title
 * @return string
 */
function _lwper_form_header($title){
	$img = get_header_image();
	return !empty($img) ? sprintf('<img alt="%s" src="%s" style="width: 100%%; height: auto;" />', esc_attr($title), $img) : $title;
}
add_filter('lwp_form_title', '_lwper_form_header');

/**
 * カスタム投稿タイプを追加する
 */
function _lwper_custom_post_types(){
	register_post_type('event', array(
		'label' => 'イベント',
		'labels' => _lwper_get_post_type_labels('イベント'),
		'description' => 'イベントなどを開催することもできます。',
		'public' => true,
		'supports' => array('title', 'editor', 'author', 'slug', 'thumbnail'),
		'has_archive' => true,
		'capability_type' => 'post',
		'rewrite' => array('slug' => 'event')
	));
	register_post_type('news', array(
		'label' => '有料ニュース',
		'labels' => _lwper_get_post_type_labels('有料ニュース'),
		'description' => '有料の定期購読を作ることもできます',
		'public' => true,
		'supports' => array('title', 'editor', 'author', 'slug', 'thumbnail'),
		'has_archive' => true,
		'capability_type' => 'post',
		'rewrite' => array('slug' => 'news')
	));
}
add_action('init', '_lwper_custom_post_types');

/**
 * カスタム投稿タイプ要のラベルを返す
 * @param string $name
 * @return string
 */
function _lwper_get_post_type_labels($name){
	$label_array = array(
		'name' => '%s',
		'add_new' => '新規追加',
		'add_new_item' => '新規%sを追加',
		'edit_item' => '%sを編集',
		'new_item' => '新規%s',
		'view_item' => '%sを表示',
		'search_items' => '%sを検索',
		'not_found' => '%sは見つかりませんでした',
		'not_found_in_trash' => 'ゴミ箱に%sは見つかりませんでした'
	);
	$labels = array();
	foreach($label_array as $key => $val){
		$labels[$key] = (false !== strpos($val, '%s'))
				? sprintf($val, $name)
				: $val;
	}
	return $labels;
}

/**
 * Google Mapを入れられるようにする
 * @param array $initArray
 * @return string
 */
function _lwper_tinymce($initArray) {
	$initArray[ 'extended_valid_elements' ] .= "iframe[id|class|title|style|align|frameborder|height|longdesc|marginheight|marginwidth|name|scrolling|src|width]";
	return $initArray;
}
add_filter('tiny_mce_before_init', '_lwper_tinymce', 10000);
