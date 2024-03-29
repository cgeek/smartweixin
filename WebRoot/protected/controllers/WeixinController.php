<?php

class WeixinController extends Controller
{
	private $TOKEN = 'askdaddy';

	private $_data;

	public function actionIndex()
	{
		echo 'test';
	}
	public function actionQuestion($id = NULL)
	{
		$question_id = $id;
		if(empty($question_id) || $question_id <= 0) {
			$this->ajax_response(404,'参数不正确');
		}
		$question_db = Question::model()->findByPk($question_id);
		if(empty($question_db)) {
			$this->ajax_response(404,'参数不正确');
		}

		//update view_count
		Question::model()->updateByPk($question_id, array('view_count'=> $question_db['view_count'] +1));

		$this->_data = $this->_format_question($question_db);
		$answers = $this->_get_answerlist($question_id);
		$this->_data['answer_list']= $answers['answer_list'];
		$this->_data['answer_count']= $answers['count'];

		if(isset($_GET['type']) && $_GET['type'] == 'json') {
			$this->ajax_response(200,'',$this->_data);
		} else {
			$this->renderPartial('/weixin/question_detail', $this->_data);
		}
	}

	private function _get_answerlist($question_id)
	{
		$limit = 100;
		$criteria = new CDbCriteria;
		$criteria->addCondition("status=0");
		$criteria->addCondition("question_id=$question_id");
		$criteria->order = ' `ctime` DESC';
		$criteria->limit = $limit;
		$count = Answer::model()->count($criteria);
		$answer_list_db = Answer::model()->findAll($criteria);
		$answer_list = array();
		foreach($answer_list_db as $answer_db)
		{
			$answer = $answer_db->attributes;
			$user_db = User::model()->findByPk($answer['user_id']);
			if(!empty($user_db)) {
				$answer['user_name'] = $user_db['user_name'];
				$answer['user_avatar'] = $user_db['avatar'];
			}
			$answer['ctime'] = human_time($answer['ctime']);
			$answer_list[] = $answer;
		}
		return array('count'=> $count, 'answer_list' => $answer_list);
	}

	public function actionQuestionList()
	{
		$lat = isset($_GET['lat']) ? $_GET['lat'] : 0;
		$lon = isset($_GET['lon']) ? $_GET['lon'] : 0;
		$limit = isset($_GET['limit']) ? $_GET['limit'] : 20;

		$keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';


		$questions = $this->_get_question_list($lat, $lon, $keyword, $limit);
		$this->_data['count'] = $questions['count'];

		$this->_data['question_list'] = $questions['list'];


		if(isset($_GET['type']) && $_GET['type'] = 'json') {
			echo json_encode($this->_data);die();
		}
		$this->renderPartial('/weixin/question_list', $this->_data);
	}

	private function _get_question_list($lat = '0', $lon = '0', $keyword ='', $limit = 2)
	{
		$distance = 2;
		if(!empty($lat) && $lat > 0 && !empty($lon) && $lon > 0) {
		//	$squares = $this->_returnSquarePoint($lat, $lon, $distance);
		}
		
		$per_page = $limit;
		$offset = 0;
		$limit = $per_page; 
		$criteria = new CDbCriteria;
		$criteria->addCondition("status=0");
		//显示附近
		if(isset($squares) && !empty($squares)) {
			$criteria->addCondition("lat<>0");
			$criteria->addCondition("lat>{$squares['right-bottom']['lat']}");
			$criteria->addCondition("lat>{$squares['right-bottom']['lat']}");
			$criteria->addCondition("lon>{$squares['left-top']['lon']}");
			$criteria->addCondition("lon<{$squares['right-bottom']['lon']}");
		}
		if(!empty($keyword)) {
			$criteria->addSearchCondition('content', $keyword);
		}
		$criteria->order = ' `view_count` DESC';
		$criteria->order = ' `ctime` DESC';
		$criteria->limit = $limit;
		$criteria->offset = $offset;
		$count = Question::model()->count($criteria);
		$data = Question::model()->findAll($criteria);
		$question_list = array();
		foreach($data as $question)
		{
			$question_list[] = $this->_format_question($question->attributes);
		}
		$result['count'] = $count;
		$result['list'] = $question_list;

		return $result;
	}

	private function _format_question($question_db , $lat =0, $lon = 0)
	{
		$user = User::model()->findByPk($question_db['user_id']);
		if($lat > 0 && $lon > 0 && $question_db['lat'] > 0 && $question_db['lon'] > 0) {
			$distance = GetDistance($lat, $lon, $question_db['lat'], $question_db['lon']) * 1000;
			$distance = intval($distance) . "米";
		} else {
			$distance = '';
		}
		$data = array(
			'question_id' => $question_db['question_id'],
			'content' => $question_db['content'],
			'answer_count' => $question_db['answer_count'],
			'ctime' =>  human_time($question_db['ctime']),
			'distance' => $distance,
			'lat' => $question_db['lat'],
			'lon' => $question_db['lon'],
			'user_id' => $question_db['user_id'],
			'user_avatar' => $user['avatar'],
			'user_name' => $user['user_name'],
		);
		return $data;
	}

	private function _responseText($message)
	{

		$content = '';
		if($message['content'] == 'Hello2BizUser' || empty($message['content'])) {
			$content = "你可以通过[点你的微信下方的+按钮 -> 选择位置-> 点击右上角的发送按钮]来获取周边问答或者发送关键词获取附近相关问答";
		} else if($message['content'] == '饿了' && $message['content'] == '附近餐馆') {
			$content = "可以调用街旁app返回附近餐馆";
		} else if($message['content'] == 'no') {
			$content = "没有找到和你输入的关键词相关的问题，你可以换个关键词试试哦！";
		} else {
			$message['msgType'] = 'news';
			return $this->_responseLocation($message ,$message['content']);
		}
	
		$resutlStr = '';
		$textTpl = "<xml>
						<ToUserName><![CDATA[%s]]></ToUserName>
						<FromUserName><![CDATA[%s]]></FromUserName>
						<CreateTime>%s</CreateTime>
						<MsgType><![CDATA[%s]]></MsgType>
						<Content><![CDATA[%s]]></Content>
						<FuncFlag>0</FuncFlag>
						</xml>";
		$resultStr = sprintf($textTpl, $message['fromUsername'], $message['toUsername'], time(), 'text', $content);
		return $resultStr;
	}

	public function actionResponseTest()
	{
		$type = isset($_GET['type']) ? $_GET['type'] : 'text';
		$message = array();
        $message['fromUsername'] = 'cgeek';
        $message['toUsername'] = '求攻略';
        $message['createTime'] = time();
		$message['msgType'] = $msgType = $type;

		if($type == 'location' && isset($_GET['lat']) && isset($_GET['lon'])) 
		{
			echo $this->_responseLocation($message);
		} else if($type == 'text') {
			echo $this->_responseText($message);
		}
		//echo json_encode($message);die();
	}

	private function _responseLocation($message, $keyword = '')
	{
		if(!empty($keyword)) {
			$question_list = $this->_get_question_list(0,0, $keyword);
			$list_url = "http://askdaddy.trip007.cn/weixin/questionList?lat=" . $message['lat'] . "&lon=" . $message['lon'] . "&keyword=" . $keyword;
		} else {
			$question_list = $this->_get_question_list($message['lat'], $message['lon']);
			$list_url = "http://askdaddy.trip007.cn/weixin/questionList?lat=" . $message['lat'] . "&lon=" . $message['lon'];
		}
		$question_list = $question_list['list'];

		if(empty($question_list))
		{
			$message['content'] = 'no';
			return $this->_responseText($message);
		}
		$count = count($question_list) + 1;
		$items = '<ArticleCount>' . $count. '</ArticleCount>';
		$items .= '<Articles>';

		foreach($question_list as $key=>$question) {
			$items .= '<item>';
			$items .= "<Title><![CDATA[" . cut_str($question['content'], 20) . "]]></Title>";
			$items .= "<Description><![CDATA[" . cut_str($question['content'],20) . "]]></Description>";
			if($key == 0) {
				$items .= "<PicUrl><![CDATA[http://askdaddy.trip007.cn/images/weixin_cover.png]]></PicUrl>";
			} else {
				$items .= "<PicUrl><![CDATA[" . $question['user_avatar'] . "]]></PicUrl>";
			}
			$items .= "<Url><![CDATA[http://askdaddy.trip007.cn/weixin/question/" . $question['question_id'] . "]]></Url>";
			$items .= '</item>';
		}
		$items .= '<item>';
		$items .= "<Title><![CDATA[查看更多附近的问答]]></Title>";
		$items .= "<Description><![CDATA[]]></Description>";
		$items .= "<PicUrl><![CDATA[]]></PicUrl>";
		$items .= "<Url><![CDATA[" . $list_url . "]]></Url>";
		$items .= '</item>';
		$items .= '</Articles>';

		$LocationTpl = "<xml>
						<ToUserName><![CDATA[%s]]></ToUserName>
						<FromUserName><![CDATA[%s]]></FromUserName>
						<CreateTime>%s</CreateTime>
						<MsgType><![CDATA[%s]]></MsgType>";
		$LocationTplFooter = "
						<FuncFlag>0</FuncFlag>
					</xml>";
		$resultStr = sprintf($LocationTpl, $message['fromUsername'], $message['toUsername'], time(), 'news') . $items . $LocationTplFooter;
		return $resultStr;
	}

	private function _responseImage($message)
	{
		return '';
	}

	public function actionMessage()
	{
		//get post data, May be due to the different environments
		$postStr = isset($GLOBALS["HTTP_RAW_POST_DATA"]) ? $GLOBALS["HTTP_RAW_POST_DATA"] : '';

      	//extract post data
		if (!empty($postStr)){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $message['fromUsername'] = $postObj->FromUserName;
            $message['toUsername'] = $postObj->ToUserName;
            $message['CreateTime'] = $postObj->CreateTime;
            $message['msgType'] = $msgType = $postObj->MsgType;

			$resultStr = '';
			if($msgType == 'text') {
				$message['content'] = trim($postObj->Content);
				$resultStr = $this->_responseText($message);
			} else if($msgType == 'location') {
				$message['lat'] = $postObj->Location_X;
				$message['lon'] = $postObj->Location_Y;
				$message['scale'] = $postObj->Scale;
				$message['label'] = $postObj->Label;
				$resultStr = $this->_responseLocation($message);
			} else if($msgType == 'image') {
				$message['picUrl'] = $postObj->PicUrl;
				$resultStr = $this->_responseText($message);
			}
			error_log($resultStr);
			echo $resultStr;
        } else {
        	echo "";
        	exit;
        }
	}

	public function actionValid()
	//public function actionMessage()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if($this->checkSignature()){
        	echo $echoStr;
        	exit;
        }
	}


	private function checkSignature()
	{
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];	
        		
		$token = $this->TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}

	private function _returnSquarePoint($lat, $lon, $distance = 0.5)
	{
		$dlon =  2 * asin(sin($distance / (2 * $this->EARTH_RADIUS)) / cos(deg2rad($lat)));
		$dlon = rad2deg($dlon);

		$dlat = $distance/$this->EARTH_RADIUS;
		$dlat = rad2deg($dlat);

		return array(
			'left-top'=>array('lat'=>$lat + $dlat,'lon'=>$lon-$dlon),
			'right-top'=>array('lat'=>$lat + $dlat, 'lon'=>$lon + $dlon),
			'left-bottom'=>array('lat'=>$lat - $dlat, 'lon'=>$lon - $dlon),
			'right-bottom'=>array('lat'=>$lat - $dlat, 'lon'=>$lon + $dlon)
			);
	}
	// Uncomment the following methods and override them if needed
	/*
	public function filters()
	{
		// return the filter configuration for this controller, e.g.:
		return array(
			'inlineFilterName',
			array(
				'class'=>'path.to.FilterClass',
				'propertyName'=>'propertyValue',
			),
		);
	}

	public function actions()
	{
		// return external action classes, e.g.:
		return array(
			'action1'=>'path.to.ActionClass',
			'action2'=>array(
				'class'=>'path.to.AnotherActionClass',
				'propertyName'=>'propertyValue',
			),
		);
	}
	*/
}
