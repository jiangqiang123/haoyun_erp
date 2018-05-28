<?php
/*****前端独立配置文件*****/
return[
    "m_name"=>"SmallAdmin",
    //超管
    "AUTH_SUPERADMIN"  => "admin",
    //不需要验证的模块
    'NOT_AUTH_MODULE' =>'Index',

    //小组长角色对应的group_id
    "AUTH_GROUP"    => 6, 

   "session"=>[

   		'id' => '',
   		'var_session_id' =>'',
   		'expire'=> 2*60*60,
   		'prefix'=>'smallv',
   		'type'=>'',
   		'auto_start' =>true,
   		'httponly' => true,
   		'secure' => false,
   			]
];