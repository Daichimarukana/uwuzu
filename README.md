
# [<img src="/img/uwuzucolorlogo.svg" width=140px>](https://docs.uwuzu.xyz)
**[ServerList](https://uwuzu-serverlist.emptybox.win)**
**[Document](https://docs.uwuzu.xyz)**
**[Community](https://discordapp.com/invite/mNdGApnBFk)**
  
  
uwuzuは、新しくて、楽しくて、そんなSNSです。  
基礎的なマイクロブログSNSとしての機能を兼ね備えています！  

## Introduction
どんなSNSなの？
uwuzuは簡潔に言えばシンプルなマイクロブログSNSです。  
基本的なSNSを楽しむための機能と**独自のお楽しみ機能**が備わっています！  
もちろん画像や動画の添付も可能、カスタム絵文字機能などもあります...  

### 見た目
![みため](https://docs.uwuzu.xyz/img/shot/top_on_pc.png)

### 機能比較
見た目だけ見せられても困るそんなあなたに！
| 一般名？           | uwuzu                        | Twitter              | Misskey                                  | Mastodon               | 
| ------------------ | ---------------------------- | -------------------- | ---------------------------------------- | ---------------------- | 
| 投稿               | ユーズ                       | ツイート(ポスト)     | ノート                                   | トゥート               | 
| 拡散               | リユーズ                     | リツイート(リポスト) | リノート                                 | ブースト               | 
| すき               | いいね                       | いいね               | リアクション                             | お気に入り             | 
| センシティブな投稿 | NSFW                         | センシティブな投稿   | NSFW                                     | NSFW/CW                   | 
| サービス           | サーバー                     | Twitter(X)           | サーバー・インスタンス                   | インスタンス           | 
| ブックマーク       | ブックマーク                 | ブックマーク         | お気に入り                               | ブックマーク           | 
| 文字数制限         | 管理者設定(最大16777216文字) | 280文字              | 3000文字                                 | 500文字                | 
| タイムライン       | おすすめ・ローカル・フォロー           | おすすめ・フォロー中 | ホーム・ローカル・ソーシャル・グローバル | ホーム・ローカル・連合 | 

## Get started
uwuzuを使いたいそんなあなたに！  
簡単にuwuzuを始めるならこっち！ → [ServerList](https://uwuzu-serverlist.emptybox.win)  
もしuwuzuサーバーを構築しようとしているのであれば...続けて読んでください...  

### 技術スタック
php - バックエンド全体を支えています。  
MySQL - データの保存を主な仕事として取り扱っています。  
jQuery - バックエンドとフロントエンドの架け橋です。  
どうです？このシンプルさ✨  
  
### 最低要件
Software
- Apache 2
- php 8
- MySQL 8.0 or MariaDB 10.4
  
Machine
- Memory 1GB
- Storage 500MB(ユーザーデータのある程度の保存含め)

### インストール
ここではMySQL, Apache2, php8.0が導入されている前提で進めます。

#### ダウンロード
Githubの[リリース](https://github.com/Daichimarukana/uwuzu/releases)より最新リリースをダウンロードしてください。

#### 展開
以下のような適当な場所(ApacheよりWebサーバーとして動作させる場所)に展開してください！  
`/user/home/web/`  
#### 権限設定
以下のようにコマンドラインを開き、uwuzuを展開したフォルダ内のすべてのファイルに権限を与えてください！  
```
sudo chmod -R 755 /user/home/web/.
```
#### phpの設定
以下のコマンドでphpの設定(php.ini)を開き、開いたら「↓」キーでひたすら下に移動して`;extension=なんとかかんとか`が何十行かあるところまで移動して、そしたらその中から以下のものの`;`を消してください。  
要するにプラグインの有効化です！  
```
sudo nano /etc/php/{phpバージョン}/cli/php.ini

extension=fileinfo
extension=gd
extension=pdo_mysql
extension=mysqli
extension=mbstring
extension=zip
```
変更か書き込めたら保存して閉じてください。  
#### MySQLの設定
まず、MySQLにログインします。  
```
sudo mysql -u root
```
このタイミングでrootアカウントにもパスワードを設定できると望ましいです！  
それでは次にuwuzuを操作するアカウントを作成します。  
`id`と`password`はuwuzuからMySQLを操作するアカウントに必要なので、これも覚えられるものを設定してください！
```
create user 'id'@'localhost' identified by 'password';
```
アカウントが作れたら、権限を与えてください！  
```
grant all on *.* to 'id'@'localhost';
flush privileges;
```
終わったら次にデータベースを作成します！  
データベース名は覚えられるものであれば何でも大丈夫です！  
```
CREATE DATABASE uwuzu_db;
```
これらの設定が完了したら
```
exit
```
でMySQLを閉じてください。  
続いては、MySQLの設定ファイルより、モードの設定を行います。  
```
sudo nano /etc/mysql/my.cnf
```
このコマンドを実行し、SQLモードから"STRICT_TRANS_TABLES"を削除してください  
```
[mysqld]
sql_mode = NO_ENGINE_SUBSTITUTION
```
変更できたら保存して閉じてください。

#### Apache2の設定
まず、Apache2の設定ファイルを開きます。
```
sudo nano /etc/apache2/apache2.conf
```
開けたら、以下のような項目があるので、uwuzuを展開したフォルダにパスを変更してください。
```
<Directory "/user/home/web/">
    Options Indexes FollowSymLinks
    AllowOverride ALL
    Require all granted
</Directory>
```
一度保存して閉じ、もう一つパスを設定しているファイルを開いて、以下の設定を書き換えてください！
```
sudo nano /etc/apache2/sites-available/000-default.conf
```
```
ServerAdmin webmaster@localhost
DocumentRoot /user/home/web/
```
最後に.htaccessを機能させるための設定です！  
```
sudo a2enmod rewrite
sudo a2enmod headers
```
これらのコマンドを実行してください！

#### 最後の再起動
すべての手順が完了したら、Apache2とMySQLを再起動します！  
```
sudo systemctl restart apache2
sudo systemctl restart mysql
```
#### 初期設定
ブラウザを立ち上げ、[localhost/admin](http://localhost/admin)を開いて早速初期設定を開始しましょう！  
初期設定後、各種サーバー設定は左側メニューの"サーバー設定"より行えます。
  
もしインストールでつまづいたら... → [Discordコミュニティ](https://discordapp.com/invite/mNdGApnBFk)か[Document](https://docs.uwuzu.xyz)を確認してください！
