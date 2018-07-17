<?php
	/** Группы прав на функционал модуля */
	$permissions = [
		/** Права на просмотр блога */
		'common' => [
			'blog',
			'blogslist',
			'commentadd',
			'commentslist',
			'comment',
			'post',
			'postslist',
			'getpostslist',
			'postview',
			'viewblogauthors',
			'viewblogfriends',
			'postsbytag',
			'checkallowcomments',
			'prepareCut',
			'prepareTags',
			'prepareContent'
		],
		/** Права на добавление постов с клиентской части */
		'add' => [
			'placecontrols',
			'itemdelete',
			'postadd',
			'postedit',
			'edituserblogs',
			'draughtslist'
		],
		/** Права на администрирование модулей */
		'admin' => [
			'blogs',
			'posts',
			'comments',
			'listitems',
			'getAllBlogs',
			'add',
			'del',
			'edit',
			'activity',
			'config',
			'blog.edit',
			'post.edit',
			'comment.edit',
			'publish'
		]
	];

