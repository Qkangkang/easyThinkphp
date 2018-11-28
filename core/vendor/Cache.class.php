<?php
class Cache{
	private $cache;
	#file,mysql,redis,yac
	static function init(){
		if (!isset($_SERVER['cacheClass'])){
			$class=C('CACHE_TYPE');
			include_once VENDOR_PATH.'cache'.DS.$class.'.php';
			$_SERVER['cacheClass']= new $class();
		}
		//$this->cache = $_SERVER['cache'];
		return  $_SERVER['cacheClass'];
	}
	static function get($key){
		$cache=self::init();
		return $cache->get($key);
	}
	static function set($key,$val,$effective = 1800){
		$cache=self::init();
		return $cache->set($key,$val,$effective);
	}
	static function clear(){
		$cache=self::init();
		return $cache->clear();
	}
}