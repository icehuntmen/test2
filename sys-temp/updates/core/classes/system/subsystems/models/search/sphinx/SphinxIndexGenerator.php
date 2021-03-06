<?php
	/**
	 * Класс для создания VIEW "плоских" таблиц для UMI.CMS 2.x
	 * Генерирует запрос на создание VIEW на основе указанных объектных типов и полей.
	 */
	class SphinxIndexGenerator {
		/** @var string $createViewTemplate шаблон для создания view */
		public $createViewTemplate = '
	CREATE OR REPLACE VIEW `{indexName}` AS
	SELECT `h`.id, `h`.type_id, `h`.domain_id, `h`.rel, `h`.obj_id, `o`.name,
	{fields}
	FROM `cms3_hierarchy` as `h`
		LEFT JOIN `cms3_objects` as `o` ON `o`.id = `h`.obj_id
	{sources}
	WHERE
		`h`.is_active = 1 AND
		`h`.is_deleted = 0 AND
		{searchCond}
		`o`.type_id IN ({typesCond})

	';

		/** @var string $searchCondTemplate шаблон части запроса для исключения неиндексируемых страниц */
		public $searchCondTemplate = '
		({fieldAlias} IS NULL OR {fieldAlias} = 0) AND';

		/** @var string $selectFieldTemplate шаблон части запроса для выбора поля */
		public $selectFieldTemplate = '
		COALESCE({columns}) as `{alias}`';

		/** @var string $joinFieldSourceTemplate шаблон join запроса на подключение поля объекта */
		public $joinFieldSourceTemplate = '
		LEFT JOIN `{contentTable}` as `{fieldSourceUid}` ON `{fieldSourceUid}`.obj_id = `h`.obj_id AND `{fieldSourceUid}`.field_id = {fieldId}';

		/** @var string $createSphinxConfig шаблон конфига для Sphinx */
		public $createSphinxConfig = "
	source content
	{
		type			= mysql

		sql_host		= {mySqlHost}
		sql_user		= {mySqlUser}
		sql_pass		= {mySqlPass}
		sql_db			= {mySqlDB}
		sql_port		= {mySqlPort}

		sql_query_pre	= SET NAMES utf8

		sql_query		= \
			SELECT id, obj_id, domain_id, name, {sqlQuery} \
			FROM sphinx_content_index

			sql_attr_uint	= obj_id
			sql_attr_uint	= domain_id

		sql_query_info		= SELECT * FROM sphinx_content_index WHERE id=\$id
	}

	index content
	{
		source         = content

		path           = {pathToIndex}sphinx_content_index

		morphology     = stem_enru

		min_word_len   = 2

		charset_type   = utf-8
		charset_table  = 0..9, A..Z->a..z, _, a..z, U+410..U+42F->U+430..U+44F, U+430..U+44F, U+401->U+451, U+451
		blend_chars    = &, ., +, U+23
		min_infix_len  = 2
		enable_star    = 1
		index_exact_words = 1
		html_strip     = 1
	}

	indexer
	{
		mem_limit           = 32M
	}

	searchd
	{
		listen			= {listen}
		log				= {pathToLog}searchd.log
		query_log		= {pathToLog}query.log
		read_timeout	= 5
		max_children	= 30
		pid_file		= {pathToLog}searchd.pid
		max_matches		= 1000
		seamless_rotate	= 1
		preopen_indexes	= 1
		unlink_old		= 1
		workers			= threads # for RT to work
		binlog_path		= {binlog}
	}
	";

		/** @var string $viewName имя view */
		protected $viewName;

		/** @var array $types типы, которые попадут в view */
		protected $types = [];

		/** @var array $fieldAlias алиасы полей */
		protected $fieldAlias = [];
		/** @var array $fieldInfo информация о полях, которые попадут в view */
		protected $fieldInfo = [];

		/** @var array $fieldAliasColumns поля со всех таблиц сгруппированные по алиасам */
		protected $fieldAliasColumns = [];

		/** @var array $fieldNames список полей, которые будут индексироваться */
		protected $fieldNames = [];

		/**
		 * Конструктор.
		 * @param string $viewName имя view
		 */
		public function __construct($viewName)
		{
			$this->viewName = $viewName;
		}

		/**
		 * Добавляет страницы указанного объектного типа в view
		 * @param iUmiObjectType $objectType объектный тип
		 * @param array $fieldNames список имен полей, которые попадут в view
		 * @param string $fieldsContentTable имя таблицы, в которой хранится контент полей
		 * @throws InvalidArgumentException если какое-либо поле не существует, либо задан пустой список полей
		 */
		public function addPages(iUmiObjectType $objectType, array $fieldNames, $fieldsContentTable = 'cms3_object_content') {
			$typeFields = $this->getAllTypeFields($objectType);

			if (empty($fieldNames) && empty($attrNames)) {
				throw new InvalidArgumentException('Cannot add pages to index. Fields list empty.');
			}

			$this->types[$objectType->getId()] = $objectType;
			/**
			 * @var iUmiFieldsGroup $group
			 * @var iUmiField $field
			 */
			foreach ($fieldNames as $fieldInfo) {
				$fieldInfo = (array) $fieldInfo;
				$fieldName = $fieldInfo[0];
				$this->fieldAlias[$fieldName] = isset($fieldInfo[1]) ? $fieldInfo[1] : $fieldName;
				$this->fieldNames[] = $this->fieldAlias[$fieldName];

				if (!isset($typeFields[$fieldName])) {
					throw new InvalidArgumentException(sprintf(
						'Cannot add pages to index. Field "%s" does not exist.',
						$fieldInfo
					));
				}
				if (!isset($this->fieldInfo[$fieldName])) {
					$this->fieldInfo[$fieldName] = [];
				}

				/** @var iUmiField $field */
				$field = $typeFields[$fieldName];
				$fieldSourceUid = $fieldsContentTable . '#' . $field->getId();
				if (!isset($this->fieldInfo[$fieldName][$fieldSourceUid])) {
					$this->fieldInfo[$fieldName][$fieldSourceUid] = [$field, $fieldsContentTable];
				}
			}
			$this->fieldNames = array_unique($this->fieldNames);

		}

		/**
		 * Добавляет страницы указанных объектных типов в view
		 * @param $pagesType array массив объектных типов
		 * @param $types umiObjectTypesCollection
		 * @param $indexFields array массив полей доступных для индексирования
		 */
		public function addPagesList($pagesType, $types, $indexFields) {
			foreach ($pagesType as $pageType) {
				$allFields = $types->getType($pageType)->getAllFields();

				$fields = [];
				/** @var iUmiField $field */
				foreach ($allFields as $field) {
					$fields[] = $field->getName();
				}

				$fields = array_uintersect($fields, $indexFields, 'strcasecmp');

				if (umiCount($fields) > 0) {
					$this->addPages(
						$types->getType($pageType),
						$fields
					);
				}
			}
		}

		/**
		 * Генерирует и возвращает запрос на создание VIEW
		 * @return string
		 */
		public function generateViewQuery() {
			if (empty($this->types)) {
				throw new RuntimeException('Cannot generate query for view. Index is empty.');
			}

			$fieldsPart = [];
			$sourcesPart = [];

			$searchCond = $this->getSearchCond();
			$this->mergeFieldTablesByType();

			foreach ($this->fieldInfo as $fieldName => $fieldInfo) {
				$fieldsPart[] = $this->getSelectFieldPartSql($fieldName);
				$sourcesPart[] = $this->getJoinSourcePartSql($fieldInfo);
			}

			$fieldsPart = array_filter($fieldsPart);

			return strtr(
				$this->createViewTemplate,
				[
					'{indexName}'  => $this->viewName,
					'{fields}'     => implode(',', $fieldsPart),
					'{sources}'    => implode('', $sourcesPart),
					'{typesCond}'  => implode(', ', array_keys($this->types)),
					'{searchCond}' => $searchCond
				]
			);
		}

		/**
		 * Генерирует и возвращает конфиг для Sphinx
		 * @param array $options параметры
		 * @return string
		 */
		public function generateSphinxConfig(array $options) {
			return strtr(
				$this->createSphinxConfig,
				array_merge(
					$options,
					[
						'{sqlQuery}' => implode(', ', $this->fieldNames),
					]
				)
			);
		}

		/** Объединяет поля с одинаковыми алиасами из всех таблиц */
		protected function mergeFieldTablesByType() {
			foreach ($this->fieldInfo as $fieldName => $fieldInfo) {
				$columns = [];
				/** @var iUmiField $field */
				foreach ($fieldInfo as $fieldSourceUid => $info) {
					list($field) = $info;
					$sourceColumnName = umiFieldType::getDataTypeDB($field->getDataType());
					$columns[] = "`{$fieldSourceUid}`.`{$sourceColumnName}`";
				}
				$this->fieldAliasColumns[$this->fieldAlias[$fieldName]][] = implode(', ', $columns);
			}
		}

		/**
		 * Генерирует часть запроса для выбора поля
		 * @param string $fieldName
		 * @return string
		 */
		protected function getSelectFieldPartSql($fieldName) {
			if (!array_key_exists($fieldName, $this->fieldAliasColumns)) {
				return '';
			}

			return strtr(
				$this->selectFieldTemplate,
				[
					'{fieldName}' => $fieldName,
					'{alias}' => $this->fieldAlias[$fieldName],
					'{columns}'   => implode(', ', $this->fieldAliasColumns[$fieldName])
				]
			);
		}

		/**
		 * Генерирует часть запроса для JOIN источников поля
		 * @param array $fieldInfo
		 * @return string
		 */
		protected function getJoinSourcePartSql(array $fieldInfo) {
			$joins = [];

			/** @var iUmiField $field */
			foreach ($fieldInfo as $fieldSourceUid => $info) {
				list($field, $fieldContentTable) = $info;
				$joins[] = strtr(
					$this->joinFieldSourceTemplate,
					[
						'{contentTable}' => $fieldContentTable,
						'{fieldSourceUid}' => $fieldSourceUid,
						'{fieldId}' => $field->getId()
					]
				);
			}

			return implode("\n", $joins);
		}

		/**
		 * Генерирует часть запроса для исключения неиндексируемых страниц
		 * @return string
		 */
		protected function getSearchCond() {
			if (!array_key_exists('is_unindexed', $this->fieldInfo)) {
				return '';
			}

			$columnName = key($this->fieldInfo['is_unindexed']);
			/** @var iUmiField $field */
			$field = $this->fieldInfo['is_unindexed'][$columnName][0];
			$columnType = umiFieldType::getDataTypeDB($field->getDataType());

			$column = "`{$columnName}`.{$columnType}";

			return strtr(
				$this->searchCondTemplate,
				[
					'{fieldAlias}' => $column
				]
			);
		}

		/**
		 * Возвращает список всех полей типа
		 * @param iUmiObjectType $objectType
		 * @return array в формате [fieldName => umiField, ...]
		 */
		protected function getAllTypeFields(iUmiObjectType $objectType) {
			$result = [];
			/** @var iUmiFieldsGroup $group */
			foreach ($objectType->getFieldsGroupsList(true) as $group) {
				/** @var iUmiField $field */
				foreach ($group->getFields() as $field) {
					$result[$field->getName()] = $field;
				}
			}

			return $result;
		}
	}

