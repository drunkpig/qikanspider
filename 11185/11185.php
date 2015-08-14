<?php
require_once "../lib/simple_html_dom.php";
require_once "../lib/HttpClient.class.php";
require_once "../lib/functions.php";


$portal = array(
    //政法时事
    "http://bk.11185.cn/index/catalogSearch.do?method=findCatalogByType&tv=01&dg=2&pid=901",
    "http://bk.11185.cn/index/catalogSearch.do?method=findCatalogByType&tv=02&dg=2&pid=901",
    //生活休闲
    "http://bk.11185.cn/index/catalogSearch.do?method=findCatalogByType&tv=03&dg=2&pid=902",
    "http://bk.11185.cn/index/catalogSearch.do?method=findCatalogByType&tv=04&dg=2&pid=902",
    //商业财经
    "http://bk.11185.cn/index/catalogSearch.do?method=findCatalogByType&tv=05&dg=2&pid=903",
    "http://bk.11185.cn/index/catalogSearch.do?method=findCatalogByType&tv=06&dg=2&pid=903",
    //通信科技
    "http://bk.11185.cn/index/catalogSearch.do?method=findCatalogByType&tv=07&dg=2&pid=904",
    "http://bk.11185.cn/index/catalogSearch.do?method=findCatalogByType&tv=08&dg=2&pid=904",
    //艺术传媒
    "http://bk.11185.cn/index/catalogSearch.do?method=findCatalogByType&tv=09&dg=2&pid=905",
    "http://bk.11185.cn/index/catalogSearch.do?method=findCatalogByType&tv=10&dg=2&pid=905",
    //人文科学
    "http://bk.11185.cn/index/catalogSearch.do?method=findCatalogByType&tv=11&dg=2&pid=906",
    "http://bk.11185.cn/index/catalogSearch.do?method=findCatalogByType&tv=12&dg=2&pid=906",
    //文学教育
    "http://bk.11185.cn/index/catalogSearch.do?method=findCatalogByType&tv=13&dg=2&pid=907",
    "http://bk.11185.cn/index/catalogSearch.do?method=findCatalogByType&tv=14&dg=2&pid=907",
    //医学保健
    "http://bk.11185.cn/index/catalogSearch.do?method=findCatalogByType&tv=15&dg=2&pid=908",
    "http://bk.11185.cn/index/catalogSearch.do?method=findCatalogByType&tv=16&dg=2&pid=908",
    //综合类
    "http://bk.11185.cn/index/catalogSearch.do?method=findCatalogByType&tv=17&dg=2&pid=909",
    "http://bk.11185.cn/index/catalogSearch.do?method=findCatalogByType&tv=18&dg=2&pid=909",
);


function parseDetailUrl($url){
    $detailUrl = array();

    $content = file_get1($url);
    $dom = new simple_html_dom();
    $html = $dom->load($content);
    $node = $html->find("div.imgBox a");
    if(count($node)>0){
        foreach($node as $a){
            $u = $a->href;
            $detailUrl[] = $u;
        }
    }
    $dom->clear();$dom = new simple_html_dom();
    $html = $dom->load($content);
     return $detailUrl;
}

function parseOnePaggerUrl($content){
    $dom = new simple_html_dom();
    $html = $dom->load($content);
    $node = $html->find("div.page a");
    if(count($node)>2){
        $node = $node[1];
    }

    $dom->clear();
    return $node->href;
}

function mkUrl($i, $urlTemplate){
    return preg_replace("/page=\d+&/", "page=$i&", $urlTemplate);
}

function parsePagger($u){
    $paggerUrl = array();
    $content = file_get1($u);
    $dom = new simple_html_dom();
    $html = $dom->load($content);
    $options = $html->find("option");
    $pageCount = count($options);
    $urlTemplate = parseOnePaggerUrl($content);

    for($i=1; $i<=$pageCount; $i++){
        $u = mkUrl($i, $urlTemplate);
        $paggerUrl[] = $u;
    }

    $dom->clear();
    return $paggerUrl;
}

function parse11185DetailPage($url){
    $result = array();
    $content = file_get1($url);
    $dom = new simple_html_dom();
    $html = $dom->load($content);
    $node = $html->find("div.right div a");
    $class = "";
    foreach($node as $n){
        $class .= (trim($n->plaintext) . "#");
    }
    $result['class'] = $class;

    $node = $html->find("div#name h1");//刊物名称
    if(count($node)>0){
        $node = $node[0];
        $bookName = $node->plaintext;
        $result['book_name_zh'] = $bookName;
    }

    $dom->clear();
    return $result;
}

foreach($portal as $url){
    echo "parsePagger\n";
    $listUrl = parsePagger($url);//找到全部的列表页
    foreach($listUrl as $listU){//针对每个列表页
        echo "parseDetailUrl\n";
        $detailUrl = parseDetailUrl($listU);//找到所有的详情页
        foreach($detailUrl as $detailU){//针对每个详情页
            echo "parse11185DetailPage $detailU\n";
            $result = parse11185DetailPage($detailU);
            file_put_contents("./detail.log", my_json_encode($result) . "\n", FILE_APPEND);
            file_put_contents("detailUrl.log", $result['class'] . "\t".$detailU . "\n", FILE_APPEND);
        }
    }
}
?>

