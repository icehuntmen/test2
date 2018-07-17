<?php

	use UmiCms\Service;

	/**
	 * Класс правила скидки типа "Сумма покупок пользователя".
	 * Подходит для скидок на заказ и на товар.
	 * Содержит 2 настройки:
	 *
	 * 1) Минимальная подходящая сумма заказов пользователя;
	 * 2) Максимальная подходящая сумма заказов пользователя;
	 *
	 * Значения настроек хранятся в объекте-источнике данных для правила скидки.
	 */
	class allOrdersPricesDiscountRule extends discountRule implements orderDiscountRule, itemDiscountRule {

		/** @inheritdoc */
		public function validateOrder(order $order) {
			$orderPricesSum = $this->getPricesSum($order);
			$minimal = $this->getValue('minimal');
			$maximum = $this->getValue('maximum');

			if ($minimal && $orderPricesSum < $minimal) {
				return false;
			}

			if ($maximum && $orderPricesSum > $maximum) {
				return false;
			}

			return true;
		}

		/** @inheritdoc */
		public function validateItem(iUmiHierarchyElement $element) {
			$orderPricesSum = null;
			if ($orderPricesSum == null) {
				$orderPricesSum = $this->getPricesSum();
			}

			$minimal = $this->getValue('minimal');
			$maximum = $this->getValue('maximum');

			if ($minimal && $orderPricesSum < $minimal) {
				return false;
			}

			if ($maximum && $orderPricesSum > $maximum) {
				return false;
			}

			return true;
		}

		/**
		 * Возвращает сумму стоимостей всех заказов покупателя
		 * @param order|null $excludeOrder заказ, которы нужно не учитывать
		 * @return float|int
		 */
		protected function getPricesSum(order $excludeOrder = null) {
			$orders = $this->getCustomerOrders($excludeOrder);

			$price = 0;
			/** @var iUmiObject $orderObject */
			foreach ($orders as $orderObject) {
				$order = order::get($orderObject->getId());
				$price += $order->getActualPrice();
			}

			return $price;
		}

		/**
		 * Возвращает список всех заказов пользователя в статусе "Готов"
		 * @param order|null $excludeOrder заказ, которы нужно не учитывать
		 * @return array
		 * @throws selectorException
		 */
		protected function getCustomerOrders(order $excludeOrder = null) {
			static $customerOrders = null;

			if ($customerOrders !== null) {
				return $customerOrders;
			}

			$domainId = Service::DomainDetector()->detectId();
			$excludeOrderId = null;

			if ($excludeOrder instanceof order) {
				$customer = customer::get(true, $excludeOrder->getCustomerId());
				$excludeOrderId = $excludeOrder->getId();
			} else {
				$customer = customer::get(true);
			}

			$sel = new selector('objects');
			$sel->types('hierarchy-type')->name('emarket', 'order');
			$sel->where('customer_id')->equals($customer->id);

			if ($excludeOrderId !== null) {
				$sel->where('id')->notequals($excludeOrderId);
			}

			$sel->where('domain_id')->equals($domainId);
			$sel->where('status_id')->equals(order::getStatusByCode('ready'));
			$sel->option('load-all-props')->value(true);
			return $customerOrders = $sel->result();
		}
	}
