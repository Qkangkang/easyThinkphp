<?php

/*@log_start*/

/**
 * 写入日志
 * @param string $s 写入字符串
 * @param string $file 保存文件名
 * @return boot
 */
function log_write($info = '', $name = 'php_error')
{	

	if(!is_dir(RUNTIME_DIR)){
		throw new Exception("缓存文件夹不存在");
	}
	if(!is_writable(RUNTIME_DIR)){
		throw new Exception("缓存文件夹不可写入");
	}
	if(!is_dir(ROOT_DIR . 'runtime/log/')){
		mkdir(ROOT_DIR . 'runtime/log/',0777,true);
	}
    $path = ROOT_DIR . 'runtime/log/' . $name . '.log';
    # 检测日志文件大小，超过配置大小则备份日志文件重新生成
    if (is_file($path) && 2097152 <= filesize($path)) {
        $to = dirname($path) . DIRECTORY_SEPARATOR . date('YmdHis') . '.log';
        if (!rename($path, $to)) {
            throw new Exception("改名失败，请检查当前文件权限 {$path} > {$to}");
        }
    }
    $time = '[date：' . date('Y-m-d H:i:s') . ']';
    $ip = '[ip：' . getIp() . ']';
    $runtime = '[time：' . number_format(microtime(true) - START_TIME, 10) . ']';
    $memory_use = '[mem：' . server_memory() . ']';
    $url = '[url：' . request_uri() . ']';
    $info = "{$time} {$ip} {$runtime} {$memory_use} {$url} {$info}";
    $info = str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $info) . "\r\n" . PHP_EOL;
    if (!file_put_contents($path, $info,FILE_APPEND)) {
        throw new Exception("写入日志失败，可能文件 {$path} 不可写或磁盘已满。");
    }
    return TRUE;
}

/*@log_end*/

# 统计程序运行时间
function server_time()
{
	//return substr(microtime(1) - $_SERVER['start_time'], 0, 5);
	return number_format(microtime(1) - START_TIME, 4);
}
# 统计程序内存开销
function server_memory()
{
	return START_EME ? f_size(memory_get_usage() - START_EME) : 'unknown';
}

/**
 * 格式化价格
 * @param	mixed	$price   价格
 * @param	integer	$n	     放大，缩小倍数
 * @param	string	$return	 返回格式， 页面显示string，入库int，浮点float
 * @return  mixed
 */
function f_price($price, $n = -2, $return = 'string')
{
	$price = floatval($price) * pow(10, $n);
	if ($return == "string") {
		$price = sprintf("%.2f", round($price, 2));
	} else {
		if ($return == "int") {
			$price = intval(round($price));
		} else {
			if ($return == "float") {
				$price = round($price, 2);
			}
		}
	}
	return $price;
}
function f_number($num)
{
	$num > 100000 && ($num = ceil($num / 10000) . '万');
	return $num;
}
/**
 * 按照文件大小格式化输出
 * @return string
 */
function f_size($num)
{
	if ($num > 1073741824) {
		return number_format($num / 1073741824, 2, '.', '') . 'G';
	} elseif ($num > 1048576) {
		return number_format($num / 1048576, 2, '.', '') . 'M';
	} elseif ($num > 1024) {
		return number_format($num / 1024, 2, '.', '') . 'K';
	} else {
		return $num . 'B';
	}
}
# 转换为人性化时间
function f_time($time)
{
	$difference = $_SERVER['time'] - $time;
	switch ($difference) {
		case $difference <= '60':
			$msg = '刚刚';
			break;
		case $difference > '60' && $difference <= '3600':
			$msg = floor($difference / 60) . '分钟前';
			break;
		case $difference > '3600' && $difference <= '86400':
			$msg = floor($difference / 3600) . '小时前';
			break;
		case $difference > '86400' && $difference <= '2592000':
			$msg = floor($difference / 86400) . '天前';
			break;
		case $difference > '2592000' && $difference <= '7776000':
			$msg = floor($difference / 2592000) . '个月前';
			break;
		case $difference > '7776000':
			$msg = '很久以前';
			break;
	}
	return $msg;
}