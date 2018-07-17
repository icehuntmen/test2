<?php
	namespace UmiCms\System\Data\Object\Property\Value;
	/**
	 * Класс значения поля типа "Ссылка на список доменов"
	 * @package UmiCms\System\Data\Object\Property\Value
	 */
	class DomainIdList extends \umiObjectProperty {

		/** @const string TABLE_NAME имя таблицы-хранилища */
		const TABLE_NAME = 'cms3_object_domain_id_list';

		/** @inheritdoc */
		protected function getTableName() {
			return self::TABLE_NAME;
		}

		/** @inheritdoc */
		protected function loadValue() {
			$objectId = (int) $this->getObjectId();
			$fieldId = (int) $this->getFieldId();
			$tableName = $this->getTableName();

			$query = <<<SQL
SELECT `obj_id`, `field_id`, `domain_id` FROM `$tableName` 
WHERE `obj_id` = $objectId AND `field_id` = $fieldId
SQL;

			$result = $this->getConnection()
				->queryResult($query);
			$result->setFetchType(\IQueryResult::FETCH_ASSOC);

			if ($result->length() == 0) {
				return [];
			}

			$idList = [];

			foreach ($result as $row) {
				$idList[] = (int) $row['domain_id'];
			}

			return $idList;
		}

		/** @inheritdoc */
		protected function saveValue() {
			$domainIdList = (array) $this->value;
			$domainIdList = $this->filterDomainIdList($domainIdList);

			$this->deleteCurrentRows();

			foreach ($domainIdList as $domainId) {
				$this->insertRow($domainId);
			}

			return true;
		}

		/** @inheritdoc */
		protected function isNeedToSave(array $newValue) {
			$newDomainIdList = $newValue;
			$newDomainIdList = $this->filterDomainIdList($newDomainIdList);

			$oldDomainIdList = (array) $this->value;
			$oldDomainIdList = $this->filterDomainIdList($oldDomainIdList);

			if (count($newDomainIdList) !== count($oldDomainIdList)) {
				return true;
			}

			foreach ($newDomainIdList as $newDomainId) {
				if (!in_array($newDomainId, $oldDomainIdList)) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Фильтрует некорректные значение из массива идентификаторов доменов
		 * @param array $domainIdList массив идентификаторов доменов
		 * @return array
		 */
		private function filterDomainIdList(array $domainIdList) {
			return array_filter($domainIdList, function($domainId) {
				return is_numeric($domainId);
			});
		}

		/**
		 * Вставляет новую строку в хранилище
		 * @param int $domainId идентификатор домена
		 */
		private function insertRow($domainId) {
			$tableName = $this->getTableName();
			$objectId = (int) $this->getObjectId();
			$fieldId = (int) $this->getFieldId();
			$domainId = (int) $domainId;
			$query = <<<SQL
INSERT INTO `$tableName` (`obj_id`, `field_id`, `domain_id`) 
VALUES ($objectId, $fieldId, $domainId)
SQL;
			$connection = $this->getConnection();
			$connection->query($query);
		}
	}
