<?php
	/** Группы прав на функционал модуля */
	$permissions = [
		/** Права на просмотр фотогелерей */
		'albums' => [
			'album',
			'albums',
			'photo'
		],
		/** Права на администрирование модуля */
		'albums_list' => [
			'lists',
			'add',
			'edit',
			'del',
			'activity',
			'uploadimages',
			'upload_arhive',
			'config',
			'album.edit',
			'photo.edit',
			'publish'
		]
	];
