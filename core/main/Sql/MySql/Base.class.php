<?php

namespace main\Sql\MySql;

use main\Sql\MySql;

/**
 *   数据库组合类
 **/
class Base extends MySql
{
    protected $table;
    protected $debug   = false;
    protected $options = array();
    protected $limit   = 20;
    //protected $distinct=false;
    //protected $field;
    //protected $join;
    //protected $where;
    //protected $group;
    //protected $having;
    //protected $order;
    //protected $limit;
    //protected $page;
    //protected $lock;
    //protected $data;

    protected $comparison = array('eq' => '=', 'neq' => '!=', 'gt' => '>', 'egt' => '>=', 'lt' => '<', 'elt' => '<=', 'notlike' => 'NOT LIKE', 'like' => 'LIKE');
    // 查询表达式
    protected $selectSql = 'SELECT%DISTINCT% %FIELDS% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT%';

    public function __construct($config=false)
    {
        parent::__construct($config);
    }

    public function table($name)
    {
        $this->table = $name;
        return $this;
    }

    public function debug($debug)
    {
        $this->debug = $debug;
        return $this;
    }

    /*** 设置锁机制*/
    public function lock($lock)
    {
        $this->options['lock'] = $lock;
        return $this;
    }

    public function distinct($distinct)
    {
        $this->options['distinct'] = $distinct;
        return $this;
    }

    public function field($field)
    {
        $this->options['field'] = $field;
        return $this;
    }

    public function join($join)
    {
        $this->options['join'] = $join;
        return $this;
    }

    public function where($where)
    {
        $this->options['where'] = $where;
        return $this;
    }

    public function group($group)
    {
        $this->options['group'] = $group;
        return $this;
    }

    public function having($having)
    {
        $this->options['having'] = $having;
        return $this;
    }

    public function order($order)
    {
        $this->options['order'] = $order;
        return $this;
    }

    public function limit($limit)
    {
        $this->options['limit'] = $limit;
        return $this;
    }

    public function data($data)
    {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }
        $this->options['data'] = $data; 
        return $this;
    }

    public function page($page)
    {
        $this->options['page'] = $page;
        return $this;
    }

    /**
     * [parseAddData 批量添加数据解析]
     * @param $data [一维数组，单条添加，二维数组，多条添加]
     * @return array
     */
    public function parseAddData($data)
    {
        $fields = array();
        $values = array();
        if (isset($data[0]) && is_array($data[0])) {
            foreach ($data as $key => $val) {
                $res = $this->parseAddData($val);
                $fields = $res['fields'];
                $values[] = $res['values'];
            }
            $values_str = !empty($values) ? implode(',', $values) : '';
        } else {
            foreach ($data as $key => $val) {
                $value = $this->parseValue($val);
                if (is_scalar($value)) { // 过滤非标量数据
                    $fields[] = $this->parseKey($key);
                    $values[] = $value;
                }
            }
            $values_str = !empty($values) ? '(' . implode(',', $values) . ')' : '';
        }

        return array('fields' => $fields, 'values' => $values_str);
    }

    public function insert($data = array(), $options = array())
    {
        $tableName = isset($options['table']) && !empty($options['table']) ? $options['table'] : $this->table;
        $data = (!empty($data)) ? $data : (isset($this->options['data']) ? $this->options['data'] : '');
        if (empty($tableName) || empty($data) || !is_array($data)) {
            return false;
        }
        $lock = isset($options['lock']) ? $options['lock'] : (isset($this->options['lock']) ? $this->options['lock'] : '');

        $res = $this->parseAddData($data);
        $fields = $res['fields'];
        $values = $res['values'];

        $sql = 'INSERT INTO ' . $tableName . ' (' . implode(',', $fields) . ') VALUES ' . $values;
        $sql .= $this->parseLock($lock);
        $sql = str_ireplace("'UUID()'", "UUID()", $sql);
        $this->options = array();
        if ($this->debug) {
            return $sql;
        }
        return $this->query($sql);
    }

    /**  更新操作， where 不能为空 */
    public function update($data = array(), $options = array())
    {
        $tableName = isset($options['table']) && !empty($options['table']) ? $options['table'] : $this->table;
        $data = (!empty($data)) ? $data : (isset($this->options['data']) ? $this->options['data'] : '');
        if (empty($tableName) || empty($data) || !is_array($data)) {
            return false;
        }
        $where = isset($options['where']) ? $options['where'] : (isset($this->options['where']) ? $this->options['where'] : '');
        $parseWhere = $this->parseWhere($where);
        if (empty($parseWhere)) {
            return false;
        }

        $order = isset($options['order']) ? $options['order'] : (isset($this->options['order']) ? $this->options['order'] : '');
        $limit = isset($options['limit']) ? $options['limit'] : (isset($this->options['limit']) ? $this->options['limit'] : $this->limit);
        $lock = isset($options['lock']) ? $options['lock'] : (isset($this->options['lock']) ? $this->options['lock'] : '');
        $sql = 'UPDATE '
            . $this->parseTable($tableName)
            . $this->parseSet($data)
            . $parseWhere
            . $this->parseOrder($order)
            // .$this->parseLimit($limit)
            . $this->parseLock($lock);
        $this->options = array();
        if ($this->debug) {
            return $sql;
        }
        return $this->query($sql);
    }

    //删除记录
    public function delete($options = array())
    {
        $tableName = isset($options['table']) && !empty($options['table']) ? $options['table'] : $this->table;
        if (empty($tableName)) {
            return false;
        }
        $where = isset($options['where']) ? $options['where'] : (isset($this->options['where']) ? $this->options['where'] : '');
        $order = isset($options['order']) ? $options['order'] : (isset($this->options['order']) ? $this->options['order'] : '');
        $limit = isset($options['limit']) ? $options['limit'] : (isset($this->options['limit']) ? $this->options['limit'] : $this->limit);
        $lock = isset($options['lock']) ? $options['lock'] : (isset($this->options['lock']) ? $this->options['lock'] : '');
        $sql = 'DELETE FROM '
            . $this->parseTable($tableName)
            . $this->parseWhere($where)
            . $this->parseOrder($order)
            . $this->parseLimit($limit)
            . $this->parseLock($lock);
        $this->options = array();
        if ($this->debug) {
            return $sql;
        }
        return $this->query($sql);
    }

    //查找记录 @param array $options 表达式  @return array
    public function select($options = array())
    {
        $tableName = isset($options['table']) && !empty($options['table']) ? $options['table'] : $this->table;
        if (empty($tableName))
            return false;
        $distinct	= isset($options['distinct']) ? $options['distinct'] : (isset($this->options['distinct']) ? $this->options['distinct'] : '');
        $field 	  	= isset($options['field']) ? $options['field'] : (isset($this->options['field']) ? $this->options['field'] : '*');
        $join		= isset($options['join']) ? $options['join'] : (isset($this->options['join']) ? $this->options['join'] : '');
        $pages		= isset($options['page']) && !empty($options['page']) ? $options['page'] : (isset($this->options['page']) ? $this->options['page'] : '');
        $where 		= isset($options['where']) ? $options['where'] : (isset($this->options['where']) ? $this->options['where'] : '');
        $group 		= isset($options['group']) ? $options['group'] : (isset($this->options['group']) ? $this->options['group'] : '');
        $having 	= isset($options['having']) ? $options['having'] : (isset($this->options['having']) ? $this->options['having'] : '');
        $order 		= isset($options['order']) ? $options['order'] : (isset($this->options['order']) ? $this->options['order'] : '');
        $limit 		= isset($options['limit']) ? $options['limit'] : (isset($this->options['limit']) ? $this->options['limit'] : $this->limit);
        $lock 		= isset($options['lock']) ? $options['lock'] : (isset($this->options['lock']) ? $this->options['lock'] : '');
        if ($pages) {// 根据页数计算limit
            if (strpos($pages, ','))
                list($page, $listRows) = explode(',', $pages);
            else
                $page = $pages;
            $page = $page ? $page : 1;
            $listRows = isset($listRows) ? $listRows : (is_numeric($limit) ? $limit : $this->limit);
            $offset = $listRows * ((int)$page - 1);
            $limit = $offset . ',' . $listRows;
        }
        $sql = str_replace(
            array('%TABLE%', '%DISTINCT%', '%FIELDS%', '%JOIN%', '%WHERE%', '%GROUP%', '%HAVING%', '%ORDER%', '%LIMIT%'),
            array(
                $this->parseTable($tableName),
                $this->parseDistinct($distinct),
                $this->parseField($field),
                $this->parseJoin($join),
                $this->parseWhere($where),
                $this->parseGroup($group),
                $this->parseHaving($having),
                $this->parseOrder($order),
                $this->parseLimit($limit)
            ), $this->selectSql);
        $sql .= $this->parseLock($lock);
        $this->options = array();
        if ($this->debug) {
            return $sql;
        }
        return $this->query($sql);
    }

    /*
     * $extend 扩展count统计字段
     */
    public function count($extend='')
    {
        $tableName = $this->table;
        $distinct = isset($this->options['distinct']) ? $this->options['distinct'] : '';
        $join  = isset($this->options['join']) ? $this->options['join'] : '';
        $page  = isset($this->options['page']) ? $this->options['page'] : '';
        $where = isset($this->options['where']) ? $this->options['where'] : '';
        $group = isset($this->options['group']) ? $this->options['group'] : '';
        $having= isset($this->options['having']) ? $this->options['having'] : '';
        $order = isset($this->options['order']) ? $this->options['order'] : '';
        $limit = isset($this->options['limit']) ? $this->options['limit'] : $this->limit;
        $lock  = isset($this->options['lock']) ? $this->options['lock'] : '';
        $field = isset($this->options['field']) ? $this->options['field'] : '';
        $fieldCount = "count(*) as count_nums";
        if( !empty( $extend )){ //如果存在having条件字段会报错.
        		if( is_array( $extend )){
        			$extendStr = implode(',',$extend );
        			$fieldCount .= ",".$extendStr;
        		}elseif( is_string( $extend )){
        			$fieldCount .= ",".$extend;
        		}

        }
        $options = array(
            'table' 	=> $tableName,
            'page' 		=> $page,
            'distinct'  => $distinct,
            'field' 	=> $fieldCount, 
            'join' 		=> $join,
            'where' 	=> $where,
            'group' 	=> $group,
            'having' 	=> $having,
            'order' 	=> $order,
            'limit' 	=> $limit,
            'lock' 		=> $lock
        );
        if( !empty( $group ) ){
        	$this->debug = true;
        	$sql = $this->select($options);
        	$sql = "select count(*) as count_nums from (".$sql.") as g";
        	$this->debug = false; 
        	$rs  = $this->query( $sql );
        	$count = isset($rs[0]['count_nums']) ? $rs[0]['count_nums'] : 0;
        }else{
        	$rs = $this->select($options);
        	$count = isset($rs[0]['count_nums']) ? $rs[0]['count_nums'] : 0;
        }
        return $count;
    }

    /*** 设置锁机制*/
    protected function parseLock($lock = false)
    {
        if (!$lock) return '';
        return ' FOR UPDATE ';
    }

    //DSN解析 格式： mysql://username:passwd@localhost:3306/DbName
    public function parseDSN($dsnStr)
    {
        if (empty($dsnStr)) {
            return false;
        }
        $info = parse_url($dsnStr);
        if ($info['scheme']) {
            $dsn = array(
                'dbms' => $info['scheme'],
                'username' => isset($info['user']) ? $info['user'] : '',
                'password' => isset($info['pass']) ? $info['pass'] : '',
                'hostname' => isset($info['host']) ? $info['host'] : '',
                'hostport' => isset($info['port']) ? $info['port'] : '',
                'database' => isset($info['path']) ? substr($info['path'], 1) : ''
            );
        } else {
            preg_match('/^(.*?)\:\/\/(.*?)\:(.*?)\@(.*?)\:([0-9]{1, 6})\/(.*?)$/', trim($dsnStr), $matches);
            $dsn = array(
                'dbms' => $matches[1],
                'username' => $matches[2],
                'password' => $matches[3],
                'hostname' => $matches[4],
                'hostport' => $matches[5],
                'database' => $matches[6]
            );
        }
        return $dsn;
    }

    //表名，字段等分析
    protected function parseKey(&$key)
    {
        $key = trim($key);
        if (false !== strpos($key, ' ') || false !== strpos($key, ',') || false !== strpos($key, '*') || false !== strpos($key, '(') || false !== strpos($key, '.') || false !== strpos($key, '`')) {
            //如果包含* 或者 使用了sql方法 则不作处理
        } else {
            $key = '`' . $this->escape($key) . '`';
        }
        return $key;
    }

    //value 分析
    protected function parseValue(&$value)
    {
        if (is_string($value)) {
            $value = '\'' . $this->escape($value) . '\'';
        } elseif (isset($value[0]) && is_string($value[0]) && strtolower($value[0]) == 'exp') {
            $value = $this->escape($value[1]);
        } elseif (is_array($value)) {
            $value = array_map(array($this, 'parseValue'), $value);
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
        } elseif (is_null($value)) {
            $value = 'null';
        }
        return $value;
    }

    /**
     *    table 分析
     *    ['table1'=>alias1,'table2'=>alias2]
     **/
    protected function parseTable($tables)
    {
        if (is_array($tables)) {// 支持别名定义
            $array = array();
            foreach ($tables as $table => $alias) {
                if (!is_numeric($table))
                    $array[] = $this->parseKey($table) . ' ' . $this->parseKey($alias);
                else
                    $array[] = $this->parseKey($table);
            }
            $tables = $array;
        } elseif (is_string($tables)) {
            $tables = explode(',', $tables);
            array_walk($tables, array(&$this, 'parseKey'));
        }
        return !empty($tables) ? implode(',', $tables) : '';
    }

    //set 分析
    public function parseSet($data)
    {
        if (empty($data) || !is_array($data)) return false;
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $set[] = $this->parseKey($key) . "=" . $this->parseValue($value);
            }
        }
        return ' SET ' . implode(',', $set);
    }

    //field分析
    public function parseField($fields)
    {

        $fieldsStr = '*';
        if (is_array($fields)) {
            // 支持 'field1'=>'field2' 这样的字段别名定义
            $array = array();
            foreach ($fields as $key => $field) {
                if (!is_numeric($key)) {
                    $array[] = $this->parseKey($key) . ' AS ' . $this->parseKey($field);
                } else {
                    $array[] = $this->parseKey($field);
                }
            }
            $fieldsStr = implode(',', $array);
        } elseif (is_string($fields) && !empty($fields)) {
            $fieldsStr = $this->parseKey($fields);
        } else {
            $fieldsStr = '*';
        }
        return $fieldsStr;
    }

    public function parseWhere($where)
    {
        $whereStr = $this->parseWhereItem($where);
        return empty($whereStr) ? '' : ' WHERE ' . $whereStr;
    }
    //where 基本条件分析
    // 1,where['field'] = value;
    // 2,where['field'] = array('比较运算','值')
    // 3,where['field'] = array('exp','表达式语句') -> ( field 表达式语句 )
    // 4,where['field'] = array('in','值或数组')
    // 5,where['field'] = array('between','值或数组')
    // 6,where['_logic'] = 'and || or';
    // 支持多where条件   $where = array( $where1,$where2,'_logic'=>'and || or')
    public function parseWhereItem($where)
    {
        $whereStr = '';
        if (is_string($where))// 直接使用字符串条件
            $whereStr .= $where;
        else if (is_array($where)) {
            $_logic = isset($where['_logic']) ? trim(strtoupper($where['_logic'])) : '';
            if (!empty($_logic) && ($_logic == 'AND' || $_logic == 'OR')) {
                $operate = ' ' . $_logic . ' ';
                unset($where['_logic']);
            } else {
                $operate = ' AND ';// 默认进行 AND 运算
            }
            foreach ($where as $key => $val) {
                
                if(empty($val) && !is_numeric($val)) continue;
                $whereStr .= "( ";

                if (is_array($val)) {
                    $key = $this->parseKey($key);
                    if (isset($val[0]) && is_string($val[0])) {
                        if (preg_match('/^(EQ|NEQ|GT|EGT|LT|ELT|NOTLIKE|LIKE)$/i', $val[0])) { // 比较运算
                            $whereStr .= $key . ' ' . $this->comparison[strtolower($val[0])] . ' ' . $this->parseValue($val[1]);
                        } elseif ('exp' == strtolower($val[0])) { // 使用表达式
                            $whereStr .= ' (' . $key . ' ' . $val[1] . ') ';
                        } elseif (preg_match('/^(IN|NOT IN)$/i', $val[0])) { // IN 运算
                            if (is_array($val[1])) {
                                array_walk($val[1], array($this, 'parseValue'));
                                $zone = implode(',', $val[1]);
                            } else
                                $zone = $val[1];
                            $whereStr .= $key . ' ' . strtoupper($val[0]) . ' (' . $zone . ')';
                        } elseif (preg_match('/BETWEEN/i', $val[0])) { // BETWEEN运算
                            $data = is_string($val[1]) ? explode(',', $val[1]) : $val[1];
                            $whereStr .= ' (' . $key . ' ' . strtoupper($val[0]) . ' ' . $this->parseValue($data[0]) . ' AND ' . $this->parseValue($data[1]) . ' )';
                        } else {
                            die('_EXPRESS_ERROR_' . ':' . $val[0]);
                        }
                    } else {
                        $whereStr .= $this->parseWhereItem($val);  //多基本条件并列
                    }
                } else {
                    if (is_numeric($key)) {
                        $whereStr .= trim($val);
                    } else {
                        $whereStr .= $this->parseKey($key) . " = " . $this->parseValue($val);
                    }
                }
                $whereStr .= ' )' . $operate;
            }
            $whereStr = substr($whereStr, 0, -strlen($operate));
        }
        return empty($whereStr) ? '' : $whereStr;
    }

    //limit分析
    public function parseLimit($limit)
    {
        if (empty($limit)) return '';
        $limitStr = '';
        if (false !== stripos($limit, 'LIMIT'))
            $limitStr .= ' ' . $limit . ' ';
        else
            $limitStr .= ' LIMIT ' . $limit . ' ';
        return $limitStr;
    }

    //join分析 数组: array('left join ..','right join ..') 或 string只有left join ('install on install.id=xx.id')
    public function parseJoin($join)
    {
        $joinStr = '';
        if (!empty($join)) {
            if (is_array($join)) {
                foreach ($join as $key => $_join) {
                    if (false !== stripos($_join, 'JOIN'))
                        $joinStr .= ' ' . $_join;
                    else
                        $joinStr .= ' LEFT JOIN ' . $_join;
                }
            } else {
                if (false !== stripos($join, 'JOIN'))
                    $joinStr .= ' ' . $join;
                else
                    $joinStr .= ' LEFT JOIN ' . $join;
            }
        }
        return $joinStr;
    }

    //order分析  数组:array('id'=>'desc') array(0=>'id desc'); 或string id desc
    public function parseOrder($order)
    {
        if (is_array($order)) {
            $array = array();
            foreach ($order as $key => $val) {
                if (is_numeric($key)) {
                    $array[] = $this->parseKey($val);
                } else {
                    $array[] = $this->parseKey($key) . ' ' . $val;
                }
            }
            $order = implode(',', $array);
        } else {
            $order = str_ireplace('order by', '', $order);
        }
        return !empty($order) ? ' ORDER BY ' . $order : '';
    }

    //group分析
    public function parseGroup($group)
    {
        if (!empty($group)) {
            $group = str_ireplace('GROUP BY', '', $group);
        }
        return !empty($group) ? ' GROUP BY ' . $group : '';
    }

    //having分析
    public function parseHaving($having)
    {
        if (!empty($having)) {
            $having = str_ireplace('HAVING', '', $having);
        }
        return !empty($having) ? ' HAVING ' . $having : '';
    }

    // distinct分析
    public function parseDistinct($distinct)
    {
        if (!empty($distinct)) {
            $distinct = str_ireplace('DISTINCT', '', $distinct);
        }
        return !empty($distinct) ? ' DISTINCT ' : '';
    }

    //取得数据库的表信息
    public function tableInfo($dbName = '')
    {
        if (!empty($dbName)) $sql = 'SHOW TABLES FROM ' . $dbName;
        else  $sql = 'SHOW TABLES ';
        $result = $this->query($sql);
        $info = array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }

    //取得数据表的字段信息
    public function tableFields($tableName)
    {
        $result = $this->query('SHOW COLUMNS FROM ' . $tableName);
        $info = array();
        if ($result) {
            foreach ($result as $key => $val) {
                $info[$val['Field']] = array(
                    'name' => $val['Field'],
                    'type' => $val['Type'],
                    'notnull' => (bool)($val['Null'] === ''), // not null is empty, null is yes
                    'default' => $val['Default'],
                    'primary' => (strtolower($val['Key']) == 'pri'),
                    'autoinc' => (strtolower($val['Extra']) == 'auto_increment'),
                );
            }
        }
        return $info;
    }


}

