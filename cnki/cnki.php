<?php 
require_once "../lib/functions.php";

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
		var_dump($info);
		echo "End get $c\n";
	}
}


?>