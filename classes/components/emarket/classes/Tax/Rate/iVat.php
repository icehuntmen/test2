<?php
	namespace UmiCms\Classes\Components\Emarket\Tax\Rate;

	/**
	 * Интерфейс ставки налога на добавленную стоимость (НДС).
	 * Ставка налога содержит идентификаторы для внешних сервисов, для того, чтобы связывать
	 * идентификаторы ставок в UMI.CMS с идентификаторами ставок в интегрируемых системах.
	 * @package UmiCms\Classes\Components\Emarket\Tax\Rate
	 */
	interface iVat {

		/** @const string YANDEX_KASSA_ID_FIELD имя поля с идентификатором для Яндекс.Касса */
		const YANDEX_KASSA_ID_FIELD = 'yandex_id';

		/** @const string ROBO_KASSA_ID_FIELD имя поля с идентификатором для Робокасса */
		const ROBO_KASSA_ID_FIELD = 'robokassa_id';

		/** @const string PAY_ANY_WAY_ID_FIELD имя поля с идентификатором для PayAnyWay */
		const PAY_ANY_WAY_ID_FIELD = 'payanyway_id';

		/** @const string PAY_ONLINE_ID_FIELD имя поля с идентификатором для PayOnline */
		const PAY_ONLINE_ID_FIELD = 'payonline_id';

		/** @const string TYPE_GUID гуид типа */
		const TYPE_GUID = 'tax-rate-guide';

		/**
		 * Конструктор
		 * @param \iUmiObject $dataObject объект данных валюты
		 */
		public function __construct(\iUmiObject $dataObject);

		/**
		 * Возвращает идентификатор
		 * @return int
		 */
		public function getId();

		/**
		 * Возвращает название
		 * @return string
		 */
		public function getName();

		/**
		 * Возвращает идентификатор ставки внешнего сервиса "Яндекс.Касса"
		 * @return int
		 */
		public function getYandexKassaId();

		/**
		 * Возвращает идентификатор ставки внешнего сервиса "Робокасса"
		 * @return string
		 */
		public function getRoboKassaId();

		/**
		 * Возвращает идентификатор ставки внешнего сервиса "PayAnyWay"
		 * @return int
		 */
		public function getPayAnyWayId();

		/**
		 * Возвращает идентификатор ставки внешнего сервиса "PayOnline"
		 * @return int
		 */
		public function getPayOnline();
	}