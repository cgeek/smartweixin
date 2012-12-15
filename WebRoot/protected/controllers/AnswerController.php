<?php

class AnswerController extends Controller
{
	private $_data;
	public function actionPost()
	{
		$data['content'] = htmlspecialchars(addslashes(trim($_GET['content'])));
		$data['question_id'] = $_GET['question_id'];
		$data['lat'] = $_GET['lat'];
		$data['lon'] = $_GET['lon'];
		$data['user_id'] = $_GET['user_id'];

		if(empty($data['question_id']) || empty($data['content']) || empty($data['user_id'])) {
			$this->ajax_response(404,'内容或者用户id不能为空');
		}
		//echo json_encode($data);die();
		$this->_save_answer($data);
	}

	private function _save_answer($data)
	{
		$new_answer = new Answer;
		$new_answer->content = $data['content'];
		$new_answer->question_id = $data['question_id'];
		$new_answer->lat = $data['lat'];
		$new_answer->lon = $data['lon'];
		$new_answer->ctime = time();
		$new_answer->mtime = time();
		$new_answer->user_id = $data['user_id'];
		$new_answer->status = 0;

		if($new_answer->save())
		{
			$new_answer_id = $new_answer->answer_id;
			// 更新用户发表数量
			$this->_data = $data;
			$this->_data['answer_id'] = $new_answer_id;
			$this->ajax_response(200,'',$this->_data);
		} else {
			var_dump($new_answer->getErrors());
			$this->ajax_response(500,'插入失败');
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