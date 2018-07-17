<?php
	namespace UmiCms\System\Import\UmiDump\Entity\Helper\SourceIdBinder;
	use UmiCms\System\Import\UmiDump\Helper\Entity\iSourceIdBinder;

	/**
	 * Интерфейс фабрики класса, связующего идентификатору импортируемых сущностей
	 * @package UmiCms\System\Import\UmiDump\Entity\Helper\SourceIdBinder
	 */
	interface iFactory {

		/**
		 * Создает экземпляр класса связывания идентификаторов импортируемых сущностей
		 * @param int $sourceId идентификатор внешнего источник
		 * @return iSourceIdBinder
		 */
		public function create($sourceId);
	}
