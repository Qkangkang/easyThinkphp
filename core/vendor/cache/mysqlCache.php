<?php
class mysqlCache extends Model{
	function __construct(){
		$conf=C('MYSQL_CACHE');
		parent::__construct($conf['table']);
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
				unlink ( $this->cache_dir.$file );
				return false;
			}
		}
		
		$value = $this->where(array('key'=>$name))->find();
		if ($value) {		
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
				$this->where(array('key'=>$name))->delete();
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
			return $this->where(array('key'=>$name))->delete();
		} else {
			$value = serialize($value);
			$data ['data'] =serialize($value);
			if(C('CACHE_ZIP')){
				$temp = gzcompress($value, 9);
				$data ['data'] = base64_encode($temp);
			}
			//$data ['data'] =  ?　base64_encode(gzcompress($value, 9)) : $value ;
			$data ['time'] = time () + $effective;
			// 缓存数据
			$_SERVER [$name] = $data;
			$data['key']=$name;
			//判断是否存在			
			$this->replace($data);	
			return null;
		}
	}
	 function clear(){
		$this->where('1=1')->delete();
	}
}
