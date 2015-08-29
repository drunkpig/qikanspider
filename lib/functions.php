<?php 
require_once "simple_html_dom.php";
require_once "HttpClient.class.php";

$client = new HttpClient('epub.cnki.net');

function parse_img_url($content)
{
	$pattern = "/<img width=206 src='\/fengmian\/(.*?)'/";
	$match = array();
	preg_match($pattern, $content, $match);
	$u = $match[1];
	$arr = explode("/", $u);
	$u = end($arr);
	return $u;
}

function get_image_big($img)
{
	$bigUrl = "http://c61.cnki.net/CJFD/big/" . $img;
	$content = file_get_contents($bigUrl);
	$img_big_dir = "./big/";
	file_put_contents($img_big_dir . $img, $content);
}

function get_image_small($img)
{
	$img_small_dir = "./small/";
	$smallUrl = "http://c61.cnki.net/CJFD/small/" . $img;
	$content = file_get_contents($smallUrl);
	file_put_contents($img_small_dir . $img, $content);
}

/**
 * 大类别＃次级类别=>链接
 * 数组形式
 */
function parse_entry_page($content)
{
	$baseUrl = "http://epub.cnki.net/kns/oldnavi/";
	$dom = new simple_html_dom(); 
	$html = $dom->load($content);
	$blocks = $html->find("div.col");
	$array = array();
	foreach($blocks as $blk)
	{
		$title = $blk->find("h5");
		$title = $title[0]->plaintext;
		$title = trim($title);
		$title = substr($title, 0, strpos($title, "("));
		
		$subClass = $blk->find("li");
		foreach($subClass as $c)
		{
			$subTitle = $c->plaintext;
			$subTitle = trim($subTitle);
			$subTitle = substr($subTitle, 0, strpos($subTitle, "("));
			// 寻找URL
			$u = $c->find("a");
			$u = $u[0];
			$u = $u->href;
			$u = $baseUrl . $u;
			$array["$title#$subTitle"] = $u;
		}
	}
	$dom->clear();
	return $array;
}

function get_cache_file_name($url)
{
	$fpath = "./cache/" . md5($url) . ".html";
	return $fpath;
}

function file_get($url)
{
	$fname = get_cache_file_name($url);
	//if(file_exists($fname))
	//{
	//	echo "cache hit!\n";
	//	return file_get_contents($fname);
	//}
	//else
	//{
	//	//echo "cache miss!\n";
	//	$c = file_get_contents($url);
	//	file_put_contents($fname, $c);
	//	return $c;
	//}
	
	global $client;
	$client->get($url);
	$content = $client->getContent();
	if(strlen($content)>100)
	{
		file_put_contents($fname, $content);
	}
	
	//sleep(2);
	return $content;
}

function parse_page_count($content)
{
	$dom = new simple_html_dom();
	$html = $dom->load($content);
	$page = $html->find("span#lblPageCount");
	$page = $page[0]->plaintext;
	$dom->clear();
	return $page;
}

function parse_paged_url($content)
{
	$urlBase = "http://epub.cnki.net/kns/oldnavi/";
	$result = array();
	$dom = new simple_html_dom();
	$html = $dom->load($content);
	$qikans = $html->find("div.colPic");
	foreach($qikans as $q)
	{
		$a = $q->find("p a");
		$a = $a[0];
		$name = $a->plaintext;
		$url = $a->href;
		$url = $urlBase. $url;
		$result[$name] = $url;
	}
	$dom->clear();
	return $result;
}

function get_view_state($content)
{
	$dom = new simple_html_dom();
	$html = $dom->load($content);
	$viewState = $html->find("input[name=__VIEWSTATE]");
	$v = $viewState[0];
	$v =  $v->value;
	return $v;
}

/*
hidUID:
hidType:CJFQ
drpField:cykm$%"{0}"
txtValue:
DisplayModeRadio:图形方式
drpAttach:order by idno

DisplayModeRadio11:图形方式
drpAttach:order by idno

*/
function parse_post_data($i, $content)
{
	$data = array();
	$data["__EVENTTARGET"] = "lbNextPage";
	$data["__EVENTARGUMENT"] = "";
	$data["__VIEWSTATE"] = get_view_state($content);
	$data["txtPageGoTo"] = $data["txtPageGoToBottom"] = $i;
	$data["hidUID"] = "";
	$data["hidType"] = "CJFQ";
	$data["drpField"] = 'cykm$%"{0}"';
	$data["txtValue"] = "";
	//$data["DisplayModeRadio"] = "图形方式";
	$data["drpAttach"] = "order by idno";
	//$data["DisplayModeRadio11"] = "图形方式";
	$data["drpAttach"] = "order by idno";
	return $data;
}

function fsave($name, $content)
{
	$content = trim($content);
	if(strlen($content)<500)
	{
		echo "Empty page\n";
		return false;
	}
	file_put_contents($name, $content);
	return true;
}

function parse_ot_page($pageCount, $firstPageContent, $startUrl)
{
	global $client;
	$client->get($startUrl);//目的是为了获取cookie和ref头

	$result = array();
	$pageContent = $firstPageContent;
	for($i=1; $i<$pageCount; $i++)//从第二页开始到$pageCount页
	{
		$fname = get_cache_file_name($startUrl.$i);
		//if(file_exists($fname))
		//{
		//	$pageContent = file_get_contents($fname);
		//	echo "...........Cached\n";
		//}
		//else
		//{
			$postData = parse_post_data($i, $pageContent);
			//var_dump($postData);exit;
			//填充post的参数
			$client->post($startUrl, $postData);
			$pageContent = $client->getContent();
			//var_dump($pageContent);exit;
			if(!fsave($fname, $pageContent))
			{
				echo "Empty Content\n";
				continue;
			}
			else
			{
				echo "POST　GET.....\n";
			}
			sleep(2);
		//}
		$cur = $i+1;
		echo "$cur / $pageCount / $fname\n";
		//解析页面内容
		echo "解析。。。。\n";
		$o = parse_paged_url($pageContent);
		$result = array_merge($result, $o);
		
	}
	//echo "========================================\n";
	return $result;
}

function resetClient()
{
	global $client;
	$client = new HttpClient('epub.cnki.net');
}

/**
 * 解析一个类别的，多页
 */
function get_a_class_of_qikan($class, $startUrl)
{
	resetClient();
	//echo "StartUrl : $startUrl\n";
	$content = file_get($startUrl);
	//1,找出来有多少页
	$pageCount = parse_page_count($content);
	echo "Get Page Count of $pageCount\n";
	//2,获取页面上具体的杂志地址
	$nameUrl = parse_paged_url($content);
	//3,循环获取其他分页中的
	$ot = array();
	if($pageCount>1)
	{
		$ot = parse_ot_page($pageCount, $content, $startUrl);
	}
	$nameUrl = array_merge($nameUrl, $ot);
	foreach($nameUrl as $u){
        file_put_contents("./cnki_url.log", "$class\t$u\n", FILE_APPEND);
    }
	return $nameUrl;
}

function my_json_encode(array $data) {
        $s= array();
        foreach($data as $k => $v) {
            if(is_array($v)) {
                $v = my_json_encode($v);
                $s[] = "\"$k\":$v";
            }else{
                $v = addslashes( str_replace( array("\n","\r"), '', $v));
                $s[] = "\"$k\": \"$v\"";
            }
        }
        return '{'.implode(', ', $s).'}';
    }

function file_get1($filePath)
{
    $filePath = trim($filePath);#有换行会导致file_get_contents报404
    $fname = md5($filePath).".html";
    $fname = "./cache/$fname";
    echo "$filePath => $fname get ";
    if(file_exists($fname))
    {
        echo " from cache.\n";
        return file_get_contents($fname);
    }
    else
    {
        $content = file_get_contents($filePath);
        $dom = new simple_html_dom();
        $html = $dom->load($content);
        $title = $html->find("title", 0);
        if(($title && $title->plaintext=="404 - 找不到文件或目录。")||!$title){
            echo "404 ERROR $filePath\n";
			$dom->clear();
            return "";//服务器出错
        }
        $dom->clear();
        if(strlen($content)>100){
            file_put_contents($fname, $content);
            echo " from net.\n";
            sleep(10);
        }
        else echo " from net but too small.\n";
        return $content;
    }
}

/**
 * 获取图片，根据大小过滤:小于1Kb的删除重新抓
 * @param $imgUrl
 */
function img_get_file($imgUrl){

    $imageCache = "./img/";
    $imgFile = $imageCache . md5($imgUrl).".jpg";
    if(file_exists($imgFile)){
        $imgContent = file_get_contents($imgFile);
        $len = strlen($imgContent);
        # echo $len . " >>>>\n";
        if($len<2048){
            unlink($imgFile);
            echo "delete file $imgFile\n";
        }
    }

    if(!file_exists($imgFile)){
		if(strlen(trim($imgFile))>0){
			$imgContent = @file_get_contents($imgUrl);
		}else $imgContent = "";


        $len = strlen($imgContent);
		if($len >2048){
			file_put_contents($imgFile, $imgContent);
			echo "save image: $imgFile, size=$len\n";
		}
		else{
			echo "image: $imgFile, size=$len, too small not save\n";
		}


		sleep(1);
    }
    return $imgFile;
}

function my_join($joinChar, $array){
    array_walk($array, function(&$val, $key){
        $val = trim($val);
    });
    $str = join($joinChar, $array);

    return $str;
}

function array_key_replace($kvMap, $array){
    $arr = array();
    foreach($kvMap as $key=>$val){
        $arr[$val] = $array[$key];
    }
    return $arr;
}
?>