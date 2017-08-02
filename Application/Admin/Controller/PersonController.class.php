<?php
namespace Admin\Controller;

class PersonController extends BaseController
{
    //人员管理首页
    public function index()
    {
        $this->assign("data", json_encode($this->getTree()));
        $this->display();
    }
    
    //得到树形菜单
    public function getTree()
    {	
    	$res = false;
    	if ( $this->My_Cache ){
    		$res = $this->getCache('admin_'.session('adminid').'_pro_tree');	
    	}
    	if( empty($res) ){
    		$votepro = M('Votepro','','DB_TPB');
    		$where['adminid'] = session('adminid');
    		$res = $votepro->field("proid as id,votename as name")->where($where)->select();
    		$res = $this->createTree($res);
    		if ( $this->My_Cache ) {
    			$this->setCache('admin_'.session('adminid').'_pro_tree',$res);
    		}
    	}	
        return $res;
    }
    
    //构造组织结构子数
    private function createTree($data)
    {
        $array = array();
        foreach ($data as $val) {
            $tmp['text']  = $val['name'];
            $tmp['nodes'] = array(
                0 =>
                    array(
                        'text' => '候选人信息',
                        'tags' => array('hxr' . $val['id']) ,
                    ),
                1 =>
                    array(
                        'text' => '投票人信息',
                        'tags' => array('tpr' . $val['id']),
                    )
            );
            $array[] = $tmp;
        }
        return $array;
    }

    /**
     * 查询投票人信息
     * @param $proid 项目id
     */
    private function getJoinPer($proid)
    {	
    	$res = false;
    	if ($this->My_Cache) {
    		$res = $this->getCache('admin_'.$proid.'_tpr');
    	}
    	if( empty($res) ){
    		$join = M('Votetpr','','DB_TPB');
    		$fields = array(
    				'tuid',
    				'joinflag',
    				'personame',
    				'daynums',
    				'department',
    				'status',
    				'votetime'
    			);
    		$where['proid'] = $proid;
    		$res = $join->field($fields)->where($where)->select();
    		if ( !$res ) {
    			foreach ($fields as $k => $v) {
    				$res[0][$v] = "-";
    			}
    		} else {
    			foreach ($res as $k => $v ){
    				$res[$k]['status'] = $res[$k]['status'] ? '有' : '无';
    			}
    		}
    		if ($this->My_Cache) {
    			$this->setCache('admin_'.$proid.'_tpr',$res);
    		}
    	}
        return $res;
    }

    /**
     * 查询候选人
     * @param $proid 项目id
     */
    private function getPer($proid)
    {	
    	$res = false;
    	if ($this->My_Cache) {
    		$res = $this->getCache('admin_'.$proid.'_hxr');
    	}
    	if( empty($res) ) {
    		$join = M('Votehxr','','DB_TPB');
    		$fields = array(
    				'huid',
    				'joinflag',
    				'personame',
    				'brifintro',
    				'perpic',
    				'daynums',
    				'department',
    				'lables',
    				'status'
    			);
    		$where['proid'] = $proid;
    		$res = $join->field($fields)->where($where)->select();
    		if (!$res) {
    			foreach ($fields as $k => $v) {
    				$res[0][$v] = "-";
    			}
    		} else {
    			foreach ($res as $k => $v ){
    				$res[$k]['status'] = $res[$k]['status'] ? '有' : '无';
    			}
    		}
    		if ($this->My_Cache) {
    			$this->setCache('admin_'.$proid.'_hxr',$res);
    		}
    	}
        return $res;
    }
    
    /**
     * 查询候选人 或 投票人
     */
    public function getPerByProid()
    {
    	$m = I('get.id');
    	if (is_array($m)) {
    		$iswhich = substr($m[0], 0, 3);
    		$proid = substr($m[0], 3);
    	} else {
    		$iswhich = substr($m, 0, 3);
    		$proid = substr($m,3);
    	}
    	if (empty($m)) {
    		$this->ajaxReturn(array());
    	} else if ($iswhich == "tpr") {
    		$this->ajaxReturn($this->getJoinPer($proid));
    	} else if ($iswhich == "hxr") {
    		$this->ajaxReturn($this->getPer($proid));
    	}
    }
    
    /**
     * 增加投票人
     */
    public function addTpr()
    {
    	M('','','DB_TPB')->startTrans();
        $votetpr = M('Votetpr','','DB_TPB');
        $m = I('post.info');
        $proid = substr($m, 3);
        // 验证标识符的唯一性
        $where['joinflag'] = I('post.joinflag');
        $where['proid'] = $proid;
        $res = $votetpr->where ($where)->count();
        if ($res > 0) {
            $this->returnAjax(false,"该身份验证码已经存在！");
        }
        $rs = $votetpr->where(array('proid'=>$proid))->max('tuid');
        	if( empty($rs) ) {
        	$tuid = 1;
        } else {
        	$tuid = $rs + 1;
        }
        $data = array(
            'proid' => $proid,
        	'tuid' => $tuid,
            'joinflag' => I('post.joinflag'),
            'personame' => I('post.personame'),
            'department' => I('post.department'),
            'status' => I('post.status'),
            'daynums'=>'0'
        );
        if ( $votetpr->add($data) !== false && $this->addTransAction($proid, $data, 3, 1) !== false ) {    
        	M('','','DB_TPB')->commit();
            $this->returnAjax(true,"添加成功");
        } else {
        	M('','','DB_TPB')->rollback();
        	$this->returnAjax(false,"添加失败");
        }
    }

    /**
     * 增加候选人
     */
    public function addHxr()
    {
    	M('','','DB_TPB')->startTrans();
        $votehxr = M('Votehxr','','DB_TPB');
        $m = I('post.iswhich');
        $proid = substr($m, 3);
        // 验证标识符的唯一性
        $where['joinflag'] = I('post.joinflag');
        $where['proid'] = $proid;
        $res = $votehxr->where ($where)->count();
        if($res>0){
            $this->returnAjax(false,"该身份验证码已经存在！");
        }
        $rs = $votehxr->where(array('proid'=>$proid))->max('huid');
        if( empty($rs) ) {
        	$huid = 1;
        } else {
        	$huid = $rs + 1;
        }
        //上传图片
        if ( I('post.isimg') == "true" ) {
            if ( !empty($_FILES['file2']['tmp_name']) ) {
            	$subName = session('adminid').'/'.$proid.'/';
                $perpic = uploadImg($_FILES['file2'],$subName,'hxr_'.$huid);
                if ($perpic == "error") {
                    $this->returnAjax(false, "图片上传错误，请重新上传！");
                }
            } else {
                $this->returnAjax(false, "图片上传错误，请重新上传！");
            }
        }
        $data = array(
            'proid' => $proid,
            'huid' => $huid,
            'joinflag' => I('post.joinflag'),
            'personame' => I('post.personame'),
            'department' => I('post.department'),
        	'brifintro' => I('post.brifintro'),
        	'lables' => I('post.lables'),
            'perpic' => $perpic,
            'daynums'=>'0',
        	'status' => I('post.status')
        );
        if ( $votehxr->add($data) !== false ) {
        	// 项目候选人数量加一处理
        	$votepro = M('Votepro','','DB_TPB');
        	$res1 = $votepro->where(array('proid' => $proid))->setInc('hxrnums',1); // 候选人数加一
        	if( $res1 !== false && $this->addTransAction($proid, $data, 2, 1) !== false ){
        		M('','','DB_TPB')->commit();
        		$this->returnAjax(true,"添加成功");
        	} else {
        		M('','','DB_TPB')->rollback();
        		$this->returnAjax(false,"添加失败！");
        	}
        } else {
        	M('','','DB_TPB')->rollback();
        	$this->returnAjax(false,"添加失败！");
        } 
    }
    
    /**
     * 删除投票人或者候选人
     */
    public function deletePer() 
    {
        $iswhich = I('post.iswhich');
        $proid = substr($iswhich,3);
        M('','','DB_TPB')->startTrans();
        if(substr($iswhich,0,3) == "tpr") {
        	$tuid = I('post.tuid');
            $votetpr = M('Votetpr','','DB_TPB');
            if( $votetpr->where ( array('proid' => $proid , 'tuid' => $tuid) )->delete() !== false && $this->addTransAction($proid, $tuid, 3, 3) !== false ) { 
            	M('','','DB_TPB')->commit();
            	$this->returnAjax ( true, "删除成功" );
            } else {
            	M('','','DB_TPB')->rollback();
            	$this->returnAjax ( false, "删除失败" );
            }
        } else {
        	$huid = I('post.huid');
            $votehxr = M('Votehxr','','DB_TPB');
            if( $votehxr->where( array('proid' => $proid , 'huid' => $huid) )->delete() !== false ) { 
            	// 项目候选人数量减一处理
            	$votepro = M('Votepro','','DB_TPB');
            	$res1 = $votepro->where(array('proid' => $proid))->setDec('hxrnums',1); // 候选人数减一
            	if( $res1 !== false && $this->addTransAction($proid, $huid, 2, 3) !== false ){
            		M('','','DB_TPB')->commit();
            		$this->returnAjax ( true, "删除成功" );
            	} else {
            		M('','','DB_TPB')->rollback();
            		$this->returnAjax ( false, "删除失败" );
            	}
            } else {
            	M('','','DB_TPB')->rollback();
            	$this->returnAjax ( false, "删除失败" );
            }
        }
    }

    /**
     * 更新投票人
     */
    public function updateTpr() 
    {
    	M('','','DB_TPB')->startTrans();
        $votetpr = M('Votetpr','','DB_TPB');
        $m = I('post.iswhich');
        $proid = substr($m, 3);
        $where['joinflag'] = I('post.joinflag');
        $where['proid'] = $proid;
        $where['tuid'] = array("neq",I('post.tuid'));
        $res = $votetpr->where ( $where )->count();
        if ( $res > 0) {
            $this->returnAjax(false,"该身份验证码已经存在！");
        }
        $data = array(
        	'proid' => $proid,
        	'tuid'=> I('post.tuid'),
            'personame' => I('post.personame'),
            'joinflag' => I('post.joinflag'),
            'daynums' => I('post.daynums'),
            'status' => I('post.status'),
            'department' => I('post.department')
        );
        $where2['proid'] = $proid;
        $where2['tuid'] = I('post.tuid');
        if ( $votetpr->where($where2)->save($data) !== false && $this->addTransAction($proid, $data, 3, 2) !== false ) {
        	M('','','DB_TPB')->commit();
        	$this->returnAjax ( true, "编辑投票人成功！");
        } else {
        	M('','','DB_TPB')->rollback();
            $this->returnAjax ( false, "编辑投票人失败！" );
        }
    }

    /**
     * 更新候选人
     */
    public function updateHxr()
    {
    	M('','','DB_TPB')->startTrans();
        $votehxr = M('Votehxr','','DB_TPB');
        $m = I('post.iswhich');
        $proid = substr($m, 3);
        $where['joinflag'] = I('post.joinflag');
        $where['proid'] = $proid;
        $where['huid'] = array("neq",I('post.huid'));
        $res = $votehxr->where ( $where )->count();
        if ( $res >0 ) {
            $this->returnAjax(false,"该身份验证码已经存在！");
        }
        $data = array(
        	'proid' => $proid,
        	'huid' => I('post.huid'),
            'personame' =>I('post.personame'),
            'joinflag' => I('post.joinflag'),
            'lables' => I('post.lables'),
            'daynums' => I('post.daynums'),
            'status' => I('post.status'),
            'department' => I('post.department'),
            'brifintro' => I('post.brifintro')
        );
        //如果图片上传发生改变
        if (I('post.isimg') == "true") {
            if (!empty($_FILES['file2']['tmp_name'])) {
                $subName = session('adminid').'/'.$proid.'/';
                $perpic = uploadImg($_FILES['file2'],$subName,'hxr_'.I('post.huid'));
                if ($perpic == "error") {
                    $this->returnAjax(false, "图片上传错误，请重新上传！");
                } else {
                	$data['perpic'] = $perpic;
                }
            } else {
                $this->returnAjax(false, "图片上传错误，请重新上传！");
            }
        }
        $where2['proid'] = $proid;
        $where2['huid'] = I('post.huid');
        if ( $votehxr->where($where2)->save($data) !== false && $this->addTransAction($proid, $data, 2, 2) !== false ) {
        	M('','','DB_TPB')->commit();
        	$this->returnAjax ( true, "编辑候选人成功！");
        } else {
        	M('','','DB_TPB')->rollback();
            $this->returnAjax ( false, "编辑候选人失败！" );
        }
    }
}