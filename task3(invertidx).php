<?php
/**
 * Created by PhpStorm.
 * User: Yura
 * Date: 20.03.2018
 * Time: 20:46
 */
require_once ('classes.php');
$file = file_get_contents('journal.xml');

$journal = new SimpleXMLElement($file);
$articles = $journal->issues[0]->Issue[0]->articles;

//var_dump($articles[0]->Article->link);

$index = new Index();
$i = 0;
foreach ($articles[0] as $article) {
    $index->titles[] = new SimpleTitle($i, $article->title->title . '') ;
    $pattern = '/[\n\r\.,\(\)?;:\-&$\\\\0-9=_]/';
    $article->abstract->abstract = implode(' ', explode(' ', $article->abstract->abstract));
    $article->abstract->stem = implode(' ', explode(' ', $article->abstract->stem));
    $article->abstract->porter = implode(' ', explode(' ', $article->abstract->porter));
    $index->words = array_merge($index->words, explode(' ', preg_replace($pattern, '', trim($article->abstract->abstract))));
    $index->stemWords = array_merge($index->stemWords, explode(' ', preg_replace($pattern, '', trim($article->abstract->stem))));
    $index->porterWords = array_merge($index->porterWords, explode(' ', preg_replace($pattern, '', trim($article->abstract->porter))));
    $i++;
}

function strToWord($words)
{
    $a = [];
    foreach ($words as $word)
    {
        if (mb_strlen($word) > 1) {
            if (strpos($word, '^') > 0){
                foreach (explode('^', $word) as $v) {
                    if ($v != '')
                        $a[] = new Word($v);
                }
            } else {
                $a[] = new Word($word);
            }
        }
    }
    return $a;
}
sort($index->words, SORT_LOCALE_STRING);
sort($index->stemWords, SORT_LOCALE_STRING);
sort($index->porterWords, SORT_LOCALE_STRING);
$index->words = strToWord(array_unique($index->words));
$index->stemWords = strToWord(array_unique($index->stemWords));
$index->porterWords = strToWord(array_unique($index->porterWords));

function findInDoc($arr, $type = 'abstract') {
    global $articles, $index;
    for($i = 0; $i < count($arr); $i++) {
        $j = 0;
        foreach ($articles[0] as $article) {
            if (strpos($article->abstract->$type, $arr[$i]->word) !== false) {
//                $arr[$i]->addDoc($j);
                $arr[$i]->docs[] = $j;
            }
            $j++;
        }
    }
    return $arr;
}

$index->words = findInDoc($index->words);
$index->stemWords = findInDoc($index->stemWords, 'stem');
$index->porterWords = findInDoc($index->porterWords, 'porter');

$xml = XMLSerializer::Serialize($index);
file_put_contents('index.xml', $xml);
