<?php
return array(
	/***  常规配置    ***/
	
	'TMPL_L_DELIM' => '<{', //修改左定界符
	'TMPL_R_DELIM' => '}>', //修改右定界符
	'SHOW_PAGE_TRACE' => false,//开启页面Trace,易于调错
	'DEFAULT_MODULE' =>  'Admin',//开启默认模板
	'URL_MODEL' => 2,
	
	/*** 自定义配置    ***/
		
	'MY_BALANCE_HOST' => 'loveme1234567.oicp.net', // 负载均衡  HOST 用来生成二维码内容链接地址 , 系统搭建了负载均衡后配置为负载均衡的地址， 没有搭建负载均衡配置当前服务器地址
	'MY_SCATTER_TB_NUM' => 10,         // 数据库分表数量   (禁止修改)
	'MY_SCATTER_DB_NUM' => 4,          // 分布式数据库数量  (扩库每次只能乘2,同时调整数据库。    扩库时必须先调整数据库，然后再修改此配置，最高不能超过 1024 )
	'MY_TASKS_COUNT' => 10,            // 事务补充最大次数                     修改后需重启守护进程
	'MY_TASKS_DELAY_TIME' => 300,	   // 事务补充延迟时间(单位秒) 修改后需重启守护进程      
	'MY_VOTE_IP_COUNT' => 10,          // 同一IP一天最多能投票次数
	'MY_REDIS_LIST' => true,           // 消息队列服务bool类型 ,true开启    false关闭      开启后必须开启Rdeis服务
	'MY_REDIS_CACHE' => true,          // 缓存服务bool类型 ,true开启    false关闭      开启后必须开启Rdeis服务
	'MY_FTP_HOST' => '192.168.17.3',   // 文件服务器   HOST 用来提供上传文件     应保持与文件服务器host一致
	'MY_SERVER_ADDR' => $_SERVER['SERVER_ADDR'], // 当前服务器   IP 地址 ,没开ftp服务时,上传文件名的服务器域名为该地址
	
	
	 /*** ftp 上传配置  ***/
	
	'FILE_UPLOAD_TYPE'    =>    'Ftp',
	'UPLOAD_TYPE_CONFIG'  =>    array(
		'host'     => '192.168.17.3', //服务器
		'port'     => 21,    //端口
		'timeout'  => 90,    //超时时间
		'username' => 'ftp', //用户名
		'password' => 'liuxinglong', //密码
	 ),
		
	
     /*** session 配置  ***/
	
    'SESSION_AUTO_START' => true,    // 是否开启session
	'SESSION_TYPE' => 'Redis',       // session保存类型存入Rdeis  必须开启Rdeis服务
	'SESSION_PREFIX' => 'sess_tpb_', // session前缀	
	'SESSION_EXPIRE' => 1800,        // SESSION过期时间
    
	
	/*** redis 配置  ***/
		
	'REDIS_HOST' => '192.168.17.3', //主机
	'REDIS_PORT' => 6379,           //端口
	'REDIS_CTYPE' => 1,             //连接类型 1:普通连接 2:长连接
	'REDIS_TIMEOUT' => 0,           //连接超时时间(S) 0:永不超时

	
     /*** 数据库配置  ***/
     
	// 统计库
    'DB_TPB' => 'mysql://root:root@192.168.17.3:3306/tpb#utf8',
				
	//分布式数据库0001
	'DB_TPB_0001' => array(
		'DB_TYPE'  => 'mysql',
		'DB_USER'  => 'root',
		'DB_PWD'   => 'root',
		'DB_HOST'  => '192.168.17.6',
		'DB_PORT'  => '3306',
		'DB_NAME'  => 'tpb_0001',
		'DB_CHARSET'=> 'utf8',
	),
		
	//分布式数据库0002
	'DB_TPB_0002' => array(
			'DB_TYPE'  => 'mysql',
			'DB_USER'  => 'root',
			'DB_PWD'   => 'root',
			'DB_HOST'  => '192.168.17.6',
			'DB_PORT'  => '3306',
			'DB_NAME'  => 'tpb_0002',
			'DB_CHARSET'=> 'utf8',
	),
		
	//分布式数据库0003
	'DB_TPB_0003' => array(
			'DB_TYPE'  => 'mysql',
			'DB_USER'  => 'root',
			'DB_PWD'   => 'root',
			'DB_HOST'  => '192.168.17.6',
			'DB_PORT'  => '3306',
			'DB_NAME'  => 'tpb_0003',
			'DB_CHARSET'=> 'utf8',
	),
		
	//分布式数据库0004
	'DB_TPB_0004' => array(
			'DB_TYPE'  => 'mysql',
			'DB_USER'  => 'root',
			'DB_PWD'   => 'root',
			'DB_HOST'  => '192.168.17.6',
			'DB_PORT'  => '3306',
			'DB_NAME'  => 'tpb_0004',
			'DB_CHARSET'=> 'utf8',
	),
);