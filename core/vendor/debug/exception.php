<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>出错啦！</title>
<style type="text/css">
body,div,ul,li,h1{margin:0;padding:0}
.errorcont h1,.errorcont ul,.errorcont ul li,.errorcont ul li span,.errorcont ul table tr td{font:14px/1.6 'Microsoft YaHei',Verdana,Arial,sans-serif}
.errorcont{width:98%;margin:8px auto;overflow:hidden;color:#000;border-radius:5px;box-shadow:0 0 20px #555;background:#fff;min-width:300px}
.errorcont h1{font-size:18px;height:26px;line-height:26px;padding:10px 3px 0;border-bottom:1px solid #dbdbdb;font-weight:700}
.errorcont ul,.errorcont h1{width:98%;margin:0 auto;overflow:hidden}
.errorcont ul{list-style:none;padding:3px;word-break:break-all}
.errorcont ul li,.errorcont ul table tr td{padding:0 3px}
.errorcont ul li span{float:left;display:inline;width:70px}
.errorcont ul li.even{background:#ddd}
.errorcont .fo{border-top:1px solid #dbdbdb;padding:5px 3px 10px;color:#666;text-align:right}
</style>
</head>
<body style="background:#aaa;padding:8px 0">
<div class="errorcont">
	<h1>错误信息</h1>
	<ul>
		<li><span>消息:</span> <font color="red"><?php echo $message;?></font></li>
		<li><span>文件:</span> <?php echo $file;?></li>
		<li><span>位置:</span> 第 <?php echo $line;?> 行</li>
	</ul>

	<h1>错误位置</h1>
	<ul><?php echo debug_get_code($file, $line);?></ul>

	<h1>基本信息</h1>
	<ul>
		<li><span>模型目录:</span> <?php echo $model_path; ?></li>
		<li><span>视图目录:</span> <?php echo $view_path; ?></li>
		<li><span>控制器:</span> <?php echo $route_path; ?></li>
		<li><span>日志目录:</span> <?php echo $log_path; ?></li>
	</ul>

	<h1>程序流程</h1>
	<ul><?php echo debug_arr2str(explode("\n", $tracestr), 0);?></ul>

	<h1>SQL</h1>
	<ul><?php echo $sql;?></ul>

	<h1>$_GET</h1>
	<ul><?php echo $GET;?></ul>

	<h1>$_POST</h1>
	<ul style="white-space:pre"><?php echo $POST;?></ul>

	<h1>$_COOKIE</h1>
	<ul><?php echo $COOKIE;?></ul>

	<h1>包含文件</h1>
	<ul><?php echo $included_files;?></ul>

	<h1>其他信息</h1>
	<ul>
		<li><span>请求路径:</span> <?php echo $request_path; ?></li>
		<li><span>当前时间:</span> <?php echo $time; ?></li>
		<li><span>当前网协:</span> <?php echo $ip;?></li>
		<li><span>运行时间:</span> <?php echo $runtime;?></li>
		<li><span>内存开销:</span> <?php echo $memory;?></li>
	</ul>

	
</div>
</body>
</html>
