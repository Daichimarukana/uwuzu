<?php
require('../db.php');
require("../function/function.php");
blockedIP($_SERVER['REMOTE_ADDR']);

$serversettings_file = "../server/serversettings.ini";
$serversettings = parse_ini_file($serversettings_file, true);

header("Content-Type: application/xml; charset=UTF-8");
header("Access-Control-Allow-Origin: *");

$domain = $_SERVER['HTTP_HOST'];

$xml_text = <<<XML
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
    <loc>https://$domain/</loc>
    <priority>1.0</priority>
    </url>
    <url>
    <loc>https://$domain/new.php</loc>
    <priority>0.8</priority>
    </url>
    <url>
    <loc>https://$domain/login.php</loc>
    <priority>0.8</priority>
    </url>
</urlset>
XML;

$test_xml = new SimpleXMLElement($xml_text);

echo $test_xml->asXML();
?>