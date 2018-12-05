<?php

/**

 * 获取和设置配置参数 支持批量定义

 * @param string|array $name 配置变量

 * @param mixed $value 配置值

 * @param mixed $default 默认值

 * @return mixed

 */

function C($name=null, $value=null,$default=null) {

     $_config = Core::$config;

    // 无参数时获取所有

    if (empty($name)) {

        return $_config;

    }

    // 优先执行设置获取或赋值

    if (is_string($name)) {

            $name = strtoupper($name);

            if (is_null($value))

                return isset($_config[$name]) ? $_config[$name] : $default;

            Core::$config[$name] = $value;

            return null;

    }

    return null; // 避免非法参数

}
/**

 * 抛出异常处理

 * @param string $msg 异常消息

 * @param integer $code 异常代码 默认为0

 * @throws Think\Exception

 * @return void

 */

function E($msg, $code=0) {

    throw new Exception($msg,$code);

}
/**
 * * URL组装 支持不同URL模式 格式：'[模块/控制器/操作#锚点]?参数1=值1&参数2=值2...'
 *
 * @param string $url
 * @param string $vars
 */
function U($url = '', $vars = '') {
	if(strpos($url, 'http')!==false){
		return $url;
	}
	$info = parse_url ( $url );
	$url = ! empty ( $info ['path'] ) ? $info ['path'] : ACTION_NAME;
	
	if (isset ( $info ['fragment'] )) { // 解析锚点
		
		$anchor = $info ['fragment'];
		
		if (false !== strpos ( $anchor, '?' )) { // 解析参数
			
			list ( $anchor, $info ['query'] ) = explode ( '?', $anchor, 2 );
		}
		
		if (false !== strpos ( $anchor, '@' )) { // 解析域名
			
			list ( $anchor, $host ) = explode ( '@', $anchor, 2 );
		}
	}
	
	// 解析参数
	
	if (is_string ( $vars )) { // aaa=1&bbb=2 转换成数组
		parse_str ( $vars, $vars );
	} elseif (! is_array ( $vars )) {
		
		$vars = array ();
	}
	
	if (isset ( $info ['query'] )) { // 解析地址里面参数 合并到vars
		parse_str ( $info ['query'], $params );
		$vars = array_merge ( $params, $vars );
	}
	
	$var = array ();
	$_config = C();
	$depr = $_config["URL_PATHINFO_DEPR"]; // PATHINFO模式下，各参数之间的分割符号
	
	$groupName = MODULE_NAME;
	$controllerName = CONTROLLER_NAME;
	$actionName = ACTION_NAME;
	
	$groupName_default = $_config ['DEFAULT_APP'];
	$controllerName_default = $_config ['DEFAULT_MOD'];
	$actionName_default = $_config ['DEFAULT_ACT'];
	
	$app_parms = $_config ['DEFAULT_APP_PARAMS'];
	$mod_parms = $_config ['DEFAULT_MOD_PARAMS'];
	$act_parms = $_config ['DEFAULT_ACT_PARAMS'];
	
	$route = false;
	
	if ($url) {
		if (0 === strpos ( $url, '/' )) { // 定义路由
			$route = true;
			$url = substr($url, 1). $_config ['URL_HTML_SUFFIX'];
		} else {
			// 解析模块、控制器和操作
			$url = trim ( $url, $depr );
			$path = explode ( $depr, $url );
			
			$var [$act_parms] = ! empty ( $path ) ? array_pop ( $path ) : $actionName;
			
			if (! empty ( $path )) {
				$var [$mod_parms] = array_pop ( $path );
			} else {
				if ($controllerName != $controllerName_default) {
					$var [$mod_parms] = $controllerName;
				}
			}
			
			if (! empty ( $path )) {
				$var [$app_parms] = array_pop ( $path );
			} else {
				if ($groupName != $groupName_default) {
					$var [$app_parms] = $groupName;
				}
			}
		}
	}
	if ($_config ['URL_TYPE'] == 0) {
		if (! $route) { // 路由地址
			$url = _PHP_FILE_ . "?" . http_build_query ( array_reverse ( $var ) );
		}
		if (! empty ( $vars )) {
			$vars = http_build_query ( $vars );
			$url .= '&' . $vars;
		}
	} else {
		
			if (! $route) { // 路由地址
				$url = implode ( $depr, array_reverse ( $var ) ) . $_config ['URL_HTML_SUFFIX'];
			}
			
			
			if (! empty ( $vars )) { // 添加参数
				// foreach ($vars as $var => $val){
				// if('' !== trim($val)) $url .= $depr . $var . $depr . urlencode($val);
				// }
				$vars = http_build_query ( $vars );
				$url .= '?' . $vars;
			}
			
			if(Route::markUrl($url)!==false){
				$url = Route::markUrl($url);
			}
			
			if ($_config ['URL_PHPINFO']) {
				$url = _PHP_FILE_ .'/'. $url;
			}
			
	}
	
	
	
	if (isset ( $anchor )) {
		
		$url .= '#' . $anchor;
	}
	
	return TRUE_DIR.$url;
}
function I($name, $default = '', $filter = 'addslashes') {
	if (strpos ( $name, '.' )) { // 指定参数来源
		list ( $method, $name ) = explode ( '.', $name, 2 );
	} else { // 默认为自动判断
		$method = 'param';
	}
	switch (strtolower ( $method )) {
		case 'get' :
			$input = & $_GET;
			break;
		case 'post' :
			$input = & $_POST;
			break;
		case 'put' :
			if (is_null ( $_PUT )) {
				parse_str ( file_get_contents ( 'php://input' ), $_PUT );
			}
			$input = $_PUT;
			break;
		case 'param' :
// 			switch ($_SERVER ['REQUEST_METHOD']) {
// 				case 'POST' :
// 					$input = $_POST;
// 					break;
// 				case 'PUT' :
// 					if (is_null ( $_PUT )) {
// 						parse_str ( file_get_contents ( 'php://input' ), $_PUT );
// 					}
// 					$input = $_PUT;
// 					break;
// 				default :
// 					$input = $_GET;
// 			}
			$input = array_merge($_GET,$_POST);
			break;
		case 'request' :
			$input = & $_REQUEST;
			break;
		case 'session' :
			$input = & $_SESSION;
			break;
		case 'cookie' :
			$input = & $_COOKIE;
			break;
		case 'server' :
			$input = & $_SERVER;
			break;
		case 'globals' :
			$input = & $GLOBALS;
			break;
		case 'data' :
			$input = & $datas;
			break;
		default :
			return null;
	}
	
	if ('' == $name) { // 获取全部变量
		$data = $input;
		$filters = isset ( $filter ) ? $filter : 'htmlspecialchars';
		if ($filters) {
			if (is_string ( $filters )) {
				$filters = explode ( ',', $filters );
			}
			foreach ( $filters as $filter ) {
				$data = array_map_recursive ( $filter, $data ); // 参数过滤
			}
		}
	} elseif (isset ( $input [$name] )) {
		
		// 取值操作
		$data = $input [$name];
		
		$filters = isset ( $filter ) ? $filter : 'htmlspecialchars';
	
		if ($filters) {
			
			if (is_string ( $filters )) {
				if (0 === strpos ( $filters, '/' )) {
					if (1 !== preg_match ( $filters, ( string ) $data )) {
						// 支持正则验证
						return isset ( $default ) ? $default : null;
					}
				} else {
					$filters = explode ( ',', $filters );
				}
			} elseif (is_int ( $filters )) {
				$filters = array (
						$filters 
				);
			}
			
			if (is_array ( $filters )) {
				foreach ( $filters as $filter ) {
					if (function_exists ( $filter )) {
						$data = is_array ( $data ) ? array_map_recursive ( $filter, $data ) : $filter ( $data ); // 参数过滤
					} else {
						$data = filter_var ( $data, is_int ( $filter ) ? $filter : filter_id ( $filter ) );
						if (false === $data) {
							return isset ( $default ) ? $default : null;
						}
					}
				}
			}
			
		}
	} else { // 变量默认值
		$data = isset ( $default ) ? $default : null;
	}
	
	is_array ( $data ) && array_walk_recursive ( $data, 'my_filter' );
	return $data;
}

/**
 * 快速文件数据读取和保存 针对简单类型数据 字符串、数组
 *
 * @param string $name
 *        	缓存名称
 * @param mixed $value
 *        	缓存值
 * @param string $path
 *        	缓存路径
 * @return mixed
 */
function F($name, $value = '', $effective = 1800) {
	if($name===null){
		return Cache::clear();
	}
	
	if($value === ''){
		return Cache::get($name);
	}else{
		return Cache::set($name,$value,$effective);
	}
	
// 	static $_cache = array ();
// 	$filename = $path . md5 ( $name ) . '.php';
// 	if (! is_dir ( $path )) {
// 		mkdir ( $path, '0777', true );
// 	}
// 	if ('' !== $value) {
// 		if (is_null ( $value )) {
// 			// 删除缓存
// 			unset ( $_cache [$name] );
// 			return unlink ( $filename );
// 		} else {
// 			$data ['data'] = $value;
// 			$data ['time'] = time () + $effective;
// 			file_put_contents ( $filename, serialize ( $data ) );
// 			// 缓存数据
// 			$_cache [$name] = $data;
// 			return null;
// 		}
// 	}
	
// 	// 获取缓存数据
// 	if (isset ( $_cache [$name] )) {
// 		if ($_cache [$name] ['time'] > time ()) {
// 			return $_cache [$name] ['data'];
// 		} else {
// 			unset ( $_cache [$name] );
// 			unlink ( $filename );
// 			return false;
// 		}
// 	}
	
// 	if (is_file ( $filename )) {
// 		$value = unserialize ( file_get_contents ( $filename ) );
// 		if ($value ['time'] > time ()) {
// 			$_cache [$name] = $value;
// 			return $value ['data'];
// 		} else {
// 			unset ( $_cache [$name] );
// 			unlink ( $filename );
// 			return false;
// 		}
// 	} else {
// 		$value = false;
// 	}
// 	return $value;
}

/**
 * 全过滤输入
 * $value = clean($_POST['value']);
 *
 * @param unknown $value        	
 */
function clean($value) {
	if (! is_array ( $value )) {
		$value = trim ( $value );
		$value = strip_tags ( $value );
		if (get_magic_quotes_gpc ()) {
			$value = stripslashes ( $value );
		}
		$value = addslashes ( $value );
	}
	return $value;
}

/**
 * * 截取中文字符串
 */
function msubstr($str, $length, $start = 0, $charset = "utf-8", $suffix = true) {
	if (function_exists ( "mb_substr" )) {
		$slice = mb_substr ( $str, $start, $length, $charset );
	} elseif (function_exists ( 'iconv_substr' )) {
		$slice = iconv_substr ( $str, $start, $length, $charset );
	} else {
		$re ['utf-8'] = "/[x01-x7f]|[xc2-xdf][x80-xbf]|[xe0-xef][x80-xbf]{2}|[xf0-xff][x80-xbf]{3}/";
		$re ['gb2312'] = "/[x01-x7f]|[xb0-xf7][xa0-xfe]/";
		$re ['gbk'] = "/[x01-x7f]|[x81-xfe][x40-xfe]/";
		$re ['big5'] = "/[x01-x7f]|[x81-xfe]([x40-x7e]|xa1-xfe])/";
		preg_match_all ( $re [$charset], $str, $match );
		$slice = join ( "", array_slice ( $match [0], $start, $length ) );
	}
	$fix = '';
	if (strlen ( $slice ) < strlen ( $str )) {
		$fix = '...';
	}
	return $suffix ? $slice . $fix : $slice;
}


/**
 * 生成随机字符串
 *
 * @param unknown $length        	
 */
function createRandomStr($length,$str='') {
	if($str==''){
		$str = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; // 62个字符
	}
	$strlen = 62;
	while ( $length > $strlen ) {
		$str .= $str;
		$strlen += 62;
	}
	$str = str_shuffle ( $str );
	return substr ( $str, 0, $length );
}
////获取访客操作系统
function getOs(){
	if(!empty($_SERVER['HTTP_USER_AGENT'])){
		$OS = $_SERVER['HTTP_USER_AGENT'];
		if (preg_match('/win/i',$OS)) {
			$OS = 'Windows';
		}elseif (preg_match('/mac/i',$OS)) {
			$OS = 'MAC';
		}elseif (preg_match('/linux/i',$OS)) {
			$OS = 'Linux';
		}elseif (preg_match('/unix/i',$OS)) {
			$OS = 'Unix';
		}elseif (preg_match('/bsd/i',$OS)) {
			$OS = 'BSD';
		}else {
			$OS = 'Other';
		}
		return $OS;
	}else{return "unknown";}
}
/**
 * 获取浏览器
 * @return unknown|mixed
 */
function getBrowser(){
	if(!empty($_SERVER['HTTP_USER_AGENT'])){
		$br = $_SERVER['HTTP_USER_AGENT'];
		if (preg_match('/MSIE/i',$br)) {
			$br = 'MSIE';
		}elseif (preg_match('/Firefox/i',$br)) {
			$br = 'Firefox';
		}elseif (preg_match('/Chrome/i',$br)) {
			$br = 'Chrome';
		}elseif (preg_match('/Safari/i',$br)) {
			$br = 'Safari';
		}elseif (preg_match('/Opera/i',$br)) {
			$br = 'Opera';
		}else {
			$br = 'Other';
		}
		return $br;
	}else{return "unknown";}
}
/**
 * 获取IP
 * @return unknown|mixed
 */
function getIp() {
	$unknown = 'unknown';
    $ip = $unknown;
	if (isset ( $_SERVER ['HTTP_X_FORWARDED_FOR'] ) && $_SERVER ['HTTP_X_FORWARDED_FOR'] && strcasecmp ( $_SERVER ['HTTP_X_FORWARDED_FOR'], $unknown )) {
		@$ip = $_SERVER ['HTTP_X_FORWARDED_FOR'];
	} elseif (isset ( $_SERVER ['REMOTE_ADDR'] ) && $_SERVER ['REMOTE_ADDR'] && strcasecmp ( $_SERVER ['REMOTE_ADDR'], $unknown )) {
		@$ip = $_SERVER ['REMOTE_ADDR'];
	}
	/*
	 * 处理多层代理的情况
	 * 或者使用正则方式：$ip = preg_match("/[d.]
	 * {7,15}/", $ip, $matches) ? $matches[0] : $unknown;
	 */
	if (false !== strpos ( $ip, ',' ))
		$ip = reset ( explode ( ',', $ip ) );
	return $ip;
}
/**
 * 当前网址
 */ 
function getNowUrl() {
	$pageURL = 'http';
	
	if (isset($_SERVER ["HTTPS"]) &&  $_SERVER ["HTTPS"] == "on") {
		$pageURL .= "s";
	}
	$pageURL .= "://";
	
	if ($_SERVER ["SERVER_PORT"] != "80") {
		$pageURL .= $_SERVER ["SERVER_NAME"] . ":" . $_SERVER ["SERVER_PORT"] . $_SERVER ["REQUEST_URI"];
	} else {
		$pageURL .= $_SERVER ["SERVER_NAME"] . $_SERVER ["REQUEST_URI"];
	}
	return $pageURL;
}

/**
 * 数据签名认证
 * @param unknown $data
 * @return string
 */
function data_auth_sign($data) {
	// 数据类型检测
	if (! is_array ( $data )) {
		$data = ( array ) $data;
	}
	ksort ( $data ); // 排序
	$code = http_build_query ( $data ); // url编码并生成query字符串
	$sign = sha1 ( $code ); // 生成签名
	return $sign;
}

/**
 * 摘自 discuz
 *
 * $string 明文或密文
 *
 * $operation 加密ENCODE或解密DECODE
 *
 * $key 密钥
 *
 * $expiry 密钥有效期 ， 默认是一直有效
 */

if (! function_exists ( "auth_code" )) {
	function auth_code($string, $operation = 'DECODE', $key = '', $expiry = 0) 

	{
		
		/*
		 *
		 * 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙
		 *
		 * 加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度。
		 *
		 * 取值越大，密文变动规律越大，密文变化 = 16 的 $ckey_length 次方
		 *
		 * 当此值为 0 时，则不产生随机密钥
		 *
		 */
		$ckey_length = 4;
		
		$key = md5 ( $key != '' ? $key : ENCRYPT_STR ); // 此处的key可以自己进行定义，写到配置文件也可以
		
		$keya = md5 ( substr ( $key, 0, 16 ) );
		
		$keyb = md5 ( substr ( $key, 16, 16 ) );
		
		$keyc = $ckey_length ? ($operation == 'DECODE' ? substr ( $string, 0, $ckey_length ) : substr ( md5 ( microtime () ), - $ckey_length )) : '';
		
		$cryptkey = $keya . md5 ( $keya . $keyc );
		
		$key_length = strlen ( $cryptkey );
		
		// 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，解密时会通过这个密匙验证数据完整性
		
		// 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确
		
		$string = $operation == 'DECODE' ? base64_decode ( substr ( str_replace ( array (
				
				'-',
				
				'_' 
		), array (
				
				'+',
				
				'/' 
		), $string ), $ckey_length ) ) : sprintf ( '%010d', $expiry ? $expiry + time () : 0 ) . substr ( md5 ( $string . $keyb ), 0, 16 ) . $string;
		
		$string_length = strlen ( $string );
		
		$result = '';
		
		$box = range ( 0, 255 );
		
		$rndkey = array ();
		
		for($i = 0; $i <= 255; $i ++) {
			
			$rndkey [$i] = ord ( $cryptkey [$i % $key_length] );
		}
		
		for($j = $i = 0; $i < 256; $i ++) {
			
			$j = ($j + $box [$i] + $rndkey [$i]) % 256;
			
			$tmp = $box [$i];
			
			$box [$i] = $box [$j];
			
			$box [$j] = $tmp;
		}
		
		for($a = $j = $i = 0; $i < $string_length; $i ++) {
			
			$a = ($a + 1) % 256;
			
			$j = ($j + $box [$a]) % 256;
			
			$tmp = $box [$a];
			
			$box [$a] = $box [$j];
			
			$box [$j] = $tmp;
			
			$result .= chr ( ord ( $string [$i] ) ^ ($box [($box [$a] + $box [$j]) % 256]) );
		}
		
		if ($operation == 'DECODE') {
			
			if ((substr ( $result, 0, 10 ) == 0 || substr ( $result, 0, 10 ) - time () > 0) && substr ( $result, 10, 16 ) == substr ( md5 ( substr ( $result, 26 ) . $keyb ), 0, 16 )) {
				
				return substr ( $result, 26 );
			} else {
				
				return '';
			}
		} else {
			
			// 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因
			
			// 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码
			
			return $keyc . str_replace ( array (
					
					'+',
					
					'/',
					
					'=' 
			), array (
					
					'-',
					
					'_',
					
					'' 
			), base64_encode ( $result ) );
		}
	}
}
/**
 * 判断是否是微信浏览
 */ 
if (! function_exists ( "is_weixin" )) {
	function is_weixin() {
		if (strpos ( $_SERVER ['HTTP_USER_AGENT'], 

		'MicroMessenger' ) !== false) {
			
			return true;
		}
		
		return false;
	}
}
/**
 * 判断是否是手机端浏览
 */
if (! function_exists ( "is_mobile" )) {
	function is_mobile() 

	{
		$_SERVER ['ALL_HTTP'] = isset ( $_SERVER ['ALL_HTTP'] ) ? $_SERVER ['ALL_HTTP'] : '';
		
		$mobile_browser = '0';
		
		if(!isset($_SERVER ['HTTP_USER_AGENT'])){
			return false;
		}
		
		if (preg_match ( '/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', strtolower ( $_SERVER ['HTTP_USER_AGENT'] ) ))
			
			$mobile_browser ++;
		
		if ((isset ( $_SERVER ['HTTP_ACCEPT'] )) and (strpos ( strtolower ( $_SERVER ['HTTP_ACCEPT'] ), 'application/vnd.wap.xhtml+xml' ) !== false))
			
			$mobile_browser ++;
		
		if (isset ( $_SERVER ['HTTP_X_WAP_PROFILE'] ))
			
			$mobile_browser ++;
		
		if (isset ( $_SERVER ['HTTP_PROFILE'] ))
			
			$mobile_browser ++;
		
		$mobile_ua = strtolower ( substr ( $_SERVER ['HTTP_USER_AGENT'], 0, 4 ) );
		
		$mobile_agents = array (
				
				'w3c ',
				'acs-',
				'alav',
				'alca',
				'amoi',
				'audi',
				'avan',
				'benq',
				'bird',
				'blac',
				
				'blaz',
				'brew',
				'cell',
				'cldc',
				'cmd-',
				'dang',
				'doco',
				'eric',
				'hipt',
				'inno',
				
				'ipaq',
				'java',
				'jigs',
				'kddi',
				'keji',
				'leno',
				'lg-c',
				'lg-d',
				'lg-g',
				'lge-',
				
				'maui',
				'maxo',
				'midp',
				'mits',
				'mmef',
				'mobi',
				'mot-',
				'moto',
				'mwbp',
				'nec-',
				
				'newt',
				'noki',
				'oper',
				'palm',
				'pana',
				'pant',
				'phil',
				'play',
				'port',
				'prox',
				
				'qwap',
				'sage',
				'sams',
				'sany',
				'sch-',
				'sec-',
				'send',
				'seri',
				'sgh-',
				'shar',
				
				'sie-',
				'siem',
				'smal',
				'smar',
				'sony',
				'sph-',
				'symb',
				't-mo',
				'teli',
				'tim-',
				
				'tosh',
				'tsm-',
				'upg1',
				'upsi',
				'vk-v',
				'voda',
				'wap-',
				'wapa',
				'wapi',
				'wapp',
				
				'wapr',
				'webc',
				'winw',
				'winw',
				'xda',
				'xda-' 
		);
		
		if (in_array ( $mobile_ua, $mobile_agents ))
			
			$mobile_browser ++;
		
		if (strpos ( strtolower ( $_SERVER ['ALL_HTTP'] ), 'operamini' ) !== false)
			
			$mobile_browser ++;
			
			// Pre-final check to reset everything if the user is on Windows
		
		if (strpos ( strtolower ( $_SERVER ['HTTP_USER_AGENT'] ), 'windows' ) !== false)
			
			$mobile_browser = 0;
			
			// But WP7 is also Windows, with a slightly different characteristic
		
		if (strpos ( strtolower ( $_SERVER ['HTTP_USER_AGENT'] ), 'windows phone' ) !== false)
			
			$mobile_browser ++;
		
		if ($mobile_browser > 0) {
			
			return true;
		} else {
			
			return false;
		}
	}
}
// 检查密码格式 6-16位 字母数字._组合
if (! function_exists ( "checkPasswd" )) {
	function checkPasswd($passwd) 

	{
		if (preg_match ( "/^([a-zA-Z0-9._]){6,16}$/", $passwd )) {
			
			return true;
		}
		
		return false;
	}
}
//检查用户名
if (! function_exists ( "checkUsername" )) {
	function checkUsername($username)
	{
		if (preg_match ( "/^([a-zA-Z0-9_]){5,16}$/", $username )) {
				
			return true;
		}

		return false;
	}
}
// 检查手机号码格式
if (! function_exists ( "checkMobile" )) {
	function checkMobile($mobile) 

	{
		$search = "/^1[3-8][0-9]{9}$/i";
		if (preg_match ( $search, $mobile )) {
			return true;
		}
		return false;
	}
}

// 验证邮箱是否合法
if (! function_exists ( "checkMail" )) {
	function checkMail($mail) {
		$pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
		if (preg_match ( $pattern, $mail )) {
			return true;
		} else {
			return false;
		}
	}
}
// 检测是否含有脏话
if (! function_exists ( "checkBadWords" )) {
    function checkBadWords($str) {
        $badstr =  C('BAD_WOREDS');
        if(!$badstr){
            return false;
        }
        $badarr = explode('|',$badstr);
        for ($i=0;$i<count($badarr);$i++){
            $content = substr_count($str, $badarr[$i]);
            if($content>0){
               return true;
                break;
            }
        }
        return false;
    }
}
// 验证 身份证号位数 是否正确
function checkCardId($cardid) {
	if (preg_match ( "/\d{17}[\d|X]|\d{15}/", $cardid )) {
		return true;
	} else {
		return false;
	}
}
// 加密
function str_encrypt($str = '', $key = '') {
	// return str_rot13(base64_encode(str_rot13(base64_encode($str))));
	return base64_encode ( str_rot13 ( base64_encode ( $str . $key ) ) );
}
// 解密
function str_decrypt($str = '', $key = '') {
	// return str_rot13(base64_decode(str_rot13(base64_decode($str))));
	return base64_decode ( str_rot13 ( base64_decode ( $str . $key ) ) );
}
function my_filter(&$value) {
	// TODO 其他安全过滤
	
	// 过滤查询特殊字符
	if (preg_match ( '/^(EXP|NEQ|GT|EGT|LT|ELT|OR|XOR|LIKE|NOTLIKE|NOT BETWEEN|NOTBETWEEN|BETWEEN|NOTIN|NOT IN|IN)$/i', $value )) {
		$value .= ' ';
	}
}
function array_map_recursive($filter, $data) {
	$result = array ();
	foreach ( $data as $key => $val ) {
		$result [$key] = is_array ( $val ) ? array_map_recursive ( $filter, $val ) : call_user_func ( $filter, $val );
	}
	return $result;
}
// 不区分大小写的in_array实现
function in_array_case($value,$array){
    return in_array(strtolower($value),array_map('strtolower',$array));
}
// 设置session
function session($name = '', $value = '') {
	$prefix = Core::$config ['SESSION_PREFIX'];
	if (! $prefix) {
		$prefix = '';
	}
	if ('' === $value) {
		
		if ('' === $name) {
			
			// 获取全部的session
			
			return $_SESSION;
		} elseif (0 === strpos ( $name, '[' )) { // session 操作
			
			if ('[pause]' == $name) { // 暂停session
				
				session_write_close ();
			} elseif ('[start]' == $name) { // 启动session
				
				session_start ();
			} elseif ('[destroy]' == $name) { // 销毁session
				
				$_SESSION = array ();
				
				session_unset ();
				
				session_destroy ();
			} elseif ('[regenerate]' == $name) { // 重新生成id
				
				session_regenerate_id ();
			}
		} elseif (0 === strpos ( $name, '?' )) { // 检查session
			$name = substr ( $name, 1 );
			return $prefix ? isset ( $_SESSION [$prefix . $name] ) : isset ( $_SESSION [$name] );
		} elseif (is_null ( $name )) { // 清空session
			$_SESSION = array ();
		} else {
			return isset ( $_SESSION [$prefix . $name] ) ? $_SESSION [$prefix . $name] : null;
		}
	} elseif (is_null ( $value )) { // 删除session
		unset ( $_SESSION [$prefix . $name] );
	} else { // 设置session
		$_SESSION [$prefix . $name] = $value;
	}
	return null;
}
//跳转
function redirect($url, $delay = '', $msg = '') {
	header ( "Location:" . $url );
	// 确保重定向后，后续代码不会被执行
	exit ();
}
//静态文件路径
function static_url($url) {
	if (APP_DEBUG) {
		$ven = NOW_TIME;
	} else {
		$ven = Core::$config ['APP_VERSION'];
	}
	$f =   strpos($url,'?') ? '&' : '?';
	echo TRUE_DIR.Core::$config ['STATIC_DIR'] . $url . $f."v=" . $ven;
}

// view里引用其它文件 public/foot.php
function viewInclude($file) {
		$config = C();
		if(!empty($config['VIEW_PATH'])){
        	$tmpPath=APP_PATH.$config['VIEW_PATH'].'/';
        }else{
        	$tmpPath=APP_DIR .MODULE_NAME.'/views/';
        }
		if($config['THEM_ON']==true && !empty($config['THEM_NAME'])){
			$tmpPath=$tmpPath.$config['THEM_NAME'].'/';
		}
	return  $tmpPath.$file;
}

function makeQrCodeByUrl($url){
    //二维码信息，用urlencode编码
    $data = urlencode($url);
    //生成二维码尺寸
    $size = '300x300';
    //完整的API地址
    $qrurl = "http://chart.googleapis.com/chart?chs=$size&cht=qr&chl=$data&chld=L|1&choe=UTF-8";
    //获取二维码
    $qrcode = file_get_contents($qrurl);
    //输出图片
    header('Content-type: image/png');
    return $qrcode;
}

function deleteBlank($str){

    return php_strip_whitespace($str);
}


// 图片转64位编码
function base64EncodeImage($image_file) {
	$base64_image = '';
	$image_info = getimagesize ( $image_file );
	$image_data = fread ( fopen ( $image_file, 'r' ), filesize ( $image_file ) );
	$base64_image = 'data:' . $image_info ['mime'] . ';base64,' . chunk_split ( base64_encode ( $image_data ) );
	return $base64_image;
}
// 500错误
if (! function_exists ( 'trigger500' )) {
	function trigger500($msg = '<h1>Server Error</h1>') {
		$system = Core::config ();
		if (! headers_sent ()) {
			header ( 'HTTP/1.1 500 Server Error' );
		}
		if (! empty ( $system ['error_page_50x'] ) && file_exists ( $system ['error_page_50x'] )) {
			include $system ['error_page_50x'];
		} else {
			echo $msg;
		}
		exit ();
	}
}
// 404错误
if (! function_exists ( 'trigger404' )) {
	function trigger404($msg = '<h1>Not Found</h1>') {
		$system = Core::config ();
		if (! headers_sent ()) {
			header ( 'HTTP/1.1 404 NotFound' );
		}
		if (! empty ( $system ['error_page_404'] ) && file_exists ( $system ['error_page_404'] )) {
			include $system ['error_page_404'];
		} else {
			echo $msg;
		}
		exit ();
	}
}



if (! function_exists ( 'truepath' )) {
	/**
	 * This function is to replace PHP's extremely buggy realpath().
	 *
	 * @param
	 *        	string The original path, can be relative etc.
	 * @return string The resolved path, it might not exist.
	 */
	function truepath($path) {
		// 是linux系统么？
		$unipath = PATH_SEPARATOR == ':';
		// 检测一下是否是相对路径，windows下面没有:,linux下面没有/开头
		// 如果是相对路径就加上当前工作目录前缀
		if (strpos ( $path, ':' ) === false && strlen ( $path ) && $path {0} != '/') {
			$path = realpath ( '.' ) . DIRECTORY_SEPARATOR . $path;
		}
		$path = str_replace ( array (
				'/',
				'\\' 
		), DIRECTORY_SEPARATOR, $path );
		$parts = array_filter ( explode ( DIRECTORY_SEPARATOR, $path ), 'strlen' );
		$absolutes = array ();
		foreach ( $parts as $part ) {
			if ('.' == $part)
				continue;
			if ('..' == $part) {
				array_pop ( $absolutes );
			} else {
				$absolutes [] = $part;
			}
		}
		// 如果是linux这里会导致linux开头的/丢失
		$path = implode ( DIRECTORY_SEPARATOR, $absolutes );
		// 如果是linux，修复系统前缀
		$path = $unipath ? (strlen ( $path ) && $path {0} != '/' ? '/' . $path : $path) : $path;
		// 最后统一分隔符为/，windows兼容/
		$path = str_replace ( array (
				'/',
				'\\' 
		), '/', $path );
		return $path;
	}
}
if (! function_exists ( 'makeConfigFile' )) {
	function makeConfigFile($data,$filename){
		if(!is_array($data)){
			return false;
		}
		if(!is_writable(CONFIG_PATH)){
    		exit('Config文件夹不存在或不可写');
    	}
		$configfile = CONFIG_PATH.strtolower($filename);
		$time = date("Y-m-d H:i" , time());
		$fp = fopen($configfile,'w');
		flock($fp,3);
		fwrite($fp,"<"."?php\r\n");
		fwrite($fp,"/*系统的基本信息配置*/\r\n");
		fwrite($fp,"/*author MyMvc*/\r\n");
		fwrite($fp,"/*time {$time}*/\r\n");
		$string = "return  ";
		$string.= var_export($data , true ) ;
		fwrite($fp,"$string;\r\n");
	}
}
/**
 * 使用CURL方式发送GET请求
 * @param  $url     [请求地址]
 * @param  $data    [array格式数据]
 * @return $请求返回结果(array)
 */
function getDataCurl($url,$head=''){

    $timeout = 5000;
    $http_header = array(
        'Content-Type:application/x-www-form-urlencoded;charset=utf-8'
    );
    if(is_array($head)){
        array_merge($http_header, $head);
    }
    //print_r($http_header);


    $ch = curl_init();
    curl_setopt ($ch, CURLOPT_URL, $url);
    curl_setopt ($ch, CURLOPT_HEADER, false );
    curl_setopt ($ch, CURLOPT_HTTPHEADER,$http_header);
    curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER,false); //处理http证书问题
    curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);

    $result = curl_exec($ch);
    if (false === $result) {
        //$result =  curl_errno($ch);
        return array('status'=>false,'data'=>curl_errno($ch));
    }
    curl_close($ch);
    return array('status'=>true,'data'=>$result);
}
/**
 * 使用CURL方式发送post请求
 * @param  $url     [请求地址]
 * @param  $data    [array格式数据]
 * @return $请求返回结果(array)
 */
 function postDataCurl($url,$data,$head=''){
	 
	$timeout = 5000;
	$http_header = array(
		'Content-Type:application/x-www-form-urlencoded;charset=utf-8'
	);
	if(is_array($head)){
		array_merge($http_header, $head);
	}
	//print_r($http_header);

	// $postdata = '';
	$postdataArray = array();
	foreach ($data as $key=>$value){
		array_push($postdataArray, $key.'='.urlencode($value));
		// $postdata.= ($key.'='.urlencode($value).'&');
	}
	$postdata = join('&', $postdataArray);

	$ch = curl_init();
	curl_setopt ($ch, CURLOPT_URL, $url);
	curl_setopt ($ch, CURLOPT_POST, 1);
	curl_setopt ($ch, CURLOPT_POSTFIELDS, $postdata);
	curl_setopt ($ch, CURLOPT_HEADER, false );
	curl_setopt ($ch, CURLOPT_HTTPHEADER,$http_header);
	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER,false); //处理http证书问题
	curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);

	$result = curl_exec($ch);
	if (false === $result) {
		//$result =  curl_errno($ch);
		return array('status'=>false,'data'=>curl_errno($ch));
	}
	curl_close($ch);
	return array('status'=>true,'data'=>$result);
}

/**
 * 使用CURL方式发送post请求（JSON类型）
 * @param  $url 	[请求地址]
 * @param  $data    [array格式数据]
 * @return $请求返回结果(array)
 */
 function postJsonDataCurl($url,$data,$head=''){
		
	$timeout = 5000;
	$http_header = array(
			'Content-Type:application/json;charset=utf-8'
	);
	if(is_array($head)){
		array_merge($http_header, $head);
	}
	//print_r($http_header);
	$postdata = json_encode($data);

	$ch = curl_init();
	curl_setopt ($ch, CURLOPT_URL, $url);
	curl_setopt ($ch, CURLOPT_POST, 1);
	curl_setopt ($ch, CURLOPT_POSTFIELDS, $postdata);
	curl_setopt ($ch, CURLOPT_HEADER, false );
	curl_setopt ($ch, CURLOPT_HTTPHEADER,$http_header);
	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER,false); //处理http证书问题
	curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);

	$result = curl_exec($ch);
	if (false === $result) {
		//$result =  curl_errno($ch);
		return array('status'=>false,'data'=>curl_errno($ch));
	}
	curl_close($ch);
	return array('status'=>true,'data'=>$result);
}
/**
 *  **  in ('.implode(',', array_map('change_to_quotes', $arr )).')
 *  in ('123','123','123')
 * @param unknown $v
 * @return string
 */

function change_to_quotes($v){
	return "'".$v."'";
}

/*
 * ============================== 截取含有 html标签的字符串 =========================
 * @param (string) $str   待截取字符串
 * @param (int)  $lenth  截取长度
 * @param (string) $repalce 超出的内容用$repalce替换之（该参数可以为带有html标签的字符串）
 * @param (string) $anchor 截取锚点，如果截取过程中遇到这个标记锚点就截至该锚点处
 * @return (string) $result 返回值
 * @demo  $res = cut_html_str($str, 256, '...'); //截取256个长度，其余部分用'...'替换
 * -------------------------------------------------------------------------------
 * $ Author: Wang Jian.  |   Email: wj@yurendu.com   |   Date: 2014/03/16
 * ===============================================================================
 */
function cut_html_str($str, $lenth, $replace='', $anchor='<!-- break -->'){
	$_lenth = mb_strlen($str, "utf-8"); // 统计字符串长度（中、英文都算一个字符）
	if($_lenth <= $lenth){
		return $str;    // 传入的字符串长度小于截取长度，原样返回
	}
	$strlen_var = strlen($str);     // 统计字符串长度（UTF8编码下-中文算3个字符，英文算一个字符）
	if(strpos($str, '<') === false){
		return mb_substr($str, 0, $lenth);  // 不包含 html 标签 ，直接截取
	}
	if($e = strpos($str, $anchor)){
		return mb_substr($str, 0, $e);  // 包含截断标志，优先
	}
	$html_tag = 0;  // html 代码标记
	$result = '';   // 摘要字符串
	$html_array = array('left' => array(), 'right' => array()); //记录截取后字符串内出现的 html 标签，开始=>left,结束=>right
	/*
	 * 如字符串为：<h3><p><b>a</b></h3>，假设p未闭合，数组则为：array('left'=>array('h3','p','b'), 'right'=>'b','h3');
	 * 仅补全 html 标签，<? <% 等其它语言标记，会产生不可预知结果
	 */
	for($i = 0; $i < $strlen_var; ++$i) {
		if(!$lenth) break;  // 遍历完之后跳出
		$current_var = substr($str, $i, 1); // 当前字符
		if($current_var == '<'){ // html 代码开始
			$html_tag = 1;
			$html_array_str = '';
		}else if($html_tag == 1){ // 一段 html 代码结束
			if($current_var == '>'){
				$html_array_str = trim($html_array_str); //去除首尾空格，如 <br / > < img src="" / > 等可能出现首尾空格
				if(substr($html_array_str, -1) != '/'){ //判断最后一个字符是否为 /，若是，则标签已闭合，不记录
					// 判断第一个字符是否 /，若是，则放在 right 单元
					$f = substr($html_array_str, 0, 1);
					if($f == '/'){
						$html_array['right'][] = str_replace('/', '', $html_array_str); // 去掉 '/'
					}else if($f != '?'){ // 若是?，则为 PHP 代码，跳过
						// 若有半角空格，以空格分割，第一个单元为 html 标签。如：<h2 class="a"> <p class="a">
						if(strpos($html_array_str, ' ') !== false){
							// 分割成2个单元，可能有多个空格，如：<h2 class="" id="">
							$html_array['left'][] = strtolower(current(explode(' ', $html_array_str, 2)));
						}else{
							//若没有空格，整个字符串为 html 标签，如：<b> <p> 等，统一转换为小写
							$html_array['left'][] = strtolower($html_array_str);
						}
					}
				}
				$html_array_str = ''; // 字符串重置
				$html_tag = 0;
			}else{
				$html_array_str .= $current_var; //将< >之间的字符组成一个字符串,用于提取 html 标签
			}
		}else{
			--$lenth; // 非 html 代码才记数
		}
		$ord_var_c = ord($str{$i});
		switch (true) {
			case (($ord_var_c & 0xE0) == 0xC0): // 2 字节
				$result .= substr($str, $i, 2);
				$i += 1; break;
			case (($ord_var_c & 0xF0) == 0xE0): // 3 字节
				$result .= substr($str, $i, 3);
				$i += 2; break;
			case (($ord_var_c & 0xF8) == 0xF0): // 4 字节
				$result .= substr($str, $i, 4);
				$i += 3; break;
			case (($ord_var_c & 0xFC) == 0xF8): // 5 字节
				$result .= substr($str, $i, 5);
				$i += 4; break;
			case (($ord_var_c & 0xFE) == 0xFC): // 6 字节
				$result .= substr($str, $i, 6);
				$i += 5; break;
			default: // 1 字节
				$result .= $current_var;
		}
	}
	if($html_array['left']){ //比对左右 html 标签，不足则补全
		$html_array['left'] = array_reverse($html_array['left']); //翻转left数组，补充的顺序应与 html 出现的顺序相反
		foreach($html_array['left'] as $index => $tag){
			$key = array_search($tag, $html_array['right']); // 判断该标签是否出现在 right 中
			if($key !== false){ // 出现，从 right 中删除该单元
				unset($html_array['right'][$key]);
			}else{ // 没有出现，需要补全
				$result .= '</'.$tag.'>';
			}
		}
	}
	return $result.$replace;
}
//压缩文件
function compress($buffer) {
	/* remove comments */
	$buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
	/* remove tabs, spaces, newlines, etc. */
	$buffer = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $buffer);
	return $buffer;
}
/**
 * 合并加載 css文件
 * @param unknown $url
 * @param string $type
 */
function static_css($url,$type='in'){
	if(!$url){
		return false;
	}
	$files = explode(',', $url);
	$static_dir = C('STATIC_DIR');
	$outstr = '';
	if($type=='in'){
		$outstr .='<style>';
		foreach ($files as $file){
			if(is_file(ROOT_DIR.C('STATIC_DIR').$file)){
				$outstr .=compress(file_get_contents(ROOT_DIR.$static_dir.$file));
			};
		}
		$outstr .='</style>';
	}else{
		foreach ($files as $file){
			$outstr .='<link rel="stylesheet" type="text/css" href="/'.$static_dir.$file.'">';
		}	
	}
	echo $outstr;
}
/**
 * 合并加載 js文件
 * @param unknown $url
 * @param string $type
 */
function static_js($url,$type='in'){
	if(!$url){
		return false;
	}
	$files = explode(',', $url);
	$static_dir = C('STATIC_DIR');
	$outstr = '';
	if($type=='in'){
		$outstr .='<script>';
		foreach ($files as $file){
			if(is_file(ROOT_DIR.$static_dir.$file)){
				$outstr .=compress(file_get_contents(ROOT_DIR.$static_dir.$file));
			};
		}
		$outstr .='</script>';
	}else{
		foreach ($files as $file){
			$outstr .='<script type="text/javascript" src="/'.$static_dir.$file.'"></script>';
		}
	}
	echo $outstr;
}