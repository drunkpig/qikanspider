<?php 
require_once "../lib/functions.php";

function saveDetail($info){
    file_put_contents("./cnki_detail.log", my_json_encode($info)."\n", FILE_APPEND);
}

function parseDetail($bigClass, $subClass, $info)
{
    $kvMap = array(
        "主办"=>"zhu_ban_dan_wei",
        "周期"=>"chu_ban_zhou_qi",
        "出版地"=>"chu_ban_di",
        "语种"=>"yu_zhong",
        "开本"=>"kai_ben",
        "ISSN"=>"issn",
        "CN"=>"cn",
        "邮发代号"=>"you_fa_dai_hao",
        "复合影响因子"=>"fu_he_ying_xiang_yin_zi",
        "综合影响因子"=>"zong_he_ying_xiang_yin_zi",
        "现用刊名"=>"xian_yong_kan_ming",
        "曾用刊名"=>"ceng_yong_kan_ming",
        "创刊时间"=>"chuang_kan_shi_jian",
        "该刊被以下数据库收录"=>"shu_ju_ku_shou_lu",
        "核心期刊"=>"he_xin_list",
        "期刊荣誉"=>"huo_jiang",
        "刊名"=>"book_name_zh",
    );
    $class = $bigClass . "#" . $subClass;
    $result = array();
    $result['class'] = $class;

    foreach ($info as $c => $url) {
        //$result['book_name'] = trim($c);
        $result['url'] = $url;
        $content = file_get1($url);
        $dom = new simple_html_dom();
        $html = $dom->load($content);
        $fengmian = $dom->find("div#tdPic img");
        if(count($fengmian)>0){
            $fengmian = $fengmian[0];
            $imgSrc = $fengmian->src;
            $imgSrc = substr($imgSrc, strlen("/fengmian/"));
            $src = "http://c61.cnki.net/" . $imgSrc;
            $result['feng_mian'] = img_get_file($src);
        }
        $isDuJia = $dom->find("div.duJia");
        if(count($isDuJia)>0){
            $result['is_du_jia'] = true;
        }else $result['is_du_jia'] = false;

        $node = $html->find("div#tdInfo");
        if (count($node) > 0) {
            $node = $node[0];
            $text = $node->plaintext;
            $text = str_replace("历史沿革：", "", $text);
            $text = str_replace("&nbsp;", "", $text);
            //var_dump($text);
            $arr = explode("\n", $text);
            $temp = array();
            $key = "";
            foreach($arr as $line){
                if(strlen(strip_tags($line))<=0){
                    continue;
                }
                //$line = strip_tags($line);
                if(strpos($line, "：")>0){
                    $l = explode("：", $line);
                    if(count($l)==1){
                        $key = trim($l[0]);
                        $key = $kvMap[$key];
                        //$temp[$key] = "";
                        echo "find key $key\n";
                    }
                    else if(count($l)==2){
                        $key = trim($l[0]);
                        $key = $kvMap[$key];
                        $value = trim($l[1]);
                        $value = strip_tags($value);
                        if(strlen($value)>0){
                            $temp[$key] = $value;
                            echo "add key-value: $key = $value\n";
                        }
                    }
                }
                else{
                    $line = strip_tags(trim($line));
                    if(strlen($line)<=0){
                        continue;
                    }
                    $v = @$temp[$key];
                    if(!$v){
                        $value = trim($line);
                    } else{
                        $value = $v . "#" . trim($line);
                    }

                    $temp[$key] = $value;
                    echo "append key-value : $key = $value\n";
                }
            }
            $result = array_merge($result, $temp);

        }

        $t = $result['zong_he_ying_xiang_yin_zi'];
        $t = str_replace("#", "",  $t);
        $result['zong_he_ying_xiang_yin_zi'] = $t;

        $t = $result['huo_jiang'];
        $t = str_replace("#", "",  $t);
        $result['huo_jiang'] = $t;

        $t = $result['chuang_kan_shi_jian'];
        $t = str_replace("#", "",  $t);
        $result['chuang_kan_shi_jian'] = $t;

        $t = $result['yu_zhong'];
        $t = str_replace(";", "#",  $t);
        $result['yu_zhong'] = $t;

        $t = $result['book_name_zh'];
        $arr = explode("#", $t);
        $name1 = $arr[0];
        if(preg_match("/[a-zA-Z]/", $name1)){
            $result['book_name_en'] = $name1;
            if(count($name1)==2){
                $result['book_name_zh'] = $arr[1];
            }
        }else{
            $result['book_name_zh'] = $name1;
            if(count($name1)==2){
                $result['book_name_en'] = $arr[1];
            }
        }

        $t = $result['ceng_yong_kan_ming'];
        $t = str_replace(";", "#",  $t);
        $result['ceng_yong_kan_ming'] = $t;


        var_dump($result);
        $result['_from'] = "cnki";
        saveDetail($result);
    }
}



?>

<?php
$portal = array(
                "自然科学与工程技术" => "http://epub.cnki.net/kns/oldnavi/n_Navi.aspx?NaviID=116&Flg=", 
                "人文社会科学"       => "http://epub.cnki.net/kns/oldnavi/n_Navi.aspx?NaviID=117&Flg="
			   );

foreach($portal as $k=>$u)
{
	$content = file_get($u);
	$urls = parse_entry_page($content);
	foreach($urls as $c=>$pageu)
	{
		echo "Begin to get $c\n";
		$info = get_a_class_of_qikan("$k#$c", $pageu);
		parseDetail($k, $c, $info);

		echo "End get $c\n";
	}
}


?>