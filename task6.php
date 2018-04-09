<?php
require_once ('vendor/autoload.php');
require_once ('classes.php');
require_once ('svd.php');
require_once ('invertMatrix.php');

$journal = new SimpleXMLElement(file_get_contents('journal.xml'));
$articles = $journal->issues[0]->Issue[0]->articles[0];
$docsInfo = [];
foreach ($articles as $article) {
    $o = new stdClass();
    $o->abstract = $article->abstract->stem[0];
    $o->title = $article->title->stem[0];
    $docsInfo[] = $o;
}
function printMatrix(array $a, array $terms = []) {
    for ($k = 0; $k < count($a); $k++) {
        if ($terms) {
            echo $terms[$k] . ' ';
        }
        for ($j = 0; $j < count($a[$k]); $j++) {
            echo $a[$k][$j], ' ';
        }
        echo PHP_EOL;
    }
}
function matrixInitValues(array $docsInfo)  {
    $values = [];
    foreach ($docsInfo as $id => $info) {
        $values[$id] = 0;
    }
    return $values;
}
function queryInitValues(array $terms) {
    $values = [];
    for ($i =0; $i < count($terms); $i++) {
        $values[] = [0];
    }
    return $values;
}
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
$matrixA = [];
$filename = 'words_tf_idf.xml';
$input = new SimpleXMLElement(file_get_contents($filename));
$terms = [];
foreach ($input->values->children() as $word) {
    $values = matrixInitValues($docsInfo);
    foreach ($word->fullTfIdf->children() as $doc) {
        $values[(int)$doc->doc] = (float)$doc->val;
    }
    $word = (string) $word->word;
    $matrixA[] = $values;
    $terms[] = $word;
}

//printMatrix($matrixA, $terms);

for ($i = 1; $i < 4; $i++) {
    $query = explode(' ', trim(file_get_contents('query' . $i)));
    $excluded_query = exclude($query);
    $query = array_diff($query, $excluded_query ? $excluded_query : []);

    $excluded_query = preg_replace(['/}{/', '/(}|{|\?)/'], [' ', ''], mystem(implode(' ', $excluded_query)));
    $query = preg_replace(['/}{/', '/(}|{|\?)/'], [' ', ''], mystem(implode(' ', $query)));
    $excluded_query = explode(' ', $excluded_query[0]);
    $query = explode(' ', $query[0]);

    foreach ($excluded_query as $eq) {
        $index = array_search($eq, $terms);
        if ($index !== false) {
            $matrixA[$index] = matrixInitValues($docsInfo);
        }
    }
    $q = queryInitValues($terms);
    foreach ($query as $qw) {
        $index = array_search($qw, $terms);
        if ($index !== false) {
            $q[$index] = [1];
        }
    }

    $matrixClass = new Matrix();
    $USV = $matrixClass->SVD($matrixA);
    $matrixU = $USV['U'];
    $matrixS = $USV['S'];
    $matrixV_t = $USV['V'];
    $k = 2;
    $matrixU_k = $matrixClass->matrixConstruct($matrixU, count($matrixU), $k);
    $matrixS_k = $matrixClass->matrixConstruct($matrixS, $k, $k);
    $matrixV_t_k = $matrixClass->matrixConstruct($matrixV_t, $k, count($matrixV_t[0]));

    $UkSk = $matrixClass->matrixMultiplication($matrixClass->matrixTranspose($q), $matrixU_k);
    $q = $matrixClass->matrixMultiplication($UkSk, invert($matrixS_k));
    $d = $matrixClass->matrixTranspose($matrixV_t_k);

//    printMatrix($q); exit();
    $sims = [];
    foreach ($d as $index => $selfVal) {
        $sim = 0;
        for ($c = 0; $c < $k; $c++) {
            $sim += ((float) $q[$c] * (float) $selfVal[$c]);
        }
        $sqr = 1;
        foreach ($q as $v) {
            $sqr += (float) $v * (float) $v;
        }
        $sim /= sqrt($sqr);
        $sqr = 1;
        foreach ($selfVal as $v) {

            $sqr += $v * $v;
        }
        $sim /= sqrt($sqr);
        $sims[$index] = $sim;
    }
    file_put_contents('sim_q_d_by_query' . $i . '.txt', trim(file_get_contents('query' . $i)) . PHP_EOL . '[ ' .
                implode(' ', $sims) . ' ]');
}




