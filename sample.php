<?php
$key_pair = openssl_pkey_new(array(
    'private_key_bits' => 2048,  // 秘密鍵のビット数
    'private_key_type' => OPENSSL_KEYTYPE_RSA // RSA秘密鍵を生成
));

// 秘密鍵を取得する
openssl_pkey_export($key_pair, $private_key);

echo $private_key;
?>