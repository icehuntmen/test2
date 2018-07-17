<?php
	/** Группы прав на функционал модуля */
	$permissions = [
		/** Права на администрирование модуля */
		'dispatches_list' => [
			'add',
			'edit',
			'activity',
			'del',
			'messages',
			'subscribers',
			'releasees',
			'fill_release',
			'release_send',
			'lists',
			'add_message',
			'releases',
			'config',
			'getNewsRubricList'
		],
		/** Права на подписку и отписку */
		'subscribe' => [
			'subscribe',
			'subscribe_do',
			'unsubscribe',
			'parseDispatches'
		]
	];
