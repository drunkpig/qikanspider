<?php 
require_once "../lib/simple_html_dom.php";
require_once "../lib/HttpClient.class.php";
require_once "../lib/functions.php";

function saveResult($line)
{
	$fp = fopen("./detail_url.log", "a+");
	fwrite($fp, $line);
	fclose($fp);
	echo "SAVE ............. $line";
}
function saveCore($arr, $u){
	$fp = fopen("./core.log", "a+");
	foreach($arr as $key){
		echo "$key\t$u\n";
		fwrite($fp, "$key\n");
	}
	//var_dump($arr);
	fclose($fp);
}
function savePrePub($arr, $u){
	$fp = fopen("./prePub.log", "a+");
	foreach($arr as $key){
		echo "$key\t$u\n";
		fwrite($fp, "$key\n");
	}
	//var_dump($arr);
	fclose($fp);
}

function rendCoreUrl($url){
	
	$code = substr($url, strpos($url, "=")+1);
	$url = "http://c.wanfangdata.com.cn/PeriodicalSubject.aspx?NodeId=$code&IsCore=true";
	return $url;
}

function rendPrePubUrl($url){
	$code = substr($url, strpos($url, "=")+1);
	$url = "http://c.wanfangdata.com.cn/PeriodicalSubject.aspx?NodeId=$code&IsPrePublished=true";
	return $url;
}

function parseOtherAttribute(&$indexArray){
	$newArray = array();
	foreach($indexArray as $class=>$url){
		$newClass = $class;
		echo "1\n";
		$content = file_get1(rendCoreUrl($url));
		$dom = new simple_html_dom();
		$html = $dom->load($content);
		//找到期刊名字数组
		$core = array();
		$docs = $html->find("ul.record_items li");
		foreach($docs as $qikan)
		{
			$a = $qikan->find("a");
			if(count($a)==2){
				$a = $a[1];
				$name = trim($a->plaintext);
				$core[] = $name;
			}
		}
		saveCore($core, rendCoreUrl($url));
		$dom->clear();
		echo "2\n";
		$content = file_get1(rendPrePubUrl($url));
		$dom = new simple_html_dom();
		$html = $dom->load($content);
		//找到优先出版刊物名称数组2
		$prePub = array();
		$docs = $html->find("ul.record_items li");
		foreach($docs as $qikan)
		{
			$a = $qikan->find("a");
			if(count($a)>=2){
				$a = $a[1];
				$name = trim($a->plaintext);
				$prePub[] = $name;
			}
			
		}
		$dom->clear();
		savePrePub($prePub, rendPrePubUrl($url));
	}

}

function process($class, $content)
{
	$dom = new simple_html_dom();
	$html = $dom->load($content);
	$docs = $html->find("ul.record_items11 li");
		
	foreach($docs as $qikan)
	{
		$a = $qikan->find("a");
		$a = $a[1];
		$name = trim($a->plaintext);
		$href = $a->href;
		$href = str_replace("Periodical", "PeriodicalProfile", $href);
		$href = "http://c.wanfangdata.com.cn/" . $href;
		$key = $class . "#" . $name;
		$url = "" . $href;
		$qikanPageMap[$key] = $url;
		saveResult("$key\t$url\n");
	}
	$dom->clear();
}

/**
 * 除去书名字
 * @param $class
 */
function _get_class($class){
	$arr = explode("#", $class);
	$len = count($arr);
	$arr2 = array();
	for($i=0; $i<$len-1; $i++){
		array_push($arr2, $arr[$i]);
	}

	return my_join("#", $arr2);
}

function parseDetail($class, $url, $content){
	echo "解析详情： $url\n";

	$detailLog = "./wf_detail_temp.log";
	
	$result = array();
	$result['url'] = $url;
	$result['class'] = _get_class($class);
	
	$dom = new simple_html_dom();
	$html = $dom->load($content);
	
	//1,期刊封面
	$node = $dom->find("img#periodicalImage");
	$node = $node[0];
	$imgUrl = $node->src;
	$result['feng_mian'] = img_get_file($imgUrl);

	echo "[1]封面->";
	//2,中文名称
	$node = $dom->find("div.qkhead_list_qk h1");
	$node = $node[0];
	$bookName = $node->plaintext;
	$result['book_name_zh'] = trim($bookName);
	echo "[2]中文名->";
	//3,英文名称
	$node = $dom->find("p#qkhead_en");
	if(count($node)>0){
		$node = $node[0];
		$bookName = $node->plaintext;
		$result['book_name_en'] = trim($bookName);
	}
	echo "[3]英文名->";
	//4,期刊简介
	$node = $dom->find("p.qikan_info");
	if(count($node)>0){
		$node = $node[0];
		$jianjie = $node->plaintext;
		$result['jian_jie'] = trim($jianjie);
	}
	echo "[4]期刊简介->曾用名->";
    //+曾用名
    $hasCengYongMing = 0;
    $node = $dom->find("div.qikan_lm");
    if(count($node)>0){
        $node = $node[0];
        $node = $node->find("p");
        if(count($node)==2){
            $cengYongMing = trim($node[0]->plaintext);
            if($cengYongMing=="曾用名"){
                $hasCengYongMing = 1;
            }
            $val = trim($node[1]->plaintext);
            $result['ceng_yong_kan_ming'] = $val;

        }
    }

	//5,主要栏目
	$node = $dom->find("div.qikan_lm");
	if(count($node)>0){
		$node = $node[0+$hasCengYongMing];
		$node = $node->find("span");
	}
		
	if(count($node)>0){
		$lanmuArr = array();
		foreach($node as $nd){
			$lm = trim($nd->plaintext);
			$lanmuArr[] = $lm;
		}
		$lanmu = my_join("#", $lanmuArr);
		$result['lan_mu'] = $lanmu;
	}
	echo "[5]主要栏目->";

	//6,期刊信息
	$node = $dom->find("div.qikan_lm");
	if(count($node)>=2){//处理情况http://c.wanfangdata.com.cn/PeriodicalProfile-sjmz.aspx
        $i = 1+$hasCengYongMing;
        if(count($node)==2){
            $i = 0;
        }
		$node = $node[$i];
		$node = $node->find("p");
		
		if(count($node)>0){
            $info = array();
            foreach($node as $nd) {
                $text = $nd->plaintext;
                $arr = explode("：", $text);
                $key = "";
                $val = "";
                if (count($arr) == 1) {
                    $key = trim($arr[0]);
                } else if (count($arr) == 2) {
                    $key = trim($arr[0]);
                    $val = trim($arr[1]);
                }
                $info[$key] = $val;
            }

            $kvMap = array(
                "主管单位" => "zhu_guan_dan_wei",
                "主办单位" => "zhu_ban_dan_wei",
                "主编" => "zhu_bian",
                "ISSN" => "issn",
                "CN" => "cn",
                "地址" => "di_zhi",
                "邮政编码" => "you_zheng_bian_ma",
                "电话" => "dian_hua",
                "Email" => "you_xiang",
                "网址" => "guan_fang_wang_zhan",
            );

            $info = array_key_replace($kvMap, $info);
            $info["you_xiang"] = strtolower($info["you_xiang"]);
            $info["guan_fang_wang_zhan"] = strtolower($info["guan_fang_wang_zhan"]);
            $info['zhu_ban_dan_wei'] = str_replace("  ", "#", $info['zhu_ban_dan_wei']);
            $info['dian_hua'] = str_replace(" ", "#", $info['dian_hua']);
            $result = array_merge($info, $result);

        }
        else{
            echo "没找到\n";
        }
	}
	echo "[6]详细信息->";
	//7,获奖情况
	$node = $dom->find("div.qikan_lm");
	if(count($node)>2){
		$node = $node[count($node)-1];
		$info = array();
		$node = $node->find("span");
		foreach($node as $nd){
			$info[] = trim($nd->plaintext);
		}
		
		$result['huo_jiang'] = my_join("#", $info);
	}
	echo "[7]获奖情况\n";
	file_put_contents($detailLog, my_json_encode($result) . "\n", FILE_APPEND);
	
	$dom->clear();
    return $result;
}

?>


<?php

//	 $url = "http://c.wanfangdata.com.cn/PeriodicalProfile-zxdt.aspx";
//	 $content = file_get1($url);
//	 $r = parseDetail("thisis-class", $url, $content);
//   var_dump($r);
//	 exit;

	$potral = "http://c.wanfangdata.com.cn/Periodical.aspx";
	$content = file_get_contents($potral);
	fsave("./cache/index.html", $content);

	$dom = new simple_html_dom();
	$html = $dom->load($content);
	$indexArray = array();
	$docs = $html->find("table.s2_base");
	foreach($docs as $table)
	{
		$class = $table->find("th a");
		$class = trim($class[0]->plaintext);

		$subClass = $table->find("li a");
		foreach($subClass as $sub)
		{
			$u = "http://c.wanfangdata.com.cn/";
			$u = $u . $sub->href;
			$subClassName = trim($sub->plaintext);
			$indexArray["$class#$subClassName"] = $u;
		}
	}
	$dom->clear();
	//////////////////////////////////////////////////////
	//根据索引来抓取
	
	//var_dump($indexArray);
	parseOtherAttribute($indexArray);//添加是否核心，是否优先出版的属性
	$qikanPageMap = array();
	foreach($indexArray as $class=>$url)
	{
		//1,得到页面
		echo "3\n";
		$content = file_get1($url);
		$dom = new simple_html_dom();
		$html = $dom->load($content);
		process($class, $content);
		
		$page = $html->find("span.page_link");
		
		if(count($page)!=0)
		{
			$page = trim($page[0]->plaintext);
			$page = str_replace("&nbsp;", "", $page);
			$page = str_replace("共", "", $page);
			$page = str_replace("页", "", $page);
			echo "total page $page\n";
			for($i=2; $i<=$page; $i++)
			{
				echo "解析第".  $i . "页\n";
				$u = $url . "&PageNo=$i";
				echo "4\n";
				$content = file_get1($u);
				process($class, $content);
			}
		}
	}
	
	/**
	 * 根据index.log里的内容解析详细的数据，
	 * 解析之后的数据放在detail.log里，以json的形式
	 */
	 $fp = fopen("./detail_url.log", "r");
	 $line = "";
	 while(($line=fgets($fp))){
		 if(strlen($line)>0){
			 $arr = explode("\t", $line);
			 $class = $arr[0];
			 $url = $arr[1];
			 $content = file_get1($url);
			 parseDetail($class, $url, $content);
		 }
	 }
	 
	/**
	 * 优先出版、是否核心
	 */
	$lines = file_get_contents("./core.log");
    $cores = explode("\n", $lines);
    $lines2 = file("./prePub.log");
    $prePub = explode("\n", $lines2);

    $fp = fopen("./wf_detail_temp.log");
    $line == "";
    while(($line=fgets($fp))){
        $line = trim($line);
        if(strlen($line)>0){
            $result = json_decode($line);
            $book_name_zh = $result['book_name_zh'];
            $book_name_en = $result['book_name_en'];
            if(in_array($book_name_zh, $cores) || in_array($book_name_en, $cores)){
                $result['is_he_xin'] = "Y";
            }
            else $result['is_he_xin'] = "N";

            if(in_array($book_name_zh, $prePub) || in_array($book_name_en, $prePub)){
                $result['is_you_xian_chu_ban'] = "Y";
            }
            else $result['is_you_xian_chu_ban'] = "N";
        }
        $result['_from'] = "wanfang";

        file_put_contents("./wf_detail.log", my_json_encode($result), FILE_APPEND);
    }
	
?>