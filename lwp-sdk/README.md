lwp-sdk
=======

lwp-sdkはWordPressプラグイン Literally WordPress に対応したWordPressテーマを作成するためのSDKです。

##使い方##

lwp-sdkを使うためには、次のステップが必要です。

1. lwp-sdkディレクトリをテーマフォルダのルートに含める
2. テーマの`functions.php`から（別のファイルからでもいいのですが）、`lwp-sdk/bootstrap.php`を読み込む
3. もし必要なら、`lwp-sdk/lwp-config.default.php`をテーマフォルダのルートにコピーして`lwp-config.php`にリネーム、設定を変更します

以上の操作であなたのテーマは Literally WordPress に対応したものになります。