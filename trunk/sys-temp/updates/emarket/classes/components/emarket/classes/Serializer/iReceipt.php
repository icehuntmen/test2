<?php
	namespace UmiCms\Classes\Components\Emarket\Serializer;

	use UmiCms\Classes\Components\Emarket\Currency\iFacade as CurrencyFacade;
	use UmiCms\Classes\Components\Emarket\Tax\Rate\Vat\iFacade as VatFacade;
	use UmiCms\System\Hierarchy\Domain\iDetector;

	/**
	 * Интерфейс сериализатора для чека по ФЗ-54
	 * @package UmiCms\Classes\Components\Emarket\Payment\Serializer
	 */
	interface iReceipt {

		/**
		 * Конструктор
		 * @param CurrencyFacade $currencyFacade фасад валют
		 * @param VatFacade $vatFacade фасад НДС
		 * @param iDetector $domainDetector определитель домена
		 */
		public function __construct(CurrencyFacade $currencyFacade, VatFacade $vatFacade, iDetector $domainDetector);

		/**
		 * Возвращает информацию о составе заказа для печати чека
		 * @param \order $order
		 * @return mixed
		 */
		public function getOrderItemInfoList(\order $order);

		/**
		 * Возвращает контакт покупателя заказа
		 * @param \order заказ
		 * @return mixed
		 */
		public function getContact(\order $customer);
	}
