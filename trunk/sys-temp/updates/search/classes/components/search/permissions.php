<?php
	/** Группы прав на функционал модуля */
	$permissions = [
		/** Права на администрирование модуля */
		'index' => [
			'index_control',
			'reindex',
			'truncate',
			'partialReindex',
			'search_replace'
		],
		/** Права на поиск по сайту */
		'search' => [
			'search_do',
			'insert_form',
			'suggestions',
			'sphinxSearch'
		]
	];
