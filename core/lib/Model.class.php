<?php

class Model 

{
    protected $_model;
    protected $_table;
    protected $table='';
    protected $_prefix;
    protected $_config;
    protected $fields           =   array(); // 字段信息
    protected $data             =   array(); // 数据信息
    protected $options          =   array(); // 查询表达式参数
    protected $error = null;
    private $Db;
    private $conn;
    
    
    public function __construct($table='',$prefix=null)
    {	
    	$dbconfig=C('MYSQL');
    	$this->_config=C();
        // 连接数据库
        //$this->connect($dbconfig['DB_HOST'],$dbconfig['DB_USER'],$dbconfig['DB_PASSWORD'],$dbconfig['DB_NAME'],$dbconfig['DB_CHARSET']);
    	//$this->Db = Sql::get_instance();
        // 获取模型类名
        $this->_model = get_class($this);
        // 删除类名最后的 Model 字符
        $this->_model = substr($this->_model, 0, -5);
        //表前缀
        $this->_prefix =  $prefix===null ? $dbconfig['DB_PREFIX'] : $prefix ;
        // 数据库表名与类名一致
        if($this->table != ''){
        	$this->_table = $this->_prefix.$this->table;
        }else{
        	$this->_table =  $table=='' ?  $this->_prefix.strtolower($this->_model) :  $this->_prefix.$table;
        }
        
    }
    
    //选择数据库  r读 w写
   private function db($t='r'){
   		if(!isset($this->conn[$t])){
   			$this->conn[$t] = Sql::get_instance($t,$this->_config['MYSQL']);
   		}
   		$this->Db=$this->conn[$t];
   		return $this->conn[$t];
   }
    
    
    /**
     * 定义数据库表名
     * @access public
     * @return Model
     */
    public function table($table,$prefix=null){
    	$this->options['table']   = $prefix===null ? $this->_prefix.$table : $prefix.$table;
    	return $this;
    }
	/**
	 * join 关联
	 * @param unknown $type
	 * @param unknown $table
	 * @param unknown $on
	 * @param unknown $prefix
	 * @return Model
	 * $Db = new Model('attention');
    	$file='stone_attention.id,stone_attention.attention_id AS fansid,stone_attention.create_date,stone_user.user_head_img AS userheadimg,stone_user.user_name AS username,stone_user.id AS uid';
    	$where['stone_attention.create_by']='afb5e87415aa4aaea4e651e48dff6469';
    	$on['stone_user.id']='stone_attention.attention_id';
    	$Db->field($file)->join('left', 'user', $on)->where($where)->order(' create_date desc ')->limit(' 10 ')->select();
    	var_dump($Db->getLastSql());
    	exit;
	 */
    
    public function join($type,$table,$on,$prefix=null){
    	$joinTable = $prefix===null ? $this->_prefix.$table : $prefix.$table;
    	$this->options['join'][] = array($type,$joinTable,$on);
    	return $this;
    }
   
    
    /**
     * 指定查询条件 支持安全过滤
     * @access public
     * @param mixed $where 条件表达式
     * @param mixed $parse 预处理参数
     * @return Model
     */
    public function where($where){
    	if(is_string($where) && trim($where)==''){
    		return $this;
    	}
    	if(is_object($where)){
    		$where  =   get_object_vars($where);
    	}
    	if(isset($this->options['where'])){
    		$this->options['where'] =   array_merge($this->options['where'],$where);
    	}else{
    		$this->options['where'] =   $where;
    	}
    	return $this;
    }
    
    /**
     * order
     * @access public
     * @param mixed $where 条件表达式
     * @param mixed $parse 预处理参数
     * @return Model
     */
	public function order($order=""){
        $temp_order='';
        if(empty($order)){
            $temp_order=" ";
        }
        if(is_array($order)){
            
            foreach ($order as $okey=>$oval){
                $temp_order.= '`'.$okey.'` '.$oval.',';
            }
            $temp_order=rtrim($temp_order,',');
           
        }else{
            $temp_order=$order;
        }
        $this->options['order']   =   $temp_order;
        return $this;
    }


    /**
     * order
     * @access public
     * @param mixed $where 条件表达式
     * @param mixed $parse 预处理参数
     * @return Model
     */
    public function group($order=""){
        $temp_order='';
        if(empty($order)){
            $temp_order=" ";
        }
        if(is_array($order)){

            foreach ($order as $okey=>$oval){
                $temp_order.= '`'.$okey.'` '.$oval.',';
            }
            $temp_order=rtrim($temp_order,',');

        }else{
            $temp_order=$order;
        }
        $this->options['group']   =   $temp_order;
        return $this;
    }


    
    /**
     * 指定查询字段 支持字段排除
     * @access public
     * @param mixed $field
     * @param boolean $except 是否排除
     * @return Model
     */
    public function field($field=""){
    	$temp_field='';
    	if(empty($field)){
    		$temp_field="*";
    	}
    	if(is_array($field)){
    		$temp_field ="`".implode("`,`", $field)."`";
    	}else{
    		$temp_field=$field;
    	}
    	$this->options['field']   =   $temp_field;
    	return $this;
    }
    
    /**
     * 新增数据
     * @access public
     * @param mixed $data 数据
     * @param array $options 表达式
     * @param boolean $replace 是否replace
     * @return mixed
     */
    public function add($data='',$options=array()) {
    	if(empty($data)) {
    		// 没有传递数据，获取当前数据对象的值
    		if(!empty($this->data)) {
    			$data           =   $this->data;
    			// 重置数据
    			$this->data     = array();
    		}else{
    			$this->error    = "无数据更新。";
    			return false;
    		}
    	}
    	// 数据处理
    	$data       =   $this->_facade($data);
    	// 分析表达式
    	$options    =   $this->_parseOptions($options);
    
    	if(!$options['table']){
    		$this->error    = "没有定义数据表";
    		return false;
    	}
    	// 写入数据到数据库
    	$result = $this->db('w')->insert($data,$options);
    
    	return $result;
    }
    
    /**
     * 新增数据
     * @access public
     * @param mixed $data 数据
     * @param array $options 表达式
     * @param boolean $replace 是否replace
     * @return mixed
     */
    public function replace($data='',$options=array()) {
    	
    	if(empty($data)) {
    		// 没有传递数据，获取当前数据对象的值
    		if(!empty($this->data)) {
    			$data           =   $this->data;
    			// 重置数据
    			$this->data     = array();
    		}else{
    			$this->error    = "无数据更新。";
    			return false;
    		}
    	}
    	// 数据处理
    	$data       =   $this->_facade($data);
    	// 分析表达式
    	$options    =   $this->_parseOptions($options);
    	
    	if(!$options['table']){
    		$this->error    = "没有定义数据表";
    		return false;
    	}
    	// 写入数据到数据库
    	$result = $this->db('w')->replace($data,$options);
    
    	return $result;
    }
    
    
    /**
     * 查询数据集
     * @access public
     * @param array $options 表达式参数
     * @return mixed
     */
    public function select($options=array()) {
    	// 分析表达式
    	$options    =  $this->_parseOptions($options);
    	$resultSet  =  $this->db()->select($options);
    	return $resultSet;
    }
    
    /**
     * 查询数据
     * @access public
     * @param mixed $options 表达式参数
     * @return mixed
     */
    public function find($options=array()) {
    
    	// 根据复合主键查找记录
    
    	// 总是查找一条记录
    	$options['limit']   =   1;
    	// 分析表达式
    	$options            =   $this->_parseOptions($options);
    	// 判断查询缓存
    
    	$resultSet          =   $this->db()->find($options);
    	if(false === $resultSet) {
    		return false;
    	}
    	if(empty($resultSet)) {// 查询结果为空
    		return null;
    	}
    	if(is_string($resultSet)){
    		return $resultSet;
    	}
    
    	// 读取数据后的处理
    	$data  =  $resultSet;
    
    	$this->data     =   $data;
    
    	return $this->data;
    }
    
    /**
     * 保存数据
     * @access public
     * @param mixed $data 数据
     * @param array $options 表达式
     * @return boolean
     */
    public function save($data='',$options=array()) {
    	
    	if(empty($data)) {
    		// 没有传递数据，获取当前数据对象的值
    		if(!empty($this->data)) {
    			$data           =   $this->data;
    			// 重置数据
    			$this->data     =   array();
    		}else{
    			$this->error    =   "无更新数据";
    			return false;
    		}
    	}
    	// 数据处理
    	$data       =   $this->_facade($data);
    	
    	if(empty($data)){
    		// 没有数据则不执行
    		$this->error    =    "无更新数据";
    		return false;
    	}
    	// 分析表达式
    	$options    =   $this->_parseOptions($options);
    	
    	$result     =  $this->db('w')->update($data,$options);
    	return $result;
    }
    
    /**
     * 删除数据
     * {@inheritDoc}
     * @see Sql::delete()
     */
    public function delete($options=array()) {
    
    	// 分析表达式
    	$options =  $this->_parseOptions($options);
    	if(empty($options['where'])){
    		// 如果条件为空 不进行删除操作 除非设置 1=1
    		return false;
    	}
    	$result  =  $this->db('w')->delete($options);
    	// 返回删除记录个数
    	return $result;
    }
    
    /**
     * 统计记录
     * {@inheritDoc}
     * @see Sql::count()
     */
    public function count($options=array()) {
    
    	// 根据复合主键查找记录
    	// 总是查找一条记录
    	$options['limit']   =   1;
    	// 分析表达式
    	$options            =   $this->_parseOptions($options);
    	// 判断查询缓存
    	$resultSet          =   $this->db()->count($options);
    	return $resultSet;
    
    }
    
    /**
     * 查询数据总和
     * @access public
     * @param mixed $options 表达式参数
     * @return mixed
     */
    public function sum($filed,$options=array()) {
    
    	// 根据复合主键查找记录
    	$this->options['field']   =   $filed;
    
    	// 分析表达式
    	$options            =   $this->_parseOptions($options);
    	// 判断查询缓存
    
    	$resultSet          =   $this->db()->sum($options);
    	if(false === $resultSet) {
    		return false;
    	}
    	if(empty($resultSet)) {// 查询结果为空
    		return null;
    	}
    	if(is_string($resultSet)){
    		return $resultSet;
    	}
    
    	// 读取数据后的处理
    	$data  =  $resultSet;
    
    	$this->data     =   $data;
    
    	return $this->data;
    }
    
    /**
     * 启动事务
     * @access public
     * @return void
     */
    public function startTrans() {

    	$this->db('w')->beginTransaction();
    	return ;
    }
    
    /**
     * 提交事务
     * @access public
     * @return boolean
     */
    public function commit() {
    	return $this->db('w')->commit();
    }
    
    /**
     * 事务回滚
     * @access public
     * @return boolean
     */
    public function rollback() {
    	return $this->db('w')->rollback();
    }
    
    /**
     * 返回模型的错误信息
     * @access public
     * @return string
     */
    public function getError(){
    	return $this->error;
    }
    
    /**
     * 返回数据库的错误信息
     * @access public
     * @return string
     */
    public function getDbError() {
    	return $this->Db->getErrinfo();
    }
    
    /**
     * 返回最后插入的ID
     * @access public
     * @return string
     */
    public function getLastInsID() {
    	return $this->Db->getLastInsID();
    }
    
    /**
     * 返回最后插入的ID
     * @access public
     * @return string
     */
    public function getLastSql() {
    	return $this->Db->getLastSql();
    }
    
   
    /**
     * 指定查询数量
     * @access public
     * @param mixed $offset 起始位置
     * @param mixed $length 查询数量
     * @return Model
     */
    public function limit($offset){
    	$this->options['limit']     =   $offset;
    	return $this;
    }
    
    /**
     * 分析表达式
     * @access protected
     * @param array $options 表达式参数
     * @return array
     */
    protected function _parseOptions($options=array()) {
    	if(is_array($options)){
    		$options =  array_merge($this->options,$options);
    	}
    	
    	if(!isset($options['table'])){
    		// 自动获取表名
    		$options['table']   =    $this->_table;
    		 
    	}
    	
    	// 记录操作的模型名称
    	//	$options['model']       =   $this->name;

    	// 查询过后清空sql表达式组装 避免影响下次查询
    	$this->options  =   array();
    	// 表达式过滤
    	return $options;
    }

    /**
     * 自增函数
     * @param $field
     * @param int $step
     */
    function inc($field,$step=1){

        $options['field']=$field;

        // 数据处理

        if(empty($field)){
            // 没有数据则不执行
            $this->error    =    "无更新字段";
            return false;
        }
        // 分析表达式
        $options    =   $this->_parseOptions($options);

        $result     =  $this->db('w')->setinc($step,$options);
        return $result;
    }
    /**
     * 自减函数
     * @param $field
     * @param int $step
     */
    function dec($field,$step=1){
        $options['field']=$field;


        if(empty($field)){
            // 没有数据则不执行
            $this->error    =    "无更新字段";
            return false;
        }
        // 分析表达式
        $options    =   $this->_parseOptions($options);

        $result     =  $this->db('w')->setdec($step,$options);
        return $result;
    }
    
    /**
     * 对保存到数据库的数据进行处理
     * @access protected
     * @param mixed $data 要操作的数据
     * @return boolean
     */
    protected function _facade($data) {
    	// 检查数据字段合法性
    	return $data;
    }
    
    
}