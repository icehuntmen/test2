<?php

	use UmiCms\Service;

	/** Класс для управления резервными копиями страниц */
	class backupModel extends singleton implements iBackupModel {

		protected function __construct() {}

		/** @inheritdoc */
		public static function getInstance($c = null) {
			return parent::getInstance(__CLASS__);
		}

		/** @inheritdoc */
		public function getChanges($pageId = false) {
			$regedit = Service::Registry();

			if (!$regedit->get('modules/backup/enabled')) {
				return false;
			}

			$limit = (int) $regedit->get('//modules/backup/max_save_actions');
			$time_limit = (int) $regedit->get('//modules/backup/max_timelimit');
			$end_time = $time_limit * 3600 * 24;
			$connection = ConnectionPool::getInstance()->getConnection();

			$pageId = (int) $pageId;

			$limit = ($limit > 2) ? $limit : 2;

			$sql = "SELECT id, ctime, changed_module, user_id, is_active FROM cms_backup WHERE param='" . $pageId . "' AND (" . time() . '-ctime)<' . $end_time . " ORDER BY ctime DESC LIMIT {$limit}";
			$result = $connection->queryResult($sql);

			if ($result->length() < 2) {
				$sql = "SELECT id, ctime, changed_module, user_id, is_active FROM cms_backup WHERE param='" . $pageId . "' ORDER BY ctime DESC LIMIT 2";
				$result = $connection->queryResult($sql);
			}

			$params = [];
			$rows = [];

			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			foreach ($result as $row) {
				$revision_info = $this->getChangeInfo(
					$row['id'],
					$row['ctime'],
					$row['changed_module'],
					$pageId,
					$row['user_id'],
					$row['is_active']
				);

				if (umiCount($revision_info)) {
					$rows[] = $revision_info;
				}
			}

			$params['nodes:revision'] = $rows;
			return $params;
		}

		/**
		 * @internal
		 * Возвращает список просроченных изменений модуля "Резервирование"
		 * @param int $daysToExpire Количество дней хранения событий
		 * @return array|void массив объектов класса backupChange
		 */
		public function getOverdueChanges($daysToExpire = 30) {
			if ($daysToExpire === 0) {
				return;
			}

			$secondsInDay = 24 * 3600;
			$maxSecondsLimit = $daysToExpire * $secondsInDay;
			$connection = ConnectionPool::getInstance()->getConnection();

			$overdueChangesQuery = <<<QUERY
				SELECT *
				FROM   `cms_backup` 
				WHERE  `ctime` < unix_timestamp() - ${maxSecondsLimit};
QUERY;
			$result = $connection->queryResult($overdueChangesQuery);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			$changes = [];

			foreach ($result as $row) {
				$changes[] = new backupChange(
					$row['id'],
					$row['ctime'],
					$row['changed_module'],
					$row['changed_method'],
					$row['param'],
					$row['param0'],
					$row['user_id'],
					$row['is_active']
				);
			}

			return $changes;
		}

		/** @inheritdoc */
		public function deleteChanges($changes = []) {
			if (!is_array($changes)) {
				return false;
			}

			$changesID = [];

			/** var backupChange $backupChange */
			foreach ($changes as $backupChange) {
				if ($backupChange instanceof backupChange) {
					$changesID[] = $backupChange->id;
				}
			}

			if (empty($changesID)) {
				return false;
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$changesIDForDeleting = implode($changesID, ', ');
			$deletingChangesQuery = <<<QUERY
				DELETE 
				FROM   `cms_backup` 
				WHERE  `id` IN (${changesIDForDeleting})
QUERY;
			$connection->query($deletingChangesQuery);

			if ($connection->errorOccurred()) {
				throw new coreException($connection->errorDescription($deletingChangesQuery));
			}

			return true;
		}

		/**
		 * Возвращает данные об изменениях в точке восстановления $revision_id
		 * @param int $revision_id - id Точки восстановления
		 * @param int Timestamp $ctime - Время создания точки восстановления
		 * @param string $changed_module - Название модуля к которому относится точка восстановления
		 * @param int $cparam - Id страницы, к которой относится точка восстановления
		 * @param int $user_id - Id пользователя, создавшего точку восстановления
		 * @param int $is_active - активность точки восстановления
		 * @return array - данные о точке восстановления, подготовленные для шаблонизатора
		 */
		protected function getChangeInfo($revision_id, $ctime, $changed_module, $cparam, $user_id, $is_active) {

			$hierarchy = umiHierarchy::getInstance();
			$cmsController = cmsController::getInstance();

			$revision_info = [];

			$element = $hierarchy->getElement($cparam);
			if ($element instanceof iUmiHierarchyElement) {

				$revision_info['attribute:changetime'] = $ctime;
				$revision_info['attribute:user-id'] = $user_id;
				if (mb_strlen($changed_module) == 0) {
					$revision_info['attribute:is-void'] = true;
				}
				if ($is_active) {
					$revision_info['attribute:active'] = 'active';
				}
				$revision_info['date'] = new umiDate($ctime);
				$revision_info['author'] = selector::get('object')->id($user_id);
				$revision_info['link'] = "/admin/backup/rollback/{$revision_id}/";

				$module_name = $element->getModule();
				$method_name = $element->getMethod();

				$module = $cmsController->getModule($module_name);
				if ($module instanceof def_module) {
					$links = $module->getEditLink($cparam, $method_name);
					if (isset($links[1])) {
						$revision_info['page'] = [];
						$revision_info['page']['attribute:name'] = $element->getName();
						$revision_info['page']['attribute:edit-link'] = $links[1];
						$revision_info['page']['attribute:link'] = $element->link;
					}
				}
			}

			return $revision_info;
		}

		/** @inheritdoc */
		public function getAllChanges() {
			if (!Service::Registry()->get('modules/backup/enabled')) {
				return false;
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = 'SELECT id, ctime, changed_module, param, user_id, is_active FROM cms_backup ORDER BY ctime DESC LIMIT 100';
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			$params = [];
			$rows = [];

			foreach ($result as $row) {
				$revision_info = $this->getChangeInfo(
					$row['id'],
					$row['ctime'],
					$row['changed_module'],
					$row['param'],
					$row['user_id'],
					$row['is_active']
				);

				if (umiCount($revision_info)) {
					$rows[] = $revision_info;
				}
			}

			$params['nodes:revision'] = $rows;
			return $params;
		}

		/** @inheritdoc */
		public function save($pageId = '', $currentModule = '', $currentMethod = '') {
			if (!Service::Registry()->get('//modules/backup/enabled')) {
				return false;
			}
			if (getRequest('rollbacked')) {
				return false;
			}

			$this->restoreIncrement();

			$cmsController = cmsController::getInstance();
			if (!$currentModule) {
				$currentModule = $cmsController->getCurrentModule();
			}
			$currentMethod = $cmsController->getCurrentMethod();

			$auth = Service::Auth();
			$cuser_id = $auth->getUserId();

			$ctime = time();

			if (!$currentModule) {
				$currentModule = getRequest('module');
			}
			if (!$currentMethod) {
				$currentMethod = getRequest('method');
			}

			foreach ($_REQUEST as $cn => $cv) {
				if ($cn == 'save-mode') {
					continue;
				}
				$_temp[$cn] = (!is_array($cv)) ? base64_encode($cv) : $cv;
			}

			if (isset($_temp['data']['new'])) {
				$element = umiHierarchy::getInstance()->getElement($pageId);
				if ($element instanceof iUmiHierarchyElement) {
					$_temp['data'][$element->getObjectId()] = $_temp['data']['new'];
					unset($_temp['data']['new']);
				}
			}

			$connection = ConnectionPool::getInstance()->getConnection();
			$req = serialize($_temp);
			$req = $connection->escape($req);

			$pageId = $connection->escape($pageId);
			$currentModule = $connection->escape($currentModule);
			$currentMethod = $connection->escape($currentMethod);

			$sql = "UPDATE cms_backup SET is_active='0' WHERE param='" . $pageId . "'";
			$connection->query($sql);

			$sql = <<<SQL
INSERT INTO cms_backup (ctime, changed_module, changed_method, param, param0, user_id, is_active)
				VALUES('{$ctime}', '{$currentModule}', '{$currentMethod}', '{$pageId}', '{$req}', '{$cuser_id}', '1')
SQL;
			$connection->query($sql);

			$limit = Service::Registry()->get('//modules/backup/max_save_actions');
			$sql = "SELECT COUNT(`id`) FROM cms_backup WHERE param='" . $pageId . "' ORDER BY ctime DESC";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);
			$total_b = 0;

			if ($result->length() > 0) {
				$fetchResult = $result->fetch();
				$total_b = array_shift($fetchResult);
			}

			$td = $total_b - $limit;

			if ($td < 0) {
				$td = 0;
			}

			$sql = "SELECT id FROM cms_backup WHERE param='" . $pageId . "' ORDER BY ctime DESC LIMIT 2";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ROW);

			$backupIds = [];

			foreach ($result as $row) {
				$backupIds[] = array_shift($row);
			}

			$notId = '';

			if (umiCount($backupIds)) {
				$notId = 'AND id NOT IN (' . implode(', ', $backupIds) . ')';
			}

			$sql = "DELETE FROM cms_backup WHERE param='" . $pageId . "' {$notId} ORDER BY ctime ASC LIMIT " . $td;
			$connection->query($sql);

			$time_limit = Service::Registry()->get('//modules/backup/max_timelimit');
			$end_time = $time_limit * 3600 * 24;
			$sql = "DELETE FROM cms_backup WHERE param='" . $pageId . "' AND (" . time() . '-ctime)>' . $end_time . " {$notId} ORDER BY ctime ASC";
			$connection->query($sql);

			return true;
		}

		/** @inheritdoc */
		public function rollback($revisionId) {
			if (!Service::Registry()->get('//modules/backup/enabled')) {
				return false;
			}

			$revisionId = (int) $revisionId;
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "SELECT param, param0, changed_module, changed_method FROM cms_backup WHERE id='$revisionId' LIMIT 1";
			$result = $connection->queryResult($sql);
			$result->setFetchType(IQueryResult::FETCH_ASSOC);

			foreach ($result as $row) {
				$element_id = $row['param'];
				$data = $row['param0'];
				$changed_module = $row['changed_module'];
				$changed_method = $row['changed_method'];

				$changed_param = $element_id;

				$sql = "UPDATE cms_backup SET is_active='0' WHERE param='" . $changed_param . "'";
				$connection->query($sql);

				$sql = "UPDATE cms_backup SET is_active='1' WHERE id='" . $revisionId . "'";
				$connection->query($sql);

				$_temp = unserialize($data);
				$_REQUEST = [];

				foreach ($_temp as $cn => $cv) {
					if (is_array($cv)) {
						foreach ($cv as $i => $v) {
							$cv[$i] = $v;
						}
					} else {
						$cv = base64_decode($cv);
					}

					$_REQUEST[$cn] = $cv;
					$_POST[$cn] = $cv;
				}

				$_REQUEST['rollbacked'] = true;
				$_REQUEST['save-mode'] = getLabel('label-save');

				if (!$changed_module_inst = cmsController::getInstance()->getModule($changed_module)) {
					throw new requreMoreAdminPermissionsException("You can't rollback this action. No permission to this module.");
				}

				$element = umiHierarchy::getInstance()->getElement($element_id);

				if ($element instanceof iUmiHierarchyElement) {
					$links = $changed_module_inst->getEditLink($element_id, $element->getMethod());
					if (umiCount($links) >= 2) {
						$edit_link = $links[1];
						$_REQUEST['referer'] = $edit_link;

						$edit_link = trim($edit_link, '/') . '/do';

						if (preg_match("/admin\/[A-z]+\/([^\/]+)\//", $edit_link, $out)) {
							if (isset($out[1])) {
								$changed_method = $out[1];
							}
						}
						$_REQUEST['path'] = $edit_link;
						$_REQUEST['param0'] = $element_id;
						$_REQUEST['param1'] = 'do';
					}
				}

				return $changed_module_inst->cms_callMethod($changed_method, []);
			}
		}

		/** @inheritdoc */
		public function addLogMessage($elementId) {
			if (!Service::Registry()->get('//modules/backup/enabled')) {
				return false;
			}

			$this->restoreIncrement();
			$auth = Service::Auth();
			$userId = $auth->getUserId();

			$time = time();
			$param = (int) $elementId;
			$connection = ConnectionPool::getInstance()->getConnection();
			$sql = "INSERT INTO cms_backup (ctime, param, user_id, param0) VALUES('{$time}', '{$param}', '{$userId}', '{$time}')";
			$connection->query($sql);

			return true;
		}

		/** @inheritdoc */
		public function fakeBackup($elementId) {
			$element = selector::get('page')->id($elementId);
			if (!($element instanceof iUmiEntinty)) {
				return false;
			}

			$originalRequest = $_REQUEST;

			$object = $element->getObject();
			$type = selector::get('object-type')->id($object->getTypeId());

			$_REQUEST['name'] = $element->name;
			$_REQUEST['alt-name'] = $element->altName;
			$_REQUEST['active'] = $element->isActive;
			foreach ($type->getAllFields() as $field) {
				$fieldName = $field->getName();
				$value = $this->fakeBackupValue($object, $field);
				if ($value === null) {
					continue;
				}
				$_REQUEST['data'][$object->id][$fieldName] = $value;
			}

			$this->save($elementId, $element->getModule());
			$_REQUEST = $originalRequest;
			return true;
		}

		/**
		 * Возвращает значение свойства объекта в том виде, в котором значения
		 * данного типа поля изначально передается в формах редактирования
		 * @param iUmiObject $object - Объект, свойство которого мы получаем
		 * @param iUmiField $field - Поле, значение которого мы хотим получить
		 * @return string Значение поля.
		 */
		protected function fakeBackupValue(iUmiObject $object, iUmiField $field) {
			$value = $object->getValue($field->getName());

			switch ($field->getDataType()) {
				case 'file':
				case 'img_file':
				case 'swf_file':
					return ($value instanceof iUmiFile) ? $value->getFilePath() : '';

				case 'boolean':
					return $value ? '1' : '0';

				case 'date':
					return ($value instanceof umiDate) ? $value->getFormattedDate('U') : null;

				case 'tags':
					return is_array($value) ? implode(', ', $value) : null;

				default:
					return (string) $value;
			}
		}

		/** Проверяет и при необходимости меняет значение автоинкремента в таблице cms_backup */
		protected function restoreIncrement() {
			$connection = ConnectionPool::getInstance()->getConnection();
			$result1 = $connection->queryResult('SELECT max( id ) FROM `cms_backup`');
			$result1->setFetchType(IQueryResult::FETCH_ROW);
			$row1 = $result1->fetch();
			$incrementToBe = $row1[0] + 1;

			$result = $connection->queryResult("SHOW TABLE STATUS LIKE 'cms_backup'");
			$result->setFetchType(IQueryResult::FETCH_ARRAY);
			$row = $result->fetch();
			$increment = isset($row['Auto_increment']) ? (int) $row['Auto_increment'] : false;

			if ($increment !== false && $increment != $incrementToBe) {
				$connection->query("ALTER TABLE `cms_backup` AUTO_INCREMENT={$incrementToBe}");
			}
		}
	}
