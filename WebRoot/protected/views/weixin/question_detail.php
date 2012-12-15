<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta http-equiv="content-type" content="text/html; charset=UTF-8">
		<meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta name="apple-mobile-web-app-status-bar-style" content="black">
		<meta name="format-detection" content="telephone=no">
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
			.header { width:100%;height:40px; background:#0381F6;float:left;margin-bottom:10px;}
			.header .logo { float:left; width:50px;height:50px;}
			.header a {underline:none;text-decoration:none;}
			.header .down-text { color:#fff;underline:none; text-decoration:none; float:left; margin-top:10px; font-size:13px;}
			.question_list li {padding:5px 10px; font-size:14px;}
			.question_list li .content { float:left; margin-left:10px; word-break:break-word; word-wrap:break-word;}
			.question_list li .avatar {width:40px;height:40px; float:left; padding:1px; border:1px solid #ccc;}
			.question_list li .avatar img {width:40px;height:40px;}
			.question_list li .user_name {color:#999;}
			.question_list li span.time {color:#ccc; font-size:12px;}
			.question_list li span.count { color:#0381F6; font-size:12px;}

			.answer_list {background:#e5e5e5; border-top:1px solid #d8d8d8; padding-top:10px; position:relative;}
			.answer_list li {border-bottom:0px;}
			.answer_list li .avatar{border-color:#fff;padding:0;}
		</style>
</head>
<body>
	<div class="wrap">
		<div class="header">
			<a href="https://itunes.apple.com/cn/artist/quan-xin/id485600101?l=en">
				<div class="logo"><img src="/images/logo.png"/></div>
				<span class="down-text">下载ASK DAD客户端，发布自己的问题</span>
			</a>
		</div>
		<div class="question_list">
			<ul>
				<li class="clearfix">
					<div class="avatar">
						<img src="<?=$user_avatar;?>">
					</div>
					<div class="content">
						<div class="user_name"><?=$user_name;?></div>
						<div class="question_content"><?=$content;?></div>
						<div class="more"><span class="time"><?=$ctime;?></span>  <span class="count"><?=$answer_count;?>个回答</span></div>
					</div>
				</li>
			</ul>
			<ul class="answer_list">
				<em></em>
		<?php foreach($answer_list as $answer):?>
				<li class="clearfix">
					<div class="avatar">
						<img src="<?=$answer['user_avatar'];?>">
					</div>
					<div class="content">
						<div class="user_name"><?=$answer['user_name'];?></div>
						<div class="question_content"><?=$answer['content'];?></div>
						<div class="more"><span class="time"><?=$answer['ctime'];?></span></div>
					</div>
				</li>
		<?php endforeach;?>
			</ul>
		</div>
	</div>
</body>
</html>
