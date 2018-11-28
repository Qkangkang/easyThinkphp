<?php
# 版本限制
version_compare(PHP_VERSION, '5.5.0', '<=') AND exit('require PHP 5.5 or newer.');
!defined('APP_DEBUG') AND exit('Access Denied.');
# 开启页面gzip压缩

define ( "GZIP_ENABLE", function_exists ( 'ob_gzhandler' ) );
ob_start ( GZIP_ENABLE ? 'ob_gzhandler' : null );
define('DS', DIRECTORY_SEPARATOR);
# 定义根目录
defined('ROOT_DIR') or define('ROOT_DIR', dirname($_SERVER['SCRIPT_FILENAME']) . DS);
defined('CORE_DIR') or define('CORE_DIR', ROOT_DIR . 'core'. DS);
defined('APP_DIR') or define('APP_DIR',ROOT_DIR.'application'. DS);
defined('CONFIG_PATH') or define('CONFIG_PATH', ROOT_DIR . 'config'. DS);
defined('RUNTIME_DIR') or define('RUNTIME_DIR', ROOT_DIR . 'runtime' . DS);
defined('VENDOR_PATH')  or define('VENDOR_PATH',  CORE_DIR.'vendor'. DS); // 第三方类库目录
defined('ADDON_PATH')   or define('ADDON_PATH',ROOT_DIR.'addon'. DS);
defined('UPLOAD_PATH')   or define('UPLOAD_PATH','upload/');

define('NOW_TIME',$_SERVER['REQUEST_TIME']);
# 字符串压缩 gzcompress($str, 9); //解压方法：gzuncompress
defined('TRUE_DIR')   or define('TRUE_DIR','/');

defined('ENCRYPT_STR') or define('ENCRYPT_STR','MYMVC');

define('START_TIME', microtime(1));
define('START_EME', memory_get_usage());

#时区
date_default_timezone_set("Asia/Shanghai");


if(isset($_SERVER['REQUEST_METHOD'])){
    define('METHOD',strtoupper($_SERVER['REQUEST_METHOD']));
    define('IS_POST','POST' == METHOD);
    define('IS_GET','GET' == METHOD);
    @ob_end_clean();
}
define('IS_CLI',PHP_SAPI=='cli'? 1   :   0);
define('IS_AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower(trim($_SERVER['HTTP_X_REQUESTED_WITH'])) == 'xmlhttprequest');


#开启session
session_start();

# 加载文件
include __DIR__ . DS.'autoload.php';

# debug初始化
debug_init();


if(IS_CLI){
    $_SERVER ['REQUEST_URI'] = isset($argv[1]) ? $argv[1] : '';
    $tempQuery_string = explode('?',$_SERVER ['REQUEST_URI']);
    $_SERVER['QUERY_STRING'] = isset($tempQuery_string[1])? $tempQuery_string[1] : '';
}

class Core{
	static $config=[];
	# 初始化
	static function init(){
		# 默认编码声明
		header('Content-type: text/html;charset=utf-8');
		# 自动加载类
		spl_autoload_register('Core::loadClass');
		# 初始配置
		self::initConf();
		# Url调度
		Route::dispatch();
	}
	#加载配置
	static function initConf(){
		//系统配置文件
		if(is_file(CORE_DIR . 'config.php')){
			$coreConfig = require CORE_DIR . 'config.php';
			self::$config =  array_merge(self::$config, $coreConfig);
		}
		
 		//应用配置
		if(is_file(CONFIG_PATH . 'config.php')){
 			$appConfig = require CONFIG_PATH . 'config.php';
 			self::$config =  array_merge(self::$config, $appConfig);
		}
		if(is_array(self::$config['AUTO_CONFIG']) && count(self::$config['AUTO_CONFIG'])>0){
			foreach (self::$config['AUTO_CONFIG'] as $tempconfigkey){
				$DbConfig=[];
				if (file_exists(CONFIG_PATH .$tempconfigkey. '.php')) {
					$DbConfig = require CONFIG_PATH .$tempconfigkey. '.php';
					self::$config =  array_merge(self::$config, $DbConfig);
				}
			}
			 
		}
	}
	#获取配置
	static function config(){
		return self::$config;
	}
	static function run(){
		self::init();
		
		self::exec();
		
		APP_DEBUG && !IS_AJAX && !IS_CLI && self::$config['SHOW_TRACE'] && debug_trace();
	}
	
	//执行 加载控制器 模型等
	private static function exec(){
	
		if(!is_dir(APP_DIR.MODULE_NAME)){
			if(APP_DEBUG){
				E('无法加载模型:'.MODULE_NAME);
			}else{
				
				if(is_file(APP_DIR.C('TMPL_ACTION_EMPTY'))){
					include APP_DIR.C('TMPL_ACTION_EMPTY');
				}else{
					include CORE_DIR.'errorpage/exception.php';
				}
			}
			exit;
		}
		 
		if(defined('BAN_MODULE') && in_array(MODULE_NAME, explode(',',BAN_MODULE))){
			if(APP_DEBUG){
				E('无法加载模型:'.MODULE_NAME,404);
			}else{
				if(is_file(APP_DIR.C('TMPL_ACTION_EMPTY'))){
					include APP_DIR.C('TMPL_ACTION_EMPTY');
				}else{
					include CORE_DIR.'errorpage/exception.php';
				}
			}
			exit;
		}
	
		$controller = CONTROLLER_NAME . 'Controller';
		
		$module  = null;
		
		if(class_exists($controller)){
			$module  =  new $controller;
		}
		 
		 
		if(!$module){
			if(APP_DEBUG){
				E('无法加载模块:'.CONTROLLER_NAME);
			}else{
				if(is_file(APP_DIR.C('TMPL_ACTION_EMPTY'))){
					include APP_DIR.C('TMPL_ACTION_EMPTY');
				}else{
					include CORE_DIR.'errorpage/exception.php';
				}
			}
			exit;
		}
		 
		if ((int)method_exists($module, ACTION_NAME)) {
			call_user_func_array(array($module, ACTION_NAME),array());
		} else {
			if ((int)method_exists($module, '_empty')) {
				call_user_func_array(array($module, '_empty'),array());
			}else{
				if(APP_DEBUG){
					E('无法加载方法:'.ACTION_NAME);
				}else{
					if(is_file(APP_DIR.C('TMPL_ACTION_EMPTY'))){
						include APP_DIR.C('TMPL_ACTION_EMPTY');
					}else{
						include CORE_DIR.'errorpage/exception.php';
					}
				}
				exit;
			}
		}
		return ;
	}
	
	#自动加载类
	public static function loadClass($class)
	{
		if(defined('MODULE_NAME')){
			$pathList[] =  APP_DIR.MODULE_NAME.DS.'controllers'.DS ;
			$pathList[] =  APP_DIR.MODULE_NAME.DS.'models'.DS  ;
		}
		$pathList[] =  CORE_DIR.'lib'.DS ;
		//公共 模型
		$pathList[] =  APP_DIR.'common'.DS.'controllers'.DS;
		$pathList[] =  APP_DIR.'common'.DS.'models'.DS;
		//拓展
		$pathList[] =  VENDOR_PATH ;
		$pathList[] =  ADDON_PATH ;
		// 根据自动加载路径设置进行尝试搜索
		foreach ( $pathList as $path){
			if(is_file($path.$class.'.class.php')){
				include $path.$class.'.class.php';
				return ;
			}
		}
	
	}
}