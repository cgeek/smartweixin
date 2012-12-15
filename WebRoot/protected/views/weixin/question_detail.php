<?php $this->renderPartial('/weixin/header');?>
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
				<s><i></i></s>
		<?php foreach($answer_list as $answer):?>
				<li class="clearfix" >
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
		<div class="jumpToApp">
			<img src="/images/jump_app_button.png" />
			<a class="jump_app_text" href="qgl://AskDaddy?question_id=<?=$question_id;?>">打开Ask Daddy应用<br>进行回答</a>
		</div>
	</div>
</body>
</html>
