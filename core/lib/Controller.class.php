<?php 

/**
 * 控制器基类
 */
class Controller
{	
	protected $_group;
    protected $_controller;
    protected $_action;
    protected $_view;
    protected $_config=array();
 
    // 构造函数，初始化属性，并实例化对应模型
    public function __construct()
    {	
    	$this->_group = MODULE_NAME;
        $this->_controller = CONTROLLER_NAME;
        $this->_action = ACTION_NAME;
        
       
        //模块配置文件
        $groupConfigFile = APP_DIR.'/'.$this->_group.'/common/config.php';
        if (file_exists($groupConfigFile)) {
        	 $groupConfig = require $groupConfigFile;
        	 Core::$config =  array_merge(Core::$config, $groupConfig);
        }
         //模块函数文件
         $groupFunctionFile = APP_DIR.'/'.$this->_group.'/common/function.php';
         if (file_exists($groupFunctionFile)) {
         	include $groupFunctionFile;
         }
         $this->_config=Core::$config;
         $this->_view = new View(MODULE_NAME,CONTROLLER_NAME,ACTION_NAME,Core::$config);
        
    }

    // 分配变量
    public function assign($name, $value='')
    {	
        $this->_view->assign($name, $value);
    }

    // 渲染视图
    public function display($path='')
    {	
        $this->_view->render($path);
    }

    // 404 错误
    public function _empty($path='public:404')
    {
        $this->_view->render($path);
        exit();
    }
    
    /**
     * 操作错误跳转的快捷方法
     * @access protected
     * @param string $message 错误信息
     * @param string $jumpUrl 页面跳转地址
     * @param mixed $ajax 是否为Ajax方式 当数字时指定跳转时间
     * @return void
     */
   
    protected function err($message='',$jumpUrl='',$data='',$ajax=false) {
    	$this->dispatchJump($message,0,$jumpUrl,$data,$ajax);
    }
    
    /**
     * 操作成功跳转的快捷方法
     * @access protected
     * @param string $message 提示信息
     * @param string $jumpUrl 页面跳转地址
     * @param mixed $ajax 是否为Ajax方式 当数字时指定跳转时间
     * @return void
     */
    protected function succ($message='',$jumpUrl='',$data='',$ajax=false) {
    	$this->dispatchJump($message,1,$jumpUrl,$data,$ajax);
    }
    
    /**
     * Ajax方式返回数据到客户端
     * @access protected
     * @param mixed $data 要返回的数据
     * @param String $type AJAX返回数据格式
     * @param int $json_option 传递给json_encode的option参数
     * @return void
     */
    protected function ajaxReturn($data,$type='',$json_option=0) {
    	if(empty($type)) $type  =   'JSON';
    	switch (strtoupper($type)){
    		case 'XML'  :
    			// 返回xml格式数据
    			header('Content-Type:text/xml; charset=utf-8');
    			exit(xml_encode($data));
    		case 'JSONP':
    			// 返回JSON数据格式到客户端 包含状态信息
    			header('Content-Type:application/json; charset=utf-8');
    			$handler  =   $_GET['callback'];
    			exit($handler.'('.json_encode($data,$json_option).');');
    		case 'EVAL' :
    			// 返回可执行的js脚本
    			header('Content-Type:text/html; charset=utf-8');
    			exit($data);
    		default     :
    			// 用于扩展其他返回格式数据
    			header('Content-Type:application/json; charset=utf-8');
    			exit(json_encode($data,$json_option));
    	}
    }
    /**
     * json返回
     * @param int $status	返回状态
     * @param string $info 提示文字
     * @param mixed $data 要返回的数据
     * @param int $json_option 传递给json_encode的option参数
     */
    protected function jsonReturn($status,$info='',$data='',$json_option=0){
    	$json['status']=$status;
    	$json['info']=$info;
    	$json['data']=$data;
    	header('Content-Type:application/json; charset=utf-8');
    	@exit(json_encode($json,$json_option));
    }
    
    
    /**
     * Action跳转(URL重定向） 支持指定模块和延时跳转
     * @access protected
     * @param string $url 跳转的URL表达式
     * @param array $params 其它URL参数
     * @param integer $delay 延时跳转的时间 单位为秒
     * @param string $msg 跳转提示信息
     * @return void
     */
    protected function redirect($url,$params=array(),$delay=0,$msg='') {
    	$url    =   U($url,$params);
    	redirect($url,$delay,$msg);
    }
    
    /**
     * 默认跳转操作 支持错误导向和正确跳转
     * 调用模板显示 默认为public目录下面的success页面
     * 提示页面为可配置 支持模板标签
     * @param string $message 提示信息
     * @param Boolean $status 状态
     * @param string $jumpUrl 页面跳转地址
     * @param mixed $ajax 是否为Ajax方式 当数字时指定跳转时间
     * @access private
     * @return void
     */
    private function dispatchJump($message,$status=1,$jumpUrl='',$cdata='',$ajax=false) {
    	
    	if(true === $ajax || IS_AJAX) {// AJAX提交
    		$data   =   is_array($ajax)?$ajax:array();
    		$data['info']   =   $message;
    		$data['status'] =   $status;
    		$data['url']    =   $jumpUrl;
    		$data['data']    =   $cdata;
    		$this->ajaxReturn($data);
    	}
    	

    	if(is_int($ajax)){
    		$this->waitSecond = $ajax;
    		$this->assign('waitSecond',$ajax);
    	}
    	if(!empty($jumpUrl)){
    		$this->jumpUrl = $jumpUrl;
    		$this->assign('jumpUrl',$jumpUrl);
    	}
    	//如果设置了关闭窗口，则提示完毕后自动关闭窗口
    	$this->assign('status',$status);   // 状态
    	
    	//保证输出不受静态缓存影响
    	if($status) { //发送成功信息
    		$this->assign('message',$message);// 提示信息
    		// 成功操作后默认停留1秒
    		if(!isset($this->waitSecond))    $this->assign('waitSecond','3');
    		// 默认操作成功自动返回操作前页面
    		if(!isset($this->jumpUrl)) $this->assign("jumpUrl",$_SERVER["HTTP_REFERER"]);
    		$this->display($this->_config['TMPL_ACTION_SUCCESS']);
    		exit;
    	}else{
    		$this->assign('message',$message);// 提示信息
    		//发生错误时候默认停留3秒
    		if(!isset($this->waitSecond))    $this->assign('waitSecond','3');
    		// 默认发生错误的话自动返回上页
    		if(!isset($this->jumpUrl)) $this->assign('jumpUrl',"javascript:history.back(-1);");
    		$this->display($this->_config['TMPL_ACTION_ERROR']);
    		// 中止执行  避免出错后继续执行
    		exit;
    	}	
    }

}