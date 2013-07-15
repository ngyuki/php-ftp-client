[![Build Status](https://travis-ci.org/ngyuki/php-ftp-client.png)](https://travis-ci.org/ngyuki/php-ftp-client)

概要
-----

PHPのFTP拡張モジュールには下記の問題があり、拡張モジュールを修正するよりソケット関数で新たに実装した方が簡単そうだったので作りました。

PHPのFTP拡張モジュールの問題
-----------------------------

ある環境のPHP(5.3.2)のftp関数で、パッシブモードを指定しているにも関わらずFTPサーバのログを見ると PORT コマンドが発行されていることがありました。
PHPのftp拡張のソースを確認したところ、下記のようなコードで PORT コマンドが発行されることがわかりました。

```php
<?php
    `iptables -F`;
    `iptables -A INPUT -j REJECT -p tcp --sport 20`;

    $ftp = ftp_connect($addr, $port, 1);

    ftp_login($ftp, $id, $pw);

    ftp_pasv($ftp, true);
    /* (1) */ ftp_put($ftp, "0001.txt", __FILE__, FTP_BINARY);

    `iptables -A INPUT -j DROP -p tcp --sport 21`;

    /* (2) */ ftp_put($ftp, "0002.txt", __FILE__, FTP_BINARY);

    `iptables -R INPUT 2`;

    /* (3) */ ftp_put($ftp, "0003.txt", __FILE__, FTP_BINARY);
    /* (4) */ ftp_put($ftp, "0004.txt", __FILE__, FTP_BINARY);

    ftp_close($ftp);
```

元々アクティブモードでは通信できないサーバに対して・・・

-  (1)でファイルを正常にアップロード
-  (2)の`ftp_put`の応答が何らかの原因で DROP された
-  (3)と(4)でファイルのアップロードを試みた
    
という動作をイメージしています。このとき (2)～(4) でそれぞれ次の通り PHP Warning が発生しています。

- (2) Warning: ftp_put(): Transfer complete in /tmp/ftp.php on line 22
- (3) Warning: ftp_put(): Entering Passive Mode (192,168,1,100,225,80). in /tmp/ftp.php on line 26
- (4) Warning: ftp_put(): PORT command successful in /tmp/ftp.php on line 27

FTPサーバ側では次の通りログが記録されています（コマンド 応答コード バイト数 の順）。

    "USER hoge" 331 -
    "PASS (hidden)" 230 -
    "PASV" 227 -
    "TYPE I" 200 -
    "STOR 0001.txt" 226 1760
    "PASV" 227 -
    "PORT 192,168,1,101,136,135" 200 -
    "PORT 192,168,1,101,199,141" 200 -
    "STOR 0004.txt" 425 0
    "QUIT" 221 -

ftp拡張のソースを見た感じ、(2)の ftp_put の中で発行されている PASV コマンドの応答が DROP されると、
その次の(3)からはアクティブモードになるようでした。


さらに、PHP Warning の内容が次のように１個ずれたような感じになっています。

- (2) のエラーメッセージは (1) の転送完了のメッセージ
- (3) のエラーメッセージは (2) の PASV の応答メッセージ
- (4) のエラーメッセージは (3) の PORT の応答メッセージ

この原因を調べたところ、単純に次のようなフローでFTPコマンドのリクエストとレスポンスの対応がずれてしまうことが判りました。

- クライアントからサーバへコマンド A をリクエスト
- サーバからクライアントへの A のレスポンスが何らかの原因で遅延
- クライアントは A をタイムアウトと判断して次のコマンド B をリクエスト(FTP関数では単に`false`が返るだけ)
- A のレスポンスがクライアントに到達する
- クライアントは A のレスポンスを B のレスポンスだと解釈する

単にアクティブ/パッシブだけの問題であれば`ftp_put`の前に必ず`ftp_pasv`を呼べば解決できそうですが、
エラーメッセージがずれる問題は、コマンドのリクエストとレスポンスがずれてしまっているので、
FTPサーバとの接続を一旦切って再接続しなければ復帰することが出来ません。

が、FTP関数はリターンコードによるコマンドの失敗も、タイムアウトなどの問題と同じように`false`を返すだけになっているため、区別することが出来ません。
そのため、PHPのエラーメッセージから失敗の原因を判断するか、ftp関数で`false`が返された場合はもれなく再接続を行うようにする必要があります。
前者はちょっとどうかと思うので後者でどうにかしようと考えましたが、`ftp_cwd`や`ftp_mkdir`の失敗まで再接続しなければならなくなり、ちょっと使い勝手が良くありません。

そこで、FTP層のエラーとそれ以下のエラーを区別できるようにこれを作成しました。

