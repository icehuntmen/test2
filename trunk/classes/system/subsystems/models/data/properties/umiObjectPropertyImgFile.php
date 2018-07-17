<?php

	use UmiCms\Service;

	/**
	 * Этот класс служит для управления полем объекта.
	 * Обрабатывает тип поля "Изображение"
	 */
	class umiObjectPropertyImgFile extends umiObjectProperty {

		/** @inheritdoc */
		protected function loadValue() {
			$res = [];
			$fieldId = $this->field_id;
			$isAdminMode = Service::Request()->isAdmin();
			$data = $this->getPropData();

			if ($data) {
				foreach ($data['text_val'] as $val) {
					if ($val === null) {
						continue;
					}

					$val = self::unescapeFilePath($val);
					$img = new umiImageFile($val);

					if ($img->getIsBroken() && !$isAdminMode) {
						continue;
					}

					$res[] = $img;
				}
				return $res;
			}

			$connection = $this->getConnection();
			$tableName = $this->getTableName();
			$sql = "SELECT text_val FROM {$tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$fieldId}' LIMIT 1";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			foreach ($result as $row) {
				$val = array_shift($row);

				if ($val === null) {
					continue;
				}

				$val = self::unescapeFilePath($val);
				$img = new umiImageFile($val);

				if ($img->getIsBroken() && !$isAdminMode) {
					continue;
				}

				$res[] = $img;
			}

			return $res;
		}

		/** @inheritdoc */
		protected function saveValue() {
			$this->deleteCurrentRows();

			if ($this->value === null) {
				return;
			}

			$connection = $this->getConnection();

			foreach ($this->value as $val) {
				if (!$val) {
					continue;
				}

				$val = ($val instanceof iUmiFile) ? $val->getFilePath() : (string) $val;

				if (!@is_file($val)) {
					continue;
				}

				$val = $connection->escape($val);
				$tableName = $this->getTableName();
				$sql = "INSERT INTO {$tableName} (obj_id, field_id, text_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";
				$connection->query($sql);
			}
		}

		/** @inheritdoc */
		protected function isNeedToSave(array $newValue) {
			$oldValue = $this->value;
			$newValue = array_filter($newValue, function($value){
				$filePath = ($value instanceof iUmiImageFile) ? $value->getFilePath() : (string) $value;
				return @is_file($filePath);
			});

			switch(true) {
				case empty($oldValue) && empty($newValue) : {
					return false;
				}
				case empty($oldValue) && !empty($newValue) : {
					return true;
				}
				case !empty($oldValue) && empty($newValue) : {
					return true;
				}
				default : {
					$oldValue = array_shift($oldValue);
					$oldValue = ($oldValue instanceof iUmiImageFile) ? $oldValue->getFilePath() : (string) $oldValue;

					$newValue = array_shift($newValue);
					$newValue = ($newValue instanceof iUmiImageFile) ? $newValue->getFilePath() : (string) $newValue;

					return $oldValue !== $newValue;
				}
			}
		}
	}
