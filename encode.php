<?php
/**
 * Created by IntelliJ IDEA.
 * User: huajun102
 * Date: 2019/6/26
 * Time: 15:47
 */
$app = str_replace('\\', '/', dirname(__FILE__));
require_once($app . '/lib/encipher.php');

$param_arr = getopt('o:e:');
//print_r($param_arr);
$original = $app . '/original'; //待加密的文件目录
$encoded  = $app . '/encoded/'.$param_arr['e'];  //加密后的文件目录
if(!file_exists($encoded)){
    mkdir($encoded,755);
}
$encipher = new Encipher($param_arr['o'], $encoded);

/**
 * 设置加密模式 false = 低级模式; true = 高级模式
 * 低级模式不使用eval函数
 * 高级模式使用了eval函数
 */
$encipher->advancedEncryption = true;

echo "<pre>\n";
$encipher->encode();
echo "</pre>\n";