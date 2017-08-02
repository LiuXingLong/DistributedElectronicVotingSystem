<?php
namespace Home\Controller;

// 投票控制器
class IndexController extends BaseController {
	/**
	 * 前置函数用来检测项目id是否正确
	 */
	public function _before_() {
		if ( !session('proid') ) {
			$this->error("非法操作！");
			die;
		} else {
			$proid = session('proid');
			$ProModel = MD('votepro',$proid);
			$res = $ProModel->where(array('proid' => $proid))->field('votename as name,voterule as rule,startime,endtime,homepic,picdes,voteway,votenums,reward,hxrnums,votesum,visitnum')->find();
			$res['way'] = $res['voteway'];
			if ( $res['voteway'] ) {
				$res['voteway'] = '投票方式:每日限投'.$res['votenums'].'票 (可重复投给一个人)';
			} else {
				$res['voteway'] = '投票方式:每日限投'.$res['votenums'].'票 (不可重复投给一个人)';
			}
			return $res;
		}
	}
	/**
	 * 投票主页面
	 * @param
	 * [
	 * 	'pid' => null | 项目id
	 * ]
	 */
    public function index() {
    	$proid = I('proid');
    	if ($proid)
    	{
    		$ProModel = MD('votepro',$proid);
    		$res = $ProModel->where(array('proid' => $proid ))->field('proid,startime,endtime,templetid,visitnum,status')->find();
    		if ($res) {
    			// 访问量加一
    			STD($proid)->startTrans();
    			$rs = $ProModel->where(array('proid' => $res['proid']))->setInc('visitnum',1); 
    			$data['visitnum'] = $res['visitnum'] + 1;
    			if( $rs!== false && $this->addTransAction($proid, $data, 1, 2) !== false ) {
    				STD( $proid )->commit();
    			} else {
    				STD( $proid )->rollback();
    			}
    			session('proid', $res['proid']);
    			session('status', $res['status']); // 活动状态
    			session('endtime',$res['endtime']);
    			session('startime',$res['startime']);
    			if ($res['templetid'] == '模板一') {
    				session('templetid', 'v1');
    			}
    			else if($res['templetid'] == '模板二') {
    				session('templetid', 'v2');
    			}
    			else if($res['templetid'] == '模板三') {
    				session('templetid', 'v3');
    			}	
    			else {
    				session('templetid', 'v4');
    			}
    			$this->redirect('Index/index');
    		} else {
    			exit('项目不存在');
    			//$this->error('项目不存在');
    		}
    	}
    	else
    	{
    		$Project = $this->_before_();
    		$proid = session('proid');
    		$num = 30;
    		$pg = I('page');
    		$lm = I('limit');
    		$HxrModel = MD('votehxr',$proid);
    		if ( $lm ) {
    			$limit = $pg*$lm+$num.','.$lm;
    			$data = $HxrModel->where(array('proid' => $proid))->order('huid')->limit($limit)->field('huid,perpic,lables,personame,daynums')->select();
    			if($data){
    				$User['data'] = $data;
    				$User['num'] = count($data);
    			}else{
    				$User['data'] = null;
    				$User['num'] = 0;
    			}
    			$this->ajaxReturn($User);
    		} else {
    			$count = $HxrModel->where(array('proid' => $proid))->count();
    			$page = new \Think\Page($count,$num);
    			$limit = $page->firstRow.','.$page->listRows;
    			$User = $HxrModel->where(array('proid' => $proid))->order('huid')->limit($limit)->select();
    			$page->setConfig('prev','上一页');
    			$page->setConfig('next','下一页');
    			$this->assign('Page',$page->show());
    			$this->assign('Project',$Project);
    			$this->assign('User',$User);
    			$tpl = session('templetid');
    			$this->display($tpl.'/index');
    		}
    	}	
    }
    /**
     * 排行榜页面
     */
	public function ranKingList() {
		$Project = $this->_before_();
		$proid = session('proid');
		$num = 30;
		$HxrModel = MD('votehxr',$proid);
		$count = $HxrModel->where(array('proid' => $proid))->count();
		$page = new \Think\Page($count,$num);
		$limit = $page->firstRow.','.$page->listRows;
		$User = $HxrModel->where(array('proid' => $proid))->order('daynums desc,huid')->limit($limit)->select();
		$page->setConfig('prev','上一页');
		$page->setConfig('next','下一页');
		$this->assign('FirstRow',$page->firstRow);
		$this->assign('Page',$page->show());
		$this->assign('Project',$Project);
		$this->assign('User',$User);
		$tpl = session('templetid');
		$this->display($tpl.'/ranklist');
	}
	/**
	 * top榜排名页面
	 */
	 public function ranKingTop() {
	 	$Project = $this->_before_();
	 	$proid = session('proid');
	 	$HxrModel = MD('votehxr',$proid);
	 	$User = $HxrModel->where(array('proid' => $proid))->order('daynums desc,huid')->limit(30)->select();
	 	$this->assign('Project',$Project);
	 	$this->assign('User',$User);
	 	$tpl = session('templetid');
	 	$this->display($tpl.'/ranktop');
	 }
	/**
	 * 搜索候选人页面
	 * @param
	 * [
	 *  'keyword' => 候选人huid | 候选人姓名
	 * ]
	 */
    public function search() {
    	if(IS_POST){
    		$Project = $this->_before_();
    		$huid = I('keyword');
    		if(!$huid){
    			echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>';
    			echo '<script charset="utf-8" type="text/javascript">alert("查询关键词不能为空！");window.history.back(-1);</script>';
    			die;
    		}
    		$proid = session('proid');
    		$where['huid|personame'] = $huid;
    		$map['_complex'] = $where;
    		$map['proid'] = $proid;
    		$HxrModel = MD('votehxr',$proid);
    		$User = $HxrModel->where($map)->field('huid,perpic,personame,lables,daynums')->select();
    		if($User){
    			$this->assign('Project',$Project);
    			$this->assign('User',$User);
    			$tpl = session('templetid');
    			$this->display($tpl.'/search');
    		}else{
    			echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>';
    			echo '<script charset="utf-8" type="text/javascript">alert("该用户不存在！");window.history.back(-1);</script>';
    			die;
    		}
    	} else {
    		$this->error('非法操作！');
    	}
    }
    /**
     * 候选人详细信息页面
     * @param
	 * [
	 *  'huid' => 候选人huid
	 * ]
     */
    public function userInfo() {
    	$Project = $this->_before_();
    	$huid = I('huid');
    	if (!$huid) {
    		echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>';
    		echo '<script charset="utf-8" type="text/javascript">alert("参数错误！");window.history.back(-1);</script>';
    		die;
    	} else {
    		$proid = session('proid');
    		$map['huid'] = $huid;
    		$map['proid'] = $proid;
    		$HxrModel = MD('votehxr',$proid);
    		$User = $HxrModel->where($map)->field('huid,perpic,personame,lables,brifintro,daynums')->find();
    		if($User){
    			$map = null;
    			$map['proid'] = $proid;
    			$map['_string'] = '(huid < ' .$huid. ' AND daynums = ' .$User['daynums']. ') OR (daynums > ' .$User['daynums']. ')';
    			$User['rank'] = $HxrModel->where($map)->count();
    			$User['rank']++;
	    		$this->assign('Project',$Project);
	    		$this->assign('User',$User);
	    		$tpl = session('templetid');
	    		$this->display($tpl.'/info');
    		}else{
    			echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>';
    			echo '<script charset="utf-8" type="text/javascript">alert("该用户不存在！");window.history.back(-1);</script>';
    			die;
    		}
    	}
    }
    /**
     * 投票用户身份验证
     * @param
     * [
     * 	'personame' => 投票人姓名
     *  'joinflag'  => 投票用户身份证后六位
     * ]
     * @return true|string 验证通过返回true 否则返回 错误信息
     */
    public function Verify(){
    	if (IS_POST) {
    		$this->_before_();
    		$proid = session('proid');
    		$joinflag = I('joinflag');
    		$personame = I('personame');
    		if (empty($personame)) {
    			exit('用户姓名不能为空！');
    		}
    		if (empty($joinflag)) {
    			exit('身份证后六位不能为空！');
    		}
    		$map['proid'] = $proid;
    		$map['joinflag'] = $joinflag;
    		$map['personame'] = $personame;
    		$TprModel = MD('votetpr',$proid);
    		$res = $TprModel->where($map)->find();
    		if ( $res ) {
    			session('tuid', $res['tuid']);
    			session('joinflag',$res['joinflag']);
    			exit(true);
    		} else {
    			exit('身份信息验证失败！');
    		}
    	} else {
    		$this->error('非法操作！');
    	}
    }
    /**
     * 点击投票
     * @param
     * [
     * 	'hxrid' => 候选人id
     * ]
     * @return true|0|string   投票成功 true  未验证身份 0  失败返回错误信息
     */
    public function clickVote(){
    	if (IS_POST) {
    		$Prodata = $this->_before_();
    		$tuid = session('tuid');
    		if (empty($tuid)) {
    			exit('0');  // 没有验证身份
    		}
    		$huid = I('hxrid');
    		$proid = session('proid');
    		if (empty($huid)) {
    			exit('所投用户不能为空！');
    		}
    		$time = date('Y-m-d H:i:s',time());
    		if ( $time < session('startime') ) {
    			exit('投票还未开始！');
    		} elseif( $time > session('endtime') ) {
    			exit('投票已经结束！');
    		} 
    		if( session('status') == 0 ){
    			exit('活动已关闭无法投票！');
    		}
    		$HxrModel = MD('votehxr',$proid);
    		$Hxrdata = $HxrModel->where( array('proid' => $proid ,'huid' => $huid) )->find();
    		if (empty($Hxrdata)) {
    			exit('所投用户不存在！');
    		}
    		if( $Hxrdata['status'] == 0 ){
    			exit('无法为此用户投票，可能出于审核中或已被屏蔽！');
    		}
    		// 检测IP投票次数是否超过
    		$InfoModel = MD('voteinfo',$proid);
    		$map = null;
    		$map['proid'] = $proid;
    		$map['w_time'] = date('Y-m-d',time());
    		$map['ip'] = get_client_ip();
    		$count = $InfoModel->where($map)->count();
    		if( $count >= $this->IP_Num ){
    			exit('此IP今日票数已投完，请明日再投！');
    		}
    		// 不能重复给一个投票时检测是否已给用户投票
    		if( $Prodata['way'] == 0 ){
    			$map = null;
    			$map['proid'] = $proid;
    			$map['tuid'] = $tuid;
    			$map['huid'] = $huid;
    			$map['w_time'] = date('Y-m-d',time());
    			$rs = $InfoModel->where($map)->find();
    			if( !empty($rs) ){
    				exit('今日您已投过该用户，请明日再投！');
    			}
    		}
    		// 检测投票人今天投票数是否超过了项目设置的投票数  和 投票人是否被禁止投票
    		$Type = 0;
    		$TprModel = MD('votetpr',$proid);
    		$Tprdata = $TprModel->where(array('proid' => $proid, 'tuid' => $tuid ))->find();
    		if( $Tprdata['status'] == 0 ){
    			exit('无法投票，您已被系统屏蔽！');
    		}
    		if ( $Tprdata['votetime'] < date('Y-m-d',time()) ) {
    			// 最后一次投票时间不是今天      将投票人日量daynums 改为 0
    			$Type = 1;
    			$rs = null;
    			$rs = $TprModel->where(array('proid' => $proid, 'tuid' => $tuid ))->save( array('daynums' => 0) );
    			if( $rs === false ) {
    				exit('系统错误，请重新再投！');
    			}
    			$Tprdata['daynums'] = 0;
    		}
    		if( $Tprdata['daynums'] >= $Prodata['votenums'] ){
    			exit('您今日的票数已投完，请明日再投！');
    		}
    		// 处理投票数据
    		STD($proid)->startTrans();
    		$ProModel = MD('votepro',$proid);
    		$res1 = $ProModel->where(array('proid' => $proid))->setInc('votesum',1);
    		$res2 = $HxrModel->where( array('proid' => $proid ,'huid' => $huid) )->setInc('daynums',1);
    		$data = array();
    		$data['votetime'] = date('Y-m-d',time());
    		$data['daynums'] = $Tprdata['daynums'] + 1;
    		$res3 = $TprModel->where(array('proid' => $proid, 'tuid' => $tuid ))->save($data);
    		$data = array();
    		$data['huid'] = $huid;
    		$data['tuid'] = $tuid;
    		$data['proid'] = $proid;
    		$data['ip'] = get_client_ip();
    		$data['w_time'] = date('Y-m-d',time());
    		$res4 = $InfoModel->add($data);
    		$data['type'] = $Type; // 为1时需要先将投票人日投量改为0
    		if ($res1 !== false && $res2 !== false && $res3 !== false && $res4 !== false && $this->addTransAction($proid, $data, 4, 1) !== false ) {
    			STD( $proid )->commit();
    			exit(true);
    		} else {
    			STD( $proid )->rollback();
    			exit('投票失败，请重试！');
    		}	
    	} else {
    		$this->error('非法操作！');
    	}	
    }  
}