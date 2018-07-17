<?php
	/** Группы прав на функционал модуля */
	$permissions = [
		/** Права на отправку сообщений */
		'add' => [
			'add',
			'send',
			'page',
			'posted'
		],
		/** Права на управление адресами форм */
		'addresses' => [
			'address_add',
			'address_delete',
			'address_edit',
			'del'
		],
		/** Права на управление формами */
		'forms' => [
			'form_add',
			'form_delete',
			'form_edit',
			'getpages',
			'getbindedpage',
			'getaddresses',
			'type_group_add',
			'type_group_edit',
			'type_field_add',
			'type_field_edit',
			'json_move_field_after',
			'json_move_group_after',
			'json_delete_field',
			'json_delete_group',
			'deltype',
			'isfieldexist'
		],
		/** Права на управление шаблонами писем */
		'templates' => [
			'template_add',
			'template_delete',
			'template_edit',
			'getforms',
			'getunbindedforms',
			'del',
			'gettemplatemacros'
		],
		/** Права на управление сообщениями форм */
		'messages' => [
			'message',
			'messages',
			'message_delete',
			'del'
		]
	];

