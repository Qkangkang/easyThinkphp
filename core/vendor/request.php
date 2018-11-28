<?php
# 判断是否为https
function request_is_secure()
{
    static $static = null;
    return NULL === $static ? $static = isset($_SERVER['HTTPS']) && 'on' == $_SERVER['HTTPS'] || isset($_SERVER['SERVER_PORT']) && 443 == $_SERVER['SERVER_PORT'] : $static;
}
# 获取url前缀
function request_prefix()
{
    static $static = null;
    if (NULL === $static) {
            $static = (request_is_secure() ? 'https' : 'http') . '://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'] . (empty($_SERVER['SERVER_PORT']) || in_array($_SERVER['SERVER_PORT'], array(80, 443)) ? '' : ':' . $_SERVER['SERVER_PORT']));
    }
    return $static;
}
# 获取请求地址
function request_uri()
{
    static $static = null;
    if (NULL !== $static) {
        return $static;
    }
    // 处理requestUri
    $requestUri = '/';
    if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
        // check this first so IIS will catch
        $requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
    } elseif (isset($_SERVER['IIS_WasUrlRewritten']) && $_SERVER['IIS_WasUrlRewritten'] == '1' && isset($_SERVER['UNENCODED_URL']) && $_SERVER['UNENCODED_URL'] != '') {
        $requestUri = $_SERVER['UNENCODED_URL'];
    } elseif (isset($_SERVER['REQUEST_URI'])) {
        $requestUri = $_SERVER['REQUEST_URI'];
        if (isset($_SERVER['HTTP_HOST']) && strstr($requestUri, $_SERVER['HTTP_HOST'])) {
            $parts = @parse_url($requestUri);
            if (false !== $parts) {
                $requestUri = (empty($parts['path']) ? '' : $parts['path']) . (empty($parts['query']) ? '' : '?' . $parts['query']);
            }
        }
    } elseif (isset($_SERVER['ORIG_PATH_INFO'])) {
        // IIS 5.0, PHP as CGI
        $requestUri = $_SERVER['ORIG_PATH_INFO'];
        if (!empty($_SERVER['QUERY_STRING'])) {
            $requestUri .= '?' . $_SERVER['QUERY_STRING'];
        }
    }
    return $static = $requestUri;
}
# 获取基础目录
function request_base_url()
{
    static $static = null;
    if(IS_CLI){
        return null;
    }
    if (NULL !== $static) {
        return $static;
    }
    $filename = isset($_SERVER['SCRIPT_FILENAME']) ? basename($_SERVER['SCRIPT_FILENAME']) : '';
    if (isset($_SERVER['SCRIPT_NAME']) && basename($_SERVER['SCRIPT_NAME']) === $filename) {
        $baseUrl = $_SERVER['SCRIPT_NAME'];
    } elseif (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) === $filename) {
        $baseUrl = $_SERVER['PHP_SELF'];
    } elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $filename) {
        $baseUrl = $_SERVER['ORIG_SCRIPT_NAME'];
        // 1and1 shared hosting compatibility
    } else {
        // Backtrack up the script_filename to find the portion matching
        // php_self
        $path = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '';
        $file = isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '';
        $segs = explode('/', trim($file, '/'));
        $segs = array_reverse($segs);
        $index = 0;
        $last = count($segs);
        $baseUrl = '';
        do {
            $seg = $segs[$index];
            $baseUrl = '/' . $seg . $baseUrl;
            ++$index;
        } while ($last > $index && false !== ($pos = strpos($path, $baseUrl)) && 0 != $pos);
    }
    // Does the baseUrl have anything in common with the request_uri?
    $finalBaseUrl = NULL;
    $requestUri = request_uri();
    if (0 === strpos($requestUri, $baseUrl)) {
        // full $baseUrl matches
        $finalBaseUrl = $baseUrl;
    } else {
        if (0 === strpos($requestUri, dirname($baseUrl))) {
            // directory portion of $baseUrl matches
            $finalBaseUrl = rtrim(dirname($baseUrl), '/');
        } else {
            if (!strpos($requestUri, basename($baseUrl))) {
                // no match whatsoever; set it blank
                $finalBaseUrl = '';
            } else {
                if (strlen($requestUri) >= strlen($baseUrl) && (false !== ($pos = strpos($requestUri, $baseUrl)) && $pos !== 0)) {
                    // If using mod_rewrite or ISAPI_Rewrite strip the script filename
                    // out of baseUrl. $pos !== 0 makes sure it is not matching a value
                    // from PATH_INFO or QUERY_STRING
                    $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
                }
            }
        }
    }
    return $static = NULL === $finalBaseUrl ? rtrim($baseUrl, '/') : $finalBaseUrl;
}
# 获取当前pathinfo
function request_path_info()
{
    static $static = null;
    if (NULL !== $static) {
        return $static;
    }
    //参考Zend Framework对pahtinfo的处理, 更好的兼容性
    $pathInfo = NULL;
    $requestUri = request_uri();
    $finalBaseUrl = request_base_url();
    // Remove the query string from REQUEST_URI
    if ($pos = strpos($requestUri, '?')) {
        $requestUri = substr($requestUri, 0, $pos);
    }
    if (NULL !== $finalBaseUrl && false === ($pathInfo = substr($requestUri, strlen($finalBaseUrl)))) {
        // If substr() returns false then PATH_INFO is set to an empty string
        $pathInfo = '/';
    } elseif (NULL === $finalBaseUrl) {
        $pathInfo = $requestUri;
    }
    if (empty($pathInfo)) {
        $pathInfo = '/';
    }
    // fix issue 456
    return $static = '/' . ltrim(urldecode($pathInfo), '/');
}
# 获取当前请求url
function  request_url()
{
    if(IS_CLI){
        return null;

    }
    return request_prefix() . request_uri();
}
function request_root()
{
    static $static = null;
    if(IS_CLI){
        return null;
    }
        if (NULL !== $static) {
            return $static;
        }
        $static = rtrim(request_prefix() . request_base_url(), '/') . '/';
        $pos = strrpos($static, '.php/');
        if ($pos) {
            $static = dirname(substr($static, 0, $pos));
        }
        $static = rtrim($static, '/');

    return $static;
}