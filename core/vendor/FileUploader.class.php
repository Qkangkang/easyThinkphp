<?php

/*
 * Copyright 2013 pm.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * MicroPHP
 * Description of test
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		MicroPHP
 * @author		狂奔的蜗牛
 * @email		672308444@163.com
 * @copyright	        Copyright (c) 2013 - 2013, 狂奔的蜗牛, Inc.
 * @link		http://git.oschina.net/snail/microphp
 * @createdtime         2013-12-4 10:05:45
 */

/**
 * Description of FileUploader
 *
 * @author pm
 */
class FileUploader {

    private $size=2048, $ext=['png','jpg','gif','rar','zip'], $file_formfield_name = 'file',$subpath = true;
    public $error = array('code' => '', 'info' => '');
	private $filehead=[
			'text'=>'data:text/plain;base64,',
			'zip'=>'data:application/octet-stream;base64,',
			'rar'=>'data:application/octet-stream;base64',
			'7z'=>'data:application/octet-stream;base64',
			'tar'=>'data:application/octet-stream;base64',
			'gif'=>'data:image/gif;base64,',
			'jpg'=>'data:image/jpeg;base64,',
			'png'=>'data:image/png;base64,',
			'js'=>'data:application/x-javascript;base64,',
			'html'=>'data:text/html;base64,',
			'htm'=>'data:text/html;base64,',
			'xls'=>'data:application/vnd.ms-excel;base64',
			'xlsx'=>'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,',
			'doc'=>'data:application/msword;base64,',
			'docx'=>'data:application/vnd.openxmlformats-officedocument.wordprocessingml.document;base64,',
			'other'=>'data:application/octet-stream;base64,'
	];
    /**
     * 设置表单文件域name名称
     * @param type $field_name
     */
    public function setFormField($field_name) {
        $this->file_formfield_name = $field_name;
    }

    /**
     * @param 设置是否开启二级目录
     */
    public function setSubPath($open) {
        $this->subpath=$open;
    }

    /**
     * 设置文件最大大小，单位KB
     * @param type $s
     */
    public function setMaxSize($s) {
        $this->size = $s;
    }

    /**
     * 设置允许的文件拓展名列表，数组的形式，
     * 比如：array('jpg','bmp'),不区分大小写
     * @param array $e
     */
    public function setExt(Array $e) {
        $this->ext = $e;
    }

    //check file size , unit is KB
    private function checkSize() {
        $max_size = $this->size;
        $size_range = 1024 * $max_size;
        if ($_FILES[$this->file_formfield_name]['size'] > $size_range || !$_FILES[$this->file_formfield_name]['size']) {
            $this->setError(401, '文件大小错误！最大：' . ( $max_size < 1024 ? $max_size . 'KB' : sprintf('%.1f', $max_size / 1024) . 'MB'));
            return FALSE;
        }
        return TRUE;
    }

    //check file extension 
    private function checkExt() {
        $ext = $this->ext;
        foreach ($ext as &$v) {
            $v = strtolower($v);
        }
        $file_ext = strtolower(pathinfo($_FILES[$this->file_formfield_name]['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, $ext)) {
            $this->setError(402, '文件类型错误！只允许：' . implode(',', $ext));
            return FALSE;
        }
        return TRUE;
    }

    public function getError() {
        return $this->error;
    }

    public function getErrorMsg() {
        return $this->error['error'];
    }

    public function getErrorCode() {
        return $this->error['code'];
    }

    private function setError($code, $info) {
        $this->error['code'] = $code;
        $this->error['error'] = $info;
    }

    public function getFileExt() {
        return strtolower(pathinfo($_FILES[$this->file_formfield_name]['name'], PATHINFO_EXTENSION));
    }

    public function getFileRawName() {
        return strtolower(pathinfo($_FILES[$this->file_formfield_name]['name'], PATHINFO_FILENAME));
    }

    public function getTmpFilePath() {
        return $_FILES[$this->file_formfield_name]['tmp_name'];
    }

    private function truepath($path) {
        //是linux系统么？
        $unipath = PATH_SEPARATOR == ':';
        //检测一下是否是相对路径，windows下面没有:,linux下面没有/开头
        //如果是相对路径就加上当前工作目录前缀
        if (strpos($path, ':') === false && strlen($path) && $path{0} != '/') {
            $path = getcwd() . DIRECTORY_SEPARATOR . $path;
        }
        // resolve path parts (single dot, double dot and double delimiters)
        $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $absolutes = array();
        foreach ($parts as $part) {
            if ('.' == $part)
                continue;
            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        //如果是linux这里会导致linux开头的/丢失
        $path = implode(DIRECTORY_SEPARATOR, $absolutes);
        //如果是linux，修复系统前缀
        $path = $unipath ? (strlen($path) && $path{0} != '/' ? '/' . $path : $path) : $path;
        //最后统一分隔符为/，windows兼容/
        $path = str_replace(array('/', '\\'), '/', $path);
        return $path;
    }
    
    /**
     * H5 方式上传 （base64）
     * @param unknown $dir
     * @param unknown $save_name
     * @return boolean
     * 
     * 需要post方式　传递　内容字符串，ext(类型字符串，默认jpg);
     */
    public function saveFileByH5($dir = null,$save_name = null){
    	if (empty($_POST[$this->file_formfield_name])) {
    		$this->setError(404, '请先上传文件');
    		return FALSE;
    	}
    	$file_ext = strtolower(isset($_POST['ext']) ? $_POST['ext'] : 'jpg');
    	if (!in_array($file_ext,$this->ext)){
    		$this->setError(402, '文件类型错误！只允许：' . implode(',', $this->ext));
    		return FALSE;
    	}
    	
    	if (empty($save_name)) {
    		$file_ext = strtolower(pathinfo($_FILES[$this->file_formfield_name]['name'], PATHINFO_EXTENSION));
    		$save_name = md5(sha1_file($_FILES[$this->file_formfield_name]['tmp_name'])) . '.' . $file_ext;
    	}
    	
    	//设置路径
    	$file_path = '';
    	$_dir = '';
    	if (!empty($dir)) {
    		$subfix = $dir{strlen($dir) - 1};
    		$_dir = ($subfix == '/' || $subfix == "\\" ? $dir : $dir . '/');
    	}
    	if($this->subpath){
    		$_dir = $_dir.date("Ymd").'/';
    	}
    	$file_path=UPLOAD_PATH.$_dir;
    	
    	$isHasFileHead = stripos($_POST[$this->file_formfield_name],"data:")!== false ? true : false;
    	
    	if(C('UP_DRIVE')=='qiniu'){
    		$qiniu = new qiniu();
    		$filehead= isset($this->filehead[$file_ext]) ? $this->filehead[$file_ext] : $this->filehead['other'];
    		$params['file']= $isHasFileHead ?  $_POST[$this->file_formfield_name] : $filehead.$_POST[$this->file_formfield_name] ;
    		$params['filename']=$file_path.$save_name;
    		$res = $qiniu->qnUpload($params);
    		if($res!==false){
    			$data['savepath']=$qiniu->url.C('TRUE_DIR').$res;
    			$data['size']=0;
    			$data['type']=$file_ext;
    			return $data;
    		}else{
    			$this->setError(501,$qiniu->getError());
    			return FALSE;
    		}
    		 
    	}else{
    		 
    		$dir = pathinfo(ROOT_DIR.UPLOAD_PATH.$_dir . $save_name, PATHINFO_DIRNAME);
    		//设置路径 end
    		if (!is_dir($dir)) {
    			mkdir($dir, 0777, TRUE);
    		}
    		$save_file = ($dir ? $this->truepath($dir) . '/' : '' ) . $save_name;
    		$datacontent=$isHasFileHead ? substr(strstr($_POST[$this->file_formfield_name],','),1) : $_POST[$this->file_formfield_name];
    		if (file_put_contents($save_file,base64_decode($datacontent))){
    			$data['savepath']=Core::$config['TRUE_DIR'].$file_path.$save_name;
    			$data['size']=0;
    			$data['type']=$file_ext;
    			return $data;
    		} else {
    			$this->setError(501, '创建文件失败,请检查目标目录是否有写权限.');
    			return FALSE;
    		}
    		 
    	}
    	
    	
    }
    
    public function saveFile($dir = null,$save_name = null) {
    	if (empty($_FILES[$this->file_formfield_name])) {
    		$this->setError(404, '请先上传文件');
    		return FALSE;
    	}
    	$server_error = array(1 => '文件大小超过了PHP.ini中的文件限制', 2 => '文件大小超过了浏览器限制', 3 => '文件部分被上传', 4 => '没有找到要上传的文件', 5 => '服务器临时文件夹丢失', 6 => '文件写入到临时文件夹出错');
    	$error_code = $_FILES[$this->file_formfield_name]['error'];
    	if ($error_code > 0) {
    		$this->setError(500, isset($server_error[$error_code]) ? $server_error[$error_code] : '未知错误');
    		return false;
    	}
    	if (!$this->checkExt($this->ext, $this->file_formfield_name)) {
    		return FALSE;
    	}
    	if (!$this->checkSize($this->size, $this->file_formfield_name)) {
    		return FALSE;
    	}
    	$src_file = realpath($_FILES[$this->file_formfield_name]['tmp_name']);
    	if (empty($save_name)) {
    		$file_ext = strtolower(pathinfo($_FILES[$this->file_formfield_name]['name'], PATHINFO_EXTENSION));
    		$save_name = md5(sha1_file($_FILES[$this->file_formfield_name]['tmp_name'])) . '.' . $file_ext;
    	}
    	//设置路径
    	$file_path = '';
        $_dir = '';
    	if (!empty($dir)) {
    		$subfix = $dir{strlen($dir) - 1};
    		$_dir = ($subfix == '/' || $subfix == "\\" ? $dir : $dir . '/');
    	}
        if($this->subpath){
            $_dir = $_dir.date("Ymd").'/';
        }
        $file_path=UPLOAD_PATH.$_dir;
        
        
        if(C('UP_DRIVE')=='qiniu'){
        	$qiniu = new qiniu();
        	$params['file']=$src_file;
        	$params['filename']=$file_path.$save_name;
        	$res = $qiniu->qnUpload($params);
        	if($res!==false){
        		$data['savepath']=$qiniu->url.C('TRUE_DIR').$res;
        		$data['size']=number_format ($_FILES[$this->file_formfield_name]['size']/1024,2);
        		$data['type']=$this->getFileExt();
        		return $data;
        	}else{
        		$this->setError(501,$qiniu->getError());
        		return FALSE;
        	}
       
        }else{
        	
        	$dir = pathinfo(ROOT_DIR.UPLOAD_PATH.$_dir . $save_name, PATHINFO_DIRNAME);
        	//设置路径 end
        	if (!is_dir($dir)) {
        		mkdir($dir, 0777, TRUE);
        	}
        	$save_file = ($dir ? $this->truepath($dir) . '/' : '' ) . $save_name;
        	move_uploaded_file($src_file, $save_file);
        	if (file_exists($save_file)) {
        		$data['savepath']=Core::$config['TRUE_DIR'].$file_path.$save_name;
        		$data['size']=number_format ($_FILES[$this->file_formfield_name]['size']/1024,2);
        		$data['type']=$this->getFileExt();
        		return $data;
        	} else {
        		$this->setError(501, '移动临时文件到目标文件失败,请检查目标目录是否有写权限.');
        		return FALSE;
        	}
        	
        } 	
    	
    }
    

}
