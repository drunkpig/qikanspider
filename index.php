<?php 


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






?>

<?php 
$u = "http://epub.cnki.net/kns/oldnavi/n_CNKIPub.aspx?naviid=110&BaseID=JSJJ&NaviLink=%E7%94%B5%E5%AD%90%E4%BF%A1%E6%81%AF%E7%A7%91%E5%AD%A6%E7%BB%BC%E5%90%88-%2fkns%2foldnavi%2fn_list.aspx%3fNaviID%3d100%26Field%3d168%25e4%25b8%2593%25e9%25a2%2598%25e4%25bb%25a3%25e7%25a0%2581%26Value%3dI000%253f%26OrderBy%3didno%7c%E8%AE%A1%E7%AE%97%E6%9C%BA%E9%9B%86%E6%88%90%E5%88%B6%E9%80%A0%E7%B3%BB%E7%BB%9F";

$content = file_get_contents($u);

file_put_contents("./a.html", $content);
$imageU = parse_img_url($content);
echo $imageU;
get_image_big($imageU);
get_image_small($imageU);

?>