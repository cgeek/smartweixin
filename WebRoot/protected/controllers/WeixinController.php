<?php

class WeixinController extends Controller
{
	private $TOKEN = 'askdaddy';

	private $_data;

	public function actionIndex()
	{
		echo 'test';
	}
	public function actionQuestion($question_id = NULL)
	{
		echo $question_id;
	}

	public function actionQuestionList()
	{
		$lat = isset($_GET['lat']) ? $_GET['lat'] : 0;
		$lon = isset($_GET['lon']) ? $_GET['lon'] : 0;
		$limit = isset($_GET['limit']) ? $_GET['limit'] : 5;

		$questions = $this->_get_question_list($lat, $lon, '', $limit);
		$this->_data['count'] = $questions['count'];

		$this->_data['question_list'] = $questions['list'];


		if(isset($_GET['type']) && $_GET['type'] = 'json') {
			echo json_encode($this->_data);die();
		}
		$this->renderPartial('/weixin/question_list', $this->_data);
	}

	private function _get_question_list($lat = '0', $lon = '0', $keyword ='', $limit = 5)
	{
		$distance = 2;
		if(!empty($lat) && $lat > 0 && !empty($lon) && $lon > 0) {
			//$squares = $this->_returnSquarePoint($lat, $lon, $distance);
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
			$distance = GetDistance($lat, $lon, $question_db['lat'], $question_db['lon']);
		} else {
			$distance = 0;
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
		$resutlStr = '';
		$textTpl = "<xml>
						<ToUserName><![CDATA[%s]]></ToUserName>
						<FromUserName><![CDATA[%s]]></FromUserName>
						<CreateTime>%s</CreateTime>
						<MsgType><![CDATA[%s]]></MsgType>
						<Content><![CDATA[%s]]></Content>
						<FuncFlag>0</FuncFlag>
						</xml>";
		$resultStr = sprintf($textTpl, $message['fromUsername'], $message['toUsername'], time(), $message['msgType'], '测试');
		return $resultStr;
	}

	private function _responseLocation($message)
	{
		/*
		$question_list = $this->_get_question_list($message['lat'], $message['lon']);

		$items = '<ArticleCount>' . count($question_list) . '</ArticleCount>';
		$items .= '<Articles>';
		foreach($question_list as $question) {
			$items = '<item>';
			$items .= "<Title>" . $question['content'] . "</Title>";
			$items .= "<Description>" . $question['content'] . "</Description>";
			$items .= "<picUrl>" . $question['user_avatar'] . "</picUrl>";
			$items .= "<Url>" . $question['question_id'] . "</Url>";
			$items .= '</item>';
		}
		$items .= '</Articles>';
		 */
		$LocationTpl = "<xml>
						<ToUserName><![CDATA[%s]]></ToUserName>
						<FromUserName><![CDATA[%s]]></FromUserName>
						<CreateTime>%s</CreateTime>
						<MsgType><![CDATA[%s]]></MsgType>
						<Content><![CDATA[%s]]></Content>
						<FuncFlag>0</FuncFlag>
					</xml>";
		$resultStr = sprintf($LocationTpl, $message['fromUsername'], $message['toUsername'], time(), $message['msgType'], '坐标测试');
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
				$resultStr = $this->_responseText($message);
			} else if($msgType == 'image') {
				$message['picUrl'] = $postObj->PicUrl;
				$resultStr = $this->_responseText($message);
			}
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
