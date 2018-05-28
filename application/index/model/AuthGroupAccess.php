<?php
namespace app\index\model;
use think\Model;
use app\index\model\AuthAdmin;
/***管理员与用户组联表***/
class AuthGroupAccess extends Model
{
	public function admins()
	{
		return $this->hasOne('AuthAdmin','uid','uid','LEFT')->setEagerlyType(0);
	} 

} 