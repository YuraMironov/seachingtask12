<?php

require_once ('classes.php');
require_once ('svd.php');

$journal = new SimpleXMLElement(file_get_contents('journal.xml'));
$articles = $journal->issues[0]->Issue[0]->articles[0];
$docsInfo = [];
foreach ($articles as $article) {
    $o = new stdClass();
    $o->abstract = $article->abstract->stem[0];
    $o->title = $article->title->stem[0];
    $docsInfo[] = $o;
}
function printMatrix(array $a) {
    for ($k = 0; $k < count($a); $k++) {
        for ($j = 0; $j < count($a[$k]); $j++) {
            echo $a[$k][$j], ' ';
        }
        echo PHP_EOL;
    }
}
$matrixA = [];
for ($i = 1; $i < 4; $i++) {
    $filename = 'idf_by_query' . $i . '.xml';
    $input = new SimpleXMLElement(file_get_contents($filename));
    $values = [];
    foreach ($docsInfo as $id => $info) {
        $values[$id] = 0;
    }
    $terms = [];
    foreach ($input->values->children() as $word) {
        foreach ($word->fullTfIdf->children() as $doc) {
            $values[(int)$doc->doc] = (float)$doc->val;
        }
        $word = (string) $word->word;
        $matrixA[] = $values;
        $terms[] = $word;
    }









    printMatrix($matrixA);
    $matrixA = [];
}




