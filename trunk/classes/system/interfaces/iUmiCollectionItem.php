<?php
	/** Интерфейс сущности коллекции */
	interface iUmiCollectionItem {

		/**
		 * Конструктор
		 * @param array $param параметры сущности
		 * @param iUmiConstantMap $map
		 */
		public function __construct(array $param, iUmiConstantMap $map);

		/**
		 * Возвращает идентификатор сущности
		 * @return int
		 */
		public function getId();

		/**
		 * Устанавливает значение поля/свойства/аттрибута сущности
		 * @param string $name имя поля/свойства/аттрибута
		 * @param string $value значение поля/свойства/аттрибута
		 * @return bool
		 */
		public function setValue($name, $value);

		/**
		 * Возвращает значение поля/свойства/аттрибута сущности
		 * @param string $name имя поля/свойства/аттрибута
		 * @return mixed
		 */
		public function getValue($name);

		/** Применяет изменения сущности */
		public function commit();

		/**
		 * Существует ли у сущности поле/свойство/аттрибут с заданным именем
		 * @param string $name имя поля/свойства/аттрибута
		 * @return bool
		 */
		public function isExistsProp($name);

		/**
		 * Возвращает список имен полей/свойств/аттрибутов сущности
		 * @return array
		 */
		public function getPropsList();

		/**
		 * Была ли сущности изменена
		 * @return bool
		 */
		public function isUpdated();

		/**
		 * Изменяет значение флага "была обновлена" сущности
		 * @param bool $isUpdated значение флага
		 */
		public function setUpdatedStatus($isUpdated);

		/**
		 * Возвращает массив полей/свойств/аттрибутов сущности со значениями
		 * @return ['name' => 'value]
		 */
		public function export();

		/**
		 * Импортирует данные в поля/свойства/аттрибуты сущности
		 * @param array $data данные ['name' => 'value]
		 * @return bool
		 */
		public function import(array $data);

		/**
		 * Перемещает текущую сущность по отношению к заданной
		 * @param \iUmiCollectionItem $baseEntity заданная сущность
		 * @param string $mode режим перемещения
		 * @return $this
		 */
		public function move(\iUmiCollectionItem $baseEntity, $mode);
	}

