<?php
namespace app\index\controller;

use think\Controller;
use think\Session;
use think\Request;
use think\config;

class Wuyong extends Controller
{
    public function index()
	{
		return $this->fetch();
	}



}
