<?php

namespace main;
/*
 *
css
.table-pagination {width:98%;margin-left:1%;margin-top:0.5rem;margin-bottom:0.5rem;font-size: 0.8rem}
.table-pagination a { padding: 5px 12px;display: inline-block;border: 1px solid #ccc;margin-right: 5px;color: #333;}
.table-pagination .current {padding: 5px 12px;margin-right: 5px;font-weight: 700;background: #DBE9F8;border: 1px solid #DBE9F8;}
.table-pagination .dropdown-toggle{border: 1px solid #ddd;padding: 0.4rem 0.2rem;}
.table-pagination .dropup{position: relative;display: inline-block;vertical-align: middle;}
.table-pagination .dropup button:hover{background: #ddd;}
.table-pagination .dropup button.active{background: #ddd;}
.table-pagination .dropup .dropdown-list li{padding:0.5rem;}
.table-pagination .dropup .dropdown-list li:hover{color:red;cursor: pointer;}
.table-pagination .dropup .dropdown-list li.on{background: #ddd;}
.table-pagination .dropdown-list{position: absolute;bottom:100%;left:0;z-index:1000;display:none;float:left;min-width:100%;padding:5px 0;margin:2px 0 0;font-size:0.8rem;list-style:none;background-color:#fff;border:1px solid #ccc;border:1px solid rgba(0,0,0,0.15);border-radius: 4px;-webkit-box-shadow: 0 6px 12px rgba(0,0,0,0.175);box-shadow: 0 6px 12px rgba(0,0,0,0.175);background-clip: padding-box;}
.table-pagination .active .dropdown-list{display:block;}
.table-pagination .page-size{padding-left:0.5rem;padding-right:0.5rem;}
js
//页码选项
$('#content-list').on('click','.table-pagination a',function(){
		var a = $(this);
		var val=a.text();
			reloadtable(val);
});
//分页条数开关
$('#content-list').on('click','.table-pagination .dropdown-toggle',function(){
		var a = $(this),p = a.parent();
			if(a.hasClass('active')){a.removeClass('active');p.removeClass('active');}else{a.addClass('active');p.addClass('active');}
});
$('#content-list').on('click','.table-pagination .dropdown-list li',function(){
		var a  =$(this);
		var val=a.text();
			reloadtable(1,val);
});
 *
 *
 */


class Page{
    public  $firstRow; // 起始行数
    public  $listRows; // 列表每页显示行数
    public  $rowArr;  // 选择每页显示行数
    public  $totalRows; // 总行数
    public  $totalPages; // 分页总页面数
    public  $rollPage   = 5;// 分页栏每页显示的页数
	public  $lastSuffix = true; // 最后一页是否显示总页数
    private $nowPage = 1;

	// 分页显示定制
    /*private $config  = array(
        'header' => '<span class="rows">共 %TOTAL_ROW% 条记录</span>',
        'prev'   => '<<',
        'next'   => '>>',
        'first'  => '1...',
        'last'   => '...%TOTAL_PAGE%',
        'theme'  => '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END%',
    );
    
    private $config  = array(
        'header' => '共<b>%TOTAL_ROW%</b>条记录&nbsp;&nbsp;每页<b>%LIST_ROW%</b>条&nbsp;&nbsp;第<b>%NOW_PAGE%</b>页/共<b>%TOTAL_PAGE%</b>页',
        'prev'   => '上一页',
        'next'   => '下一页',
        'first'  => '首页',
        'last'   => '末页',
        'theme'  => '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%',
    );*/
    private $config  = array(
        'header' => '共<b>%TOTAL_ROW%</b>条记录&nbsp;&nbsp;第<b>%NOW_PAGE%</b>页/共<b>%TOTAL_PAGE%</b>页 &nbsp;&nbsp;每页<b>%LIST_ROW%</b>条&nbsp;',
        'prev'   => '上一页',
        'next'   => '下一页',
        'first'  => '首页',
        'last'   => '尾页',
        'theme'  => '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%',
    );
    

    /**
     * 架构函数
     * @param array $nowPage  当前页
     * @param array $totalRows  总的记录数
     * @param array $listRows  每页显示记录数
     */
    public function __construct($nowPage, $totalRows, $listRows=20,$rowArr=[20,40,60]) {
       // C('VAR_PAGE') && $this->p = C('VAR_PAGE'); //设置分页参数名称
        /* 基础设置 */
        $this->totalRows  = $totalRows; //设置总记录数
        $this->listRows   = $listRows;  //设置每页显示行数
        $this->rowArr     = $rowArr;
        $this->nowPage    = empty($nowPage) ? 1 : intval($nowPage);
        $this->nowPage    = $this->nowPage>0 ? $this->nowPage : 1;
        $this->firstRow   = $this->listRows * ($this->nowPage - 1);
    }

    /**
     * 定制分页链接设置
     * @param string $name  设置名称
     * @param string $value 设置值
     */
    public function setConfig($name,$value) {
        if(isset($this->config[$name])) {
            $this->config[$name] = $value;
        }
    }

    /**
     * 组装分页链接
     * @return string
     */
    public function show() {
        if(0 == $this->totalRows) return '';

        /* 计算分页信息 */

        $this->totalPages =  !empty($this->listRows) ? ceil($this->totalRows / $this->listRows) : 0; //总页数
        if(!empty($this->totalPages) && $this->nowPage > $this->totalPages) {
            $this->nowPage = $this->totalPages;
        }

        /* 计算分页临时变量 */
        $now_cool_page      = $this->rollPage/2;
		$now_cool_page_ceil = ceil($now_cool_page);
		//$this->lastSuffix && $this->config['last'] = $this->totalPages;

        //上一页
        $up_row  = $this->nowPage - 1;
        $up_page = $up_row > 0 ? '<a class="paging prev" href="#" data-page="'.$up_row.'" >' . $this->config['prev'] . '</a>' : '';

        //下一页
        $down_row  = $this->nowPage + 1;
        $down_page = ($down_row <= $this->totalPages) ? '<a class="paging next" href="#" data-page="'.$down_row.'">' . $this->config['next'] . '</a>' : '';

        //第一页
        $the_first = '';
        if($this->totalPages > $this->rollPage && ($this->nowPage - $now_cool_page) >= 1){
            $the_first = '<a class="paging first" href="#" data-page="1" >' . $this->config['first'] . '</a>';
        }

        //最后一页
        $the_end = '';
        if($this->totalPages > $this->rollPage && ($this->nowPage + $now_cool_page) < $this->totalPages){
            $the_end = '<a class="paging end" href="#" data-page="'.$this->totalPages.'" >' . $this->config['last'] . '</a>';
        }

        //数字连接
        $link_page = "";
        for($i = 1; $i <= $this->rollPage; $i++){
			if(($this->nowPage - $now_cool_page) <= 0 ){
				$page = $i;
			}elseif(($this->nowPage + $now_cool_page - 1) >= $this->totalPages){
				$page = $this->totalPages - $this->rollPage + $i;
			}else{
				$page = $this->nowPage - $now_cool_page_ceil + $i;
			}
            if($page > 0 && $page != $this->nowPage){

                if($page <= $this->totalPages){
                    $link_page .= '<a class="paging num" href="#" data-page="'.$page.'">' . $page . '</a>';
                }else{
                    break;
                }
            }else{
                if($page > 0 && $this->totalPages != 1){
                    $link_page .= '<span class="current">' . $page . '</span>';
                }
            }
        }

        $listRows  = '<span class="dropup">';
        $listRows .= '<button type="button" class="dropdown-toggle"><span class="page-size">'.$this->listRows.'</span></button>';
	    if( !empty( $this->rowArr )){
		        $listRows .= '<ul class="dropdown-list">';
		        foreach( $this->rowArr as $r_key => $r_val ){
		        		if( $r_val == $this->listRows ){
		        			$listRows .= '<li class="on">'.$r_val.'</li>';
		        		}else{
		        			$listRows .= '<li>'.$r_val.'</li>';
		        		}
		        			
		        }
		        $listRows .= '</ul>';
	    }
        $listRows .= '</span>';

        //替换分页内容
        $page_str = str_replace(
            array('%HEADER%', '%NOW_PAGE%', '%UP_PAGE%', '%DOWN_PAGE%', '%FIRST%', '%LINK_PAGE%', '%END%', '%TOTAL_ROW%', '%TOTAL_PAGE%', '%LIST_ROW%'),
            array($this->config['header'], $this->nowPage, $up_page, $down_page, $the_first, $link_page, $the_end, $this->totalRows, $this->totalPages, $listRows),
            $this->config['theme']);
        return "{$page_str}";
    }
}