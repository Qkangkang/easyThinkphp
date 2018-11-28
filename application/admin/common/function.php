<?php
/**
 * 整理菜单住方法
 * @param $param
 * @return array
 */
function prepareMenu($param)
{
	$parent = []; //父类
	$child = [];  //子类
	
	foreach($param as $key=>$vo){
		
		if($vo['prant_id'] == 0){
			$vo['href'] = '#';
			$parent[] = $vo;
		}else{
			$vo['href'] = U($vo['controller'] .'/'. $vo['action']); //跳转地址
			$child[] = $vo;
		}
	}

	foreach($parent as $key=>$vo){
		foreach($child as $k=>$v){

			if($v['prant_id'] == $vo['id']){
				$parent[$key]['child'][] = $v;
			}
		}
	}
	unset($child);
	return $parent;
}


function getTree($data, $pId,$qianzui=false,$floor = 1) {
	$tree = null;
	$qianzui_str='';
	foreach ( $data as $k => $v ) {
		if ($v ['prant_id'] == $pId) { // 父亲找到儿子
			$qianzui!==false && $qianzui_str=str_repeat($qianzui,$floor);
			$v ['title']=$qianzui_str.$v ['title'];
			$v ['status'] = $v ['status'] ? '显示' : '隐藏' ;
			$v ['iscomuser'] = $v ['iscomuser'] ? '是' : '否' ;
			$v ['floor'] = $floor;
			$v ['subnav'] = getTree ( $data, $v ['id'],$qianzui,$floor + 1 );
			$tree [] = $v;
			// unset($data[$k]);
		}
	}
	return $tree;
}
//获取分组树
function menuTree(&$list,$pid=0,$level=0,$html='--',$id='id',$pdataid='parent'){
	static $tree = array();
	if(count($list)>0){
		foreach($list as $v){
			if($v[$pdataid] == $pid){
				$v['floor'] = $level;
				$v['html'] = str_repeat($html,$level);
				$tree[] = $v;
				menuTree($list,$v[$id],$level+1,$html,$id,$pdataid);
			}
		}
	}
	return $tree;
}


/**
 * 后台 设置格式化
 *
 * @param unknown $data
 */
function setting_format($data)
{
	$config = $data;
	$html = '';
	$html = '<label class="layui-form-label">' . $config['name'] . '</label>';
	switch ($config['type']) {
		case 'upload':
			$html .= '<div class="layui-input-inline">';
			$html .= '<input name="' . $config['key'] . '" id="' . $config['key'] . '" value="' . $config['value'] . '" class=" input-text" type="hidden">';
			if ($config['value'] == '') {
				$html .= '<img src="show_' . $config['key'] . '" style="max-height:40px; max-width:80px;display:none;"  />';
			} else {
				$html .= '<img src="show_' . $config['key'] . '" style="max-height:40px; max-width:80px;"  />';
			}
			$html .= '<input  type="button" id="upbtn_' . $config['key'] . '" class="btn layui-btn layui-btn-primary" value="上传' . $config['name'] . '" />';

			//$config['descr'] && $html .= '<i class="key_descr">' . $config['descr'] . '</i>';

			$html .= '<script type="text/javascript">';
			$html .= '$(function () {';
			$html .= '$("#upbtn_' . $config['key'] . '").uploader({';
			$html .= 'input_id:"' . $config['key'] . '"';
			$html .= '    });';
			$html .= '});';
			$html .= '</script>';
			$html .= '</div>';
			break;
		case 'radio':
			$html .= '<div class="layui-input-inline">';
			$arr = unserialize($config['type_value']);
			foreach ($arr as $key => $val) {
				$check_str = $val['val'] == $config['value'] ? ' checked="checked" ' : '';
				$html .= '<input name="' . $config['key'] . '" type="radio" value="' . $val['val'] . '" ' . $check_str . ' title=' . $val['tit'] . ' />';
			}
			//$config['descr'] && $html .= '<i class="key_descr">' . $config['descr'] . '</i>';
			$html .= '</div>';
			break;
		case 'checkbox':
			$html .= '<div class="layui-input-inline">';
			$arr = unserialize($config['type_value']);
			foreach ($arr as $key => $val) {
				$check_str = $val['val'] == $config['value'] ? ' checked="checked" ' : '';
				$html .= '<input name="' . $config['key'] . '" type="checkbox" value="' . $val['val'] . '" ' . $check_str . ' title=' . $val['tit'] . ' />';
			}
			//$config['descr'] && $html .= '<i class="key_descr">' . $config['descr'] . '</i>';

			$html .= '</div>';
			break;
		case 'select':
			$html .= '<div class="layui-input-inline">';
			$html .= '<select name="' . $config['name'] . '" class="select" lay-verify="">';
			$arr = unserialize($config['type_value']);
			foreach ($arr as $key => $val) {
				$check_str = $val['val'] == $config['value'] ? ' selected="selected" ' : '';
				$html .= ' <option value="' . $val['val'] . '" ' . $check_str . '>' . $val['tit'] . '</option>';
			}
			$html .= '</select>';
			//$config['descr'] && $html .= '<div class="layui-form-mid layui-word-aux">' . $config['descr'] . '</div>';
			$html .= '</div>';
			break;
		case 'textarea':
			$html .= '<div class="layui-input-block">';
			$html .= '<textarea id="' . $config['key'] . '" name="' . $config['key'] . '"  class="layui-textarea"  style=" height: 100px;">' . $config['value'] . '</textarea>';
			//$config['descr'] && $html .= '<label class="key_descr">' . $config['descr'] . '</i>';
			$html .= '</div>';

			break;
		default:
			$html .= '<div class="layui-input-inline">';
			$html .= '<input name="' . $config['key'] . '" class="layui-input input-text" value="' . $config['value'] . '" type="text">';
			//$config['descr'] && $html .= '<i class="key_descr">' . $config['descr'] . '</i>';
			$html .= '</div>';
			 
	}
	
	$config['descr'] && $html .= '<div class="layui-form-mid layui-word-aux">' . $config['descr'] . '</div>';
	
	return '<div class="layui-form-item setting_key" data-settingid="'.$config['keyid'].'">'.$html.'</div>';;
}
