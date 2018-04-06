<?php

require_once ('classes.php');

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
for ($i = 1; $i < 4; $i++) {
    $query = explode(' ', trim(file_get_contents('query' . $i)));
    $excluded_query = exclude($query);
    $query = array_diff($query, $excluded_query ? $excluded_query : []);

    $excluded_query = preg_replace(['/}{/', '/(}|{|\?)/'], [' ', ''], mystem(implode(' ', $excluded_query)));
    $query = preg_replace(['/}{/', '/(}|{|\?)/'], [' ', ''], mystem(implode(' ', $query)));
    $excluded_query = explode(' ', $excluded_query[0]);
    $query = explode(' ', $query[0]);


    $journal = new SimpleXMLElement(file_get_contents('journal.xml'));
    $articles = $journal->issues[0]->Issue[0]->articles[0];
    $docsInfo = [];
    foreach ($articles as $article) {
        $o = new stdClass();
        $o->abstract = $article->abstract->stem[0];
        $o->title = $article->title->stem[0];
        $o->abstract_len = str_word_count($article->abstract);
        $o->title_len = str_word_count($article->title);
        $o->sum_len = $o->abstract_len + $o->title_len;
        $docsInfo[] = $o;
    }

    $docsCount = count($docsInfo);


    $index = new SimpleXMLElement(file_get_contents('index.xml'));
    /* @var SimpleXMLElement $stemWords */
    $stemWords = $index->stemWords;
    $result = [];
    foreach ($stemWords->children() as $stemWord) {
        $word = (string)$stemWord->word;
        $w = new WordTfIdfInfo($word);
        foreach ($docsInfo as $id => $article) {
            $abstract_len = str_word_count($article->abstract);
            $title_len = str_word_count($article->title);
            $sum_len = $abstract_len + $title_len;
            $abstract_word_count = substr_count($article->abstract, $word);
            $title_word_count = substr_count($article->title, $word);
            $sum_count = $abstract_word_count + $title_word_count;
            $titleTfIdf = $title_word_count / $title_len;
            $abstractTfIdf = $abstract_word_count / $abstract_len;
            $fullTfIdf = $sum_count / $sum_len;
            $score = 0;
            if (strpos($article->title, $word) !== false) {
                $count = (int)$stemWord->titles->attributes()['count'];
                if ($count > 0) {
                    $titleTfIdf *= log($docsCount / $count);
                } else {
                    $titleTfIdf = 0;
                }
            }
            if (strpos($article->abstract, $word) !== false) {
                $count = (int)$stemWord->docs->attributes()['count'];
                if ($count > 0) {
                    $abstractTfIdf *= log($docsCount / $count);
                } else {
                    $abstractTfIdf = 0;
                }
            }
            if (strpos($article->title . ' ' . $article->abstract, $word) !== false) {
                $d = ((int)$stemWord->docs->attributes()['count']) > 0 ? (array)$stemWord->docs->id : [];
                $t = ((int)$stemWord->titles->attributes()['count']) > 0 ? (array)$stemWord->titles->id : [];
                $count = count(array_unique(array_merge($t, $d)));
                if ($count > 0) {
                    $fullTfIdf *= log($docsCount / $count);
                } else {
                    $fullTfIdf = 0;
                }
                $score = 0.4 * $abstractTfIdf + 0.6 * $titleTfIdf;
            }
            if ($fullTfIdf > 0) {
                $w->scores[] = new DocScore($id, $score);
                $w->fullTfIdf[] = new DocTfIdf($id, $fullTfIdf);
                $w->titleTfIdf[] = new DocTitleTfIdf($id, $titleTfIdf);
                $w->abstractTfIdf[] = new DocAbstractTfIdf($id, $abstractTfIdf);
                $result[] = $w;
            }
        }
    }

    file_put_contents('idf_by_query' . $i . '.xml', XMLSerializer::Serialize(new Idf($result)));
}
