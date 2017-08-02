<?php
namespace Think\Session\Driver;

class Redis {
//  Redis连接对象
	private $redis;
//  Session过期时间
  	private $expire;
   /**
    * 打开方法
    * @param type $path
    * @param type $name
    * @return type
    */
	public function open($path, $name) {
		$this->expire = C('SESSION_EXPIRE') ? C('SESSION_EXPIRE') : ini_get('session.gc_maxLifetime');
		$this->redis = new \Redis();
		return $this->redis->connect(C('REDIS_HOST'), C('REDIS_PORT'));
	}
	/**
	 * 关闭
	 * @return type
	 */
	public function close() {
		return $this->redis->close();
	}
  	/**
  	 * 读取
   	 * @param string $id
     * @return type
     */
	public function read($id) {
		$id = C('SESSION_PREFIX') . $id;
		$data = $this->redis->get($id);
		return $data ? $data : '';
	}
	/**
	 * 写入
	 * @param string $id
	 * @param type $data
	 * @return type
	 */
	public function write($id, $data) {
		$id = C('SESSION_PREFIX') . $id;
		return $this->redis->set($id, $data, $this->expire);
	}
   /**
    * 销毁
    * @param string $id
    */
	public function destroy($id) {
		$id = C('SESSION_PREFIX') . $id;
		$this->redis->delete($id);
	}
   /**
    * 垃圾回收
    * @param type $maxLifeTime
    * @return boolean
    */
	public function gc($maxLifeTime) {
		return true;
	}
}