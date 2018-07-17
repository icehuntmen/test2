<?php
	namespace UmiCms\Classes\Components\Emarket\Tax\Rate\Vat;

	use UmiCms\Classes\Components\Emarket\Tax\Rate\iVat;

	/**
	 * Интерфейс фабрики ставок налога на добавленную стоимость (НДС)
	 * @package UmiCms\Classes\Components\Emarket\Tax\Rate\Vat
	 */
	interface iFactory {

		/**
		 * Создает ставку налога на добавленную стоимость (НДС)
		 * @param \iUmiObject $object объект данных ставки
		 * @return iVat
		 * @throws \wrongParamException
		 */
		public function create(\iUmiObject $object);
	}