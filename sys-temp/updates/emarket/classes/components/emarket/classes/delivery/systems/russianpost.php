<?php
	/**
	 * Способ доставки типа "Почта России".
	 * Подходит ко всем заказам.
	 * Стоимость доставки рассчитывается за счет интеграции
	 * с ресурсами:
	 *
	 * 1) http://www.russianpost.ru/autotarif/Autotarif.aspx;
	 * 2) http://emspost.ru/api/rest/;
	 */
	class russianpostDelivery extends delivery {
		const DEFAULT_CITY = 'Москва';
		const KILOGRAM = 1000;

		const EMS_DECLARED_VALUE_GUID = 'c5f7a7eb8380a03c76ba26c22eb38118b6838b3b';
		const EMS_MAX_DECLARED_VALUE = 50000;

		const EMS_URL = 'http://emspost.ru/api/rest/?';
		const EMS_MAX_WEIGHT_URL = 'http://emspost.ru/api/rest/?method=ems.get.max.weight';
		const EMS_CITIES_URL = 'http://emspost.ru/api/rest?method=ems.get.locations&type=cities&plain=true';

		/** @inheritdoc */
		public function validate(order $order) {
			return true;
		}

		/** @inheritdoc */
		public function getDeliveryPrice(order $order) {
			$umiObjects = umiObjectsCollection::getInstance();

			try {
				$address = $this->getOrderAddress($order);
				$weight = $this->getOrderWeight($order);
				$viewPost = $umiObjects->getObject($this->getValue('viewpost'));
				$typePost = $umiObjects->getObject($this->getValue('typepost'));

				if ($this->isEmsViewpost($viewPost)) {
					$declaredValue = 0;

					if ($viewPost->getGUID() === self::EMS_DECLARED_VALUE_GUID) {
						$declaredValue = $order->getActualPrice();

						if ($declaredValue > self::EMS_MAX_DECLARED_VALUE) {
							$declaredValue = self::EMS_MAX_DECLARED_VALUE;
						}
					}

					$price = $this->getEmsPrice($address, $weight, $declaredValue);

				} else {
					$viewPostIdentifier = $viewPost->getValue('identifier');
					$typePostIdentifier = $typePost->getValue('identifier');
					$price = $this->getMailPrice($address, $weight, $order, $viewPostIdentifier, $typePostIdentifier);
				}

				return $price;

			} catch (privateException $e) {
				return $e->getMessage();
			}
		}

		/**
		 * Возвращает адрес заказа.
		 * @param order $order заказ
		 * @return object $address адрес
		 * @throws privateException если в заказе не указан адрес
		 */
		protected function getOrderAddress($order) {
			$address = umiObjectsCollection::getInstance()->getObject($order->getValue('delivery_address'));

			if (!$address) {
				throw new privateException(getLabel('error-russianpost-no-address'));
			}

			return $address;
		}

		/**
		 * Возвращает вес заказа.
		 * @param order $order заказ
		 * @return int вес
		 * @throws privateException если в заказе нет товаров
		 * @throws privateException если у товара в заказе не указан вес
		 */
		protected function getOrderWeight($order) {
			$items = $order->getItems();

			if (!$items) {
				throw new privateException(getLabel('error-russianpost-empty-order'));
			}

			return $order->getTotalWeight();
		}

		/**
		 * Относится ли вид отправления к ЕМС-доставке?
		 * @param object $viewpost вид отправления
		 * @return boolean
		 */
		protected function isEmsViewpost($viewpost) {
			$emsNames = [getLabel('object-ems_standart'), getLabel('object-ems_declared_value')];
			return in_array($viewpost->name, $emsNames);
		}

		/**
		 * Рассчитывает стоимость ЕМС-доставки и возвращает ее.
		 * @param object $address адрес
		 * @param int $weight вес
		 * @param int $declaredValue объявленная стоимость
		 * @return string информация о стоимости и сроках доставки
		 */
		protected function getEmsPrice($address, $weight, $declaredValue) {
			$umiObjects = umiObjectsCollection::getInstance();

			$weight = $weight / self::KILOGRAM;
			$fromCity = $umiObjects->getObject($this->getValue('departure_city'));

			if ($fromCity instanceof iUmiObject) {
				$fromCityName = $fromCity->getName();
			} else {
				$fromCityName = self::DEFAULT_CITY;
			}

			$toCityName = $address->city;
			$response = $this->calculateEmsPrice($fromCityName, $toCityName, $weight, $declaredValue);

			$price = $response->price;
			$min = $response->term->min;
			$max = $response->term->max;

			return "{$price} руб. (займет от {$min} до {$max} дней)";
		}

		/**
		 * Возвращает стоимость ЕМС-доставки на сайте http://emspost.ru.
		 * @param string $fromCityName название города отправления
		 * @param string $toCityName название города получения
		 * @param int $weight вес
		 * @param int $declaredValue объявленная стоимость
		 * @return object ЕМС-ответ
		 * @throws privateException если в ответ на запрос получена ошибка
		 */
		protected function calculateEmsPrice($fromCityName, $toCityName, $weight, $declaredValue) {
			$response = $this->requireEmsResponse(self::EMS_MAX_WEIGHT_URL);
			$maxWeight = $response->rsp->max_weight;

			if (($weight <= 0) || ($weight > $maxWeight)) {
				throw new privateException(getLabel('error-russianpost-max-weight', false, $maxWeight));
			}

			$response = $this->requireEmsResponse(self::EMS_CITIES_URL);
			$cities = $response->rsp->locations;
			$fromCityEms = $this->getCityEms(mb_strtoupper($fromCityName), $cities, 'from');
			$toCityEms = $this->getCityEms(mb_strtoupper($toCityName), $cities, 'to');

			$params = [
				'method' => 'ems.calculate',
				'from'   => $fromCityEms,
				'to'     => $toCityEms,
				'weight' => $weight,
				'price' => $declaredValue
			];

			$query = http_build_query($params);
			$response = $this->requireEmsResponse(self::EMS_URL . $query);
			$flag = $response->rsp->stat;

			if ($flag !== 'ok') {
				throw new privateException(getLabel('error-russianpost-no-to-city'));
			}

			return $response->rsp;
		}

		/**
		 * Выполняет запрос к EMS и возвращает ответ
		 * @param string $url Адрес запроса
		 * @return object
		 */
		private function requireEmsResponse($url) {
			$response = json_decode(umiRemoteFileGetter::get($url));
			if (!$response || !$response->rsp) {
				throw new privateException(getLabel('error-russianpost-undefined'));
			}
			return $response;
		}

		/**
		 * Возвращает идентификатор города по его названию из ЕМС-массива городов
		 * @param string $cityName название города
		 * @param array $cities массив с городами
		 * @param string 'from'|'to' $mode что ищем - город отправления или город получения
		 * @return string ЕМС-идентификатор города
		 * @throws privateException если искомого города нет в массиве
		 */
		protected function getCityEms($cityName, $cities, $mode) {
			foreach ($cities as $city) {
				if ($city->name == $cityName) {
					$cityEms = $city->value;
					break;
				}
			}

			if (!isset($cityEms)) {
				$fromError = getLabel('error-russianpost-no-from-city');
				$toError = getLabel('error-russianpost-no-to-city');

				$msg = ($mode === 'from') ? $fromError : $toError;
				throw new privateException($msg);
			}

			return $cityEms;
		}

		/**
		 * Возвращает стоимость почтовой доставки на сайте http://www.russianpost.ru.
		 * @param object $address адрес
		 * @param int $weight вес
		 * @param order $order название города отправления
		 * @param int $viewpostIdentifier ид вида отправления
		 * @param int $typepostIdentifier ид способа пересылки
		 * @return int|string цена доставки или сообщение об ошибке
		 * @throws privateException если сервер вернул неожиданный ответ
		 */
		protected function getMailPrice($address, $weight, $order, $viewpostIdentifier, $typepostIdentifier) {
			$value = $this->object->setpostvalue ? ceil($order->getActualPrice()) : 0;
			$zipcode = $address->getValue('index');

			$params = [
				'viewPost'     => $viewpostIdentifier,
				'typePost'     => $typepostIdentifier,
				'countryCode'  => 643,
				'weight'       => $weight,
				'value1'       => $value,
				'postOfficeId' => $zipcode
			];

			$query = http_build_query($params);
			$url = "http://www.russianpost.ru/autotarif/Autotarif.aspx?{$query}";
			$content = umiRemoteFileGetter::get($url);

			if (preg_match("/<input id=\"key\" name=\"key\" value=\"(\d+)\"\/>/i", $content, $match)) {
				$key = trim($match[1]);
				$headers = ['Content-type' => 'application/x-www-form-urlencoded'];
				$postVars = ['key' => $key];

				umiRemoteFileGetter::get($url, false, $headers, $postVars);
				$content = umiRemoteFileGetter::get($url);
			}

			if (preg_match("/span\s+id=\"TarifValue\">([^<]+)<\/span/i", $content, $match)) {
				$price = (float) str_replace(',', '.', trim($match[1]));

				if ($price > 0) {
					return $price;
				}

				if (preg_match("/span\s+id=\"lblErrStr\">([^<]+)<\/span/i", $content, $match)) {
					return $match[1];
				}
			}

			throw new privateException(getLabel('error-russianpost-undefined'));
		}
	}

