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
			switch($_REQUEST['lwp-method']){
				case 'gmo-cc':
					$message = <<<EOS
有効なカードとして認識されるのは次の情報です。
カード番号：<strong>4111111111111111</strong>
有効期限：<strong>2013年1月</strong>
セキュリティコード：<strong>3~4桁の任意の番号</strong>
EOS;
					break;
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