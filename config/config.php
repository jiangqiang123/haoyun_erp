<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/7/25/025
 * Time: 14:04
 */
error_reporting(E_ERROR | E_WARNING | E_PARSE);

return[
    'app_debug'              => true,
    'template'               => [

        /*****模板布局配置start***/
        'layout_on' => false,
        'layout_name' => 'base/base',
        /*****模板布局配置end***/
        // 模板引擎类型 支持 php think 支持扩展
        'type'         => 'Think',
        // 模板路径
        'view_path'    => '',
        // 模板后缀
        'view_suffix'  => 'html',
        // 模板文件名分隔符
        'view_depr'    => DS,
        // 模板引擎普通标签开始标记
        'tpl_begin'    => '{',
        // 模板引擎普通标签结束标记
        'tpl_end'      => '}',
        // 标签库标签开始标记
        'taglib_begin' => '{',
        // 标签库标签结束标记
        'taglib_end'   => '}',
    ],
    // 视图输出字符串内容替换
    'view_replace_str'       => [
        "__PUBLIC__" => "/static",
		"__TIME__"	=>"/static/lib/laydate",
    ],
    //默认错误跳转对应的模板文件
    'dispatch_error_tmpl' => './static/show.html',
    //默认成功跳转对应的模板文件
    'dispatch_success_tmpl' =>'./static/show.html',
    //前端页面网址
    'front_url' => 'http://v.tommmt.com/',

];
