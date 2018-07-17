<?php

	/** Подписчик на рассылку */
	class umiSubscriber extends umiObject implements iUmiSubscriber {
		protected $o_user;

		/** @inheritdoc */
		public function __construct($id, $row = false) {
			$umiObjects = umiObjectsCollection::getInstance();
			$umiObjectTypes = umiObjectTypesCollection::getInstance();
			$umiHierarchyTypes = umiHierarchyTypesCollection::getInstance();

			$this->store_type = 'subscriber';
			$object = $umiObjects->getObject($id);

			if ($object instanceof iUmiObject) {
				$typeId = $object->getTypeId();
				$type = $umiObjectTypes->getType($typeId);
				$hierarchyType = $umiHierarchyTypes->getType($type->getHierarchyTypeId());

				if ($hierarchyType->getName() === 'dispatches' && $hierarchyType->getExt() === 'subscriber') {
					$uid = $object->getValue('uid');
					$this->o_user = $umiObjects->getObject($uid);
				} elseif ($hierarchyType->getName() === 'users' && $hierarchyType->getExt() === 'user') {
					$this->o_user = $object;
					$id = $this->getSubscriberByUserId($id);
				}
			}

			parent::__construct($id);
		}

		/**
		 * Является ли подписчик зарегистрированным пользователем?
		 * @return bool
		 */
		public function isRegisteredUser() {
			return ($this->o_user instanceof iUmiObject);
		}

		/**
		 * Список идентификаторов рассылок, на которые подписан подписчик
		 * @return int[]
		 */
		public function getDispatches() {
			return $this->getValue('subscriber_dispatches');
		}

		/**
		 * Идентификатор подписчика по идентификатору пользователя.
		 * Если пользователя нет, он будет создан.
		 * @param int $userId идентификатор пользователя
		 * @return int
		 */
		public static function getSubscriberByUserId($userId) {
			$umiObjects = umiObjectsCollection::getInstance();

			$type = selector::get('object-type')->name('dispatches', 'subscriber');
			$typeId = $type->getId();

			$sel = new selector('objects');
			$sel->types('object-type')->id($typeId);
			$sel->where('uid')->equals($userId);
			$sel->limit(0, 1);

			/** @var umiObject|null $subscriber */
			$subscriber = $sel->first();

			if ($subscriber) {
				$subscriberId = $subscriber->getId();
			} else {
				$user = $umiObjects->getObject($userId);

				$email = $user->getValue('e-mail');
				$lastName = $user->getValue('lname');
				$firstName = $user->getValue('fname');
				$middleName = $user->getValue('father_name');
				$gender = $user->getValue('gender');

				$subscriberId = $umiObjects->addObject($email, $typeId);
				$subscriber = $umiObjects->getObject($subscriberId);

				if ($subscriber instanceof iUmiObject) {
					$subscriber->setName($email);
					$subscriber->setValue('lname', $lastName);
					$subscriber->setValue('fname', $firstName);
					$subscriber->setValue('father_name', $middleName);

					$date = new umiDate(time());
					$subscriber->setValue('subscribe_date', $date);
					$subscriber->setValue('gender', $gender);
					$subscriber->setValue('uid', $userId);
				}

				$subscriber->commit();
			}

			return $subscriberId;
		}

		/** @deprecated */
		public function isRegistredUser() {
			return $this->isRegisteredUser();
		}

	}


