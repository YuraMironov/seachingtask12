<?php
require_once ('classes.php');
$url = 'http://m.mathnet.ru/php/archive.phtml?jrnid=uzku&wshow=issue&bshow=contents&series=0&year=2014&volume=156&issue=1&option_lang=rus&bookID=1517';
libxml_use_internal_errors(true);
$doc = new DOMDocument();
$doc->loadHTMLFile($url);
$xpath = new DOMXPath($doc);

$journal = new Journal($xpath);
$journal->url = $url;

$xml = XMLSerializer::Serialize($journal);
file_put_contents('journal.xml', $xml);