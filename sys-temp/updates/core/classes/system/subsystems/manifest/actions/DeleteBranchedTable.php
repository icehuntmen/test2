<?php
	/** Команда удаления таблицы для хранения значений полей объектов заданного иерархического типа */
	class DeleteBranchedTableAction extends Action {

		/** @inheritdoc */
		public function execute() {
			$hierarchyTypeId = $this->getParam('hierarchy-type-id');
			$secondaryTableName = 'cms3_object_content_' . $hierarchyTypeId;
			
			$sql = <<<SQL
DROP TABLE IF EXISTS `{$secondaryTableName}`
SQL;
			$this->mysql_query($sql);
		}

		/** @inheritdoc */
		public function rollback() {}
	}
