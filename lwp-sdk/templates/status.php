<?php /* @var $this LWPer_Theme_SDK */ ?>
<div id="icon-themes" class="icon32"><br /></div>
<h2><?php $this->e('テーマのステータス'); ?></h2>

<table class="form-table">
	<tr>
		<th><?php $this->e('テーマ名'); ?></th>
		<td>
			<?php echo wp_get_theme()->display('Name'); ?>&nbsp;
			<small><?php printf('<a href="%s">配布ページ</a>', LWPER_PROVIDER_URL.'?p='.LWPER_THEME_ID); ?></small>
			<p class="description">
				<?php echo wp_get_theme()->display('Description'); ?>
			</p>
		</td>
	</tr>
	<tr>
		<th><?php $this->e('テーマ配布元'); ?></th>
		<td>
			<?php echo esc_html(LWPER_PROVIDE_NAME); ?>&nbsp;
			<small><?php printf('<a target="_blank" href="%1$s">%1$s</a>', LWPER_PROVIDER_URL); ?></small>
		</td>
	</tr>
	<tr>
		<th><?php $this->e('アカウント接続状況'); ?></th>
		<td>
			<?php if($this->get_provider_token()): ?>
			<p class="valid"><?php $this->e('接続されています'); ?></p>
			<?php else: ?>
			<p class="description">
				<?php printf($this->_('このサイトは%sと接続されていません。自動アップデートを行うには接続してください'), LWPER_PROVIDE_NAME); ?>
			</p>
			<form method="post" action="<?php echo admin_url('themes.php?page=lwper-status'); ?>">
				<?php wp_nonce_field('lwper_get_theme_token'); ?>
				<label>
					<input type="text" name="lwp_user_login" value="" class="regular-text" />
					&nbsp;<?php printf($this->_('%sのユーザー名'), LWPER_PROVIDE_NAME); ?>
				</label><br />
				<label>
					<input type="password" name="lwp_user_pass" value="" class="regular-text" />
					&nbsp;<?php printf($this->_('%sのパスワード'), LWPER_PROVIDE_NAME); ?>
				</label><br />
				<input type="submit" value="<?php _e('Submit'); ?>" class="button-primary" />
			</form>
			<?php endif; ?>
		</td>
	</tr>
	<tr>
		<th><?php $this->e('テーマのバージョン'); ?></th>
		<td>
			<?php echo lwp_theme_version(); ?>
			<?php if($this->needs_update()): ?>
			<p class="invalid">
				<?php printf($this->_('最新バージョン %s がリリースされています。アップデートしてください。'), $this->get_repository_head_version()); ?>
				<?php if(!$this->get_provider_token()): ?>
					<br /><strong><?php printf($this->_('アップデートするには%sと接続してください。'), LWPER_PROVIDE_NAME);?></strong>
				<?php endif; ?>
			</p>
			<?php else: ?>
			<p class="valid"><?php $this->e('このテーマは最新です。'); ?></p>
			<?php endif; ?>
		</td>
	</tr>
</table>

<?php if($this->needs_update() && $this->get_provider_token()):  ?>
	<div id="lwp-theme-updater">
	<?php
		$action = admin_url('admin-ajax.php');
		if($this->can_upload_directly()): ?>
		<form method="post" action="<?php echo esc_attr($action); ?>">
			<input type="hidden" name="connection_type" value="direct" />
			<p class="submit">
				<input type="submit" class="button" value="<?php $this->e('アップデート開始') ?>" />
			</p>
		</form>
	<?php else:
			request_filesystem_credentials($action, '', false, false, '');
	endif; ?>
	</div>
	<div id="updater-status">
		<h3><?php $this->e('アップデートの状況'); ?></h3>
		<div class="ui-progressbar ui-widget ui-widget-content ui-corner-all">
			<div class="ui-progressbar-value ui-widget-header ui-corner-left" style="width:0%;"></div>
		</div>
		<p class="description">
			<?php printf($this->_('<strong>%d</strong>ステップの処理があります。完了するまでブラウザを移動しないでください。'), 4); ?>
		</p>
		<ol></ol>
	</div>
<?php endif; ?>