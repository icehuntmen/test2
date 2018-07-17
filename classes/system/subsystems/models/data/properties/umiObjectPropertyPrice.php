<?php

	/**
	 * Этот класс служит для управления полем объекта.
	 * Обрабатывает тип поля "Цена". При загрузке данных вызывается событие "umiObjectProperty_loadPriceValue".
	 */
	class umiObjectPropertyPrice extends umiObjectPropertyFloat {
		protected $dbValue;

		/** @inheritdoc */
		protected function loadValue() {
			$res = parent::loadValue();
			$price = 0;

			if (is_array($res) && isset($res[0])) {
				list($price) = $res;
				$price = round($price, 2);
			}

			$this->dbValue = $price;

			$oEventPoint = new umiEventPoint('umiObjectProperty_loadPriceValue');
			$oEventPoint->setParam('object_id', $this->object_id);
			$oEventPoint->addRef('price', $price);
			$oEventPoint->call();

			$res = [$price];
			return $res;
		}

		/** @inheritdoc */
		protected function saveValue() {
			$this->deleteCurrentRows();
			$connection = $this->getConnection();
			$tableName = $this->getTableName();

			foreach ($this->value as $val) {
				if ($val === false || $val === '') {
					continue;
				}

				if (mb_strpos('.', $val) === false) {
					$val = str_replace(',', '.', $val);
				}

				$val = abs((float) $val);

				if ($val > 999999999.99) {
					$val = 999999999.99;
				}

				$val = round($val, 2);

				$sql = "INSERT INTO {$tableName} (obj_id, field_id, float_val) VALUES('{$this->object_id}', '{$this->field_id}', '{$val}')";
				$connection->query($sql);
			}

			$this->dbValue = $this->value;
		}

		public function __wakeup() {
			if ($this->dbValue) {
				$price = $this->dbValue;

				$oEventPoint = new umiEventPoint('umiObjectProperty_loadPriceValue');
				$oEventPoint->setParam('object_id', $this->object_id);
				$oEventPoint->addRef('price', $price);
				$oEventPoint->call();

				$value = [$price];
				$this->value = $value;
			}
		}

		/**
		 * Получить неизмененное значение цены
		 * @return float
		 */
		public function getDbValue() {
			return $this->dbValue;
		}
	}
