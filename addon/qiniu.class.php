<?php
require_once  __DIR__.'/qiniu/autoload.php';
use Qiniu\Auth;

/**
 * 七牛上传类
 * @author Administrator
 *使用前请添加配置
 *'QINIU'=>array(
 *		'ACCESSKEY'=>'你的ACCESSKEY',
 *		'SECRETKEY'=>'你的SECRETKEY',
 *		'BUCKET'=>'要使用的空间名称'
 *	)
 */
class qiniu{
	
	private $key=null;
	private $secret=null;
	private $bucket=null;
	private $error=null;
	public $url='';
	
	public function getError() {
		return $this->error;
	}
	function __construct(){
		$conf = C('QINIU');
		if(!$conf){
			E('请添加七牛配置');
			return false;
		}
		$this->key=$conf['ACCESSKEY'];
		$this->secret=$conf['SECRETKEY'];
		$this->bucket = $conf['BUCKET'];
		$this->url = $conf['URL'];
	}
	//上传图片
	function qnUpload($params){
		$auth = new Auth($this->key,$this->secret);
		$bucket = $this->bucket;
		$returnBody = '{"key":"$(key)","data":"$(key)","hash":"$(etag)","fsize":$(fsize),"bucket":"$(bucket)","name":"$(x:name)"}';
		$policy = array(
			'returnBody' => $returnBody
		);
		log_write($params['file'],'debugger');
		$filePath = $params['file'];
		$key = (isset($params['filename']) && $params['filename']) ? $params['filename'] : '' ;
		
		$keyToOverwrite=null;
		if($key){
			$keyToOverwrite=$key;
		}
		$expires=30;
		$token = $auth->uploadToken($bucket, $keyToOverwrite , $expires, $policy, true);
		$uploadMgr = new \Qiniu\Storage\UploadManager();
		//----------------------------------------upload demo2 ----------------------------------------
		// 上传文件到七牛
		
		list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
		if ($err !== null) {
			$this->error=$err->message();
			return false;
		} else {
			return $ret['data'];
		}
	}
	//上传网络图片
	function urlUpload($params){
		$auth = new Auth($this->key,$this->secret);
		$bucketMgr=new BucketManager($auth);
		$bucket = $this->bucket;
		$filePath = $params['file'];
		$key = (isset($params['filename']) && $params['filename']) ? $params['filename'] : '' ;
		list($ret, $err) = $bucketMgr->fetch($filePath, $bucket,$key);
		if ($err !== null) {
			$this->error=$err->message();
			return false;
		} else {
			return $ret['key'];
		}
	}
	
}
