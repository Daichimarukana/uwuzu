# 注意:結構脆弱です。導入後はローカルな環境で使うことをおすすめします。

# uwuzu
あたらしいSNSです！！！
右側のReleaseからDLして導入していただけます！
AGPLライセンスです！！！

導入方法は以下のとおりです！


## 5. サーバーの建て方
まず、Apache2とPHP 8とmysql Ver 15が導入されているサーバーを準備します！
PHP 8では事前にGDを有効化しておいてください！(QRコードの生成に必要です。)
次にSQLを設定します。(InnoDB)
まず、お好きな名前でDBを作成し、その中に、account,emoji,notice,role,ueuse,notification,ads,reportとテーブルを作成します。
テーブルの中身は以下のとおりです。
照合順序は全て標準でutf8mb4_general_ciです。

## 簡単に構築できるようSQLの構造ファイルがリリースに添付されています！そちらをインポートしていただけますと大幅に簡単に導入できます！
(userロールとofficialロールとiceロールの設定は別途必要です。お手数ですがそこの設定だけよろしくお願いいたします。)

### account
- sysid(INT)(AUTO_INCREMENT	) アカウントが追加されるとカウントされるシステム用ID
- username(varchar(500)) ユーザーネーム保存用
- userid(varchar(500)) ユーザーID保存用
- password(varchar(1024)) パスワード保存用(ハッシュ化されます)
- loginid(varchar(256)) 自動ログイン時に本人アカウントか確認
- mailadds(varchar(500)) メールアドレス保存用
- profile(TEXT) プロフィールテキスト保存用
- iconname(varchar(256)) アイコン画像リンク保存用
- headname(varchar(256)) ヘッダー画像リンク保存用
- role(varchar(1024)) 「user」のようなロール保存用
- datetime(datetime) アカウント作成日時保存用
- follow(text) アカウントがフォローしている人保存用
- follower(text) アカウントがフォローされている人保存用
- admin(varchar(25)) 管理者アカウントなら「yes」、それ以外なら「none」と入力。
- authcode(varchar(256)) 二段階認証用キー保存用
- backupcode(varchar(256)) 二段階認証のバックアップコード保存用

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

### report
- sysid(INT)(AUTO_INCREMENT) 追加されるとカウントされるシステム用ID
- uniqid(varchar(256)) 通報ID保存用
- userid(varchar(500)) 通報先ユーザーID保存用
- report_userid(varchar(500)) 通報元ユーザーID保存用
- msg(text) サービス管理者宛メッセージ保存用
- datetime(datetime) 通報日時保存用
- admin_chk(varchar(25)) 解決済みかどうか確認用

### ads
- sysid(INT)(AUTO_INCREMENT) 追加されるとカウントされるシステム用ID
- uniqid(varchar(512)) 広告ID保存用
- url(varchar(512)) 広告のクリック先URL保存用
- image_url(varchar(512)) 広告に表示する画像URL保存用
- memo(text) 広告にマウスオーバーしたときに表示されるメッセージ保存用
- start_date(datetime) 広告配信開始日時保存用
- limit_date(datetime) 広告配信終了日時保存用
- datetime(datetime) 広告追加日時保存用

すべて作成完了したらGithubよりuwuzuのファイルをDLし、解凍し、それをサーバーの動作ディレクトリに置き、Apacheのhttpd.confからその動作ディレクトリを指定し、動作ディレクトリ内のdb.phpにDBのログイン情報を書き込んであとはApacheとphpとMy SQLを起動するだけ！
起動したらまずDBのroleにphpmyadminから「user」ロールと「official」ロールと「ice」ロールを追加、権限は「user」と「official」と「ice」でOK。ロール名はとりあえず「一般ユーザー」とか適当でOK、ロールの色はHEXコード(#を除く)で000000のように指定。(この3つのロールがないとエラーが発生します。)
そしたら普通にuwuzuにアクセスして自分のアカウントを登録。
## 管理者アカウント登録機能が追加されました。【[domain]/admin/】より設定できるのでそちらをご利用ください。
なお、管理者アカウントを導入後に登録した場合サーバーを止めてuwuzu動作ディレクトリ内のserverフォルダ内のファイルを設定する必要はございません。

### これでサーバーは完成！！！
脆弱だから自己責任で楽しんでね～()

### uwuzuを改変する人へ
基本的にReleaseより最新のコードをDLして改変してください。
Githubのmain内のコードは信頼しないほうがいいです。なぜなら私だいちまるがGithubの使い方をいまいち理解していないからです()
ご迷惑をおかけしますがどうぞよろしくお願いいたします。
