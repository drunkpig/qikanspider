<?php
require_once "./lib/functions.php";
/**
 * Created by PhpStorm.
 * User: cxu
 * Date: 2015/8/29
 * Time: 14:20
 */

$filepath = $argv[1];
if(empty($filepath)||!file_exists($filepath)){
    echo "file not found : $filepath\n";
    exit;
}

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
        if(!in_array($key, $fields)){
            array_push($fields, $key);
        }
    }
}

echo my_join(",", $fields);