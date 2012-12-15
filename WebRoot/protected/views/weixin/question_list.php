<?php $this->renderPartial('/weixin/header');?>
<body>
	<div class="wrap">
		<div class="header">
			<a href="https://itunes.apple.com/cn/artist/quan-xin/id485600101?l=en">
				<div class="logo"><img src="/images/logo.png" /></div>
				<span class="down-text">下载ASK DAD客户端，发布自己的问题</span>
			</a>
		</div>
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
