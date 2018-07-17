<?php
	/** Группы прав на функционал модуля */
	$permissions = [
		/** Права на администрирование каталога */
		'tree' => [
			'tree',
			'filters',
			'indexposition',
			'setvalueforindexfield',
			'deleteindex',
			'cleangroupallfields',
			'getindexgroup',
			'getsettings',
			'add',
			'edit',
			'del',
			'activity',
			'config',
			'category.edit',
			'object.edit',
			'publish'
		],
		/** Права на просмотр каталога */
		'view' => [
			'category',
			'object',
			'viewobject',
			'getcategorylist',
			'getsmartfilters',
			'makeemptyfilterresponse',
			'getsmartcatalog',
			'makeemptycatalogresponse',
		]
	];
