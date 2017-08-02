<?php
/**
 * 移除数组中字段名称的前缀
 * @param string $array_data 数据数组
 * @param string $pre 要去掉前缀
 * @return string|multitype:unknown
 */

function removePre($array_data,$pre){
	if(empty($pre) || empty($array_data))
		return $array_data;
	$res = array();
	$len = strlen($pre);
	foreach ($array_data as $key=>$value){
		if(is_numeric($key)){
			$res[$key] = removePre($value, $pre);
		}else{
			$key = substr($key, $len);
			$res[$key] = $value;
		}
	}
	return $res;
}

/**
 * @param $array     上传文件信息
 * @param $subName   子目录名称 (如 'aaa/222/')
 * @param $saveName  保存文件名(不要加文件格式后缀)
 * @return string    文件保存url
 */
function uploadImg ($array, $subName = null, $saveName = null) {
	header("Content-Type:text/html;charset=utf-8");
	$upload = new \Think\Upload();// 实例化上传类
	$upload->replace   =     true; //覆盖同名文件
	$upload->maxSize   =     20145728 ;// 设置附件上传大小
	$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类	
	//存取路径设置
	$upload->rootPath  =     'Public/img/'; // 设置附件上传根目录
	$upload->savePath  =     ''; // 设置附件上传目录
	$upload->saveExt   =     'jpg'; //保存文件后缀
	//生成子目录
	if( !empty($subName) ) {
		$upload->autoSub = true;
		$upload->subName = substr($subName,0,-1); // 子目录 格式如     'aaa/222/';
	} else {
		$upload->autoSub = false;
	}
	//保存文件名
	if( !empty($saveName) ) {
		$upload->saveName = $saveName;  // 文件名不要加后缀
	}
	// 上传文件
	$info   =   $upload->uploadOne($array);
	$is_ftp = C('MY_FTP_HOST');
	if( !empty($is_ftp) ){
		$filename = __ROOT__."/Public/img/". $subName . $info['savename'].'?ip='.C('MY_FTP_HOST');
	}else{
		$filename = __ROOT__."/Public/img/". $subName . $info['savename'].'?ip='.C('MY_SERVER_ADDR');
	}
	if(!$info) {
		$filename = "error"; // 上传错误提示错误信息
		return $filename;
	}else{
		return $filename; // 上传成功
	}
}

/**
 * 开启ftp服务后,用来将本地生成的二维码上传到ftp服务器上
 * @param $rootpath 保存根目录
 * @param $savepath 保存目录
 * @param $savename 保存文件名(不要加文件格式后缀)
 * @param $local_file_dir 本地文件路径
 */
function ftpUploadImg ($rootpath,$savepath,$savename,$local_file_dir) {
	$config = C('UPLOAD_TYPE_CONFIG');
	if (empty($config)) {
		return '没有配置上传配置信息';
	}		
    $ext = pathinfo($local_file_dir,PATHINFO_EXTENSION);
	if( in_array(strtolower($ext), array('gif','jpg','jpeg','png')) === false ){
		return '上传文件格式非法';
	}
	$file['rootpath'] = $rootpath;  //根目录
	$file['savepath'] = $savepath;  //保存目录
	$file['savename'] = $savename.'.jpg'; // 保存文件名
	$file['tmp_name'] = $local_file_dir;  // 本地文件路径
	$Ftp = new \Think\Upload\Driver\Ftp($config);
	if( $Ftp->checkRootPath($file['rootpath']) === false ) {
		return $Ftp->getError();
	}
	if($Ftp->checkSavePath($file['savepath']) === false){
		return $Ftp->getError();
	}
	if($Ftp->save($file) === false){
		return $Ftp->getError();
	}
	return true;
}

/**
 * 生成项目id
 * 最高支持 1024 库
 * 
 * 扩库说明：数量为当前分布式库乘2
 * 如： 当前库为 0001、0002、0003、0004 , 扩库之后的数量因为 8 
 * 扩库方法：
 * 将所有的分布式库复制一份，然后将复制的分布式库的库号修改为当前扩号加扩库前分布式库的数量
 * 如：复制一份分布式的库后，将   0001 改为  0005 , 0002 改为  0006 , 0003 改为  0007 , 0004 改为  0008
 * 
 */
function getProid() {
	$time = time();
	$db_num = C('MY_SCATTER_DB_NUM');	
    $Db_id = ($time % 1024) + 1;
    $Db_id = ($Db_id % $db_num) + 1;
	$databases = str_pad($Db_id,4,"0",STR_PAD_LEFT);
	$rand = rand(1000000000,9999999999);
	$Tb_id = ($rand % 10) + 1;
	$table = str_pad($Tb_id,2,"0",STR_PAD_LEFT);
	$Proid = $time.$databases.$table.$rand;
	return $Proid;
}

 /**
  * 获取分布式model
  * @param $name   model名称
  * @param $proid  项目id
  */
function MD ($name,$proid) {
	$class = '\Common\Model\\'.ucfirst(strtolower($name)).'Model';
	$model = new $class( $proid );
	return $model->DB;
}
/**
 * 分布式数据库事务model
 *  @param $proid  项目id
 */
function STD ($proid) {
	$class = '\Common\Model\\StartTransModel';
	$model = new $class( $proid );
	return $model->Trans;
}

