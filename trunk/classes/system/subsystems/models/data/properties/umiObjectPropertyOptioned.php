<?php

	/**
	 * Этот класс служит для управления полем объекта.
	 * Обрабатывает тип поля "Составное"
	 */
	class umiObjectPropertyOptioned extends umiObjectProperty {

		/** @inheritdoc */
		protected function loadValue() {
			$values = [];
			$data = $this->getPropData();
			$tableName = $this->getTableName();

			if (!$data) {
				$data = [];
				$connection = $this->getConnection();
				$sql = "SELECT int_val, varchar_val, text_val, rel_val, tree_val, float_val FROM {$tableName} WHERE obj_id = '{$this->object_id}' AND field_id = '{$this->field_id}'";
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ASSOC);

				foreach ($result as $row) {
					foreach ($row as $i => $v) {
						$data[$i][] = $v;
					}
				}
			}

			$i = 0;
			while ($value = $this->parsePropData($data, $i)) {
				foreach ($value as $t => $v) {
					$value[$t] = ($t == 'float') ? $this->filterFloat($v) : $v;
				}

				$values[] = $value;
				$i++;
			}

			return $values;
		}

		/** @inheritdoc */
		public function setValue($value) {
			if (is_array($value)) {
				$value = array_distinct($value);
			}
			parent::setValue($value);
		}

		/** @inheritdoc */
		protected function saveValue() {
			$this->deleteCurrentRows();
			$connection = $this->getConnection();
			$tableName = $this->getTableName();
			$objectId = (int) $this->object_id;
			$fieldId = (int) $this->field_id;
			$insertSqlPart = <<<SQL
INSERT INTO `$tableName` (
	`obj_id`, `field_id`, `int_val`, `varchar_val`, `text_val`, `rel_val`, `tree_val`, `float_val`
	) VALUES (
	$objectId, $fieldId,
SQL;

			foreach ($this->value as $key => $data) {
				$insertSql = $insertSqlPart;
				$cnt = 0;

				$intValue = (int) getArrayKey($data, 'int');
				if ($intValue) {
					$insertSql .= "'{$intValue}', ";
					++$cnt;
				} else {
					$insertSql .= 'NULL, ';
				}

				$varcharValue = (string) getArrayKey($data, 'varchar');
				if ($varcharValue) {
					$varcharValue = self::filterInputString($varcharValue);
					$varcharValue = preg_replace('/([\x00-\x1F])/', '', $varcharValue);
					$insertSql .= "'{$varcharValue}', ";
					++$cnt;
				} else {
					$insertSql .= 'NULL, ';
				}

				$textValue = (string) getArrayKey($data, 'text');
				if ($textValue) {
					$textValue = self::filterInputString($textValue);
					$textValue = preg_replace('/([\x01-\x08]|[\x0B-\x0C]|[\x0E-\x1F])/', '', $textValue);
					$insertSql .= "'{$textValue}', ";
					++$cnt;
				} else {
					$insertSql .= 'NULL, ';
				}

				$relValue = (int) $this->prepareRelationValue(getArrayKey($data, 'rel'));
				if ($relValue) {
					$insertSql .= "'{$relValue}', ";
					++$cnt;
				} else {
					$insertSql .= 'NULL, ';
				}
				$this->values[$key]['rel'] = $relValue;

				$treeValue = (int) getArrayKey($data, 'tree');
				if ($treeValue) {
					$insertSql .= "'{$treeValue}', ";
					++$cnt;
				} else {
					$insertSql .= 'NULL, ';
				}

				$floatValue = (float) getArrayKey($data, 'float');
				if ($floatValue) {
					$insertSql .= "'{$floatValue}'";
					++$cnt;
				} else {
					$insertSql .= 'NULL';
				}

				$insertSql .= ')';

				if ($cnt < 2) {
					continue;
				}

				$connection->query($insertSql);
			}
		}

		protected function parsePropData($data, $index) {
			$result = [];
			$hasValue = false;

			foreach ($data as $contentType => $values) {
				if (isset($values[$index])) {
					$contentType = $this->decodeContentType($contentType);
					$result[$contentType] = $values[$index];
					$hasValue = true;
				}
			}

			return $hasValue ? $result : false;
		}

		protected function decodeContentType($contentType) {
			if (mb_substr($contentType, -4) == '_val') {
				$contentType = mb_substr($contentType, 0, mb_strlen($contentType) - 4);
			}

			return $contentType;
		}

		/** @inheritdoc */
		protected function applyParams($values, $params = null) {
			$filter = getArrayKey($params, 'filter');
			$requireFieldType = getArrayKey($params, 'field-type');

			if ($filter !== null) {
				$result = [];

				foreach ($values as $index => $value) {
					foreach ($filter as $fieldType => $filterValue) {
						if (isset($value[$fieldType]) && $value[$fieldType] == $filterValue) {
							$result[] = $value;
						}
					}
				}
				$values = $result;
			}

			if ($requireFieldType !== null) {
				foreach ($values as $i => $value) {
					$values[$i] = getArrayKey($value, $requireFieldType);
				}
			}

			return $values;
		}

		protected function filterFloat($value) {
			return round($value, 2);
		}

		/** @inheritdoc */
		protected function isNeedToSave(array $newValue) {
			$oldValue = $this->value;

			$oldValue = $this->normaliseValue($oldValue);
			$newValue = $this->normaliseValue($newValue);

			if (umiCount($oldValue) !== umiCount($newValue)) {
				return true;
			}

			foreach ($newValue as $key => $value) {
				if (!isset($oldValue[$key])) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Приводит значение составного поля к определенному формату, для сравнения.
		 * Возвращает результат форматирования.
		 * @param array $values значение составного поля
		 * @return array
		 */
		private function normaliseValue(array $values) {

			if (umiCount($values) == 0) {
				return $values;
			}

			$normalisedValues = [];

			foreach ($values as $value) {
				$normalisedValue = [];

				$normalisedInt = $this->extractIntValue($value);

				if ($normalisedInt !== null) {
					$normalisedValue['int'] = $normalisedInt;
				}

				$normalisedVarchar = $this->extractStringValue($value, 'varchar');

				if ($normalisedVarchar !== null) {
					$normalisedValue['varchar'] = $normalisedVarchar;
				}

				$normalisedText = $this->extractStringValue($value, 'text');

				if ($normalisedText !== null) {
					$normalisedValue['text'] = $normalisedText;
				}

				$normalisedRel = $this->extractRelValue($value);

				if ($normalisedRel !== null) {
					$normalisedValue['rel'] = $normalisedRel;
				}

				$normalisedTree = $this->extractTreeValue($value);

				if ($normalisedTree !== null) {
					$normalisedValue['tree'] = $normalisedTree;
				}

				$normalisedFloat = $this->extractFloatValue($value);

				if ($normalisedFloat !== null) {
					$normalisedValue['float'] = $normalisedFloat;
				}

				$propertyKey = '';

				foreach ($normalisedValue as $key) {
					$propertyKey .= $key;
				}

				$normalisedValues[md5($propertyKey)] = $normalisedValue;
			}

			return $normalisedValues;
		}

		/**
		 * Извлекает значение ссылки на дерево из составного поля
		 * @param array $value значение составного поля
		 * @return int|null
		 */
		private function extractTreeValue(array $value) {

			if (!isset($value['tree'])) {
				return null;
			}

			switch (true) {
				case $value['tree'] instanceof iUmiHierarchyElement === true: {
					return (int) $value['tree']->getId();
				}
				case is_numeric($value['tree']): {
					return (int) $value['tree'];
				}
				default: {
					return null;
				}
			}
		}

		/**
		 * Извлекает строковое значение из составного поля
		 * @param array $value значение составного поля
		 * @param string $valueType тип значения поля (varchar/text)
		 * @return int|null
		 */
		private function extractStringValue(array $value, $valueType) {

			$correctValuesTypes = ['varchar', 'text'];

			if (!in_array($valueType, $correctValuesTypes)) {
				return null;
			}

			if (!isset($value[$valueType])) {
				return null;
			}

			return (string) $value[$valueType];
		}

		/**
		 * Извлекает целочисленное значение из составного поля
		 * @param array $value значение составного поля
		 * @return int|null
		 */
		private function extractIntValue(array $value) {

			if (!isset($value['int'])) {
				return null;
			}

			return (int) $value['int'];
		}

		/**
		 * Извлекает дробное числовое значение из составного поля
		 * @param array $value значение составного поля
		 * @return int|null
		 */
		private function extractFloatValue(array $value) {

			if (!isset($value['float'])) {
				return null;
			}

			return (float) $value['float'];
		}

		/**
		 * Извлекает значение ссылки на объект из составного поля
		 * @param array $value значение составного поля
		 * @return int|null
		 */
		private function extractRelValue(array $value) {
			if (!isset($value['rel'])) {
				return null;
			}

			switch (true) {
				case $value['rel'] instanceof iUmiObject === true: {
					return (int) $value['rel']->getId();
				}
				case is_numeric($value['rel']): {
					return (int) $value['rel'];
				}
				case is_string($value['rel']): {
					return (string) $value['rel'];
				}
				default: {
					return null;
				}
			}
		}
	}
