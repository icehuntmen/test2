<?php

	use UmiCms\Service;

	/**
	 * Базовый класс модуля "Обмен данными".
	 *
	 * Модуль управляет следующими сущностями:
	 *
	 * 1) Сценарии экспорта;
	 * 2) Сценарии импорта;
	 *
	 * Модуль умеет выполнять сценарии экспорта и импорта
	 * и выступает посредником в интеграции 1С между запросами
	 * от 1С и api UMI.CMS.
	 * @link http://help.docs.umi-cms.ru/rabota_s_modulyami/modul_obmen_dannymi/
	 */
	class exchange extends def_module {
		/** @var array $currency_aliases псевдонимы для кодов валют */
		protected $currency_aliases = [
			'RUR' => [
				'руб',
				'руб.',
				'р',
				'rub'
			],
			'USD' => [
				'$',
				'у.е.'
			],
			'EUR' => [
				'є',
				'евро'
			]
		];

		/** Конструктор */
		public function __construct () {
			parent::__construct ();

			if (Service::Request()->isAdmin()) {

				$commonTabs = $this->getCommonTabs();

				if ($commonTabs) {
					$commonTabs->add('import');
					$commonTabs->add('export');
				}

				$this->__loadLib('admin.php');
				$this->__implement('ExchangeAdmin');

				$this->__loadLib('1CExchange.php');
				$this->__implement('OneCExchange');

				$this->loadAdminExtension();

				$this->__loadLib('customAdmin.php');
				$this->__implement('ExchangeCustomAdmin', true);
			}

			$this->loadSiteExtension();

			$this->__loadLib('customMacros.php');
			$this->__implement('ExchangeCustomMacros', true);

			$this->loadCommonExtension();
			$this->loadTemplateCustoms();
		}

		/**
		 * Возвращает ссылку на страницу, где можно отредактировать сущность модуля
		 * @param int $objectId идентификатор сущности
		 * @param bool|string $type типа сущности
		 * @return string
		 */
		public function getObjectEditLink($objectId, $type = false) {
			return $this->pre_lang . '/admin/exchange/edit/' . $objectId . '/';
		}

		/**
		 * Возвращает кол валюты по ее псевдониму
		 * @return int|string
		 */
		public function getCurrencyCodeByAlias() {
			$alias = getRequest('alias');

			foreach ($this->currency_aliases as $code => $aliases) {
				for ($i = 0; $i < umiCount($aliases); $i++) {
					if ($alias == $code ||	$alias == $aliases[$i]) {
						return $code;
					}
				}
			}

			$emarket = cmsController::getInstance()
				->getModule('emarket');

			if ($emarket instanceof emarket) {
				return $emarket->getCurrencyFacade()
					->getDefault()
					->getCode();
			}

			return 'RUR';
		}

		/**
		 * Возвращает настройки модуля из сonfig.ini
		 * @return array
		 */
		public function getTranslatorSettings() {
			$cfg = mainConfiguration::getInstance();
			$arr_settings = $cfg->getList('modules');
			$translator_settings = [];

			for ($i = 0; $i < umiCount($arr_settings); $i++) {
				$key = $arr_settings[$i];
				if (mb_strpos($key, 'exchange.translator') !== false) {
					$translator_settings[] = [
						'attribute:key' => $key,
						'node:value' => $cfg->get('modules', $key)
					];
				}
			}

			return [
				'subnodes:settings' => $translator_settings
			];
		}

		/**
		 * Создает кеш для сценария экспорта
		 * @param int $objectId идентификатор сценария экспорта
		 * @throws publicException
		 * @throws selectorException
		 */
		public function saveScenarioCache($objectId) {
			$objects = umiObjectsCollection::getInstance();
			$object = $objects->getObject($objectId);
			$format_id = $object->getValue('format');
			$exportFormat = $objects->getObject($format_id);

			if (!$exportFormat instanceof iUmiObject) {
				throw new publicException(getLabel('exchange-err-format_undefined'));
			}

			$suffix = $exportFormat->getValue('sid');

			if ($suffix != 'YML') {
				return;
			}

			$dirName = SYS_TEMP_PATH . '/yml/';

			if (!is_dir($dirName)) {
				mkdir($dirName, 0777, true);
			}

			$objectId = $object->getId();
			$array = $dirName . $objectId . 'el';
			$array2 = $dirName . $objectId . 'cat';
			$array3 = $dirName . $objectId . 'excluded';

			if (file_exists($dirName . 'categories' . $objectId)) {
				unlink($dirName . 'categories' . $objectId);
			}

			if (file_exists($array)) {
				unlink($array);
			}

			if (file_exists($array2)) {
				unlink($array2);
			}

			if (file_exists($array3)) {
				unlink($array3);
			}

			$elements = $object->getValue('elements');
			$excludedBranches = $object->getValue('excluded_elements');

			if (!umiCount($elements)) {
				$sel = new selector('pages');
				$sel->where('hierarchy')->page(0);
				$elements = $sel->result();
			}

			$excludedElements = [];
			$umiHierarchy = umiHierarchy::getInstance();

			foreach ($excludedBranches as $element) {
				if (!$element instanceof iUmiHierarchyElement) {
					continue;
				}

				$elementId = $element->getId();
				$excludedElements[$elementId] = $elementId;
				$childs = $umiHierarchy->getChildrenList($elementId);

				foreach ($childs as $childId) {
					$excludedElements[$childId] = $childId;
				}
			}

			$elementsToExport = array_diff($this->getArrayToExport($elements), $excludedElements);
			$correctElementsToExport = [];

			$counter = 0;

			foreach ($elementsToExport as $elementId) {
				$correctElementsToExport[$counter] = $elementId;
				$counter++;
			}

			$parentsToExport = array_diff($this->getParentArrayToExport($elements), $excludedElements);

			file_put_contents($array, serialize($correctElementsToExport));
			file_put_contents($array2, serialize($parentsToExport));
			file_put_contents($array3, serialize(array_values($excludedElements)));
		}

		/**
		 * Возвращает массив идентификатор объектов каталога, которые необходимо экспортировать
		 * @param iUmiHierarchyElement[] $elements массив страниц, заданых в сценарии
		 * @return array
		 * @throws selectorException
		 */
		public function getArrayToExport($elements) {
			$elementsToExport = [];

			foreach($elements as $element) {
				if (!$element instanceof iUmiHierarchyElement) {
					continue;
				}

				$sel = new selector('pages');
				$sel->types('hierarchy-type')->name('catalog', 'object');
				$sel->option('return')->value('id');
				$sel->where('hierarchy')->page($element->getId())->childs(100);

				foreach ($sel->result() as $res) {
					$elementsToExport[] = $res['id'];
				}

				$elementsToExport[] = $element->getId();
			}

			$elementsToExport = array_unique($elementsToExport);
			sort($elementsToExport);
			return $elementsToExport;
		}

		/**
		 * Возвращает массив идентификатор разделов каталога, которые необходимо экспортировать
		 * @param iUmiHierarchyElement[] $elements массив страниц, заданых в сценарии
		 * @return array
		 */
		public function getParentArrayToExport($elements) {
			$elementsToExport = [];
			$hierarchy = umiHierarchy::getInstance();

			foreach ($elements as $el) {
				if ($el instanceof iUmiHierarchyElement) {
					$id = $el->getId();
					$elementsToExport[] = $id;
				}
			}

			foreach ($elementsToExport as $key => $id) {
				$parents = $hierarchy->getAllParents($id, false, true);

				if (umiCount(array_intersect($elementsToExport, $parents))) {
					unset($elementsToExport[$key]);
				}
			}

			$elementsToExport = array_unique($elementsToExport);
			sort($elementsToExport);
			return $elementsToExport;
		}

		/**
		 * Возвращает кодировку по умолчанию для обмена данными в формате CSV
		 * @return string наименование кодировки
		 */
		public function getDefaultEncoding() {
			$config = mainConfiguration::getInstance();
			$defaultEncoding = $config->get('system', 'default-exchange-encoding');
			return $defaultEncoding ?: 'Windows-1251';
		}

		/**
		 * Возвращает объект формата по его кодовому названию
		 * @param string $code кодовое название формата
		 * @param string $type тип формата 'import' или 'export'
		 * @return iUmiObject|null
		 * @throws selectorException
		 */
		public function getFormatByCode($code, $type) {
			$exportFormatGUID = 'exchange-format-export';
			$importFormatGUID = 'exchange-format-import';

			$formatTypeGUID = '';
			switch ($type) {
				case 'export': {
					$formatTypeGUID = $exportFormatGUID;
					break;
				}
				case 'import': {
					$formatTypeGUID = $importFormatGUID;
					break;
				}
				default:
					//no default
			}

			$sel = new selector('objects');
			$sel->types('object-type')->guid($formatTypeGUID);
			$sel->where('sid')->equals($code);

			$format = $sel->first();

			if ($format instanceof iUmiObject) {
				return $format;
			}

			return null;
		}
	}
