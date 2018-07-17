<?php

	use UmiCms\Classes\Components\Emarket\Currency;
	use UmiCms\Service;

	/**
	 * Базовый класс модуля "Интернет-магазин".
	 *
	 * Модуль управляет следующими сущностями:
	 *
	 * 1) Заказы;
	 * 2) Товарные наименования;
	 * 3) Способ оплаты;
	 * 4) Способы доставки;
	 * 5) Скидки;
	 * 6) Модификаторы скидок;
	 * 7) Правила скидок;
	 * 8) Незарегистрированные покупатели;
	 *
	 * Модуль предоставляет следующий функционал:
	 *
	 * 1) Разные схемы организации оформления заказа;
	 * 2) Почтовые уведомления менеджеру и клиенту об изменениях заказов;
	 * 3) API для мобильного приложения UMI.Manager;
	 * 4) Оплата через различные платежные системы;
	 * 5) Оплата заказа по внутреннем счету клиента;
	 * 6) Автоматическое обновление курсов валют;
	 * 7) Механизмы расчета стоимости доставок;
	 * 8) Конструктор скидок;
	 * 9) Формирование счетов на оплату заказа для физ. и юр. лиц.
	 * 10) Интеграция сервиса Яндекса "Заказ на Маркете";
	 * 11) Интеграция сервиса Яндекса "Быстрые заказ";
	 * 12) Сбор базовой статистики и предоставление отчетов;
	 * @link http://help.docs.umi-cms.ru/rabota_s_modulyami/modul_internet-magazin/
	 */
	class emarket extends def_module {
		/** @var int $iMaxCompareElements максимально количество товаров для сравнения */
		public $iMaxCompareElements;

		/** @var array $allowedReports список отчетов статистики, которые можно получить */
		public $allowedReports = [
			'RegUsers',
			'OrderAll',
			'OrderStatusNull',
			'OrderPayment',
			'OrderCanceled',
			'OrderReady',
			'SumAll',
			'SumMiddle',
			'AddItems'
		];

		/** @var array $purchaseStages список этапов оформления заказа */
		public $purchaseStages = [
			'required'
		];

		/** @var array $availableStages список этапов оформления заказа */
		public $availableStages = [
			'autofill'
		];

		/** @var order|null $currentBasket текущая корзина */
		private $currentBasket;

		/** @const имя класса управления настройками */
		const SETTINGS_CLASS = 'EmarketSettings';

		/** @const string ADMIN_CLASS имя класса административного функционала */
		const ADMIN_CLASS = 'EmarketAdmin';

		/**
		 * Конструктор
		 * @throws coreException
		 */
		public function __construct() {
			parent::__construct();
			$this->initPurchasingSteps();
			$this->initMaxCountOfComparableElements();

			$this->__loadLib('settings.php');
			$this->__implement('EmarketSettings');

			if (Service::Request()->isAdmin()) {
				$this->initTabs();

				$this->__loadLib('admin.php');
				$this->__implement(self::ADMIN_CLASS);

				$this->__loadLib('statReports.php');
				$this->__implement('EmarketStatReports');

				$this->__loadLib('umiManagerAPI.php');
				$this->__implement('EmarketUMIManagerAPI');

				$this->__loadLib('classes/delivery/api/ApiShip/ModuleApi/Admin.php');
				$this->__implement('UmiCms\Classes\Components\Emarket\Delivery\ApiShip\ModuleApi\Admin');

				$this->loadAdminExtension();

				$this->__loadLib('customAdmin.php');
				$this->__implement('EmarketCustomAdmin', true);

				$this->__loadLib('deliverySettingsAdmin.php');
				$this->__implement('EmarketDeliverySettingsAdmin');

				$this->__loadLib('paymentSettingsAdmin.php');
				$this->__implement('EmarketPaymentSettingsAdmin');
			}

			$this->__loadLib('macros.php');
			$this->__implement('EmarketMacros');

			$this->__loadLib('printInvoices.php');
			$this->__implement('EmarketPrintInvoices');

			$this->__loadLib('purchasingStages.php');
			$this->__implement('EmarketPurchasingStages');

			$this->__loadLib('purchasingStagesSteps.php');
			$this->__implement('EmarketPurchasingStagesSteps');

			$this->__loadLib('purchasingOneClick.php');
			$this->__implement('EmarketPurchasingOneClick');

			$this->__loadLib('purchasingOneStep.php');
			$this->__implement('EmarketPurchasingOneStep');

			$this->loadSiteExtension();

			$this->__loadLib('customMacros.php');
			$this->__implement('EmarketCustomMacros', true);

			$this->__loadLib('classes/delivery/api/ApiShip/ModuleApi/Common.php');
			$this->__implement('UmiCms\Classes\Components\Emarket\Delivery\ApiShip\ModuleApi\Common');

			$this->__loadLib('handlers.php');
			$this->__implement('EmarketHandlers');

			$this->__loadLib('notification.php');
			$this->__implement('EmarketNotification');

			$this->__loadLib('yandexMarketClient.php');
			$this->__implement('EmarketYandexMarketClient');

			$this->loadCommonExtension();
			$this->loadTemplateCustoms();
		}

		/**
		 * Возвращает экземпляр класса управления настройками
		 * @return EmarketSettings
		 * @throws coreException
		 */
		public function getSettings() {
			return $this->getImplementedInstance(self::SETTINGS_CLASS);
		}

		/** Инициализирует список этапов оформления заказа */
		public function initPurchasingSteps() {
			$umiRegistry = Service::Registry();

			if ($umiRegistry->get('//modules/emarket/enable-delivery')) {
				$this->purchaseStages[] = 'delivery';
			}

			if ($umiRegistry->get('//modules/emarket/enable-payment')) {
				$this->purchaseStages[] = 'payment';
			}

			$this->purchaseStages[] = 'result';
		}

		/** Инициализирует максимально количество товаров для сравнения */
		public function initMaxCountOfComparableElements() {
			$umiConfig = mainConfiguration::getInstance();
			$this->iMaxCompareElements = (int) $umiConfig->get('modules', 'emarket.compare.max-items');

			if ($this->iMaxCompareElements == 0) {
				$this->iMaxCompareElements = 3;
			}

			if ($this->iMaxCompareElements <= 1) {
				$this->iMaxCompareElements = 3;
			}
		}

		/** Инициализирует список вкладок модуля */
		public function initTabs() {
			$commonTabs = $this->getCommonTabs();
			$configTabs = $this->getConfigTabs();

			if (!$commonTabs || !$configTabs) {
				return;
			}

			$commonTabs->add('orders', ['order_edit']);
			$umiRegistry = Service::Registry();

			if ($umiRegistry->get('//modules/emarket/enable-discounts')) {
				$commonTabs->add('discounts', [
					'discount_add',
					'discount_edit'
				]);
			}

			if ($umiRegistry->get('//modules/emarket/enable-delivery')) {
				$commonTabs->add('delivery', [
					'delivery_add',
					'delivery_edit',
					'delivery_address_edit'
				]);
			}

			if ($umiRegistry->get('//modules/emarket/enable-payment')) {
				$commonTabs->add('payment', [
					'payment_add',
					'payment_edit'
				]);
			}

			if ($umiRegistry->get('//modules/emarket/enable-currency')) {
				$commonTabs->add('currency', [
					'currency_add',
					'currency_edit'
				]);
			}

			if ($umiRegistry->get('//modules/emarket/enable-stores')) {
				$commonTabs->add('stores', [
					'store_add',
					'store_edit'
				]);
			}

			$commonTabs->add('stats');
			$configTabs->add('config');
			$configTabs->add('mail_config');
			$configTabs->add('yandex_market_config');
		}

		/**
		 * Возвращает ссылку на страницу, где можно отредактировать сущность модуля
		 * @param int $objectId идентификатор сущности
		 * @param bool|string $type тип сущности
		 * @return bool|string
		 */
		public function getObjectEditLink($objectId, $type = false) {
			switch ($type) {
				case 'order' : {
					return $this->pre_lang . "/admin/emarket/order_edit/{$objectId}/";
				}
				case 'discount' : {
					return $this->pre_lang . "/admin/emarket/discount_edit/{$objectId}/";
				}
				case 'currency' : {
					return $this->pre_lang . "/admin/emarket/currency_edit/{$objectId}/";
				}
				case 'delivery' : {
					return $this->pre_lang . "/admin/emarket/delivery_edit/{$objectId}/";
				}
				case 'payment' : {
					return $this->pre_lang . "/admin/emarket/payment_edit/{$objectId}/";
				}
				case 'store' : {
					return $this->pre_lang . "/admin/emarket/store_edit/{$objectId}/";
				}
				default: {
					return false;
				}
			}
		}

		/**
		 * Возвращает текущую корзину
		 * @return null|order
		 */
		public function getCurrentBasket() {
			return $this->currentBasket;
		}

		/**
		 * Устанавливает текущую корзину
		 * @param order|null $basket корзина
		 * @return $this
		 */
		public function setCurrentBasket(order $basket = null) {
			$this->currentBasket = $basket;
			return $this;
		}

		/**
		 * Возвращает контрольную сумму строки
		 * @param string $string строка
		 * @return string
		 */
		public function getCheckSum($string) {
			$config = mainConfiguration::getInstance();
			$salt = $config->get('system', 'salt');
			return md5($string . $salt);
		}

		/**
		 * Возвращает параметры ссылки личного кабинета покупателя
		 * @param int $customerId ID покупателя
		 * @return string
		 */
		public function getPersonalLinkParams($customerId) {
			$checkSum = $this->getCheckSum($customerId);
			return $customerId . '/' . $checkSum;
		}

		/**
		 * Возвращает заказ, который представляет собой текущую корзину пользователя
		 * @param bool $useDummyOrder использовать заказ-заглушку
		 * @return order
		 */
		public function getBasketOrder($useDummyOrder = true) {
			$currentBasket = $this->getCurrentBasket();

			if ($currentBasket instanceof order) {
				$objectCollection = umiObjectsCollection::getInstance();
				$currentId = $currentBasket->getId();
				$correctObject = ($objectCollection->isLoaded($currentId) || $objectCollection->isExists($currentId));

				if ($correctObject && $currentBasket->isOrderBasket() && $useDummyOrder) {
					return $currentBasket;
				}
			}

			$domainId = Service::DomainDetector()
				->detectId();

			$basket = customer::get()
				->getBasketByDomainId($domainId, $useDummyOrder);

			return $this->setCurrentBasket($basket)
				->getCurrentBasket();
		}

		/**
		 * Возвращает стоимость товара (объекта каталога)
		 * @param iUmiHierarchyElement $element товар
		 * @param bool $ignoreDiscounts игнорировать скидки
		 * @return Float
		 */
		public function getPrice(iUmiHierarchyElement $element, $ignoreDiscounts = false) {
			$price = $element->getValue('price');

			if (!$ignoreDiscounts) {
				$discount = itemDiscount::search($element);

				if ($discount instanceof discount) {
					$price = $discount->recalcPrice($price);
				}
			}

			return $price;
		}

		/**
		 * Возвращает товарное наименование из текущей корзины,
		 * соответствующее товару (объекту каталога)
		 * @param int $elementId идентификатор товара (объекта каталога)
		 * @param bool $autoCreate автоматически создать товарное наименование,
		 * если оно не было задано
		 * @return null|orderItem
		 */
		public function getBasketItem($elementId, $autoCreate = true) {
			$order = $this->getBasketOrder();
			$orderItems = $order->getItems();

			/** @var orderItem $orderItem */
			foreach ($orderItems as $key => $orderItem) {
				$element = $orderItem->getItemElement(true);

				if (!$element instanceof iUmiHierarchyElement) {
					$order->removeItem($orderItem);
					continue;
				}

				if ($element->getId() == $elementId) {
					return $orderItem;
				}
			}

			return $autoCreate ? orderItem::create($elementId) : null;
		}

		/**
		 * Возвращает true в случае если, в системе присутствуют только способы доставки
		 * типа "Самовывоз" или способы доставки вообще отстутствуют.
		 * @return boolean
		 */
		public function isOnlySelfDeliveryExist() {
			$selfDeliveryTypeId = $this->getSelfDeliveryTypeId();

			$deliveries = new selector('objects');
			$deliveries->types('hierarchy-type')->name('emarket', 'delivery');
			$deliveries->option('return')->value('delivery_type_id');
			$result = $deliveries->result();

			if (umiCount($result) > 0) {
				foreach ($result as $delivery) {
					$deliveryTypeId = (int) $delivery['delivery_type_id'];

					if ($deliveryTypeId !== $selfDeliveryTypeId) {
						return false;
					}
				}
			}

			return true;
		}

		/**
		 * Возвращает true, если среди способов доставки присутствует
		 * хотя бы один типа "Самовывоз"
		 * @return boolean
		 */
		public function isSelfDeliveryExist() {
			$selfDeliveryTypeId = $this->getSelfDeliveryTypeId();

			$selfDeliveries = new selector('objects');
			$selfDeliveries->types('hierarchy-type')->name('emarket', 'delivery');
			$selfDeliveries->where('delivery_type_id')->equals($selfDeliveryTypeId);

			return $selfDeliveries->length() > 0;
		}

		/**
		 * Возвращает ID объекта "Самовывоз" из справочника "Типы доставки"
		 * @return bool|int
		 * @throws selectorException
		 */
		public function getSelfDeliveryTypeId() {
			$deliveryTypes = new selector('objects');
			$deliveryTypes->types('object-type')->guid('emarket-deliverytype');
			$deliveryTypes->where('class_name')->equals('self');
			$selfDelivery = $deliveryTypes->first;

			if (!$selfDelivery instanceof iUmiObject) {
				return false;
			}

			/** @var iUmiObject $selfDelivery */
			return $selfDelivery->getId();
		}

		/**
		 * Возвращает список скидок заданого типа
		 * @param bool|string $codeName тип скидки, если не передан - вернет все скидки.
		 * @param bool $resetCache игнорировать внутренний кеш
		 * @return array|mixed
		 * @throws selectorException
		 */
		public function getAllDiscounts($codeName = false, $resetCache = false) {
			static $discounts = [];

			if ($resetCache || cmsController::$IGNORE_MICROCACHE) {
				$discounts = [];
			}

			if ($codeName && isset($discounts[$codeName])) {
				return $discounts[$codeName];
			}

			if (isset($discounts['all'])) {
				return $discounts['all'];
			}

			$sel = new selector('objects');
			$sel->types('hierarchy-type')->name('emarket', 'discount');
			$sel->where('is_active')->equals(true);
			$sel->option('load-all-props')->value(true);
			$sel->option('no-length')->value(true);

			if ($codeName) {
				$sel->where('discount_type_id')->equals(discount::getTypeId($codeName));
			} else {
				$codeName = 'all';
			}

			$discounts[$codeName] = $sel->result();
			$relatedEntityIdList = [];

			/** @var iUmiObject $discount */
			foreach ($discounts[$codeName] as $discount) {
				$typeId = $discount->getValue('discount_type_id');

				if ($typeId) {
					$relatedEntityIdList[] = $typeId;
				}

				$modifierId = $discount->getValue('discount_modificator_id');

				if ($modifierId) {
					$relatedEntityIdList[] = $modifierId;
				}

				$ruleIdList = $discount->getValue('discount_rules_id');

				if (!is_array($ruleIdList)) {
					continue;
				}

				foreach ($ruleIdList as $ruleId) {
					$relatedEntityIdList[] = $ruleId;
				}
			}

			$relatedEntityIdList = array_unique($relatedEntityIdList);
			umiObjectProperty::loadPropsData($relatedEntityIdList);

			return $discounts[$codeName];
		}

		/**
		 * Возвращает список полей, которые будут участвовать в сравнениии товаров
		 * @param int $element_id идентификатор товара (объекта каталога)
		 * @param string $groups_names перечень строковых идентификатор групп полей,
		 * разделенные пробелами
		 * @return iUmiField[]|bool
		 * @throws coreException
		 */
		public function getComparableFields($element_id, $groups_names = '') {
			$element = umiHierarchy::getInstance()->getElement($element_id);

			if (!$element instanceof iUmiHierarchyElement) {
				return false;
			}

			$type_id = $element->getObjectTypeId();
			$type = umiObjectTypesCollection::getInstance()->getType($type_id);

			if (!$type instanceof iUmiObjectType) {
				return false;
			}

			if (empty($groups_names)) {
				$fields = $type->getAllFields(true);
			} else {
				$groups_names = trim($groups_names);
				$groups_names = mb_strlen($groups_names) ? explode(' ', $groups_names) : [];
				$groups_arr = $type->getFieldsGroupsList();
				$fields = [];

				/** @var iUmiFieldsGroup $group */
				foreach ($groups_arr as $group) {
					if (!$group->getIsActive() || !in_array($group->getName(), $groups_names)) {
						continue;
					}

					foreach ($group->getFields() as $groupField) {
						$fields[] = $groupField;
					}
				}
			}

			$res = [];

			/** @var iUmiField $field */
			foreach ($fields as $field) {
				if (!$field->getIsVisible() || $field->getName() == 'price') {
					continue;
				}

				$res[$field->getName()] = $field;
			}

			return $res;
		}

		/**
		 * Убирает у всех складов флаг "Основной".
		 * @param int $exceptId идентификатор склада, у которого не нужно убирать флаг
		 * @return bool
		 * @throws selectorException
		 */
		public function clearPrimary($exceptId = 0) {
			$stores = new selector('objects');
			$stores->types('object-type')->name('emarket', 'store');
			$stores->option('load-all-props')->value(true);
			$stores = $stores->result();
			/** @var iUmiObject $store */
			foreach ($stores as $store) {
				if ($exceptId == $store->getId()) {
					continue;
				}

				$store->setValue('primary', 0);
				$store->commit();
			}

			return true;
		}

		/**
		 * Возвращает имена полей сущностей
		 * @param string $serviceName название сервиса, работающего с сущностью
		 * @return array
		 * @throws publicAdminException
		 */
		public function getEntityFieldsKeys($serviceName) {
			$serviceContainer = ServiceContainerFactory::create();
			/** @var iUmiConstantMapInjector $collection */
			$collection = $serviceContainer->get($serviceName);
			$map = $collection->getMap();
			switch ($serviceName) {
				case 'ApiShipOrders' : {
					return [
						$map->get('ID_FIELD_NAME'),
						$map->get('EXTERNAL_ID_FIELD_NAME'),
						$map->get('STATUS_ID_FIELD_NAME'),
						$map->get('UPDATE_TIME_FIELD_NAME'),
						$map->get('CREATE_TIME_FIELD_NAME')
					];
				}
			}

			$exceptionMessage = sprintf(getLabel('label-api-ship-error-unsupported-service-name', get_class($this)));
			throw new publicAdminException($exceptionMessage);
		}

		/**
		 * Возвращает идентификатор поля товара, в котором должен храниться вес
		 * @return int
		 */
		public function getProductWeightFieldId() {
			return (int) $this->getSettings()->get(EmarketSettings::ORDER_ITEM_SECTION, 'weightField');
		}

		/**
		 * Доступны ли способы доставки для оформления заказа
		 * @return bool
		 */
		public function isDeliveryAvailable() {
			return in_array('delivery', $this->purchaseStages);
		}

		/**
		 * Доступны ли способы оплаты для оформления заказа
		 * @return bool
		 */
		public function isPaymentAvailable() {
			return in_array('payment', $this->purchaseStages);
		}

		/**
		 * Возвращает экземпляр класса фасада валют
		 * @return \UmiCms\Classes\Components\Emarket\Currency\iFacade
		 */
		public function getCurrencyFacade() {
			return Service::get('Currencies');
		}


		/** @inheritdoc */
		protected function prepareClassesForAutoload($classes) {
			$umiConfig = mainConfiguration::getInstance();

			/** Используемый генератор номера заказа */
			$orderNumberPrefix = $umiConfig->get('modules', 'emarket.numbers');
			$selectedOrderNumberPath = dirname(__FILE__) . '/classes/orders/number/' . $orderNumberPrefix . '.php';

			if (is_file($selectedOrderNumberPath)) {
				$orderNumberClassName = $orderNumberPrefix . 'OrderNumber';
				$classes[$orderNumberClassName] = [$selectedOrderNumberPath];
			} else {
				$classes['defaultOrderNumber'] = [dirname(__FILE__) . '/classes/orders/number/default.php'];
			}

			return $classes;
		}

		/** @inheritdoc */
		public function getVariableNamesForMailTemplates() {
			$commonVariables = [
				'order_id' => getLabel('mail-template-variable-order_id', 'emarket'),
				'order_name' => getLabel('mail-template-variable-order_name', 'emarket'),
				'order_number' => getLabel('mail-template-variable-order_number', 'emarket'),
				'domain' => getLabel('mail-template-variable-domain', 'emarket'),
				'total_amount' => getLabel('mail-template-variable-total_amount', 'emarket'),
				'total_price' => getLabel('mail-template-variable-total_price', 'emarket'),
				'suffix' => getLabel('mail-template-variable-suffix', 'emarket'),
			];

			$itemVariables = [
				'link' => getLabel('mail-template-variable-link', 'emarket'),
				'name' => getLabel('mail-template-variable-name', 'emarket'),
				'price' => getLabel('mail-template-variable-price', 'emarket'),
				'suffix' => getLabel('mail-template-variable-suffix', 'emarket'),
				'amount' => getLabel('mail-template-variable-amount', 'emarket'),
			];

			$statusVariables = [
				'parse.emarket-status-notification-item.items' => getLabel('mail-template-variable-items', 'emarket'),
				'status' => getLabel('mail-template-variable-status', 'emarket'),
				'personal_params' => getLabel('mail-template-variable-personal_params', 'emarket'),
			];

			$receiptVariables = [
				'receipt_signature' => getLabel('mail-template-variable-receipt_signature', 'emarket'),
			];

			$neworderVariables = [
				'parse.emarket-neworder-notification-item.items' => getLabel('mail-template-variable-items', 'emarket'),
				'payment_type' => getLabel('mail-template-variable-payment_type', 'emarket'),
				'payment_status' => getLabel('mail-template-variable-payment_status', 'emarket'),
				'first_name' => getLabel('mail-template-variable-first_name', 'emarket'),
				'last_name' => getLabel('mail-template-variable-last_name', 'emarket'),
				'email' => getLabel('mail-template-variable-email', 'emarket'),
				'phone' => getLabel('mail-template-variable-phone', 'emarket'),
				'delivery' => getLabel('mail-template-variable-delivery', 'emarket'),
				'address' => getLabel('mail-template-variable-address', 'emarket'),
			];

			$invoiceVariables = [
				'domain' => getLabel('mail-template-variable-domain', 'emarket'),
				'invoice_link' => getLabel('mail-template-variable-invoice_link', 'emarket'),
			];

			return [
				'emarket-status-notification-subject' => [
					'header' => getLabel('mail-template-variable-header', 'emarket'),
				],
				'emarket-status-notification-content' => $commonVariables + $statusVariables,
				'emarket-status-notification-receipt' => $commonVariables + $statusVariables + $receiptVariables,
				'emarket-status-notification-item' => $itemVariables,

				'emarket-neworder-notification-subject' => [
					'header' => getLabel('mail-template-variable-header', 'emarket'),
				],
				'emarket-neworder-notification-content' => $commonVariables + $neworderVariables,
				'emarket-neworder-notification-item' => $itemVariables,

				'emarket-invoice-subject' => $invoiceVariables,
				'emarket-invoice-content' => $invoiceVariables
			];
		}

		/** @deprecated */
		public static function isBasket(order $order) {
			return $order->isOrderBasket();
		}

		/**
		 * @deprecated
		 * @return iUmiObject
		 */
		public function getCurrency($codeName) {
			/** @var Currency $currency */
			$currency = $this->getCurrencyFacade()
				->getByCode($codeName);
			return $currency->getDataObject();
		}

		/**
		 * @deprecated
		 * @return iUmiObject
		 */
		public function getDefaultCurrency() {
			/** @var Currency $currency */
			$currency = $this->getCurrencyFacade()
				->getDefault();
			return $currency->getDataObject();
		}

		/**
		 * @deprecated
		 * @return iUmiObject
		 */
		public function getCurrentCurrency() {
			/** @var Currency $currency */
			$currency = $this->getCurrencyFacade()
				->getCurrent();
			return $currency->getDataObject();
		}

		/**
		 * @deprecated
		 * @return iUmiObject[]
		 */
		public function getCurrencyList() {
			$currencyDataObjectList = [];

			/** @var Currency $currency */
			foreach ($this->getCurrencyFacade()->getList() as $currency) {
				$currencyDataObjectList[] = $currency->getDataObject();
			}

			return $currencyDataObjectList;
		}
	}
