<?php
	namespace UmiCms\System\Import\UmiDump\Helper\Entity;
	/**
	 * Интерфейс класса управления связями между идентификаторами импортированных/импортируемых системных сущностей
	 * и их идентификаторами из внешних источников.
	 *
	 * Для каждой импортируемой сущности сохраняется ее оригинальный идентификатор, чтобы при повторном импорте
	 * данных из заданного внешнего источника сущности были обновлены, а не созданы вновь.
	 */
	interface iSourceIdBinder {

		/**
		 * Конструктор
		 * @param int $sourceId идентификатор ресурса
		 */
		public function __construct($sourceId);

		/**
		 * Возвращает идентификатор ресурса
		 * @return int
		 */
		public function getSourceId();

		/**
		 * Устанавливает связь между импортируемой сущностью и уже созданной в системе сущностью
		 * @param int $externalId Идентификатор импортируемой сущности
		 * @param int $internalId Идентификатор созданной сущности
		 * @param string $table имя таблицы со связями импорта
		 */
		public function defineRelation($externalId, $internalId, $table);

		/**
		 * Возвращает идентификатор созданной сущности
		 * @param string $externalId Идентификатор импортируемой сущности
		 * @param string $table имя таблицы со связями импорта
		 * @return int|null
		 */
		public function getInternalId($externalId, $table);

		/**
		 * Возвращает идентификатор импортируемой сущности
		 * @param int $internalId Идентификатор созданной сущности
		 * @param string $table имя таблицы со связями импорта
		 * @return string|null
		 */
		public function getExternalId($internalId, $table);

		/**
		 * Определяет связана ли импортированная сущность с другими внешними источниками,
		 * то есть обновлялась или создавалась ли она в рамках работы с другими источниками.
		 * @param int $internalId Идентификатор созданной сущности
		 * @param string $table имя таблицы со связями импорта
		 * @return bool
		 */
		public function isRelatedToAnotherSource($internalId, $table);
	}