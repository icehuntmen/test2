<?php
	namespace UmiCms\Classes\Components\Emarket\Tax\Rate\Vat;

	use UmiCms\Classes\Components\Emarket\Tax\Rate\iVat;

	/**
	 * Интерфейс фасада ставок налога на добавленную стоимость (НДС)
	 * @package UmiCms\Classes\Components\Emarket\Tax\Rate\Vat
	 */
	interface iFacade {

		/**
		 * Конструктор
		 * @param iRepository $repository репозиторий ставок
		 */
		public function __construct(iRepository $repository);

		/**
		 * Возвращает ставку налога на добавленную стоимость (НДС)
		 * @param int $id идентификатор ставки
		 * @return iVat
		 */
		public function get($id);
	}