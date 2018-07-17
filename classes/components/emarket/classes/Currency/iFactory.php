<?php
	namespace UmiCms\Classes\Components\Emarket\Currency;

	use UmiCms\Classes\Components\Emarket\iCurrency;

	/**
	 * Интерфейс фабрики валют
	 * @package UmiCms\Classes\Components\Emarket\Currency
	 */
	interface iFactory {

		/**
		 * Создает валюту
		 * @param \iUmiObject $object объект данных валюты
		 * @return iCurrency
		 * @throws \wrongParamException
		 */
		public function create(\iUmiObject $object);
	}