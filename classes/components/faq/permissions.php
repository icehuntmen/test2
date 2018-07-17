<?php
	/** Группы прав на функционал модуля */
	$permissions = [
		/** Права на просмотр базы знаний */
		'projects' => [
			'project',
			'projects',
			'category',
			'question'
		],
		/** Право задать вопрос */
		'post_question' => [
			'addquestionform',
			'post_question',
		],
		/** Права на администрирование модуля */
		'projects_list' => [
			'lists',
			'projects_list',
			'add',
			'edit',
			'del',
			'activity',
			'config',
			'category.edit',
			'project.edit',
			'question.edit',
			'publish'
		]
	];
