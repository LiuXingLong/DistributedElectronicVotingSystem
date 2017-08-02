<?php
namespace Admin\Controller;

use Think\Controller;

class LoginController extends Controller
{
    //登陆页面
    public function index()
    {
        $this->display();
    }

    //登陆验证
    public function login($username, $password)
    {
        $admin = D('Admin');
        $map = array(
            'username' => $username,
            'password' => md5($password),
        );
        $res = $admin->field('id,username')->where($map)->find();
        if ($res) {
            session('adminid', $res['id']);
            session('username', $res['username']);
            $this->redirect('Index/index');
        } else {
            $this->error('用户名或者密码错误');
        }
    }

    //退出登陆
    public function logout()
    {
    	session(null);
    	$this->redirect('Login/index');
    }
}