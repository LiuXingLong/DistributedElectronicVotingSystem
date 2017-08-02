<?php
namespace Admin\Controller;

class IndexController extends BaseController
{
    public function index()
    {
        $this->display();
    }
    private function my_sort( $a, $b )
    {	
    	if ( $a['huid'] == $b['huid'] ) return 0;
    	return ( $a['huid'] < $b['huid'] )?-1:1;
    }
    public function data() 
    {
    	$ProModel = M('Votepro','','DB_TPB');
    	$prodata = $ProModel->where(array('adminid' => session('adminid') ))->field('proid,votename')->select();
    	$this->assign('pro',$prodata);
    	$this->display('data');
    }
    public function voteData()
    {
    	if ( I('proid') ) {
    		$HxrModel = M('Votehxr','','DB_TPB');
    		$data = $HxrModel->where(array('proid' => I('proid') ))->field('huid,personame as name,daynums as num,joinflag,department')->order('daynums desc,huid')->select();    		
    		$table = array();
    		foreach( $data as $k=>$v ) {
    			$table[$k] = $v;
    			$table[$k]['rank'] = $k+1;	
    		}
    		$data = $table;
    		usort($data,array('\Admin\Controller\IndexController', 'my_sort')); // 根据  huid 排序
    		$chart = array();
    		foreach( $data as $k => $v ) {
    			$chart['num'][] = (int)$v['num'];
    			$chart['rank'][] = $v['rank'];
    			$chart['name'][] = $v['name'];
    		}
    		$data = array();
    		$data['table'] = $table;
    		$data['chart'] = $chart;
    		$this->ajaxReturn($data);
    	}
    }
}