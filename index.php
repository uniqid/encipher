<?php
/*************************************************

Encipher - the PHP code encode tool
Author: Jacky Yu <jacky325@qq.com>
Copyright (c): 2012-2017 Jacky Yu, All rights reserved
Version: 1.1.2

* This library is free software; you can redistribute it and/or modify it.
* You may contact the author of Encipher by e-mail at: jacky325@qq.com

The latest version of Encipher can be obtained from:
https://github.com/uniqid/encipher

*************************************************/

$app = str_replace('\\', '/', dirname(__FILE__));
require_once($app . '/lib/encipher.php');

$original = $app . '/original'; //待加密的文件目录
$encoded  = $app . '/encoded';  //加密后的文件目录
$encipher = new Encipher($original, $encoded);

/**
 * 设置加密模式 false = 低级模式; true = 高级模式
 * 低级模式不使用eval函数
 * 高级模式使用了eval函数
 */
$encipher->advancedEncryption = true;

echo "<pre>\n";
$encipher->encode();
echo "</pre>\n";
