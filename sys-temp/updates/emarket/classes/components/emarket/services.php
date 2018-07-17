<?php
	/** @var array $parameters параметры инициализации сервисов */
	$parameters = [
		'ApiShipOrders' => 'ApiShipOrders'
	];

	/** @var array $rules правила инициализации сервисов */
	$rules = [
		'ApiShipOrders' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Orders\Collection',
			'arguments' => [
				new ParameterReference('ApiShipOrders'),
			],
			'calls' => [
				[
					'method' => 'setConnection',
					'arguments' => [
						new ParameterReference('connection')
					]
				],
				[
					'method' => 'setMap',
					'arguments' => [
						new InstantiableReference('UmiCms\Classes\Components\Emarket\Delivery\ApiShip\Orders\ConstantMap')
					]
				]
			]
		],

		'YandexKassaClient' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Payment\Yandex\Client\Kassa',
			'arguments' => [
				new ServiceReference('LoggerFactory'),
				new ServiceReference('Configuration'),
			]
		],

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

		'TaxRateVatFactory' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Tax\Rate\Vat\Factory',
		],

		'TaxRateVatRepository' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Tax\Rate\Vat\Repository',
			'arguments' => [
				new ServiceReference('TaxRateVatFactory'),
				new ServiceReference('SelectorFactory'),
			]
		],

		'TaxRateVat' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Tax\Rate\Vat\Facade',
			'arguments' => [
				new ServiceReference('TaxRateVatRepository')
			]
		],

		'ReceiptSerializerFactory' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Serializer\Receipt\Factory',
			'arguments' => [
				new ServiceContainerReference()
			]
		],

		'ReceiptSerializerRoboKassa' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Serializer\Receipt\RoboKassa',
			'arguments' => [
				new ServiceReference('Currencies'),
				new ServiceReference('TaxRateVat'),
				new ServiceReference('DomainDetector'),
			]
		],

		'ReceiptSerializerPayAnyWay' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Serializer\Receipt\PayAnyWay',
			'arguments' => [
				new ServiceReference('Currencies'),
				new ServiceReference('TaxRateVat'),
				new ServiceReference('DomainDetector'),
			]
		],

		'ReceiptSerializerYandexKassa3' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Serializer\Receipt\YandexKassa3',
			'arguments' => [
				new ServiceReference('Currencies'),
				new ServiceReference('TaxRateVat'),
				new ServiceReference('DomainDetector'),
			]
		],

		'ReceiptSerializerYandexKassa4' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Serializer\Receipt\YandexKassa4',
			'arguments' => [
				new ServiceReference('Currencies'),
				new ServiceReference('TaxRateVat'),
				new ServiceReference('DomainDetector'),
			]
		],

		'ReceiptSerializerPayOnline' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Serializer\Receipt\PayOnline',
			'arguments' => [
				new ServiceReference('Currencies'),
				new ServiceReference('TaxRateVat'),
				new ServiceReference('DomainDetector'),
			]
		],

		'PayOnlineFiscalClient' => [
			'class' => 'UmiCms\Classes\Components\Emarket\Payment\PayOnline\Client\Fiscal',
			'arguments' => [
				new ServiceReference('LoggerFactory'),
				new ServiceReference('Configuration'),
			]
		],
	];