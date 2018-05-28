<?php
return [
    // 生成应用公共文件
    '__file__' => ['common.php', 'config.php', 'database.php'],

    //公共模块目录
    'common' => [
        '__file__'   => ['common.php'],
        '__dir__'    => ['controller', 'model','lang'],
        'controller' => ['Index'],
        'model'      => ['Base'],
    ],
    // Index模块
    'index'     => [
        '__file__'   => ['common.php'],
        '__dir__'    => ['behavior', 'controller', 'model', 'view','lang'],
        'controller' => ['Index'],
        'model'      => ['Test'],
        'view'       => ['index/index'],
    ],
    // Admin 模块
    'admin'     => [
        '__file__'   => ['common.php'],
        '__dir__'    => ['behavior', 'controller', 'model', 'view','lang'],
        'controller' => ['Index'],
        'model'      => ['Test'],
        'view'       => ['index/index'],
    ],

];