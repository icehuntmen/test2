<?php

	use UmiCms\Service;

	/**
	 * Базовый класс модуля "Социальные сети".
	 * Модуль управляет приложениями для социальных сетей.
	 * Приложение - сайт, отображаемый по альтернативному шаблону,
	 * у которого маршрутизация адресов начинается с
	 *
	 * http(s)://domain.ru/social_networks/тип_приложения/.
	 *
	 * В данный момент поддерживает только тип приложения "vkontakte".
	 * @link http://help.docs.umi-cms.ru/rabota_s_modulyami/modul_social_nye_seti/
	 */
	class social_networks extends def_module {
		/** @var social_network|bool $current_network текущее приложение */
		public $current_network = false;
		/** @var null|int $templateId идентификатор текущего шаблона дизайна */
		protected static $templateId;

		/** Конструктор */
		public function __construct() {
			parent::__construct();

			if (Service::Request()->isAdmin()) {
				$tabs = $this->getCommonTabs();
				$networks = social_network::getList();

				foreach ($networks as $object) {
					/** @var social_network $network */
					$network = social_network::get($object->getId());
					$tabs->add($network->getCodeName());
				}

				$this->__loadLib('admin.php');
				$this->__implement('Social_networksAdmin');

				$this->loadAdminExtension();

				$this->__loadLib('customAdmin.php');
				$this->__implement('SocialNetworkCustomAdmin', true);

			}

			$this->loadSiteExtension();

			$this->__loadLib('customMacros.php');
			$this->__implement('SocialNetworkCustomMacros', true);

			$this->loadCommonExtension();
			$this->loadTemplateCustoms();
		}

		/**
		 * Устанавливает и возвращает идентификатор текущего шаблона
		 * дизайна.
		 * @param string $method строковый идентификатор типа приложения социальной сети
		 * @return bool|int|null
		 * @throws coreException
		 * @throws selectorException
		 */
		public static function setupTemplate($method) {
			if (is_numeric(self::$templateId)) {
				return self::$templateId;
			}

			$sel = new selector('objects');
			$sel->types('hierarchy-type')->name('social_networks', 'network');
			$sel->where('social_id')->equals($method);
			$sel->where('domain_id')->equals(Service::DomainDetector()->detectId());
			$sel->option('no-length')->value(true);
			$sel->limit(0, 1);
			$object = $sel->result();

			if (umiCount($object) == 0) {
				$defaultDomain = Service::DomainCollection()
					->getDefaultDomain();

				if (!$defaultDomain instanceof iDomain) {
					throw new coreException('Cannot detect default domain');
				}

				$defaultDomainId = $defaultDomain->getId();

				$sel = new selector('objects');
				$sel->types('hierarchy-type')->name('social_networks', 'network');
				$sel->where('social_id')->equals($method);
				$sel->where('domain_id')->equals($defaultDomainId);
				$sel->option('no-length')->value(true);
				$sel->limit(0, 1);
				$object = $sel->result();
			}

			if (isset($object[0]) && $object[0] instanceof iUmiObject) {
				/** @var iUmiObject $object */
				$object = $object[0];
				return self::$templateId = $object->getValue('template_id');
			}

			return false;
		}

		/**
		 * Возвращает текущее приложение социальной сети
		 * @return bool|social_network
		 */
		public function getCurrentSocial() {
			return $this->current_network;
		}

		/**
		 * Возвращает значение параметра текущего приложения социальной сети
		 * @param string $param имя параметра
		 * @return Mixed|null
		 */
		public function getCurrentSocialParams($param = '') {
			if (!$this->current_network instanceof social_network) {
				return null;
			}

			return $this->current_network->getValue($param);
		}

		/**
		 * Обрабатывает запрос приложения социальной сети.
		 * В административном режиме возвращает список приложений
		 * социальных сетей типа "vkontakte".
		 * В клиенском режиме возвращает данные приложения.
		 * @return array
		 * @throws coreException
		 */
		public function vkontakte() {
			$domainsCollection = Service::DomainCollection();
			$defaultDomain = $domainsCollection->getDefaultDomain();

			if (!$defaultDomain instanceof iDomain) {
				throw new coreException('Cannot detect default domain');
			}

			$defaultDomainId = $defaultDomain->getId();

			if (Service::Request()->isNotAdmin()) {
				$domainId = Service::DomainDetector()->detectId();
				$network = social_network::getByCodeName(__FUNCTION__, $domainId);

				if (!$network instanceof social_network) {
					$network = social_network::getByCodeName(__FUNCTION__, $defaultDomainId);
				}

				if (!$network instanceof social_network) {
					$network = social_network::getByCodeName(__FUNCTION__);
				}

				if (!$network instanceof social_network) {
					return false;
				}

				return $this->display_social_frame($network);
			}

			$defaultNetwork = social_network::getByCodeName(__FUNCTION__, $defaultDomainId);

			if (!$defaultNetwork instanceof social_network) {
				$defaultNetwork = social_network::getByCodeName(__FUNCTION__);
			}

			$networks = [];
			$objectsCollection = umiObjectsCollection::getInstance();
			$config = mainConfiguration::getInstance();

			/** @var iDomain $domain */
			foreach ($domainsCollection->getList() as $domain) {
				$network = social_network::getByCodeName(__FUNCTION__, $domain->getId());

				if ($network instanceof social_network) {
					$networks[] = $network;
					continue;
				}

				if (!$defaultNetwork instanceof social_network) {
					$networks[] = social_network::addByCodeName(__FUNCTION__, $domain->getId());
					continue;
				}

				$defaultNetworkId = $objectsCollection->cloneObject($defaultNetwork->getId());
				/** @var iUmiObject $network */
				$network = $objectsCollection->getObject($defaultNetworkId);

				if (!$network instanceof iUmiObject) {
					throw new coreException('Cannot clone object');
				}

				if (!$network->getValue('template_id')) {
					$network->setValue('template_id', $config->get('templates', 'social_networks.' . __FUNCTION__));
				}

				$network->setValue('domain_id', $domain->getId());
				$network->commit();

				$networks[] = social_network::get($network->getId());
			}

			/** @var social_network|Social_networksAdmin $this */
			$this->network_settings($networks);
		}

		/**
		 * Возвращает данные приложения.
		 * @param social_network $network приложение
		 * @return array
		 * @throws coreException
		 */
		protected function display_social_frame($network) {
			$path = getRequest('path');
			$path = trim($path, '/');
			$path = explode('/', $path);

			if (Service::LanguageDetector()->detectPrefix() == $path[0]) {
				array_shift($path);
			}

			$path = array_slice($path, 2);
			$path = '/' . implode('/',$path);
			Service::Request()->Get()->set('path', $path);

			$buffer = Service::Response()
				->getCurrentBuffer();

			if (!$network || !$network->isIframeEnabled()) {
				$buffer->push("<script type='text/javascript'>parent.location.href = '".$path."';</script>");
				$buffer->end();
			}

			$cmsController = cmsController::getInstance();
			$cmsController->analyzePath(true);
			$current_element_id = $cmsController->getCurrentElementId();
			$cmsController->setUrlPrefix('' .  __CLASS__  . '/' . $network->getCodeName());

			if (Service::Request()->isAdmin() || !$network->isHierarchyAllowed($current_element_id)) {
				$buffer->push("<script type='text/javascript'>parent.location.href = '".$path."';</script>");
				$buffer->end();
			}

			$this->current_network = $network;
			$currentModule = $cmsController->getCurrentModule();
			$cmsController->getModule($currentModule);
			return $cmsController->getGlobalVariables(true);
		}
	}
