<?php 
require_once "../lib/functions.php";

$portal = array(
                "��Ȼ��ѧ�빤�̼���" => "http://epub.cnki.net/kns/oldnavi/n_Navi.aspx?NaviID=116&Flg=", 
                //"��������ѧ"       => "http://epub.cnki.net/kns/oldnavi/n_Navi.aspx?NaviID=117&Flg="
			   );

foreach($portal as $k=>$u)
{
	$content = file_get($u);
	$urls = parse_entry_page($content);
	foreach($urls as $c=>$pageu)
	{
		echo "Begin to get $c\n";
		$info = get_a_class_of_qikan($pageu);
		//echo "SAVE $c\n";
		//TODO ����
		echo "End get $c\n";
	}
}


?>