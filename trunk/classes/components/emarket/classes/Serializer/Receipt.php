<?php

	namespace UmiCms\Classes\Components\Emarket\Serializer;

	use UmiCms\Classes\Components\Emarket\Currency\iFacade as CurrencyFacade;
	use UmiCms\Classes\Components\Emarket\Tax\Rate\Vat\iFacade as VatFacade;
	use UmiCms\Classes\Components\Emarket\Tax\Rate\iVat;
	use UmiCms\System\Hierarchy\Domain\iDetector;

	/**
	 * Класс абстрактного сериализатора для чека по ФЗ-54
	 * @package UmiCms\Classes\Components\Emarket\Payment\Serializer
	 */
	abstract class Receipt implements iReceipt {

		/** @var CurrencyFacade $currencyFacade фасад валют */
		private $currencyFacade;

		/** @var VatFacade $vatFacade фасад ставок НДС */
		private $vatFacade;

		/** @var iDetector $domainDetector определитель домена */
		private $domainDetector;

		/** @inheritdoc */
		public function __construct(CurrencyFacade $currencyFacade, VatFacade $vatFacade, iDetector $domainDetector) {
			$this->currencyFacade = $currencyFacade;
			$this->vatFacade = $vatFacade;
			$this->domainDetector = $domainDetector;
		}

		/** @inheritdoc */
		public function getOrderItemInfoList(\order $order) {
			$orderItemInfoList = [];

			try {
				$orderItemInfoList[] = $this->getDeliveryInfo($order);
			} catch (\expectObjectException $e) {
				//nothing
			}

			foreach ($order->getItems() as $orderItem) {
				$orderItemInfoList[] = $this->getOrderItemInfo($order, $orderItem);
			}

			if (empty($orderItemInfoList)) {
				throw new \publicException(getLabel('error-payment-empty-order'));
			}

			return $this->fixItemPriceSummary($order, $orderItemInfoList);
		}

		/** @inheritdoc */
		public function getContact(\order $order) {
			$email = $this->getCustomer($order)
				->getEmail();

			//@todo: отрефакторить класс umiMail и передать его в зависимостях
			if (!\umiMail::checkEmail($email)) {
				throw new \publicException(getLabel('error-payment-wrong-customer-email'));
			}

			return $email;
		}

		/**
		 * Возвращает информацию о доставке
		 * @param \order $order заказ
		 * @return mixed
		 */
		abstract protected function getDeliveryInfo(\order $order);

		/**
		 * Возвращает информацию о товарном наименовании заказа
		 * @param \order $order заказ
		 * @param \orderItem $orderItem товарное наименование
		 * @return mixed
		 */
		abstract protected function getOrderItemInfo(\order $order, \orderItem $orderItem);

		/**
		 * Исправляет стоимости товарных наименований, если они "не бьются" со стоимостью заказа
		 * @param \order $order заказ
		 * @param array $orderItemList информацию о составе заказа для печати чека
		 * @return mixed
		 */
		abstract protected function fixItemPriceSummary(\order $order, array $orderItemList);

		/**
		 * Возвращает стоимость товарного наименования заказа
		 * @param \order $order заказ
		 * @param \orderItem $orderItem товарное наименование
		 * @return float|string
		 */
		protected function getOrderItemPrice(\order $order, \orderItem $orderItem) {
			if (!$order->getDiscountValue()) {
				return $orderItem->getActualPrice();
			}

			$orderItemPrice = $orderItem->getActualPrice() * (100 - $order->getDiscountPercent()) / 100;
			return round($orderItemPrice, -1, PHP_ROUND_HALF_DOWN);
		}

		/**
		 * Подготавливает название позиции заказа
		 * @param string $name название позиции заказа
		 * @return string
		 */
		protected function prepareItemName($name) {
			return trim($name);
		}

		/**
		 * Возвращает доставку заказа
		 * @param \order $order заказ
		 * @return \courierDelivery|\delivery|mixed|\russianpostDelivery|\selfDelivery
		 * @throws \expectObjectException
		 */
		protected function getDelivery(\order $order) {
			$id = $order->getDeliveryId();

			try {
				//@todo: отрефакторить класс delivery и передать его в зависимостях
				$delivery = \delivery::get($id);
			} catch (\coreException $exception) {
				throw new \expectObjectException(getLabel('error-unexpected-exception'));
			}

			return $delivery;
		}

		/**
		 * Возвращает покупателя заказа
		 * @param \order $order заказ
		 * @return \customer
		 * @throws \expectObjectException
		 */
		protected function getCustomer(\order $order) {
			//@todo: отрефакторить класс customer и передать его в зависимостях
			$customer = \customer::get(true, $order->getCustomerId());

			if (!$customer instanceof \customer) {
				throw new \expectObjectException(getLabel('error-unexpected-exception'));
			}

			return $customer;
		}

		/**
		 * Возвращает ставку НДС
		 * @param \orderItem|\delivery $object товарное наименование или доставка
		 * @return iVat
		 * @throws \publicException
		 */
		protected function getVat($object) {
			$rateId = $object->getTaxRateId();

			if (!$rateId) {
				throw new \publicException(getLabel('error-payment-order-item-empty-tax'));
			}

			return $this->getVatFacade()
				->get($rateId);
		}

		/**
		 * Возвращает адрес домена с протоколом
		 * @return string
		 */
		protected function getDomain() {
			return rtrim($this->getDomainDetector()->detectUrl(), '/');
		}

		/**
		 * Возвращает код валюты системы по умолчанию
		 * @return string
		 */
		protected function getCurrencyCode() {
			return $this->getCurrencyFacade()
				->getDefault()
				->getISOCode();
		}

		/**
		 * Возвращает фасад валют
		 * @return CurrencyFacade
		 */
		protected function getCurrencyFacade() {
			return $this->currencyFacade;
		}

		/**
		 * Возвращает фасад ставок НДС
		 * @return VatFacade
		 */
		protected function getVatFacade() {
			return $this->vatFacade;
		}

		/**
		 * Возвращает определитель домена
		 * @return iDetector
		 */
		protected function getDomainDetector() {
			return $this->domainDetector;
		}
	}