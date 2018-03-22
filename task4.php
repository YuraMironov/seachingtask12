<?php
/**
 * Created by PhpStorm.
 * User: Yura
 * Date: 21.03.2018
 * Time: 21:23
 */
require_once ('classes.php');

$query = explode(' ', trim(file_get_contents('query')));
function exclude(&$arr) {
    $a = [];
    foreach ($arr as $w) {
        if (strpos($w, '-') === 0) {
            $arr = array_diff($arr, [$w]);
            $a[] = substr($w ,1);
        }
    }
    return $a;
};
$excluded_query = exclude($query);
$query = array_diff($query, $excluded_query ? $excluded_query : []);

$excluded_query = preg_replace(['/}{/', '/(}|{|\?)/'], [' ', ''], mystem(implode(' ', $excluded_query)));
$query = preg_replace(['/}{/', '/(}|{|\?)/'], [' ', ''], mystem(implode(' ', $query)));
$excluded_query = explode(' ', $excluded_query[0]);
$query = explode(' ', $query[0]);

$docs = $minusDocs = [];
$index = new SimpleXMLElement(file_get_contents('index.xml'));
/* @var SimpleXMLElement $stemWords */
$stemWords = $index->stemWords;
foreach ($stemWords->children() as $stemWord) {
//    var_dump($stemWord->word);
//    var_dump($stemWord->docs);
//    var_dump($stemWord->docs->attributes()['count']);
//    var_dump($stemWord->docs->id);
//    var_dump($stemWord->titles->attributes()['count']);
    if (array_search($stemWord->word, $query) !== false) {
        $docs['' . $stemWord->word] =  $stemWord->docs->id;
    }
    if (array_search($stemWord->word, $excluded_query) !== false) {
        $minusDocs['' . $stemWord->word] =  $stemWord->docs->id;
    }
}

$res = [];
$minusRes = [];
foreach ($docs as &$v) {
    /* @var SimpleXMLElement $v */
    $v = (array) $v;
    $res = !$res ? $v : array_intersect($res, $v);
}
foreach ($minusDocs as &$v) {
    /* @var SimpleXMLElement $v */
    $v = (array) $v;
    $minusRes = !$minusRes ? $v : array_intersect($minusRes, $v);
}


$res = array_diff($res, array_unique($minusRes));


file_put_contents('query_search_result_docs_id', implode(' ', $res));

