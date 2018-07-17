<?php
	/** Группы прав на функционал модуля */
	$permissions = [
		/** Права на просмотр переходов на сайте */
		'json_get_referer_pages' => [
			'json_get_referer_pages'
		],
		/** Права на просмотр отчетов статистики */
		'total' => [
			'phrases',
			'phrase',
			'popular_pages',
			'engines',
			'engine',
			'sources',
			'sources_domain',
			'visitors',
			'visitors_by_date',
			'visitor',
			'sectionHits',
			'sectionHitsIncluded',
			'visits',
			'visits_sessions',
			'visits_visitors',
			'auditoryActivity',
			'auditoryLoyality',
			'visitDeep',
			'visitTime',
			'entryPoints',
			'paths',
			'exitPoints',
			'openstatCampaigns',
			'openstatServicesByCampaign',
			'openstatAdsByService',
			'openstatServices',
			'openstatSources',
			'openstatServicesBySource',
			'openstatAds',
			'visits_hits',
			'visits_visitors',
			'visitersCommonHours',
			'auditory',
			'sources_entry'.
			'yandex',
			'yandexMetric',
			'flushCounterListConfig',
			'addCounter',
			'editName',
			'getCounterStat',
			'deleteCounter',
			'saveCounterCode',
			'downloadCounterCode'
		],
		/** Права на просмотр облака тегов */
		'tagsCloud' => [
			'get_tags_cloud'
		]
	];
