<?php
	namespace UmiCms\Classes\Components\Emarket\Currency;

	use UmiCms\Classes\Components\Emarket\iCurrency;

	/**
	 * Интерфейс фасада валют
	 * @package UmiCms\Classes\Components\Emarket\Currency
	 */
	interface iFacade {

		/**
		 * Конструктор
		 * @param iRepository $repository репозиторий валют
		 * @param iCollection $collection коллекция валют
		 * @param \iConfiguration $configuration конфигурация системы
		 * @param iCalculator $calculator калькулятор валют
		 */
		public function __construct(
			iRepository $repository, iCollection $collection, \iConfiguration $configuration, iCalculator $calculator
		);

		/**
		 * Возвращает список валют
		 * @return iCurrency[]
		 */
		public function getList();

		/**
		 * Возвращает валюту магазина по умолчанию
		 * @return iCurrency
		 */
		public function getDefault();

		/**
		 * Устанавливает валюту магазина по умолчанию
		 * @param iCurrency $currency валюта
		 * @return $this
		 */
		public function setDefault(iCurrency $currency);

		/**
		 * Определяет является ли валюта валютой по умолчанию
		 * @param iCurrency $currency проверяемая валюта
		 * @return bool
		 */
		public function isDefault(iCurrency $currency);

		/**
		 * Возвращает текущую валюту покупателя
		 * @return iCurrency
		 */
		public function getCurrent();

		/**
		 * Устанавливает текущую выбранную валюту
		 * @param iCurrency $currency валюта
		 * @return $this
		 */
		public function setCurrent(iCurrency $currency);

		/**
		 * Определяет является ли валюта валютой выбранной пользователем
		 * @param iCurrency $currency проверяемая валюта
		 * @return bool
		 */
		public function isCurrent(iCurrency $currency);

		/**
		 * Возвращает валюту с заданным кодом (ОКВ)
		 * @param string $code код валюты
		 * @return iCurrency
		 */
		public function getByCode($code);

		/**
		 * Сохраняет изменения валюты
		 * @param iCurrency $currency валюта
		 * @return $this
		 */
		public function save(iCurrency $currency);

		/**
		 * Пересчитывает цену из одной валюты в другую
		 * @param float $price цена
		 * @param iCurrency|null $from исходная валюта (если не передана - возьмет валюту магазина по умолчанию)
		 * @param iCurrency|null $to целевая валюта (если не передана возьмет текущую валюту покупателя)
		 * @return float
		 */
		public function calculate($price, iCurrency $from = null, iCurrency $to = null);
	}

