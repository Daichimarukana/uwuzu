# 注意:結構脆弱です。導入後はローカルな環境で使うことをおすすめします。

# uwuzu
あたらしいSNSです！！！
右側のReleaseからDLして導入していただけます！
AGPLライセンスです！！！

導入方法は以下のとおりです！

## 5. サーバーの建て方
※MySQLの設定結構めんどいです。脆弱です。
まず、Apache2とPHP 8とmysql Ver 15が導入されているサーバーを準備します！
次にSQLを設定します。(InnoDB)
まず、お好きな名前でDBを作成し、その中に、account,emoji,notice,role,ueuse,notificationとテーブルを作成します。
テーブルの中身は以下のとおりです。
照合順序は全て標準でutf8mb4_general_ciです。
### account
- sysid(INT)(AUTO_INCREMENT	) アカウントが追加されるとカウントされるシステム用ID
- username(varchar(500)) ユーザーネーム保存用
- userid(varchar(500)) ユーザーID保存用
- password(varchar(1024)) パスワード保存用(ハッシュ化されます)
- loginid(varchar(256)) 自動ログイン時に本人アカウントか確認
- mailadds(varchar(500)) メールアドレス保存用
- profile(TEXT) プロフィールテキスト保存用
- iconname(varchar(256)) アイコン画像名保存用
- iconcontent(mediumblob) アイコン画像保存用
- icontype(varchar(256)) アイコン画像拡張子保存用
- iconsize(INT) アイコン画像サイズ保存用
- headname(varchar(256)) ヘッダー画像名保存用
- headcontent(mediumblob) ヘッダー画像保存用
- headtype(varchar(256)) ヘッダー画像拡張子保存用
- headsize(INT) ヘッダー画像サイズ保存用
- role(varchar(1024)) 「user」のようなロール保存用
- datetime(datetime) アカウント作成日時保存用
- follow(text) アカウントがフォローしている人保存用
- follower(text) アカウントがフォローされている人保存用
- admin(varchar(25)) 管理者アカウントなら「yes」、それ以外なら「none」と入力。
- authcode(varchar(256)) 二段階認証用キー保存用

### emoji
- sysid(INT)(AUTO_INCREMENT) アカウントが追加されるとカウントされるシステム用ID
- emojifile(varchar(512)) 絵文字ファイル名保存用
- emojitype(varchar(256)) 絵文字拡張子保存用
- emojicontent(mediumblob) 絵文字画像保存用
- emojiname(varchar(512)) 「:emoji:」のような絵文字名保存用
- emojiinfo(text) 絵文字についての説明保存用
- emojidate(datetime) 絵文字登録日時保存用

### notice
- sysid(INT)(AUTO_INCREMENT) うんえいからのおしらせが追加されるとカウントされるシステム用ID
- title(varchar(1024)) お知らせのタイトル保存用
- note(text) お知らせの内容保存用
- account(varchar(500)) 編集者ID保存用
- emojidate(datetime) お知らせ登録日時保存用

### role
- sysid(INT)(AUTO_INCREMENT) ロールが追加されるとカウントされるシステム用ID
- rolename(varchar(512)) ロール表示名保存用
- roleauth(varchar(256)) ロールの権限保存用
- rolecolor(varchar(25)) ロールの色保存用
- roleidname(varchar(512)) 「user」のようなロール指定用

### ueuse
- sysid(INT)(AUTO_INCREMENT) 投稿されるとカウントされるシステム用ID
- account(varchar(256)) 投稿者ID保存用
- uniqid(varchar(256)) 投稿ID保存用
- rpuniqid(varchar(256)) リプライ先ID保存用
- ueuse(text) 投稿内容保存用
- photo1(varchar(512)) 投稿に添付されたファイルの保存ディレクトリ保存用
- photo2(varchar(512)) 投稿に添付されたファイルの保存ディレクトリ保存用
- video1(varchar(512)) 投稿に添付されたファイルの保存ディレクトリ保存用
- datetime(datetime) 投稿日時保存用
- favorite(text) いいね保存用
- abi(text) 投稿者の追記保存用
- abidate(datetime) 追記日時保存用

### notification
- sysid(INT)(AUTO_INCREMENT) 通知されるとカウントされるシステム用ID
- touserid(varchar(512)) 通知先ID保存用
- title(varchar(1024)) 通知のタイトル
- msg(text) 通知の内容
- datetime(datetime) 通知日時
- userchk(varchar(25)) 通知の既読確認

### invitation
- sysid(INT)(AUTO_INCREMENT) 追加されるとカウントされるシステム用ID
- code(varchar(512)) 招待コード
- used(varchar(25)) 使用済みかそうでないか
- datetime(datetime) 招待コード仕様日時更新用

すべて作成完了したらGithubよりuwuzuのファイルをDLし、解凍し、それをサーバーの動作ディレクトリに置き、Apacheのhttpd.confからその動作ディレクトリを指定し、あとはApacheとphpとMy SQLを起動するだけ！
起動したらまずDBのroleにphpmyadminから「user」ロールを追加権限は「user」でOK。ロール名はとりあえず「一般ユーザー」ロールの色はHEXコード(#を除く)で000000のように指定。
そしたら普通にuwuzuにアクセスして自分のアカウントを登録。
それが終わったら一度サーバーを止め、uwuzuの動作ディレクトリ内のserverフォルダ内のファイルを各自設定
ファイルの機能は以下の通り！
- admininfo.txt : 管理者名(てすとまる/@sampledayo)
- contact.txt : 管理者への連絡用メアド(sample@test.com)
- info.txt : サーバー登録時に表示されるメッセージ(好きな内容)
- privacypolicy.txt : プライバシーポリシー(サーバーのプライバシーポリシーを記載)
- servername.txt : サーバー名(てすとさば)
- terms.txt : 利用規約(サーバーの利用規約を記載)
- uwuzuabout.txt : このファイル(uwuzuを改造した場合は書き換え)
- uwuzuinfo.txt : uwuzuのバージョン等記載(uwuzuを改造した場合は書き換え)
- uwuzurelease.txt : uwuzuのバージョン等記載(uwuzuを改造した場合は書き換え)
- onlyuser.txt : 招待コード機能をオンにするかどうか、「true」でオン、「false」でオフ。招待コードはDBに直接追加。


すべて作成完了したらGithubよりuwuzuのファイルをDLし、解凍し、それをサーバーの動作ディレクトリに置き、Apacheのhttpd.confからその動作ディレクトリを指定し、あとはApacheとphpとMy SQLを起動するだけ！
起動したらまずDBのroleにphpmyadminから「user」ロールを追加権限は「user」でOK。ロール名はとりあえず「一般ユーザー」ロールの色はHEXコード(#を除く)で000000のように指定。
そしたら普通にuwuzuにアクセスして自分のアカウントを登録。
それが終わったら一度サーバーを止め、uwuzuの動作ディレクトリ内のserverフォルダ内のファイルを各自設定
ファイルの機能は以下の通り！
- admininfo.txt : 管理者名(てすとまる/@sampledayo)
- contact.txt : 管理者への連絡用メアド(sample @test.com)
- info.txt : サーバー登録時に表示されるメッセージ(好きな内容)
- privacypolicy.txt : プライバシーポリシー(サーバーのプライバシーポリシーを記載)
- servername.txt : サーバー名(てすとさば)
- terms.txt : 利用規約(サーバーの利用規約を記載)
- uwuzuabout.txt : このファイル(uwuzuを改造した場合は書き換え)
- uwuzuinfo.txt : uwuzuのバージョン等記載(uwuzuを改造した場合は書き換え)
- uwuzurelease.txt : uwuzuのバージョン等記載(uwuzuを改造した場合は書き換え)

### これでサーバーは完成！！！
脆弱だから自己責任で楽しんでね～()
