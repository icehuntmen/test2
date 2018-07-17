<?php
	/** Группы прав на функционал модуля */
	$permissions = [
		/** Просмотр о создание комментариев */
		'insert' => [
			'insert',
			'post',
			'comment',
			'countcomments',
			'smilepanel',
			'insertvkontakte',
			'insertfacebook',
		],
		/** Администрирование модуля */
		'view_comments' => [
			'view_comments',
			'del',
			'edit',
			'activity',
			'config',
			'view_noactive_comments',
			'comment.edit',
			'publish'
		]
	];
