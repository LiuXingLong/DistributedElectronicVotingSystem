<?php
namespace Home\Controller;

use Think\Controller;

class BaseController extends Controller
{	
	private $Redis;
	protected $IP_Num;
	protected $My_List;
    public function _initialize()
    {
    	$this->My_List = C('MY_REDIS_LIST');
    	$this->IP_Num = C('MY_VOTE_IP_COUNT');   
        if( $this->My_List )
        {
        	Vendor('Redis.RedisClient');
        	$this->Redis = new \RedisClient();
        }
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
    	$Transaction = M('transaction_pro','','DB_TPB');
    	$res = $Transaction->add($data);
    	if( $res !== false && $this->My_List ) {
    		// 加入消息队列
    		$data['transid'] = $res;
    		unset($data['w_time']);
    		$this->Redis->listPush('home_pro_list',json_encode($data));
    	}
    	return $res;
    }
    /**
     * 添加投票信息事务
     * @param $proid   事务项目id
     * @param $content 事务数据内容
     * @param $action  事务类型： 1(新增) 2(修改) 3(删除)
     */
    private function transActionInfo($proid,$content,$action){
    	$data['proid'] = $proid;
    	$data['content'] = json_encode($content);
    	$data['action'] = $action;
    	$data['w_time'] = time();
    	$Transaction = M('transaction_info','','DB_TPB');
    	$res = $Transaction->add($data);
    	if( $res !== false && $this->My_List ){
    		// 加入消息队列
    		$data['transid'] = $res;
    		unset($data['w_time']);
    		$this->Redis->listPush('home_info_list',json_encode($data));
    	}
    	return $res;
    }
    /**
     * 添加事务
     * @param $proid   事务项目id
     * @param $content 事务数据内容
     * @param $table   事务类型对应的表： 1(项目表) 4(信息表)
     * @param $action  事务类型： 1(新增) 2(修改)
     */
    public function addTransAction($proid,$content,$table,$action)
    {
    	switch ($table)
    	{
    		case 1:
    			return $this->transActionPro($proid,$content,$action);
    			break;
    		case 4:
    			return $this->transActionInfo($proid,$content,$action);
    			break;
    		default:
    			return false;
    	}
    }
}