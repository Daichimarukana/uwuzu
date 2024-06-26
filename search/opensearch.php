<?php
$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

header("Content-Type: application/xml");
header("charset=UTF-8");
header("Access-Control-Allow-Origin: *");

$domain = $_SERVER['HTTP_HOST'];

if(!(empty($serversettings["serverinfo"]["server_icon"]))){
    $servericon = htmlentities($this->value["servericon"], ENT_QUOTES, 'UTF-8', false);
}else{
    $servericon = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://').$domain."/img/uwuzuicon.png";
}

?>
<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/"
                       xmlns:moz="http://www.mozilla.org/2006/browser/search/">
  <ShortName><?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8', false);?></ShortName>
  <Description><?php echo htmlspecialchars($serversettings["serverinfo"]["server_name"], ENT_QUOTES, 'UTF-8', false);?>で検索</Description>
  <InputEncoding>UTF-8</InputEncoding>
  <Image width="16" height="16"><?php echo htmlentities($servericon, ENT_QUOTES, 'UTF-8', false);?></Image>
  <Url type="text/html" template="<?php echo htmlentities((empty($_SERVER['HTTPS']) ? 'http://' : 'https://').$domain."/search/?q={searchTerms}");?>"/>
</OpenSearchDescription>