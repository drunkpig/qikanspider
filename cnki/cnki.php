<?php 
require_once "../lib/functions.php";

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
        echo $len . " >>>>\n";
        if($len<2048){
            unlink($imgFile);
            echo "delete file $imgFile\n";
        }
    }

    if(!file_exists($imgFile)){
        $imgContent = file_get_contents($imgUrl);
        file_put_contents($imgFile, $imgContent);
    }
    return $imgFile;
}

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
            sleep(1);
        }
        else echo " from net but too small.\n";
        return $content;
    }
}

function saveDetail($info){
    file_put_contents("./detail.log", my_json_encode($info), FILE_APPEND);
}

function parseDetail($bigClass, $subClass, $info)
{
    $class = $bigClass . "#" . $subClass;
    $result = array();
    $result['class'] = $class;

    foreach ($info as $c => $url) {
        $result['book_name_zh'] = trim($c);
        $content = file_get1($url);
        $dom = new simple_html_dom();
        $html = $dom->load($content);
        $fengmian = $dom->find("div#tdPic img");
        if(count($fengmian)>0){
            $fengmian = $fengmian[0];
            $imgSrc = $fengmian->src;
            $imgSrc = substr($imgSrc, strlen("/fengmian/")+1);
            $src = "http://c61.cnki.net/" . $imgSrc;
            $result['fengmian'] = img_get_file($src);
        }
        $node = $html->find("div#tdInfo");
        if (count($node) > 0) {
            $node = $node[0];
            $text = $node->plaintext;
            $text = str_replace("<strong>历史沿革：</strong>", "", $text);
            $text = str_replace("&nbsp;", "", $text);
            var_dump($text);

        }

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
		$info = get_a_class_of_qikan($pageu);
		parseDetail($k, $c, $info);
		echo "End get $c\n";
	}
}


?>