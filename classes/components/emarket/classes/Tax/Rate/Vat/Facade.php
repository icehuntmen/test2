<?php
	namespace UmiCms\Classes\Components\Emarket\Tax\Rate\Vat;

	use UmiCms\Classes\Components\Emarket\Tax\Rate\iVat;

	/**
	 * Класс фасада ставок налога на добавленную стоимость (НДС)
	 * @package UmiCms\Classes\Components\Emarket\Tax\Rate\Vat
	 */
	class Facade implements iFacade {

		/** @var iRepository $repository репозиторий ставок */
		private $repository;

		/** @inheritdoc */
		public function __construct(iRepository $repository) {
			$this->repository = $repository;
		}

		/** @inheritdoc */
		public function get($id) {
			$rate = $this->getRepository()
				->load($id);

			if ($rate instanceof iVat) {
				return $rate;
			}

			throw new \privateException(sprintf('Vat tax rate with "id" = "%d" not found', $id));
		}

		/**
		 * Возвращает репозиторий ставок
		 * @return iRepository
		 */
		private function getRepository() {
			return $this->repository;
		}
	}