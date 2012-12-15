<?php

class QuestionController extends Controller
{
	private $_data;

	private $EARTH_RADIUS = 6371; //地球半径，平均半径为6371km

	public function actionPost()
	{
		$data['content'] = htmlspecialchars(urldecode(trim($_GET['content'])));
		$data['lat'] = $_GET['lat'];
		$data['lon'] = $_GET['lon'];
		$data['user_id'] = $_GET['user_id'];

		if(empty($data['content']) || empty($data['user_id'])) {
			$this->ajax_response(404,'内容或者用户id不能为空');
		}

		$this->_save_question($data);
	}

	private function _save_question($data)
	{
		$new_question = new Question;
		$new_question->content = $data['content'];
		$new_question->lat = $data['lat'];
		$new_question->lon = $data['lon'];
		$new_question->ctime = time();
		$new_question->mtime = time();
		$new_question->user_id = $data['user_id'];
		$new_question->status = 0;

		if($new_question->save())
		{
			$new_question_id = $new_question->question_id;
			// 更新用户发表数量
			$question_db = Question::model()->findByPk($new_question_id);
			$this->_data = $this->_format_question($question_db);
			$this->ajax_response(200,'',$this->_data);
		} else {
			var_dump($new_question->getErrors());
			$this->ajax_response(500,'插入失败');
		}
	}

	public function actionDetail()
	{
		$question_id = intval($_GET['question_id']) > 1 ? intval($_GET['question_id']) : 1;
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
		$this->ajax_response(200,'',$this->_data);
	}

	private function _get_answerlist($question_id)
	{
		$question_id = 1;
		$limit = 10;
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
			$answer_list[] = $answer;
		}
		return array('count'=> $count, 'answer_list' => $answer_list);
	}

	public function actionList()
	{
		$lat = $_GET['lat'];
		$lon = $_GET['lon'];
		$distance = isset($_GET['distance']) ? $_GET['distance'] : 2;
		if(!empty($lat) && $lat > 0 && !empty($lon) && $lon > 0) {
			//$squares = $this->_returnSquarePoint($lat, $lon, $distance);
		}
		$p = isset($_GET['page'])  ? intval($_GET['page']) : 1;
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

		$criteria->order = ' `view_count` DESC';
		$criteria->order = ' `ctime` DESC';
		$criteria->limit = $limit;
		$criteria->offset = $offset;
		$count = Question::model()->count($criteria);
		$data = Question::model()->findAll($criteria);
		$question_list = array();
		foreach($data as $question)
		{
			$question_list[] = $this->_format_question($question->attributes, $lat, $lon);
		}
		$this->_data['count'] = $count;
		$this->_data['list'] = $question_list;
		$this->ajax_response(200,'',$this->_data);
	}

	private function _format_question($question_db , $lat =0, $lon = 0)
	{
		$user = User::model()->findByPk($question_db['user_id']);
		if($lat > 0 && $lon > 0 && $question_db['lat'] > 0 && $question_db['lon'] > 0) {
			$distance = GetDistance($lat, $lon, $question_db['lat'], $question_db['lon']) * 1000;
			$distance = intval($distance) . "米";
		} else {
			$distance = 0;
		}
		$data = array(
			'question_id' => $question_db['question_id'],
			'content' => $question_db['content'],
			'answer_count' => $question_db['answer_count'],
			'distance' => $distance,
			'ctime' =>  human_time($question_db['ctime']),
			'lat' => $question_db['lat'],
			'lon' => $question_db['lon'],
			'user_id' => $question_db['user_id'],
			'user_avatar' => $user['avatar'],
			'user_name' => $user['user_name'],
		);
		return $data;
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
