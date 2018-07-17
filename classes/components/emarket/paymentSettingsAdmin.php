<?php
	/** Класс вкладки настроек оплаты */
	class EmarketPaymentSettingsAdmin implements iModulePart{

		use baseModuleAdmin;
		use tModulePart;

		/** @var EmarketSettings настройки модуля */
		protected $settings;
		/** @const название вкладки */
		const TAB_NAME = 'paymentSettings';
		/** @var array конфиг опций вкладки */
		protected $options = [
			'order-item-default-settings' => [
				'select:item-default-tax-id' => [
					'group' => 'ORDER_ITEM_SECTION',
					'name' => 'taxRateId',
					'initialValue' => 'getOrderItemTaxList',
					'extra' => [
						'empty' => 'label-not-chosen'
					]
				],
			],
		];

		/**
		 * Конструктор
		 * @param emarket $module
		 * @throws publicAdminException
		 */
		public function __construct($module) {
			$tabsManager = $module->getConfigTabs();

			if (!$tabsManager instanceof adminModuleTabs) {
				return false;
			}

			$tabsManager->add(self::TAB_NAME);
			$this->settings = $module->getImplementedInstance($module::SETTINGS_CLASS);
		}

		/**
		 * Метод вкладки настроек оплаты
		 * @throws coreException
		 */
		public function paymentSettings() {
			$options = $this->initOptions();

			$isSaveMode = (getRequest('param0') === 'do');
			$settings = $this->settings;

			if ($isSaveMode) {
				$options = $this->expectParams($options);

				$this->forEachOption(function($group, $option, $settingGroup, $settingName) use ($settings, $options) {
					$settings->set($settingGroup, $settingName, $options[$group][$option]);
				});

				$this->chooseRedirect();
			}

			$this->forEachOption(function($group, $option, $settingGroup, $settingName) use ($settings, &$options) {
				$options[$group][$option]['value'] = $settings->get($settingGroup, $settingName);
			});

			/** @var baseModuleAdmin|emarket $module */
			$module = $this->getModule();
			$module->setDataType('settings');
			$module->setActionType('modify');
			$module->setData($module->prepareData($options, 'settings'));
			$module->doData();
		}

		/**
		 * Возвращает список ставок НДС
		 * @return array
		 *
		 * [
		 *      umiObject::getId() => umiObject::getName()
		 * ]
		 */
		protected function getOrderItemTaxList() {
			$taxGuideId = umiObjectTypesCollection::getInstance()
				->getTypeIdByGUID('tax-rate-guide');

			return umiObjectsCollection::getInstance()
				->getGuidedItems($taxGuideId);
		}
	}