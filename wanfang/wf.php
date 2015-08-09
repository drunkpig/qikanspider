<?php 
require_once "../lib/simple_html_dom.php";
require_once "../lib/HttpClient.class.php";
require_once "../lib/functions.php";

function file_get1($filePath)
{
	$filePath = trim($filePath);#有换行会导致file_get_contents报404
	$fname = md5($filePath).".html";
	$fname = "./cache/$fname";
	echo "$fname get ";
	if(file_exists($fname))
	{
		echo " from cache.\n";
		return file_get_contents($fname);
	}
	else
	{
		$content = file_get_contents($filePath);
		if(strlen($content)>100){
			file_put_contents($fname, $content);
			echo " from net.\n";
			sleep(2000);
		}
		else echo " from net but too small.\n";
		return $content;
	}
}

function saveResult($line)
{
	$fp = fopen("./index.log", "a+");
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
	var_dump($arr);
	fclose($fp);
}
function savePrePub($arr, $u){
	$fp = fopen("./prePub.log", "a+");
	foreach($arr as $key){
		echo "$key\t$u\n";
		fwrite($fp, "$key\n");
	}
	var_dump($arr);
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

function parseDetail($class, $url, $content){
	echo "解析详情： $url\n";
	$imageCache = "./img/";
	$detailLog = "./detail.log";
	
	$result = array();
	$result['url'] = $url;
	$result['class'] = $class;
	
	$dom = new simple_html_dom();
	$html = $dom->load($content);
	
	//1,期刊封面
	$node = $dom->find("img#periodicalImage");
	$node = $node[0];
	$imgUrl = $node->src;
	$imgContent = file_get_contents($imgUrl);
	$imgFile = $imageCache . md5($imgUrl).".jpg";
	file_put_contents($imgFile, $imgContent);
	$result['image_little'] = $imgFile;
	//2,中文名称
	$node = $dom->find("div.qkhead_list_qk h1");
	$node = $node[0];
	$bookName = $node->plaintext;
	$result['book_name_zh'] = trim($bookName);
	//3,英文名称
	$node = $dom->find("p#qkhead_en");
	if(count($node)>0){
		$node = $node[0];
		$bookName = $node->plaintext;
		$result['book_name_en'] = trim($bookName);
	}
	
	//4,期刊简介
	$node = $dom->find("p.qikan_info");
	if(count($node)>0){
		$node = $node[0];
		$jianjie = $node->plaintext;
		$result['qikan_jianjie'] = trim($jianjie);
	}
	//5,主要栏目
	$node = $dom->find("div.qikan_lm");
	if(count($node)>0){
		$node = $node[0];
		$node = $node->find("span");
	}
		
	if(count($node)>0){
		$lanmu = "";
		foreach($node as $nd){
			$lm = trim($nd->plaintext);
			$lanmu .= ($lm . "#");
		}
		$result['qikan_lanmu'] = $lanmu;
	}
	//6,期刊信息
	$node = $dom->find("div.qikan_lm");
	if(count($node)>=2){
		$node = $node[1];
		$node = $node->find("p");
		
		if(count($node)>0){
		$info = array();
		foreach($node as $nd){
			$text = $nd->plaintext;
			$arr = explode("：", $text);
			$key = "";
			$val = "";
			if(count($arr)==1){
				$val = trim($arr[0]);
			}
			else if(count($arr)==2){
				$key = trim($arr[0]);
				$val = trim($arr[1]);
			}
			$info[$key] = $val;
		}
		$result['qikan_info'] = $info;
	}
	}
	
	//7,获奖情况
	$node = $dom->find("div.qikan_lm");
	if(count($node)>2){
		$node = $node[count($node)-1];
		$info = array();
		$node = $node->find("span");
		foreach($node as $nd){
			$info[] = trim($nd->plaintext);
		}
		
		$result['huo_jiang'] = $info;
	}
	
	file_put_contents($detailLog, my_json_encode($result) . "\n", FILE_APPEND);
	
	$dom->clear();
}

?>


<?php

	// $url = "http://c.wanfangdata.com.cn/PeriodicalProfile-jcfy.aspx";
	// $content = file_get1($url);
	// parseDetail("thisis-class", $url, $content);
	// exit;
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
	 $fp = fopen("./index.log", "r");
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
	 
	
	
?>