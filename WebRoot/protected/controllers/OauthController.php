<?php
Yii::import('ext.sinaWeibo.SinaWeibo',true);
Yii::import('ext.qqWeibo.QqWeibo',true);

class OauthController extends Controller
{
	private $_identity = NULL;

	public function actionQqWeibo()
	{
		OAuth::init('801288215', '6838887096887f3bbcb44fd13369d159');
		$callback = 'http://' . $_SERVER['HTTP_HOST'] . '/oauth/qqWeiboCallback';//回调url
		$url = OAuth::getAuthorizeURL($callback);
		header('Location: ' . $url);
		/*
		OAuth::init($client_id, $client_secret);
		$r = OAuth::checkOAuthValid();
		Tencent::$debug = $debug;
		*/
	}

	public function acctionQqWeiboAppCallback()
	{
	
	}

	public function actionQqWeiboCallback()
	{
		$callback = 'http://' . $_SERVER['HTTP_HOST'] . '/oauth/qqWeiboCallback';//回调url
		OAuth::init('801288215', '6838887096887f3bbcb44fd13369d159');
		if ($_GET['code']) {//已获得code
			$code = $_GET['code'];
			$openid = $_GET['openid'];
			$openkey = $_GET['openkey'];
			//获取授权token
			$url = OAuth::getAccessToken($code, $callback);
			$r = Http::request($url);
			parse_str($r, $out);
 			//存储授权数据
			if ($out['access_token']) {
				$_SESSION['t_access_token'] = $out['access_token'];
				$_SESSION['t_refresh_token'] = $out['refresh_token'];
			   	$_SESSION['t_expire_in'] = $out['expire_in'];
			    $_SESSION['t_code'] = $code;
			    $_SESSION['t_openid'] = $openid;
			    $_SESSION['t_openkey'] = $openkey;
			    //验证授权
			    $r = OAuth::checkOAuthValid();
				if ($r) {
					$this->process_qqweibo_callback();
		//	    	header('Location: ' . $callback);//刷新页面
			    } else {
			       exit('<h3>授权失败,请重试</h3>');
			  	}
			} else {
				exit($r);
			}
		}
	}

	public function actionQqWeiboUpdate()
	{
		$_SESSION['t_access_token'] = $_GET['access_token'];
		$_SESSION['t_refresh_token'] = $_GET['refresh_token'];
		$_SESSION['t_expire_time'] = $_GET['expire_time'];
		$_SESSION['t_openid'] = $_GET['open_id'];
		$_SESSION['t_openkey'] = $_GET['open_key'];
		
		OAuth::init('801288215', '6838887096887f3bbcb44fd13369d159');
		if ($_SESSION['t_access_token'] || ($_SESSION['t_openid'] && $_SESSION['t_openkey'])) {//用户已授权
		    //获取用户信息
			$r = Tencent::api('user/info');
			$r = json_decode($r, true);
			$user = $r['data'];
			if(!empty($user)) {
				$user_info['screen_name'] = $user['nick'];
				$user_info['out_uid'] = $user['openid'];
				$user_info['id'] = $user['openid'];
				$user_info['profile_image_url'] = $user['head'] . '/50';
				$user_info['avatar_large'] = $user['head'] . '/180';
				$user_info['t_openkey'] = $_SESSION['t_openkey'];
				$user_info['out_expire_time'] = $_SESSION['t_expire_time'];
				$user_info['token']['access_token'] = $_SESSION['t_access_token'];

				error_log(var_dump($user_info));
				$new_user = $this->_get_user_info($$user_info['id'], $user_info);
				$this->ajax_response(200, '', $new_user);
			} else {
				$this->ajax_response(500, '更新失败', $r);
			}
		}
	}

	private function _get_user_info($uid, $user_info)
	{
		$user_db = User::model()->find("out_uid=:out_uid",array(":out_uid"=>$uid));
		if(!empty($user_db)) {
			$this->_identity=new UserIdentity($user_info['id'],'','weibo');
			$this->_identity->authenticate();
			Yii::app()->user->login($this->_identity,3600*24*30);
			User::model()->updateByPk($user_db['user_id'], array('out_token'=>$user_info['token']['access_token'],'last_login_time'=>time()));
			$this->redirect(Yii::app()->session['back_url']);
		} else {
			$new_user = new User;
			$new_user->user_name = $user_info['screen_name'];
			//$new_user->province = $user_info['province'];
			//$new_user->location = $user_info['location'];
			$new_user->avatar = $user_info['profile_image_url'];
			$new_user->avatar_large = $user_info['avatar_large'];
			//$new_user->gender = $user_info['gender'];
			//$new_user->description = mysql_escape_string($user_info['description']);
			$new_user->out_source = 'weibo';
			$new_user->out_uid = $user_info['id'];
			$new_user->out_token = $user_info['token']['access_token'];
			$new_user->t_openkey = $user_info['t_openkey'];
			$new_user->out_expire_time = $user_info['out_expire_time'];
			$new_user->ctime = time();
			$new_user->status = 0;
			if($new_user->save()) {
				//process login 
				$new_user_id = $new_user->user_id;
				return array(
					'user_id' => $new_user_id,
					'user_name' => $user_info['screen_name'],
					'user_avatar' => $user_info['profile_image_url']
				);
			//	$this->_identity=new UserIdentity($user_info['id'],'','weibo');
			//	$this->_identity->authenticate();
			//	Yii::app()->user->login($this->_identity,3600*24*30);
			//	$this->redirect(Yii::app()->session['back_url']);
			} else {
				header( "refresh:3;url=http://www.trip007.net/");
				echo "<h1>添加用户失败！将会在3秒之后跳转到首页,请重新登录。如果没有，点击<a href=\"/\">这里</a>。</h1>";
			}
		}
	}

	public function actionWeibo()
	{
		$weiboService=new SinaWeibo(WB_AKEY, WB_SKEY);
		$code_url = $weiboService->getAuthorizeURL( WB_CALLBACK_URL );
		Yii::app()->session['back_url'] = Yii::app()->request->getUrlReferrer();
		$this->redirect($code_url);
	}

	public function actionWeiboCallback(){
		$weiboService=new SinaWeibo(WB_AKEY, WB_SKEY);
		if (isset($_REQUEST['code'])) {
			$keys = array();
			$keys['code'] = $_REQUEST['code'];
			$keys['redirect_uri'] = WB_CALLBACK_URL;
			try {
				$token = @$weiboService->getAccessToken( 'code', $keys ) ;
			} catch (OAuthException $e) {
			}
		}

		if (isset($token) && !empty($token)) {
			$_SESSION['token'] = $token;
			setcookie( 'weibojs_'.$weiboService->client_id, http_build_query($token));
			$this->process_out_callback();
		} else {
			echo '认证失败';
		}
	}

	private function process_qqweibo_callback()
	{
		if ($_SESSION['t_access_token'] || ($_SESSION['t_openid'] && $_SESSION['t_openkey'])) {//用户已授权
			echo '<pre><h3>已授权</h3>用户信息：<br>';
		    //获取用户信息
			  $r = Tencent::api('user/info');
			  print_r(json_decode($r, true));
			  echo '</pre>';
		} else {
			echo 'bbb';
		}
	}

	private function process_out_callback()
	{
		$access_token =  $_SESSION['token']['access_token'];
		$c = new SaeTClientV2( WB_AKEY , WB_SKEY , $access_token);
		$uid_get = $c->get_uid();
		if( ! isset($uid_get['uid']))
		{
			echo "登录失败，无法获得用户id: ";
			Yii::log('oauth error:'. var_dump($uid_get));
			return ;
		}
		$uid = $uid_get['uid'];
		$user_info = $c->show_user_by_id($uid);//根据ID获取用户等基本信息
		if(empty($user_info))
		{
			echo "登录失败，不能取得用户信息 ";
			return ;
		}
		//echo json_encode($user_info);die();
		$user_db = User::model()->find("out_uid=:out_uid",array(":out_uid"=>$uid));
		if(!empty($user_db)) {
			$this->_identity=new UserIdentity($user_info['id'],'','weibo');
			$this->_identity->authenticate();
			Yii::app()->user->login($this->_identity,3600*24*30);
			User::model()->updateByPk($user_db['user_id'], array('out_token'=>$access_token,'last_login_time'=>time()));
			$this->redirect(Yii::app()->session['back_url']);
		} else {
			$new_user = new User;
			$new_user->user_name = $user_info['screen_name'];
			$new_user->province = $user_info['province'];
			$new_user->location = $user_info['location'];
			$new_user->avatar = $user_info['profile_image_url'];
			$new_user->avatar_large = $user_info['avatar_large'];
			$new_user->gender = $user_info['gender'];
			$new_user->description = mysql_escape_string($user_info['description']);
			$new_user->out_source = 'weibo';
			$new_user->out_uid = $user_info['id'];
			$new_user->out_token = $_SESSION['token']['access_token'];
			$new_user->ctime = time();
			$new_user->status = 0;
			if($new_user->save()) {
				//process login 
				$this->_identity=new UserIdentity($user_info['id'],'','weibo');
				$this->_identity->authenticate();
				Yii::app()->user->login($this->_identity,3600*24*30);
				$this->redirect(Yii::app()->session['back_url']);
			} else {
				header( "refresh:3;url=http://www.trip007.net/");
				echo "<h1>添加用户失败！将会在3秒之后跳转到首页,请重新登录。如果没有，点击<a href=\"/\">这里</a>。</h1>";
				die();
			}
		}
	}

}
