<?php
	namespace UmiCms\Classes\Components\Emarket\Currency;

	use UmiCms\Classes\Components\Emarket\Currency;
	use UmiCms\Classes\Components\Emarket\iCurrency;

	/**
	 * Класс фабрики валют
	 * @package UmiCms\Classes\Components\Emarket\Currency
	 */
	class Factory implements iFactory {

		/** @inheritdoc */
		public function create(\iUmiObject $object) {
			$this->validate($object);
			return new Currency($object);
		}

		/**
		 * Валидирует объект данных валюты
		 * @param \iUmiObject $object объект данных валюты
		 * @throws \wrongParamException
		 */
		private function validate(\iUmiObject $object) {
			if ($object->getTypeGUID() !== iCurrency::TYPE_GUID) {
				$message = sprintf('Data object for currency must have type "%s"', iCurrency::TYPE_GUID);
				throw new \wrongParamException($message);
			}
		}
	}