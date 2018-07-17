<?php

	use UmiCms\Classes\Components\Emarket\Currency;
	use UmiCms\Classes\Components\Emarket\Delivery\Address;
	use UmiCms\Classes\Components\Emarket\iCurrency;
	use UmiCms\Service;

	/** Класс макросов, то есть методов, доступных в шаблоне */
	class EmarketMacros {

		/** @var emarket|EmarketMacros|EmarketPurchasingStages $module */
		public $module;

		/**
		 * Возвращает адрес, перейдя по которому товар будет добавлен в корзину
		 * @param int $elementId идентификатор товара (объекта каталога)
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 */
		public function basketAddLink($elementId, $template = 'default') {
			list($tpl_block) = emarket::loadTemplates(
				'emarket/' . $template,
				'basket_add_link'
			);

			return emarket::parseTemplate($tpl_block, [
				'link' => $this->module->pre_lang . '/emarket/basket/put/element/' . (int) $elementId . '/',
			]);
		}

		/**
		 * Возвращает адрес, перейдя по которому товар будет добавлен в корзину
		 * и в заказе будет выбран способ оплаты
		 * @param int $elementId идентификатор товара (объекта каталога)
		 * @param int|string $paymentIdOrGUID идентификатор или гуид способа оплаты
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 */
		public function basketAddFastLink($elementId, $paymentIdOrGUID, $template = 'default') {
			if ($elementId === null) {
				$elementId = getRequest('param0');
			}

			if ($paymentIdOrGUID === null) {
				$paymentIdOrGUID = (int) getRequest('param1');
			}

			list($tplBlock, $tplError) = emarket::loadTemplates(
				'emarket/' . $template,
				'basket_add_fast_link',
				'basket_add_fast_error'
			);

			$umiObjectsCollection = umiObjectsCollection::getInstance();

			if (is_numeric($paymentIdOrGUID)) {
				$payment = $umiObjectsCollection->getObject($paymentIdOrGUID);
			} else {
				$payment = $umiObjectsCollection->getObjectByGUID($paymentIdOrGUID);
			}

			if (!$payment instanceof iUmiObject || !in_array($payment, payment::getList())) {
				return emarket::parseTemplate($tplError, [
					'error' => getLabel('error-basket_fast_add-no_payment'),
				]);
			}

			/** @var iUmiObject $payment */
			return emarket::parseTemplate($tplBlock, [
				'link' => $this->module->pre_lang . '/emarket/fastPurchase/' . (int) $elementId . '/' . $payment->getId() . '/',
			]);
		}

		/**
		 * Добавляет в товар в заказ и устанавливает заказу способ оплаты
		 * @param null|int $elementId идентификатор товара (объекта каталога)
		 * @param null|int $paymentId идентификатор способа оплаты
		 * @throws breakException
		 */
		public function fastPurchase($elementId = null, $paymentId = null) {
			if ($elementId === null) {
				$elementId = getRequest('param0');
			}
			if ($paymentId === null) {
				$paymentId = (int) getRequest('param1');
			}

			$umiObjectsCollection = umiObjectsCollection::getInstance();
			$payment = $umiObjectsCollection->getObject($paymentId);

			if (!$payment instanceof iUmiObject || !in_array($payment, payment::getList())) {
				throw new breakException(getLabel('error-basket_fast_add-no_payment'));
			}

			$noRedirect = getRequest('no-redirect');
			$redirectUrl = null;

			if (!$noRedirect) {
				if (($redirectUrl = getRequest('redirect-uri')) == null) {
					$redirectUrl = $this->module->pre_lang . '/emarket/cart/';
				}
			}

			$_REQUEST['no-redirect'] = 1;
			$this->basket('put', 'element', $elementId);

			$order = $this->module->getBasketOrder();
			$payment = payment::get($paymentId, $order);

			$order->setPayment($payment);
			$order->commit();

			if (!$noRedirect && $redirectUrl !== null) {
				$this->module->redirect($redirectUrl);
			}
		}

		/**
		 * Возвращает стоимость товара с учетом скидок.
		 * @param null|int $elementId идентификатор товара (объекта каталога)
		 * @param string $template имя шаблона (для tpl)
		 * @param bool $showAllCurrency выводить во всех доступных валютах
		 * @return mixed|null
		 * @throws publicException
		 */
		public function price($elementId = null, $template = 'default', $showAllCurrency = true) {
			if (!$elementId) {
				return null;
			}

			$hierarchy = umiHierarchy::getInstance();
			$elementId = $this->module->analyzeRequiredPath($elementId);
			$element = $hierarchy->getElement($elementId);

			if (!$element instanceof iUmiHierarchyElement) {
				throw new publicException('Wrong element id given');
			}

			/** @var emarket|EmarketMacros $module */
			$module = $this->module;
			list($tpl_block) = emarket::loadTemplates(
				'emarket/' . $template,
				'price_block'
			);

			$result = [
				'attribute:element-id' => $elementId,
			];

			$discount = itemDiscount::search($element);

			if ($discount instanceof discount) {
				$result['discount'] = [
					'attribute:id' => $discount->getId(),
					'attribute:name' => $discount->getName(),
					'description' => $discount->getValue('description'),
				];
				$result['void:discount_id'] = $discount->getId();
			}

			$price = $module->formatPrice($element->getValue('price'), $discount);
			$currencyPrice = $module->formatCurrencyPrice($price);

			if ($currencyPrice) {
				$result['price'] = $currencyPrice;
			} else {
				$result['price'] = $price;
			}

			$result['price'] = $module->parsePriceTpl($template, $result['price']);
			$result['void:price-original'] = getArrayKey($result['price'], 'original');
			$result['void:price-actual'] = getArrayKey($result['price'], 'actual');

			if ($showAllCurrency) {
				$result['currencies'] = $module->formatCurrencyPrices($price);
				$result['currency-prices'] = $module->parseCurrencyPricesTpl($template, $price);
			}

			return emarket::parseTemplate($tpl_block, $result);
		}

		/**
		 * @param string $template
		 * @param array $pricesData
		 * @param iUmiObject $currentCurrency
		 * @return mixed
		 */
		public function parseCurrencyPricesTpl($template = 'default', $pricesData = [], iUmiObject $currentCurrency = null) {
			list($tpl_block, $tpl_item) = emarket::loadTemplates(
				"emarket/currency/{$template}",
				'currency_prices_block',
				'currency_prices_item'
			);

			$currencyFacade = $this->module->getCurrencyFacade();

			if ($currentCurrency instanceof iUmiObject) {
				$currentCurrency = $currencyFacade->getByCode($currentCurrency->getValue('codename'));
			} else {
				$currentCurrency = $currencyFacade->getCurrent();
			}

			$block_arr = [];
			$items_arr = [];
			/** @var emarket|EmarketMacros $module */
			$module =  $this->module;

			foreach ($currencyFacade->getList() as $currency) {

				if ($currentCurrency->getId() === $currency->getId()) {
					continue;
				}

				$info = $module->formatCurrencyPrice(
					$pricesData, $currency, $currentCurrency
				);

				if ($info) {
					if (!$info['original']) {
						$info['original'] = $info['actual'];
					}

					$info['price-original'] = $info['original'];
					$info['price-actual'] = $info['actual'];
					$items_arr[] = emarket::parseTemplate($tpl_item, $info);
				}
			}

			$block_arr['subnodes:items'] = $items_arr;
			return emarket::parseTemplate($tpl_block, $block_arr);
		}

		/**
		 * Изменяет состояние корзины покупателя.
		 *
		 * Действия над корзиной:
		 *
		 * 1) /emarket/basket/put/element/16/ - положить в корзину товар (объект каталога) с id = 16
		 * 2) /emarket/basket/put/element/16/?amount=2 - положить в корзину товар (объект каталога) с id = 16 в количестве = 2
		 * 3) /emarket/basket/put/element/16/?options[name]=10 - положить в корзину товар (объект каталога) с id = 16 с опцией
		 * 4) /emarket/basket/put/element/16/?amount=2&options[name]=10  2) и 3) пункты одновременно
		 * 5) /emarket/basket/remove/element/16/ - убрать из корзины товар (объект каталога) с id = 16
		 * 6) /emarket/basket/remove/item/16/ - убрать из корзины товар (наименование заказа) с id = 16
		 * 7) /emarket/basket/remove_all - убрать из корзины все товары
		 *
		 * Вызывает пересчет корзины.
		 * Либо возвращает заказ, либо осуществляет перенаправление.
		 *
		 * @param string|bool $mode выполняемое действие (put/remove/remove_all)
		 * @param string|bool $itemType тип товара (element/item)
		 * @param int|bool $itemId идентификатор товара
		 * @return mixed
		 * @throws publicException
		 */
		public function basket($mode = false, $itemType = false, $itemId = false) {
			$mode = $mode ?: getRequest('param0');
			$itemType = $itemType ?: getRequest('param1');
			$itemId = (int) ($itemId ?: getRequest('param2'));

			$module = $this->module;
			$order = $module->getBasketOrder(!in_array($mode, ['put', 'remove']));

			switch ($mode) {
				case 'put' : {
					$module->handleBasketPut($itemType, $itemId, $order);
					break;
				}

				case 'remove' : {
					$orderItem = ($itemType == 'element') ? $module->getBasketItem($itemId, false) : orderItem::get($itemId);
					if ($orderItem instanceof orderItem) {
						$order->removeItem($orderItem);
					}
					break;
				}

				case 'remove_all' : {
					foreach ($order->getItems() as $orderItem) {
						$order->removeItem($orderItem);
					}
					break;
				}
			}

			$order->refresh();
			$module->redirectIfRequired($itemType, $itemId);

			return $module->order($order->getId());
		}

		/**
		 * Кладет товар в корзину.
		 * Вспомогательный метод. @see EmarketMacros::basket()
		 * @param string|bool $itemType тип товара (element/item)
		 * @param int|bool $itemId идентификатор товара
		 * @param order $order заказ
		 */
		public function handleBasketPut($itemType, $itemId, $order) {
			$amount = getRequest('amount');
			$this->module->validateAmount($amount);

			$options = getRequest('options');
			$isNewElement = false;

			if ($itemType == 'element') {
				$orderItem = $this->module->getBasketItem($itemId, false);

				if (!$orderItem) {
					$orderItem = $this->module->getBasketItem($itemId);
					$isNewElement = true;
				}
			} else {
				$orderItem = $order->getItem($itemId);
			}

			if (!$orderItem instanceof orderItem) {
				throw new publicException('Order item is not defined');
			}

			if (is_array($options)) {
				if ($itemType != 'element') {
					throw new publicException('Put basket method required element id of optionedOrderItem');
				}

				$orderItem = $this->appendOption($order, $orderItem, $options, $isNewElement, $itemId);
			}

			$oldAmount = $orderItem->getAmount();
			$amount = $amount ?: $oldAmount + 1;
			$orderItem->setAmount($amount ?: 1);
			$orderItem->refresh();
			$newAmount = $orderItem->getAmount();

			if ($itemType == 'element') {
				$order->appendItem($orderItem);
			} elseif ($oldAmount != $newAmount) {
				$order->saveTotalProperties();
			}
		}

		/**
		 * Валидирует количество товара
		 * Вспомогательный метод. @see EmarketMacros::basket()
		 * @param mixed $amount Количество товара
		 */
		public function validateAmount($amount) {
			if ($amount === null) {
				return;
			}

			if (!is_numeric($amount)) {
				throw new publicException("Expected numeric amount, given $amount");
			}

			if ($amount <= 0) {
				throw new publicException("Expected positive amount, given $amount");
			}
		}

		/**
		 * Осуществляет перенаправление,если это необходимо.
		 * Вспомогательный метод. @see EmarketMacros::basket()
		 * @param string|bool $itemType тип товара (element/item)
		 * @param int|bool $itemId идентификатор товара
		 */
		public function redirectIfRequired($itemType, $itemId) {
			$redirectUri = getRequest('redirect-uri');
			if ($redirectUri) {
				$this->module->redirect($redirectUri);
			}

			$referrer = getServer('HTTP_REFERER');
			$noRedirect = getRequest('no-redirect');

			if (defined('VIA_HTTP_SCHEME') || $noRedirect || !$referrer) {
				return;
			}

			$current = $_SERVER['REQUEST_URI'];

			if (mb_substr($referrer, -mb_strlen($current)) == $current) {
				if ($itemType == 'element') {
					$referrer = umiHierarchy::getInstance()->getPathById($itemId);
				} else {
					$referrer = '/';
				}
			}

			$this->module->redirect($referrer);
		}

		/**
		 * Применяет опции к товару в заказ и возвращает его
		 * @param order $order заказ
		 * @param orderItem $orderItem товар в заказе
		 * @param array $options данные опций
		 * @param bool $isNewElement новый ли товар модифицируется
		 * @param int $itemId идентификатор товара
		 * @return null|optionedOrderItem|orderItem
		 * @throws publicException
		 */
		public function appendOption(order $order, orderItem $orderItem, array $options, $isNewElement, $itemId) {
			$orderItems = $order->getItems();
			$currentProduct = $orderItem->getItemElement();

			if (!$currentProduct instanceof iUmiHierarchyElement) {
				throw new publicException('Wrong current item');
			}

			/** @var iUmiHierarchyElement $currentProduct */
			foreach ($orderItems as $tOrderItem) {
				if (!$tOrderItem instanceOf optionedOrderItem) {
					$itemOptions = null;
					$tOrderItem = null;
					continue;
				}

				$itemOptions = $tOrderItem->getOptions();

				if (umiCount($itemOptions) != umiCount($options)) {
					$itemOptions = null;
					$tOrderItem = null;
					continue;
				}

				$itemProduct = $tOrderItem->getItemElement();

				if (!$itemProduct instanceof iUmiHierarchyElement) {
					$itemOptions = null;
					$tOrderItem = null;
					continue;
				}

				/** @var iUmiHierarchyElement $itemProduct */
				if ($itemProduct->getId() != $currentProduct->getId()) {
					$itemOptions = null;
					$tOrderItem = null;
					continue;
				}

				foreach ($options as $optionName => $optionId) {
					$itemOption = getArrayKey($itemOptions, $optionName);

					if (getArrayKey($itemOption, 'option-id') != $optionId) {
						$tOrderItem = null;
						continue 2;
					}
				}

				break;
			}

			if (!isset($tOrderItem) || $tOrderItem === null) {
				$tOrderItem = orderItem::create($itemId);
				$order->appendItem($tOrderItem);

				if ($isNewElement) {
					$orderItem->remove();
				}
			}

			if ($tOrderItem instanceof optionedOrderItem) {
				foreach ($options as $optionName => $optionId) {
					if ($optionId) {
						$tOrderItem->appendOption($optionName, $optionId);
					} else {
						$tOrderItem->removeOption($optionName);
					}
				}
			}

			if ($tOrderItem) {
				$orderItem = $tOrderItem;
			}

			return $orderItem;
		}

		/**
		 * Возвращает содержимое заказа пользователя.
		 * Вызывает пересчет заказа
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 */
		public function cart($template = 'default') {
			if (Service::Auth()->isAuthorized() || customer::getTransientCustomerId()) {
				$order = $this->module->getBasketOrder();
				$order->refresh();
				return $this->module->order($order->getId(), $template);
			}

			list($emptyBlock) = emarket::loadTemplates(
				'emarket/' . $template,
				'order_block_empty'
			);
			$result = [
				'attribute:id' => 'dummy',
				'summary' => ['amount' => 0],
				'steps' => $this->module->getPurchaseSteps($template, null),
			];

			return emarket::parseTemplate($emptyBlock, $result);
		}

		/**
		 * Возвращает данные текущего покупателя
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 */
		public function getCustomerInfo($template = 'default') {
			$order = $this->module->getBasketOrder();
			/** @var emarket|EmarketMacros|EmarketPurchasingStages $module */
			$module = $this->module;
			return $module->renderOrderCustomer($order, $template);
		}

		/**
		 * Возвращает список складов товара
		 * @param int|string|bool $elementId идентификатор или адрес товара (объекта каталога)
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 * @throws publicException
		 */
		public function stores($elementId, $template = 'default') {
			if (!$template) {
				$template = 'default';
			}

			$hierarchy = umiHierarchy::getInstance();
			$objects = umiObjectsCollection::getInstance();

			list($tpl_block, $tpl_block_empty, $tpl_item) = emarket::loadTemplates(
				'emarket/stores/' . $template,
				'stores_block',
				'stores_block_empty',
				'stores_item'
			);

			$elementId = $this->module->analyzeRequiredPath($elementId);

			if (!$elementId) {
				throw new publicException('Wrong element id given');
			}

			$element = $hierarchy->getElement($elementId);

			if (!$element instanceof iUmiHierarchyElement) {
				throw new publicException('Wrong element id given');
			}

			$storesInfo = $element->getValue('stores_state');
			$items_arr = [];
			$stores = [];
			$total = 0;

			if (is_array($storesInfo)) {
				foreach ($storesInfo as $storeInfo) {
					/** @var iUmiObject $object */
					$object = $objects->getObject(getArrayKey($storeInfo, 'rel'));

					if (!$object instanceof iUmiObject) {
						continue;
					}

					$amount = (int) getArrayKey($storeInfo, 'int');
					$total += $amount;

					$store = ['attribute:amount' => $amount];

					if ($object->getValue('primary')) {
						$reserved = (int) $element->getValue('primary');
						$store['attribute:amount'] -= $reserved;
						$store['attribute:reserved'] = $reserved;
						$store['attribute:primary'] = 'primary';
					}

					$store['item'] = $object;
					$stores[] = $store;
					$items_arr[] = emarket::parseTemplate($tpl_item, [
						'store_id' => $object->getId(),
						'amount' => $amount,
						'name' => $object->getName(),
					], false, $object->getId());
				}
			}

			$result = [
				'stores' => [
					'attribute:total-amount' => $total,
					'nodes:store' => $stores,
				],
			];

			$result['void:total-amount'] = $total;
			$result['void:items'] = $items_arr;

			if (!$total) {
				$tpl_block = $tpl_block_empty;
			}

			return emarket::parseTemplate($tpl_block, $result);
		}

		/**
		 * Возвращает данные скидки на товар
		 * @param bool $discountId
		 * @param string $template
		 * @return mixed
		 */
		public function discountInfo($discountId = false, $template = 'default') {
			if (!$template) {
				$template = 'default';
			}
			list($tpl_block, $tpl_block_empty) = def_module::loadTemplates("emarket/discounts/{$template}",
				'discount_block', 'discount_block_empty');

			try {
				/** @var discount $discount */
				$discount = itemDiscount::get($discountId);
			} catch (privateException $e) {
				$discount = null;
			}

			if (!$discount instanceof discount) {
				return emarket::parseTemplate($tpl_block_empty, []);
			}

			$info = [
				'attribute:id' => $discount->getId(),
				'attribute:name' => $discount->getName(),
				'description' => $discount->getValue('description'),
			];

			return emarket::parseTemplate($tpl_block, $info, false, $discount->getId());
		}

		/**
		 * Возвращает список цен, пересчитанных в разные валюты
		 * @param array $prices оригинальная и актуальная цены
		 * @param iUmiObject $defaultCurrency валюта по умолчанию
		 * @return array
		 */
		public function formatCurrencyPrices($prices, iUmiObject $defaultCurrency = null) {
			/** @var emarket|EmarketMacros $module */
			$module = $this->module;
			$result = [];

			foreach ($module->getCurrencyFacade()->getList() as $currency) {
				$info = $module->formatCurrencyPrice($prices, $currency, $defaultCurrency);

				if (is_array($info)) {
					$result[] = $info;
				}
			}

			return [
				'nodes:price' => $result,
			];
		}

		/**
		 * Получает значение скидки и возвращает оригинальную цену и цену со скидкой
		 * @param float $originalPrice оригинальная цена
		 * @param itemDiscount $discount скидка
		 * @return array
		 */
		public function formatPrice($originalPrice, itemDiscount $discount = null) {
			$actualPrice = ($discount instanceof itemDiscount) ? $discount->recalcPrice($originalPrice) : $originalPrice;

			if ($originalPrice == $actualPrice) {
				$originalPrice = null;
			}

			return [
				'original' => $originalPrice,
				'actual' => $actualPrice,
			];
		}

		/**
		 * Возвращает список заказов пользователя, отсортированные по id
		 * @param string $template имя шаблона (для tpl)
		 * @param string $sort режим сортировки (asc/desc)
		 * @return mixed
		 * @throws selectorException
		 */
		public function ordersList($template = 'default', $sort = 'asc') {
			list($tplBlock, $tplBlockEmpty, $tplItem) = emarket::loadTemplates(
				'emarket/' . $template,
				'orders_block',
				'orders_block_empty',
				'orders_item'
			);

			$domainId = Service::DomainDetector()->detectId();

			$select = new selector('objects');
			$select->types('object-type')->name('emarket', 'order');
			$select->where('customer_id')->equals(customer::get()->getId());
			$select->where('name')->isnull(false);
			$select->where('domain_id')->equals($domainId);
			$select->option('no-length')->value(true);
			$select->option('load-all-props')->value(true);

			if ($sort === 'desc') {
				call_user_func([$select->order('id'), $sort]);
			}

			if (!$select->first) {
				$tplBlock = $tplBlockEmpty;
			}

			$itemsArray = [];
			/** @var iUmiObject $order */
			foreach ($select->result() as $order) {
				$item = [
					'attribute:id' => $order->getId(),
					'attribute:name' => $order->getName(),
					'attribute:type-id' => $order->getTypeId(),
					'attribute:guid' => $order->getGUID(),
					'attribute:type-guid' => $order->getTypeGUID(),
					'attribute:ownerId' => $order->getOwnerId(),
					'xlink:href' => $order->xlink,
				];

				$itemsArray[] = emarket::parseTemplate($tplItem, $item, false, $order->getId());
			}

			return emarket::parseTemplate($tplBlock, [
				'subnodes:items' => $itemsArray,
			]);
		}

		/**
		 *  Возвращает ссылку на оформление заказа в соответствии с настройкой модуля магазина - "покупать в 1 шаг"
		 * @return string
		 */
		public function getPurchaseLink() {
			$purchaseMethod = Service::Registry()->get('//modules/emarket/purchasing-one-step') ? 'purchasing_one_step' : 'purchase';
			return $this->module->pre_lang . '/' . cmsController::getInstance()->getUrlPrefix() . 'emarket/' . $purchaseMethod;
		}

		/**
		 * Возвращает список валют магазина
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 * @throws coreException
		 */
		public function currencySelector($template = 'default') {
			list(
				$tpl_block, $tpl_item, $tpl_item_a
				) = emarket::loadTemplates(
				"emarket/currency/{$template}",
				'currency_block',
				'currency_item',
				'currency_item_a'
			);

			$currencyFacade = $this->module->getCurrencyFacade();
			$items_arr = [];

			/** @var Currency $currency */
			foreach ($currencyFacade->getList() as $currency) {
				$item_arr = [
					'attribute:id' => $currency->getId(),
					'attribute:name' => $currency->getName(),
					'attribute:codename' => $currency->getCode(),
					'attribute:rate' => $currency->getRate(),
					'xlink:href' => $currency->getDataObject()
						->getXlink(),
				];

				if ($currencyFacade->isDefault($currency)) {
					$item_arr['attribute:default'] = 'default';
				}

				$tpl = $currencyFacade->isCurrent($currency) ? $tpl_item_a : $tpl_item;
				$items_arr[] = emarket::parseTemplate($tpl, $item_arr, false, $currency->getId());
			}

			$block_arr = [
				'subnodes:items' => $items_arr,
			];

			return emarket::parseTemplate($tpl_block, $block_arr);
		}

		/**
		 * Возвращает список товаров (объектов каталога), добавленных
		 * к сравнению со значениями полей заданных групп
		 * @param string $template имя шаблона (для tpl)
		 * @param string $groups_names строковые идентификатор групп полей,
		 * разделенные пробелом
		 * @return mixed
		 */
		public function compare($template = 'default', $groups_names = '') {
			if (!$template) {
				$template = 'default';
			}

			list(
				$template_block,
				$template_block_empty,
				$template_block_header,
				$template_block_header_item,
				$template_block_line,
				$template_block_line_item
				) = emarket::loadTemplates(
				"emarket/compare/{$template}",
				'compare_block',
				'compare_block_empty',
				'compare_block_header',
				'compare_block_header_item',
				'compare_block_line',
				'compare_block_line_item'
			);

			$elements = $this->getCompareElements();

			if (umiCount($elements) == 0) {
				return emarket::parseTemplate($template_block_empty, []);
			}

			$hierarchy = umiHierarchy::getInstance();
			$hierarchy->loadElements($elements);
			$umiLinksHelper = umiLinksHelper::getInstance();
			$umiLinksHelper->loadLinkPartForPages($elements);

			$block_arr = [];
			$items = [];
			$headers_arr = [];

			foreach ($elements as $element_id) {
				$element = $hierarchy->getElement($element_id);

				if (!$element instanceof iUmiHierarchyElement) {
					continue;
				}

				$item_arr = [
					'attribute:id' => $element_id,
					'attribute:link' => $umiLinksHelper->getLinkByParts($element),
					'node:title' => $element->getName(),
				];

				$items[] = emarket::parseTemplate($template_block_header_item, $item_arr, $element_id);
			}

			$headers_arr['subnodes:items'] = $items;
			$headers = emarket::parseTemplate($template_block_header, $headers_arr);
			$fields = [];

			foreach ($elements as $element_id) {
				$comparableFields = $this->module->getComparableFields($element_id, $groups_names);
				foreach ($comparableFields as $field) {
					$fields[$field->getName()] = $field;
				}
			}

			$lines = [];
			$iCnt = 0;

			/** @var iUmiField $field */
			foreach ($fields as $field_name => $field) {
				$field_title = $field->getTitle();
				$items = [];
				$is_void = true;

				foreach ($elements as $element_id) {
					$element = $hierarchy->getElement($element_id);

					$item_arr = [
						'attribute:id' => $element_id,
						'void:name' => $field_name,
						'void:field_name' => $field_name,
						'value' => $element->getObject()->getPropByName($field_name),
					];

					if ($is_void && $element->getValue($field_name)) {
						$is_void = false;
					}

					$items[] = emarket::parseTemplate($template_block_line_item, $item_arr, $element_id);
				}

				if ($is_void) {
					continue;
				}

				$iCnt++;
				$line_arr = [
					'attribute:title' => $field_title,
					'attribute:name' => $field_name,
					'attribute:type' => $field->getDataType(),
					'attribute:par' => (int) ($iCnt / 2 == ceil($iCnt / 2)),
					'subnodes:values' => $line_arr['void:items'] = $items,
				];

				$lines[] = emarket::parseTemplate($template_block_line, $line_arr);
			}

			$block_arr['headers'] = $headers;
			$block_arr['void:lines'] = $block_arr['void:fields'] = $lines;
			$block_arr['fields'] = [];
			$block_arr['fields']['nodes:field'] = $lines;

			return emarket::parseTemplate($template_block, $block_arr);
		}

		/**
		 * Возвращает список товаров, добавленных к сравнению
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 */
		public function getCompareList($template = 'default') {
			if (!$template) {
				$template = 'default';
			}

			list(
				$template_block, $template_block_empty, $template_block_line, $template_block_link
				) = emarket::loadTemplates(
				"emarket/compare/{$template}",
				'compare_list_block',
				'compare_list_block_empty',
				'compare_list_block_line',
				'compare_list_block_link'
			);

			$block_arr = [];
			$elements = $this->getCompareElements();
			$maxItemsCount = $this->module->iMaxCompareElements;

			if (umiCount($elements) == 0) {
				$block_arr['void:max_elements'] = $maxItemsCount ?: getLabel('label-unlimited');

				if ($maxItemsCount) {
					$block_arr['attribute:max-elements'] = $maxItemsCount;
				}

				return emarket::parseTemplate($template_block_empty, $block_arr);
			}

			$items = [];
			$hierarchy = umiHierarchy::getInstance();
			$hierarchy->loadElements($elements);
			$umiLinksHelper = umiLinksHelper::getInstance();
			$umiLinksHelper->loadLinkPartForPages($elements);

			foreach ($elements as $element_id) {
				$el = $hierarchy->getElement($element_id);

				if (!$el instanceof iUmiHierarchyElement) {
					continue;
				}

				$line_arr = [];
				$line_arr['attribute:id'] = $element_id;
				$line_arr['node:value'] = $el->getName();
				$line_arr['attribute:link'] = $umiLinksHelper->getLinkByParts($el);
				$line_arr['xlink:href'] = 'upage://' . $element_id;
				$items[] = emarket::parseTemplate($template_block_line, $line_arr, $element_id);
			}

			$block_arr['compare_link'] = (umiCount($elements) >= 2) ? $template_block_link : '';
			$block_arr['void:max_elements'] = $maxItemsCount ?: getLabel('label-unlimited');

			if ($maxItemsCount) {
				$block_arr['attribute:max-elements'] = $maxItemsCount;
			}

			$block_arr['subnodes:items'] = $items;
			return emarket::parseTemplate($template_block, $block_arr);
		}

		/**
		 * Возвращает адреса, по которым можно добавить товар к сравнению и
		 * удалить его из сравнения
		 * @param null|int $elementId
		 * @param string $template
		 * @return mixed|void
		 */
		public function getCompareLink($elementId = null, $template = 'default') {
			if (!$elementId) {
				return;
			}

			if (!$template) {
				$template = 'default';
			}

			list($tpl_add_link, $tpl_del_link) = emarket::loadTemplates(
				"emarket/compare/{$template}",
				'add_link',
				'del_link'
			);

			$elements = $this->getCompareElements();
			$inCompare = in_array($elementId, $elements);
			$prefix = $this->module->pre_lang;

			$addLink = $prefix . '/emarket/addToCompare/' . $elementId . '/';
			$delLink = $prefix . '/emarket/removeFromCompare/' . $elementId . '/';
			$block_arr = [
				'add-link' => $inCompare ? null : $addLink,
				'del-link' => $inCompare ? $delLink : null,
			];

			return emarket::parseTemplate(($inCompare ? $tpl_del_link : $tpl_add_link), $block_arr, $elementId);
		}

		/** Добавляет товар с сранению и перенаправляет не реферер */
		public function addToCompare() {
			$this->add_to_compare(getRequest('param0'));
			$this->module->redirect(getServer('HTTP_REFERER'));
		}

		/** Добавляет товар с сравнению и выводит результат в буффер */
		public function jsonAddToCompareList() {
			$element_id = getRequest('param0');
			list($add_to_compare_tpl, $already_exists_tpl) = emarket::loadTemplates(
				'emarket/compare/default',
				'json_add_to_compare',
				'json_compare_already_exists'
			);

			$template = $this->add_to_compare($element_id) ? $add_to_compare_tpl : $already_exists_tpl;
			$block_arr = [
				'id' => $element_id,
			];

			Service::Response()
				->getCurrentBuffer()
				->contentType('text/javascript');
			$this->module->flush(emarket::parseTemplate($template, $block_arr, $element_id));
		}

		/** Убирает товар из сравнения и перенаправляет не реферер */
		public function removeFromCompare() {
			$this->remove_from_compare(getRequest('param0'));
			$referrer = getServer('HTTP_REFERER');

			if (stristr(getServer('HTTP_USER_AGENT'), 'msie')) {
				$referrer = preg_replace(["/\b\d{10,}\b/", '/&{2,}/', '/&$/'], ['', '&', ''], $referrer);
				$referrer .= (strstr($referrer, '?') ? '&' : '?') . time();
				$referrer = str_replace('?&', '?', $referrer);
			}

			$this->module->redirect($referrer);
		}

		/** Убирает товар из сравнения и выводит результат в буффер */
		public function jsonRemoveFromCompare() {
			$element_id = getRequest('param0');
			$this->remove_from_compare($element_id);

			list($template) = emarket::loadTemplates(
				'emarket/compare/default',
				'json_remove_from_compare'
			);

			$block_arr = [
				'id' => $element_id,
			];

			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->contentType('text/javascript');
			$buffer->charset('utf-8');
			/** @noinspection PhpMethodParametersCountMismatchInspection */
			$this->module->flush($template, $block_arr, $element_id);
		}

		/**
		 * Очищает список товаров, добавленных к сравнению,
		 * и перенаправляет на реферер
		 */
		public function resetCompareList() {
			$this->reset_compare();
			$this->module->redirect(getServer('HTTP_REFERER'));
		}

		/**
		 * Очищает список товаров, добавленных к сравнению,
		 * и выводи результат в буффер
		 */
		public function jsonResetCompareList() {
			$this->reset_compare();

			list($template) = emarket::loadTemplates(
				'emarket/compare/default',
				'json_reset_compare_list'
			);

			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->contentType('text/javascript');
			$buffer->charset('utf-8');
			$this->module->flush($template);
		}

		/**
		 * Возвращает данные для вывода личного кабинета покупателя
		 * @param string $template имя шаблона (для tpl)
		 * @param int|bool $customerId ID покупателя владельца личного кабинета
		 * @param string|bool $checkSum Контрольная сумма для верификации покупателя
		 * @return array
		 */
		public function personal($template = 'default', $customerId = false, $checkSum = false) {
			$customer = null;

			if ($customerId !== false && $checkSum !== false) {
				$correctCheckSum = $this->module->getCheckSum($customerId);

				if ($correctCheckSum === $checkSum) {
					$customer = customer::get(false, $customerId);
				}
			}

			if (!$customer) {
				$customer = customer::get();
			}

			$data = [
				'customer' => [
					'@id' => $customer->getId(),
				],
			];

			list($tpl_block) = emarket::loadTemplates(
				'emarket/' . $template,
				'personal'
			);

			return emarket::parseTemplate($tpl_block, $data);
		}

		/**
		 * Устанавливает покупателю предпочитаемую валюту.
		 * У зарегистрированного покупателя она хранится поле 'preffered_currency'
		 * объекта пользователя, у незарегистрированного в cookie 'customer_currency'.
		 * После операции перенаправляет на реферер.
		 * @throws coreException
		 * @throws privateException
		 */
		public function selectCurrency() {
			$currencyFacade = $this->module->getCurrencyFacade();

			try {
				$code = getRequest('currency-codename');
				$currency = $currencyFacade->getByCode($code);
				$currencyFacade->setCurrent($currency);
			} catch (privateException $exception) {
				//nothing
			}

			$redirectUri = getRequest('redirect-uri') ?: getServer('HTTP_REFERER');
			$this->module->redirect($redirectUri);
		}

		/**
		 * Убирает адрес доставки из списка адресов покупателя.
		 * Если адрес не использует ни в одном заказе, то
		 * адрес удаляется.
		 * @param bool|int $addressId идентификатор адреса доставки
		 * @throws coreException
		 * @throws publicException
		 * @throws selectorException
		 */
		public function removeDeliveryAddress($addressId = false) {
			if (!$addressId) {
				$addressId = getRequest('param0');
			}

			$customer = customer::get();
			$addresses = $customer->getValue('delivery_addresses');
			$addressId = (int) $addressId;
			$addressKey = array_search($addressId, $addresses);

			if (!is_bool($addressKey)) {
				unset($addresses[$addressKey]);
				$customer->setValue('delivery_addresses', $addresses);
				$customer->commit();
			}

			$sel = new selector('objects');
			$sel->types('hierarchy-type')->name('emarket', 'order');
			$sel->where('delivery_address')->equals($addressId);
			$sel->option('no-length')->value(true);
			$sel->limit(0, 1);

			if (!$sel->first() instanceof iUmiObject) {
				umiObjectsCollection::getInstance()
					->delObject($addressId);
			}

			$this->module->redirect(getServer('HTTP_REFERER'));
		}

		/**
		 * Возвращает список товаров (объектов каталога), добавленных к сравнению
		 * @return int[]|bool|null
		 */
		public function getCompareElements() {
			static $elements;

			if (is_array($elements)) {
				return $elements;
			}

			$session = Service::Session();
			$compareList = $session->get('compare_list');
			$compareList = is_array($compareList) ? $compareList : [];

			if (is_array(getRequest('compare_list'))) {
				$compareList = getRequest('compare_list');
			}

			$session->set('compare_list', $compareList);

			$elements = $session->get('compare_list');
			return is_array($elements) ? array_unique($elements) : [];
		}

		/**
		 * Добавляет товар (объект каталога) в список сравниваемых товаров
		 * @param int $element_id идентификатор товара
		 * @return bool
		 * @throws coreException
		 * @throws errorPanicException
		 * @throws privateException
		 */
		public function add_to_compare($element_id) {
			$session = Service::Session();
			$compareList = $session->get('compare_list');
			$compareList = is_array($compareList) ? $compareList : [];

			/** @var emarket $module */
			$module = $this->module;

			if ($module->iMaxCompareElements && umiCount($compareList) >= $module->iMaxCompareElements) {
				$module->errorNewMessage('%errors_max_items_compare%');
				$module->errorPanic();
			}

			$oEventPoint = new umiEventPoint('emarket_add_to_compare');
			$oEventPoint->setMode('before');
			$oEventPoint->setParam('element_id', $element_id);
			$oEventPoint->setParam('compare_list', $compareList);
			emarket::setEventPoint($oEventPoint);
			$result = false;

			if (!in_array($element_id, $compareList)) {
				$compareList[] = $element_id;
				$oEventPoint = new umiEventPoint('emarket_add_to_compare');
				$oEventPoint->setMode('after');
				$oEventPoint->setParam('element_id', $element_id);
				$oEventPoint->setParam('compare_list', $compareList);
				emarket::setEventPoint($oEventPoint);
				$result = true;
			}

			$session->set('compare_list', $compareList);
			return $result;
		}

		/**
		 * Удаляет товар (объект каталога) из списка сравниваемых товаров
		 * @param int $element_id идентификатор товара
		 */
		public function remove_from_compare($element_id) {
			$session = Service::Session();
			$compareList = $session->get('compare_list');
			$compareList = is_array($compareList) ? $compareList : [];

			if (in_array($element_id, $compareList)) {
				$key = array_search($element_id, $compareList);
				unset($compareList[$key]);
				$session->set('compare_list', $compareList);
			}
		}

		/** Очищает список сравниваемых товаров */
		public function reset_compare() {
			Service::Session()->set('compare_list', []);
		}

		/**
		 * Обрабатывает запрос от платежной системы
		 * @return mixed
		 * @throws publicException
		 */
		public function gateway() {
			$error = getRequest('err_msg');

			if ($error) {
				$error = $error[0];
				$error = iconv('windows-1251', 'utf-8', urldecode($error));
				cmsController::getInstance()->errorUrl = '/emarket/ordersList/';
				$this->module->errorNewMessage($error);
			}

			$orderId = payment::getResponseOrderId();

			if (!$orderId) {
				throw new publicException("Couldn't receive the order id from the payment system");
			}

			$order = order::get($orderId);

			if ($order instanceof order === false) {
				throw new publicException("Order #{$orderId} doesn't exist");
			}

			$paymentId = $order->getValue('payment_id');

			if (!$paymentId) {
				throw new publicException("No payment method inited for order #{$orderId}");
			}

			/** @var payment $payment */
			$payment = payment::get($paymentId, $order);
			return $payment->poll();
		}

		/**
		 * Возвращает адрес доставки заказа
		 * @param string $template имя шаблона (для tpl)
		 * @param null|int $orderId идентификатор заказа (если не передать - возьмет текущий)
		 * @return mixed
		 * @throws publicAdminException
		 */
		public function getOrderDeliveryAddress($template = 'default', $orderId = null) {
			$order = ($orderId === null) ? $this->module->getBasketOrder() : order::get($orderId);

			if (!$order instanceof order) {
				throw new publicAdminException('Wrong order id given');
			}

			$addressId = $order->getDeliveryAddressId();
			$address = Address\AddressFactory::createByObjectId($addressId);

			$result = [
				'result' => [
					'country' => $address->getCountry(),
					'country_iso_code' => $address->getCountryISOCode(),
					'index' => $address->getPostIndex(),
					'region' => $address->getRegion(),
					'city' => $address->getCity(),
					'street' => $address->getStreet(),
					'house' => $address->getHouseNumber(),
					'flat' => $address->getFlatNumber(),
					'order_comments' => $address->getComment(),
				],
			];

			list($block) = emarket::loadTemplates(
				'emarket/' . $template,
				'delivery_address'
			);

			return emarket::parseTemplate($block, $result, false, $addressId);
		}

		/**
		 * Возвращает данные заказа
		 * @param bool $orderId идентификатор заказа
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 * @throws publicException
		 */
		public function order($orderId = false, $template = 'default') {
			if (!$template) {
				$template = 'default';
			}

			$permissions = permissionsCollection::getInstance();
			$orderId = (int) ($orderId ?: getRequest('param0'));

			if (!$orderId) {
				throw new publicException('You should specify order id');
			}

			$order = order::get($orderId);

			if (!$order instanceof order) {
				throw new publicException("Order #{$orderId} doesn't exist");
			}

			$auth = Service::Auth();

			if (
				!$permissions->isSv() &&
				($order->getName() !== 'dummy') &&
				(customer::get()->getId() != $order->customer_id) &&
				!$permissions->isAllowedMethod($auth->getUserId(), 'emarket', 'control')
			) {
				throw new publicException(getLabel('error-require-more-permissions'));
			}

			list($tpl_block, $tpl_block_empty) = emarket::loadTemplates(
				'emarket/' . $template,
				'order_block',
				'order_block_empty'
			);

			$discount = $order->getDiscount();
			$totalAmount = $order->getTotalAmount();
			$originalPrice = $order->getOriginalPrice();
			$actualPrice = $order->getActualPrice();
			$deliveryPrice = $order->getDeliveryPrice();
			$bonusDiscount = $order->getBonusDiscount();

			if ($originalPrice == $actualPrice) {
				$originalPrice = null;
			}

			/** @var emarket|EmarketMacros $module */
			$module = $this->module;
			$discountAmount = $originalPrice ? $originalPrice + $deliveryPrice - $actualPrice - $bonusDiscount : 0;
			$steps = null;

			if (Service::Request()->isNotAdmin()) {
				/** @var emarket|EmarketMacros|EmarketPurchasingStages $module */
				$steps = $module->getPurchaseSteps($template, null);
			}

			$result = [
				'attribute:id' => $orderId,
				'xlink:href' => 'uobject://' . $orderId,
				'customer' => ($order->getName() == 'dummy') ? null : $module->renderOrderCustomer($order, $template),
				'subnodes:items' => ($order->getName() == 'dummy') ? null : $module->renderOrderItems($order, $template),
				'delivery' => $module->renderOrderDelivery($order, $template),
				'summary' => [
					'amount' => $totalAmount,
					'price' => $module->formatCurrencyPrice([
						'original' => $originalPrice,
						'delivery' => $deliveryPrice,
						'actual' => $actualPrice,
						'discount' => $discountAmount,
						'bonus' => $bonusDiscount,
					]),
				],
				'discount_value' => $order->getDiscountValue(),
				'steps' => $steps,
			];

			if ($order->number) {
				$result['number'] = $order->number;
				$result['status'] = selector::get('object')->id($order->status_id);
			}

			if (!arrayValueContainsNotEmptyArray($result, 'subnodes:items')) {
				$tpl_block = $tpl_block_empty;
			}

			$result['void:total-price'] = $module->parsePriceTpl($template, $result['summary']['price']);
			$result['void:delivery-price'] = $module->parsePriceTpl($template, $module->formatCurrencyPrice(
				['actual' => $deliveryPrice]
			));

			$result['void:bonus'] = $module->parsePriceTpl($template, $module->formatCurrencyPrice(
				['actual' => $bonusDiscount]
			));

			$result['void:total-amount'] = $totalAmount;
			$result['void:discount_id'] = false;

			if ($discount instanceof discount) {
				$result['discount'] = [
					'attribute:id' => $discount->id,
					'attribute:name' => $discount->getName(),
					'description' => $discount->getValue('description'),
				];
				$result['void:discount_id'] = $discount->id;
			}

			return emarket::parseTemplate($tpl_block, $result, false, $order->id);
		}

		/**
		 * Возвращает оформленные цены
		 * @param string $template имя шаблона (для tpl)
		 * @param array $priceData значения цен
		 * @return array
		 */
		public function parsePriceTpl($template = 'default', $priceData = []) {
			if (emarket::isXSLTResultMode()) {
				return $priceData;
			}

			list($tpl_original, $tpl_actual) = emarket::loadTemplates(
				'emarket/' . $template,
				'price_original',
				'price_actual'
			);

			$originalPrice = getArrayKey($priceData, 'original');
			$actualPrice = getArrayKey($priceData, 'actual');

			$result = [];
			$result['original'] = emarket::parseTemplate(($originalPrice ? $tpl_original : ''), $priceData);
			$result['actual'] = emarket::parseTemplate(($actualPrice ? $tpl_actual : ''), $priceData);
			return $result;
		}

		/**
		 * Формирует цену и пересчитывает ее разные валюты
		 * @param int|float $price цены
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 */
		public function applyPriceCurrency($price = 0, $template = 'default') {
			list($tpl_block) = emarket::loadTemplates(
				"emarket/{$template}",
				'price_block'
			);

			/** @var emarket|EmarketMacros $module */
			$module = $this->module;
			$price = $module->parsePriceTpl($template, $module->formatCurrencyPrice([
				'actual' => $price,
			]));

			$result = [
				'price' => $price,
			];
			$result['void:price-original'] = getArrayKey($result['price'], 'original');
			$result['void:price-actual'] = getArrayKey($result['price'], 'actual');

			return emarket::parseTemplate($tpl_block, $result);
		}

		/**
		 * Пересчитывает каждую цену в валюту и возвращает список полученных цен
		 * @param array $prices список цен
		 * @param iUmiObject|iCurrency|null $to валюта, в которую требуется пересчитать цены
		 * @param iUmiObject|iCurrency|null $from валюта по умолчанию
		 * @return array
		 * @throws InvalidArgumentException если переданые некорректные значения для валют
		 * @throws privateException если в качестве валют переданы объекты другого типа, либо у валют не указан код
		 * @throws coreException если объекты валют не переданы, а валюту по умолчанию не определить
		 */
		public function formatCurrencyPrice($prices, $to = null, $from = null) {
			$currencyFacade = $this->module->getCurrencyFacade();

			if ($to instanceof iUmiObject) {
				$to = $currencyFacade->getByCode($to->getValue(iCurrency::CODE));
			} elseif ($to === null) {
				$to = $currencyFacade->getCurrent();
			} elseif (!$to instanceof iCurrency) {
				throw new InvalidArgumentException('Incorrect initial currency given');
			}

			$result = [
				'attribute:name' => $to->getName(),
				'attribute:code' => $to->getCode(),
				'attribute:rate' => $to->getRate(),
				'attribute:nominal' => $to->getDenomination(),
				'void:currency_name' => $to->getName()
			];

			if ($to->getPrefix()) {
				$result['attribute:prefix'] = $to->getPrefix();
			} else {
				$result['void:prefix'] = false;
			}

			if ($to->getSuffix()) {
				$result['attribute:suffix'] = $to->getSuffix();
			} else {
				$result['void:suffix'] = false;
			}

			if ($from instanceof iUmiObject) {
				$from = $currencyFacade->getByCode($from->getValue(iCurrency::CODE));
			} elseif ($from === null) {
				$from = $currencyFacade->getDefault();
			} elseif (!$from instanceof iCurrency) {
				throw new InvalidArgumentException('Incorrect final currency given');
			}

			foreach ($prices as $key => $price) {
				if ($price == null) {
					$result[$key] = null;
					continue;
				}

				$result[$key] = $currencyFacade->calculate($price, $from, $to);
			}

			return $result;
		}

		/**
		 * Возвращает данные о адресе или способе доставки заказа
		 * @param order $order заказ
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 */
		public function renderOrderDelivery(order $order, $template = 'default') {
			$objectsCollection = umiObjectsCollection::getInstance();

			list($tpl, $tplMethod, $tplAddress, $tplPrice) = emarket::loadTemplates(
				'emarket/' . $template,
				'order_delivery',
				'delivery_method',
				'delivery_address',
				'delivery_price'
			);

			$result = [];
			$method = $objectsCollection->getObject($order->delivery_id);

			if (!$method instanceof iUmiObject) {
				return emarket::parseTemplate($tpl, $result);
			}

			$deliveryMethod = [
				'attribute:id' => $method->getId(),
				'attribute:name' => $method->getName(),
				'xlink:href' => 'uobject://' . $method->getId(),
			];

			$result['method'] = emarket::parseTemplate($tplMethod, $deliveryMethod);

			/** @var iUmiObject $address */
			$address = $objectsCollection->getObject($order->getValue('delivery_address'));

			if ($address instanceof iUmiObject) {
				$country = $objectsCollection->getObject($address->getValue('country'));
				$countryName = $country instanceof iUmiObject ? $country->getName() : '';
				$deliveryAddress = [
					'attribute:id' => $address->getId(),
					'attribute:name' => $address->getName(),
					'xlink:href' => 'uobject://' . $address->getId(),
					'country' => $countryName,
					'index' => $address->getValue('index'),
					'region' => $address->getValue('region'),
					'city' => $address->getValue('city'),
					'street' => $address->getValue('street'),
					'house' => $address->getValue('house'),
					'flat' => $address->getValue('flat'),
					'comment' => $address->getValue('order_comments'),
				];
				$result['address'] = emarket::parseTemplate($tplAddress, $deliveryAddress);
			}

			$result['price'] = emarket::parseTemplate($tplPrice, $this->formatCurrencyPrice([
				'delivery' => $order->getValue('delivery_price'),
			]));

			return emarket::parseTemplate($tpl, $result);
		}

		/**
		 * Возвращает данные покупателя
		 * @param order $order заказ покупателя
		 * @param string $template имя шаблона (для tpl)
		 * @return mixed
		 * @throws publicException
		 */
		public function renderOrderCustomer(order $order, $template = 'default') {
			$customer = selector::get('object')->id($order->customer_id);

			if (!$customer instanceof iUmiObject) {
				throw new publicException(getLabel('error-object-does-not-exist', null, $order->customer_id));
			}

			/** @var iUmiObject $customer */
			list($tpl_user, $tpl_guest) = emarket::loadTemplates(
				'emarket/customer/' . $template,
				'customer_user',
				'customer_guest'
			);

			/** @var iUmiObjectType $objectType */
			$objectType = selector::get('object-type')->id($customer->getTypeId());
			$tpl = ($objectType->getModule() == 'users') ? $tpl_user : $tpl_guest;

			return emarket::parseTemplate($tpl, [
				'full:object' => $customer,
			], false, $customer->getId());
		}

		/**
		 * Возвращает данные списка товаров заказа
		 * @param order $order заказ
		 * @param string $template имя шаблона (для tpl)
		 * @return array
		 */
		public function renderOrderItems(order $order, $template = 'default') {
			$items_arr = [];
			$objects = umiObjectsCollection::getInstance();

			list($tpl_item, $tpl_options_block, $tpl_options_block_empty, $tpl_options_item) = emarket::loadTemplates(
				'emarket/' . $template,
				'order_item',
				'options_block',
				'options_block_empty',
				'options_item'
			);

			$orderItems = $order->getItems();

			if (umiCount($orderItems) == 0) {
				return emarket::parseTemplate($tpl_options_block_empty, []);
			}

			/** @var emarket|EmarketMacros $module */
			$module = $this->module;
			$isBasket = emarket::isBasket($order);
			/** @var orderItem $orderItem */
			foreach ($orderItems as $orderItem) {
				$orderItemId = $orderItem->getId();

				$item_arr = [
					'attribute:id' => $orderItemId,
					'attribute:name' => htmlspecialchars($orderItem->getName()),
					'xlink:href' => 'uobject://' . $orderItemId,
					'amount' => $orderItem->getAmount(),
					'options' => null,
				];

				$plainPriceOriginal = $orderItem->getOriginalPrice();

				if ($isBasket) {
					$itemDiscount = $orderItem->getDiscount();
					$plainPriceActual = ($itemDiscount instanceof itemDiscount) ? $itemDiscount->recalcPrice($plainPriceOriginal) : $plainPriceOriginal;
					$pricesDiff = ($plainPriceOriginal - $plainPriceActual);
					$discountValue = ($pricesDiff < 0) ? 0 : $pricesDiff;
				} else {
					$discountValue = $orderItem->getDiscountValue();
					$plainPriceActual = $orderItem->getActualPrice();
				}

				$totalPriceOriginal = $orderItem->getTotalOriginalPrice();
				$totalPriceActual = $orderItem->getTotalActualPrice();

				if ($plainPriceOriginal == $plainPriceActual) {
					$plainPriceOriginal = null;
				}

				if ($totalPriceOriginal == $totalPriceActual) {
					$totalPriceOriginal = null;
				}

				$item_arr['price'] = $module->formatCurrencyPrice([
					'original' => $plainPriceOriginal,
					'actual' => $plainPriceActual,
				]);

				$item_arr['total-price'] = $module->formatCurrencyPrice([
					'original' => $totalPriceOriginal,
					'actual' => $totalPriceActual,
				]);

				$item_arr['price'] = $module->parsePriceTpl($template, $item_arr['price']);
				$item_arr['total-price'] = $module->parsePriceTpl($template, $item_arr['total-price']);
				$item_arr['discount_value'] = (float) $discountValue;
				$item_arr['weight'] = (int) $orderItem->getWeight();

				$status = order::getCodeByStatus($order->getOrderStatus());

				if (!$status || $status == 'basket') {
					$element = $orderItem->getItemElement();
				} else {
					$symlink = $orderItem->getObject()->getValue('item_link');

					if (is_array($symlink) && umiCount($symlink)) {
						list($item) = $symlink;
						$element = $item;
					} else {
						$element = null;
					}
				}

				/** @var iUmiHierarchyElement $element */
				if ($element instanceof iUmiHierarchyElement) {
					$item_arr['page'] = $element;
					$item_arr['void:element_id'] = $element->getId();
					$item_arr['void:link'] = $element->link;
				}

				$discountAmount = $totalPriceOriginal ? $totalPriceOriginal - $totalPriceActual : 0;
				$discount = $orderItem->getDiscount();

				if ($discount instanceof itemDiscount) {
					$item_arr['discount'] = [
						'attribute:id' => $discount->getId(),
						'attribute:name' => $discount->getName(),
						'description' => $discount->getValue('description'),
						'amount' => $discountAmount,
					];
					$item_arr['void:discount_id'] = $discount->getId();
				}

				$elementId = ($element instanceof iUmiHierarchyElement) ? $element->getId() : null;

				if ($orderItem instanceof optionedOrderItem) {
					/** @var optionedOrderItem $orderItem */
					$options = $orderItem->getOptions();
					$options_arr = [];

					foreach ($options as $optionInfo) {
						$optionId = $optionInfo['option-id'];
						$price = $optionInfo['price'];
						$fieldName = $optionInfo['field-name'];
						$option = $objects->getObject($optionId);
						if ($option instanceof iUmiObject) {
							$option_arr = [
								'attribute:id' => $optionId,
								'attribute:name' => $option->getName(),
								'attribute:price' => $price,
								'attribute:field-name' => $fieldName,
								'attribute:element_id' => $elementId,
								'xlink:href' => 'uobject://' . $optionId,
							];

							$options_arr[] = emarket::parseTemplate($tpl_options_item, $option_arr, false, $optionId);
						}
					}

					$item_arr['options'] = emarket::parseTemplate($tpl_options_block, [
						'nodes:option' => $options_arr,
						'void:items' => $options_arr,
					]);
				}

				$items_arr[] = emarket::parseTemplate($tpl_item, $item_arr);
			}

			return $items_arr;
		}
	}
