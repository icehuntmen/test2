<?php

	use UmiCms\Service;

	/**
	 * Базовый класс способа доставки абстракного типа.
	 * Одновременно является родительским классом всех способов доставки
	 * и предоставляет интерфейс для создания и получения конкретного способа доставки.
	 *
	 * По умолчанию в системе существуют следующие реализации типов способов доставок:
	 *
	 * 1) "Курьером";
	 * 2) "Почта России";
	 * 3) "Самовывоз";
	 * 4) "ApiShip"
	 */
	abstract class delivery extends umiObjectProxy {

		/** @const string STATUS_WAIT_SHIPPING ид статуса доставки "Ожидает отгрузки" */
		const STATUS_WAIT_SHIPPING = 'waiting_shipping';

		/** @const string STATUS_DELIVERED ид статуса доставки "Доставлен" */
		const STATUS_DELIVERED = 'ready';

		/** @const string STATUS_DELIVERING ид статуса доставки "Доставляется" */
		const STATUS_DELIVERING = 'shipping';

		/** @const string STATUS_UNKNOWN ид статуса доставки "Не установлен" */
		const STATUS_UNKNOWN = 'not_defined';

		/** @const string STATUS_CANCELED ид статуса доставки "Отменен" */
		const STATUS_CANCELED = 'canceled';

		/** @const string STATUS_RETURN ид статуса доставки "Возврат" */
		const STATUS_RETURN = 'return';

		/** @const string TAX_RATE_ID_FIELD имя поля с идентификатором ставки ндс */
		const TAX_RATE_ID_FIELD = 'tax_rate_id';

		/**
		 * Создает способ доставки с заданным типом и возвращает его id
		 * @param iUmiObject $deliveryTypeObject объект типа способа доставки
		 * @return delivery|bool
		 * @throws coreException
		 */
		final public static function create(iUmiObject $deliveryTypeObject) {
			$objects = umiObjectsCollection::getInstance();
			$deliveryTypeId = null;
			$deliveryTypeGUID = $deliveryTypeObject->getValue('delivery_type_guid');

			if (mb_strlen($deliveryTypeGUID)) {
				$deliveryTypeId = umiObjectTypesCollection::getInstance()->getTypeIdByGUID($deliveryTypeGUID);
			} else {
				$deliveryTypeId = $deliveryTypeObject->getValue('delivery_type_id');
			}

			$objectId = $objects->addObject('', $deliveryTypeId);
			$object = $objects->getObject($objectId);

			if (!$object instanceof iUmiObject) {
				return false;
			}

			$object->setValue('delivery_type_id', $deliveryTypeObject->getId());
			$object->commit();

			return self::get($objectId);
		}

		/**
		 * Возвращает способ доставки, который использует в качестве
		 * источника данных заданную сущность
		 * @param int|iUmiObject $objectId идентификатор сущности
		 * @return delivery|courierDelivery|russianpostDelivery|selfDelivery
		 * @throws coreException
		 */
		final public static function get($objectId) {
			if ($objectId instanceof iUmiObject) {
				$object = $objectId;
			} else {
				$objects = umiObjectsCollection::getInstance();
				$object = $objects->getObject($objectId);

				if (!$object instanceof iUmiObject) {
					throw new coreException("Couldn't load delivery object #{$objectId}");
				}
			}

			$classPrefix = objectProxyHelper::getClassPrefixByType($object->getValue('delivery_type_id'));
			objectProxyHelper::includeClass('emarket/classes/delivery/systems/', $classPrefix);
			$className = $classPrefix . 'Delivery';
			return new $className($object);
		}

		/**
		 * Возвращает список способов доставки
		 * @param bool $selfDeliveryOnly возвращать только способы доставки типа "Самовывоз"
		 * @return iUmiObject[]
		 * @throws selectorException
		 */
		final public static function getList($selfDeliveryOnly = false) {
			$deliveryWithAddress = (bool) Service::Registry()
				->get('//modules/emarket/delivery-with-address');

			$sel = new selector('objects');
			$sel->types('hierarchy-type')->name('emarket', 'delivery');

			if ($deliveryWithAddress) {
				$types = [];
				$typesSel = new selector('objects');
				$typesSel->types('object-type')->guid('emarket-deliverytype');
				$typesSel->where('class_name')->equals('self');
				$typesSel->option('load-all-props')->value(true);

				/** @var iUmiObject $typeId */
				foreach ($typesSel as $typeId){
					$types[] = $typeId->getId();
				}

				if ($selfDeliveryOnly) {
					$sel->where('delivery_type_id')->equals($types);
				} else {
					$sel->where('delivery_type_id')->notequals($types);
				}
			}

			$sel->where('disabled')->notequals(true);
			$currentDomainId = Service::DomainDetector()->detectId();
			$sel->option('or-mode')->field('domain_id_list');
			$sel->where('domain_id_list')->equals($currentDomainId);
			$sel->where('domain_id_list')->isnull();
			$sel->option('load-all-props', true);
			$sel->option('no-length', true);
			return $sel->result();
		}

		/**
		 * Возвращает идентификатор ставки НДС
		 * @return int|null
		 */
		public function getTaxRateId() {
			return $this->getObject()
				->getValue(self::TAX_RATE_ID_FIELD);
		}

		/**
		 * Устанавливает идентификатор ставки НДС
		 * @param int $id идентификатор ставки НДС
		 * @return $this
		 */
		public function setTaxRateId($id) {
			$this->getObject()
				->setValue(self::TAX_RATE_ID_FIELD, $id);
			return $this;
		}

		/**
		 * Сохраняет в заказа параметры доставки, выбранные пользователем
		 * @param order $order заказ
		 * @return bool
		 */
		public function saveDeliveryOptions(order $order) {
			return true;
		}

		/**
		 * Валидирует заказ на предмет применимости к нему способа доставки
		 * @see EmarketPurchasingStagesSteps::renderDeliveryList()
		 * @param order $order валидируемый заказ
		 * @return bool
		 */
		abstract public function validate(order $order);

		/**
		 * Возвращает стоимость доставки для заданного заказа.
		 * При ошибке возвращает ее текст.
		 * @see EmarketPurchasingStagesSteps::renderDeliveryList()
		 * @param order $order заказ
		 * @return float|string
		 */
		abstract public function getDeliveryPrice(order $order);
	}
