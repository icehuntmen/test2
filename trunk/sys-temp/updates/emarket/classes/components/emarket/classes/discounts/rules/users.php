<?php

	use UmiCms\Service;

	/**
	 * Класс правила скидки типа "Покупатель".
	 * Подходит для скидок на заказ и на товар.
	 * Содержит 1 настройку:
	 *
	 * 1) Список подходящих пользователей;
	 *
	 * Значение настройки хранится в объекте-источнике данных для правила скидки.
	 */
	class usersDiscountRule extends discountRule implements orderDiscountRule, itemDiscountRule {

		/** @inheritdoc */
		public function validateOrder(order $order) {
			return $this->validate();
		}

		/** @inheritdoc */
		public function validateItem(iUmiHierarchyElement $element) {
			return $this->validate();
		}

		/**
		 * Запускает валидацию и возвращает результат
		 * @return bool
		 */
		public function validate() {
			$orderId = null;
			
			if (Service::Request()->isAdmin()) {
				$requestData = getRequest('data');
				
				if (!is_array($requestData)) {
					return false;
				}
				
				$arrayKeys = array_keys($requestData);
				
				if (isset($arrayKeys[0])) {
					$orderId = $arrayKeys[0];
				} 
			}

			$cmsController = cmsController::getInstance();
			$currentModule = $cmsController->getCurrentModule();    
			$currentMethod = $cmsController->getCurrentMethod();
			$umiObjects = umiObjectsCollection::getInstance();

			if ($currentModule == 'content' && $currentMethod == 'save_editable_region') {
				$orderId = getRequest('param0');
			}
			
			if ($currentModule == 'emarket' && $currentMethod == 'gateway') {
				$orderId = payment::getResponseOrderId();
			}
			
			if ($orderId !== null && is_array($this->users)) {
			
				$order = order::get($orderId);
				if (!$order instanceof order) {
					return false;
				}
				
				$customer = $umiObjects->getObject($order->getCustomerId());
				if (!$customer instanceof iUmiObject) {
					return false;
				}
				
				return in_array($customer->getId(), $this->users);
			}
			
			if (Service::Request()->isSite() && is_array($this->users)) {

				$customer = customer::get();
				$customerId = $customer->id;

				$customerObject = $umiObjects->getObject($customerId);

				if (!$customerObject instanceof iUmiObject) {
					return false;
				}

				$guid = $customerObject->getType()->getGUID();

				if ($guid == 'users-user') {
					return in_array($customer->id, $this->users);
				}

				$ownerId = $customerObject->getOwnerId();

				return in_array($ownerId, $this->users);
			}
			
			return false;
		}
	}
