<?php
function getBrowserLanguage() {
    $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    $languages = explode(',', $acceptLanguage);
    return $languages[0]; // 最も優先度の高い言語を取得
}

$browserLanguage = getBrowserLanguage();
echo "ブラウザの言語設定: " . $browserLanguage;

function getCountryFromLanguage($language) {
    // 言語設定の例: en-US, fr-FR, ja-JP
    $parts = explode('-', $language);
    if (count($parts) > 1) {
        return strtoupper($parts[1]); // 国コード (例: US, FR, JP)
    }
    return null;
}

$browserLanguage = getBrowserLanguage();
$countryCode = getCountryFromLanguage($browserLanguage);
if ($countryCode) {
    echo "推測される国: " . $countryCode;
} else {
    echo "国を推測できませんでした";
}

?>