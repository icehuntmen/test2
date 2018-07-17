<?php
	namespace UmiCms\Classes\Components\Emarket\Tax\Rate;

	/**
	 * Класс ставки налога на добавленную стоимость (НДС).
	 * Ставка налога содержит идентификаторы для внешних сервисов, для того, чтобы связывать
	 * идентификаторы ставок в UMI.CMS с идентификаторами ставок в интегрируемых системах.
	 * @package UmiCms\Classes\Components\Emarket\Tax\Rate
	 */
	class Vat implements iVat {

		/** @var \iUmiObject $dataObject объект данных валюты */
		private $dataObject;

		/** @inheritdoc */
		public function __construct(\iUmiObject $dataObject) {
			$this->dataObject = $dataObject;
		}

		/** @inheritdoc */
		public function getId() {
			return $this->getDataObject()
				->getId();
		}

		/** @inheritdoc */
		public function getName() {
			return $this->getDataObject()
				->getName();
		}

		/** @inheritdoc */
		public function getYandexKassaId() {
			return (int) $this->getDataObject()
				->getValue(self::YANDEX_KASSA_ID_FIELD);
		}

		/** @inheritdoc */
		public function getRoboKassaId() {
			return (string) $this->getDataObject()
				->getValue(self::ROBO_KASSA_ID_FIELD);
		}

		/** @inheritdoc */
		public function getPayAnyWayId() {
			return (int) $this->getDataObject()
				->getValue(self::PAY_ANY_WAY_ID_FIELD);
		}

		/** @inheritdoc */
		public function getPayOnline() {
			return (int) $this->getDataObject()
				->getValue(self::PAY_ONLINE_ID_FIELD);
		}

		/** @internal  */
		public function getDataObject() {
			return $this->dataObject;
		}
	}