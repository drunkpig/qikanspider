<?php
require_once "../lib/simple_html_dom.php";
require_once "../lib/HttpClient.class.php";
require_once "../lib/functions.php";
//http://lib.cqvip.com/evaluation/index.aspx
function saveUrl($u){
    file_put_contents("./cqvip_detail_url.log", $u."\n", FILE_APPEND);
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

function getQkCode($url){
    $arr = explode("/", $url);
    $len = count($arr)-2;
    return $arr[$len];
}

/**
 * 解析详情页
 * @param $content
 */
function parseCqvipDetail($u){
    $result = array();
    $result['url'] = $u;
    $content = file_get1($u);
    $dom = new simple_html_dom();
    $html = $dom->load($content);
    $classList = $html->find("div.magsearch span.song");
    if($classList){
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
    }
    else{
        return $result;
    }


    $bookNameCn = $html->find("h1.f20", 0);
    if($bookNameCn){
        $result['book_name_zh'] = trim($bookNameCn->plaintext);
    }

    $bookNameEn = $html->find("h2.f10", 0);
    if($bookNameEn){
        $result['book_name_en'] = trim($bookNameEn->plaintext);
    }

    $bookNameEn = $html->find("h1.f10", 0);
    if($bookNameEn){
        $result['book_name_en'] = $bookNameEn;
    }

    $isCore = $html->find("li.f12 img");//是否核心
    if(count($isCore)>0){
        $result['is_core'] = "Y";
        //获取是那些核心
        $code = getQkCode($u);
        $coreUrl = "http://www.cqvip.com/journal/getdata.aspx?action=jra&gch=$code";
        $cc = file_get_contents($coreUrl);
        $cc = str_replace(",", "#", $cc);
        $cc = trim($cc);
        $result['he_xin'] = $cc;

    }else $result['is_core'] = "N";

    $intro = $html->find("ul.jorintro li");
    if(count($intro)==2){
        $intro = $intro[1];
        $txt = $intro->plaintext;
        $arr = explode("：", $txt);
        $result['jian_jie'] = trim($arr[1]);
    }

    $kvMap = array(
        "主管单位"=>"zhu_guan_dan_wei",
        "主办单位"=>"zhu_ban_dan_wei",
        "主　　编"=>"zhu_bian",
        "刊　　期"=>"kan_qi",
        "开　　本"=>"kai_ben",
        "创刊时间"=>"chuang_kan_shi_jian",
        "邮发代号"=>"you_fa_dai_hao",
        //"联系方式"=>"lian_xi_fang_shi",
        "单　　价"=>"dan_jia",
        "定　　价"=>"ding_jia",
        "国内统一刊号"=>"cn",
        "国际标准刊号"=>"issn",
        "获奖情况"=>"huo_jiang",
        "国外数据库收录"=>"guo_wai_shu_ju_ku_shou_lu",
        "地　　址"=>"di_zhi",
        "邮政编码"=>"you_bian",
        "电　　话"=>"dian_hua",
        "邮　　箱"=>"you_xiang",
        "官方网站"=>"guan_fang_wang_zhan",

    );
    $qikanInfo = $html->find("ul.wow li");
    if(count($qikanInfo)>0){
        $tempInfo = array();
        foreach($qikanInfo as $li){
            $txt = trim($li->plaintext);
            $arr = explode("：", $txt);
            if(count($arr)==2){
                $key = trim($arr[0]);
                //$key = str_replace(" ", "", $key);
                $val = trim($arr[1]);
                $realkey = @$kvMap[$key];
                if($realkey){
                    $tempInfo[$realkey] = $val;
                }

                $result = array_merge($result, $tempInfo);
            }
            /*else if(count($arr)==1){
                $a = $li->find("a");
                if($a){
                    $tmp = array();
                    foreach($a as $href){
                        $tmp[] = trim($href->plaintext);
                    }
                    $str = my_join("#", $tmp);
                    $ss2 = @$result['shu_ju_ku'];
                    if(strlen($ss2)>0){
                        $ss2 = "$ss2#$str";
                    }
                    $result['shu_ju_ku'] = $ss2;
                }
            }*/
            else{
                echo "信息缺失：{$li->plaintext}\n";
            }
        }
    }

    $zhu_ban_dan_wei = @$result['zhu_ban_dan_wei'];
    if($zhu_ban_dan_wei){
        $zhu_ban_dan_wei = str_replace(" ", "#", $zhu_ban_dan_wei);
        $result['zhu_ban_dan_wei'] = $zhu_ban_dan_wei;
    }

    $huo_jiang = @$result['huo_jiang'];
    if($huo_jiang){
        $huo_jiang = str_replace("；", "#", $huo_jiang);
        $result['huo_jiang'] = $huo_jiang;
    }

    $guowai = @$result['guo_wai_shu_ju_ku_shou_lu'];
    if($guowai){
        $guowai = preg_replace("/\s+/", "#", $guowai);
        $result['guo_wai_shu_ju_ku_shou_lu'] = $guowai;
    }

    //联系方式
    $lianxi = $html->find("div#vipcontacttext ul.mdline li");
    if($lianxi){
        $array = array();
        foreach($lianxi as $lx){
            $txt = trim($lx->plaintext);
            $arr = explode("：", $txt);
            if(count($arr)==2){
                $k = trim($arr[0]);
                $v = trim($arr[1]);
                $result[$kvMap[$k]] = $v;
            }
        }
    }

    $dianhua = @$result['dian_hua'];
    if($dianhua){
        $dianhua = str_replace(" ", "#", $dianhua);
        $result['dian_hua'] = $dianhua;
    }

    $youxiang = @$result['you_xiang'];
    if($youxiang){
        $youxiang = str_replace(";", "#", $youxiang);
        $result['you_xiang'] = $youxiang;
    }

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
        return img_get_file($src);
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

//$u = "http://www.cqvip.com/qk/95190X/";
//$r = parseCqvipDetail($u);
//var_dump($r);
//exit;


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
        $result = array();
        $content = file_get1($url);
        if(strlen($content)>100){//至少不是空的
            $result = parseCqvipDetail($url);
            if(count($result)<=0){
                echo "抓到没有数据页面 $url\n";
                continue;
            }
            $img = getCover($content);
            $result['image'] = $img;
            saveUrl($result['class'] . "\t" . $url);
            file_put_contents("./cqvip_detail.log", my_json_encode($result)."\n", FILE_APPEND);
        }
    }

    $dom->clear();
}
?>
