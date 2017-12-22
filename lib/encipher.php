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

class Encipher
{
    /**
     * The file/path which you want to encode.
     */
    public $source_file = '';

    /**
     * The file/path which you want to save the encoded file/path.
     */
    public $encoded_file = '';
    
    /**
     * Default comments.
     */
    public $comments = array(
        'Author:Jacky Yu',
        'Email: jacky325@qq.com'
    );
    
    /**
     * advanced encryption
     */
    public $advancedEncryption = false;
    
    /**
     * variable name length.
     */
    public $varnameLength = 8;

    public function __construct($source_file = '', $encoded_file = '', $comments = array()){
        !empty($source_file) && $this->source_file = $source_file;
        !empty($encoded_file) && $this->encoded_file = $encoded_file;
        !empty($comments)    && $this->comments    = (array)$comments;

        if(empty($this->source_file) || !file_exists($this->source_file)){
            exit("Source file/path does not exist.");
        }
        if(empty($this->encoded_file) || !file_exists($this->encoded_file)){
            exit("Encoded file/path does not exist.");
        }
    }

    public function encode(){
        list($paths, $files) = $this->_getPathsAndFiles($this->source_file);
        $this->_createPaths($paths);
        $this->_encryptFiles($files);
    }

    private function _getPathsAndFiles($dir){
        $files = $paths = array();
        if(is_dir($dir)){
            $_files = scandir($dir);
            foreach($_files as $key => $file){
                if($file == '.' || $file == '..'){
                    continue;
                }
                if(is_dir($dir ."/". $file)){
                    $paths[]  = $file;
                    list($subPaths, $subFiles) = $this->_getPathsAndFiles($dir ."/". $file);
                    $subPaths = $this->_addBasePath($subPaths, $file);
                    $subFiles = $this->_addBasePath($subFiles, $file);
                    $paths = array_merge($paths, $subPaths);
                    $files = array_merge($files, $subFiles);
                }
                else{
                    $files[] = $file;
                }
            }
        }
        elseif(is_file($dir)){
            $files[] = basename($dir);
        }
        return array($paths, $files);
    }

    private function _addBasePath($files, $base){
        foreach($files as $key => $file){
            $files[$key] = $base . "/" . $file;
        }
        return $files;
    }

    private function _createPaths($paths){
        foreach($paths as $path){
            !is_dir($this->encoded_file . "/" . $path) && mkdir($this->encoded_file . "/" . $path, 0700);
        }
    }

    private function _encryptFiles($files){
        foreach($files as $file){
            if($this->advancedEncryption){
                $this->_encryptFile($file);
            }else{
                $this->_setHumanUnreadable($file);
            }
        }
    }
    
    private function _setHumanUnreadable($file){
        $code = $this->_getPHPCode($file);
        $regVars = $this->_setVarName($this->_getMatchedVariables($code));
        list($usedFuncs, $funcChars) = $this->_getMatchedFunctions($code);
        if(!empty($usedFuncs)){
            $_tmp = $this->_setVarName(array('funcStrVar' => ''), $regVars);
            $funcStrVar = $_tmp['funcStrVar'];
            $usedFuncMaps = $this->_setVarName($usedFuncs, $regVars);
            $regVars = array_merge($usedFuncMaps, $regVars);
        } else {
            $usedFuncMaps = array();
        }
        $funcVarDefCode = $this->_getFuncVarDefCode($usedFuncMaps, $funcChars, $funcStrVar);
        $headers = array_map('trim', array_merge(array('<?php', '/*'), $this->comments, array('*/')));
        $enCode  = implode("\r\n", $headers) . "\r\n" . $funcVarDefCode . strtr($code, $regVars);
        $this->_saveEncryptFile($file, $enCode);
    }

    private function _encryptFile($file){
        list($enkey, $dekey)    = $this->_getKeyPairs();
        $baseCodeOfHostedCode   = $this->_getBaseCodeOfHostedCode();
        $decodeCodeOfHostedCode = $this->_getDecodeCodeOfHostedCode($file, $enkey, $dekey);
        $hostedCode = $baseCodeOfHostedCode . $decodeCodeOfHostedCode;

        $regVars = $this->_setVarName($this->_getMatchedVariables($hostedCode));
        list($usedFuncs, $funcChars) = $this->_getMatchedFunctions($hostedCode);
        if(!empty($usedFuncs)){
            $_tmp = $this->_setVarName(array('funcStrVar' => ''), $regVars);
            $funcStrVar = $_tmp['funcStrVar'];
            $usedFuncMaps = $this->_setVarName($usedFuncs, $regVars);
            $regVars = array_merge($usedFuncMaps, $regVars);
        } else {
            $usedFuncMaps = array();
        }

        //$prefixCode: define function name & base extra code.
        $funcVarDefCode = $this->_getFuncVarDefCode($usedFuncMaps, $funcChars, $funcStrVar);
        $prefixCode = preg_replace("/\r|\n|\s+/is", "", $funcVarDefCode. strtr($baseCodeOfHostedCode, $regVars));

        $headers = array_map('trim', array_merge(array('<?php', '/*'), $this->comments, array('*/')));
        $hookKey = strtr(md5(implode("\r\n", $headers) . "\r\n" . $prefixCode), $enkey, $dekey);
        $evalEmbedCode = $this->_getEvalEmbedCode($decodeCodeOfHostedCode, $regVars, $enkey, $dekey);
        /**
         * eval(base64_decode(
         *     str_replace("\$hookKey", '', strtr($hookKey.$evalEmbedCode, $dekey, $enkey))
         * ));
         * $unset;
         */
        $unset = 'unset('.$funcStrVar;
        foreach($regVars as $var){
            $unset .= ','.$var;
        }
        $unset .= ');';
        $evalCode = "@eval(".$regVars["base64_decode"]."(".$regVars["str_replace"]."(".$regVars["\$hookKey"].",'',".$regVars["strtr"]."('".$hookKey.$evalEmbedCode."','".$dekey."','".$enkey."'))));".$unset;
        $originalEncodedCode = $this->_getPHPEncode($file, $enkey, $dekey);
        $enCode = implode("\r\n", $headers) . "\r\n" . $prefixCode . $evalCode . "return;?>\r\n" . $originalEncodedCode;
        $this->_saveEncryptFile($file, $enCode, $enkey, $dekey);
    }
    /**
     * The encoded code needs extra code
     */
    private function _getBaseCodeOfHostedCode(){
        $code = <<<EOT
            \$farrs   = file(str_replace('\\\\', '/', __FILE__));
            \$enCode  = array_pop(\$farrs);
            \$phpCode = array_pop(\$farrs);
            \$fstrs   = implode('', \$farrs) . substr(\$phpCode, 0, strrpos(\$phpCode, '@ev'));
            \$hookKey = md5(\$fstrs);
            \$farrs   = \$phpCode = \$fstrs = NULL;
EOT;
        return $code;
    }
    
    /**
     * The encoded code needs decode code
     * if the licence is generated, also need to process it. 
     */
    private function _getDecodeCodeOfHostedCode($file, $enkey, $dekey){
        $code = <<<EOT
            eval(base64_decode(strtr(\$enCode, '{$dekey}', '{$enkey}')));
            \$enCode = NULL;
EOT;
        return $code;
    }
    
    private function _getFuncVarDefCode($usedFuncMaps, $funcChars, $funcStrVar){
        //all the chars of function name
        $funcStr = implode("", $funcChars);
        
        //set variable name's value for each variable of function name
        $funcVarValArr = $this->_getFuncVarvalArr($usedFuncMaps, $funcChars, $funcStrVar);
        
        //encoded code define function name string.
        $code = $funcStrVar."='{$funcStr}';";
        foreach($usedFuncMaps as $func => $val){
            $code .= $val."= ".$funcVarValArr[$func].";\n";
        }
        return $code;
    }
    
    private function _getEvalEmbedCode($decodeCodeOfHostedCode, $regVars, $enkey, $dekey){
        $code = preg_replace("/\r|\n/is", "", strtr($decodeCodeOfHostedCode, $regVars));
        //replace multi space to one, and encode it via base64
        $code = base64_encode(preg_replace("/\s{2,}/is"," ",$code));
        $code = strtr($code, $enkey, $dekey);
        return $code;
    }
    
    /**
     * get function names and chars for all functions
     */
    private function _getMatchedFunctions($code){
        //match all function name
        preg_match_all("/([a-z_0-9]+)\(/is", $code, $matches);
        $usedFuncs = array_unique($matches[1]);
        if(false !== ($key = array_search('eval', $usedFuncs))){
            unset($usedFuncs[$key]);
        }

        $funcChars = array_unique(preg_split("//is", implode("", $usedFuncs), -1, PREG_SPLIT_NO_EMPTY));
        shuffle($funcChars);
        return array(array_flip($usedFuncs), $funcChars);
    }
    
    /**
     * get variable names
     */
    private function _getMatchedVariables($code){
        preg_match_all("/(\\\$[a-z0-9]+)\s*\=/is", $code, $matches);
        return array_flip($matches[1]);
    }
    
    private function _getFuncVarvalArr($usedFuncMaps, $funcChars, $funcStrVar){
        $funcVarValArr = array();
        foreach($usedFuncMaps as $func => $_val){
            $val = "";
            for($i=0, $len=strlen($func); $i<$len; $i++){
                if($val==""){
                    $val = $funcStrVar."{".array_search($func{$i}, $funcChars)."}";
                } else {
                    $val = $val . "." .$funcStrVar."{".array_search($func{$i}, $funcChars)."}";
                }
            }
            $funcVarValArr[$func] = $val;
        }
        return $funcVarValArr;
    }
    
    /**
     * get php pure code, trim php tag
     */
    private function _getPHPCode($file){
        $from = $this->source_file . '/' . $file;
        $str = file_get_contents($from);
        $str = preg_replace("/^[\s\xef\xbb\xbf]*<\?php/is", "", $str);
        $str = trim(preg_replace("/\?>\s*$/is", "", $str));
        return $str;
    }
    
    /**
     * get php encoded code
     */
    private function _getPHPEncode($file, $enkey, $dekey){
        $code   = $this->_getPHPCode($file);
        $enCode = strtr(base64_encode($code), $enkey, $dekey);
        return $enCode;
    }

    private function _getKeyPairs(){
        $enkey = $this->_getKeyStr();
        $dekey = $this->_getKeyStr();
        while($enkey === $dekey){
            $dekey = $this->_getKeyStr();
        }
        return array($enkey, $dekey);
    }

    private function _getKeyStr() {
        $base64str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
        for($i=127; $i <= 160; $i++){
            $base64str .= chr($i);
        }
        $baseChars = array_filter(preg_split("//is", $base64str));
        $baseChars[] = 0;
        shuffle($baseChars);
        return implode("", $baseChars);
    }

    private function _setVarName($funcs, $filter = array()) {
        $length  = $this->varnameLength;
        $basestr = $this->_getInvisibleStr($length);
        $count   = count($funcs);
        if($count == 0){
            return array();
        }
        $varArr = array();
        do{
            $randStr  = substr("\$" . str_shuffle($basestr), 0, rand(2, $length));
            if(!in_array($randStr, $varArr) && !in_array($randStr, $filter)){
                $varArr[] = $randStr;
                $count--;
            }
        }while($count>0);
        return array_combine(array_keys($funcs), $varArr);
    }
    
    /**
     * legal variable names: '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*'
     * Invisiable string's ascii is from 127 to 255:'[\x7f-\xff][\x7f-\xff]*'
     * param $length  the variable name's length.
     */
    private function _getInvisibleStr($length = 10){
        $str = '';
        for($i=0; $i < $length; $i++){
            $num  = rand(127, 255);
            $str .= chr($num);
        }
        return $str;
    }
   
    private function _saveEncryptFile($file, $enCode, $enkey = null, $dekey = null){
        $to = $this->encoded_file . '/' . $file;
        file_put_contents($to, $enCode);
        echo $to . "\n";
    }
}
?>