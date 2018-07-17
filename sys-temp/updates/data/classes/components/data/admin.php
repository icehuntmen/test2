<?php

	use UmiCms\Service;

	/** Класс функционала административной панели модулей "Шаблоны данных" и "Корзина " */
	class DataAdmin {

		use baseModuleAdmin;
		/** @var data $module */
		public $module;
		/** @var int $per_page количество элементов справочника к выводу */
		public $per_page = 50;

		/**
		 * Возвращает список иерархических типов данных, если передан ключевой параметр $_REQUEST['param0'] = do,
		 * то метод сохраняет изменения списка иерархических типов данных
		 * @throws coreException
		 */
		public function config() {
			$mode = getRequest('param0');

			if ($mode == 'do') {
				$this->saveEditedList('basetypes');
				$this->chooseRedirect();
			}

			$hierarchy_types = umiHierarchyTypesCollection::getInstance()->getTypesList();

			$this->setDataType('list');
			$this->setActionType('modify');
			$data = $this->prepareData($hierarchy_types, 'hierarchy_types');
			$this->setData($data, umiCount($hierarchy_types));
			$this->doData();
		}

		/**
		 * Возвращает список объектных типов данных
		 * @throws coreException
		 * @throws expectObjectTypeException
		 */
		public function types() {
			$perPage = getRequest('per_page_limit');
			$currentPageNumber = (int) getRequest('p');

			if (isset($_REQUEST['rel'][0])) {
				$parentTypeId = $this->expectObjectTypeId($_REQUEST['rel'][0], false, true);
			} else {
				$parentTypeId = $this->expectObjectTypeId('param0');
			}

			if (isset($_REQUEST['search-all-text'][0])) {
				$searchAllText = getFirstValue($_REQUEST['search-all-text']);
			} else {
				$searchAllText = false;
			}

			$types = umiObjectTypesCollection::getInstance();
			$domainId = getFirstValue(getRequest('domain_id'));
			$parentTypeId = $parentTypeId ?: 0;

			if ($searchAllText) {
				$childIdList = $types->getIdListByNameLike($searchAllText, $domainId);
			} else {
				$childIdList = $types->getSubTypeListByDomain($parentTypeId, $domainId);
			}

			$tmp = [];
			foreach ($childIdList as $typeId) {
				$type = $types->getType($typeId);

				if (!$type instanceof iUmiObjectType) {
					continue;
				}

				$tmp[$typeId] = $type->getName();
			}

			if (isset($_REQUEST['order_filter']['name'])) {
				natsort($tmp);
				if ($_REQUEST['order_filter']['name'] == 'desc') {
					$tmp = array_reverse($tmp, true);
				}
			}

			$childIdList = array_keys($tmp);
			unset($tmp);
			$childIdList = $this->excludeNestedTypes($childIdList);

			$total = umiCount($childIdList);
			$childIdList = array_slice($childIdList, $currentPageNumber * $perPage, $perPage);

			$this->setDataType('list');
			$this->setActionType('view');
			$this->setDataRange($perPage, $currentPageNumber * $perPage);

			$data = $this->prepareData($childIdList, 'types');
			$this->setData($data, $total);
			$this->doData();
		}

		/**
		 * Создает объектный тип данных и перенаправляет на форму редактирования объектного типа данных
		 * @throws coreException
		 * @throws expectObjectTypeException
		 */
		public function type_add() {
			$parent_type_id = (int) $this->expectObjectTypeId('param0');

			$objectTypes = umiObjectTypesCollection::getInstance();
			$type_id = $objectTypes->addType($parent_type_id, 'i18n::object-type-new-data-type');

			$this->module->redirect($this->module->pre_lang . '/admin/data/type_edit/' . $type_id . '/');
		}

		/**
		 * Выводит информацию об объектном типе данных для построения формы редактирования.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do, сохраняет изменения объектного типа данных
		 * @throws coreException
		 * @throws expectObjectTypeException
		 */
		public function type_edit() {
			$type = $this->expectObjectType('param0');

			$mode = (String) getRequest('param1');

			if ($mode == 'do') {

				try {
					$this->saveEditedTypeData($type);
				} catch (wrongParamException $exception) {
					throw new publicAdminException($exception->getMessage());
				}

				$this->chooseRedirect();
			}

			$this->setDataType('form');
			$this->setActionType('modify');

			$data = $this->prepareData($type, 'type');

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Выводит информацию для построения формы добавления поля.
		 * Если передан ключевой параметр $_REQUEST['param2'] = do, то добавляет поле
		 * @param bool $redirectString нужно ли осуществлять перенаправление на форму редактирования
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function type_field_add($redirectString = false) {
			$groupId = (int) getRequest('param0');
			$typeId = (int) getRequest('param1');
			$mode = (string) getRequest('param2');

			$inputData = [
				'group-id' => $groupId,
				'type-id' => $typeId
			];

			if ($mode == 'do') {
				try {
					$fieldId = $this->saveAddedFieldData($inputData);
				} catch (wrongParamException $exception) {
					throw new publicAdminException($exception->getMessage());
				}

				if (getRequest('noredirect')) {
					$field = umiFieldsCollection::getInstance()->getField($fieldId);
					$this->setDataType('form');
					$this->setActionType('modify');
					$data = $this->prepareData($field, 'field');
					$this->setData($data);
					$this->doData();
					return;
				}

				$fieldEditLink = $this->module->pre_lang . "/admin/data/type_field_edit/$fieldId/$typeId/";
				$redirectString = $redirectString ?: $fieldEditLink;
				$this->chooseRedirect($redirectString);
			}

			$this->setDataType('form');
			$this->setActionType('create');

			$data = $this->prepareData($inputData, 'field');

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Выводит информацию для построения формы редактирования поля.
		 * Если передан ключевой параметр $_REQUEST['param2'] = do, то сохраняет изменения поля.
		 * @throws coreException
		 */
		public function type_field_edit() {
			$field_id = (int) getRequest('param0');
			$mode = (string) getRequest('param2');

			$field = umiFieldsCollection::getInstance()->getField($field_id);

			if ($mode == 'do') {
				$this->saveEditedFieldData($field);
				if (!getRequest('noredirect')) {
					$this->chooseRedirect();
				}
			}

			$this->setDataType('form');
			$this->setActionType('modify');

			$data = $this->prepareData($field, 'field');

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Прикрепляет поле к группе, находящейся в типе данных
		 * @param int $typeId идентификатор типа данных
		 * @param int $groupId идентификатор группы
		 * @param int $fieldId идентификатор поля
		 * @throws publicAdminException
		 */
		public function attachField($typeId = null, $groupId = null, $fieldId = null){
			$typeId = ($typeId === null) ? (int) getRequest('param0') : $typeId;
			$groupId = ($groupId === null) ? (int) getRequest('param1') : $groupId;
			$fieldId = ($fieldId === null) ? (int) getRequest('param2') : $fieldId;

			$umiFields = umiFieldsCollection::getInstance();
			$field = $umiFields->getById($fieldId);

			if (!$field instanceof iUmiField) {
				throw new publicAdminException(getLabel('label-incorrect-field-id'));
			}

			$umiObjectTypes = umiObjectTypesCollection::getInstance();
			$type = $umiObjectTypes->getType($typeId);

			if (!$type instanceof iUmiObjectType) {
				throw new publicAdminException(getLabel('label-incorrect-type-id'));
			}

			$allowInactiveGroup = true;
			$group = $type->getFieldsGroup($groupId, $allowInactiveGroup);

			if (!$group instanceof iUmiFieldsGroup) {
				throw new publicAdminException(getLabel('label-incorrect-group-id'));
			}

			$group->attachField($field->getId());

			$this->setDataType('form');
			$this->setActionType('modify');

			$data = $this->prepareData($field, 'field');

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Производит поиск среди типов данных, связанных с заданным, поля с заданными параметрами.
		 * По связанными типами подразумеваются:
		 *
		 * 1) Родитель типа данных;
		 * 2) Соседи типа данных;
		 * 3) Дочерние типы данных (на один уровень)
		 *
		 * Возвращает идентификатор найденного поля или null
		 * @param int $typeId идентификатор типа данных
		 * @param array $fieldData данные поля
		 *
		 * [
		 *      'name' => 'строковой идентификатор поля',
		 *      'title' => 'название поля',
		 *      'field_type_id' => 'идентификатор типа поля'
		 * ]
		 *
		 * @throws publicAdminException
		 */
		public function getSameFieldFromRelatedTypes($typeId = null, array $fieldData = []) {
			$typeId = ($typeId === null) ? (int) getRequest('param0') : $typeId;
			$fieldData = empty($fieldData) ? (array) getRequest('data') : $fieldData;
			$fieldName = isset($fieldData['name']) ? $fieldData['name'] : '';
			$fieldTitle = isset($fieldData['title']) ? $fieldData['title'] : '';
			$fieldDataTypeId = isset($fieldData['field_type_id']) ? $fieldData['field_type_id'] : '';

			$umiObjectTypes = umiObjectTypesCollection::getInstance();
			$type = $umiObjectTypes->getType($typeId);

			if (!$type instanceof iUmiObjectType) {
				throw new publicAdminException(getLabel('label-incorrect-type-id'));
			}

			$parentTypeId = $type->getParentId();

			$sameFieldSource = 'parent';
			$fieldIdAndTypeId = $this->getSameFieldIdAndTypeId(
				[$parentTypeId], $fieldName, $fieldTitle, $fieldDataTypeId
			);

			if (empty($fieldIdAndTypeId)) {
				$siblingTypeIdList = $umiObjectTypes->getSubTypesList($parentTypeId);
				$sameFieldSource = 'sibling';
				$fieldIdAndTypeId = $this->getSameFieldIdAndTypeId(
					$siblingTypeIdList, $fieldName, $fieldTitle, $fieldDataTypeId
				);
			}

			if (empty($fieldIdAndTypeId)) {
				$childrenTypeIdList = $umiObjectTypes->getSubTypesList($typeId);
				$sameFieldSource = 'child';
				$fieldIdAndTypeId = $this->getSameFieldIdAndTypeId(
					$childrenTypeIdList, $fieldName, $fieldTitle, $fieldDataTypeId
				);
			}

			if (empty($fieldIdAndTypeId)) {
				$sameFieldId = null;
				$message = null;
			} else {
				$sameFieldId = $fieldIdAndTypeId['field_id'];
				$sameTypeId = $fieldIdAndTypeId['type_id'];

				$sameType = $umiObjectTypes->getType($sameTypeId);

				if (!$sameType instanceof iUmiObjectType) {
					throw new publicAdminException(getLabel('label-incorrect-type-id'));
				}

				$format = getLabel('label-message-format-attach-field');
				$sourceLabel = getLabel('label-message-attach-field-' . $sameFieldSource);
				$message = sprintf($format, $sourceLabel, $sameType->getName());
			}

			$this->setDataType('list');
			$this->setActionType('view');
			$this->setData([
				'fieldId' => $sameFieldId,
				'message' => $message
			]);

			$this->doData();
		}

		/**
		 * Выводит информацию для построения формы редактирования группы полей.
		 * Если передан ключевой параметр $_REQUEST['param2'] = do, то сохраняет изменения группы полей.
		 * @throws coreException
		 */
		public function type_group_edit() {
			$group_id = (int) getRequest('param0');
			$type_id = (int) getRequest('param1');
			$mode = (string) getRequest('param2');

			$group = umiObjectTypesCollection::getInstance()->getType($type_id)->getFieldsGroup($group_id);

			if ($mode == 'do') {
				$this->saveEditedGroupData($group);

				if (!getRequest('noredirect')) {
					$this->chooseRedirect();
				}
			}

			$this->setDataType('form');
			$this->setActionType('modify');

			$data = $this->prepareData($group, 'group');

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Выводит информацию для построения формы добавления группы полей.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do, то добавляет группу полей
		 * @param bool $redirectString нужно ли осуществлять перенаправление на форму редактирования
		 * @throws coreException
		 */
		public function type_group_add($redirectString = false) {
			$type_id = (int) getRequest('param0');
			$mode = (string) getRequest('param1');
			$inputData = ['type-id' => $type_id];

			if ($mode == 'do') {
				$fields_group_id = $this->saveAddedGroupData($inputData);
				if (getRequest('noredirect')) {
					$group = umiObjectTypesCollection::getInstance()->getType($type_id)->getFieldsGroup($fields_group_id);
					$this->setDataType('form');
					$this->setActionType('modify');
					$data = $this->prepareData($group, 'group');
					$this->setData($data);
					$this->doData();

					return;
				}

				$this->chooseRedirect(($redirectString ?: ($this->module->pre_lang . '/admin/data/type_group_edit/')) . $fields_group_id . '/' . $type_id . '/');
			}

			$this->setDataType('form');
			$this->setActionType('create');

			$data = $this->prepareData($inputData, 'group');

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Удаляет объектный тип данных
		 * @throws coreException
		 * @throws expectObjectTypeException
		 * @throws publicAdminException
		 */
		public function type_del() {
			$types = getRequest('element');

			if (!is_array($types)) {
				$types = [$types];
			}

			foreach ($types as $typeId) {
				$this->expectObjectTypeId($typeId, true, true);
				umiObjectTypesCollection::getInstance()->delType($typeId);
			}

			$this->setDataType('list');
			$this->setActionType('view');
			$data = $this->prepareData($types, 'types');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает настройки для формирования табличного контрола
		 * @param string $param контрольный параметр
		 * @return array
		 */
		public function getDatasetConfiguration($param = '') {
			$deleteMethod = 'type_del';
			if ($param == 'guides') {
				$loadMethod = 'guides';
			} elseif ($param == 'trash') {
				$loadMethod = 'trash';
				$deleteMethod = 'trash_del';
			} elseif (is_numeric($param)) {
				$loadMethod = 'guide_items/' . $param;

				return [
						'methods' => [
								[
										'title'   => getLabel('smc-load'),
										'forload' => true,
										'module'  => 'data',
										'#__name' => $loadMethod
								],
								[
										'title'   => getLabel('smc-delete'),
										'module'  => 'data',
										'#__name' => 'guide_item_del',
										'aliases' => 'tree_delete_element,delete,del'
								]
						],
						'types'   => [
								[
										'common' => 'true',
										'id'     => $param
								]
						]
				];
			} else {
				$loadMethod = 'types';
			}

			$p = [
					'methods' => [
							[
									'title'   => getLabel('smc-load'),
									'forload' => true,
									'module'  => 'data',
									'#__name' => $loadMethod
							],
							[
									'title'   => getLabel('smc-delete'),
									'module'  => 'data',
									'#__name' => $deleteMethod,
									'aliases' => 'tree_delete_element,delete,del'
							]
					]
			];

			if ($param == 'trash') {
				$p['methods'][] = [
						'title'   => getLabel('smc-restore'),
						'module'  => 'data',
						'#__name' => 'trash_restore',
						'aliases' => 'restore_element'
				];
			}

			$p['default'] = 'name[400px]';

			return $p;
		}

		/**
		 * Возвращает список справочников
		 * @throws coreException
		 */
		public function guides() {
			if (isset($_REQUEST['search-all-text'][0])) {
				$searchAllText = array_extract_values($_REQUEST['search-all-text']);
				foreach ($searchAllText as $i => $v) {
					$searchAllText[$i] = mb_strtolower($v);
				}
			} else {
				$searchAllText = false;
			}

			$rel = umiObjectTypesCollection::getInstance()->getTypeIdByGUID('root-guides-type');
			if (($rels = getRequest('rel')) && umiCount($rels)) {
				$rel = getArrayKey($rels, 0);
			}

			$per_page = getRequest('per_page_limit');
			$curr_page = (int) getRequest('p');

			$types = umiObjectTypesCollection::getInstance();
			$guides_list = $types->getGuidesList(true, $rel);

			$tmp = [];
			foreach ($guides_list as $typeId => $name) {
				if ($searchAllText) {
					$match = false;
					foreach ($searchAllText as $searchString) {
						if (strstr(mb_strtolower($name), $searchString) !== false) {
							$match = true;
						}
					}
					if (!$match) {
						continue;
					}
				}
				$tmp[$typeId] = $name;
			}

			if (isset($_REQUEST['order_filter']['name'])) {
				natsort($tmp);
				if ($_REQUEST['order_filter']['name'] == 'desc') {
					$tmp = array_reverse($tmp, true);
				}
			}
			$guides_list = array_keys($tmp);
			unset($tmp);
			$guides_list = $this->excludeNestedTypes($guides_list);

			$total = umiCount($guides_list);
			$guides = array_slice($guides_list, $per_page * $curr_page, $per_page);

			$this->setDataType('list');
			$this->setActionType('view');
			$this->setDataRange($per_page, $curr_page * $per_page);

			$data = $this->prepareData($guides, 'types');
			$this->setData($data, $total);
			$this->doData();
		}

		/**
		 * Возвращает список объектов справочника.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do,
		 * то сохраняет изменения списка объектов
		 * @param bool|int $guide_id идентификатор справочника
		 * @param bool|int $per_page количество элементов справочника к выводу
		 * @param int $curr_page текущий номер страницы, в рамках пагинации
		 * @throws coreException
		 */
		public function guide_items($guide_id = false, $per_page = false, $curr_page = 0) {
			$this->setDataType('list');
			$this->setActionType('modify');

			if (!$curr_page) {
				$curr_page = (int) getRequest('p');
			}
			if (!$per_page) {
				$per_page = getRequest('per_page_limit');
			}
			if (!$per_page) {
				$per_page = $this->per_page;
			}
			if (!$guide_id) {
				$guide_id = (int) getRequest('param0');
			}

			$mode = (string) getRequest('param1');
			$guide = selector::get('object-type')->id($guide_id);

			if ($guide) {
				$this->setHeaderLabel(getLabel('header-data-guide_items') . ' "' . $guide->getName() . '"');
			}

			if ($this->module->ifNotXmlMode()) {
				$this->setDirectCallError();
				$this->doData();

				return;
			}

			$sel = new selector('objects');
			$sel->types('object-type')->id($guide_id);
			$sel->limit($per_page * $curr_page, $per_page);

			selectorHelper::detectFilters($sel);

			if ($mode == 'do') {
				$params = [
						'type_id' => $guide_id
				];
				$this->saveEditedList('objects', $params);
				$this->chooseRedirect();
			}

			$this->setDataRange($per_page, $curr_page * $per_page);
			$data = $this->prepareData($sel->result(), 'objects');
			$this->setData($data, $sel->length());
			$this->doData();
		}

		/**
		 * Возвращает данные справочника для формирования формы создания элемента справочника.
		 * Если передан ключевой параметр $_REQUEST['param1'] = do, то создает элемент справочника
		 * и перенаправляет на страницу редактирования справочника, а если $_REQUEST['param1'] = fast,
		 * то создает пустой элемент справочника.
		 * @throws coreException
		 * @throws publicAdminException
		 * @throws wrongElementTypeAdminException
		 */
		public function guide_item_add() {
			$type = (int) getRequest('param0');
			$mode = (string) getRequest('param1');
			$inputData = ['type-id' => $type];

			if ($mode == 'do') {
				$object = $this->saveAddedObjectData($inputData);
				$this->chooseRedirect($this->module->pre_lang . '/admin/data/guide_item_edit/' . $object->getId() . '/');
			} elseif ($mode == 'fast') {
				$objects = umiObjectsCollection::getInstance();
				try {
					$objects->addObject(null, $type);
				} catch (fieldRestrictionException $e) {
				}
			}

			$this->setDataType('form');
			$this->setActionType('create');

			$data = $this->prepareData($inputData, 'object');

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает данные справочника для формирования формы редактирования.
		 * Если передан ключевой параметр $_REQUEST['param1'] = 0, то сохраняет
		 * изменения справочника.
		 * @throws coreException
		 * @throws expectObjectException
		 */
		public function guide_item_edit() {
			$object = $this->expectObject('param0');
			$mode = (string) getRequest('param1');

			if ($mode == 'do') {
				$this->saveEditedObjectData($object);
				$this->chooseRedirect();
			}

			$this->setDataType('form');
			$this->setActionType('modify');

			$data = $this->prepareData($object, 'object');

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Удаляет объекты справочника
		 * @throws coreException
		 * @throws expectObjectException
		 * @throws wrongElementTypeAdminException
		 */
		public function guide_item_del() {
			$objects = getRequest('element');

			if (!is_array($objects)) {
				$objects = [$objects];
			}

			foreach ($objects as $objectId) {
				$object = $this->expectObject($objectId, false, true);
				$params = ['object' => $object];
				$this->deleteObject($params);
			}

			$this->setDataType('list');
			$this->setActionType('view');
			$data = $this->prepareData($objects, 'objects');
			$this->setData($data);
			$this->doData();
		}

		/**
		 * Удаляет справочник
		 * @throws coreException
		 * @throws publicAdminException
		 */
		public function guide_del() {
			$type_id = (int) getRequest('param0');
			umiObjectTypesCollection::getInstance()->delType($type_id);
			$this->module->redirect($this->module->pre_lang . '/admin/data/guides/');
		}

		/**
		 * Создает справочник и перенаправяет на форму редактирования справочника
		 * @throws coreException
		 * @throws expectObjectTypeException
		 */
		public function guide_add() {
			$objectTypes = umiObjectTypesCollection::getInstance();
			$parent_type_id = (int) $this->expectObjectTypeId('param0');

			if ($parent_type_id == 0) {
				$parent_type_id = $objectTypes->getTypeIdByGUID('root-guides-type');
			}

			$type_id = $objectTypes->addType($parent_type_id, 'i18n::object-type-new-guide');
			$type = $objectTypes->getType($type_id);
			$type->setIsPublic(true);
			$type->setIsGuidable(true);
			$type->commit();

			$this->module->redirect($this->module->pre_lang . '/admin/data/type_edit/' . $type_id . '/');
		}

		/**
		 * Возвращает список элементов справочника для
		 * интерфейса полей типов "Выпадающий список" и "Выпадающий список со множественным выбором"
		 * @throws coreException
		 * @throws selectorException
		 */
		public function guide_items_all() {
			$this->setDataType('list');
			$this->setActionType('modify');

			if ($this->module->ifNotXmlMode()) {
				$this->setDirectCallError();
				$this->doData();

				return;
			}

			$guide_id = (int) getRequest('param0');
			$sel = new selector('objects');
			$sel->types('object-type')->id($guide_id);

			$maxItemsCount = (int) mainConfiguration::getInstance()->get('kernel', 'max-guided-items');

			if ($maxItemsCount && $maxItemsCount <= 15 && $maxItemsCount > 0) {
				$maxItemsCount = 16;
			} elseif ($maxItemsCount <= 0) {
				$maxItemsCount = 50;
			}

			$textSearch = getRequest('search');

			if ($textSearch) {
				foreach ($textSearch as $searchString) {
					$stringLabel = ulangStream::getI18n($searchString, '', true);
					if ($stringLabel === null) {
						$sel->where('name')->like('%' . $searchString . '%');
					} else {
						if (!is_array($stringLabel)) {
							$stringLabel = [$stringLabel];
						}
						$sel->option('or-mode')->field('name');
						foreach ($stringLabel as $label) {
							$sel->where('name')->equals($label);
						}
						$sel->where('name')->like('%' . $searchString . '%');
					}
				}
			}

			$umiPermission = permissionsCollection::getInstance();

			if (!$umiPermission->isSv()) {
				$systemUsersPermissions = Service::SystemUsersPermissions();
				$sel->where('id')->notequals($systemUsersPermissions->getSvGroupId());
			}

			if (getRequest('limit') !== null) {
				$sel->limit(15 * (int) getRequest('p'), 15);
			}

			$sel->option('return')->value('count');
			$total = $sel->length();

			if (getRequest('allow-empty') !== null && $total > $maxItemsCount) {
				$data = [
						'empty' => [
								'attribute:total'  => $total,
								'attribute:result' => 'Too much items'
						]
				];
				$this->setDataRange(0);
				$this->setData($data, $total);
				$this->doData();
			} else {
				$sel->flush();
				$sel->option('return')->value('id');

				$guide_items = [];
				$tmp = [];
				$objects = umiObjectsCollection::getInstance();

				foreach ($sel->result() as $itemArray) {
					$itemId = $itemArray['id'];
					$item = $objects->getObject($itemId);
					if ($item instanceof iUmiObject) {
						$tmp[$itemId] = $item->getName();
						$guide_items[$itemId] = $item;
					}
				}

				if (!umiObjectsCollection::isGuideItemsOrderedById()) {
					natsort($tmp);
					$guide_items = array_keys($tmp);
					unset($tmp);
				}

				$this->setDataRangeByPerPage($maxItemsCount);
				$data = $this->prepareData($guide_items, 'objects');
				$this->setData($data, $total);
				$this->doData();
			}
		}

		/**
		 * Возвращает список доменов в формате содержимого справочника.
		 * Часть api для табличного контрола.
		 */
		public function getDomainList() {
			$this->setDataType('list');
			$this->setActionType('modify');

			if ($this->module->ifNotXmlMode()) {
				$this->setDirectCallError();
				$this->doData();
				return;
			}

			$domainList = Service::DomainCollection()
				->getList();
			$domainNodeList = [];

			foreach ($domainList as $domain) {
				$domainNodeList[] = [
					'attribute:id' => $domain->getId(),
					'attribute:name' => $domain->getHost()
				];
			}

			$result = [
				'nodes:object' => $domainNodeList
			];

			$this->setData($result, umiCount($domainList));
			$this->doData();
		}

		/**
		 * Добавляет объект в справочник
		 * @param string $name имя создаваемого объекта
		 * @param int $guideId ID справочника, в который будет добавлен объект
		 */
		public function addObjectToGuide($name = '', $guideId = 0) {
			if (!$name) {
				$name = getRequest('param0');
			}

			if (!$guideId) {
				$guideId = getRequest('param1');
			}

			$inputData = [
					'type-id' => $guideId,
					'name'    => $name
			];

			try {
				$objectId = $this->saveAddedObjectData($inputData);
			} catch (Exception $exception) {
				$objectId = 0;
			}

			$result = ['object' => $objectId];
			$this->setData($result);
			$this->doData();
		}

		/**
		 * Меняет порядок поля внутри группы полей
		 * @throws Exception
		 * @throws coreException
		 */
		public function json_move_field_after() {
			$field_id = (int) getRequest('param0');
			$before_field_id = (int) getRequest('param1');
			$is_last = (string) getRequest('param2');
			$type_id = (int) getRequest('param3');
			$connection = ConnectionPool::getInstance()->getConnection();

			if ($is_last != 'false') {
				$new_group_id = (int) $is_last;
			} else {
				$sql = <<<SQL
SELECT fc.group_id FROM cms3_object_field_groups ofg, cms3_fields_controller fc WHERE ofg.type_id = '{$type_id}' AND fc.group_id = ofg.id AND fc.field_id = '{$before_field_id}'
SQL;
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);

				$new_group_id = null;

				if ($result->length() > 0) {
					$fetchResult = $result->fetch();
					$new_group_id = array_shift($fetchResult);
				}
			}

			$sql = <<<SQL
SELECT fc.group_id FROM cms3_object_field_groups ofg, cms3_fields_controller fc WHERE ofg.type_id = '{$type_id}' AND fc.group_id = ofg.id AND fc.field_id = '{$field_id}'
SQL;
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$group_id = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$group_id = array_shift($fetchResult);
			}

			if ($is_last == 'false') {
				$after_field_id = $before_field_id;
			} else {
				$sql = "SELECT field_id FROM cms3_fields_controller WHERE group_id = '{$group_id}' ORDER BY ord DESC LIMIT 1";

				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);
				$after_field_id = 0;

				if ($result->length() > 0) {
					$fetchResult = $result->fetch();
					$after_field_id = array_shift($fetchResult);
				}
			}

			$type = umiObjectTypesCollection::getInstance()
				->getType($type_id);

			if (!$type instanceof iUmiObjectType) {
				$this->module->flush();
			}

			$fieldsGroup = $type->getFieldsGroup($group_id);

			if (!$fieldsGroup instanceof iUmiFieldsGroup) {
				$this->module->flush();
			}

			$is_last = $is_last == 'false';
			$fieldsGroup->moveFieldAfter($field_id, $after_field_id, $new_group_id, $is_last);
			$this->module->flush();
		}

		/**
		 * Меняет порядок группы полей внутри типа данных
		 * @throws Exception
		 * @throws coreException
		 */
		public function json_move_group_after() {
			$group_id = (int) getRequest('param0');
			$before_group_id = (string) getRequest('param1');
			$type_id = (int) getRequest('param2');
			$connection = ConnectionPool::getInstance()->getConnection();

			if ($before_group_id != 'false') {
				$sql = "SELECT ord FROM cms3_object_field_groups WHERE type_id = '{$type_id}' AND id = '" . ((int) $before_group_id) . "'";
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);
				$neword = 0;

				if ($result->length() > 0) {
					$fetchResult = $result->fetch();
					$neword = array_shift($fetchResult);
				}
			} else {
				$sql = "SELECT MAX(ord) FROM cms3_object_field_groups WHERE type_id = '{$type_id}'";
				$result = $connection->queryResult($sql);
				$result->setFetchType(IQueryResult::FETCH_ROW);
				$neword = 5;

				if ($result->length() > 0) {
					$fetchResult = $result->fetch();
					$neword = array_shift($fetchResult) + 5;
				}
			}

			$type = umiObjectTypesCollection::getInstance()
				->getType($type_id);

			if (!$type instanceof iUmiObjectType) {
				$this->module->flush();
			}

			$before_group_id = $before_group_id == 'false';
			$type->setFieldGroupOrd($group_id, $neword, $before_group_id);
			$this->module->flush();
		}

		/**
		 * Удаляет поле
		 * @throws Exception
		 * @throws coreException
		 */
		public function json_delete_field() {
			$field_id = (int) getRequest('param0');
			$type_id = (int) getRequest('param1');
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "SELECT fc.group_id FROM cms3_object_field_groups ofg, cms3_fields_controller fc WHERE ofg.type_id = '{$type_id}' AND fc.group_id = ofg.id AND fc.field_id = '{$field_id}'";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$group_id = null;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$group_id = array_shift($fetchResult);
			}

			$type = umiObjectTypesCollection::getInstance()
				->getType($type_id);

			if (!$type instanceof iUmiObjectType) {
				$this->module->flush();
			}

			$fieldsGroup = $type->getFieldsGroup($group_id);

			if (!$fieldsGroup instanceof iUmiFieldsGroup) {
				$this->module->flush();
			}

			$fieldsGroup->detachField($field_id);
			$this->module->flush();
		}

		/**
		 * Удаляет группу полей
		 * @throws coreException
		 */
		public function json_delete_group() {
			$group_id = (int) getRequest('param0');
			$type_id = (int) getRequest('param1');
			$type = umiObjectTypesCollection::getInstance()
				->getType($type_id);

			if (!$type instanceof iUmiObjectType) {
				$this->module->flush();
			}

			$type->delFieldsGroup($group_id);
			$this->module->flush();
		}

		/**
		 * Возвращает список страниц, помещенных в корзину
		 * @throws coreException
		 */
		public function trash() {
			$limit = getRequest('per_page_limit'); // внутри магия с сессией для этого параметра
			$pageNumber = getRequest('p'); // внутри магия с сессией для этого параметра

			$request = Service::Request()
				->Get();
			$searchName = getFirstValue($request->get('search-all-text'));
			$domainId = getFirstValue($request->get('domain_id'));
			$languageId = getFirstValue($request->get('lang_id'));

			$total = 0; // значение передается по ссылке
			$pageIdList = umiHierarchy::getInstance()
				->getDeletedList($total, $limit, $pageNumber, $searchName, $domainId, $languageId);

			$this->setDataType('list');
			$this->setActionType('view');
			$this->setDataRange($limit, $pageNumber * $limit);
			$data = $this->prepareData($pageIdList, 'pages');
			$this->setData($data, $total);
			$this->doData();
		}

		/**
		 * Восстанавливает страницы из корзины
		 * @throws expectElementException
		 */
		public function trash_restore() {
			$redirect = true;
			$elements = $this->expectElementId('param0');

			if (!$elements) {
				$elements = getRequest('element');
				$redirect = false;
			}

			if (!is_array($elements)) {
				$elements = [$elements];
			}

			$hierarchy = umiHierarchy::getInstance();

			foreach ($elements as $element_id) {
				$hierarchy->restoreElement($element_id);
			}

			if ($redirect) {
				$this->chooseRedirect($this->module->pre_lang . '/admin/data/trash/');
			} else {
				$this->setData([]);
				$this->doData();
			}
		}

		/**
		 * Окончательно удаляет выбранные страницы
		 * @throws expectElementException
		 */
		public function trash_del() {
			$redirect = true;
			$elements = $this->expectElementId('param0');

			if (!$elements) {
				$elements = getRequest('element');
				$redirect = false;
			}

			if (!is_array($elements)) {
				$elements = [$elements];
			}

			$hierarchy = umiHierarchy::getInstance();

			foreach ($elements as $element_id) {
				$hierarchy->removeDeletedElement($element_id);
			}

			if ($redirect) {
				$this->chooseRedirect($this->module->pre_lang . '/admin/data/trash/');
			} else {
				$this->setData([]);
				$this->doData();
			}
		}

		/**
		 * Окончательно удаляет 100 страниц.
		 * Используется для итерационной очистки корзины
		 * @throws coreException
		 */
		public function trash_empty() {
			$limit = 100;
			$c = umiHierarchy::getInstance()->removeDeletedWithLimit($limit);

			$buffer = Service::Response()
				->getCurrentBuffer();
			$buffer->charset('utf-8');
			$buffer->contentType('text/xml');

			$data = [
					'attribute:complete' => (int) ($c < $limit),
					'attribute:deleted' => $c
			];

			$this->setData($data);
			$this->doData();
		}

		/**
		 * Возвращает идентификатор поля, которое соответствует заданным параметрам,
		 * и идентификатор типа данных, к которому прикреплено поле.
		 * @param array $typeIdList список типов данных
		 * @param string $fieldName строковой идентификатор поля
		 * @param string $fieldTitle название поля
		 * @param string $fieldDataTypeId идентификатор типа поля
		 * @return array
		 *
		 * [
		 *     "field_id" => iUmiField->getId(),
		 *     "type_id" => iUmiTypeId->getId(),
		 * ]
		 */
		protected function getSameFieldIdAndTypeId(array $typeIdList, $fieldName, $fieldTitle, $fieldDataTypeId) {
			$umiObjectTypes = umiObjectTypesCollection::getInstance();
			$umiFields = umiFieldsCollection::getInstance();

			foreach ($typeIdList as $typeId) {
				$type = $umiObjectTypes->getType($typeId);

				if (!$type instanceof iUmiObjectType) {
					continue;
				}

				$fieldId = $type->getFieldId($fieldName);
				$field = $umiFields->getById($fieldId);

				if (!$field instanceof iUmiField) {
					$umiObjectTypes->unloadType($typeId);
					continue;
				}

				if ($field->getTitle() != $fieldTitle ||  $field->getFieldTypeId() != $fieldDataTypeId) {
					$umiObjectTypes->unloadType($typeId);
					continue;
				}

				$umiObjectTypes->unloadType($typeId);
				return [
					'field_id' => $fieldId,
					'type_id' => $typeId
				];
			}

			return [];
		}
	}
