<?php

	use UmiCms\Service;

	/** Класс функционала административной панели */
	class UmiNotificationsAdmin implements iModulePart {
		use baseModuleAdmin;
		use tModulePart;

		/**
		 * Возвращает список уведомлений
		 * @return bool
		 * @throws Exception
		 */
		public function notifications() {
			$this->setDataType('list');
			$this->setActionType('view');

			if ($this->module->ifNotJsonMode()) {
				$this->setDirectCallError();
				$this->doData();
				return true;
			}

			$mailNotifications = Service::MailNotifications();
			$collectionMap = $mailNotifications->getMap();

			$idField = $collectionMap->get('ID_FIELD_NAME');
			$langIdField = $collectionMap->get('LANG_ID_FIELD_NAME');
			$domainIdField = $collectionMap->get('DOMAIN_ID_FIELD_NAME');
			$nameField = $collectionMap->get('NAME_FIELD_NAME');
			$moduleField = $collectionMap->get('MODULE_FIELD_NAME');

			$result = [];
			$total = 0;
			$limit = (int) getRequest('per_page_limit');
			$limit = ($limit === 0) ? 25 : $limit;
			$currentPage = (int) getRequest('p');
			$offset = $currentPage * $limit;

			$domainId = Service::DomainDetector()->detectId();
			$domainIdList = (array) getRequest('domain_id');

			if ($domainIdList) {
				$domainId = (int) array_shift($domainIdList);
			}

			$langId = Service::LanguageDetector()->detectId();
			$langIdList = (array) getRequest('lang_id');

			if ($langIdList) {
				$langId = (int) array_shift($langIdList);
			}

			try {
				$total = $mailNotifications->count([
					$collectionMap->get('CALCULATE_ONLY_KEY') => true,
					$langIdField => $langId,
					$domainIdField => $domainId
				]);

				if ($total === 0) {
					$this->generateNotificationsForLangAndDomain($langId, $domainId);
				}

				$queryParams = [
					$collectionMap->get('OFFSET_KEY') => $offset,
					$collectionMap->get('LIMIT_KEY') => $limit,
					$collectionMap->get('COUNT_KEY') => true,
					$collectionMap->get('LIKE_MODE_KEY') => [],
					$collectionMap->get('COMPARE_MODE_KEY') => [],
					$moduleField => cmsController::getInstance()
						->getModulesList(),
					$langIdField => $langId,
					$domainIdField => $domainId
				];

				$filtersKey = 'fields_filter';
				$filters = (isset($_REQUEST[$filtersKey]) && is_array($_REQUEST[$filtersKey])) ? $_REQUEST[$filtersKey] : [];

				$fieldNames = [
					$idField,
					$nameField,
					$moduleField
				];

				foreach ($filters as $fieldName => $fieldInfo) {
					if (!in_array($fieldName, $fieldNames)) {
						continue;
					}

					foreach ($fieldInfo as $mode => $value) {
						if ($fieldName === $moduleField) {
							$moduleLabelsToNames = array_flip($this->getModuleNamesToLabels());
							$value = $moduleLabelsToNames[$value];
						}

						if ($value === null || $value === '') {
							continue 2;
						}

						if ($mode == 'like') {
							$queryParams[$collectionMap->get('LIKE_MODE_KEY')][$fieldName] = true;
						} elseif (in_array($mode, ['ge', 'le', 'gt', 'lt', 'eq', 'ne'])) {
							$queryParams[$collectionMap->get('COMPARE_MODE_KEY')][$fieldName] = $mode;
						}

						$queryParams[$fieldName] = $value;
					}
				}

				$orders = (isset($_REQUEST['order_filter']) && is_array($_REQUEST['order_filter'])) ? $_REQUEST['order_filter'] : [];

				if (umiCount($orders) > 0) {
					$queryParams[$collectionMap->get('ORDER_KEY')] = $orders;
				}

				$notificationList = $mailNotifications->export($queryParams);

				foreach ($notificationList as &$notification) {
					$notification[$nameField] = getLabel($notification[$nameField]);
					$notification[$moduleField] = getLabel('module-' . $notification[$moduleField]);
				}

				$result['data'] = $notificationList;
				$total = $mailNotifications->count([
					$collectionMap->get('CALCULATE_ONLY_KEY') => true,
					$langIdField => $langId,
					$domainIdField => $domainId
				]);

			} catch (Exception $e) {
				$result['data']['error'] = $e->getMessage();
			}

			$result['data']['offset'] = $offset;
			$result['data']['per_page_limit'] = $limit;
			$result['data']['total'] = $total;

			$this->module->printJson($result);
		}

		/**
		 * Возвращает данные для создания форм редактирования шаблонов,
		 * которые используются в запрошенном уведомлении.
		 * Если передан $_REQUEST['param1'] = do,
		 * то сохраняет изменения шаблонов и производит перенаправление.
		 * Адрес перенаправления зависит от режима кнопки "Сохранить".
		 * @throws publicAdminException
		 */
		public function edit() {
			$mailNotifications = Service::MailNotifications();
			$notification = $mailNotifications->getById(getRequest('param0'));

			if (!$notification instanceof MailNotification) {
				throw new publicAdminException(getLabel('error-notification-not-found'));
			}

			$templates = $notification->getTemplates();
			$isSaveMode = (getRequest('param1') == 'do');

			if ($isSaveMode) {
				foreach ($templates as $template) {
					$template->setContent(getRequest($template->getName()));
					$template->commit();
				}

				$this->chooseRedirect();
			}

			$data = [
				'notification-label' => getLabel($notification->getName()),
				'mail-templates' => [
					'nodes:mail-template' => []
				]
			];

			foreach ($templates as $template) {
				$data['mail-templates']['nodes:mail-template'][] = [
					'attribute:name' => $template->getName(),
					'attribute:label' => getLabel('mail-template-' . $template->getName(), $notification->getModule()),
					'fields' => $this->getVariableNamesForTemplate($template),
					'content' => $template->getContent(),
					'type' => $template->getType()
				];
			}

			$this->setDataType('form');
			$this->setActionType('modify');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает настройки для формирования табличного контрола
		 * @return array
		 */
		public function getDatasetConfiguration() {
			return [
				'methods' => [
					[
						'title' => getLabel('smc-load'),
						'forload' => true,
						'module' => 'umiNotifications',
						'type' => 'load',
						'name' => 'notifications'
					],
					[
						'title' => getLabel('js-permissions-edit'),
						'module' => 'umiNotifications',
						'type' => 'edit',
						'name' => 'edit'
					],
				],

				'default' => 'name[400px]|module[400px]',

				'fields' => [
					[
						'name' => 'name',
						'title' => getLabel('label-name-field'),
						'type' => 'string',
						'editable' => false,
						'filterable' => false,
						'sortable' => false,
					],
					[
						'name' => 'module',
						'title' => getLabel('label-module-field'),
						'type' => 'relation',
						'multiple' => 'false',
						'options' => implode(',', $this->getModuleNamesToLabels()),
						'editable' => false,
						'sortable' => false,
					]
				]
			];
		}

		/** Возвращает конфиг модуля в формате JSON для табличного контрола */
		public function flushDatasetConfiguration() {
			$this->module->printJson($this->getDatasetConfiguration());
		}

		/**
		 * Список переведенных названий переменных для шаблона уведомления
		 * @param MailTemplate $template
		 * @return array ['variableName' => 'variableLabel', ...]
		 * @throws publicAdminException
		 */
		protected function getVariableNamesForTemplate($template) {
			$moduleName = $template->getModule();
			$module = cmsController::getInstance()
				->getModule($moduleName);

			if (!$module instanceof def_module) {
				$message = getLabel('error-label-module-not-installed', $this->getModuleName(), $moduleName);
				throw new publicAdminException($message);
			}

			$config = $module->getVariableNamesForMailTemplates();

			if (isset($config[$template->getName()])) {
				return $config[$template->getName()];
			}

			return [];
		}

		/**
		 * Переведенные имена модулей, в которых используются уведомления
		 * @return array ['moduleName' => 'moduleLabel', ...]
		 */
		protected function getModuleNamesToLabels() {
			$mailNotifications = Service::MailNotifications();
			$modules = [];

			foreach ($mailNotifications->export() as $notification) {
				$modules[$notification['module']] = getLabel('module-' . $notification['module']);
			}

			return $this->filterNotExistingModules($modules);
		}

		/**
		 * Фильтрует список модулей от неустановленных
		 * @param array $moduleList
		 *
		 * [
		 *      'name' => 'title'
		 * ]
		 *
		 * @return array
		 *
		 * [
		 *      'name' => 'title'
		 * ]
		 */
		protected function filterNotExistingModules(array $moduleList) {
			$existingModuleList = cmsController::getInstance()
				->getModulesList();
			$filteredModuleList = [];

			foreach ($moduleList as $name => $title) {
				if (in_array($name, $existingModuleList)) {
					$filteredModuleList[$name] = $title;
				}
			}

			return $filteredModuleList;
		}

		/**
		 * Создать новые уведомления для связки язык/домен.
		 * Уведомления и их шаблоны будут скопированы из уведомлений и шаблонов языка/домена по умолчанию.
		 * @param int $langId идентификатор языка
		 * @param int $domainId идентификатор домена
		 */
		protected function generateNotificationsForLangAndDomain($langId, $domainId) {
			$defaultLangId = Service::LanguageCollection()->getDefaultLang()->getId();
			$defaultDomainId = Service::DomainCollection()
				->getDefaultDomain()
				->getId();

			$mailNotifications = Service::MailNotifications();
			$mailNotificationsMap = $mailNotifications->getMap();

			$mailTemplates = Service::MailTemplates();
			$mailTemplatesMap = $mailTemplates->getMap();

			/** @var MailNotification[] $notificationList */
			$notificationList = $mailNotifications->get([
				$mailNotificationsMap->get('LANG_ID_FIELD_NAME') => $defaultLangId,
				$mailNotificationsMap->get('DOMAIN_ID_FIELD_NAME') => $defaultDomainId
			]);

			foreach ($notificationList as $notification) {
				$newNotification = $mailNotifications->create([
					$mailNotificationsMap->get('LANG_ID_FIELD_NAME') => $langId,
					$mailNotificationsMap->get('DOMAIN_ID_FIELD_NAME') => $domainId,
					$mailNotificationsMap->get('NAME_FIELD_NAME') => $notification->getName(),
					$mailNotificationsMap->get('MODULE_FIELD_NAME') => $notification->getModule()
				]);

				$templateList = $notification->getTemplates();

				foreach ($templateList as $template) {
					$mailTemplates->create([
						$mailTemplatesMap->get('NOTIFICATION_ID_FIELD_NAME') => $newNotification->getId(),
						$mailTemplatesMap->get('NAME_FIELD_NAME') => $template->getName(),
						$mailTemplatesMap->get('TYPE_FIELD_NAME') => $template->getType(),
						$mailTemplatesMap->get('CONTENT_FIELD_NAME') => $template->getContent()
					]);
				}
			}
		}
	}
