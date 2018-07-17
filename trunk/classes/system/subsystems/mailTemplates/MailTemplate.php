<?php

	use UmiCms\Service;

	/** Шаблон письма */
	class MailTemplate implements
		iUmiCollectionItem,
		iUmiDataBaseInjector,
		iUmiConstantMapInjector,
		iClassConfigManager
	{
		use tUmiDataBaseInjector;
		use tCommonCollectionItem;
		use tUmiConstantMapInjector;
		use tClassConfigManager;

		/** @const string символ, обрамляющий идентификаторы (placeholders) полей в шаблоне */
		const FIELD_WRAPPER_SYMBOL = '%';

		/** @var int ID уведомления, которому принадлежит шаблон */
		private $notificationId;

		/** @var string имя шаблона */
		private $name;

		/** @var string тип шаблона */
		private $type;

		/** @var string содержимое шаблона */
		private $content;

		/** @var array конфигурация класса */
		private static $classConfig = [
			'fields' => [
				[
					'name' => 'ID_FIELD_NAME',
					'required' => true,
					'unchangeable' => true,
					'setter' => 'setId',
					'getter' => 'getId'
				],
				[
					'name' => 'NOTIFICATION_ID_FIELD_NAME',
					'required' => true,
					'setter' => 'setNotificationId',
					'getter' => 'getNotificationId'
				],
				[
					'name' => 'NAME_FIELD_NAME',
					'required' => true,
					'setter' => 'setName',
					'getter' => 'getName'
				],
				[
					'name' => 'TYPE_FIELD_NAME',
					'required' => true,
					'setter' => 'setType',
					'getter' => 'getType'
				],
				[
					'name' => 'CONTENT_FIELD_NAME',
					'required' => true,
					'setter' => 'setContent',
					'getter' => 'getContent'
				]
			]
		];

		/**
		 * ID уведомления, которому принадлежит шаблон
		 * @return int
		 */
		public function getNotificationId() {
			return $this->notificationId;
		}

		/**
		 * Устанавливает ID уведомления, которому принадлежит шаблон
		 * @param int $id новый ID уведомления
		 */
		public function setNotificationId($id) {
			$this->setDifferentValue('notificationId', $id, 'int');
		}

		/**
		 * Имя шаблона
		 * @return string
		 */
		public function getName() {
			return $this->name;
		}

		/**
		 * Устанавливает имя шаблона
		 * @param string $name новое имя шаблона
		 */
		public function setName($name) {
			$this->setDifferentValue('name', $name, 'string');
		}

		/**
		 * Тип шаблона
		 * @return string
		 */
		public function getType() {
			return $this->type;
		}

		/**
		 * Устанавливает имя шаблона
		 * @param string $type новое имя шаблона
		 */
		public function setType($type) {
			$this->setDifferentValue('type', $type, 'string');
		}

		/**
		 * Содержимое шаблона
		 * @return string
		 */
		public function getContent() {
			return $this->content;
		}

		/**
		 * Устанавливает содержимое шаблона
		 * @param string $content новое содержимое шаблона
		 */
		public function setContent($content) {
			$this->setDifferentValue('content', $content, 'string');
		}

		/**
		 * Модуль шаблона
		 * @return mixed
		 */
		public function getModule() {
			return $this->getNotification()->getModule();
		}

		/**
		 * Уведомление шаблона
		 * @return mixed
		 */
		public function getNotification() {
			$mailNotifications = Service::MailNotifications();
			$map = $mailNotifications->getMap();
			$result = $mailNotifications->get([
				$map->get('ID_FIELD_NAME') => $this->getNotificationId()
			]);

			if (umiCount($result) > 0) {
				return array_shift($result);
			}
		}

		/** @inheritdoc */
		public function commit() {
			if (!$this->isUpdated()) {
				return false;
			}

			$map = $this->getMap();
			$connection = $this->getConnection();
			$tableName = $connection->escape($map->get('TABLE_NAME'));

			$idField = $connection->escape($map->get('ID_FIELD_NAME'));
			$notificationIdField = $connection->escape($map->get('NOTIFICATION_ID_FIELD_NAME'));
			$nameField = $connection->escape($map->get('NAME_FIELD_NAME'));
			$typeField = $connection->escape($map->get('TYPE_FIELD_NAME'));
			$contentField = $connection->escape($map->get('CONTENT_FIELD_NAME'));

			$id = $this->getId();
			$notificationId = $connection->escape($this->getNotificationId());
			$type = $connection->escape($this->getType());
			$name = $connection->escape($this->getName());
			$content = $connection->escape($this->getContent());

			$sql = <<<SQL
UPDATE `$tableName`
	SET `$notificationIdField` = '$notificationId', `$nameField` = '$name', 
		`$typeField` = '$type', `$contentField` = '$content'
		WHERE `$idField` = $id;
SQL;
			$connection->query($sql);

			return true;
		}

		/**
		 * Возвращает обработанное содержимое шаблона, такое, что в нем (содержимом)
		 * заменены вставки идентификаторов полей на конкретные значения.
		 * В шаблоне могут содержаться вложенные шаблоны.
		 *
		 * @param array $params массив идентификаторов полей и их значений
		 *
		 * Вид массива: [
		 *   // переменная для основного шаблона
		 *   'status' => 'test status',
		 *
		 *   // переменные могут быть обрамлены знаком процента
		 *   '%order_number%' => 1,
		 *
		 *   // переменные для вложенных шаблонов расположены в массиве
		 *   '%items%' => [
		 *     0 => [
		 *       '%link%' => 'test link 1',
		 *       '%name%' => 'test name 1',
		 *     ],
		 *     1 => [
		 *       '%link%' => 'test link 2',
		 *       '%name%' => 'test name 2',
		 *     ]
		 *   ]
		 * ]
		 *
		 * @return mixed
		 */
		public function getProcessedContent(array $params = []) {
			$params = $this->stripSpecialCharacters($params);

			$topLevelVariables = array_filter($params, function ($param) {
				return !is_array($param);
			});
			$recursiveVariables = array_filter($params, function ($param) {
				return is_array($param);
			});
			$content = $this->parseTopLevelContent($topLevelVariables);
			return $this->parseRecursiveContent($content, $recursiveVariables);
		}

		/**
		 * Рекурсивно вырезать все специальные символы из идентификаторов полей.
		 * Пример:
		 *   %price% => price
		 *   +items => items
		 *
		 * @param mixed $params массив идентификаторов полей и их значений или отдельное значение
		 * @return array
		 */
		protected function stripSpecialCharacters($params) {
			if (!is_array($params)) {
				return $params;
			}

			$fields = array_keys($params);
			$fieldRegex = '/^\W*(\w+)\W*$/';

			foreach ($fields as $field) {
				$value = $params[$field];
				unset($params[$field]);
				$field = preg_replace($fieldRegex, '$1', $field);
				$params[$field] = $this->stripSpecialCharacters($value);
			}

			return $params;
		}

		/**
		 * Возвращает обработанное содержимое шаблона, такое, что в нем (содержимом)
		 * заменены вставки идентификаторов полей на конкретные значения.
		 * @param array $params массив идентификаторов полей и их значений
		 * @return mixed
		 */
		protected function parseTopLevelContent(array $params) {
			$fields = array_keys($params);
			$values = array_values($params);

			$wrappedFields = array_map(function ($value) {
				return self::FIELD_WRAPPER_SYMBOL . $value . self::FIELD_WRAPPER_SYMBOL;
			}, $fields);

			return str_replace($wrappedFields, $values, $this->getContent());
		}

		/**
		 * Возвращает результат обработки всех вложенных шаблонов.
		 *
		 * Пример вызова вложенного шаблона:
		 *  <p>Товары:</p>
		 *  %parse.emarket-status-notification-item.items%
		 *
		 * Где `parse` - ключевое слово для вызова
		 *     `emarket-status-notification-item` - название вложенного шаблона
		 *     `items` - название поля с массивом переменных для шаблона
		 *
		 * @param string $content содержимое основного шаблона
		 * @param array $params массив идентификаторов полей и их значений
		 * @return mixed
		 */
		protected function parseRecursiveContent($content, array $params) {
			$mailTemplates = Service::MailTemplates();
			$parseRegex = '/%parse\.([^%]+)%/';

			while (preg_match($parseRegex, $content, $matches, PREG_OFFSET_CAPTURE)) {
				$start = $matches[0][1];
				$length = mb_strlen($matches[0][0]);
				$string = $matches[1][0];
				list($templateName, $param) = explode('.', $string);

				$template = $mailTemplates->getByName($templateName);
				$replacement = $this->parseTemplateList($template, $params[$param]);
				$content = substr_replace($content, $replacement, $start, $length);
			}

			return $content;
		}

		/**
		 * Возвращает обработанное содержимое шаблона для каждой комбинации переменных и их значений.
		 * Используется для массового парсинга вложенного шаблона.
		 *
		 * @param MailTemplate|null $template шаблон
		 * @param array $paramsCollection массив, каждый элемент которого -
		 *  массив переменных и их значений для обработки шаблона
		 *
		 * @return string конкатенация обработки шаблона для всех переменных
		 */
		protected function parseTemplateList($template, array $paramsCollection) {
			if (!$template instanceof MailTemplate) {
				return '';
			}

			$content = [];

			foreach ($paramsCollection as $params) {
				$content[] = $template->getProcessedContent($params);
			}

			return implode("\n", $content);
		}

	}
