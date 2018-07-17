<?php

	use UmiCms\Service;

	/** Модуль системных уведомлений */
	class umiNotifications extends def_module {

		/** Конструктор */
		public function __construct() {
			parent::__construct();

			if (Service::Request()->isAdmin()) {
				$this->initTabs();
				$this->includeAdminClasses();
			} else {
				$this->includeGuestClasses();
			}

			$this->includeCommonClasses();
		}

		/**
		 * Возвращает ссылку на редактирование уведомления
		 * @param int $id идентификатор уведомления
		 * @return array
		 */
		public function getEditLink($id) {
			$prefix = $this->pre_lang;
			$addLink = false;
			$editLink = $prefix . "/admin/umiNotifications/edit/{$id}/";

			return [$addLink, $editLink];
		}

		/** Создает вкладки административной панели модуля */
		protected function initTabs() {
			$commonTabs = $this->getCommonTabs();

			if ($commonTabs instanceof iAdminModuleTabs) {
				$commonTabs->add('notifications');
			}
		}

		/** Подключает классы функционала административной панели */
		protected function includeAdminClasses() {
			$this->__loadLib('admin.php');
			$this->__implement('UmiNotificationsAdmin');

			$this->loadAdminExtension();

			$this->__loadLib('customAdmin.php');
			$this->__implement('umiNotificationsCustomAdmin', true);
		}

		/** Подключает классы функционала клиентской части */
		protected function includeGuestClasses() {
			$this->__loadLib('macros.php');
			$this->__implement('umiNotificationsMacros');

			$this->loadSiteExtension();

			$this->__loadLib('customMacros.php');
			$this->__implement('umiNotificationsCustomMacros', true);
		}

		/** Подключает общие классы функционала */
		protected function includeCommonClasses() {
			$this->loadCommonExtension();
			$this->loadTemplateCustoms();
		}
	}
