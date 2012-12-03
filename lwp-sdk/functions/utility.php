<?php
/*
 * テーマに関する色々な情報を提供してくれる関数群です。
 * 
 */

/**
 * テーマのバージョン番号を返します
 * 
 * @param boolean $parent trueにした場合は親テーマのバージョン
 * @return string テーマのバージョン
 */
function lwp_theme_version($parent = false){
	/* @var $theme WP_Theme */
	$theme = wp_get_theme();
	if($parent){
		if(is_child_theme()){
			return $theme->parent()->display('Version', false);
		}else{
			return false;
		}
	}else{
		return $theme->display('Version', false);
	}
}

