<?php
	namespace UmiCms\Classes\Components\Emarket\Tax\Rate\Vat;

	use UmiCms\Classes\Components\Emarket\Tax\Rate\iVat;
	use UmiCms\Classes\Components\Emarket\Tax\Rate\Vat;

	/**
	 * Класс фабрики ставок НДС
	 * @package UmiCms\Classes\Components\Emarket\Currency
	 */
	class Factory implements iFactory {

		/** @inheritdoc */
		public function create(\iUmiObject $object) {
			$this->validate($object);
			return new Vat($object);
		}

		/**
		 * Валидирует объект данных ставки
		 * @param \iUmiObject $object объект данных ставки
		 * @throws \wrongParamException
		 */
		private function validate(\iUmiObject $object) {
			if ($object->getTypeGUID() !== iVat::TYPE_GUID) {
				$message = sprintf('Data object for tax rate VAT must have type "%s"', iVat::TYPE_GUID);
				throw new \wrongParamException($message);
			}
		}
	}