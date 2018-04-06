<?php
/**
 * Created by PhpStorm.
 * User: Yura
 * Date: 20.03.2018
 * Time: 20:44
 */
class Keyword {
    public $value = null;
    public function __construct(string $value)
    {
        $value = trim(urldecode($value));
        $this->value = $value;
    }
}
function mystem($string)
{
    exec('echo ' . $string . ' | c:\mystem\mystem.exe -ld -e utf-8', $result);
    return $result;
}
class StemAndPorter
{
    public function __construct(string $value)
    {
        $this->mystem($value);
        $this->porter($value);
    }

    public $stem = '';
    public $porter = '';
    public function mystem($q) {
        $result = mystem($q);
        foreach ($result as $k => $v) {

            $this->stem .=  preg_replace(['/}{/', '/(}|{)/'], [' ', ''],   $v);
        }
//        var_dump($q, $this->stem);
    }
    public function porter(string $value) {
        require_once ('LinguaStemRu.php');
        $s = new \Stem\LinguaStemRu();
        $s = explode(' ',$s->stem_text($value));
        foreach ($s as $v) {
            $this->porter  .= ' ' . $v;
        }
        $this->porter = trim($this->porter);
    }
}

class MyAbstract extends StemAndPorter
{
    public $abstract = null;
    public function __construct(string $value)
    {
        $this->abstract = $value;
        parent::__construct($value);
    }
}
class Title extends StemAndPorter
{
    public $title = null;
    public function __construct(string $value)
    {
        $this->title = $value;
        parent::__construct($value);
    }
}


function myToLower($string)
{
    $string = mb_strtolower($string);
    return $string;
//    return strtr( $string, 'ЙЦУКЕНГШЩЗХЪФЫВАПРОЛДЖЭЯЧСМИТЬБЮЁ', 'йцукенгшщзхъфывапролджэячсмитьбюё' );
}

class Article
{
    public $id;
    public $link;
    public $title;
    public $abstract;
    /**
     * @var Keyword[] $keywords
     */
    public $keywords = [];
    public function __construct(string $link)
    {
        $this->link = Journal::DOMAIN . ($link[0] == '/' ? $link : '/' . $link);
        $doc = new DOMDocument();
        $doc->loadHTMLFile($this->link);
        $xpath = new DOMXPath($doc);
        $this->title = trim($xpath->query("// body / div[contains(@class, 'mob')] / span / font")->item(0)->nodeValue);
        $this->title = new Title(myToLower($this->title));
        $e = $xpath->query("// b[contains(.,'Аннотация:')] / following-sibling::node()[following-sibling::br[count(// b[contains(.,'Аннотация:')] / following-sibling::br)]]");
        $abstract = "";
        foreach ($e as $el) {
            $abstract = $abstract . $el->nodeValue;
        }
        $abstract = preg_replace('/\t/', '', $abstract);
        $abstract = preg_replace('/\n/', '', $abstract);

        $this->abstract = new MyAbstract(myToLower($abstract));
        $keys = $xpath->query("// body / div[contains(@class, 'mob')] / b[contains(., 'Ключевые')]  / following-sibling::i")->item(0)->nodeValue;
        $keys = $keys != "" ? explode(',', $keys) : [];
        foreach ($keys as $k) {
            $this->keywords[] = new Keyword(myToLower($k));
        }

    }
}

class Issue
{
    public $issue;
    /**
     * @var Article[] $articles
     */
    public $articles = [];

    public function __construct(DOMXPath $xpath)
    {
        $this->issue = trim($xpath->query("// table[@width='100%']")->item(3)->nodeValue);
        $arts = $xpath->query("// td[@colspan='2'] / a[contains(@class, 'SLink')]");
        for($i = 0; $i < $arts->length; $i++) {
//        for($i = 0; $i < 1; $i++) {
            $a = $arts[$i]->getAttribute('href');
            $article = new Article($a);
            $article->id = $i;
            $this->articles[] = $article;
        }
    }
}

class Journal {
    const DOMAIN = 'http://m.mathnet.ru';
    public $name;
    public $url;
    /**
     * @var Issue[] $issues
     */
    public $issues = [];

    public function __construct(DOMXPath $xpath)
    {
        preg_match("/([^\/]*)\/\/(.*)([^\/]*)/", trim($xpath->query("// table[@class='Card']")->item(1)->childNodes->item(1)->nodeValue), $m);
        $this->name = $m[2];
        $this->issues[] = new Issue($xpath);
    }
}

class XMLSerializer {

    /**
     * Get object class name without namespace
     * @param object $object Object to get class name from
     * @return string Class name without namespace
     */
    private static function GetClassNameWithoutNamespace($object) {
        $class_name = get_class($object);
        return $class_name;
    }

    /**
     * Converts object to XML compatible with .NET XmlSerializer.Deserialize
     * @param type $object Object to serialize
     * @param type $root_node Root node name (if null, objects class name is used)
     * @return string XML string
     */
    public static function Serialize($object, $root_node = null) {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>' . PHP_EOL;
        if (!$root_node) {
            $root_node = self::GetClassNameWithoutNamespace($object);
        }
        $xml .= '<' . $root_node . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">' . PHP_EOL;
        $xml .= self::SerializeNode($object, 1);
        $xml .= '</' . $root_node . '>';
        return $xml;
    }

    /**
     * Create XML node from object property
     * @param mixed $node Object property
     * @param int $level
     * @param bool|string $parent_node_name Parent node name
     * @param bool $is_array_item Is this node an item of an array?
     * @return string XML node as string
     * @throws Exception
     */
    private static function SerializeNode($node, int $level = 0, $parent_node_name = false, $is_array_item = false) {
        $xml = '';
        $tab = '';
        for($i = 0; $i < $level; $i++) {
            $tab .= "    ";
        }
        if (is_object($node)) {
            $vars = get_object_vars($node);
        } else if (is_array($node)) {
            $vars = $node;
        } else {
            throw new Exception('hahahahaha');
        }

        foreach ($vars as $k => $v) {
            if (is_object($v)) {
                $node_name = ($parent_node_name ? $parent_node_name : self::GetClassNameWithoutNamespace($v));
                if (!$is_array_item) {
                    $node_name = $k;
                }
                $xml .= $tab . '<' . $node_name . '>'  . PHP_EOL;
                $xml .= self::SerializeNode($v, $level + 1) . PHP_EOL;
                $xml .= $tab . '</' . $node_name . '>' . PHP_EOL;
            } else if (is_array($v)) {
                $xml .= $tab . '<' . $k . ' count="' . count($v) . '">' . PHP_EOL;
                if (count($v) > 0) {
                    if (is_object(reset($v))) {
                        $xml .= self::SerializeNode($v, $level + 1, self::GetClassNameWithoutNamespace(reset($v)), true);
                    } else {
                        $xml .= self::SerializeNode($v, $level + 1, gettype(reset($v)), true);
                    }
                } else {
                    $xml .= self::SerializeNode($v, $level + 1, false, true);
                }
                $xml .= $tab . '</' . $k . '>' . PHP_EOL;
            } else {
                $node_name = ($parent_node_name ? $parent_node_name : $k);
                $node_name = 'integer' == $node_name ? 'id' : $node_name;
                if ($v === null) {
                    continue;
                } else {
                    $xml .= $tab . '<' . $node_name . '>';
                    if (is_bool($v)) {
                        $xml .= $v ? 'true' : 'false';
                    } else {
                        $xml .= htmlspecialchars($v, ENT_QUOTES);
                    }
                    $xml .= '</' . $node_name . '>' . PHP_EOL;
                }
            }
        }
        return $xml;
    }
}


class Index
{
    public $titles = [];
    public $words = [];
    public $stemWords = [];
    public $porterWords = [];
}

class SimpleTitle{
    public $id;
    public $title;
    public function __construct($id, $title)
    {
        $this->id = $id;
        $this->title = $title;
    }
};
class Word {
    public $word;
    public $docs;
    public $titles;
    public function __construct($word)
    {
        $this->word = $word;
        $this->docs = [];
        $this->titles = [];
    }
//    }
}

class Idf{
    public function __construct($a)
    {
        $this->values = $a;
    }
    public $values;
}
class WordTfIdfInfo {
    public function __construct($a)
    {
        $this->word = $a;
    }
    public $word;
    public $abstractTfIdf;
    public $titleTfIdf;
    public $fullTfIdf;
    public $scores;
}
class DocScore
{
    public $doc;
    public $score;
    public function __construct($d, $s)
    {
        $this->score = $s;
        $this->doc = $d;
    }
}