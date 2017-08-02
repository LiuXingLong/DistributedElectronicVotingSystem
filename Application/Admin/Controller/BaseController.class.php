<?php
namespace Admin\Controller;

use Think\Controller;

class BaseController extends Controller
{	
	private $Redis;
	protected $My_List;
	protected $My_Cache;
    //初始化入口，所以访问必须先登陆
    public function _initialize()
    {
        if (!session('username'))
        {
            $this->redirect ("Login/index" );
        }
        $this->My_List = C('MY_REDIS_LIST');
        $this->My_Cache = C('MY_REDIS_CACHE');
        if( $this->My_List || $this->My_Cache )
        {
        	Vendor('Redis.RedisClient');
        	$this->Redis = new \RedisClient();
        }
    }
    
    protected function returnAjax($status = true, $info = "", $content = null)
    {
    	$info = $status ? (empty ($info) ? "操作成功" : $info) : (empty ($info) ? "操作失败" : $info);
    	$this->ajaxReturn(array(
    			"content" => $content,
    			"info" => $info,
    			"status" => $status
    	), "json");
    }
    // 删除缓存
    private function delCache($key){
    	return $this->Redis->delete($key);
    }
    // 添加缓存
    protected function setCache($key,$value){
    	return $this->Redis->set($key, json_encode($value));
    }
    // 获取缓存
    protected function getCache($key){
    	$data = $this->Redis->get($key);
    	return json_decode($data,true);
    }
    /**
     * 添加项目事务
     * @param $proid   事务项目id
     * @param $content 事务数据内容
     * @param $action  事务类型： 1(新增) 2(修改) 3(删除)
     */
    private function transActionPro($proid,$content,$action){
    	$data['proid'] = $proid;
    	$data['content'] = json_encode($content);
    	$data['action'] = $action;
    	$data['w_time'] = time();
    	$Transaction = MD('transactionpro',$proid);
    	$res = $Transaction->add($data);
    	// 数据发生改变加入消息队列
    	if( $res !== false && $this->My_List ) {
    		$data['transid'] = $res;
    		unset($data['w_time']);
    		$this->Redis->listPush('admin_pro_list',json_encode($data));	
    	}
    	// 数据发生改变清空缓存
    	if( $res !== false && $this->My_Cache ) {
    		$this->delCache('admin_'.session('adminid').'_pro');
    		$this->delCache('admin_'.session('adminid').'_pro_tree');
    		if( $action == 3 ) {
    			$this->delCache('admin_'.$proid.'_hxr');
    			$this->delCache('admin_'.$proid.'_tpr');
    		}
    	}
    	return $res;
    } 
    /**
     * 添加候选人事务
     * @param $proid   事务项目id
     * @param $content 事务数据内容
     * @param $action  事务类型： 1(新增) 2(修改) 3(删除)
     */
    private function transActionHxr($proid,$content,$action){
    	$data['proid'] = $proid;
    	$data['content'] = json_encode($content);
    	$data['action'] = $action;
    	$data['w_time'] = time();
    	$Transaction = MD('transactionhxr',$proid);
    	$res = $Transaction->add($data);
    	// 数据发生改变加入消息队列
    	if( $res !== false && $this->My_List ) {
    		$data['transid'] = $res;
    		unset($data['w_time']);
    		$this->Redis->listPush('admin_hxr_list',json_encode($data));
    	}
    	// 数据发生改变清空缓存
    	if( $res !== false && $this->My_Cache ) {
    		$this->delCache('admin_'.$proid.'_hxr');
    		if( $action != 2 ) {
    			// 项目的候选人数量发生变法,如果项目的候选人有缓存的话需要清缓存
    		}
    	}
    	return $res;
    }
    /**
     * 添加投票人事务
     * @param $proid   事务项目id
     * @param $content 事务数据内容
     * @param $action  事务类型： 1(新增) 2(修改) 3(删除)
     */
    private function transActionTpr($proid,$content,$action){
    	$data['proid'] = $proid;
    	$data['content'] = json_encode($content);
    	$data['action'] = $action;
    	$data['w_time'] = time();
    	$Transaction = MD('transactiontpr',$proid);
    	$res = $Transaction->add($data);
    	// 数据发生改变加入消息队列
    	if( $res !== false && $this->My_List ) {
    		$data['transid'] = $res;
    		unset($data['w_time']);
    		$this->Redis->listPush('admin_tpr_list',json_encode($data));
    	}
    	// 数据发生改变清空缓存
    	if( $res !== false && $this->My_Cache ) {
    		$this->delCache('admin_'.$proid.'_tpr');
    	}
    	return $res;
    }
    /**
     * 生成二维码
     * [
     * 	'value'  => 生成二维的内容
     *  'qrcode' => 生成二维码存放的路径位置
     * ]
     */
    public function qrcode($value,$qrcode){
    	vendor('phpqrcode.phpqrcode');
    	$errorCorrectionLevel = 'L';   //容错级别
    	$matrixPointSize = 8;         //生成图片大小片
    	\QRcode::png($value, $qrcode, $errorCorrectionLevel, $matrixPointSize, 2);
    }
    
    /**
     * 添加事务
     * @param $proid   事务项目id
     * @param $content 事务数据内容
     * @param $table   事务类型对应的表： 1(项目表) 、 2(候选人表) 、 3(投票人表)
     * @param $action  事务类型： 1(新增) 2(修改) 3(删除)
     */
    public function addTransAction($proid,$content,$table,$action)
    {
    	switch ($table)
    	{
    		case 1:
    			return $this->transActionPro($proid,$content,$action);
    			break;
    		case 2:
    			return $this->transActionHxr($proid,$content,$action);
    			break;
    		case 3:
    			return $this->transActionTpr($proid,$content,$action);
    			break;
    		default:
    			return false;
    	}
    }
}