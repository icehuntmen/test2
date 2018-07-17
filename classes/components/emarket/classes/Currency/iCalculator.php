<?php
	namespace UmiCms\Classes\Components\Emarket\Currency;

	use UmiCms\Classes\Components\Emarket\iCurrency;

	/**
	 * Интерфейс калькулятора валют
	 * @package UmiCms\Classes\Components\Emarket\Currency
	 */
	interface iCalculator {

		/**
		 * Пересчитывает цену из одной валюты в другую
		 * @param float $price цена
		 * @param iCurrency $from исходная валюта
		 * @param iCurrency $to целевая валюта
		 * @return float
		 */
		public function calculate($price, iCurrency $from, iCurrency $to);
	}