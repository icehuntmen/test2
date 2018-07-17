<?php
	/** Группы прав на функционал модуля */
	$permissions = [
		/** Права на администрирование модуля */
		'banners_list' => [
			'add',
			'edit',
			'activity',
			'del',
			'lists',
			'places',
			'config'
		],
		/** Права на просмотр баннеров */
		'insert' => [
			'insert',
			'go_to',
			'fastinsert',
			'multiplefastinsert',
			'getstaticbannercall',
			'renderBanner'
		]
	];
