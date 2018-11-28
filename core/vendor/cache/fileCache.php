<?php
class fileCache{
	private $cache_dir;
	function __construct(){
		$conf=C('FILE_CACHE');
		if(!is_dir(RUNTIME_DIR)){
			throw new Exception("缓存文件夹不存在");
		}
		if(!is_writable(RUNTIME_DIR)){
			throw new Exception("缓存文件夹不可写入");
		}
		if(!is_dir(RUNTIME_DIR.$conf['dir'])){
			mkdir(RUNTIME_DIR.$conf['dir'],0777,true);
		}
		$this->cache_dir=RUNTIME_DIR.$conf['dir'];
	}
	
	function get($name){
		$file = $name.'.cache';
		// 获取缓存数据
		if (isset ( $_SERVER [$name] )) {
			if ($_SERVER [$name] ['time'] > time ()) {				
				$cachedata = $_SERVER [$name] ['data'];
				if(C('CACHE_ZIP')){
					$cachedata =gzuncompress(base64_decode($_SERVER [$name] ['data']));	
				}
				return unserialize($cachedata);
			} else {
				unset ( $_SERVER [$name] );
				unlink ( $this->cache_dir.$file );
				return false;
			}
		}
		
		if (is_file ( $this->cache_dir.$file )) {
			$value = unserialize ( file_get_contents ( $this->cache_dir.$file  ) );
			if ($value ['time'] > time ()) {
				$_SERVER [$name] = $value;
				
				$cachedata = $value['data'];
				if(C('CACHE_ZIP')){
					$cachedata =gzuncompress(base64_decode($value['data']));
				}
				return unserialize($cachedata);
				
				#return $value ['data'];
				
				
			} else {
				unset ( $_SERVER [$name] );
				unlink ( $this->cache_dir.$file );
				return false;
			}
		} else {
			$value = false;
		}
		return $value;
	}
	 function set($name,$value,$effective = 1800){
		$file = $name.'.cache';
		if (is_null ( $value )) {
			// 删除缓存
			unset ($_SERVER[$name]);
			return is_file ( $this->cache_dir.$file ) ?  unlink ( $this->cache_dir.$file ) : true ;
		} else {
			$value = serialize($value);
			$data ['data'] =serialize($value);
			if(C('CACHE_ZIP')){
				$temp = gzcompress($value, 9);
				$data ['data'] = base64_encode($temp);
			}
			//$data ['data'] =  ?　base64_encode(gzcompress($value, 9)) : $value ;
			$data ['time'] = time () + $effective;
			file_put_contents ( $this->cache_dir.$file, serialize ($data) );
			// 缓存数据
			$_SERVER [$name] = $data;
			return null;
		}
	}
	 function clear(){
		deldir($this->cache_dir,false);
	}
}
function deldir($dirname, $self = true) {
	if (!file_exists($dirname)) {
		return false;
	}
	if (is_file($dirname) || is_link($dirname)) {
		return unlink($dirname);
	}
	$dir = dir($dirname);
	if ($dir) {
		while (false !== $entry = $dir->read()) {
			if ($entry == '.' || $entry == '..') {
				continue;
			}
			deldir($dirname . '/' . $entry);
		}
	}
	$dir->close();
	$self && rmdir($dirname);
}