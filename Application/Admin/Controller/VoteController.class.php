<?php
namespace Admin\Controller;

class VoteController extends BaseController
{
    //创建投票页面
    public function index()
    {
    	$this->display();
    }
    
    //创建投票
    public function createVote()
    {
    	$proid = session('tmp_proid');
    	if ( empty($proid) ) {  
    		exit('true');
    	}
    	$homepic = session('tmp_homepic');
    	session('tmp_homepic', null);
    	session('tmp_proid',null);
        $data = array(
        	'proid' => $proid,
            'adminid' => session('adminid'),
            'templetid' => I('post.templateid'),
            'votename' => I('post.votename'),
            'startime' => I('post.startime'),
            'endtime' => I('post.endtime'),
            'homepic' => $homepic,
            'picdes' => I('post.picdes'),
            'voterule' => $this->trimall($_POST['voterule']),
            'voteway' => I('post.voteway'),
            'votenums' => I('post.votenums')
        );
        M('','','DB_TPB')->startTrans();
        $vote = M('Votepro','','DB_TPB');
        $id = $vote->add($data);
        if ($id !== false) {
           	// 生成 url的二维码, 上传到服务器  、 将二维码路径存到数据库          	       
            $url = 'http://'. C('MY_BALANCE_HOST') .'/tpb/Home/Index/index/proid/'.$proid;            
            $qrcode = __ROOT__. '/Public/img/'. session("adminid").'/'.$proid.'/'.'qrcode.jpg';
            $dir = $_SERVER['DOCUMENT_ROOT'].$qrcode;
            if( $this->qrcode($url,$dir) === false ){
            	M('','','DB_TPB')->rollback();
            	$this->returnAjax(false, '二维码生成失败！');
            }
            // 上传二维码到 ftp服务器
            $is_ftp = C('MY_FTP_HOST');
            if ( !empty($is_ftp) ) {
            	$rootpath = 'Public/img/'; //根目录
            	$savepath = session("adminid").'/'.$proid.'/';   //保存目录
            	$savename = 'qrcode';    //文件名
            	$local_file_dir = $dir;  //文件本地位置路径
            	$info = ftpUploadImg ($rootpath,$savepath,$savename,$local_file_dir);
            	if ( $info !== true ) {
            		M('','','DB_TPB')->rollback();
            		$this->returnAjax(false, $info);
            	}
            	$qrcode = $qrcode.'?ip='.C('MY_FTP_HOST');
            	unlink( $dir );
            	rmdir( pathinfo($dir,PATHINFO_DIRNAME) );
            } else {
            	$qrcode = $qrcode.'?ip='.C('MY_SERVER_ADDR');
            }
            $data['qrcode'] = $qrcode;
            $rs = $vote->where(array('id' => $id))->save(array('qrcode' => $qrcode));
            $res = $this->addTransAction($proid,$data,1,1);
            if ($rs !== false && $res !== false) {
            	M('','','DB_TPB')->commit();
            	$this->returnAjax(true, "发起投票成功！", $data);            	
            } else {
            	M('','','DB_TPB')->rollback();
            	$this->returnAjax(false, "发起投票失败!");
            }
        } else {
        	M('','','DB_TPB')->rollback();
        	$this->returnAjax(false, "发起投票失败，原因：[" . $vote->getError() . "]");
        }
    }

    //删除字符串中所有的空格
    public function trimall($str)
    {
        $rearch = array(" ", "\t", "\n", "\r");
        $replace = array("", "", "", "");
        return str_replace($rearch, $replace, $str);
    }

    //管理项目首页，我的项目显示
    public function manager()
    {
        $this->display();
    }

    //查询所有项目信息
    public function getAll()
    {
    	$res = false;
    	if( $this->My_Cache ) {
    		$res = $this->getCache('admin_'.session('adminid').'_pro');
    	} 
    	if( empty($res) ) {
    		$votepro = M('Votepro','','DB_TPB');
    		$field = array(
    				'proid',
    				'templetid',
    				'votename',
    				'startime',
    				'endtime',
    				'homepic',
    				'picdes',
    				'voterule',
    				'voteway',
    				'votenums',
    				'qrcode'
    		);
    		$where['adminid'] = session('adminid');
    		$res = $votepro->field($field)->where($where)->select();
    		if( $this->My_Cache ){
    			$this->setCache('admin_'.session('adminid').'_pro', $res);
    		}
    	}
        $this->ajaxReturn($res);
    }

    //删除项目
    public function deleteVote()
    {
        $proid = I('post.vote_id');
        if(empty ($proid)) {
        	$this->returnAjax(false, "错误请求!");
        } else {
        	M('','','DB_TPB')->startTrans();
        	$votepro = M('Votepro','','DB_TPB');
        	$where['proid'] = $proid;
        	$where['adminid'] = session('adminid');
        	$rs = $votepro->where($where)->delete();
        	$res = $this->addTransAction($proid,0, 1, 3);
        	$rs1 = M('Votehxr','','DB_TPB')->where( array('proid' => $proid) )->delete();
        	$rs2 = M('Votetpr','','DB_TPB')->where( array('proid' => $proid) )->delete();
        	$rs3 = M('Voteinfo','','DB_TPB')->where( array('proid' => $proid) )->delete();
        	if( $rs !== false && $rs1 !== false && $rs2 !== false && $rs3 !== false && $res !== false){
        		M('','','DB_TPB')->commit();
        		$this->returnAjax(true, "删除成功");
        	} else {
        		M('','','DB_TPB')->rollback();
        		$this->returnAjax(false, "删除失败");
        	}
        }
    }

    //修改页面,，取出所有信息，进行页面初始化
    public function updateVote()
    {
        $proid = I('get.proid');
        $votepro = M('Votepro','','DB_TPB');
        $where['proid'] = $proid;
        $where['adminid'] = session('adminid');
        $res = $votepro->where($where)->find();
        $this->assign("res", $res);
        $this->display();
    }
    
    //上传图片
    public function updateImg($proid)
    {   
        //重新上传了图片
    	session('tmp_proid',null);
    	session('tmp_homepic', null);
        if ( I('post.isimg') == 'true' ) {
            if (!empty($_FILES['file2']['tmp_name'])) {
            	if( empty($proid) ) {
            		$tmp_proid = getProid();
            		session('tmp_proid',$tmp_proid);
            		$proid = $tmp_proid;
            	}
            	$subName = session('adminid').'/'.$proid.'/';
                $homepic = uploadImg($_FILES['file2'] , $subName ,'homepic');
                if ($homepic == 'error') {
                	session('tmp_proid',null);
                    $this->returnAjax(false, "图片上传错误，请重新上传！");
                } else {
                	session('tmp_homepic', $homepic);
                }
            } else {
                $this->returnAjax(false, "图片上传错误，请重新上传！");
            }
        }
        $this->returnAjax(true, "图片上传成功");
    }

    //更新操作
    public function saveUpdate()
    {
    	$proid = I('post.proid');
        $data = array(
        	'proid' => $proid,
            'templetid' => I('post.templateid'),
            'votename' => I('post.votename'),
            'startime' => I('post.startime'),
            'endtime' => I('post.endtime'),
            'picdes' => I('post.picdes'),
            'voterule' => $this->trimall($_POST['voterule']),
            'voteway' => I('post.voteway'),
            'votenums' => I('post.votenums')
        );
        $tmp_proid = session('tmp_proid');
        $tmp_homepic = session('tmp_homepic');
        if( !empty($tmp_proid) && !empty($tmp_homepic) ) {
        	// 创建项目没成功留下的缓存
        	session('tmp_proid',null);
        	session('tmp_homepic', null);
        }
        if( empty($tmp_proid) && !empty($tmp_homepic) ){
        	$data['homepic'] = $tmp_homepic;
        	session('tmp_homepic', null);
        }
        M('','','DB_TPB')->startTrans();
        $vote = M('Votepro','','DB_TPB');
        $where['proid'] = $proid;
        $where['adminid'] = session('adminid');
        $rs = $vote->where($where)->save($data);
        $res = $this->addTransAction($proid,$data,1,2);
        if ( $rs !== false && $res !== false) {
        	M('','','DB_TPB')->commit();
        	$this->returnAjax(true, "更新投票成功！", $data);
        } else {
        	M('','','DB_TPB')->rollback();
        	$this->returnAjax(false, "更新投票失败");
        }
    }
}