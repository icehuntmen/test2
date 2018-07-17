<?php
	namespace UmiCms\Classes\Components\Emarket\Currency;

	use UmiCms\Classes\Components\Emarket\iCurrency;

	/**
	 * Класс фасада валют
	 * @package UmiCms\Classes\Components\Emarket\Currency
	 */
	class Facade implements iFacade {

		/** @var iRepository $repository репозиторий валют */
		private $repository;

		/** @var iCollection $collection коллекция валют */
		private $collection;

		/** @var \iConfiguration $configuration конфигурация системы */
		private $configuration;

		/** @var iCalculator $calculator калькулятор валют */
		private $calculator;

		/** @inheritdoc */
		public function __construct(
			iRepository $repository, iCollection $collection, \iConfiguration $configuration, iCalculator $calculator
		) {
			$this->repository = $repository;
			$this->collection = $collection;
			$this->configuration = $configuration;
			$this->calculator = $calculator;
		}

		/** @inheritdoc */
		public function getList() {
			return $this->getCollection()
				->getAll();
		}

		/** @inheritdoc */
		public function getDefault() {
			$defaultCode = (string) $this->getConfiguration()
				->get('system', 'default-currency');

			if (!$defaultCode) {
				throw new \coreException('Default currency is not defined (system.default-currency)');
			}

			return $this->getByCode($defaultCode);
		}

		/** @inheritdoc */
		public function setDefault(iCurrency $currency) {
			$configuration = $this->getConfiguration();
			$configuration->set('system', 'default-currency', $currency->getCode());
			$configuration->save();
			return $this;
		}

		/** @inheritdoc */
		public function isDefault(iCurrency $currency) {
			return $this->getDefault()->getId() === $currency->getId();
		}

		/** @inheritdoc */
		public function getCurrent() {
			/** @todo: произвести рефакторинг customer и передать новый класс в зависимость этому */
			$id = \customer::get()
				->getCurrencyId();
			$currency = $this->getRepository()
				->load($id);

			if ($currency instanceof iCurrency) {
				return $currency;
			}

			return $this->getDefault();
		}

		/** @inheritdoc */
		public function setCurrent(iCurrency $currency) {
			/** @todo: произвести рефакторинг customer и передать новый класс в зависимость этому */
			$customer = \customer::get();
			$customer->setCurrencyId($currency->getId());
			$customer->commit();

			return $this;
		}

		/** @inheritdoc */
		public function isCurrent(iCurrency $currency) {
			return $this->getCurrent()->getId() === $currency->getId();
		}

		/** @inheritdoc */
		public function getByCode($code) {
			return $this->getCollection()
				->getBy(iCurrency::CODE, $code);
		}

		/** @inheritdoc */
		public function save(iCurrency $currency) {
			$this->getRepository()
				->save($currency);
			return $this;
		}

		/** @inheritdoc */
		public function calculate($price, iCurrency $from = null, iCurrency $to = null) {
			$from = $from ?: $this->getDefault();
			$to = $to ?: $this->getCurrent();

			return $this->getCalculator()
				->calculate($price, $from, $to);
		}

		/**
		 * Возвращает репозиторий валют
		 * @return iRepository
		 */
		private function getRepository() {
			return $this->repository;
		}

		/**
		 * Возвращает коллекцию валют
		 * @return iCollection
		 */
		private function getCollection() {
			$collection = $this->collection;

			if (empty($collection->getAll())) {
				$this->fillCollection($collection);
			}

			return $this->collection;
		}

		/**
		 * Заполняет коллекцию валютами
		 * @param iCollection $collection коллекция валют
		 * @return $this
		 */
		private function fillCollection(iCollection $collection) {
			$currencyList = $this->getRepository()
				->loadAll();
			$collection->loadList($currencyList);
			return $this;
		}

		/**
		 * Возвращает конфигурация системы
		 * @return \iConfiguration
		 */
		private function getConfiguration() {
			return $this->configuration;
		}

		/**
		 * Возвращает калькулятор валют
		 * @return iCalculator
		 */
		private function getCalculator() {
			return $this->calculator;
		}
	}

