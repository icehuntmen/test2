<?php
	/** @var array $rules правила инициализации сервисов */
	$rules = [
		'CurrencyCollection' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Currency\Collection',
		],

		'CurrencyFactory' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Currency\Factory',
		],

		'CurrencyRepository' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Currency\Repository',
			'arguments' => [
				new ServiceReference('CurrencyFactory'),
				new ServiceReference('SelectorFactory'),
			]
		],

		'Currencies' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Currency\Facade',
			'arguments' => [
				new ServiceReference('CurrencyRepository'),
				new ServiceReference('CurrencyCollection'),
				new ServiceReference('Configuration'),
				new ServiceReference('CurrencyCalculator'),
			]
		],

		'CurrencyCalculator' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Currency\Calculator',
		],
	];