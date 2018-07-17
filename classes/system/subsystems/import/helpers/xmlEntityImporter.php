<?php

	use UmiCms\Service;
	use UmiCms\System\Import\UmiDump\Helper\Entity\iSourceIdBinder;
	/**
	 * Вспомогательный класс для класса xmlImporter.
	 * Импортирует сущности.
	 */
	class xmlEntityImporter {

		/** @var DOMXPath объект для осуществления XPath-запросов */
		private $parser;

		/**
		 * @var iSourceIdBinder $relations экземпляр класса для создания связей импортируемых сущностей
		 * с уже существующими в системе сущностями
		 */
		private $relations;

		/** @var array $result результат импорта */
		private $result;

		/**
		 * Конструктор
		 * @param DOMXPath $parser
		 * @param iSourceIdBinder $relations
		 * @internal param int $sourceId идентификатор ресурса
		 */
		public function __construct(DOMXPath $parser, iSourceIdBinder $relations) {
			$this->parser = $parser;
			$this->relations = $relations;
			$this->result = [
				'created' => 0,
				'updated' => 0,
				'errors' => [],
				'log' => [],
			];
		}

		/**
		 * Импортирует все сущности
		 * @return array результат импорта
		 */
		public function import() {
			/** @var array $nodeList */
			$nodeList = $this->parser->evaluate('/umidump/entities/entity');

			foreach ($nodeList as $entityNode) {
				try {
					$this->importEntity($entityNode);
				} catch (Exception $e) {
					$this->logError($e->getMessage());
				}
			}

			return $this->result;
		}

		/**
		 * Импортирует отдельную сущность
		 * @param DOMElement $entityNode узел сущности
		 */
		private function importEntity(DOMElement $entityNode) {
			$id = $this->getRequiredAttribute($entityNode, 'id');
			$service = $this->getRequiredAttribute($entityNode, 'service');
			$module = $entityNode->getAttribute('module');

			if (is_string($module) && !empty($module)) {
				cmsController::getInstance()
					->getModule($module);
			}

			$collection = $this->getCollection($service);
			$table = $collection->getMap()->get('EXCHANGE_RELATION_TABLE_NAME');

			$entityId = $this->getEntityId($id, $table);
			$properties = $this->getEntityProperties($entityNode);
			$properties = $collection->updateRelatedId($properties, $this->relations->getSourceId());

			if ($entityId) {
				if ($entityNode->getAttribute('install-only')) {
					return;
				}

				$properties['id'] = $entityId;
				$this->update($id, $properties, $collection);
			} else {
				$this->create($id, $properties, $collection);
			}
		}

		/**
		 * Возвращает значение обязательного атрибута сущности
		 * @param DOMElement $entityNode узел сущности
		 * @param string $key название атрибута
		 * @return mixed
		 * @throws importException
		 */
		private function getRequiredAttribute($entityNode, $key) {
			$attribute = $entityNode->getAttribute($key);
			if (!$attribute) {
				throw new importException(getLabel('error-no-entity-attribute', false, $key));
			}
			return $attribute;
		}

		/**
		 * Возвращает коллекцию сущностей
		 * @param string $service название сервиса
		 * @return iUmiCollection|iUmiConstantMapInjector|object
		 */
		private function getCollection($service) {
			return Service::get($service);
		}

		/**
		 * Возвращает идентификатор уже существующей в системе сущности
		 * @param string $id идентификатор импортируемой сущности
		 * @param string $table название таблицы связей
		 * @return mixed
		 */
		private function getEntityId($id, $table) {
			return $this->relations->getInternalId($id, $table);
		}

		/**
		 * Обновляет свойства сущности с id, указанном в массиве свойств.
		 * Если сущность с таким id не будет найдена, система создаст новую сущность.
		 * @param string $id идентификатор импортируемой сущности, указанный в файле импорта
		 * @param array $properties свойства сущности
		 * @param iUmiCollection|iUmiConstantMapInjector $collection коллекция сущностей
		 */
		private function update($id, $properties, $collection) {
			$result = $collection->import([$properties]);
			$map = $collection->getMap();
			$createdKey = $map->get('CREATED_COUNTER_KEY');
			$updatedKey = $map->get('UPDATED_COUNTER_KEY');
			$errorsKey = $map->get('IMPORT_ERRORS_KEY');

			if ($result[$createdKey]) {
				$this->logCreated($id);
			} elseif ($result[$updatedKey]) {
				$this->logUpdated($id);
			}

			/** @var array $errorList */
			$errorList = $result[$errorsKey];

			foreach ($errorList as $errorMessage) {
				$this->logError($errorMessage);
			}
		}

		/**
		 * Создает новую сущность с указанными свойствами
		 * @param string $id идентификатор импортируемой сущности, указанный в файле импорта
		 * @param array $properties свойства сущности
		 * @param iUmiCollection|iUmiConstantMapInjector $collection коллекция сущностей
		 */
		private function create($id, $properties, $collection) {
			$entity = $collection->create($properties);
			$table = $collection->getMap()->get('EXCHANGE_RELATION_TABLE_NAME');
			$this->relations->defineRelation($id, $entity->getId(), $table);
			$this->logCreated($id);
		}

		/**
		 * Возвращает все свойства сущности в формате [name => value, ...]
		 * Формат импортируемой сущности:
		 *
		 * <entity id="1" service="redirects">
		 *   <source>test-source</source>
		 *   <target>test-target</target>
		 *   <status>301</status>
		 *   <made_by_user>1</made_by_user>
		 * </entity>
		 *
		 * @param DOMElement $entityNode узел сущности
		 * @return array
		 */
		private function getEntityProperties(DOMElement $entityNode) {
			$propertyList = [];
			$propertyNodeList = $entityNode->childNodes;

			foreach ($propertyNodeList as $propertyNode) {
				if ($propertyNode instanceof DOMElement) {
					$propertyList[$propertyNode->tagName] = $propertyNode->nodeValue;
				}
			}

			return $propertyList;
		}

		/**
		 * Записывает в журнал о создании сущности и обновляет счетчик
		 * @param string $id идентификатор сущности
		 */
		private function logCreated($id) {
			$this->result['log'][] = getLabel('label-entity') . " ({$id}) " . getLabel('label-has-been-created-f');
			$this->result['created'] += 1;
		}
		
		/**
		 * Записывает в журнал об обновлении сущности и обновляет счетчик
		 * @param string $id идентификатор сущности
		 */
		private function logUpdated($id) {
			$this->result['log'][] = getLabel('label-entity') . " ({$id}) " . getLabel('label-has-been-updated-f');
			$this->result['updated'] += 1;
		}

		/**
		 * Записывает в журнал ошибок сообщение об ошибке
		 * @param string $message сообщение об ошибке
		 */
		private function logError($message) {
			$this->result['errors'][] = $message;
		}
	}
