<?php
	namespace UmiCms\Classes\Components\Emarket\Tax\Rate\Vat;

	use UmiCms\Classes\Components\Emarket\Tax\Rate\iVat;
	use UmiCms\Classes\Components\Emarket\Tax\Rate\Vat\iFactory as VatFactory;
	use UmiCms\System\Selector\iFactory as SelectorFactory;

	/**
	 * Интерфейс репозитория ставок налога на добавленную стоимость (НДС)
	 * @package UmiCms\Classes\Components\Emarket\Tax\Rate\Vat
	 */
	interface iRepository {

		/**
		 * Конструктор
		 * @param VatFactory $vatFactory фабрика ставок
		 * @param SelectorFactory $selectorFactory фабрика селекторов
		 */
		public function __construct(VatFactory $vatFactory, SelectorFactory $selectorFactory);

		/**
		 * Загружает ставку из репозитория
		 * @param int $id идентификатор ставки
		 * @return iVat|null
		 */
		public function load($id);
	}