<?php
namespace Tasks\Controller;
set_time_limit(0);
ini_set("memory_limit","256M");
/**
 * 处理Home的操作添加到统计库的事务
 * @author tyler.liu
 */
class HomeController extends BaseController
{
	// 项目事务处理
	private function transPro($trans)
	{
		M('','','DB_TPB')->startTrans();
		$Transaction = M('transaction_pro','','DB_TPB');
		$re = $Transaction->where( array('id'=> $trans['transid']) )->field('status')->find();
		// 丢弃已经处理过的事务
		if ($re['status']) {
			return false;
		}
		$proid = $trans['proid'];
		$data = $trans['content'];
		$Model = M('votepro','', 'DB_TPB');
		$res = false;
		// 分布式只存在产生项目修改事务,不存在添加 和 删除
		if ($trans['action'] == 2) {
			$res = $Model->where( array('proid' => $proid) )->save($data);
		}
		// 改变数据库事务状态
		$res1 = $Transaction->where( array('id'=> $trans['transid']) )->save( array('status' => 1) );
		if ( $res !== false && $res1 !== false ) {
			M('','','DB_TPB')->commit();
		} else {
			M('','','DB_TPB')->rollback();
			$Transaction->where( array('id'=> $trans['transid']) )->setInc('count',1);
		}
	}
	// 信息事务处理
	private function transInfo($trans)
	{
		M('','','DB_TPB')->startTrans();
		$Transaction = M('transaction_info','','DB_TPB');
		$re = $Transaction->where( array('id'=> $trans['transid']) )->field('status')->find();
		// 丢弃已经处理过的事务
		if ($re['status']) {
			return false;
		}
		$proid = $trans['proid'];
		$data = $trans['content'];
		$type = $data['type'];
		// 分布式信息只存在产生添加事务
    	$Model =  M('voteinfo','', 'DB_TPB');
		$res = false;
		if ( $trans['action'] == 1 ) {
			unset($data['type']);
			$res = $Model->add($data);
			// 检测是否需要将投票人日投量设为 0 
			$TprModel =  M('votetpr','', 'DB_TPB');
			if ( $type ) {
				$rs = $TprModel->where(array('proid' => $proid, 'tuid' => $data['tuid'] ))->save( array('daynums' => 0) );
				if( $rs === false ) {
					$res = false;
				}
			}
			// 项目总投票数加一
			$ProModel = M('votepro','', 'DB_TPB');
			$res1 = $ProModel->where(array('proid' => $proid))->setInc('votesum',1);
			// 候选人票数加一
			$HxrModel = M('votehxr','', 'DB_TPB');
			$res2 = $HxrModel->where( array('proid' => $proid ,'huid' => $data['huid'] ) )->setInc('daynums',1);
            // 投票人日投量加一,更新最后投票时间
			$res3 = false;
			$Tprdata = $TprModel->where(array('proid' => $proid, 'tuid' => $data['tuid'] ))->find();
			if(!empty( $Tprdata )){
				$data1 = array();
				$data1['votetime'] = $data['w_time'];
				$data1['daynums'] = $Tprdata['daynums'] + 1;
				$res3 = $TprModel->where(array('proid' => $proid, 'tuid' => $data['tuid'] ))->save($data1);
			}
		}
		// 改变数据库事务状态
		$res4 = $Transaction->where( array('id'=> $trans['transid']) )->save( array('status' => 1) );
		// 投票后投票人、候选人、项目 数据都发生改变,需要清空后台管理的缓存数据
		$rs1 = $rs2 = $rs3 = $rs4 = true;
		if( $this->My_Cache ) {
			$rs1 = $ProModel->where(array('proid' => $proid))->field('adminid')->find();
			$rs2 = $this->delCache('admin_'.$rs1['adminid'].'_pro');
			$rs3 = $this->delCache('admin_'.$proid.'_hxr');
			$rs4 = $this->delCache('admin_'.$proid.'_tpr');
		}
		if( $res !== false && $res1 !== false && $res2 !== false && $res3 !== false && $res4 !== false && $rs1 !== false && $rs2 !== false && $rs3 !== false && $rs4 !== false ) {
			M('','','DB_TPB')->commit();
		} else {
			M('','','DB_TPB')->rollback();
			$Transaction->where( array('id'=> $trans['transid']) )->setInc('count',1);
		}
	}
	// 消息队列处理项目事务
	public function transActionPro(){
		while(true){
			$trans = $this->getTransAction('home_pro_list');
			if (empty($trans)) {
				unset($trans);
				usleep(500000); // 休息0.5秒
				continue;
			}
			$this->transPro($trans);
			unset($trans);
		}
	}
	// 消息队列处理信息事务
	public function transActionInfo(){
		while(true){
			$trans = $this->getTransAction('home_info_list');
			if (empty($trans)) {
				unset($trans);
				usleep(500000); // 休息0.5秒
				continue;
			}
			$this->transInfo($trans);
			unset($trans);
		}
	}
	// 数据库补充项目事务
	public function transTasksPro(){
		while(true){
			$Model = M('transaction_pro','','DB_TPB');
			$map['status'] = 0;
			$map['w_time'] = array('elt',time() - $this->tasks_delay_time);      // 查找配置时间前没有被处理的数据
			$map['count'] = array('elt',$this->tasks_count) ; // 事务最大补充次数限制
			$res = $Model->where($map)->field('id as transid,proid,content,action')->order('count,w_time')->limit(10)->select();
			if( empty($res) ){
				unset($map);unset($res);unset($Model);
				usleep(500000); // 休息0.5秒
				continue;
			}
			foreach( $res as $val ){
				$val['content'] = json_decode($val['content'],true);
				$this->transPro($val);
				unset($val);
			}
			unset($map);unset($res);unset($Model);
			sleep(3);	
		}
	}
	// 数据库补充投票信息事务
	public function transTasksInfo(){
		while(true){
			$Model = M('transaction_info','','DB_TPB');
			$map['status'] = 0;
			$map['w_time'] = array('elt',time() - $this->tasks_delay_time);      // 查找配置时间前没有被处理的数据
			$map['count'] = array('elt',$this->tasks_count) ; // 事务最大补充次数限制
			$res = $Model->where($map)->field('id as transid,proid,content,action')->order('count,w_time')->limit(10)->select();
			if( empty($res) ){
				unset($map);unset($res);unset($Model);
				usleep(500000); // 休息0.5秒
				continue;
			}
			foreach( $res as $val ){
				$val['content'] = json_decode($val['content'],true);
				$this->transInfo($val);
				unset($val);
			}
			unset($map);unset($res);unset($Model);
			sleep(3);
		}
	}
}