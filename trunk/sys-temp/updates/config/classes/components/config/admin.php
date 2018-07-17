<?php

	use UmiCms\Service;

	/** Класс функционала административной панели */
	class ConfigAdmin {

		use baseModuleAdmin;
		/** @var config $module */
		public $module;

		/**
		 * Возвращает главные настройки системы.
		 * Если передан ключевой параметр $_REQUEST['param0'] = do,
		 * то метод запустит сохранение настроек.
		 * @throws coreException
		 */
		public function main() {
			$regedit = Service::Registry();
			$config = mainConfiguration::getInstance();

			$timezones = $this->module->getTimeZones();
			$timezones['value'] = $config->get('system', 'time-zone');
			$modules = [];

			foreach ($regedit->getList('//modules') as $module) {
				list($module) = $module;
				$modules[$module] = getLabel('module-' . $module);
			}

			if ($regedit->get('//modules/events/') && !$regedit->get('//settings/default_module_admin_changed')) {
				$modules['value'] = 'events';
			} else {
				$modules['value'] = $regedit->get('//settings/default_module_admin');
			}

			$params = [
				'globals' => [
					'string:keycode' => null,
					'boolean:disable_url_autocorrection' => null,
					'int:max_img_filesize' => null,
					'status:upload_max_filesize' => null,
					'boolean:allow-alt-name-with-module-collision' => null,
					'int:session_lifetime' => null,
					'status:busy_quota_files_and_images' => null,
					'int:quota_files_and_images' => null,
					'status:busy_quota_uploads' => null,
					'int:quota_uploads' => null,
					'boolean:disable_too_many_childs_notification' => null,
					'select:timezones' => null,
					'select:modules' => null
				]
			];

			/** @var data $moduleData */
			$moduleData = cmsController::getInstance()->getModule('data');
			$maxUploadFileSize = $moduleData->getAllowedMaxFileSize();

			$mode = getRequest('param0');

			if ($mode == 'do') {
				$params = $this->expectParams($params);

				$regedit->set('//settings/keycode', $params['globals']['string:keycode']);
				$regedit->set('//settings/disable_url_autocorrection', $params['globals']['boolean:disable_url_autocorrection']);

				$maxImgFileSize = $params['globals']['int:max_img_filesize'];
				if ($maxUploadFileSize != -1 && ($maxImgFileSize <= 0 || $maxImgFileSize > $maxUploadFileSize)) {
					$maxImgFileSize = $maxUploadFileSize;
				}
				$regedit->set('//settings/max_img_filesize', $maxImgFileSize);

				$config->set('kernel', 'ignore-module-names-overwrite', $params['globals']['boolean:allow-alt-name-with-module-collision']);
				$config->set('session', 'active-lifetime', $params['globals']['int:session_lifetime']);

				$quota = (int) $params['globals']['int:quota_files_and_images'];
				if ($quota < 0) {
					$quota = 0;
				}
				$config->set('system', 'quota-files-and-images', $quota * 1024 * 1024);

				$quotaUploads = (int) $params['globals']['int:quota_uploads'];
				if ($quotaUploads < 0) {
					$quotaUploads = 0;
				}
				$config->set('system', 'quota-uploads', $quotaUploads * 1024 * 1024);
				$config->set('system', 'disable-too-many-childs-notification', $params['globals']['boolean:disable_too_many_childs_notification']);
				$config->set('system', 'time-zone', $params['globals']['select:timezones']);
				$config->save();
				$regedit->set('//settings/default_module_admin', $params['globals']['select:modules']);
				$regedit->set('//settings/default_module_admin_changed', 1);
				$this->chooseRedirect();
			}

			$params['globals']['string:keycode'] = $regedit->get('//settings/keycode');
			$params['globals']['boolean:disable_url_autocorrection'] = $regedit->get('//settings/disable_url_autocorrection');
			$params['globals']['status:upload_max_filesize'] = $maxUploadFileSize;

			$maxImgFileSize = $regedit->get('//settings/max_img_filesize');

			$params['globals']['int:max_img_filesize'] = $maxImgFileSize ?: $maxUploadFileSize;
			$params['globals']['boolean:allow-alt-name-with-module-collision'] = $config->get('kernel', 'ignore-module-names-overwrite');

			$quotaByte = getBytesFromString(mainConfiguration::getInstance()->get('system', 'quota-files-and-images'));
			$params['globals']['status:busy_quota_files_and_images'] = ceil(getBusyDiskSize(getResourcesDirs()) / (1024 * 1024));

			if ($quotaByte > 0) {
				$params['globals']['status:busy_quota_files_and_images'] .= ' ( ' . getBusyDiskPercent() . '% )';
			}

			$params['globals']['int:quota_files_and_images'] = (int) (getBytesFromString($config->get('system', 'quota-files-and-images')) / (1024 * 1024));
			$quotaUploadsBytes = getBytesFromString(mainConfiguration::getInstance()->get('system', 'quota-uploads'));
			$params['globals']['status:busy_quota_uploads'] = ceil(getBusyDiskSize(getUploadsDir()) / (1024 * 1024));

			if ($quotaUploadsBytes > 0) {
				$params['globals']['status:busy_quota_uploads'] .= ' ( ' . getOccupiedDiskPercent(getUploadsDir(), $quotaUploadsBytes) . '% )';
			}

			$params['globals']['int:quota_uploads'] = (int) (getBytesFromString($config->get('system', 'quota-uploads')) / (1024 * 1024));
			$params['globals']['int:session_lifetime'] = $config->get('session', 'active-lifetime');
			$params['globals']['boolean:disable_too_many_childs_notification'] = $config->get('system', 'disable-too-many-childs-notification');
			$params['globals']['select:timezones'] = $timezones;
			$params['globals']['select:modules'] = $modules;

			$this->setDataType('settings');
			$this->setActionType('modify');

			if (isDemoMode()) {
				unset($params['globals']['string:keycode']);
			}

			$data = $this->prepareData($params, 'settings');

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает содержимое вкладки "Модули":
		 *
		 * 1) Список модулей, которые не были установлены, но их можно установить;
		 * 2) Список модулей, которые были установлены;
		 *
		 * @throws coreException
		 */
		public function modules() {
			$this->setDataType('list');
			$this->setActionType('view');
			/** @var autoupdate $autoUpdate */
			$autoUpdate = cmsController::getInstance()
				->getModule('autoupdate');
			$data = [];

			if (!$autoUpdate instanceof autoupdate) {
				$this->setData($data);
				$this->doData();
			}

			$cmsController = cmsController::getInstance();
			$moduleList = $cmsController->getModulesList();
			$data = $this->prepareData($moduleList, 'modules');

			try {
				$data['attribute:is-last-version'] = (int) $autoUpdate->isLastVersion();
			} catch (publicException $exception) {
				$data['attribute:is-last-version'] = 0;
			}

			try {
				$availableModuleList = isDemoMode() ? [] : $autoUpdate->getAvailableModuleList();
			} catch (publicException $exception) {
				$availableModuleList = [
					'error' => $exception->getMessage()
				];
			}

			$installedModuleList = [];

			foreach ($moduleList as $module) {
				$installedModuleList[$module] = getLabel('module-' . $module);
			}

			$notInstalledModules = array_diff_key($availableModuleList, $installedModuleList);
			$installList = [];

			foreach ($notInstalledModules as $name => $label) {
				if ($name == 'error') {
					$installList[] = [
						'attribute:error' => $label
					];
					continue;
				}

				$installList[] = [
					'attribute:label' => $label,
					'node:available-module' => $name
				];
			}

			$data['nodes:available-module'] = $installList;

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает содержимое вкладки "Расширения":
		 *
		 * 1) Список расширений, которые не были установлены, но их можно установить;
		 * 2) Список расширений, которые были установлены;
		 *
		 * @throws coreException
		 */
		public function extensions() {
			$this->setDataType('list');
			$this->setActionType('view');

			/** @var autoupdate $autoUpdate */
			$autoUpdate = cmsController::getInstance()
				->getModule('autoupdate');
			$data = [];

			if (!$autoUpdate instanceof autoupdate) {
				$this->setData($data);
				$this->doData();
			}

			try {
				$data['attribute:is-last-version'] = (int) $autoUpdate->isLastVersion();
			} catch (publicException $exception) {
				$data['attribute:is-last-version'] = 0;
			}

			try {
				$allExtensions = isDemoMode() ? [] : $autoUpdate->getAvailableExtensionList();
			} catch (publicException $exception) {
				$allExtensions = [
					'error' => $exception->getMessage()
				];
			}

			$installedExtensions = Service::ExtensionRegistry()
				->getList();
			$data['nodes:installed-extension'] = array_map(function($name) use ($allExtensions){
				return [
					'attribute:label' => isset($allExtensions[$name]) ? $allExtensions[$name] : $name,
					'node:value' => $name
				];
			}, $installedExtensions);

			$availableExtensionList = array_diff_key($allExtensions, array_flip($installedExtensions));

			foreach ($availableExtensionList as $name => $label) {
				if ($name == 'error') {
					$data['nodes:available-extension'][] = [
						'attribute:error' => $label
					];
					continue;
				}

				$data['nodes:available-extension'][] = [
					'attribute:label' => $label,
					'node:value' => $name
				];
			}

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Оптимизирует хранение контента объектов, сгруппированых по
		 * иерархическому типу.
		 * Если объектов иерархического типа больше 3500 и отдельной таблицы для хранения нет,
		 * то их контент переносится в отдельную таблицу, если объектов меньше и отдельная
		 * таблица есть, то контент переносится в общую таблицу.
		 * @throws coreException
		 */
		public function reviewDatabase() {
			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->contentType('text/javascript');
			$buffer->charset('utf-8');

			$maxItemsPerType = 3500;
			$minItemsPerType = round($maxItemsPerType / 2);
			$status = umiBranch::getDatabaseStatus();

			foreach ($status as $item) {
				if ($item['isBranched']) {
					if ($item['count'] < $minItemsPerType) {
						$hierarchyTypeId = $item['id'];
						$this->module->mergeTable($hierarchyTypeId);
					}
				} else {
					if ($item['count'] > $maxItemsPerType) {
						$hierarchyTypeId = $item['id'];
						$this->module->branchTable($hierarchyTypeId);
					}
				}
			}

			$buffer->push("\nwindow.location = window.location;\n");
			$buffer->end();
		}

		/**
		 * Возвращает настройки кеширования.
		 * Если передан ключевой параметр $_REQUEST['param0'] = do,
		 * то метод запустит сохранение настроек.
		 * @throws coreException
		 */
		public function cache() {
			$staticSettings = $this->module->getStaticCacheSettings();
			$streamsSettings = $this->module->getStreamsCacheSettings();

			$cacheFrontend = Service::CacheFrontend();
			$enginesList = $cacheFrontend->getCacheEngineList();
			$currentEngineName = $cacheFrontend->getCacheEngineName();

			$engines = [getLabel('cache-engine-none')];
			foreach ($enginesList as $engineName) {
				$engines[$engineName] = getLabel('cache-engine-' . $engineName);
			}

			$engines['value'] = $currentEngineName;
			$cacheEngineLabel = $currentEngineName ? getLabel('cache-engine-' . $currentEngineName) : getLabel('cache-engine-none');
			$cacheStatus = $cacheFrontend->isCacheEnabled() ? getLabel('cache-engine-on') : getLabel('cache-engine-off');
			$browserSettings = $this->module->getBrowserCacheSettings();

			$params = [
				'engine' => [
					'status:current-engine' => $cacheEngineLabel,
					'status:cache-status' => $cacheStatus,
					'select:engines' => $engines
				],
				'streamscache' => [
					'boolean:cache-enabled' => null,
					'int:cache-lifetime' => null,
				],
				'static' => [
					'boolean:enabled' => null,
					'select:expire' => [
						'short' => getLabel('cache-static-short'),
						'normal' => getLabel('cache-static-normal'),
						'long' => getLabel('cache-static-long')
					]
				],
				'browser' => [
					'status:current-browser-cache-engine' => getLabel(sprintf('%s-browser-cache', $browserSettings['current-engine'])),
					'select:browser-cache-engine' => [
						'None' => getLabel('None-browser-cache'),
						'LastModified' => getLabel('LastModified-browser-cache'),
						'EntityTag' => getLabel('EntityTag-browser-cache'),
						'Expires' => getLabel('Expires-browser-cache'),
					]
				],
				'test' => [

				],
			];

			if (isset($_REQUEST['show-something'])) {
				$dbReport = $this->module->getDatabaseReport();
				if ($dbReport) {
					$params['branching']['status:branch'] = $dbReport;
				}
			}

			if (!$staticSettings['expire']) {
				unset($params['static']['select:expire']);
			}

			if ($currentEngineName) {
				$params['engine']['status:reset'] = true;
			}

			if (!$streamsSettings['cache-enabled']) {
				unset($params['streamscache']['int:cache-lifetime']);
			}

			if (!$currentEngineName) {
				unset($params['streamscache']);
			}

			$mode = (string) getRequest('param0');
			$is_demo = isDemoMode();

			if ($mode == 'do' and !$is_demo) {
				$params = $this->expectParams($params);

				if (!isset($params['static']['select:expire'])) {
					$params['static']['select:expire'] = 'normal';
				}

				$staticSettings = [
					'enabled' => $params['static']['boolean:enabled'],
					'expire' => $params['static']['select:expire']
				];

				if (isset($params['streamscache']['boolean:cache-enabled'])) {
					$streamsSettings['cache-enabled'] = $params['streamscache']['boolean:cache-enabled'];
				}

				if (isset($params['streamscache']['int:cache-lifetime'])) {
					$streamsSettings['cache-lifetime'] = $params['streamscache']['int:cache-lifetime'];
				}

				$this->module->setStaticCacheSettings($staticSettings);
				$this->module->setStreamsCacheSettings($streamsSettings);


				$browserSettings = [
					'current-engine' => $params['browser']['select:browser-cache-engine']
				];

				$this->module->setBrowserCacheSettings($browserSettings);
				Service::CacheFrontend()->switchCacheEngine($params['engine']['select:engines']);
				$this->chooseRedirect($this->module->pre_lang . '/admin/config/cache/');
			} elseif ($mode == 'reset') {
				if (!$is_demo) {
					Service::CacheFrontend()->flush();
				}
				$this->chooseRedirect($this->module->pre_lang . '/admin/config/cache/');
			}

			$staticSettings = $this->module->getStaticCacheSettings();
			$params['static']['boolean:enabled'] = $staticSettings['enabled'];
			$params['static']['select:expire']['value'] = $staticSettings['expire'];

			if (!$staticSettings['expire']) {
				unset($params['static']['select:expire']);
			}

			$streamsSettings = $this->module->getStreamsCacheSettings();
			$params['streamscache']['boolean:cache-enabled'] = $streamsSettings['cache-enabled'];
			$params['streamscache']['int:cache-lifetime'] = $streamsSettings['cache-lifetime'];

			if (!$params['streamscache']['boolean:cache-enabled']) {
				unset($params['streamscache']['int:cache-lifetime']);
			}

			if (!$currentEngineName) {
				unset($params['streamscache']);
			}

			$this->setDataType('settings');
			$this->setActionType('modify');
			$data = $this->prepareData($params, 'settings');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает список доменов для одноименной
		 * вкладки модуля.
		 * Если передан ключевой параметр $_REQUEST['param0'] = do,
		 * то метод запустит сохранение списка.
		 * @throws coreException
		 */
		public function domains() {
			$mode = getRequest('param0');

			if ($mode == 'do') {
				if (!isDemoMode()) {
					$this->saveEditedList('domains');
				}
				$this->chooseRedirect($this->module->pre_lang . '/admin/config/domains/');
			}

			$domains = Service::DomainCollection()->getList();

			$this->setDataType('list');
			$this->setActionType('modify');
			$data = $this->prepareData($domains, 'domains');
			$this->setData($data, umiCount($domains));
			$this->doData();
		}

		/**
		 * Возвращает данные для вкладки "Свойства домена":
		 *   - seo настройки
		 *   - список зеркал домена
		 *
		 * Если передан ключевой параметр $_REQUEST['param1'] = do,
		 * то метод запустит сохранение списка и настроек.
		 * @throws coreException
		 */
		public function domain_mirrows() {
			$domainId = getRequest('param0');
			$mode = getRequest('param1');
			$regedit = Service::Registry();
			$langId = Service::LanguageDetector()->detectId();

			$seoInfo = [];
			$additionalInfo = [];
			$seoInfo['string:seo-title'] = $regedit->get("//settings/title_prefix/{$langId}/{$domainId}");
			$seoInfo['string:seo-default-title'] = $regedit->get("//settings/default_title/{$langId}/{$domainId}");
			$seoInfo['string:seo-keywords'] = $regedit->get("//settings/meta_keywords/{$langId}/{$domainId}");
			$seoInfo['string:seo-description'] = $regedit->get("//settings/meta_description/{$langId}/{$domainId}");
			$seoInfo['string:ga-id'] = $regedit->get("//settings/ga-id/{$domainId}");
			$additionalInfo['string:site_name'] = $regedit->get("//settings/site_name/{$domainId}/{$langId}/") ?
				$regedit->get("//settings/site_name/{$domainId}/{$langId}") : $regedit->get('//settings/site_name');

			$params = [
				'seo' => $seoInfo,
				'additional' => $additionalInfo,
			];

			if ($mode == 'do') {
				if (!isDemoMode()) {
					$this->saveEditedList('domain_mirrows');
					$params = $this->expectParams($params);

					$title = $params['seo']['string:seo-title'];
					$defaultTitle = $params['seo']['string:seo-default-title'];
					$keywords = $params['seo']['string:seo-keywords'];
					$description = $params['seo']['string:seo-description'];
					$gaId = $params['seo']['string:ga-id'];
					$siteName = $params['additional']['string:site_name'];

					$regedit->set("//settings/title_prefix/{$langId}/{$domainId}", $title);
					$regedit->set("//settings/default_title/{$langId}/{$domainId}", $defaultTitle);
					$regedit->set("//settings/meta_keywords/{$langId}/{$domainId}", $keywords);
					$regedit->set("//settings/meta_description/{$langId}/{$domainId}", $description);
					$regedit->set("//settings/ga-id/{$domainId}", $gaId);
					$regedit->set("//settings/site_name/{$domainId}/{$langId}", $siteName);
				}

				$this->chooseRedirect($this->module->pre_lang . '/admin/config/domain_mirrows/' . $domainId . '/');
			}

			$domain = Service::DomainCollection()->getDomain($domainId);
			if (!$domain instanceof iDomain) {
				throw new publicAdminException(getLabel('label-cannot-detect-domain'));
			}

			$mirrors = $domain->getMirrorsList();

			$this->setDataType('settings');
			$this->setActionType('modify');
			$seoData = $this->prepareData($params, 'settings');
			$mirrorsData = $this->prepareData($mirrors, 'domain_mirrows');
			$data = array_merge($seoData, $mirrorsData);
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Обновляет данные для построения sitemap.xml.
		 * Обходит страницы всех доменов и языков, используется
		 * для итеративно.
		 */
		public function update_sitemap() {
			$domainId = (int) getRequest('param0');
			$complete = false;
			$hierarchy = umiHierarchy::getInstance();
			$dirName = CURRENT_WORKING_DIR . "/sys-temp/sitemap/{$domainId}/";

			if (!is_dir($dirName)) {
				mkdir($dirName, 0777, true);
			}

			$filePath = $dirName . 'domain';
			$updater = Service::SiteMapUpdater();

			if (!file_exists($filePath)) {
				$updater->deleteByDomain($domainId);
				$elements = [];
				$langs = Service::LanguageCollection()->getList();
				/** @var lang $lang */
				foreach ($langs as $lang) {
					$elements = array_merge($elements, $hierarchy->getChildrenList(0, false, true, false, $domainId, false, $lang->getId()));
				}
				sort($elements);
				file_put_contents($filePath, serialize($elements));
			}

			$progressKey = 'sitemap_offset_' . $domainId;
			$session = Service::Session();
			$offset = (int) $session->get($progressKey);

			$blockSize = mainConfiguration::getInstance()->get('modules', 'exchange.splitter.limit') ?: 25;
			$elements = unserialize(file_get_contents($filePath));

			for ($i = $offset; $i <= $offset + $blockSize - 1; $i++) {
				if (!array_key_exists($i, $elements)) {
					$complete = true;
					break;
				}
				$element = $hierarchy->getElement($elements[$i], true, true);

				if ($element instanceof iUmiHierarchyElement) {
					$updater->update($element);
				}
			}

			$progressValue = $offset + $blockSize;
			$session->set($progressKey, $progressValue);

			if ($complete) {
				$session->del($progressKey);
				unlink($filePath);
			}

			$data = [
				'attribute:complete' => (int) $complete
			];

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает список языков для одноименной
		 * вкладки модуля.
		 * Если передан ключевой параметр $_REQUEST['param0'] = do,
		 * то метод запустит сохранение списка.
		 * @throws coreException
		 */
		public function langs() {
			$mode = getRequest('param0');

			if ($mode == 'do' && !isDemoMode()) {
				$this->saveEditedList('langs');
				$this->chooseRedirect();
			}

			$langs = Service::LanguageCollection()
				->getList();

			$this->setDataType('list');
			$this->setActionType('modify');
			$data = $this->prepareData($langs, 'langs');
			$this->setData($data, umiCount($langs));
			$this->doData();
		}

		/**
		 * Возвращает настройки отправляемых писем для вкладки "Почта".
		 * Если передан ключевой параметр $_REQUEST['param0'] = do,
		 * то метод запустит сохранение настроек.
		 * @throws coreException
		 */
		public function mails() {
			$regedit = Service::Registry();

			$params = [
				'mails' => [
					'email:admin_email' => null,
					'string:email_from' => null,
					'string:fio_from' => null
				]
			];

			$mode = getRequest('param0');

			if ($mode == 'do') {
				$params = $this->expectParams($params);

				if (!isDemoMode()) {
					$regedit->set('//settings/admin_email', $params['mails']['email:admin_email']);
					$regedit->set('//settings/email_from', $params['mails']['string:email_from']);
					$regedit->set('//settings/fio_from', $params['mails']['string:fio_from']);
				}

				$this->chooseRedirect();
			}

			$params['mails']['email:admin_email'] = $regedit->get('//settings/admin_email');
			$params['mails']['string:email_from'] = $regedit->get('//settings/email_from');
			$params['mails']['string:fio_from'] = $regedit->get('//settings/fio_from');

			$this->setDataType('settings');
			$this->setActionType('modify');
			$data = $this->prepareData($params, 'settings');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает результаты тестов безопасности
		 * для вкладки "Безопасность"
		 * @throws coreException
		 */
		public function security() {
			$params = [
				'security-audit' => []
			];

			/** @var config|ConfigTest $module */
			$module = $this->module;
			$allowedTestNames = $module->getSecurityTestNames();

			foreach ($allowedTestNames as $test) {
				$params['security-audit'][$test . ':security-' . $test] = null;
			}

			$this->setDataType('settings');
			$this->setActionType('modify');

			$data = $this->prepareData($params, 'settings');

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает наложения водяного знака для вкладки "Водяной знак".
		 * Если передан ключевой параметр $_REQUEST['param0'] = do,
		 * то метод запустит сохранение настроек.
		 * @throws coreException
		 */
		public function watermark() {
			$regedit = Service::Registry();

			$params = [
				'watermark' => [
					'string:image' => null,
					'int:alpha' => null,
					'select:valign' => [
						'top' => getLabel('watermark-valign-top'),
						'bottom' => getLabel('watermark-valign-bottom'),
						'center' => getLabel('watermark-valign-center')
					],
					'select:halign' => [
						'left' => getLabel('watermark-halign-left'),
						'right' => getLabel('watermark-halign-right'),
						'center' => getLabel('watermark-halign-center')
					]
				]
			];

			$mode = getRequest('param0');

			if ($mode == 'do') {
				$params = $this->expectParams($params);

				if (!$regedit->contains('//settings/watermark')) {
					$regedit->set('//settings/watermark', '');
				}

				$imagePath = trim($params['watermark']['string:image']);
				$imagePath = str_replace('./', '', $imagePath);

				if (mb_substr($imagePath, 0, 1) == '/') {
					$imagePath = mb_substr($imagePath, 1);
				}

				if (!empty($imagePath) && file_exists('./' . $imagePath)) {
					$imagePath = ('./' . $imagePath);
				}
				if ((int) $params['watermark']['int:alpha'] > 0 && (int) $params['watermark']['int:alpha'] <= 100) {
					$regedit->set('//settings/watermark/alpha', $params['watermark']['int:alpha']);
				}

				$regedit->set('//settings/watermark/image', $imagePath);
				$regedit->set('//settings/watermark/valign', $params['watermark']['select:valign']);
				$regedit->set('//settings/watermark/halign', $params['watermark']['select:halign']);

				$this->chooseRedirect();
			}

			$params['watermark']['string:image'] = $regedit->get('//settings/watermark/image');
			$params['watermark']['int:alpha'] = $regedit->get('//settings/watermark/alpha');

			$params['watermark']['select:valign'] = [
				'top' => getLabel('watermark-valign-top'),
				'bottom' => getLabel('watermark-valign-bottom'),
				'center' => getLabel('watermark-valign-center'),
				'value' => $regedit->get('//settings/watermark/valign')
			];
			$params['watermark']['select:halign'] = [
				'left' => getLabel('watermark-halign-left'),
				'right' => getLabel('watermark-halign-right'),
				'center' => getLabel('watermark-valign-center'),
				'value' => $regedit->get('//settings/watermark/halign')
			];

			$this->setDataType('settings');
			$this->setActionType('modify');
			$data = $this->prepareData($params, 'settings');
			$this->setData($data);
			$this->doData();
		}

		/** Возвращает настройки капчи для вкладки "CAPTCHA" */
		public function captcha() {
			$params = $this->getCaptchaParams();

			$mode = getRequest('param0');
			if ($mode == 'do') {
				$params = self::expectedParams($params);
				$this->setCommonCaptchaParams($params['captcha']);
				$this->setSiteCaptchaParams($params);
				$this->chooseRedirect();
			}

			$data = $this->prepareData($params, 'settings');
			$this->setDataType('settings');
			$this->setActionType('modify');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает настройки капчи (общие + специфические для каждого сайта)
		 * @return array
		 */
		private function getCaptchaParams() {
			return array_merge(
				$this->getCommonCaptchaParams(),
				$this->getSiteCaptchaParams()
			);
		}

		/**
		 * Возвращает настройки капчи, общие для всех сайтов
		 * @return array
		 */
		private function getCommonCaptchaParams() {
			$settings = Service::CaptchaSettingsFactory()
				->getCommonSettings();
			return [
				'captcha' => [
					'select:captcha' => $this->getCaptchaCommonSelectList(),
					'boolean:captcha-remember' => $settings->shouldRemember(),
					'string:captcha-drawer' => $settings->getDrawerName(),
					'string:recaptcha-sitekey' => $settings->getSitekey(),
					'string:recaptcha-secret' => $settings->getSecret(),
				],
			];
		}

		/**
		 * Возвращает элементы выпадающего списка для настроек капчи,
		 * плюс название текущей общей стратегии капчи
		 * @return array
		 */
		private function getCaptchaCommonSelectList() {
			$settings = Service::CaptchaSettingsFactory()
				->getCommonSettings();
			return array_merge(
				$this->getCaptchaSelectList(),
				[
					'value' => $settings->getStrategyName()
				]
			);
		}

		/**
		 * Возвращает элементы выпадающего списка для настроек капчи
		 * @return array
		 */
		private function getCaptchaSelectList() {
			return [
				'null-captcha' => getLabel('null-captcha', 'config'),
				'captcha' => getLabel('captcha', 'config'),
				'recaptcha' => getLabel('recaptcha', 'config'),
			];
		}

		/**
		 * Возвращает настройки капчи, специфические для каждого сайта на текущей языковой версии
		 * @return array
		 */
		private function getSiteCaptchaParams() {
			$domainList = Service::DomainCollection()->getList();
			$params = [];

			foreach ($domainList as $domain) {
				$domainId = $domain->getId();
				$settings = Service::CaptchaSettingsFactory()
					->getSiteSettings($domainId);
				$params["captcha-{$domainId}"] = [
					'status:domain' => $domain->getHost(),
					"boolean:use-site-settings-{$domainId}" => $settings->shouldUseSiteSettings(),
					"select:captcha-{$domainId}" => $this->getCaptchaSiteSelectList($domainId),
					"boolean:captcha-remember-{$domainId}" => $settings->shouldRemember(),
					"string:captcha-drawer-{$domainId}" => $settings->getDrawerName(),
					"string:recaptcha-sitekey-{$domainId}" => $settings->getSitekey(),
					"string:recaptcha-secret-{$domainId}" => $settings->getSecret(),
				];
			}

			return $params;
		}

		/**
		 * Возвращает элементы выпадающего списка для настроек капчи,
		 * плюс название текущей стратегии капчи для выбранного сайта
		 * @param int $domainId ИД домена сайта
		 * @return array
		 */
		private function getCaptchaSiteSelectList($domainId) {
			$settings = Service::CaptchaSettingsFactory()
				->getSiteSettings($domainId);
			return array_merge(
				$this->getCaptchaSelectList(),
				[
					'value' => $settings->getStrategyName()
				]
			);
		}

		/**
		 * Сохраняет общие настройки капчи
		 * @param array $params новые значения настроек
		 */
		private function setCommonCaptchaParams($params) {
			Service::CaptchaSettingsFactory()
				->getCommonSettings()
				->setStrategyName($params['select:captcha'])
				->setDrawerName($params['string:captcha-drawer'])
				->setShouldRemember($params['boolean:captcha-remember'])
				->setSitekey($params['string:recaptcha-sitekey'])
				->setSecret($params['string:recaptcha-secret']);
		}

		/**
		 * Сохраняет настройки капчи, специфические для каждого сайта (домен + язык)
		 * @param array $params новые значения настроек
		 */
		private function setSiteCaptchaParams($params) {
			$domainList = Service::DomainCollection()->getList();
			foreach ($domainList as $domain) {
				$domainId = $domain->getId();
				$siteParams = $params["captcha-{$domainId}"];
				$name = $siteParams["select:captcha-{$domainId}"];
				Service::CaptchaSettingsFactory()
					->getSiteSettings($domainId)->setStrategyName($name)
					->setShouldUseSiteSettings($siteParams["boolean:use-site-settings-{$domainId}"])
					->setShouldRemember($siteParams["boolean:captcha-remember-{$domainId}"])
					->setDrawerName($siteParams["string:captcha-drawer-{$domainId}"])
					->setSitekey($siteParams["string:recaptcha-sitekey-{$domainId}"])
					->setSecret($siteParams["string:recaptcha-secret-{$domainId}"]);
			}
		}
	}
