<?php
	/** Группы прав на функционал модуля */
	$permissions = [
		/** Гостевые права */
		'guest' => [
			'page',
			'pageslist',
			'objectslist'
		],
		/** Административный права */
		'admin' => [
			'pages',
			'addpage',
			'editpage',
			'deletepages',
			'activity',
			'objects',
			'addobject',
			'editobject',
			'deleteobjects'
		]
	];
