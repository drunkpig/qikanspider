<?php
require_once "../lib/simple_html_dom.php";
require_once "../lib/HttpClient.class.php";
require_once "../lib/functions.php";

function saveUrl($u){
    file_put_contents("./detailUrl.log", $u."\n", FILE_APPEND);
}

/**
 * 获取一个页面上的详细期刊链接地址
 * @param $url
 * @return array
 */
function getDetailUrl($url){
    $detailUrl = array();//全部的详情页面

    $content = file_get1($url);
    $dom = new simple_html_dom();
    $html = $dom->load($content);
    $node = $html->find("div#journallistpanel table a");
    if(count($node)>0){
        foreach($node as $a){
            $u = $a->href;
            $detailUrl[] = $u;
        }
    }

    return $detailUrl;
}

/**
 * 解析详情页
 * @param $content
 */
function parseCqvipDetail($content){
    $result = array();

    $dom = new simple_html_dom();
    $html = $dom->load($content);
    $classList = $html->find("div.magsearch span.song");
    $classList = $classList[0];
    $tex = $classList->plaintext;
    $tex = str_replace("&gt;", ">", $tex);
    $arr = explode(">", $tex);
    $class  = "";
    for($i=2; $i<count($arr)-1; $i++){
        $class .= trim($arr[$i])."#";
    }
    $class .= trim($arr[count($arr)-1]);
    $result['class'] = $class;

    $dom->clear();

    return $result;
}

function getCover($content){
    $dom = new simple_html_dom();
    $html = $dom->load($content);
    $img = $html->find("td.magcover img");
    if(count($img)>0){
        $img = $img[0];
        $src = $img->src;
        img_get_file($src);
    }
    else{
        echo "没有发现封皮\n";
    }

    $dom->clear();
}
$portal = array(
    "http://www.cqvip.com/Journal/2.shtml",
    "http://www.cqvip.com/Journal/3.shtml",
    "http://www.cqvip.com/Journal/4.shtml",
    "http://www.cqvip.com/Journal/5.shtml",
    "http://www.cqvip.com/Journal/6.shtml",
    "http://www.cqvip.com/Journal/7.shtml",
    "http://www.cqvip.com/Journal/8.shtml",
    "http://www.cqvip.com/Journal/9.shtml",
    "http://www.cqvip.com/Journal/10.shtml",
    "http://www.cqvip.com/Journal/11.shtml",
    "http://www.cqvip.com/Journal/12.shtml",
    "http://www.cqvip.com/Journal/13.shtml",
    "http://www.cqvip.com/Journal/14.shtml",
    "http://www.cqvip.com/Journal/15.shtml",
    "http://www.cqvip.com/Journal/16.shtml",
    "http://www.cqvip.com/Journal/17.shtml",
    "http://www.cqvip.com/Journal/18.shtml",
    "http://www.cqvip.com/Journal/68.shtml",

    "http://www.cqvip.com/Journal/19.shtml",
    "http://www.cqvip.com/Journal/20.shtml",
    "http://www.cqvip.com/Journal/21.shtml",
    "http://www.cqvip.com/Journal/22.shtml",
    "http://www.cqvip.com/Journal/23.shtml",
    "http://www.cqvip.com/Journal/24.shtml",
    "http://www.cqvip.com/Journal/25.shtml",
    "http://www.cqvip.com/Journal/26.shtml",
    "http://www.cqvip.com/Journal/27.shtml",
    "http://www.cqvip.com/Journal/28.shtml",
    "http://www.cqvip.com/Journal/29.shtml",
    "http://www.cqvip.com/Journal/30.shtml",
    "http://www.cqvip.com/Journal/31.shtml",
    "http://www.cqvip.com/Journal/32.shtml",
    "http://www.cqvip.com/Journal/33.shtml",
    "http://www.cqvip.com/Journal/34.shtml",
    "http://www.cqvip.com/Journal/35.shtml",
    "http://www.cqvip.com/Journal/36.shtml",
    "http://www.cqvip.com/Journal/37.shtml",
    "http://www.cqvip.com/Journal/38.shtml",

    "http://www.cqvip.com/Journal/52.shtml",
    "http://www.cqvip.com/Journal/53.shtml",
    "http://www.cqvip.com/Journal/54.shtml",
    "http://www.cqvip.com/Journal/55.shtml",
    "http://www.cqvip.com/Journal/56.shtml",
    "http://www.cqvip.com/Journal/57.shtml",
    "http://www.cqvip.com/Journal/58.shtml",
    "http://www.cqvip.com/Journal/59.shtml",
    "http://www.cqvip.com/Journal/60.shtml",
    "http://www.cqvip.com/Journal/61.shtml",
    "http://www.cqvip.com/Journal/62.shtml",

    "http://www.cqvip.com/Journal/48.shtml",
    "http://www.cqvip.com/Journal/49.shtml",
    "http://www.cqvip.com/Journal/50.shtml",
    "http://www.cqvip.com/Journal/51.shtml",
    "http://www.cqvip.com/Journal/70.shtml",

    "http://www.cqvip.com/Journal/39.shtml",
    "http://www.cqvip.com/Journal/40.shtml",
    "http://www.cqvip.com/Journal/41.shtml",
    "http://www.cqvip.com/Journal/42.shtml",
    "http://www.cqvip.com/Journal/43.shtml",
    "http://www.cqvip.com/Journal/44.shtml",
    "http://www.cqvip.com/Journal/45.shtml",
    "http://www.cqvip.com/Journal/46.shtml",
    "http://www.cqvip.com/Journal/47.shtml",
    "http://www.cqvip.com/Journal/69.shtml",
);

foreach($portal as $url){
    $detailUrl = getDetailUrl($url);

    /*pagger*/
    $content = file_get1($url);
    $dom = new simple_html_dom();
    $html = $dom->load($content);
    $paggerUrl = array();
    $pagger = $html->find("div.pager ul.pagenum a");
    $paggerUrlPrefix = "http://www.cqvip.com";
    if(count($pagger)>0){
        foreach($pagger as $a){
            $href = $a->href;
            $u = $paggerUrlPrefix . $href;
            $paggerUrl[] = $u;
        }
    }

    foreach($paggerUrl as $pgUrl){
        $detailUrlTemp = getDetailUrl($pgUrl);
        $detailUrl = array_merge($detailUrl, $detailUrlTemp);
    }

    /*抓全部的url*/
    foreach($detailUrl as $url){
        $content = file_get1($url);
        if(strlen($content)>100){//至少不是空的
            $result = parseCqvipDetail($content);
            getCover($content);
            saveUrl($result['class'] . "\t" . $url);
            file_put_contents("./detail.log", my_json_encode($result)."\n", FILE_APPEND);
        }
    }

    $dom->clear();
}
?>
