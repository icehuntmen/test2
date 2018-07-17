<?php
	namespace UmiCms\Classes\Components\Emarket\Currency;

	use UmiCms\Classes\Components\Emarket\iCurrency;
	use UmiCms\Classes\Components\Emarket\Currency\iFactory as CurrencyFactory;
	use UmiCms\System\Selector\iFactory as SelectorFactory;

	/**
	 * Интерфейс репозитория валют
	 * @package UmiCms\Classes\Components\Emarket\Currency
	 */
	interface iRepository {

		/**
		 * Конструктор
		 * @param CurrencyFactory $currencyFactory фабрика валют
		 * @param SelectorFactory $selectorFactory фабрика селекторов
		 */
		public function __construct(CurrencyFactory $currencyFactory, SelectorFactory $selectorFactory);

		/**
		 * Загружает валюту из репозитория
		 * @param int $id идентификатор валюты
		 * @return iCurrency|null
		 */
		public function load($id);

		/**
		 * Загружает все валюты из репозитория
		 * @return iCurrency[]
		 */
		public function loadAll();

		/**
		 * Сохраняет изменения валюты
		 * @param iCurrency $currency валюта
		 * @return $this
		 */
		public function save(iCurrency $currency);
	}