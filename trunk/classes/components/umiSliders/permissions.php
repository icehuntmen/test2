<?php
/** @var array $permissions группы прав на функционал модуля */
$permissions = [
	/** Права на управление слайдерами */
	'manage' => [
		'getsliders',
		'getdatasetconfiguration',
		'flushDatasetConfiguration',
		'deleteentitieswithdefinedtypes',
		'geteditformofentitywithdefinedtype',
		'saveformdatatoentitywithdefinedtype',
		'getcreateformofentitywithdefinedtype',
		'createentitywithdefinedtypefromformdata',
		'moveentitieswithdefinedtypes',
		'config',
		'getslideslist',
		'saveslideslist',
		'deleteslide',
	],
	/** Права на просмотр слайдеров */
	'view' => [
		'getslidesbyslidername',
		'getslidelistbyslidercustomid',
		'getslidelistbyslidername',
	]
];
