<?php

	/**
	 * Этот класс служит для управления полем объекта
	 * Обрабатывает тип поля "Ссылка на дерево".
	 */
	class umiObjectPropertySymlink extends umiObjectProperty {

		/** @inheritdoc */
		protected function loadValue() {
			$res = [];
			$fieldId = $this->field_id;
			$umiHierarchy = umiHierarchy::getInstance();
			$data = $this->getPropData();

			if ($data) {
				$umiHierarchy->loadElements($data['tree_val']);

				foreach ($data['tree_val'] as $val) {
					if ($val === null) {
						continue;
					}

					$element = $umiHierarchy->getElement((int) $val);

					if ($element === false || !$element->getIsActive()) {
						continue;
					}

					$res[] = $element;
				}

				return $res;
			}

			$connection = $this->getConnection();
			$tableName = $this->getTableName();
			$sql = "SELECT tree_val FROM {$tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$fieldId}'";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			foreach ($result as $row) {
				$val = array_shift($row);

				if ($val === null) {
					continue;
				}

				$element = $umiHierarchy->getElement((int) $val);

				if ($element === false || !$element->getIsActive()) {
					continue;
				}

				$res[] = $element;
			}

			return $res;
		}

		/** @inheritdoc */
		protected function saveValue() {
			$this->deleteCurrentRows();
			$hierarchy = umiHierarchy::getInstance();
			$connection = $this->getConnection();
			$tableName = $this->getTableName();

			foreach ($this->value as $i => $val) {
				if (is_object($val)) {
					$val = (int) $val->getId();
				} else {
					$val = (int) $val;
				}

				if (!$val) {
					continue;
				}

				$this->value[$i] = $hierarchy->getElement($val);
				$sql = "INSERT INTO {$tableName} (obj_id, field_id, tree_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";
				$connection->query($sql);
			}
		}

		/** @inheritdoc */
		protected function isNeedToSave(array $newValue) {
			$oldValue = $this->value;

			$oldValue = $this->normaliseValue($oldValue);
			$newValue = $this->normaliseValue($newValue);

			if (umiCount($oldValue) !== umiCount($newValue)) {
				return true;
			}

			foreach ($newValue as $newValueTag) {
				if (!in_array($newValueTag, $oldValue)) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Приводит значение поля типа "ссылка на дерево" к определенному формату, для сравнения.
		 * Возвращает результат форматирования.
		 * @param array $values значение поля типа "ссылка на дерево""
		 * @return array
		 */
		private function normaliseValue(array $values) {
			if (umiCount($values) == 0) {
				return $values;
			}

			$normalisedValues = [];

			foreach ($values as $value) {
				switch (true) {
					case $value instanceof iUmiEntinty: {
						$normalisedValues[] = (int) $value->getId();
						break;
					}
					case is_numeric($value): {
						$normalisedValues[] = (int) $value;
						break;
					}
				}
			}

			return $normalisedValues;
		}
	}
