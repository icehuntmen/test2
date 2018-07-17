<?php
	/** Группы прав на функционал модуля */
	$permissions = [
		/** Права на администрирование модуля */
		'seo' => [
			'seo',
			'links',
			'webmaster',
			'flushSiteListConfig',
			'flushExternalLinksListConfig',
			'getExternalLinkList',
			'getSiteInfo',
			'addSite',
			'verifySite',
			'addSiteMap',
			'deleteSite',
			'config',
			'megaindex',
			'yandex',
			'getBrokenLinks',
			'getDatasetConfiguration',
			'flushBrokenLinksDatasetConfiguration',
			'indexLinks',
			'checkLinks',
			'getLinkSources'
		],
		/** Гостевые права */
		'guest' => [
			'getRelCanonical'
		]
	];
