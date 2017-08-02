<?php
namespace Tasks\Controller;

use Think\Controller;

class BaseController extends Controller
{
	private $Redis;
	protected $tb_num;
	protected $db_num;
	protected $My_List;
	protected $My_Cache;
	protected $tasks_count;
	protected $tasks_delay_time;
	
	public function _initialize()
	{	
		$this->My_List = C('MY_REDIS_LIST');
		$this->My_Cache = C('MY_REDIS_CACHE');
		$this->db_num = C('MY_SCATTER_DB_NUM');
		$this->tb_num = C('MY_SCATTER_TB_NUM');
		$this->tasks_count = C('MY_TASKS_COUNT');
		$this->tasks_delay_time = C('MY_TASKS_DELAY_TIME');
		if( $this->My_List || $this->My_Cache )
		{
			Vendor('Redis.RedisClient');
			$this->Redis = new \RedisClient();
		}
	}
	// 删除缓存
	protected function delCache($key){
		return $this->Redis->delete($key);
	}
	/**
	 * 获取消息队列中的信息
	 * @param $key 队列 key
	 */
	public function getTransAction($key) {
		$res = false;
		if($this->My_List){
			$res = json_decode($this->Redis->listPop($key),true);
			if( !empty($res) ){
				$res['content'] = json_decode($res['content'],true);
			}
		}
		return $res;
	}
}