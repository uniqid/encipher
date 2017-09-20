<?php
//decode assistant

$app = str_replace('\\', '/', dirname(__FILE__));
require_once($app . '/lib/decipher.php');

$app = str_replace('\\', '/', dirname(__FILE__));
$encoded = $app . '/encoded/'; 
$decoded = $app . '/decoded/'; 

$filename = 'phpinfo.php';

$decipher = new Decipher($encoded . $filename);
file_put_contents($decoded . $filename, $decipher->decode());
echo $decoded . $filename;

