<?php

class WeixinController extends Controller
{
	private $TOKEN = 'askdaddy';

	public function actionIndex()
	{
		echo 'test';
	}

	private function _get_question_list($lat = '0', $lon = '0', $keyword ='', $limit = 5)
	{
		$distance = 2;
		if(!empty($lat) && $lat > 0 && !empty($lon) && $lon > 0) {
			//$squares = $this->_returnSquarePoint($lat, $lon, $distance);
		}
		$p = intval($_GET['page']) > 1 ? intval($_GET['page']) : 1;
		$per_page = 10;
		$offset = ($p - 1) * $per_page;
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
		$this->_data['count'] = $count;
		$this->_data['list'] = $question_list;
		$this->ajax_response(200,'',$this->_data);
	}

	private function _format_question($question_db)
	{
		return $question_db;

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
