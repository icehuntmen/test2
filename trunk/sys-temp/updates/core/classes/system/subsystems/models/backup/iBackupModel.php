<?php

	/** Интерфейс для управления резервными копиями страниц */
	interface iBackupModel {

		/**
		 * Возвращает список изменений для страницы или false,
		 * если резервное копирование выключено.
		 * @param int|bool $pageId id страницы
		 * @return array|bool
		 */
		public function getChanges($pageId = false);

		/**
		 * Удаляет изменения модуля "Резервирование"
		 * @param array $changes массив объектов класса backupChange
		 * @return boolean true в случае удаления хотя бы одного изменения
		 * @throws coreException в случае ошибки MySQL
		 */
		public function deleteChanges($changes = []);

		/**
		 * Возвращает список изменений для всех страниц или false,
		 * если резервное копирование выключено.
		 * @return array|bool
		 */
		public function getAllChanges();

		/**
		 * Сохраняет как точку восстановления текущие изменения для страницы
		 * @param int|string $pageId id страницы
		 * @param string $currentModule текущий модуль
		 * @param string $currentMethod не используется
		 * @return bool
		 */
		public function save($pageId = '', $currentModule = '', $currentMethod = '');

		/**
		 * Восстанавливает данные из резервной точки $revision_id
		 * @param int $revisionId id резервное копии
		 * @return bool false, если восстановление невозможно
		 * @throws requreMoreAdminPermissionsException - если недостаточно прав на использования модуля,
		 * к которому относится точка восстановления
		 */
		public function rollback($revisionId);

		/**
		 * Добавляет сообщение в список изменений страницы без занесения самих изменений
		 * @param int $elementId id страницы
		 * @return bool
		 */
		public function addLogMessage($elementId);

		/**
		 * Создает точку восстановления страницы, используя данные страницы и объекта,
		 * сохраненные в БД на текущий момент
		 * @param int $elementId - id страницы которая сохраняется
		 * @return bool
		 */
		public function fakeBackup($elementId);
	}
