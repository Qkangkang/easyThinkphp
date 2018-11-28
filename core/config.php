<?php
return array (
		// 数据库配置信息  支持配置 主从  默认第一个是主
// 		'MYSQL'=>array(
// 				'DB_HOST' => '127.0.0.1,127.0.0.2',
// 				'DB_NAME' => 'mymvc',
// 				'DB_USER' => 'root,myuser',
// 				'DB_PASSWORD' => 'root,root',
// 				'DB_PORT' => '3306',
// 				'DB_PREFIX' => 'mymvc_',
// 				'DB_CHARSET' => 'utf8',
// 				'DB_DEBUG' => true, // 数据库调试
// 		)
		
		#  缓存配置
		'CACHE_ZIP'=>true,//缓存压缩   base64_encode(gzcompress($str, 9)); //解压方法：gzuncompress(base64_decode($cache));
		'CACHE_TYPE'=>'fileCache',//fileCache,mysqlCache,yacCache
		'FILE_CACHE'=>array(//文件 缓存
			'dir'=>'cache/'
		),
		'MYSQL_CACHE'=>array(//mysql 缓存
				'table'=>'cache'
		),
		'YAC_CACHE'=>array(//YAC 缓存
				'fix'=>'mymvc_'
		),
			
		'SHOW_TRACE' => true, //显示调试信息		
		'TRUE_DIR' => TRUE_DIR,
		
		'DEFAULT_APP' => defined ( 'DEFAULT_MODULE' ) ? DEFAULT_MODULE : 'index', // 默认 mod
		'DEFAULT_MOD' => 'index', // 默认 mod
		'DEFAULT_ACT' => 'index', // 默认act

        'AUTO_MOBILE_TPL' => true, // 默认手机检测模版  如果开启了 手机主题 建议关闭

		'DEFAULT_APP_PARAMS' => 'app', // 默认 app
		'DEFAULT_MOD_PARAMS' => 'mod', // 默认 mod
		'DEFAULT_ACT_PARAMS' => 'act', // 默认act
		'BAD_WOREDS'=>'',//脏字过滤  | 分割
		'DEBUG' => APP_DEBUG, // 调试模式 显示错误详细信息
		
		'MULTI_MODULE' => true, // 是否允许多模块 如果为false 则必须设置 DEFAULT_MODULE
		'MODULE_DENY_LIST' => array (), // 禁止访问模块列表
		'URL_HTML_SUFFIX' => '.html', // URL伪静态后缀设置 就可以了
		'URL_PATHINFO_DEPR' => '/', // PATHINFO模式下，各参数之间的分割符号
		'URL_PATHINFO_FETCH' => 'ORIG_PATH_INFO,REDIRECT_PATH_INFO,REDIRECT_URL', // 用于兼容判断PATH_INFO 参数的SERVER替代变量列表
		'URL_PHPINFO' => false, // 强制 显示index.php
		
		'URL_TYPE' => defined ( 'URL_TYPE' ) ? URL_TYPE : 1 , // 定义URL的形式 0 为普通模式 index.php?c=controller& 1为 /app/contorlle/action?query=test
		                        // 0 (普通模式); 1 (PATHINFO 模式); 2 (REWRITE 模式); 3 (兼容模式) 默认为PATHINFO 模式
		'URL_ROUTER_ON' => false, // 开启路由
		
		'UP_DIR' => '/upload/',
		'UP_DRIVE'=>'file',//上传驱动  file,qiniu
		'THEM_ON' => false, // 开启主题
		'THEM_NAME' => 'default', // 默认主题
		'VIEW_PATH'=>'',//设置模版路径 默认为模块下views目录
		
		'SESSION_PREFIX' => 'GXDYXH_SESSION_',
		'COOKIE_PREFIX' => 'GXDYXH_COOKIE_',
		
		'VAR_JSONP_HANDLER' => 'callback',
		'VAR_AJAX_SUBMIT' => 'ajax', // 默认的AJAX提交变量
		'VAR_PATHINFO' => 's', // 兼容模式PATHINFO获取变量例如 ?s=/module/action/id/1
		
		'STATIC_DIR' => 'static/',
		
		'TMPL_ACTION_EMPTY' => 'errorpage/404.php', // 成功提示页面
		'TMPL_ACTION_SUCCESS' => 'errorpage/jump.php', // 成功提示页面
		'TMPL_ACTION_ERROR' => 'errorpage/jump.php', // 错误提示页面
		'TMPL_ACTION_EXCEPTION' => 'errorpage/exception.php', // 错误提示页面
		
		'APP_VERSION' => '0.1',

        /* 错误设置 */

        'ERROR_MESSAGE' => '页面错误！请稍后再试～', // 错误显示信息,非调试模式有效
		
		'ERROR_PAGE' => '', // 错误定向页面
		
		'SHOW_ERROR_MSG' => false, // 显示错误信息
		
		'TRACE_MAX_RECORD' => 100, // 每个级别的错误信息 最大记录数
		
		/* 日志设置 */
		
		'LOG_RECORD' => false, // 默认不记录日志
		
		'LOG_TYPE' => 'File', // 日志记录类型 默认为文件方式
		/**
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
		 */
		'LOG_LEVEL' => ['E_NOTICE','E_USER_NOTICE','E_DEPRECATED'], // 允许记录的日志级别
		
		'LOG_FILE_SIZE' => 2097152, // 日志文件大小限制
		
		'LOG_EXCEPTION_RECORD' => false, // 是否记录异常信息日志
		
		'AUTO_CONFIG' => array () 
) // 自动加载配置文件
;