<?php
	namespace UmiCms\System\Import\UmiDump\Demolisher;
	use UmiCms\System\Import\UmiDump\iDemolisher;

	/**
	 * Интерфейс класса удаления сущностей системы.
	 * @package UmiCms\System\Import\UmiDump\Demolisher
	 */
	interface iEntities extends iDemolisher {

		/**
		 * Устанавливает идентификатор источника данных.
		 * Используется для связывания внешних и внутренний идентификаторов сущностей.
		 * @param int $id идентификатор источника данных
		 * @return iEntities
		 */
		public function setSourceId($id);
	}
