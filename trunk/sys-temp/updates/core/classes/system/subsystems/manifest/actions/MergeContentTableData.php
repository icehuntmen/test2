<?php
	/** Команда переноса значений полей объектов заданного иерархического типа из отдельной таблицы в общую */
	class MergeContentTableDataAction extends Action {

		/** @var int $hierarchyTypeId идентификатор иерархического типа */
		protected $hierarchyTypeId;

		/** @inheritdoc */
		public function execute() {		
			$this->hierarchyTypeId = $this->getParam('hierarchy-type-id');
			
			if (!is_numeric($this->hierarchyTypeId)) {
				throw new Exception('Param "hierarchy-type-id" must be numeric');
			}
			
			$this->moveBranchedData();
		}

		/** @inheritdoc */
		public function rollback() {}

		/** Переносит значения полей объектов заданного иерархического типа из отдельной таблицы в общую */
		protected function moveBranchedData() {
			$hierarchyTypeId = $this->hierarchyTypeId;
			$primaryTableName = 'cms3_object_content';
			$secondaryTableName = 'cms3_object_content_' . $hierarchyTypeId;

			$objectTypes = umiObjectTypesCollection::getInstance()
				->getTypesByHierarchyTypeId($hierarchyTypeId);

			$objectTypeIdList = array_keys($objectTypes);

			if (umiCount($objectTypeIdList) == 0) {
				return;
			}
			
			$objectTypesCondition = implode(', ', $objectTypeIdList);
			
			$this->mysql_query('SET FOREIGN_KEY_CHECKS=0');
			
			$sql = <<<SQL
INSERT INTO `{$primaryTableName}` SELECT * FROM `{$secondaryTableName}`
	WHERE `obj_id` IN (SELECT `id` FROM `cms3_objects` WHERE `type_id` IN ({$objectTypesCondition}))
SQL;
			$this->mysql_query($sql);
			
			$this->mysql_query('SET FOREIGN_KEY_CHECKS=1');
		}
	}
