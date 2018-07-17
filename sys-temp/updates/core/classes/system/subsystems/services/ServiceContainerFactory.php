<?php

	/**
	 * Фабрика контейнеров сервисов
	 * Через него следует получать желаемый контейнер,
	 * @example $ServiceContainer = ServiceContainerFactory::create();
	 */
	class ServiceContainerFactory implements iServiceContainerFactory {
		/** @var ServiceContainer[] $serviceContainerList список контейнеров сервисов */
		private static $serviceContainerList = [];

		/** @inheritdoc */
		public static function create($type = self::DEFAULT_CONTAINER_TYPE, array $rules = [], array $parameters = []) {
			if (isset(self::$serviceContainerList[$type])) {
				return self::$serviceContainerList[$type];
			}

			$defaultRules = self::getDefaultRules();
			$defaultParameters = self::getDefaultParameters();

			if ($type !== self::DEFAULT_CONTAINER_TYPE) {
				$rules = array_merge($defaultRules, $rules);
				$parameters = array_merge($defaultParameters, $parameters);
			} else {
				$rules = $defaultRules;
				$parameters = $defaultParameters;
			}

			return self::$serviceContainerList[$type] = new ServiceContainer($rules, $parameters);
		}

		/**
		 * Возвращает список параметров по умолчанию для контейнера сервисов
		 * @return array
		 * @throws Exception
		 * @throws coreException
		 */
		protected static function getDefaultParameters() {
			return [
				'connection' => ConnectionPool::getInstance()->getConnection(),
				'imageFileHandler' => new umiImageFile(__FILE__),
				'baseUmiCollectionConstantMap' => new baseUmiCollectionConstantMap(),
				'directoriesHandler' => new umiDirectory(__FILE__),
				'umiRedirectsCollection' => 'Redirects',
				'MailTemplatesCollection' => 'MailTemplates',
				'MailNotificationsCollection' => 'MailNotifications'
			];
		}

		/**
		 * Возвращает список правил инстанцирования сервисов по умолчанию для контейнера сервисов
		 * @return array
		 */
		protected static function getDefaultRules() {
			return [
				'Redirects' => [
					'class' => 'umiRedirectsCollection',
					'arguments' => [
						new ParameterReference('umiRedirectsCollection'),
					],
					'calls' => [
						[
							'method' => 'setConnection',
							'arguments' => [
								new ParameterReference('connection')
							]
						],
						[
							'method' => 'setConfiguration',
							'arguments' => [
								new ServiceReference('Configuration')
							]
						],
						[
							'method' => 'setMap',
							'arguments' => [
								new InstantiableReference('umiRedirectsConstantMap')
							]
						],
						[
							'method' => 'setResponse',
							'arguments' => [
								new \ServiceReference('Response')
							]
						],
						[
							'method' => 'setDomainDetector',
							'arguments' => [
								new \ServiceReference('DomainDetector')
							]
						],
						[
							'method' => 'setDomainCollection',
							'arguments' => [
								new \ServiceReference('DomainCollection')
							]
						],
						[
							'method' => 'setLanguageCollection',
							'arguments' => [
								new \ServiceReference('LanguageCollection')
							]
						]
					]
				],

				'MailTemplates' => [
					'class' => 'MailTemplatesCollection',
					'arguments' => [
						new ParameterReference('MailTemplatesCollection'),
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
								new InstantiableReference('mailTemplatesConstantMap')
							]
						]
					]
				],

				'MailNotifications' => [
					'class' => 'MailNotificationsCollection',
					'arguments' => [
						new ParameterReference('MailNotificationsCollection'),
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
								new InstantiableReference('mailNotificationsConstantMap')
							]
						],
						[
							'method' => 'setDomainCollection',
							'arguments' => [
								new \ServiceReference('DomainCollection')
							]
						],
						[
							'method' => 'setLanguageCollection',
							'arguments' => [
								new \ServiceReference('LanguageCollection')
							]
						],
						[
							'method' => 'setLanguageDetector',
							'arguments' => [
								new \ServiceReference('LanguageDetector')
							]
						],
						[
							'method' => 'setDomainDetector',
							'arguments' => [
								new \ServiceReference('DomainDetector')
							]
						],
					]
				],

				'AuthenticationRulesFactory' => [
					'class' => 'UmiCms\System\Auth\AuthenticationRules\Factory',
					'arguments' => [
						new ServiceReference('PasswordHashAlgorithm'),
						new ServiceReference('SelectorFactory')
					]
				],

				'PasswordHashAlgorithm' => [
					'class' => 'UmiCms\System\Auth\PasswordHash\Algorithm'
				],

				'Authentication' => [
					'class' => 'UmiCms\System\Auth\Authentication',
					'arguments' => [
						new ServiceReference('AuthenticationRulesFactory'),
						new ServiceReference('Session')
					]
				],

				'Authorization' => [
					'class' => 'UmiCms\System\Auth\Authorization',
					'arguments' => [
						new ServiceReference('Session'),
						new ServiceReference('CsrfProtection'),
						new ServiceReference('permissionsCollection'),
						new ServiceReference('CookieJar'),
						new ServiceReference('objects'),
						new ServiceReference('Configuration')
					]
				],

				'SystemUsersPermissions' => [
					'class' => 'UmiCms\System\Permissions\SystemUsersPermissions',
					'arguments' => [
						new ServiceReference('objects')
					]
				],

				'Auth' => [
					'class' => 'UmiCms\System\Auth\Auth',
					'arguments' => [
						new ServiceReference('Authentication'),
						new ServiceReference('Authorization'),
						new ServiceReference('SystemUsersPermissions')
					]
				],

				'CsrfProtection' => [
					'class' => '\UmiCms\System\Protection\CsrfProtection',
					'arguments' => [
						new ServiceReference('Session'),
						new ServiceReference('DomainDetector'),
						new ServiceReference('IdnConverter'),
						new ServiceReference('DomainCollection')
					],
				],

				'Request' => [
					'class' => '\UmiCms\System\Request\Facade',
					'arguments' => [
						new ServiceReference('RequestHttp'),
						new ServiceReference('BrowserDetector'),
						new ServiceReference('RequestModeDetector'),
						new ServiceReference('RequestPathResolver')
					]
				],

				'CookieJar' => [
					'class' => 'UmiCms\System\Cookies\CookieJar',
					'arguments' => [
						new ServiceReference('CookiesFactory'),
						new ServiceReference('CookiesResponsePool'),
						new ServiceReference('RequestHttpCookies')
					]
				],

				'Session' => [
					'class' => 'UmiCms\System\Session\Session',
					'arguments' => [
						new ServiceReference('Configuration'),
						new ServiceReference('CookieJar')
					]
				],

				'templates' => [
					'class' => 'templatesCollection',
				],

				'pages' => [
					'class' => 'umiHierarchy',
				],

				'cmsController' => [
					'class' => 'cmsController',
				],

				'objects' => [
					'class' => 'umiObjectsCollection',
				],

				'permissionsCollection' => [
					'class' => 'permissionsCollection',
				],

				'connectionPool' => [
					'class' => 'ConnectionPool',
				],

				'objectTypes' => [
					'class' => 'umiObjectTypesCollection'
				],

				'hierarchyTypes' => [
					'class' => 'umiHierarchyTypesCollection'
				],

				'typesHelper' => [
					'class' => 'umiTypesHelper'
				],

				'fields' => [
					'class' => 'umiFieldsCollection'
				],

				'objectPropertyFactory' => [
					'class' => 'UmiCms\System\Data\Object\Property\Factory',
					'arguments' => [
						new ServiceReference('fields'),
						new ServiceReference('objects')
					],
				],

				'ActionFactory' => [
					'class' => 'ActionFactory',
					'calls' => [
						[
							'method' => 'setConfiguration',
							'arguments' => [
								new ServiceReference('Configuration')
							]
						]
					]
				],

				'BaseXmlConfigFactory' => [
					'class' => 'BaseXmlConfigFactory'
				],

				'AtomicOperationCallbackFactory' => [
					'class' => 'AtomicOperationCallbackFactory'
				],

				'TransactionFactory' => [
					'class' => 'TransactionFactory',
					'calls' => [
						[
							'method' => 'setConfiguration',
							'arguments' => [
								new ServiceReference('Configuration')
							]
						]
					]
				],

				'ManifestSourceFactory' => [
					'class' => 'ManifestSourceFactory'
				],

				'ManifestFactory' => [
					'class' => 'ManifestFactory',
					'arguments' => [
						new ServiceReference('BaseXmlConfigFactory'),
						new ServiceReference('AtomicOperationCallbackFactory'),
						new ServiceReference('ManifestSourceFactory')
					],
					'calls' => [
						[
							'method' => 'setConfiguration',
							'arguments' => [
								new ServiceReference('Configuration')
							]
						]
					]
				],

				'EventPointFactory' => [
					'class' => '\UmiCms\System\Events\EventPointFactory'
				],

				'SiteMapUpdater' => [
					'class' => '\UmiCms\Utils\SiteMap\Updater',
					'arguments' => [
						new ParameterReference('connection'),
						new ServiceReference('pages'),
						new ServiceReference('EventPointFactory')
					]
				],

				'CacheKeyGenerator' => [
					'class' => '\UmiCms\System\Cache\Key\Generator',
					'arguments' => [
						new ServiceReference('Configuration'),
						new ServiceReference('DomainDetector'),
						new ServiceReference('LanguageDetector')
					]
				],

				'CacheEngineFactory' => [
					'class' => '\UmiCms\System\Cache\EngineFactory'
				],

				'CountriesFactory' => [
					'class' => '\UmiCms\Classes\System\Entities\Country\CountriesFactory'
				],

				'CitiesFactory' => [
					'class' => '\UmiCms\Classes\System\Entities\City\CitiesFactory'
				],

				'DirectoryFactory' => [
					'class' => '\UmiCms\Classes\System\Entities\Directory\Factory'
				],

				'FileFactory' => [
					'class' => '\UmiCms\Classes\System\Entities\File\Factory'
				],

				'UmiDumpDirectoryDemolisher' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Type\Directory',
					'arguments' => [
						new ServiceReference('DirectoryFactory')
					]
				],

				'UmiDumpFileDemolisher' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Type\File',
					'arguments' => [
						new ServiceReference('FileFactory')
					]
				],

				'Registry' => [
					'class' => 'regedit',
					'arguments' => [
						new ParameterReference('connection'),
						new ServiceReference('CacheEngineFactory')
					]
				],

				'UmiDumpRegistryDemolisher' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Type\Registry',
					'arguments' => [
						new ServiceReference('Registry')
					]
				],

				'ImportSourceIdBinder' => [
					'class' => 'umiImportRelations'
				],

				'UmiDumpDomainDemolisher' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Type\Domain',
					'arguments' => [
						new ServiceReference('DomainCollection')
					],
					'calls' => [
						[
							'method' => 'setSourceIdBinder',
							'arguments' => [
								new ServiceReference('ImportSourceIdBinder')
							]
						]
					]
				],

				'UmiDumpLanguageDemolisher' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Type\Language',
					'arguments' => [
						new ServiceReference('LanguageCollection')
					],
					'calls' => [
						[
							'method' => 'setSourceIdBinder',
							'arguments' => [
								new ServiceReference('ImportSourceIdBinder')
							]
						]
					]
				],

				'UmiDumpObjectDemolisher' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Type\Objects',
					'arguments' => [
						new ServiceReference('objects')
					],
					'calls' => [
						[
							'method' => 'setSourceIdBinder',
							'arguments' => [
								new ServiceReference('ImportSourceIdBinder')
							]
						]
					]
				],

				'UmiDumpTemplateDemolisher' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Type\Template',
					'arguments' => [
						new ServiceReference('templates')
					],
					'calls' => [
						[
							'method' => 'setSourceIdBinder',
							'arguments' => [
								new ServiceReference('ImportSourceIdBinder')
							]
						]
					]
				],

				'UmiDumpObjectTypeDemolisher' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Type\ObjectType',
					'arguments' => [
						new ServiceReference('objectTypes')
					],
					'calls' => [
						[
							'method' => 'setSourceIdBinder',
							'arguments' => [
								new ServiceReference('ImportSourceIdBinder')
							]
						]
					]
				],

				'UmiDumpPageDemolisher' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Type\Page',
					'arguments' => [
						new ServiceReference('pages')
					],
					'calls' => [
						[
							'method' => 'setSourceIdBinder',
							'arguments' => [
								new ServiceReference('ImportSourceIdBinder')
							]
						]
					]
				],

				'RestrictionCollection' => [
					'class' => '\UmiCms\System\Data\Field\Restriction\Collection',
					'arguments' => [
						new ParameterReference('connection')
					]
				],

				'UmiDumpRestrictionDemolisher' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Type\Restriction',
					'arguments' => [
						new ServiceReference('RestrictionCollection'),
					],
					'calls' => [
						[
							'method' => 'setSourceIdBinder',
							'arguments' => [
								new ServiceReference('ImportSourceIdBinder')
							]
						]
					]
				],

				'UmiDumpFieldGroupDemolisher' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Type\FieldGroup',
					'arguments' => [
						new ServiceReference('objectTypes')
					],
					'calls' => [
						[
							'method' => 'setSourceIdBinder',
							'arguments' => [
								new ServiceReference('ImportSourceIdBinder')
							]
						]
					]
				],

				'UmiDumpFieldDemolisher' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Type\Field',
					'arguments' => [
						new ServiceReference('fields'),
						new ServiceReference('objectTypes')
					],
					'calls' => [
						[
							'method' => 'setSourceIdBinder',
							'arguments' => [
								new ServiceReference('ImportSourceIdBinder')
							]
						]
					]
				],

				'UmiDumpPermissionDemolisher' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Type\Permission',
					'arguments' => [
						new ServiceReference('permissionsCollection'),
						new ServiceReference('SystemUsersPermissions'),
						new ServiceReference('objects'),
					],
					'calls' => [
						[
							'method' => 'setSourceIdBinder',
							'arguments' => [
								new ServiceReference('ImportSourceIdBinder')
							]
						]
					]
				],

				'ImportEntitySourceIdBinderFactory' => [
					'class' => '\UmiCms\System\Import\UmiDump\Entity\Helper\SourceIdBinder\Factory'
				],

				'UmiDumpEntityDemolisher' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Type\Entity',
					'arguments' => [
						new ServiceReference('ImportEntitySourceIdBinderFactory'),
						new ServiceContainerReference(),
						new ServiceReference('cmsController'),
					],
					'calls' => [
						[
							'method' => 'setSourceIdBinder',
							'arguments' => [
								new ServiceReference('ImportSourceIdBinder')
							]
						]
					]
				],

				'UmiDumpDemolisherTypeFactory' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Type\Factory',
					'arguments' => [
						new ServiceContainerReference()
					]
				],

				'UmiDumpDemolisherExecutor' => [
					'class' => '\UmiCms\System\Import\UmiDump\Demolisher\Executor',
					'arguments' => [
						new ServiceReference('UmiDumpDemolisherTypeFactory')
					]
				],

				'RegistryPart' => [
					'class' => '\UmiCms\System\Registry\Part',
					'arguments' => [
						new ServiceReference('Registry')
					]
				],

				'ExtensionRegistry' => [
					'class' => '\UmiCms\System\Extension\Registry',
					'arguments' => [
						new ServiceReference('Registry')
					]
				],

				'ExtensionLoader' => [
					'class' => '\UmiCms\System\Extension\Loader',
					'arguments' => [
						new ServiceReference('DirectoryFactory'),
						new ServiceReference('FileFactory')
					]
				],

				'ModulePermissionLoader' => [
					'class' => '\UmiCms\System\Module\Permissions\Loader',
					'arguments' => [
						new ServiceReference('cmsController'),
						new ServiceReference('DirectoryFactory'),
						new ServiceReference('FileFactory')
					]
				],

				'CacheFrontend' => [
					'class' => 'cacheFrontend',
					'arguments' => [
						new ServiceReference('CacheEngineFactory'),
						new ServiceReference('CacheKeyGenerator'),
						new ServiceReference('Configuration'),
						new ServiceReference('CacheKeyValidatorFactory'),
						new ServiceReference('RequestModeDetector')
					]
				],

				'CacheKeyValidatorFactory' => [
					'class' => '\UmiCms\System\Cache\Key\Validator\Factory',
					'arguments' => [
						new ServiceReference('Configuration')
					]
				],

				'BrowserDetector' => [
					'class' => 'BrowserDetect',
					'arguments' => [
						new ServiceReference('CacheEngineFactory')
					]
				],

				'StaticCacheStorage' => [
					'class' => '\UmiCms\System\Cache\Statical\Storage',
					'arguments' => [
						new ServiceReference('Configuration'),
						new ServiceReference('FileFactory'),
						new ServiceReference('DirectoryFactory')
					]
				],

				'StaticCacheKeyGenerator' => [
					'class' => '\UmiCms\System\Cache\Statical\Key\Generator',
					'arguments' => [
						new ServiceReference('Request'),
						new ServiceReference('pages'),
						new ServiceReference('Configuration'),
						new ServiceReference('DomainCollection')
					]
				],

				'CacheStateValidator' => [
					'class' => 'UmiCms\System\Cache\State\Validator',
					'arguments' => [
						new ServiceReference('Auth'),
						new ServiceReference('Request'),
						new ServiceReference('cmsController'),
						new ServiceReference('Response'),
					]
				],

				'StaticCache' => [
					'class' => 'UmiCms\System\Cache\Statical\Facade',
					'arguments' => [
						new ServiceReference('Configuration'),
						new ServiceReference('CacheStateValidator'),
						new ServiceReference('CacheKeyValidatorFactory'),
						new ServiceReference('StaticCacheKeyGenerator'),
						new ServiceReference('StaticCacheStorage')
					]
				],

				'ResponseBufferDetector' => [
					'class' => 'UmiCms\System\Response\Buffer\Detector',
					'arguments' => [
						new ServiceReference('RequestModeDetector')
					]
				],

				'ResponseBufferFactory' => [
					'class' => 'UmiCms\System\Response\Buffer\Factory'
				],

				'ResponseBufferCollection' => [
					'class' => 'UmiCms\System\Response\Buffer\Collection'
				],

				'Response' => [
					'class' => 'UmiCms\System\Response\Facade',
					'arguments' => [
						new ServiceReference('ResponseBufferFactory'),
						new ServiceReference('ResponseBufferDetector'),
						new ServiceReference('ResponseBufferCollection'),
						new ServiceReference('ResponseUpdateTimeCalculator'),
					]
				],

				'ResponseUpdateTimeCalculator' => [
					'class' => 'UmiCms\System\Response\UpdateTime\Calculator',
					'arguments' => [
						new ServiceReference('pages'),
						new ServiceReference('objects')
					]
				],

				'Configuration' => [
					'class' => 'mainConfiguration',
				],

				'BrowserCacheEngineFactory' => [
					'class' => 'UmiCms\System\Cache\Browser\Engine\Factory',
					'arguments' => [
						new ServiceContainerReference()
					]
				],

				'BrowserCache' => [
					'class' => 'UmiCms\System\Cache\Browser\Facade',
					'arguments' => [
						new ServiceReference('Configuration'),
						new ServiceReference('BrowserCacheEngineFactory'),
						new ServiceReference('CacheStateValidator')
					]
				],

				'LoggerFactory' => [
					'class' => 'UmiCms\Utils\Logger\Factory',
					'arguments' => [
						new ServiceReference('DirectoryFactory')
					]
				],

				'SelectorFactory' => [
					'class' => 'UmiCms\System\Selector\Factory'
				],

				'QuickExchangeSourceDetector' => [
					'class' => 'UmiCms\Classes\System\Utils\QuickExchange\Source\Detector',
					'arguments' => [
						new ServiceReference('cmsController')
					]
				],

				'QuickExchangeFileDownloader' => [
					'class' => 'UmiCms\Classes\System\Utils\QuickExchange\File\Downloader',
					'arguments' => [
						new ServiceReference('QuickExchangeSourceDetector'),
						new ServiceReference('FileFactory'),
						new ServiceReference('Response'),
						new ServiceReference('Configuration')
					]
				],

				'QuickExchangeFileUploader' => [
					'class' => 'UmiCms\Classes\System\Utils\QuickExchange\File\Uploader',
					'arguments' => [
						new ServiceReference('Request'),
						new ServiceReference('Configuration')
					]
				],

				'QuickExchangeCsvExporter' => [
					'class' => 'UmiCms\Classes\System\Utils\QuickExchange\Csv\Exporter',
					'arguments' => [
						new ServiceReference('QuickExchangeSourceDetector'),
						new ServiceReference('Request')
					]
				],

				'QuickExchangeCsvImporter' => [
					'class' => 'UmiCms\Classes\System\Utils\QuickExchange\Csv\Importer',
					'arguments' => [
						new ServiceReference('QuickExchangeSourceDetector'),
						new ServiceReference('Request'),
						new ServiceReference('FileFactory'),
						new ServiceReference('Configuration'),
						new ServiceReference('Session')
					]
				],

				'QuickExchange' => [
					'class' => 'UmiCms\Classes\System\Utils\QuickExchange\Facade',
					'arguments' => [
						new ServiceReference('QuickExchangeCsvExporter'),
						new ServiceReference('QuickExchangeCsvImporter'),
						new ServiceReference('QuickExchangeFileDownloader'),
						new ServiceReference('QuickExchangeFileUploader'),
						new ServiceReference('Configuration'),
						new ServiceReference('Response')
					]
				],

				'DataObjectFactory' => [
					'class' => 'UmiCms\System\Data\Object\Factory'
				],

				'HierarchyElementFactory' => [
					'class' => 'UmiCms\System\Hierarchy\Element\Factory'
				],

				'CookiesFactory' => [
					'class' => 'UmiCms\System\Cookies\Factory'
				],

				'CookiesResponsePool' => [
					'class' => 'UmiCms\System\Cookies\ResponsePool'
				],

				'RequestHttpCookies' => [
					'class' => 'UmiCms\System\Request\Http\Cookies'
				],

				'RequestHttpFiles' => [
					'class' => 'UmiCms\System\Request\Http\Files'
				],

				'RequestHttpGet' => [
					'class' => 'UmiCms\System\Request\Http\Get'
				],

				'RequestHttpPost' => [
					'class' => 'UmiCms\System\Request\Http\Post'
				],

				'RequestHttpServer' => [
					'class' => 'UmiCms\System\Request\Http\Server'
				],

				'RequestHttp' => [
					'class' => 'UmiCms\System\Request\Http\Request',
					'arguments' => [
						new ServiceReference('RequestHttpCookies'),
						new ServiceReference('RequestHttpServer'),
						new ServiceReference('RequestHttpPost'),
						new ServiceReference('RequestHttpGet'),
						new ServiceReference('RequestHttpFiles')
					]
				],

				'RequestModeDetector' => [
					'class' => 'UmiCms\System\Request\Mode\Detector',
					'arguments' => [
						new ServiceReference('RequestPathResolver')
					]
				],

				'RequestPathResolver' => [
					'class' => 'UmiCms\System\Request\Path\Resolver',
					'arguments' => [
						new ServiceReference('RequestHttpGet'),
						new ServiceReference('Configuration')
					]
				],

				'RegistrySettings' => [
					'class' => 'UmiCms\System\Registry\Settings',
					'arguments' => [
						new ServiceReference('Registry'),
					]
				],

				'DateFactory' => [
					'class' => 'UmiCms\Classes\System\Entities\Date\Factory'
				],

				'IdnConverter' => [
					'class' => 'idna_convert'
				],

				'DomainCollection' => [
					'class' => 'domainsCollection',
					'arguments' => [
						new ParameterReference('connection'),
						new ServiceReference('IdnConverter')
					]
				],

				'DomainDetector' => [
					'class' => 'UmiCms\System\Hierarchy\Domain\Detector',
					'arguments' => [
						new ServiceReference('DomainCollection'),
						new ServiceReference('RequestHttp')
					]
				],

				'CaptchaSettingsFactory' => [
					'class' => 'UmiCms\Classes\System\Utils\Captcha\Settings\Factory',
					'arguments' => [
						new ServiceReference('Configuration'),
						new ServiceReference('Registry'),
						new ServiceReference('DomainDetector'),
						new ServiceReference('LanguageDetector')
					]
				],

				'LanguageCollection' => [
					'class' => 'langsCollection',
					'arguments' => [
						new ParameterReference('connection'),
						new ServiceReference('DomainCollection')
					]
				],

				'LanguageDetector' => [
					'class' => 'UmiCms\System\Hierarchy\Language\Detector',
					'arguments' => [
						new ServiceReference('LanguageCollection'),
						new ServiceReference('DomainDetector'),
						new ServiceReference('Request'),
						new ServiceReference('pages')
					]
				],

				'YandexOAuthClient' => [
					'class' => 'UmiCms\Classes\System\Utils\Api\Http\Json\Yandex\Client\OAuth'
				],

				'ObjectTypeHierarchyRelationFactory' => [
					'class' => 'UmiCms\System\Data\Object\Type\Hierarchy\Relation\Factory'
				],

				'ObjectTypeHierarchyRelationRepository' => [
					'class' => 'UmiCms\System\Data\Object\Type\Hierarchy\Relation\Repository',
					'arguments' => [
						new ParameterReference('connection'),
						new ServiceReference('ObjectTypeHierarchyRelationFactory')
					]
				],

				'ObjectTypeHierarchyRelationMigration' => [
					'class' => 'UmiCms\System\Data\Object\Type\Hierarchy\Relation\Migration',
					'arguments' => [
						new ParameterReference('connection'),
						new ServiceReference('ObjectTypeHierarchyRelationRepository')
					]
				]
			];
		}
	}
