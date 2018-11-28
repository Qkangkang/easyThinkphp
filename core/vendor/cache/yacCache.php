<?php
class yacCache{
	private $yac;
	function __construct(){
		$conf=C('YAC_CACHE');
		if(!class_exists('Yac')){
    		E('还未安装yac拓展。');
    	}
		$this->yac=new Yac($conf['fix']);
	}
	
	function get($name){
		
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
				$this->yac->delete($name);
				return false;
			}
		}
		$value = $this->yac->get($name);
		if ($value) {
			$value = unserialize ($value);
			
			if ($value ['time'] > time ()) {
				$_SERVER [$name] = $value;
				$cachedata = $value['data'];
				if(C('CACHE_ZIP')){
					$cachedata =gzuncompress(base64_decode($value['data']));
				}
				unset($value);
				return unserialize($cachedata);
				
				#return $value ['data'];
				
				
			} else {
				unset ( $_SERVER [$name] );
				$this->yac->delete($name);
				return false;
			}
		} else {
			$value = false;
		}
		return $value;
	}
	 function set($name,$value,$effective = 1800){
		if (is_null ( $value )) {
			// 删除缓存
			unset ( $_SERVER[$name] );
			return $this->yac->delete($name);
		} else {
			$value = serialize($value);
			$data ['data'] =serialize($value);
			if(C('CACHE_ZIP')){
				$temp = gzcompress($value, 9);
				$data ['data'] = base64_encode($temp);
			}
			//$data ['data'] =  ?　base64_encode(gzcompress($value, 9)) : $value ;
			$data ['time'] = time () + $effective;
			$this->yac->set($name,serialize ($data));
			$this->yac->delete($name,$effective);
			// 缓存数据
			$_SERVER [$name] = $data;
			return null;
		}
	}
	 function clear(){
		return $this->yac->flush();
	}
}