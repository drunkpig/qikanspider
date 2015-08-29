<?php
/**
 * Created by PhpStorm.
 * User: cxu
 * Date: 2015/8/29
 * Time: 15:09
 */
require_once "./lib/functions.php";

$filepath = $argv[1];
$field_name = $argv[2];
if(empty($filepath)||!file_exists($filepath) || empty($field_name)){
    echo "file not found : $filepath. Or field_name not found\n";
    exit;
}
$field_name = trim($field_name);

$fields = array();

$fp = fopen($filepath, "r");
$line = "";
while(($line=fgets($fp))){
    $line = trim($line);
    if(count($line)==0){
        continue;
    }

    $arr = json_decode($line);
    foreach($arr as $key=>$val){
        if($key==$field_name && !in_array($val, $fields)){
            array_push($fields, $val);
        }
    }
}

echo my_join(",", $fields)."\n";