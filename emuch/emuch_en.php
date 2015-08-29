<?php
require_once "../lib/simple_html_dom.php";
require_once "../lib/HttpClient.class.php";
require_once "../lib/functions.php";

$keyMap = array(
    "期刊名"=>"book_name_en",

    "出版周期"=>"chu_ban_zhou_qi",
    "出版ISSN"=>"issn",
    "通讯方式"=>"chu_ban_di",
    "期刊主页网址"=>"site",
    "在线投稿网址"=>"tou_gao_site",
    "其他相关链接"=>"xiang_guan_lian_jie",

    "偏重的研究方向"=>"lan_mu",
    "投稿录用比例"=>"lu_yong_bi_li",
    "审稿速度"=>"shen_gao_su_du",
    "审稿费用"=>"shen_gao_fei",
    "版面费用"=>"ban_mian_fei",

);
/**
 * @param $i
 * @return string  下一个列表页面url
 */
function rendDetailPage($i){
    $portal = "http://emuch.net/bbs/journal.php?view=detail&jid=$i";
    return $portal;
}

/**
 * @param $u 从这个页面上获得条目总数
 */
function getMaxPage($u){
    $content = file_get1($u);
    $dom = new simple_html_dom();
    $html = $dom->load($content);
    $node = $html->find("table.multi td.header");
    $node = $node[0];
    $bookCount = trim($node->plaintext);

    $dom->clear();
    return $bookCount;
}

function parseDetailInfo($url){
    global $keyMap;
    $content = file_get1($url);
    $content =  iconv('GB2312', 'UTF-8', $content);
    $result = array();
    $result['url'] = $url;
    $dom = new simple_html_dom();
    $html = $dom->load($content);
    $node = $html->find("div#bbsmain table[bgcolor=#648EB2]");
    foreach($node as $tb){
        $table1 = $tb->find("table[cellpadding=5]", 0);

        if($table1){
            $trs = $table1->find("tr");

            foreach($trs as $tr){
                $tds = $tr->find("td");
                if(count($tds)==2){
                    $td1 = $tds[0];
                    $td2 = $tds[1];
                    $key = trim($td1->plaintext);
                    $value = trim($td2->plaintext);
                    if($key=="期刊主页网址" || $key=="在线投稿网址"){
                        $value = $td2->find("a",0);
                        $value = $value->href;
                    }
                    if($key=='其他相关链接'){
                        $value = $td2->find("a");
                        $temp = array();
                        if(count($value)>0){
                            foreach($value as $a){
                                $text = $a->plaintext;
                                $text = str_replace("&amp;", "&", $text);
                                $val = $a->href;
                                $temp[] = trim($text) . "#" . trim($val);
                            }
                            $value = my_join("#", $temp);
                        }
                    }
                    $realKey = @$keyMap[$key];
                    if(strlen($realKey)>0){
                        $result[$realKey] = $value;
                    }
                    else{
                        echo "没有找到key:$key\n";
                    }
                }
            }
        }
    }
    $result['lan_mu'] = str_replace("&nbsp;", "", $result['lan_mu']);
    $result['lan_mu'] = str_replace(" ", "#", $result['lan_mu']);
    $result['lan_mu'] = preg_replace("/\(.*?\)/", "", $result['lan_mu']);

    $result['_from'] = "emuch_en";
    return $result;
}

$detailLog = "./emuch_detail_en.log";
$detailUrl = "./emuch_url_en.log";

$udetail = "http://emuch.net/bbs/journal.php?from=emuch&view=&classid=0&class_credit=0&page=1";
$bookCount = getMaxPage($udetail);
for($i=1; $i<=$bookCount; $i++){
    $bookUrl = rendDetailPage($i);
    $bookInfo = parseDetailInfo($bookUrl);
    file_put_contents($detailLog, my_json_encode($bookInfo)."\n", FILE_APPEND);
    echo "$bookUrl\n";
    file_put_contents($detailUrl, $bookInfo['book_name_zh']."\t$bookUrl\n", FILE_APPEND);
}
