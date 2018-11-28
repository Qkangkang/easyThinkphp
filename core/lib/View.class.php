<?php
/**
 * 视图基类
 */
class View
{
    protected $variables = array();
    protected $_group;
    protected $_controller;
    protected $_action;
    protected $_config;
   
    function __construct($group,$controller, $action,$config=array())
    {	
    	$this->_group = $group;
        $this->_controller = $controller;
        $this->_action = $action;
        $this->_config = $config;
    }
 
    // 分配变量
    
    public function assign($name, $value = '')
    {
    	if (is_array($name)) {
    		$this->variables = array_merge($this->variables, $name);
    	} else {
    		$this->variables[$name] = $value;
    	}
    	return $this;
    }
    
    // 渲染显示
    public function render($path='')
    {

        extract($this->variables);

        if (!empty($this->_config['VIEW_PATH'])) {
            $tmpPath = ROOT_DIR . $this->_config['VIEW_PATH'] . '/';
        } else {
            $tmpPath = APP_DIR . $this->_group . '/views/';
        }
        if ($this->_config['THEM_ON'] == true && !empty($this->_config['THEM_NAME'])) {
            $tmpPath = $tmpPath . $this->_config['THEM_NAME'] . '/';
        }
        if ($path == '') {
            $controllerLayout = $this->_controller . '/' . $this->_action . '.php';
        } else {

            //系统设置路径
            if (file_exists(APP_DIR . $path)) {
                $tmpPath = APP_DIR;
                $controllerLayout = $path;

            } else {

                $tmparray = explode(':', $path);

                if (count($tmparray) > 1) {
                    $controllerLayout = $tmparray[0] . '/' . $tmparray[1] . '.php';
                } else {
                    $controllerLayout = $this->_controller . '/' . $tmparray[0] . '.php';
                }
                //404 处理
                if ($path == 'public:404' && !file_exists($tmpPath . $controllerLayout)) {
                    $tmpPath = APP_DIR;
                    $controllerLayout = 'errorpage/404.php';
                }
            }
        }

        // 页内容文件
        if ($this->_config['AUTO_MOBILE_TPL'] && is_mobile() && file_exists($tmpPath.str_replace(".php","_m.php",$controllerLayout))) {
           
            $_SERVER['VIEW_TPL']=str_replace(".php","_m.php",$controllerLayout);
        	include ($tmpPath.str_replace(".php","_m.php",$controllerLayout));
        }else if (file_exists($tmpPath.$controllerLayout)) {
        	$_SERVER['VIEW_TPL']=$controllerLayout;
        	include ($tmpPath.$controllerLayout);
        } else {
        	E($tmpPath.$controllerLayout . "模版不存在");
        }
        

    }
}