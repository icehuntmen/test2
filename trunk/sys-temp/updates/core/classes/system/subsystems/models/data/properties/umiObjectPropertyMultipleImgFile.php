<?php

	use UmiCms\Service;

	/**
	 * Этот класс служит для управления полем объекта.
	 * Обрабатывает тип поля "Набор изображений"
	 */
	class umiObjectPropertyMultipleImgFile extends umiObjectProperty {
		/** @const string TABLE_NAME имя таблицы, в которой хранятся значения поля типа "Набор изображений" */
		const TABLE_NAME = 'cms3_object_images';

		/** @inheritdoc */
		protected function getTableName() {
			return self::TABLE_NAME;
		}

		/** @inheritdoc */
		protected function loadValue() {
			$result = [];
			$fieldId = (int) $this->field_id;
			$objectId = (int) $this->object_id;
			$connection = $this->getConnection();
			$tableName = $this->getTableName();

			$selection = <<<SQL
	SELECT `id`, `src`, `alt`, `ord`
	FROM `$tableName`
	WHERE `obj_id` = $objectId AND `field_id` = $fieldId
	ORDER BY `ord`;
SQL;
			$selectionResult = $connection->queryResult($selection);
			$selectionResult->setFetchType(IQueryResult::FETCH_ASSOC);

			if ($selectionResult->length() == 0) {
				return $result;
			}

			$isAdminMode = Service::Request()->isAdmin();

			foreach ($selectionResult as $row) {
				$src = self::unescapeFilePath($row['src']);
				$image = new umiImageFile($src);
				$image->setAlt($row['alt']);
				$image->setOrder($row['ord']);
				$image->setId($row['id']);

				if ($image->getIsBroken() && !$isAdminMode) {
					continue;
				}

				$result[$image->getFilePath()] = $image;
			}

			return $result;
		}

		/** @inheritdoc */
		protected function saveValue() {
			$this->deleteCurrentRows();

			if (!is_array($this->value)) {
				return;
			}

			$fieldId = (int) $this->field_id;
			$objectId = (int) $this->object_id;
			$connection = $this->getConnection();
			$tableName = $this->getTableName();

			foreach ($this->value as $key => $value) {
				if (!$value instanceof iUmiImageFile || $value->getIsBroken()) {
					continue;
				}

				$src = $connection->escape($value->getFilePath());
				$alt = $connection->escape($value->getAlt());
				$ord = (int) $value->getOrder();

				if ($ord === 0) {
					$ord = $this->getMaxOrder() + 1;
				}

				$insertion = <<<SQL
	INSERT INTO `$tableName` (`obj_id`, `field_id`, `src`, `alt`, `ord`)
	VALUES ($objectId, $fieldId, '$src', '$alt', $ord)
SQL;
				$connection->query($insertion);
			}
		}

		/** @inheritdoc */
		protected function isNeedToSave(array $newValue) {
			$oldValues = $this->value;

			if (umiCount($newValue) !== umiCount($oldValues)) {
				return true;
			}

			$oldFilesPath = [];

			/* @var umiImageFile $oldValue */
			foreach ($oldValues as $oldValue) {
				$oldFilesPath[$oldValue->getFilePath()] = $oldValue;
			}

			foreach ($newValue as $key => $value) {
				if (!$value instanceof iUmiImageFile) {
					continue;
				}

				/* @var umiImageFile $oldValue */
				switch (true) {
					case isset($oldFilesPath[$value->getFilePath()]): {
						$oldValue = $oldFilesPath[$value->getFilePath()];
						break;
					}
					default: {
						return true;
					}
				}

				if ($value->getFilePath() !== $oldValue->getFilePath()) {
					return true;
				}

				if ($value->getAlt() !== $oldValue->getAlt()) {
					return true;
				}

				if ($value->getOrder() !== $oldValue->getOrder()) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Возвращает максимальное значение индекса сортировки
		 * среди изображений для текущего поля объекта
		 * @return int
		 */
		public function getMaxOrder() {
			$objectId = (int) $this->object_id;
			$fieldId = (int) $this->field_id;
			$connection = $this->getConnection();
			$tableName = $this->getTableName();

			$selection = <<<SQL
	SELECT max(`ord`) as ord FROM `$tableName` WHERE `obj_id` = $objectId AND `field_id` = $fieldId;
SQL;
			$result = $connection->queryResult($selection);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);
			$result = $result->getIterator();
			/* @var mysqliQueryResultIterator $result */
			$row = $result->current();

			return (int) $row['ord'];
		}
	}
