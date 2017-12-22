<?php
class Decipher
{
	public $source_file = '';
	public function __construct($source_file = ''){
		!empty($source_file) && $this->source_file = $source_file;
	}

	public function replaceinvisible($str){
		for($i = 127; $i<256; $i++){
			$str = str_replace(chr($i), '_'.$i, $str);
		}
		return $str;
	}

	public function decode($str = ''){
		if(empty($str)){
			if(!is_file($this->source_file)){
				return false;
			} else {
				$str = file_get_contents($this->source_file);
			}
		}
		$str = $this->replaceVariableName($str);
		$str = $this->replaceFunctionName($str);
		$str = $this->replaceInterferenceStr($str);
		return $str;
	}
	
	public function replaceVariableName($str){
		preg_match_all('/\$[a-z_\x7f-\xff]+\s*[,|;|\(|\)|\.|\=|\[|\{]/is', $str, $matches);
		$old_var = array_unique($matches[0]); rsort($old_var);
		$new_var = array_map(array($this, 'replaceinvisible'), $old_var);
		$str = str_replace($old_var, $new_var, $str);
		return $str;
	}
	
	public function replaceFunctionName($str){
		preg_match_all('/[\x7f-\xff]+\s*[\(]/is', $str, $matches);
		$old_func = array_unique($matches[0]); rsort($old_func);
		$new_func = array_map(array($this, 'replaceinvisible'), $old_func);
		$str = str_replace($old_func, $new_func, $str);
		return $str;
	}

	public function replaceInterferenceStr($str){
		for($i = 127; $i<256; $i++){
			$str = str_replace(chr($i), '', $str);
		}
		return $str;
	}
}
