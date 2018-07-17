<?php

	use UmiCms\Service;

	/**
	 * Основной класс модуля "Новости".
	 * Модуль отвечает за работу:
	 *
	 * 1) Новостей;
	 * 2) Лент новостей;
	 * 3) Сюжетов новостей;
	 * 4) Импорт лент новостей в формате RSS и ATOM;
	 * @link http://help.docs.umi-cms.ru/rabota_s_modulyami/modul_novosti/
	 */
	class news extends def_module {

		/** @const string имя класса административного функционала */
		const ADMIN_CLASS = 'NewsAdmin';

		/** @var mixed количество новостей и лент новостей, выводимых на странице по умолчанию */
		public $per_page;

		/** Конструктор */
		public function __construct() {
			parent::__construct();

			$this->per_page = Service::Registry()
				->get('//modules/news/per_page');

			if (Service::Request()->isAdmin()) {

				$commonTabs = $this->getCommonTabs();

				if ($commonTabs instanceof iAdminModuleTabs) {
					$commonTabs->add('lists');
					$commonTabs->add('subjects');
					$commonTabs->add('rss_list');
				}

				$configTabs = $this->getConfigTabs();

				if ($configTabs instanceof iAdminModuleTabs) {
					$configTabs->add('config');
				}

				$this->__loadLib('admin.php');
				$this->__implement('NewsAdmin');

				$this->loadAdminExtension();

				$this->__loadLib('customAdmin.php');
				$this->__implement('NewsCustomAdmin', true);

			}

			$this->__loadLib('macros.php');
			$this->__implement('NewsMacros');

			$this->loadSiteExtension();

			$this->__loadLib('customMacros.php');
			$this->__implement('NewsCustomMacros', true);

			$this->__loadLib('feeds.php');
			$this->__implement('NewsFeeds');

			$this->__loadLib('calendar.php');
			$this->__implement('Calendar');

			$this->__loadLib('handlers.php');
			$this->__implement('NewsHandlers');

			$this->loadCommonExtension();
			$this->loadTemplateCustoms();
		}

		/**
		 * Возвращает ссылки на редактирование и создание элемента модуля
		 * @param int $element_id ID редактируемого элемента
		 * @param string $element_type метод типа элемента
		 * @return array|bool
		 */
		public function getEditLink($element_id, $element_type) {
			switch ($element_type) {
				case 'rubric': {
					$link_add = $this->pre_lang . "/admin/news/add/{$element_id}/item/";
					$link_edit =  $this->pre_lang . "/admin/news/edit/{$element_id}/";
					return [$link_add, $link_edit];
					break;
				}
				case 'item': {
					$link_edit = $this->pre_lang . "/admin/news/edit/{$element_id}/";
					return [false, $link_edit];
					break;
				}
				default: {
					return false;
				}
			}
		}
	}
