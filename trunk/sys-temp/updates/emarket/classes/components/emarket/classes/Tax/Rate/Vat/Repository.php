<?php
	namespace UmiCms\Classes\Components\Emarket\Tax\Rate\Vat;

	use UmiCms\Classes\Components\Emarket\Tax\Rate\iVat;
	use UmiCms\Classes\Components\Emarket\Tax\Rate\Vat\iFactory as VatFactory;
	use UmiCms\System\Selector\iFactory as SelectorFactory;

	/**
	 * Класс репозитория ставок налога на добавленную стоимость (НДС)
	 * @todo: Реализовать создание и удаление валют
	 * @package UmiCms\Classes\Components\Emarket\Tax\Rate\Vat
	 */
	class Repository implements iRepository {

		/** @var VatFactory $currencyFactory фабрика валют */
		private $currencyFactory;

		/** @var SelectorFactory $selectorFactory фабрика ставок налога на добавленную стоимость (НДС) */
		private $selectorFactory;

		/** @inheritdoc */
		public function __construct(VatFactory $vatFactory, SelectorFactory $selectorFactory) {
			$this->currencyFactory = $vatFactory;
			$this->selectorFactory = $selectorFactory;
		}

		/** @inheritdoc */
		public function load($id) {
			$selector = $this->getSelector();
			$selector->where('id')->equals($id);
			$selector->limit(0, 1);

			$dataObjectList = $selector->result();

			if (!isset($dataObjectList[0]) || !$dataObjectList[0] instanceof \iUmiObject) {
				return null;
			}

			$dataObject = array_shift($dataObjectList);

			return $this->getVatFactory()
				->create($dataObject);
		}

		/**
		 * Возвращает фабрику ставок налога на добавленную стоимость (НДС)
		 * @return VatFactory
		 */
		private function getVatFactory() {
			return $this->currencyFactory;
		}

		/**
		 * Возвращает фабрику селекторов
		 * @return SelectorFactory
		 */
		private function getSelectorFactory() {
			return $this->selectorFactory;
		}

		/**
		 * Возвращает селектор
		 * @return \selector
		 */
		private function getSelector() {
			$selector = $this->getSelectorFactory()
				->createObjectTypeGuid(iVat::TYPE_GUID);
			$selector->option('ignore-children-types', true);
			$selector->option('no-length', true);
			$selector->option('load-all-props', true);
			return $selector;
		}
	}
