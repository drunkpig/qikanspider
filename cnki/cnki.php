<?php 
require_once "../lib/functions.php";

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
            $imgSrc = substr($imgSrc, strlen("/fengmian/"));
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
            //TODO
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