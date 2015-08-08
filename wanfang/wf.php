<?php 
require_once "../lib/simple_html_dom.php";
require_once "../lib/HttpClient.class.php";
require_once "../lib/functions.php";

function file_get1($filePath)
{
	$fname = md5($filePath).".html";
	$fname = "./cache/$fname";
	if(file_exists($fname))
	{
		return file_get_contents($fname);
	}
	else
	{
		$content = file_get_contents($filePath);
		if(strlen($content)>100){
			file_put_contents($fname, $content);
		}
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

?>


<?php

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
?>