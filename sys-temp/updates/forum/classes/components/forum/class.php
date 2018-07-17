<?php

	use UmiCms\Service;

	/**
	 * Базовый класс модуля "Форум"
	 *
	 * Модуль управляет следующими сущностями:
	 *
	 * 1) Конференции;
	 * 2) Топики;
	 * 3) Сообщения;
	 * @link http://help.docs.umi-cms.ru/rabota_s_modulyami/modul_forum/
	 */
	class forum extends def_module {
		/** @var int $per_page ограничение на количество выводимых страниц */
		public $per_page = 10;

		/** Конструктор */
		public function __construct() {
			parent::__construct();

			$per_page = (int) Service::Registry()
				->get('//modules/forum/per_page');

			if ($per_page) {
				$this->per_page = $per_page;
			}

			if (Service::Request()->isAdmin()) {
				$commonTabs = $this->getCommonTabs();

				if ($commonTabs) {
					$commonTabs->add('lists', ['confs_list']);
					$commonTabs->add('last_messages');
				}

				$this->__loadLib('admin.php');
				$this->__implement('ForumAdmin');

				$this->loadAdminExtension();

				$this->__loadLib('customAdmin.php');
				$this->__implement('ForumCustomAdmin', true);
			}

			$this->__loadLib('macros.php');
			$this->__implement('ForumMacros');

			$this->loadSiteExtension();

			$this->__loadLib('customMacros.php');
			$this->__implement('ForumCustomMacros', true);

			$this->__loadLib('handlers.php');
			$this->__implement('ForumHandlers');

			$this->loadCommonExtension();
			$this->loadTemplateCustoms();
		}

		/**
		 * Является ли страница топиком или сообщением форума
		 * @param iUmiHierarchyElement $page
		 * @return bool
		 */
		public function isTopicOrMessage(iUmiHierarchyElement $page) {
			$umiHierarchyTypesCollection = umiHierarchyTypesCollection::getInstance();

			$allowedTypeIds = [];
			$allowedTypeIds[] = $umiHierarchyTypesCollection->getTypeByName('forum', 'topic')->getId();
			$allowedTypeIds[] = $umiHierarchyTypesCollection->getTypeByName('forum', 'message')->getId();

			return in_array($page->getTypeId(), $allowedTypeIds);
		}

		/**
		 * Актуализует счетчики конференций и топиков форума.
		 * @param iUmiHierarchyElement $element конференция или топик форума
		 */
		public function recalcCounts(iUmiHierarchyElement $element) {
			/** @var iUmiHierarchyElement $element */
			if ($element->getMethod() == 'topic') {
				$element->messages_count = $this->calculateCount($element, 'message');
				$element->last_message = $this->calculateLastMessageId($element);
				$element->commit();
			}

			$element = selector::get('page')->id($element->getParentId());

			if (!$element instanceof iUmiHierarchyElement) {
				return;
			}

			switch ($element->getMethod()) {
				case 'conf': {
					$element->messages_count = $this->calculateCount($element, 'message');
					$element->topics_count = $this->calculateCount($element, 'topic');
					$element->last_message = $this->calculateLastMessageId($element);
					$element->commit();
					break;
				}
				case 'topic': {
					$element->messages_count = $this->calculateCount($element, 'message');
					$element->last_message = $this->calculateLastMessageId($element);
					$element->commit();
					$this->recalcCounts($element);
					break;
				}
			}
		}

		/**
		 * Возвращает количество страниц форума, дочерних заданной странице
		 * @param iUmiHierarchyElement $element родительская страница
		 * @param string $typeName тип искомых страниц
		 * @return int
		 * @throws selectorException
		 */
		public function calculateCount(iUmiHierarchyElement $element, $typeName) {
			$level = ($typeName == 'message' && $element->getMethod() == 'conf') ? 2 : 1;
			/** @var iUmiHierarchyElement $element */
			$sel = new selector('pages');
			$sel->types('object-type')->name('forum', $typeName);
			$sel->where('hierarchy')->page($element->getId())->childs($level);
			$sel->where('is_active')->equals(1);
			$sel->option('return')->value('count');
			return $sel->result();
		}

		/**
		 * Вычисляет и возвращает последнее сообщение из топика или конференции
		 * @param iUmiHierarchyElement $element топик или конференция
		 * @return iUmiHierarchyElement|null
		 * @throws selectorException
		 */
		public function calculateLastMessageId(iUmiHierarchyElement $element) {
			/** @var iUmiHierarchyElement $element */
			$sel = new selector('pages');
			$sel->types('object-type')->name('forum', 'message');

			if (is_numeric($sel->searchField('publish_time'))) {
				$sel->order('publish_time')->desc();
			}

			$sel->limit(0, 1);

			if ($element->getMethod() == 'conf') {
				$lastTopics = new selector('pages');
				$lastTopics->types('object-type')->name('forum', 'topic');
				$lastTopics->where('hierarchy')->page($element->getId())->level(1);
				$lastTopics->order('last_post_time')->desc();
				$lastTopics->limit(0, 1);

				if ($lastTopics->first) {
					$sel->where('hierarchy')->page($lastTopics->first->id)->level(1);
				} else {
					return null;
				}
			} else {
				$sel->where('hierarchy')->page($element->getId())->level(1);
			}

			return $sel->first();
		}

		/**
		 * Возвращает идентификатор последнего сообщения
		 * из топика или конференции, либо запускает
		 * его вычисление
		 * @param int $elementId идентификатор конференции или топика
		 * @return bool|int
		 */
		public function getLastMessageId($elementId) {
			/** @var iUmiHierarchyElement $element */
			$element = selector::get('page')->id($elementId);

			if (!$element instanceof iUmiHierarchyElement) {
				return false;
			}

			/** @var iUmiHierarchyElement[] $lastMessageList */
			$lastMessageList = $element->getValue('last_message');

			if (count($lastMessageList) > 0) {
				$lastMessage = array_shift($lastMessageList);
				return ($lastMessage instanceof iUmiHierarchyElement) ? $lastMessage->getId() : false;
			}

			$lastMessage = $this->calculateLastMessageId($element);

			if (!$lastMessage instanceof iUmiHierarchyElement) {
				return false;
			}

			$element->setValue('last_message', $lastMessage);
			$element->commit();
			return $lastMessage->getId();
		}

		/**
		 * Возвращает ссылки на форму редактирования страницы модуля и
		 * на форму добавления дочернего элемента к странице.
		 * @param int $element_id идентификатор страницы модуля
		 * @param string $element_type тип страницы модуля
		 * @return array
		 */
		public function getEditLink($element_id, $element_type) {
			$prefix = $this->pre_lang;
			$link_edit = $prefix . '/admin/forum/edit/' . $element_id . '/';
			switch ($element_type) {
				case 'conf': {
					$link_add = $prefix . '/admin/forum/add/' . $element_id . '/topic/';
					return [$link_add, $link_edit];
				}
				case 'topic': {
					$link_add = $prefix . '/admin/forum/add/' . $element_id . '/message/';
					return [$link_add, $link_edit];
				}
				case 'message': {
					$link_add = false;
					return [$link_add, $link_edit];
				}
				default: {
					return [false, false];
				}
			}
		}

		/** @inheritdoc */
		public function getVariableNamesForMailTemplates() {
			return [
				'forum-new-message-subject' => [],
				'forum-new-message-content' => [
					'h1' => getLabel('mail-template-variable-h1', 'forum'),
					'message' => getLabel('mail-template-variable-message', 'forum'),
					'unsubscribe_link' => getLabel('mail-template-variable-unsubscribe_link', 'forum'),
				],
			];
		}
	}
