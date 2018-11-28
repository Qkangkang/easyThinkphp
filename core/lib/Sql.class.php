<?php
class Sql {
	private static $_instance;//单例属性
	public static $sql;
	protected static $_sql = [];
	protected $_dbHandle;
	protected $_result;
	// 最后插入ID
	protected $lastInsID  = null;
	protected $error=NULL;
	protected $errorInfo=NULL;
	private $filter = '';
	// PDO连接参数
	protected $options = array(
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_CASE              =>  PDO::CASE_LOWER,
			PDO::ATTR_ERRMODE           =>  PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_ORACLE_NULLS      =>  PDO::NULL_NATURAL,
			PDO::ATTR_STRINGIFY_FETCHES =>  false,
	
	);
	// PDO操作实例
	protected $PDOStatement = null;
	
	// 返回或者影响记录数
	protected $numRows    = 0;
	protected $config     = array();
	
	//单例方法
	public static function get_instance($t='r',$conf)
	{
		
		if(!isset(self::$_instance[$t]))
		{
			self::$_instance[$t]=new self($t,$conf);
		}
		return self::$_instance[$t];
	}
	
	private function __construct($t,$conf)
	{	
		$this->config=$conf;
		$host = explode(',',$this->config['DB_HOST']);
		$user = explode(',',$this->config['DB_USER']);
		$pass = explode(',',$this->config['DB_PASSWORD']);
		$port = explode(',',$this->config['DB_PORT']);
		$index = 0;
		if($t=='r'){
			if(!isset($host[1])){
				$index = 0;
			}else{
				$index = 1;
			}
			if($index==1){
				!isset($user[1]) && $user[1]=$user[0];
				!isset($pass[1]) && $pass[1]=$pass[0];
				!isset($port[1]) && $port[1]=$port[0];
				
			}
		}
	
		$this->connect($host[$index],$port[$index],$user[$index],$pass[$index],$this->config['DB_NAME'],$this->config['DB_CHARSET'],$this->config);
		
	}
	
	//阻止用户复制对象实例
	private function __clone()
	{
		E('Clone is not allow' ,E_USER_ERROR);
	}
	
	// 连接数据库
	public function connect($host,$port, $user, $pass, $dbname,$charset,$config) {
			$dsn = sprintf ( "mysql:host=%s;port=%s;dbname=%s;charset=%s", $host,$port,$dbname,$charset);
			
			try {
				$this->_dbHandle = new PDO ( $dsn, $user, $pass, $this->options);
			} catch (PDOException $e) {
				if($config['DB_DEBUG']){
                    E($e->getMessage());
                }else{
//                 	trace($e->getMessage(),'','ERR');
					log_write($e->getMessage(),"sql_error");
                	return false;
                }
			}	
	}
	
	
	// 创建sql
	private function buildsql($options, $type, $data = '') {
		// select * from table_a where num = 4 group by num1 order by num1, num2 ASC limit 10，10,
		// 1.$field 2.tabename 3.where 4.group by 5.order by 6.limit
		if (isset ( $options ['field'] )) {
			$field_str = $options ['field'];
		} else {
			$field_str = " * ";
		}
		
		$where_str = '';
		if (isset ( $options ['where'] )) {
			if (is_array ( $options ['where'] )) {
				foreach ( $options ['where'] as $wkey => $wval ) {
					
					
					if ($wkey == '_str_') {
						$where_str .= " and " . $wval;
						continue;
					}else{
						$tempkey = explode(".", $wkey);
						if(isset($tempkey[1])){
							$wkey=$tempkey[0].'.`' . $tempkey[1] . '`';
						}else{
							$wkey = '`' . $tempkey[0] . '`';
						}
					}
					
				
					
					if (is_array ( $wval )) {
						
						if($wval [0] =='EXP'){
							$val_str =" ". $wval[1]." "  ;
						}else{
							$val_str = " " . $wval [0] . " ";
							$val_str .= (gettype ( $wval[1] )!='string') ? " " . $wval [1] . " " : " '" . $wval [1] . "'";
						}
						

					} else {
						$val_str = (gettype ( $wval )!='string') ? " = " . $wval . " " : " = '" . $wval . "'";
					}
					
					$where_str .= " and " . $wkey . " " . $val_str;
				}
				$where_str = ltrim ( trim ( $where_str ), 'and' );
				
				//$where_str = implode ( ' ', $options ['where'] );
			} else {
				$where_str = $options ['where'];
			}
			
			if(!empty($where_str)){
				
				$where_str = " where " . $where_str;
			
			}
		
		}
		
		$join_str='';
		if (isset ( $options ['join'] )) {
			foreach ( $options ['join'] as $jkey => $jval) {
				if(empty($jval[2])){
					continue;
				}
				$on_str='';
				if (is_array ( $jval[2] )) {
					foreach ( $jval[2] as $wkey => $wval ){
						if (is_array ( $wval )) {
							$val_str = " " . $wval [0] . " ";
							$val_str .=  " " . $wval [1] . " " ;
						} else {
							$val_str =  " = " . $wval . " " ;
						}
						
						$on_str .= " and " . $wkey . " " . $val_str;
					}
					$on_str = ltrim ( trim ( $on_str ), 'and' );
					//$where_str = implode ( ' ', $options ['where'] );
				}else{
					$on_str = $jval[2];
				}
				$join_str .=  $jval[0].' join '. $jval[1].' on '.$on_str.' ';
			}
		}
		
	
		
		$group_str = '';
		if (isset ( $options ['group'] )) {
			$group_str = " group by  " . $options ['group'];
		}
		
		$order_str = '';
		if (isset ( $options ['order'] )) {
			$order_str = " order by  " . $options ['order'];
		}
		
		$limit_str = '';
		if (isset ( $options ['limit'] )) {
			$limit_str = "limit " . $options ['limit'] . " ";
		}
		
		
		if ($type == 'delete') {
			return "DELETE FROM `{$options['table']}` {$where_str}";
		}
		if ($type == 'select') {
			return "select {$field_str} from `{$options['table']}` {$join_str}  {$where_str} {$group_str} {$order_str} {$limit_str}";
		}
		if ($type == 'find') {
			return "select {$field_str} from `{$options['table']}`  {$where_str} {$group_str} {$order_str} limit 1";
		}
		if ($type == 'count') {
			return "select count(*) as countnum from `{$options['table']}`  {$where_str} {$group_str} {$order_str} limit 1";
		}
		if ($type == 'sum') {
			return "select SUM({$field_str}) as countnum from `{$options['table']}`  {$where_str} ";
		}
		if ($type == 'insert') {
			// return "INSERT INTO `{$options['table']}` (`" . implode ( '`,`', array_keys ( $data ) ) . "`) VALUES ('" . implode ( "','", $data ) . "')";
			return sprintf ( "insert into `%s` %s", $options ['table'], $this->formatInsert ( $data ) );
		}
		
		if ($type == 'replace') {
			// return "INSERT INTO `{$options['table']}` (`" . implode ( '`,`', array_keys ( $data ) ) . "`) VALUES ('" . implode ( "','", $data ) . "')";
			return sprintf ( "replace into `%s` %s", $options ['table'], $this->formatInsert ( $data ) );
		}
		if ($type == 'update') {
			
			$data_str = '';
			
			if (is_array ( $data )){
				
				// foreach ( $data as $dkey => $dval ) {
				// $data_str .= "`" . $dkey . "`= '" . addslashes ( $dval ) . "',";
				// }
				// $data_str = rtrim ( trim ( $data_str ), "," );
				$data_str = $this->formatUpdate ( $data );
			} else {
				$data_str = $options ['where'];
			}
			
			return sprintf ( "update `%s` set %s %s", $options ['table'], $data_str, $where_str );
			// return "UPDATE `{$options['table']}` SET {$data_str} {$where_str} ";
		}

        if ($type == 'inc') {
             $tempFiledStr =   strpos($options ['field'],'`')===false ? '`'.$options ['field'].'`' : $options ['field'] ;
             $data_str = $tempFiledStr .' = '.$tempFiledStr.' + '.$data;
            return sprintf ( "update `%s` set %s %s", $options ['table'], $data_str, $where_str );
            // return "UPDATE `{$options['table']}` SET {$data_str} {$where_str} ";
        }
        if ($type == 'dec') {

            $tempFiledStr =   strpos($options ['field'],'`')===false ? '`'.$options ['field'].'`' : $options ['field'] ;
            $data_str = $tempFiledStr .' = '.$tempFiledStr.' - '.$data;
            return sprintf ( "update `%s` set %s %s", $options ['table'], $data_str, $where_str );
            // return "UPDATE `{$options['table']}` SET {$data_str} {$where_str} ";
        }


	}
	
	// 获取上一条SQL
	public static function getLastSql() {
		return self::$sql;
	}
	
	// 将数组转换成插入格式的sql语句
	private function formatInsert($data) {
		$fields = array ();
		$values = array ();
		foreach ( $data as $key => $value ) {
			$fields [] = sprintf ( "`%s`", $key );
			$values [] = (gettype ( $value )!='string') ?  sprintf ( "%s", $value ) :  sprintf ( "'%s'", $value );
		}
		
		$field = implode ( ',', $fields );
		$value = implode ( ',', $values );
		return sprintf ( "(%s) values (%s)", $field, $value );
	}
	
	// 将数组转换成更新格式的sql语句
	private function formatUpdate($data){
		$fields = array ();
		
		foreach ( $data as $key => $value ) {
			
			if (is_string($value)){
				$value="'".$value."'";
			}else if(is_array($value)){
				$value = $value[0];
				if(isset($value[1]) && function_exists($value[1])){
					$value = call_user_func($value[1],$value[0]);
				} 
			}
			$fields [] = sprintf ( "`%s` = %s", $key, $value );
		}
		return implode (',', $fields );
	}
	
	// 自定义SQL查询，返回影响的行数
	public function query($sql,$type='all') {
		
		$this->PDOStatement =null;
		$this->PDOStatement = $this->_dbHandle->prepare($sql);
		
		
 		if(false === $this->PDOStatement) {
 			$this->display_error();
 			return false;
 		}
	
		try{
			$start_time = microtime(TRUE);
			
			$result =   $this->PDOStatement->execute();
			
			$end_time = microtime(TRUE);
			
			self::log($sql, $end_time - $start_time);
			// 调试结束
		
			if ( false === $result) {
		
				$this->display_error();
		
				return false;
		
			} else {
				 if($type=='all'){
					 $data = $this->getResult();
				 }else{
				 	$data = $this->getOneResult();
				 }
				 return $data;
		
			}
		
		}catch (PDOException $e) {
			
			$this->display_error();
			//throw new PDOException($e);
			//return false;
		
		}
		
		
		/* $sth = $this->_dbHandle->query ( $sql );
		if($sth===false){
			$this->display_error();
			return false;
		}
		return $sth->fetchAll ();
		 */
		
		
	}
	
	public function exec($sql) {
		
		$this->PDOStatement=null;
		
		$this->PDOStatement = $this->_dbHandle->prepare($sql);
		
// 		if(false === $this->PDOStatement) {
// 			$this->display_error();
// 			return false;
// 		}
		
		try{
			$start_time = microtime(TRUE);
			
			$result =   $this->PDOStatement->execute();
			$end_time = microtime(TRUE);
			self::log($sql, $end_time - $start_time);
			// 调试结束
		
			if ( false === $result) {
		
				$this->display_error();
		
				return false;
		
			} else {
		
				$this->numRows = $this->PDOStatement->rowCount();
		
				if(preg_match("/^\s*(INSERT\s+INTO|REPLACE\s+INTO)\s+/i", $sql)) {
		
					$this->lastInsID = $this->_dbHandle->lastInsertId();
		
				}		
				return $this->numRows;
		
			}
		
		}catch (PDOException $e) {
		
			$this->display_error();
		
			return false;
		
		}
		
		
		/* $result = $this->_dbHandle->exec($sql);
		if($result===false){
			$this->display_error();
			return false;
		}
		return $result; */
		
	}
	
	// 查询
	public function select($options) {
		$strSql = $this->buildsql ( $options, 'select' );
		self::$sql = $strSql;
		return $this->query($strSql);
	}
	// 查询单条
	public function find($options) {
		$strSql = $this->buildsql ( $options, 'find' );
		self::$sql = $strSql;
			
		return $this->query($strSql,'one');
			
	}
	
	// 修改数据
	public function update($data, $options) {
		
		$strSql = $this->buildsql ( $options, 'update', $data );
		self::$sql = $strSql;
		return $this->exec($strSql);
	}

    // 数据 数据自增
    public function setinc($data, $options) {

        $strSql = $this->buildsql ( $options, 'inc', $data );
        self::$sql = $strSql;
        return $this->exec($strSql);
    }
    // 数据 数据自减
    public function setdec($data, $options) {

        $strSql = $this->buildsql ( $options, 'dec', $data );
        self::$sql = $strSql;
        return $this->exec($strSql);
    }
	
	// 根据条件删除
	public function delete($options) {
		$strSql = $this->buildsql ( $options, 'delete' );
		self::$sql = $strSql;
		return $this->exec($strSql);
	}
	
	// 新增数据
	public function insert($data, $options) {
		$strSql = $this->buildsql ( $options, 'insert', $data );
		self::$sql = $strSql;
		
		if($this->exec($strSql)!==false){
			return $this->_dbHandle->lastInsertId(); 
		}
		return false;

	}
	// 覆盖新增数据
	public function replace($data, $options) {
		
		$strSql = $this->buildsql ( $options, 'replace', $data );
		self::$sql = $strSql;
	
		if($this->exec($strSql)!==false){
			return $this->_dbHandle->lastInsertId();
		}
		return false;
	
	}
	// 统计
	public function count($options) {
		$strSql = $this->buildsql ( $options, 'count' );
		self::$sql = $strSql;
		$res = $this->query($strSql,'one');
		return intval($res['countnum']);
	}
	
	//sum总和
	public function sum($options) {
		$strSql = $this->buildsql ( $options, 'sum' );
		self::$sql = $strSql;
		$res = $this->query($strSql,'one');
		return intval($res['countnum']);
	}
	
	
	// 获取插入ID
	public function getLastInsID() {
		return $this->_dbHandle->lastInsertId();
	}
	
	/**
	
	* 获得所有的查询数据
	
	* @access private
	
	* @return array
	
	*/
	
	private function getResult() {
	
		//返回数据集
	
		$result =   $this->PDOStatement->fetchAll(PDO::FETCH_ASSOC);
		
		$this->numRows = count( $result );
		
		return $result;
	
	}
	
	private function getOneResult() {
	
		//返回数据集
		$result =   $this->PDOStatement->fetch(PDO::FETCH_ASSOC);
		$this->numRows = $this->PDOStatement->rowCount();
		if($this->numRows==0){
			$result=null;
		}
		$this->numRows = count( $result );
	
		return $result;
	
	}
	
	// destruct 关闭数据库连接
	public function destruct() {
		$this->_dbHandle = null;
	}
	
	
	//beginTransaction 事务开始
	function beginTransaction()
	{
		if(!$this->_dbHandle->beginTransaction()){
			die('事务未正确开启，请检查表结构。');
		}
	}
	
	/**
	 * commit 事务提交
	 */
	function commit()
	{
		$this->_dbHandle->commit();
	}
	
	/**
	 * rollback 事务回滚
	 */
	function rollback()
	{
		$this->_dbHandle->rollBack();
	}
	
	 function getErrinfo(){
		return   $this->errorInfo[2];
	}
	 function getErrno(){
		return $this->errorInfo[1];
	}
	
	
/**

     * 数据库错误信息

     * 并显示当前的SQL语句

     * @access public

     * @return string

     */

    public function display_error() {

        if($this->PDOStatement) {

            $error = $this->PDOStatement->errorInfo();

            $this->error = $error[1].':'.$error[2];

        }else{

            $this->error = '';

        }

        if('' != self::$sql){

            $this->error .= "\n [ SQL语句 ] : ".self::$sql;

        }

        // 记录错误日志

        log_write( $this->error  , 'sql_error');

        if($this->config['DB_DEBUG']) {// 开启数据库调试模式

            E($this->error);

        }else{

            return $this->error;

        }

    }
    
    /**
     * SQL语句调试.
     * debug===true时, 默认记录在sql.log
     *
     * @param string $statement <code>select * from user where id = ?</code>
     * @param array $params <code>参数的值 array(1)</code>
     * @param float $execute_time 当前sql执行时间
     *
     * @return void
     */
    public static function log($statement, $execute_time = 0)
    {	
    	
    	if(C('MYSQL')['DB_DEBUG']){
    		log_write($statement . ' [time:' . number_format($execute_time, 4) . ' s]', 'sql');
    	}
    	
    	if (APP_DEBUG) {
    		if (count(self::$_sql) < 1000) {
    			self::$_sql[] = ' <font color="red">[time:' . number_format($execute_time, 4) . 's]</font> ' . $statement;
    		}	
    	} else {
    		self::$_sql[] = 1;
    	}
    }
    public static function getSql()
    {
    	return self::$_sql;
    }
    
    
}