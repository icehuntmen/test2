<?php
	namespace UmiCms\System\Import\UmiDump\Entity\Helper\SourceIdBinder;
	/**
	 * Класс фабрики класса, связующего идентификатору импортируемых сущностей
	 * @package UmiCms\System\Import\UmiDump\Entity\Helper\SourceIdBinder
	 */
	class Factory implements iFactory {

		/** @inheritdoc */
		public function create($sourceId) {
			return new \entityImportRelations($sourceId);
		}
	}
