<?php
$_SERVER['__debug_once'] = FALSE;
/**
 * 初始化 DEDUG 操作
 */
function debug_init()
{  
	
    if (APP_DEBUG) {
        error_reporting(E_ALL | E_STRICT);
        # 程序关闭时执行
       
    } else {
        # 关闭错误输出
        error_reporting(0);
    }
    register_shutdown_function('debug_shutdown_handler');
	//function_exists('ini_set') AND ini_set('display_errors', APP_DEBUG ? '1' : '0');
	function_exists('ini_set') AND ini_set('display_errors','0');
	# 设置错误处理方法
    set_error_handler('debug_error_handler');
    # 设置异常处理方法
    set_exception_handler('debug_exception_handler');  
}
/**
 * 错误处理
 * @param string $errno 错误类型
 * @param string $errstr 错误消息
 * @param string $errfile 错误文件
 * @param int $errline 错误行号
 */
function debug_error_handler($errno, $errstr, $errfile, $errline)
{	
    if ($_SERVER['__debug_once']) {
        return;
    }
    // 兼容 php 5.3 以下版本
    defined('E_DEPRECATED') || define('E_DEPRECATED', 8192);
    defined('E_USER_DEPRECATED') || define('E_USER_DEPRECATED', 16384);
    $error_type = [
	    E_ERROR => '运行错误', 
		E_WARNING => '运行警告', 
		E_PARSE => '语法错误', 
		E_NOTICE => '运行通知', 
		E_CORE_ERROR => '初始错误', 
		E_CORE_WARNING => '初始警告', 
		E_COMPILE_ERROR => '编译错误', 
		E_COMPILE_WARNING => '编译警告', 
		E_USER_ERROR => '用户定义的错误', 
		E_USER_WARNING => '用户定义的警告', 
		E_USER_NOTICE => '用户定义的通知', 
		E_STRICT => '代码标准建议', 
		E_RECOVERABLE_ERROR => '致命错误', 
		E_DEPRECATED => '代码警告', 
		E_USER_DEPRECATED => '用户定义的代码警告'
	];
    $errno_str = isset($error_type[$errno]) ? $error_type[$errno] : '未知错误';
    $s = "[{$errno_str}] : {$errstr}";
    if (APP_DEBUG){
        throw new Exception($s);
    } else {
        // 线上模式放宽一些，只记录日志，不中断程序执行
        if (in_array($errno, C('LOG_LEVEL'))) {
            log_write($s);
        } else {
            throw new Exception($s);
        }
    }
}
/**
 * 异常处理
 * @param int $e 异常对象
 */
function debug_exception_handler($e)
{		

    # 只输出一次
    APP_DEBUG && ($_SERVER['__debug_once'] = 1);
    # 第1步正确定位
    $trace = $e->getTrace();
    if (!empty($trace) && $trace[0]['function'] == 'debug_error_handler') {
        $message = $e->getMessage();
        $file = $trace[0]['args'][2];
        $line = $trace[0]['args'][3];
    } else {
        $message = '[程序异常] : ' . $e->getMessage();
        $file = $e->getFile();
        $line = $e->getLine();
    }
    $message = debug_filter($message);
    // 第2步写日志 (不使用 error_log() )
    log_write("{$message} File: {$file} [{$line}]");
    // 第3步根据情况输出错误信息
   
    	$conf=Core::$config;
    	
        //ob_clean();
        if (IS_AJAX) {
            if (APP_DEBUG) {
                $error = "{$message} File: {$file} [{$line}]<br><br>" . str_replace("\n", '<br>', $e->getTraceAsString());
            } else {
                 $len = strlen($_SERVER['DOCUMENT_ROOT']);
                 $file = substr($file, $len);
                 $error = "{$message} File: {$file} [{$line}]";
            	if(!$conf['SHOW_ERROR_MSG']){
            		$error=$conf['ERROR_MESSAGE'];
            	}
            }
            $json['status']=0;
            $json['info']=$error;
            header('Content-Type:application/json; charset=utf-8');
            echo json_encode($json);
        } else {
            if (APP_DEBUG) {
                debug_exception($message, $file, $line, $e->getTraceAsString());
            } else {
            	
//                 $len = strlen($_SERVER['DOCUMENT_ROOT']);
//                 $file = substr($file, $len);
//                 debug_error($message, $file, $line);
            	if(!$conf['SHOW_ERROR_MSG']){
            		$message=$conf['ERROR_MESSAGE'];
            	}
            	//echo $message;
               //echo $message;
            	if(is_file(APP_DIR.C('TMPL_ACTION_EXCEPTION'))){
            		include APP_DIR.C('TMPL_ACTION_EXCEPTION');
            	}else{
            		include CORE_DIR.'errorpage/exception.php';
            	}
            	return ;
            	exit;
                
            }
        }
    
}
/**
 * 输出异常信息
 * @param string $message 异常消息
 * @param string $file 异常文件
 * @param int $line 异常行号
 * @param string $tracestr 异常追踪信息
 */
function debug_exception($message, $file, $line, $tracestr)
{
    extract(debug_extract());
    include CORE_DIR . '/vendor/debug/exception.php';
}
/**
 * 数组转换成HTML代码 (支持双行变色)
 * @param array $arr 一维数组
 * @param int $type 显示类型
 * @param boot $html 是否转换为 HTML 实体
 * @return string
 */
function debug_arr2str($arr, $type = 2, $html = TRUE)
{
    if (!is_array($arr)) {
        return;
    }
    $s = '';
    $i = 0;
    foreach ($arr as $k => $v) {
        switch ($type) {
            case 0:
                $k = '';
                break;
            case 1:
                $k = "#{$k} ";
                break;
            default:
                $k = "#{$k} => ";
        }
        $i++;
        $c = $i % 2 == 0 ? ' class="even"' : '';
        $html && is_string($v) && ($v = safe_htmlspecialchars($v));
        if (is_array($v) || is_object($v)) {
            $v = gettype($v);
        }
        $s .= "<li{$c}>{$k}{$v}</li>";
    }
    return $s;
}
/**
 * 程序关闭时执行
 */
function debug_shutdown_handler()
{	
    if (!$_SERVER['__debug_once']) {
        if ($e = error_get_last()) {
           // ob_clean();
            $message = $e['message'];
            $file = $e['file'];
            $line = $e['line'];
           
            $conf = Core::$config;
           
            if(!APP_DEBUG){
            	if(!$conf['SHOW_ERROR_MSG']){
            		$message=$conf['ERROR_MESSAGE'];
            	}
            	if (IS_AJAX) {
            		$json['status']=0;
            		$json['info']=$message;
            		header('Content-Type:application/json; charset=utf-8');
            		echo json_encode($json);
            	}else{
            		//echo $message;
            		if(is_file(APP_DIR.C('TMPL_ACTION_EXCEPTION'))){
            			include APP_DIR.C('TMPL_ACTION_EXCEPTION');
            		}else{
            			include CORE_DIR.'errorpage/exception.php';
            		}
            		return ;
            		exit;
            	}
            	
            }else{
	            if (IS_AJAX) {
	                if (!APP_DEBUG) {
	                    $len = strlen($_SERVER['DOCUMENT_ROOT']);
	                    $file = substr($file, $len);
	                }
	                
	                $error = "[致命错误] : {$message} File: {$file} [{$line}]";
	                $json['status']=0;
            		$json['info']=$error;
            		header('Content-Type:application/json; charset=utf-8');
            		echo json_encode($json);
            		
	            } else {
	            	
	                debug_error('[致命错误] : ' . $message, $file, $line);
	            }
             }
        }
    }
}
function debug_extract()
{
    return [
	    'runtime' => server_time(), 
		'model_path' => defined('CONTROLLER_NAME') ? CONTROLLER_NAME : '--',
		'view_path' => isset($_SERVER['VIEW_TPL']) ? $_SERVER['VIEW_TPL'] : '--', 
		'route_path' => defined('ACTION_NAME') ? ACTION_NAME : '--',
		'log_path' => 'runtime/log', 
		'ip' => getIp(), 
		'memory' => server_memory(), 
		'time' => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']), 
		'request_path' => request_base_url(), 
		'request_url' => request_url(), 
		//'sql' => Sql::getSql(), 
    	'sql' => debug_arr2str(Sql::getSql(), 1, FALSE),
		'GET' => debug_arr2str($_GET), 
		'POST' => $_POST ? debug_arr2str(print_r(safe_htmlspecialchars($_POST), 1)) : '', 
		'COOKIE' => debug_arr2str($_COOKIE), 
		'included_files' => debug_arr2str(get_included_files(), 1)
	];
}
/**
 * 输出系统错误
 * @param string $message 错误消息
 * @param string $file 错误文件
 * @param int $line 错误行号
 */
function debug_error($message, $file, $line)
{	
	
    extract(debug_extract());
    include CORE_DIR . '/vendor/debug/error.php';
   
}
/**
 * 获取错误定位代码
 * @param string $file 错误文件
 * @param int $line 错误行号
 * @return array
 */
function debug_get_code($file, $line)
{
    $arr = file($file);
    $arr2 = array_slice($arr, max(0, $line - 5), 10, true);
    $s = '<table cellspacing="0" width="100%">';
    foreach ($arr2 as $i => &$v) {
        $i++;
        $v = safe_htmlspecialchars($v);
        $v = str_replace(' ', '&nbsp;', $v);
        $v = str_replace('	', '&nbsp;&nbsp;&nbsp;&nbsp;', $v);
        $s .= '<tr' . ($i == $line ? ' style="background:#faa;"' : '') . '><td width="40">#' . $i . "</td><td>{$v}</td>";
    }
    $s .= '</table>';
    return $s;
}
/**
 * 过滤消息内容
 * @param string $s 消息内容
 * @return string
 */
function debug_filter($s)
{
    $s = strip_tags($s);
    if (strpos($s, 'mysql_connect') !== false) {
        $s .= ' [连接数据库出错！请检查配置信息]';
    }
    return $s;
}
/**
 * 输出追踪信息
 */
function debug_trace()
{
    extract(debug_extract());
    include CORE_DIR . '/vendor/debug/trace.php';
}

# 递归转换为HTML实体代码
function safe_htmlspecialchars($var)
{
	if (is_array($var)) {
		foreach ($var as $k => $v) {
			safe_htmlspecialchars($v);
		}
	} else {
		$var = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $var);
	}
	return $var;
}