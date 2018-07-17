<?php

namespace UmiCms;

/**
 * Интерфейс фасада для получения сервисов
 * @package UmiCms\Service
 */
interface iService {

	/**
	 * Возвращает экземпляр сервиса по его названию
	 * @param string $serviceName название сервиса
	 * @return mixed
	 */
	public static function get($serviceName);

	/**
	 * Возвращает новый экземпляр сервиса по его названию
	 * @param string $serviceName название сервиса
	 * @return mixed
	 */
	public static function getNew($serviceName);

	/**
	 * Возвращает экземпляр класса коллекции редиректов
	 * @return \umiRedirectsCollection
	 */
	public static function Redirects();

	/**
	 * Возвращает экземпляр класса коллекции шаблонов писем
	 * @return \MailTemplatesCollection
	 */
	public static function MailTemplates();

	/**
	 * Возвращает экземпляр класса коллекции уведомлений
	 * @return \MailNotificationsCollection|\iUmiConstantMapInjector
	 */
	public static function MailNotifications();

	/**
	 * Возвращает экземпляр класса фасада для работы с аутентификацией и авторизацией пользователя
	 * @return \UmiCms\System\Auth\iAuth
	 */
	public static function Auth();

	/**
	 * Возвращает экземпляр класса защиты от CSRF
	 * @return \UmiCms\System\Protection\CsrfProtection
	 */
	public static function CsrfProtection();

	/**
	 * Возвращает экземпляр класса прав системных пользователей
	 * @return \UmiCms\System\Permissions\iSystemUsersPermissions
	 */
	public static function SystemUsersPermissions();

	/**
	 * Возвращает экземпляр класса алгоритма хеширования паролей
	 * @return \UmiCms\System\Auth\PasswordHash\iAlgorithm
	 */
	public static function PasswordHashAlgorithm();

	/**
	 * Возвращает экземпляр класса фасада запроса
	 * @return \UmiCms\System\Request\iFacade
	 */
	public static function Request();

	/**
	 * Возвращает экземпляр класса для работы с куками
	 * @return \UmiCms\System\Cookies\CookieJar
	 */
	public static function CookieJar();

	/**
	 * Возвращает экземпляр класса для работы с сессией
	 * @return \UmiCms\System\Session\iSession
	 */
	public static function Session();

	/**
	 * Возвращает экземпляр фабрики атомарных команд транзакции
	 * @return \iActionFactory
	 */
	public static function ActionFactory();

	/**
	 * Возвращает экземпляр фабрики манифестов
	 * @return \iManifestFactory
	 */
	public static function ManifestFactory();

	/**
	 * Возвращает экземпляр фабрики транзакций
	 * @return \iTransactionFactory
	 */
	public static function TransactionFactory();

	/**
	 * Возвращает экземпляр фабрики событий
	 * @return System\Events\iEventPointFactory
	 */
	public static function EventPointFactory();

	/**
	 * Возвращает экземпляр класса обновления карты сайта
	 * @return Utils\SiteMap\iUpdater
	 */
	public static function SiteMapUpdater();

	/**
	 * Возвращает экземпляр класса коллекции объектов
	 * @return \iUmiObjectsCollection
	 */
	public static function ObjectsCollection();

	/**
	 * Возвращает экземпляр класса коллекции объектных типов
	 * @return \iUmiObjectTypesCollection
	 */
	public static function ObjectsTypesCollection();

	/**
	 * Возвращает экземпляр класса подключения к базе данных
	 * @return \ConnectionPool
	 */
	public static function ConnectionPool();

	/**
	 * Возвращает экземпляр класса для быстрого доступа к данным полей объектов
	 * @return \umiTypesHelper
	 */
	public static function TypesHelper();

	/**
	 * Возвращает экземпляр класса коллекции иерархических типов
	 * @return \iUmiHierarchyTypesCollection
	 */
	public static function HierarchyTypesCollection();

	/**
	 * Возвращает экземпляр фабрики значений полей объектов
	 * @return \UmiCms\System\Data\Object\Property\iFactory
	 */
	public static function ObjectPropertyFactory();

	/**
	 * Возвращает экземпляр класса генерации ключей для кеширования
	 * @return \UmiCms\System\Cache\Key\iGenerator
	 */
	public static function CacheKeyGenerator();

	/**
	 * Возвращает экземпляр фабрики хранилищ кеша
	 * @return \UmiCms\System\Cache\iEngineFactory
	 */
	public static function CacheEngineFactory();

	/**
	 * Возвращает фабрику стран
	 * @return \UmiCms\Classes\System\Entities\Country\iCountriesFactory
	 */
	public static function CountryFactory();

	/**
	 * Возвращает фабрику городов
	 * @return \UmiCms\Classes\System\Entities\City\iCitiesFactory
	 */
	public static function CityFactory();

	/**
	 * Возвращает коллекцию ограничей полей
	 * @return \UmiCms\System\Data\Field\Restriction\iCollection
	 */
	public static function RestrictionCollection();

	/**
	 * Возвращает фабрику директорий
	 * @return \UmiCms\Classes\System\Entities\Directory\iFactory
	 */
	public static function DirectoryFactory();

	/**
	 * Возвращает фабрику файлов
	 * @return \UmiCms\Classes\System\Entities\File\iFactory
	 */
	public static function FileFactory();

	/**
	 * Возвращает фабрику класса, связующего идентификатору импортируемых сущностей
	 * @return \UmiCms\System\Import\UmiDump\Entity\Helper\SourceIdBinder\iFactory
	 */
	public static function ImportEntitySourceIdBinderFactory();

	/**
	 * Возвращает исполнителя удаления по UmiDump
	 * @return \UmiCms\System\Import\UmiDump\Demolisher\Executor
	 */
	public static function UmiDumpDemolisherExecutor();

	/**
	 * Возвращает реестр установленных расширений
	 * @return \UmiCms\System\Extension\iRegistry
	 */
	public static function ExtensionRegistry();

	/**
	 * Возвращает загрузчика расширений
	 * @return \UmiCms\System\Extension\iLoader
	 */
	public static function ExtensionLoader();

	/**
	 * Возвращает загрузчика разрешений модулей
	 * @return \UmiCms\System\Module\Permissions\iLoader
	 */
	public static function ModulePermissionLoader();

	/**
	 * Возвращает фасад для работы с кешем
	 * @return \iCacheFrontend
	 */
	public static function CacheFrontend();

	/**
	 * Возвращает фабрику валидаторов ключей кеша
	 * @return \UmiCms\System\Cache\Key\Validator\iFactory
	 */
	public static function CacheKeyValidatorFactory();

	/**
	 * Возвращает экземпляр определителя параметров браузера
	 * @return \UmiCms\Utils\Browser\iDetector
	 */
	public static function BrowserDetector();

	/**
	 * Возвращает фасад для работы со статическим кешем
	 * @return \UmiCms\System\Cache\Statical\iFacade
	 */
	public static function StaticCache();

	/**
	 * Возвращает фасад для работы с буферами вывода
	 * @return \UmiCms\System\Response\iFacade
	 */
	public static function Response();

	/**
	 * Возвращает конфигурацию системы
	 * @return \iConfiguration
	 */
	public static function Configuration();

	/**
	 * Возвращает фасад для работы с браузерным кешированием
	 * @return \UmiCms\System\Cache\Browser\iFacade
	 */
	public static function BrowserCache();

	/**
	 * Возвращает фасад быстрого обмена данными в формате csv.
	 * @return \UmiCms\Classes\System\Utils\QuickExchange\iFacade
	 */
	public static function QuickExchange();

	/**
	 * Возвращает фабрику селекторов
	 * @return \UmiCms\System\Selector\iFactory
	 */
	public static function SelectorFactory();

	/**
	 * Возвращает фабрику объектов данных
	 * @return \UmiCms\System\Data\Object\iFactory
	 */
	public static function DataObjectFactory();

	/**
	 * Возвращает фабрику иерархических элементов (страниц)
	 * @return \UmiCms\System\Hierarchy\Element\iFactory
	 */
	public static function HierarchyElementFactory();

	/**
	 * Возвращает системный реестр
	 * @return \iRegedit
	 */
	public static function Registry();

	/**
	 * Возвращает реестр общих настроек системы
	 * @return \UmiCms\System\Registry\iSettings
	 */
	public static function RegistrySettings();

	/**
	 * Возвращает фабрику дат
	 * @return \UmiCms\Classes\System\Entities\Date\iFactory
	 */
	public static function DateFactory();

	/**
	 * Возвращает Idn (Internationalized Domain Names) конвертер
	 * @return \UmiCms\Classes\System\Utils\Idn\iConverter
	 */
	public static function IdnConverter();

	/**
	 * Возвращает коллекцию доменов
	 * @return \iDomainsCollection
	 */
	public static function DomainCollection();

	/**
	 * Возвращает определитель запрошенного домена
	 * @return \UmiCms\System\Hierarchy\Domain\iDetector
	 */
	public static function DomainDetector();

	/**
	 * Возвращает фабрику настроек каптчи
	 * @return \UmiCms\Classes\System\Utils\Captcha\Settings\iFactory
	 */
	public static function CaptchaSettingsFactory();

	/**
	 * Возвращает коллекцию языков
	 * @return \iLangsCollection
	 */
	public static function LanguageCollection();

	/**
	 * Возвращает определитель запрошенного языка
	 * @return \UmiCms\System\Hierarchy\Language\iDetector
	 */
	public static function LanguageDetector();

	/**
	 * Возвращает клиент сервиса Яндекс.OAuth
	 * @return \UmiCms\Classes\System\Utils\Api\Http\Json\Yandex\Client\iOAuth
	 */
	public static function YandexOAuthClient();
}
