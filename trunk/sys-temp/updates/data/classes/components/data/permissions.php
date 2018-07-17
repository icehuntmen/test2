<?php
	/** Группы прав на функционал модуля */
	$permissions = [
		/** Просмотр объектов и использование форм */
		'main' => [
			'geteditform',
			'getcreateform',
			'saveeditedobject',
			'getproperty',
			'getpropertygroup',
			'getpropertyofobject',
			'getpropertygroupofobject',
			'getallgroups',
			'getallgroupsofobject',
			'rss',
			'atom',
			'generateFeed',
			'getrssmeta',
			'getrssmetabypath',
			'getatommeta',
			'getatommetabypath',
			'checkiffeedable',
			'doSelection',
			'getguideitems',
		],
		/** Управление справочниками */
		'guides' => [
			'guide_items',
			'guide_item_edit',
			'guide_add',
			'guide_items_all',
			'guide_item_del',
			'guide_item_add',
			'getguideitems',
			'getDomainList'
		],
		/** Файловый менеджер */
		'files' => [
			'getfilelist',
			'getfolderlist',
			'createfolder',
			'deletefolder',
			'uploadfile',
			'deletefiles',
			'rename',
			'getimagepreview',
			'elfinder_connector',
			'get_filemanager_info'
		],
		/** Модуль "Корзина" */
		'trash' => [
			'trash_restore',
			'trash_del',
			'trash_empty'
		],
		/** Управление типами данных */
		'types' => [
			'type_add',
			'type_edit',
			'type_del',
			'type_field_add',
			'isfieldexist',
			'type_field_edit',
			'type_group_add',
			'type_group_edit',
			'json_move_field_after',
			'json_move_group_after',
			'json_delete_field',
			'json_delete_group',
			'getSameFieldFromRelatedTypes',
			'attachField'
		]
	];
