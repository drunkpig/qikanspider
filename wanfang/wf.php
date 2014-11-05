<?php 
require_once "../lib/simple_html_dom.php";
require_once "../lib/HttpClient.class.php";
require_once "../lib/functions.php";

function file_get($filePath)
{
	
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

	//////////////////////////////////////////////////////
	//根据索引来抓取
	
	var_dump($indexArray);
	foreach($indexArray as $k=>$v)
	{
		//1,得到页面
		$content = file_get("");
		
	}

?>