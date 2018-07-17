<?php
	/** Команда удаления значений полей объектов заданного иерархического типа из таблицы по умолчанию */
	class DeleteContentTableDataAction extends Action {

		/** @var int $hierarchyTypeId идентификатор иерархического типа */
		protected $hierarchyTypeId;

		/** @inheritdoc */
		public function execute() {
			$this->hierarchyTypeId = $this->getParam('hierarchy-type-id');
			$this->deleteBranchedDataFromSource();
		}

		/** @inheritdoc */
		public function rollback() {}

		/** Удаляет значения полей объектов заданного иерархического типа из таблицы по умолчанию */
		protected function deleteBranchedDataFromSource() {
			$hierarchyTypeId = $this->hierarchyTypeId;
			$primaryTableName = 'cms3_object_content';
			
			$objectTypes = umiObjectTypesCollection::getInstance()
				->getTypesByHierarchyTypeId($hierarchyTypeId);

			$objectTypeIdList = array_keys($objectTypes);

			if (umiCount($objectTypeIdList) == 0) {
				return;
			}
			
			$objectTypesCondition = implode(', ', $objectTypeIdList);
			
			$sql = <<<SQL
DELETE FROM `{$primaryTableName}`
	WHERE `obj_id` IN (SELECT `id` FROM `cms3_objects` WHERE `type_id` IN ({$objectTypesCondition}))
SQL;
			$this->mysql_query($sql);
		}
	}
