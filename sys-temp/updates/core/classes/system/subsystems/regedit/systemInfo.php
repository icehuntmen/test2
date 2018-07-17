<?php

	use \UmiCms\Service;

	/** Класс для сбора системной информации */
	class systemInfo extends singleton {

		/** @var regedit реестр UMI.CMS */
		private $registry;

		/** @var resource $resource подключение к бд */
		private $resource;

		/* @const int SYSTEM Общая информация о системе */
		const SYSTEM     = 1;
		/* @const int PHP Информация о PHP и его модулях */
		const PHP        = 2;
		/* @const int DATABASE Информация о базе данных */
		const DATABASE   = 4;
		/* @const int NETWORK Информация о доменах и ip адресах */
		const NETWORK   =  8;
		/* @const int STAT Статистическая информация о системе */
		const STAT       = 16;
		/* @const int MODULES Информация об установленных модулях UMI.CMS */
		const MODULES    = 32;
		/* @const int LICENSE Информация о лицензионном ключе */
		const LICENSE    = 64;

		/**
		 * @inherit
		 * @return systemInfo
		 */
		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}

		/**
		 * Конструктор
		 * @throws coreException если не удалось подключиться к бд
		 */
		protected function __construct() {
			$this->registry = Service::Registry();
			$resource = $this->getConnection();

			if ($resource === null) {
				throw new coreException(__METHOD__ . ': can\' connect to data base');
			}

			$this->resource = $resource;
		}

		/**
		 *	Возвращает системную информацию.
		 *	@param int $option модификатор для вывода различных блоков (см. константы класса, -1 = вывести все)
		 *	@return array многомерный массив с различной информацией о системе и окружении
		 *		- 'system'   - информация о системе @see systemInfo::getSystemInfo()
		 *		- 'php'      - информация о php @see systemInfo::getPHPInfo()
		 *		- 'database' - информация о бд @see systemInfo::getDataBaseInfo()
		 *		- 'network'  - информация о доменах и ip адресах @see systemInfo::getNetworkInfo()
		 *		- 'stat'     - статистическая информация о системе @see systemInfo::getStatInfo()
		 *		- 'modules'  - информация об установленных модулях UMI.CMS @see systemInfo::getModulesInfo()
		 *		- 'license'  - информация о задействованной лицензии @see systemInfo::getLicenseInfo()
		 *	@example systemInfo::getInstance()->getInfo(systemInfo::SYSTEM);
		 *	@throws coreException если $option не является числом
		 */
		public function getInfo($option = 1) {
			if (!is_numeric($option)) {
				throw new coreException(__METHOD__ . ': incorrect option given');
			}

			$out = [];

			if (($option & self::SYSTEM) === self::SYSTEM) {
				$out['system'] = $this->getSystemInfo();
			}

			if (($option & self::PHP) === self::PHP) {
				$out['php'] = $this->getPHPInfo();
			}

			if (($option & self::DATABASE) === self::DATABASE) {
				$out['database'] = $this->getDataBaseInfo();
			}

			if (($option & self::NETWORK) === self::NETWORK) {
				$out['network'] = $this->getNetworkInfo();
			}

			if (($option & self::STAT) === self::STAT) {
				$out['stat'] = $this->getStatInfo();
			}

			if (($option & self::MODULES) === self::MODULES) {
				$out['modules'] = $this->getModulesInfo();
			}

			if (($option & self::LICENSE) === self::LICENSE) {
				$out['license'] = $this->getLicenseInfo();
			}

			return $out;
		}

		/**
		 *	Возвращает общую информацию о UMI.CMS
		 *	@return array массив с общей информацией о системе
		 *		- 'version' - версия системы
		 *		- 'revision' - ревизия системы
		 *		- 'license' - редакция системы
		 */
		private function getSystemInfo() {
			return [
				'version' => $this->registry->get('//modules/autoupdate/system_version'),
				'revision' => $this->registry->get('//modules/autoupdate/system_build'),
				'license' => $this->registry->get('//modules/autoupdate/system_edition'),
			];
		}

		/**
		 *	Возвращает информацию о php
		 *	@return array массив с информацией о php
		 *		- 'version' - версия php
		 * 		- 'os' - информация об операционной системе, на которой PHP был собран
		 *		- 'info' - дополнительная информация
		 * 			- 'modules' - имена всех скомпилированных и загруженных модулей
		 * 			- 'configurations' - все зарегистрированные настройки конфигурации
		 */
		private function getPHPInfo() {
			return [
				'version' => $this->parseVersion(phpversion()),
				'os' => php_uname(),
				'info' => [
					'modules' => get_loaded_extensions(),
					'configurations' => ini_get_all()
				]
			];
		}

		/**
		 *	Возвращает информацию о бд
		 *	@return array массив с информацией о бд
		 *		- 'driver' - название СУБД
		 *		- 'version' - версия СУБД
		 */
		private function getDataBaseInfo() {
			return [
				'driver' => iConfiguration::MYSQL_DB_DRIVER,
				'version' => $this->getMySQLVersion()
			];
		}

		/**
		 *	Возвращает информацию о доменах и ip адресах
		 *	@return array массив с информацией о доменах и ip адресах
		 *		- 'hosts' - домены системы @see systemInfo::getHosts()
		 *		- 'ip' - ip адрес сервера, на котором выполняется текущий скрипт.
		 */
		private function getNetworkInfo() {
			return [
				'hosts' => $this->getHosts(),
				'ip' =>  getServer('SERVER_ADDR')
			];
		}

		/**
		 *	Возвращает статистическую информацию
		 *	@return array массив со статистической информацией
		 *		- 'last_update_time' - timestamp даты посленего обновления системы
		 *		- 'trial_days_left' - сколько дней осталось жить системе, если она на триальном периоде
		 *		- 'web_server_id' - строка идентификации сервера
		 */
		private function getStatInfo() {
			return [
				'last_update_time' => $this->registry->get('//modules/autoupdate/last_updated'),
				'trial_days_left' => $this->registry->getDaysLeft(),
				'web_server_id' => getServer('SERVER_SOFTWARE')
			];
		}

		/**
		 *	Возвращает информацию об установленных модулях UMI.CMS
		 *	@return array массив с информацией об установленных модулях UMI.CMS
		 *		- # - имя модуля
		 */
		private function getModulesInfo() {
			$modules = $this->registry->getList('//modules');
			$modulesNames = [];

			foreach ($modules as $key => $value) {
				if (isset($value[0])) {
					$modulesNames[] = $value[0];
				}
			}

			return $modulesNames;
		}

		/**
		 *	Возвращает информацию о задействованной лицензии UMI.CMS
		 *	@return array массив с информацией о задействованной лицензии UMI.CMS
		 *		- 'key' - доменный ключ
		 */
		private function getLicenseInfo() {
			return [
				'key' => $this->registry->get('//settings/keycode')
			];
		}

		/**
		 * Разбирает строку с информацией о версии php|MySQL и оставляет только номер версии
		 * @param string $version строка с информацией о версии php|MySQL
		 * @return string
		 * @throws coreException если операция не удалась
		 */
		private function parseVersion($version) {
			preg_match('/[0-9]+\.[0-9]+\.[0-9]+/', $version, $matches);

			if (!isset($matches[0])) {
				throw new coreException(__METHOD__ . ': can\' grab version');
			}

			return $matches[0];
		}

		/**
		 * Возвращает ресурс для подключения к бд
		 * @return resource
		 */
		private function getConnection() {
			if ($this->resource !== null) {
				return $this->resource;
			}
			/* @var IConnection $connection */
			$connection = ConnectionPool::getInstance()->getConnection();
			$connection->open();
			$info = $connection->getConnectionInfo();
			return isset($info['link']) ? $info['link'] : null;
		}

		/**
		 * Возвращает версию MySQL
		 * @return string
		 */
		private function getMySQLVersion() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$MySQLServerInfo = $connection->getServerInfo();
			return $this->parseVersion($MySQLServerInfo);
		}

		/**
		 *	Возвращает информацию о доменах в UMI.CMS
		 *	@return array массив с информацией о доменах в UMI.CMS
		 *		- id - ид домена
		 *			- 'host' - хост домена
		 * 			- 'mirror'
		 * 				- # - хост зеркала домена
		 * 			- 'is_default' - выводится если домен основной
		 */
		private function getHosts() {
			$domains = Service::DomainCollection()
				->getList();
			$hosts = [];

			/* @var iDomain $domain */
			foreach ($domains as $domain) {
				$hosts[$domain->getId()]['host'] = $domain->getHost();
				$mirrors = $domain->getMirrorsList();
				/* @var iDomainMirror $mirror */
				foreach ($mirrors as $mirror) {
					$hosts[$domain->getId()]['mirrors'][] = $mirror->getHost();
				}
				if ($domain->getIsDefault()) {
					$hosts[$domain->getId()]['is_default'] = '1';
				}
			}

			return $hosts;
		}
	}
