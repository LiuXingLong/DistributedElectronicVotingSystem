<?php
namespace Tasks\Controller;
set_time_limit(0);
ini_set("memory_limit","256M");
/**
 * 处理Admin的操作添加到分布式库的事务
 * @author tyler.liu
 */
class AdminController extends BaseController 
{	
	// 项目事务处理
	private function transPro($trans)
	{	
		STD( $trans['proid'] )->startTrans();
		$Transaction = MD('transactionpro',$trans['proid']);
		$re = $Transaction->where( array('id'=> $trans['transid']) )
						  ->field('status')->find();
		// 丢弃已经处理过的事务
		if ($re['status']) {
			return false;
		}
		$proid = $trans['proid'];
		$data = $trans['content'];
		$Model = MD('votepro', $proid);
		$res = false;
		if ( $trans['action'] == 1 ) {
			$res = $Model->add($data);
		} else if ($trans['action'] == 2) {
			$res = $Model->where( array('proid' => $proid) )->save($data);
		} else {
			$res = $Model->where( array('proid' => $proid) )->delete();
			// 需要删除候选人
			$HxrModel = MD('votehxr', $proid);
			$re1 = $HxrModel->where( array('proid' => $proid) )->delete();
			if( $re1 === false ){
				$res = false;
			}
			// 需要删除投票人
			$TprModel = MD('votetpr', $proid);
			$re2 = $TprModel->where( array('proid' => $proid) )->delete();
			if( $re2 === false ){
				$res = false;
			}
			// 需要删除投票信息数据
			$InfoModel = MD('voteinfo', $proid);
			$re3 = $InfoModel->where( array('proid' => $proid) )->delete();
			if( $re3 === false ){
				$res = false;
			}
		}
		// 改变数据库事务状态
		$res1 = $Transaction->where( array('id'=> $trans['transid']) )
							->save( array('status' => 1) );
		if ( $res !== false && $res1 !== false ) {
			STD( $trans['proid'] )->commit();			
		} else {
			STD( $trans['proid'] )->rollback();
			$Transaction->where( array('id'=> $trans['transid']) )
						->setInc('count',1);
		}
	}
	// 候选人事务处理
	private function transHxr($trans)
	{
		STD( $trans['proid'] )->startTrans();
		$Transaction = MD('transactionhxr',$trans['proid']);
		$re = $Transaction->where( array('id'=> $trans['transid']) )->field('status')->find();
		// 丢弃已经处理过的事务
		if ($re['status']) {
			return false;
		}
		$proid = $trans['proid'];
		$data = $trans['content'];
		$Model = MD('votehxr', $proid);
		$res = false;
		if ( $trans['action'] == 1 ) {
			$res = $Model->add($data);
			// 项目参赛人加一
			$ProModel = MD('votepro', $proid);
			$res1 = $ProModel->where(array('proid' => $proid))->setInc('hxrnums',1); // 候选人数加一
			if( $res1 === false ){
				$res = false;
			}
		} else if ($trans['action'] == 2) {
			$res = $Model->where( array('proid' => $proid , 'huid' => $data['huid'] ) )->save($data);
		} else {
			$res = $Model->where( array('proid' => $proid , 'huid' => $data) )->delete();
			// 项目参赛人减一
			$ProModel = MD('votepro', $proid);
			$res1 = $ProModel->where(array('proid' => $proid))->setDec('hxrnums',1); // 候选人数减一
			if( $res1 === false ){
				$res = false;
			}
		}
		// 改变数据库事务状态
		$res2 = $Transaction->where( array('id'=> $trans['transid']) )->save( array('status' => 1) );
		if ( $res!== false && $res2 !== false ) {
			STD( $trans['proid'] )->commit();			
		} else {
			STD( $trans['proid'] )->rollback();
			$Transaction->where( array('id'=> $trans['transid']) )->setInc('count',1);
		}	
	}
	// 投票人事务处理
	private function transTpr($trans)
	{
		STD( $trans['proid'] )->startTrans();
		$Transaction = MD('transactiontpr',$trans['proid']);
		$re = $Transaction->where( array('id'=> $trans['transid']) )->field('status')->find();
		// 丢弃已经处理过的事务
		if ($re['status']) {
			return false;
		}
		$proid = $trans['proid'];
		$data = $trans['content'];
		$Model = MD('votetpr', $proid);
		$res = false;
		if ( $trans['action'] == 1 ) {
			$res = $Model->add($data);
		} else if ($trans['action'] == 2) {
			$res = $Model->where( array('proid' => $proid , 'tuid' => $data['tuid'] ) )->save($data);
		} else {
			$res = $Model->where( array('proid' => $proid , 'tuid' => $data) )->delete();
		}
		// 改变数据库事务状态
		$res1 = $Transaction->where( array('id'=> $trans['transid']) )->save( array('status' => 1) );
		if ( $res!== false && $res1 !== false ) {
			STD( $trans['proid'] )->commit();			
		} else {
			STD( $trans['proid'] )->rollback();
			$Transaction->where( array('id'=> $trans['transid']) )->setInc('count',1);
		}
	}
	// 消息队列处理项目事务
	public function transActionPro(){
		while(true){
			$trans = $this->getTransAction('admin_pro_list');
			if (empty($trans)) {
				unset($trans);
				usleep(500000); // 休息0.5秒
				continue;
			}
			$this->transPro($trans);
			unset($trans);
		}
	}
	// 消息队列处理候选人事务
	public function transActionHxr(){
		while(true){
			$trans = $this->getTransAction('admin_hxr_list');
			if (empty($trans)) {
				unset($trans);
				usleep(500000); // 休息0.5秒
				continue;
			}
			$this->transHxr($trans);
			unset($trans);
		}
	}
	// 消息队列处理投票人事务
	public function transActionTpr(){
		while(true){
			$trans = $this->getTransAction('admin_tpr_list');
			if (empty($trans)) {
				unset($trans);
				usleep(500000); // 休息0.5秒
				continue;
			}
			$this->transTpr($trans);
			unset($trans);
		}
	}
	// 数据库补充项目事务
	public function transTasksPro(){
		while(true){
			for ($i = 1; $i <= $this->db_num ; $i++ )
			{
				$databases = str_pad($i,4,"0",STR_PAD_LEFT);
				for($j = 1 ; $j <= $this->tb_num ; $j++ )
				{
					$table = str_pad($j,2,"0",STR_PAD_LEFT);
					$Model = M( $table , 'transaction_pro_', 'DB_TPB_'.$databases );
					$map['status'] = 0;
					$map['w_time'] = array('elt',time() - $this->tasks_delay_time);      // 查找配置时间前没有被处理的数据
					$map['count'] = array('elt',$this->tasks_count) ; // 事务最大补充次数限制
					$res = $Model->where($map)->field('id as transid,proid,content,action')->order('count,w_time')->limit(10)->select();
					if( empty($res) ){
						unset($map);unset($res);unset($table);unset($Model);
						usleep(500000); // 休息0.5秒
						continue;
					}
					foreach( $res as $val ){
						$val['content'] = json_decode($val['content'],true);
						$this->transPro($val);
						unset($val);
					}
					unset($map);unset($res);unset($table);unset($Model);
					sleep(3);
				}
				unset($databases);
			}
		}
	}
	// 数据库补充候选人事务
	public function transTasksHxr(){
		while(true){
			for ($i = 1; $i <= $this->db_num ; $i++ )
			{
				$databases = str_pad($i,4,"0",STR_PAD_LEFT);
				for($j = 1 ; $j <= $this->tb_num ; $j++ )
				{
					$table = str_pad($j,2,"0",STR_PAD_LEFT);
					$Model = M( $table , 'transaction_hxr_', 'DB_TPB_'.$databases );
					$map['status'] = 0;
					$map['w_time'] = array('elt',time() - $this->tasks_delay_time);      // 查找配置时间前没有被处理的数据
					$map['count'] = array('elt',$this->tasks_count) ; // 事务最大补充次数限制
					$res = $Model->where($map)->field('id as transid,proid,content,action')->order('count,w_time')->limit(10)->select();
					if( empty($res) ){
						unset($map);unset($res);unset($table);unset($Model);
						usleep(500000); // 休息0.5秒
						continue;
					}
					foreach( $res as $val ){
						$val['content'] = json_decode($val['content'],true);
						$this->transPro($val);
						unset($val);
					}
					unset($map);unset($res);unset($table);unset($Model);
					sleep(3);
				}
				unset($databases);
			}
		}
	}
	// 数据库补充投票人事务
	public function transTasksTpr(){
		while(true){
			for ($i = 1; $i <= $this->db_num ; $i++ )
			{
				$databases = str_pad($i,4,"0",STR_PAD_LEFT);
				for($j = 1 ; $j <= $this->tb_num ; $j++ )
				{
					$table = str_pad($j,2,"0",STR_PAD_LEFT);
					$Model = M( $table , 'transaction_tpr_', 'DB_TPB_'.$databases );
					$map['status'] = 0;
					$map['w_time'] = array('elt',time() - $this->tasks_delay_time);      // 查找配置时间前没有被处理的数据
					$map['count'] = array('elt',$this->tasks_count) ; // 事务最大补充次数限制
					$res = $Model->where($map)->field('id as transid,proid,content,action')->order('count,w_time')->limit(10)->select();
					if( empty($res) ){
						unset($map);unset($res);unset($table);unset($Model);
						usleep(500000); // 休息0.5秒
						continue;
					}
					foreach( $res as $val ){
						$val['content'] = json_decode($val['content'],true);
						$this->transPro($val);
						unset($val);
					}
					unset($map);unset($res);unset($table);unset($Model);
					sleep(3);
				}
				unset($databases);
			}
		}
	}
}