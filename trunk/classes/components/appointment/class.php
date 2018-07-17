<?php

	use UmiCms\Service;

	/**
	 * Базовый класс модуля "Онлайн-запись"
	 *
	 * Модуль управляет следующими сущностями:
	 *
	 * 1) Заявки на запись;
	 * 2) Группы услуг;
	 * 3) Услуги;
	 * 4) Сотрудники;
	 * 5) Страницы с данными для виджета;
	 * 6) Рабочие часы сотрудника;
	 * 7) Услуги, предоставляемые сотрудником;
	 *
	 * Модуль предоставляет функционал для получения данных он сущностях модуля и создании заявок на запись.
	 * Пример реализации виджета для записи на прием поставляется с шаблоном "demodizzy".
	 */
	class appointment extends def_module {

		/** @var string $timePregReplacePattern шаблон для удаления значений секунд у времени */
		public $timePregReplacePattern = '/(:[0-9]{2})$/';

		/** @var string $timeValidatePattern шаблон для валидации передаваемых значений времени */
		public $timeValidatePattern = '/^[0-9]{2}:[0-9]{2}$/';

		/** @var string $defaultSeconds секунды, по умолчанию добавляемые к переданным значениями времени */
		public $defaultSeconds = ':00';

		/** @var string $dateFormat формат даты */
		public $dateFormat = 'd.m.Y';

		/** Конструктор */
		public function __construct() {
			parent::__construct();

			if (Service::Request()->isAdmin()) {
				$this->initTabs();
				$this->includeAdminClasses();
			}

			$this->includeCommonClasses();
		}

		/**
		 * Возвращает ссылку на редактирование страницы с данными для виджета записи
		 * @param int $elementId идентификатор страницы
		 * @return array
		 */
		public function getEditLink($elementId) {
			return [
				false,
				$this->pre_lang . "/admin/appointment/editPage/{$elementId}/"
			];
		}

		/**
		 * Возвращает часы работы сервиса
		 * @return array
		 */
		public function getDefaultSchedule() {
			$umiRegistry = Service::Registry();
			$daysNumber = [0, 1, 2, 3, 4, 5, 6];
			$timePattern = $this->timeValidatePattern;
			$registryKeyPartFrom = '//modules/appointment/work-time-%d-from';
			$registryKeyPartTo = '//modules/appointment/work-time-%d-to';
			$result = [];

			foreach ($daysNumber as $dayNumber) {
				$timeFrom = (string) $umiRegistry->get(sprintf($registryKeyPartFrom, $dayNumber));

				if (!preg_match($timePattern, $timeFrom)) {
					continue;
				}

				$timeTo = (string) $umiRegistry->get(sprintf($registryKeyPartTo, $dayNumber));

				if (!preg_match($timePattern, $timeTo)) {
					continue;
				}

				$result[$dayNumber] = $timeFrom . '-' . $timeTo;
			}

			return $result;
		}

		/**
		 * Возвращает группы услуг по указанными параметрам
		 * @param array $params параметры
		 * @return AppointmentServiceGroup[]
		 * @throws Exception
		 */
		public function getServiceGroups(array $params = []) {
			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentServiceGroupsCollection $groupsCollection */
			$groupsCollection = $serviceContainer->get('AppointmentServiceGroups');
			return $groupsCollection->get($params);
		}

		/**
		 * Возвращает услуги по указанными параметрам
		 * @param array $params параметры
		 * @return AppointmentService[]
		 * @throws Exception
		 */
		public function getServices(array $params = []) {
			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentServicesCollection $servicesCollection */
			$servicesCollection = $serviceContainer->get('AppointmentServices');
			return $servicesCollection->get($params);
		}

		/**
		 * Возвращает сотрудников по указанными параметрам
		 * @param array $params параметры
		 * @return AppointmentEmployee[]
		 * @throws Exception
		 */
		public function getEmployees(array $params = []) {
			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentEmployeesCollection $employeesCollection */
			$employeesCollection = $serviceContainer->get('AppointmentEmployees');
			return $employeesCollection->get($params);
		}

		/**
		 * Возвращает услуги, оказаваемые сотрудниками по параметрам
		 * @param array $params параметры
		 * @return AppointmentEmployeeService[]
		 * @throws Exception
		 */
		public function getEmployeesServices(array $params = []) {
			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentEmployeesServicesCollection $employeesServicesCollection */
			$employeesServicesCollection = $serviceContainer->get('AppointmentEmployeesServices');
			return $employeesServicesCollection->get($params);
		}

		/**
		 * Возвращает рабочие часы сотрудников по параметрам
		 * @param array $params параметры
		 * @return AppointmentEmployeeSchedule[]
		 * @throws Exception
		 */
		public function getEmployeesSchedules(array $params = []) {
			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentEmployeesSchedulesCollection $employeesSchedulesCollection */
			$employeesSchedulesCollection = $serviceContainer->get('AppointmentEmployeesSchedules');
			return $employeesSchedulesCollection->get($params);
		}

		/**
		 * Возвращает названия групп услуг
		 * @return array
		 */
		public function getServicesGroupsNames() {
			$serviceGroups = $this->getServiceGroups();
			$serviceGroupsNames = [];

			/** @var AppointmentServiceGroup $group */
			foreach ($serviceGroups as $group) {
				$serviceGroupsNames[$group->getId()] = $group->getName();
			}

			return $serviceGroupsNames;
		}

		/**
		 * Возвращает названия статусов заявок на запись
		 * @return array
		 */
		public function getStatuses() {
			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentOrdersCollection $ordersCollection */
			$ordersCollection = $serviceContainer->get('AppointmentOrders');
			$map = $ordersCollection->getMap();

			$orderStatusesIds = [
				$map->get('ORDER_STATUS_NOT_CONFIRMED'),
				$map->get('ORDER_STATUS_CONFIRMED'),
				$map->get('ORDER_STATUS_DECLINED')
			];

			$orderStatuses = [];

			foreach ($orderStatusesIds as $orderStatusId) {
				$orderStatuses[$orderStatusId] = getLabel('appointment-status-' . $orderStatusId, 'appointment');
			}

			return $orderStatuses;
		}

		/**
		 * Возвращает подтвержденные заявки на запись на даты, больше текущей
		 * @return AppointmentOrder[]
		 * @throws Exception
		 */
		public function getBookedOrders() {
			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentOrdersCollection $ordersCollection */
			$ordersCollection = $serviceContainer->get('AppointmentOrders');
			$map = $ordersCollection->getMap();

			$time = new DateTime();
			$time->setTime(0, 0);
			$time->format('DDMMYYYY');
			$time = $time->getTimestamp();

			$params = [
				$map->get('ORDER_DATE_FIELD_NAME') => $time,
				$map->get('STATUS_ID_FIELD_NAME') => $map->get('ORDER_STATUS_CONFIRMED'),
				$map->get('COMPARE_MODE_KEY') => [
					$map->get('ORDER_DATE_FIELD_NAME') => '>='
				]
			];

			return $ordersCollection->get($params);
		}

		/**
		 * Возвращает названия услуг
		 * @return array
		 */
		public function getServicesNames() {
			$services = $this->getServices();
			$servicesNames = [];

			/** @var AppointmentService $service */
			foreach ($services as $service) {
				$servicesNames[$service->getId()] = $service->getName();
			}

			return $servicesNames;
		}

		/**
		 * Возвращает имена сотрудников
		 * @return array
		 */
		public function getEmployeesNames() {
			$employees = $this->getEmployees();
			$employeesNames = [];

			/** @var AppointmentEmployee $employee */
			foreach ($employees as $employee) {
				$employeesNames[$employee->getId()] = $employee->getName();
			}

			return $employeesNames;
		}

		/**
		 * Возвращает имена полей сущностей
		 * @param string $serviceName название сервиса, работающего с сущностью
		 * @return array
		 */
		public function getEntityFieldsKeys($serviceName) {
			$serviceContainer = ServiceContainerFactory::create();
			/** @var iUmiConstantMapInjector $collection */
			$collection = $serviceContainer->get($serviceName);
			$map = $collection->getMap();
			switch ($serviceName) {
				case 'AppointmentOrders' : {
					return [
						$map->get('NAME_FIELD_NAME'),
						$map->get('PHONE_FIELD_NAME'),
						$map->get('EMAIL_FIELD_NAME'),
						$map->get('DATE_FIELD_NAME'),
						$map->get('TIME_FIELD_NAME'),
						$map->get('STATUS_ID_FIELD_NAME'),
						$map->get('COMMENT_FIELD_NAME'),
						$map->get('SERVICE_ID_FIELD_NAME'),
						$map->get('EMPLOYEE_ID_FIELD_NAME')
					];
				}
				case 'AppointmentEmployees' : {
					return [
						$map->get('NAME_FIELD_NAME'),
						$map->get('PHOTO_FIELD_NAME'),
						$map->get('DESCRIPTION_FIELD_NAME')
					];
				}
				case 'AppointmentServices' : {
					return [
						$map->get('GROUP_ID_FIELD_NAME'),
						$map->get('NAME_FIELD_NAME'),
						$map->get('TIME_FIELD_NAME'),
						$map->get('PRICE_FIELD_NAME')
					];
				}
				case 'AppointmentServiceGroups' : {
					return [
						$map->get('NAME_FIELD_NAME')
					];
				}
			}
		}

		/**
		 * Возвращает идентификатор случайного сотрудника, которые умеет оказывать заданную услугу
		 * @param int $serviceId идентификатор услуги
		 * @return int
		 * @throws publicAdminException
		 */
		public function getRandomEmployeeIdByServiceId($serviceId) {
			$serviceContainer = ServiceContainerFactory::create();
			/** @var AppointmentEmployeesServicesCollection $employeesServicesCollection */
			$employeesServicesCollection = $serviceContainer->get('AppointmentEmployeesServices');
			$employeesIds = $this->getEmployeesServices(
				[
					$employeesServicesCollection->getMap()->get('SERVICE_ID_FIELD_NAME') => $serviceId
				]
			);

			if (umiCount($employeesIds) == 0) {
				throw new publicAdminException(getLabel('error-employee-by-service-not-found', 'appointment'));
			}

			$arrayKey = array_rand($employeesIds);
			/** @var AppointmentEmployeeService $employeeService */
			$employeeService = $employeesIds[$arrayKey];
			return $employeeService->getEmployeeId();
		}

		/** @inheritdoc */
		public function getVariableNamesForMailTemplates() {
			return [
				'new-record-admin-notify-content' => [
					'category' => getLabel('label-field-service-group', 'appointment'),
					'service' => getLabel('label-field-order-service', 'appointment'),
					'name' => getLabel('label-field-customer-name', 'appointment'),
					'phone' => getLabel('label-field-customer-phone', 'appointment'),
					'email' => getLabel('label-field-customer-email', 'appointment'),
					'comment' => getLabel('label-field-order-comment', 'appointment'),
					'date' => getLabel('label-field-order-date', 'appointment'),
					'time' => getLabel('label-field-order-time', 'appointment'),
					'specialist' => getLabel('label-field-order-employee', 'appointment'),
				],

				'new-record-user-notify-content' => [
					'category' => getLabel('label-field-service-group', 'appointment'),
					'service' => getLabel('label-field-order-service', 'appointment'),
					'date' => getLabel('label-field-order-date', 'appointment'),
					'time' => getLabel('label-field-order-time', 'appointment'),
					'specialist' => getLabel('label-field-order-employee', 'appointment'),
				],

				'record-status-changed-user-notify-content' => [
					'category' => getLabel('label-field-service-group', 'appointment'),
					'service' => getLabel('label-field-order-service', 'appointment'),
					'date' => getLabel('label-field-order-date', 'appointment'),
					'time' => getLabel('label-field-order-time', 'appointment'),
					'specialist' => getLabel('label-field-order-employee', 'appointment'),
					'new-status' => getLabel('label-field-order-status', 'appointment')
				],

				'new-record-admin-notify-subject' => [
					'category' => getLabel('label-field-service-group', 'appointment'),
					'service' => getLabel('label-field-order-service', 'appointment'),
				],

				'new-record-user-notify-subject' => [
					'category' => getLabel('label-field-service-group', 'appointment'),
					'service' => getLabel('label-field-order-service', 'appointment'),
				],

				'record-status-changed-user-notify-subject' => [
					'category' => getLabel('label-field-service-group', 'appointment'),
					'service' => getLabel('label-field-order-service', 'appointment'),
				]
			];
		}

		/** Создает вкладки административной панели модуля */
		protected function initTabs() {
			$configTabs = $this->getConfigTabs();

			if ($configTabs) {
				$configTabs->add('config');
				$configTabs->add('serviceWorkingTime');
			}

			$commonTabs = $this->getCommonTabs();

			if ($commonTabs instanceof iAdminModuleTabs) {
				$commonTabs->add('orders');
				$commonTabs->add('services');
				$commonTabs->add('employees');
				$commonTabs->add('pages');
			}
		}

		/** Подключает классы функционала административной панели */
		protected function includeAdminClasses() {
			$this->__loadLib('admin.php');
			$this->__implement('AppointmentAdmin');

			$this->loadAdminExtension();

			$this->__loadLib('customAdmin.php');
			$this->__implement('AppointmentCustomAdmin', true);
		}

		/** Подключает общие классы функционала */
		protected function includeCommonClasses() {
			$this->__loadLib('macros.php');
			$this->__implement('AppointmentMacros');

			$this->loadSiteExtension();

			$this->__loadLib('customMacros.php');
			$this->__implement('AppointmentCustomMacros', true);

			$this->__loadLib('notifier.php');
			$this->__implement('AppointmentNotifier', true);

			$this->__loadLib('handlers.php');
			$this->__implement('AppointmentHandlers');

			$this->loadCommonExtension();
			$this->loadTemplateCustoms();
		}
	}
