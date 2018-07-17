<?php
	namespace UmiCms\Classes\Components\Emarket\Currency;

	use UmiCms\Classes\Components\Emarket\iCurrency;

	/**
	 * Интерфейс коллекции валют
	 * @package UmiCms\Classes\Components\Emarket\Currency
	 */
	interface iCollection {

		/**
		 * Возвращает валюту с заданным значением поля
		 * @param string $name имя поля
		 * @param mixed $value значение поля
		 * @return iCurrency
		 */
		public function getBy($name, $value);

		/**
		 * Возвращает полный список валют
		 * @return iCurrency[]
		 */
		public function getAll();

		/**
		 * Загружает валюту
		 * @param iCurrency $currency валюта
		 * @return $this
		 */
		public function load(iCurrency $currency);

		/**
		 * Загружает список валют
		 * @param iCurrency[] $currencyList список валют
		 * @return $this
		 */
		public function loadList(array $currencyList);

		/**
		 * Выгружает валюту
		 * @param int $id идентификатор валюты
		 * @return $this
		 */
		public function unload($id);

		/**
		 * Определяет загружаена ли валюта
		 * @param int $id
		 * @return bool
		 */
		public function isLoaded($id);
	}