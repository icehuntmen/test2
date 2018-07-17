<?php
	namespace UmiCms\System\Import\UmiDump\Demolisher;
	use UmiCms\System\Import\tSourceIdBinderInjector;
	use UmiCms\System\Import\UmiDump\Demolisher;

	/**
	 * Абстрактный класс удаления сущностей системы.
	 * @package UmiCms\System\Import\UmiDump\Demolisher
	 */
	abstract class Entities extends Demolisher implements iEntities {

		use tSourceIdBinderInjector;

		/** @var int $sourceId идентификатор источника данных */
		private $sourceId;

		/** @inheritdoc */
		public function setSourceId($id) {
			$this->sourceId = (int) $id;
			return $this;
		}

		/**
		 * Возвращает идентификатор источника данных
		 * @return int
		 */
		protected function getSourceId() {
			return $this->sourceId;
		}
	}
