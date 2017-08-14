## 基于ThinKPHP框架的分布式投票系统

### 服务类型

 * 分布式缓存
 * 消息队列服务
 * 守护进程服务
 * 负载均衡服务器
 * 分布式文件服务

** 服务器  **

 * Redis服务器
 * Ftp文件服务器
 * 负载均衡服务器(Nginx)
 * Web服务器(Nginx)
 * 数据库服务器(Mysql)


### Ftp服务说明
 
 * ftp服务器目录结构  Public/img
 * 关闭 Ftp文件服务器后    启用本地文件服务
 * 关闭方法 ：注释配置文件中的    MY_FTP_HOST 和   FILE_UPLOAD_TYPE 配置
 
### Ftp主要配置说明

 ** 关闭firewall和SELinux **
 * setenforce 0   # 设置SELinux 成为permissive模式  （关闭SELinux）
 * setenforce 1   # 设置SELinux 成为enforcing模式   （开启SELinux）
 * 或者修改配置文件
 * vim /etc/selinux/config
 * # SELINUX=enforcing     # 注释掉
 * # SELINUXTYPE=targeted  # 注释掉
 * # SELINUX=disabled        # 增加
 * :wq! #保存退出
 * setenforce 0

 * getsebool -a | grep ftp
 * setsebool allow_ftpd_full_access on

 ** vsftpd 配置文件路径   /etc/vsftpd/vsftpd.conf  **
 * local_enable=YES                   // 本地用户可登录
 * local_root=/usr/local/nginx/html   // 设置本地用户根目录
 * chroot_local_user=YES              // 限定用户在指定目录下
 * allow_writeable_chroot=YES # 如果启用了限定用户在其主目录下需要添加这个配置，解决报错 500 OOPS: vsftpd: refusing to run with writable root inside chroot()
 * 没办法上传文件夹，但可以创建文件  mkdir 文件夹名     rm 删除文件夹

 * systemctl stop firewalld.service  #停止firewall
 * systemctl disable firewalld.service  #禁止firewall开机启动


### 分布式数据库扩库说明

 * 扩库说明：数量为当前分布式库乘2
 * 如： 当前库为 0001、0002、0003、0004 , 扩库之后的数量应为 8 
 
 ** 扩库方法：**
 * 列如：
 * 修改应用公共配置文件 MY_SCATTER_DB_NUM 值为  8
 * 将所有的分布式库复制一份，然后将复制的分布式库的库号修改为当前扩号加扩库前分布式库的数量
 * 复制一份分布式的库后，将   0001 改为  0005 , 0002 改为  0006 , 0003 改为  0007 , 0004 改为  0008
 * 同时添加   0005 、 0006 、 0007 、0008 的数据库配置信息


### Redis 缓存服务说明

 * 关闭  Redis 服务后, 无法使用 ：缓存服务 、消息队列服务、负载均衡服务（分布式session情况）
 * 关闭后:事务将只能使用本地数据库补充事务处理,且会延迟五分钟补充( 强烈建议开启Redis 服务 )

### 负载均衡配置说明

 * 方法一：
 * nginx 采用 ip_hash 负载均衡 （无需处理session同步问题）
 * 优点：无需对 session 同步处理
 * 缺点： 无法均匀的分布请求到到每台服务器上
 
	upstream myserver {
	   ip_hash;
	   server 192.168.17.4;
	   server 192.168.17.5;
	}

	location / {
	   root   html;
	   index  index.php index.html index.htm;
	   proxy_pass   http://myserver;
	}

 * 方法二：
 * nginx 采用 weigth 负载均衡 （Redis 做 session 同步）
 * 优点：可以均匀的分布请求到到每台服务器上
 * 缺点：需要另外增加对 session 同步处理

	upstream myserver {
	    server 192.168.17.4  weight=1;
	    server 192.168.17.5  weight=1;
	}

	location / {
	    root   html;
	    index  index.php index.html index.htm;
	    proxy_pass   http://myserver;
	}

### 分布式图片文件资源动态反向代理解析 ###
	
	upstream myserver {
	    server 192.168.17.4  weight=1;
	    server 192.168.17.5  weight=1;
	}
	
	location ~* \.(gif|jpg|jpeg)$ {
	    if ($arg_ip ~* ^(\d+.\d+.\d+.\d+)$ ) {
	         proxy_pass http://$1;
	    }
	    proxy_pass   http://myserver;
	}

 
### 守护进程说明

**处理Admin的操作添加到分布式库的事务**

	/usr/local/php/bin/php /usr/local/nginx/html/tpb/cli.php /Tasks/Admin/transActionPro &
	/usr/local/php/bin/php /usr/local/nginx/html/tpb/cli.php /Tasks/Admin/transActionHxr &
	/usr/local/php/bin/php /usr/local/nginx/html/tpb/cli.php /Tasks/Admin/transActionTpr &
	/usr/local/php/bin/php /usr/local/nginx/html/tpb/cli.php /Tasks/Admin/transTasksPro &
	/usr/local/php/bin/php /usr/local/nginx/html/tpb/cli.php /Tasks/Admin/transTasksHxr &
	/usr/local/php/bin/php /usr/local/nginx/html/tpb/cli.php /Tasks/Admin/transTasksTpr &

** 处理Home的操作添加到统计库的事务 **

	/usr/local/php/bin/php /usr/local/nginx/html/tpb/cli.php /Tasks/Home/transActionPro &
	/usr/local/php/bin/php /usr/local/nginx/html/tpb/cli.php /Tasks/Home/transActionInfo &
	/usr/local/php/bin/php /usr/local/nginx/html/tpb/cli.php /Tasks/Home/transTasksPro &
	/usr/local/php/bin/php /usr/local/nginx/html/tpb/cli.php /Tasks/Home/transTasksInfo &







