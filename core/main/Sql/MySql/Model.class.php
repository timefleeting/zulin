<?php

namespace main\Sql\MySql;

/**
 *
 *  内程数据接口
 *
 *  数据库交互，只对关键必要数据作基本限制
 *
 * @author hebin
 * @version v1.0
 *
 * 基于角色权限控制接口[通用 经论讨修改]
 * 核心准则: 1,配置-> [基于角色 继承|[禁权|解禁]]  2,实现->[以用户为入口 依照从下至上禁权，解禁，继承最终确认权限]
 *
 *        配置权限是基于角色是由上至下[一对多<也就是变相n对n>]模式配置，反之，将是禁用/解禁模式
 *      图解思路：
 *                角色 --[1 to n]--> 组 --[1 to n]--> 用户
 *                otherwise:
 *                    {X 用户 --[禁权|解禁]--> 组 --[禁权|解禁]--> 角色  [修正:直系不回溯]}
 *                用户<单元> --[禁权|解禁]--> 权限<单元>
 * //一、接口组件表  1,菜单表 2,API功能接口表 3,页面模块表
 * protected $x_app_node = "xingfu.xf_app_node";    //功能导航目录
 * protected $x_app_api  = "xingfu.xf_app_api";     //请求接口功能
 * protected $x_app_page = "xingfu.xf_app_page";     //请求的页面模块
 * // 二、权限表(api接口功能)
 *    1, [权限 基本信息]
 *    2, [权限 一对多 [接口组件表 分配]]
 *    3, [权限 一对多 [角色 分配]] <反之*>
 * protected $x_privilege       = "xingfu.xf_privilege";
 * protected $x_privilege_com = "xingfu.xf_privilege_component";
 * // 三、角色表
 *    1,    [角色 基本信息]
 *        2,    [角色 一对多 [权限 分配|(0为超级权限)]]
 *        3,    [角色 一对多 [组 分配]]
 * protected $x_role        = "xingfu.xf_role";
 * protected $x_role_p     = "xingfu.xf_role_privilege";
 * protected $x_role_o     = "xingfu.xf_role_org";
 * // 四、分组表(不参于权限配置，权限只能通过角色继承)
 *    1,  [组 基本信息]
 *        2,    [组 一对多 [角色 分配]] <反之*>
 *        3,    [组 一对多 [用户 分配]]
 *      4,  [组 一对多 [权限 禁用|解禁]] (权限:直接通过权限禁用 或 解禁<可继续通过继承使用该权限>)
 * protected $x_org      = "xingfu.xf_org";
 * protected $x_org_s    = "xingfu.xf_org_staff";              //部门人员
 * protected $x_org_pd   = "xingfu.xf_org_privilege_disable";
 *    五、用户表(不参于权限配置,权限只能通过组继承)
 *        1,    [用户 基本信息]
 *       2,  [用户 一对多 [组 分配]] <反之*>
 *       3,  [用户 一对多[权限 禁用|解禁]]    (权限:直接通过权限禁用 或 解禁<可继续通过继承使用该权限>)
 * protected $x_staff    = 'xingfu.xf_staff';
 * protected $x_staff_pd = "xingfu.xf_staff_privilege_disable";
 */


class Model extends Base
{

	/***/
    private function returnRes()
    {
        $res = array('status' => 0, 'data' => 0 );
        return $res;
    }

    public function __construct($config=false)
    {
        parent::__construct($config);
    }

    /**
     * [lists  通用列表信息查询]
     * @param  integer $page [页码]
     * @param  integer $limit [分页条数]
     * @param  [array] $option   [查询条件]
     * @return [array] $res      [列表信息]
     */
    public function lists($page = 0, $limit = 0, $option = '',$debug=false)
    {
        $res = $this->returnRes();
        $_page  = !empty($page)  ? $page : 1;
        $_limit = !empty($limit) ? $limit : 0;
        $limit = $_limit;
        if (!empty($_page)) {
            $limit = ($_page - 1) * $_limit . ',' . $_limit;
        }
        $field = isset($option['field']) ? $option['field'] : '';
        $join  = isset($option['join'] ) ? $option['join']  : '';
        $where = isset($option['where']) ? $option['where'] : '';
        $order = isset($option['order']) ? $option['order'] : '';
        $group = isset($option['group']) ? $option['group'] : '';
        $having= isset($option['having'])? $option['having']: '';
        $fieldCount = isset($option['fieldCount'])? $option['fieldCount']: '';
        $count = parent::join( $join )->field($field)->where($where)->group($group)->having($having)->count($fieldCount);
        if($debug===true){
            parent::debug(true); 
            $lists = parent::join( $join )->field($field)->where($where)->group($group)->order($order)->having($having)->limit($limit)->select();
            var_dump( $lists );
            $sqlError = parent::errors();
            var_dump( $sqlError );
            die;
        }
        $lists = parent::join( $join )->field($field)->where($where)->group($group)->order($order)->having($having)->limit($limit)->select();
        $data = array(
            'page' => $_page,
            'limit' => $_limit,
            'count' => $count,
            'lists' => $lists,
        );
        if (!empty($lists[0])) {
            $res['data'] = $data;
            $res['status'] = 1;
        }
        return $res;
    }
    /**
     * [find 根据条件查找单条记录]
     * @param $table    表单
     * @param array $where
     * @return array
     */
    /*
    public function find($where = [],$order='',$debug=false)
    {
        $res = $this->returnRes();
        if (empty($where) || !is_array($where)){
                return $res;
        }
        if($debug===true){
            parent::debug(true); 
            $rs = parent::where($where)->limit(1)->order($order)->select();
            var_dump( $rs );
            $sqlError = parent::errors();
            var_dump( $sqlError );
            die;
        }
        $rs = parent::where($where)->limit(1)->order($order)->select();
        if (!empty($rs[0])) {
            $res['data'] = $rs[0];
            $res['status'] = 1;
        }
        return $res;
    }
    */
    /**
     * [add 添加数据]
     * @param [type] $table [表名]
     * @param [type] $data  [数据]
     */
    public function add($data,$debug=false)
    {
	        $table = $this->table;
	        $checkData = array();
	        if (isset($data[0]) && is_array($data[0])) {
	                $checkData = $data[0];
	        }else{
	                $checkData = $data;
	        }
	        $resNotNull = $this->notNullData($table, $checkData);
	        if ($resNotNull['status'] != 1) {
	            return $resNotNull;
	        }
	        $resUnique = $this->uniqueData($table, $checkData);
	        if ($resUnique['status'] != 1) {
	            return $resUnique;
	        }
	        $res = $this->returnRes();
	        if($debug===true){
	            parent::debug(true); 
	            $rs = parent::data($data)->insert();
	            var_dump( $rs );
	            $sqlError = parent::errors();
            	var_dump( $sqlError );
	            die;
	        }
	        $rs = parent::data($data)->insert();
	        if ($rs) {
	            $res['status'] = 1;
	            $res['data'] = $rs;
	        }
	        return $res;
    }
    /**
     * [save 根据条件更新数据]
     * @param  [type]  $table  [表名]
     * @param  [type]  $data   [数据 一维关联数组]
     * @param  [array] $where  [条件 一维关联数组 注意:防止泛条件更新]
     * @param  [array] $noSave [无需更新字段]
     * @return [type]  $res    [description]
     */
    public function save($data, $where = [],$debug=false )
    {
        $res = $this->returnRes();
        if (empty($where) || !is_array($where)) {
            return $res;
        }
        $table = $this->table;
        $resNotNull = $this->notNullData($table, $data);
        if ($resNotNull['status'] != 1) {
            $res['status'] = $resNotNull['status'];
            //return $resNotNull;
        }
        $resUnique = $this->uniqueData($table, $data);
        if ($resUnique['status'] != 1) {
            $res['status'] = $resUnique['status'];
            //return $resUnique;
        }
        $tableInfoRs = $this->showTableInfo($table);
        $fieldArr = isset($tableInfoRs['field']) ? $tableInfoRs['field'] : '';
        if (empty($fieldArr)) {
            $res['status'] = -1;
            $res['info'] = 'table field error';
            return $res;
        }
        foreach ($where as $key => $val) {
            if (!in_array($key, $fieldArr)) {
                $res['status'] = -100;   //更新条件字段不存在
                $res['info'] = $key;
                return $res;
            }
            /* //更新条件可以作为更新数据
            if (isset($data[$key])) {
                unset($data[$key]);
            }
            */
        }
        $prikey = isset($tableInfoRs['prikey']) ? $tableInfoRs['prikey'] : '';
        if (!empty($prikey)) {
            unset($data[$prikey]);
        }
        /*
        if (!empty($noSave)) {
            foreach ($noSave as $no_key => $no_val) {
                unset($data[$no_val]);
            }
        }
        */
        if($debug===true){
            parent::debug(true); 
            $rs = parent::data($data)->where($where)->update();
            var_dump( $rs );
            $sqlError = parent::errors();
            var_dump( $sqlError );
            die;
        }
        $rs = parent::data($data)->where($where)->update(); //更新:rs==-1失败,否则成功
        if ($rs>=0) {
            $res['status'] = 1;
            $res['data'] = $rs;
        }
        return $res;
    }
	/**
     * [remove 删除数据]
     * @param array $where
     * @return array
     */
    public function remove($where = [],$debug=false){
        $res = $this->returnRes();
        if (empty($where) || !is_array($where)) {
            return $res;
        }
        if($debug===true){
            parent::debug(true); 
            $rs = parent::data($data)->where($where)->update();
            var_dump( $rs );die;
        }
        $rs = parent::where($where)->delete();
        if ($rs) {
            $res['status'] = 1;
            $res['data']   = $rs;
        }
        return $res;
    }

    /**
     * [createTable 建表模板]
     * @param  [type] $table  [表名]
     * @param  array $field [表字段] [example int(8) NOT NULL PRIMARY KEY AUTO_INCREMENT || tinyint(2) not null default '1' comment '备注']
     * @param  array $option [表选项，引挚，编码，备注]
     * @return [type]         [description]
     */
    public function createTable( )
    {
        /*@param*/
        $argv   = func_get_args();
        $table  = isset( $argv[0] ) ? $argv[0] : '';
        $field  = isset( $argv[1] ) ? $argv[1] : [];
        $option = isset( $argv[2] ) ? $argv[2] : [];
        $debug  = isset( $argv[3] ) ? $argv[3] : [];

        $res = $this->returnRes();
        if (empty($table)) {
            $res['status'] = -11; //建立表表名不能为空
            return $res;
        }
        if (empty($field) || !is_array($field)) {
            $res['status'] = -12; //建立表字段不能为空
            return $res;
        }

        $field_arr = array();
        foreach ($field as $key => $val) {
            $field_arr[] = $key . ' ' . $val;
        }
        $field_str = implode(',', $field_arr);

        $engine  = isset($option['engine']) ? $option['engine'] : 'InnoDB';
        $charset = isset($option['charset']) ? $option['charset'] : 'utf8';
        $comment = isset($option['comment']) ? $option['comment'] : '';

        $sql = " CREATE TABLE IF NOT EXISTS {$table} (
								{$field_str}
						) ENGINE={$engine} DEFAULT CHARSET={$charset} COMMENT='{$comment}';
					";
        $rs = parent::query($sql); 
        if($debug===true){
           var_dump( $sql );
           var_dump( $db->errors() );die;
        }
        if ($rs) {
            $res['status'] = 1;
        }
        return $res;
    }
  /**
     * [setTableIndex 设置表索引]
     * @param [string] $table     [表名]
     * @param [string] $indexname [索引别名]
     * @param array $index [索引字段组]
     */
    public function setTableIndex($table,$indexname, $index = [])
    {
        $res = $this->returnRes();
        if (empty($table)) {
            $res['status'] = -21; //建立索引表名不能为空
            return $res;
        }
        if (empty($indexname)) {
            $res['status'] = -22; //建立索引字段别名不能为空
            return $res;
        }
        if (empty($index) || !is_array($index)) {
            $res['status'] = -23; //建立索引字段不能为空
            return $res;
        }
        //加标符
        $index_arr = array();
        foreach ($index as $val) {
            $index_arr[] = "`" . $val . "`";
        }
        $index_str = implode(',', $index_arr);

        $sql = "ALTER TABLE {$table} ADD INDEX {$indexname}({$index_str})";
        $rs  = parent::query($sql);
        if ($rs) {
            $res['status'] = 1;
        }
        return $res;
    }

        /**
     * [setTableUnique 设置表联合唯一约束]
     * @param [string] $table     [表名]
     * @param [string] $uniquename [索引别名]
     * @param array $unique [索引字段组]
     */
    public function setTableUnique($table,$uniquename, $unique = [])
    {
        $res = $this->returnRes();
        if (empty($table)) {
            $res['status'] = -32; //表名不能为空
            return $res;
        }
        if (empty($uniquename)) {
            $res['status'] = -32; //唯一字段别名不能为空
            return $res;
        }
        if (empty($unique) || !is_array($unique)) {
            $res['status'] = -33; //唯一字段配置不能为空
            return $res;
        }
        //加标符
        $unique_arr = array();
        foreach ($unique as $val) {
            $unique_arr[] = "`" . $val . "`";
        }
        $unique_str = implode(',', $unique_arr);

        $sql = "ALTER TABLE {$table} add constraint {$uniquename} unique({$unique_str})";
        $rs  = parent::query($sql);
        if ($rs) {
            $res['status'] = 1;
        }
        return $res;
    }

     /**
     * [showTableIndex 取得数据表的字段索引信息]
     * @param  [string] $table   [表名]
     * @return [array]  $res['data'] [{'unique_name1':['field1','field2',...],'unique_name2':['field1','field2',...]}]
     */
    private function showTableIndex($table)
    {
        $res = $this->returnRes();
        if (empty($table)) {
            $res['status'] = -1;
            return $res;
        }
        $result = parent::query('SHOW INDEX FROM ' . $table);
        $info = array();
        if ($result) {
            foreach ($result as $key => $val) {
                if (strtoupper($val['Key_name']) == 'PRIMARY') {
                    continue;
                }
                if ($val['Non_unique'] === '0') { //如果索引不能包括重复词,则为0,如果可以则为1。
                    $info[$val['Key_name']][$val['Seq_in_index']] = $val['Column_name'];
                }
            }
        }
        if (!empty($info)) {
            $res['status'] = 1;
            $res['data'] = $info;
        }
        return $res;
    }

     /**
     * [notNullData 数据不为空字段]
     * @param  [type] $table [表名]
     * @param  [type] $data  [写入数据库数据]
     * @return [type] $res   []
     */
    private function notNullData($table, $data )
    {
        $res = $this->returnRes();
        $tableInfoRs = $this->showTableInfo($table);
        if ($tableInfoRs['status'] != '1') {
            return $tableInfoRs;
        }
        //不为空字段
        $notnull_arr = $tableInfoRs['noNull'];
        if (!empty($notnull_arr)) {
            foreach ($notnull_arr as $key => $val) {
                if( $data[$val]!==0&&$data[$val]!=='0'&&empty($data[$val]) ) {
                    $res['status'] = -1;
                    $res['field'] = $val;
                    return $res;
                }
            }
        }
        $res['status'] = 1;
        return $res;
    }
    /**
     * [uniqueData 数据唯一性字段]
     * @param  [type] $table [表名]
     * @param  [type] $data  [数据]
     * @return [type] $res   [description]
     */
    private function uniqueData($table, $data)
    {
        $res = $this->returnRes();
        $tableInfoRs = $this->showTableInfo($table);
        if ($tableInfoRs['status'] != '1') {
            return $tableInfoRs;
        }
        $prikey = $tableInfoRs['prikey'];//主键
        //唯一字段
        $unique_rs = $this->showTableIndex($table);
        $unique_arr = isset($unique_rs['data']) ? $unique_rs['data'] : '';
        if (!empty($unique_arr)) {
            $unique_where = array();
            foreach ($unique_arr as $key => $val) {
                foreach ($val as $field) {
                    if (empty($data[$field])) { //唯一字段不能为空
                        continue;
                        /*
                        $res['status'] = -2;
                        $res['field'] = $field;
                        return $res;
                        */
                    }
                    $unique_where[$key][$field] = $data[$field];
                }
            }
            if (!empty($unique_where)) {
                if (count($unique_where) > 1) {
                    $unique_where['_logic'] = 'or';
                }
                
                $unique_rs = parent::table($table)->field($prikey)->where($unique_where)->limit(1)->select();
                if (isset($unique_rs[0][$prikey])) { //唯一字段已存在重复了。
                    $res['status'] = -3;
                    $res['info'] = $unique_rs[0][$prikey];
                    return $res;
                }
            }
        }
        $res['status'] = 1;
        return $res;
    }

    /**
     * [showTableInfo 取得数据表的字段信息]
     * @param  [string] $table   [表名]
     * @return [array]  $res     [表字段详细信息 $info结果可作缓存]
     */
    private function showTableInfo($table)
    {
        $res = $this->returnRes();
        if (empty($table)) {
            $res['status'] = -1;
            return $res;
        }

        $result = parent::query('SHOW COLUMNS FROM ' . $table);
        $info = array();
        $field = array();
        $prikey = '';
        $noNull = array();
        if ($result) {
            foreach ($result as $key => $val) {
                $info[$val['Field']] = $val;
                $field[] = $val['Field'];
                if (strtoupper($val['Key']) == 'PRI') {
                    $prikey = $val['Field'];
                } elseif (strtoupper($val['Null']) == 'NO') {
                    $noNull[] = $val['Field'];
                }
            }
        }
        if (!empty($info)) {
            $res['status'] = 1;
            $res['data'] = $info;
            $res['field'] = $field;
            $res['prikey'] = $prikey;
            $res['noNull'] = $noNull;
        }
        return $res;
    }

    /**
     * [showTableList 取得数据库的表列表]
     * @param  [string] $dbName     [库名]
     * @return [array] [库中的表列表]
     */
    private function showTableList($dbName = '')
    {
        $res = $this->returnRes();
        if (!empty($dbName))
            $sql = 'SHOW TABLES FROM ' . $dbName;
        else
            $sql = 'SHOW TABLES ';

        $result = parent::query($sql);
        $info = array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        if (!empty($info)) {
            $res['status'] = 1;
            $res['data'] = $info;
        }
        return $res;
    }

    public function statusMsg( $status ){
           $msg = '';
           switch( $status ){
               case '0':
                   $msg = '失败';
                   break;
               case '1':
                   $msg = '成功';
                   break;
               case '-1':
                   $msg = '不能为空';
                   break;
               case '-2':
                   $msg = '唯一字段不能为空';
                   break;
               case '-3':
                   $msg = '已存在记录';
                   break;
               case '-11':
                   $msg = '建立表表名不能为空';
                   break;
               case '-12':
                   $msg = '建立表字段不能为空';
                   break;
               case '-21':
                   $msg = '建立索引表名不能为空';
                   break;
               case '-22':
                   $msg = '建立索引字段别名不能为空';
                   break;
               case '-23':
                   $msg = '建立索引字段不能为空';
                   break;
               case '-31':
                   $msg = '表名不能为空';
                   break;
               case '-32':
                   $msg = '唯一字段别名不能为空';
                   break;
               case '-33':
                   $msg = '唯一字段配置不能为空';
                   break;
               case '-100':
                   $msg = '更新条件字段不存在';
                   break;
               default:
                   $msg = '未知';
                   break;
           }
           return $msg;
    }

}