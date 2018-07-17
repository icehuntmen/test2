<?php

	use UmiCms\Service;

	/**
	 * Класс правила скидки типа "Группа покупателя".
	 * Подходит для скидок на заказ и на товар.
	 * Содержит 1 настройку:
	 *
	 * 1) Список подходящих групп пользователей;
	 *
	 * Значение настройки хранится в объекте-источнике данных для правила скидки.
	 */
	class userGroupsDiscountRule extends discountRule implements orderDiscountRule, itemDiscountRule {

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
			$umiObjects =  umiObjectsCollection::getInstance();

			if ($currentModule == 'content' && $currentMethod == 'save_editable_region') {
				$orderId = getRequest('param0');
			}

			if ($currentModule == 'emarket' && $currentMethod == 'gateway') {
				$orderId = payment::getResponseOrderId();
			}

			if ($orderId !== null && is_array($this->user_groups)) {

				$order = order::get($orderId);

				if (!$order instanceof order) {
					return false;
				}

				$customerObject = $umiObjects->getObject($order->getCustomerId());

				if (!$customerObject instanceof iUmiObject) {
					return false;
				}

				return $this->validateOnAdminAndGatewayMode($customerObject);
			}

			if (Service::Request()->isSite() && is_array($this->user_groups)) {
				$customer = customer::get();
				$customerId = $customer->id;

				$customerObject = $umiObjects->getObject($customerId);

				if (!$customerObject instanceof iUmiObject) {
					return false;
				}

				return $this->validateOnSiteMode($customerObject, $customer);
			}

			return false;
		}

		/**
		 * Валидирует скидку при вызове в сайтовой части
		 * @param iUmiObject $customerObject
		 * @param customer $customer
		 * @return bool
		 */
		public function validateOnSiteMode(iUmiObject $customerObject, customer $customer) {

			$guid = $customerObject->getType()->getGUID();

			if ($guid == 'users-user' && is_array($customer->groups)) {
				return (bool) umiCount(array_intersect($customer->groups, $this->user_groups));
			}

			$umiObjects = umiObjectsCollection::getInstance();
			$ownerId = $customerObject->getOwnerId();
			$userObject = $umiObjects->getObject($ownerId);

			if (!$userObject instanceof iUmiObject) {
				return false;
			}

			if (is_array($userObject->groups)) {
				return (bool) umiCount(array_intersect($userObject->groups, $this->user_groups));
			}

			return false;
		}

		/**
		 * Валидирует скидку при вызове в административной части, либо
		 * при вызове в контексте обращения платежной системы
		 * @param iUmiObject $customerObject
		 * @return bool
		 */
		public function validateOnAdminAndGatewayMode(iUmiObject $customerObject) {

			$guid = $customerObject->getType()->getGUID();

			if ($guid == 'users-user' && is_array($customerObject->groups)) {
				return (bool) umiCount(array_intersect($customerObject->groups, $this->user_groups));
			}

			$umiObjects = umiObjectsCollection::getInstance();
			$ownerId = $customerObject->getOwnerId();
			$userObject = $umiObjects->getObject($ownerId);

			if (!$userObject instanceof iUmiObject) {
				return false;
			}

			if (is_array($userObject->groups)) {
				return (bool) umiCount(array_intersect($userObject->groups, $this->user_groups));
			}

			return false;
		}
	}
