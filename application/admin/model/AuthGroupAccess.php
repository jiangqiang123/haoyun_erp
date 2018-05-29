<?php
namespace app\admin\model;
use think\Model;
use app\admin\model\AuthAdmin;
/***管理员与用户组联表***/
class AuthGroupAccess extends Model
{
	public function admins()
	{
		return $this->hasOne('AuthAdmin','uid','uid','LEFT')->setEagerlyType(0);
	} 

} 