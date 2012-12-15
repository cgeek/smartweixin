<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta name="author" content="求攻略">
		<meta name="Description" content="">
		<title>附近问答</title>
		<link href="/favicon.ico" title="" rel="shortcut icon" type="image/x-icon">
		<style>
			html,body,menu,ul,ol,li,p,div,form,h1,h2,h3,h4,h5,h6,img,a img,input,textarea,fieldset{padding:0;margin:0;border:0}
			ul,ol,li{list-style:none}
			h1,h2,h3,h4,h5,h6,b,i,em{font-size:1em;font-weight:normal;font-style:normal}
			body,input{-webkit-text-size-adjust:none;font:normal 16px/1.6 helvetica,verdana,san-serif;outline:none;color:#333}
			input[type="text"],input[type="password"],input[type="button"],input[type="submit"]{-webkit-appearance:none}
			body{background:#fff;}
			.c9{color:#999}
			.clearfix:after {content: "."; display: block; height: 0; clear: both; visibility: hidden;}
			#header{overflow:hidden;padding:15px 5px}
			.question_list li {border-bottom:1px solid #ccc;width:100%; padding:5px 10px; font-size:14px; max-width:550px;}
			.question_list li .content { float:left; margin-left:10px; word-break:break-word; word-wrap:break-word;}
			.question_list li .avatar {width:40px;height:40px; float:left; padding:1px; border:1px solid #ccc;}
			.question_list li .avatar img {width:40px;height:40px;}
			.question_list li .user_name {color:#999;}
			.question_list li span.time {color:#ccc; font-size:12px;}
			.question_list li span.count { color:#0381F6; font-size:12px;}
		</style>
</head>
<body>
	<div class="wrap">
		<div class="question_list">
			<ul>
		<?php foreach($question_list as $question):?>
				<li class="clearfix">
				<a href="/weixin/question/<?=$question['question_id'];?>">
					<div class="avatar">
						<img src="<?=$question['user_avatar'];?>">
					</div>
					<div class="content">
						<div class="user_name"><?=$question['user_name'];?></div>
						<div class="question_content"><?=$question['content'];?></div>
						<div class="more"><span class="time"><?=$question['ctime'];?></span>  <span class="count"><?=$question['answer_count'];?>个回答</span></div>
					</div>
				<?php if($question['distance'] > 0 ):?>
					<div class="distance"><?=$question['distance'];?>米<div>
				<?php endif;?>
				</a>
				</li>
		<?php endforeach;?>
			</ul>
		</div>
	</div>
</body>
</html>