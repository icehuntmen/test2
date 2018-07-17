<?php
	use UmiCms\System\Import\UmiDump\Helper\Entity\iSourceIdBinder;
	/**
	 * Класс служит связующим звеном между импортируемыми сущностями и сущностями в UMI.CMS.
	 * В отдельную таблицу записываются соответствия между идентификаторами
	 * импортируемых сущностей и уже существующих. (@see iUmiImportRelations)
	 *
	 * Используется в классе xmlEntityImporter.
	 */
	class entityImportRelations implements iSourceIdBinder {

		/** @var int $sourceId идентификатор ресурса */
		private $sourceId;

		/** @inheritdoc */
		public function __construct($sourceId) {
			if (!is_numeric($sourceId)) {
				throw new InvalidArgumentException('Source id is not numeric');
			}

			$this->sourceId = (int) $sourceId;
		}

		/** @inheritdoc */
		public function getSourceId() {
			return $this->sourceId;
		}

		/** @inheritdoc */
		public function defineRelation($externalId, $internalId, $table) {
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$externalId = (int) $externalId;
			$internalId = (int) $internalId;
			$table = $connection->escape($table);
			$sourceId = (int) $this->getSourceId();

			if (!$table) {
				throw new InvalidArgumentException('Empty table');
			}

			$sql = <<<SQL
INSERT INTO `{$table}`
	(`external_id`, `internal_id`, `source_id`) VALUES
	($externalId, $internalId, $sourceId)
SQL;

			$connection->queryResult($sql);
		}

		/** @inheritdoc */
		public function getInternalId($externalId, $table) {
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$externalId = (int) $externalId;
			$table = $connection->escape($table);
			$sourceId = (int) $this->getSourceId();

			if (!$table) {
				throw new InvalidArgumentException('Empty table');
			}

			$sql = <<<SQL
SELECT `internal_id`
FROM `{$table}`
WHERE
	`external_id` = $externalId AND `source_id` = $sourceId
LIMIT 0,1
SQL;

			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$internalId = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$internalId = (int) array_shift($fetchResult);
			}

			return $internalId;
		}

		/** @inheritdoc */
		public function getExternalId($internalId, $table) {
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$internalId = (int) $internalId;
			$table = $connection->escape($table);
			$sourceId = (int) $this->getSourceId();

			if (!$table) {
				throw new InvalidArgumentException('Empty table');
			}

			$sql = <<<SQL
SELECT `external_id`
FROM `{$table}`
WHERE `internal_id` = $internalId AND `source_id` = $sourceId
LIMIT 0,1
SQL;

			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$externalId = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$externalId = (string) array_shift($fetchResult);
			}

			return $externalId;
		}

		/** @inheritdoc */
		public function isRelatedToAnotherSource($internalId, $table) {
			$connection = ConnectionPool::getInstance()
				->getConnection();
			$internalId = (int) $internalId;
			$table = $connection->escape($table);
			$sourceId = (int) $this->getSourceId();

			if (!$table) {
				throw new InvalidArgumentException('Empty table');
			}

			$selectSql = <<<SQL
SELECT `external_id` FROM `{$table}` 
	WHERE `source_id` != $sourceId AND `internal_id` = $internalId LIMIT 0,1
SQL;
			$result = ConnectionPool::getInstance()
				->getConnection()
				->queryResult($selectSql);

			return $result->length() > 0;
		}
	}
