<?php

	namespace UmiCms\Classes\Components\AutoUpdate;

	use UmiCms\System\Registry\iPart;

	/**
	 * Интерфейс реестра модуля "Автообновления"
	 * @package UmiCms\Classes\Components\AutoUpdate
	 */
	interface iRegistry extends iPart {

		/**
		 * Возвращает версию системы
		 * @return string
		 */
		public function getVersion();

		/**
		 * Возвращает ревизию системы
		 * @return string
		 */
		public function getRevision();

		/**
		 * Устанавливает ревизию
		 * @param string $revision ревизия
		 * @return $this
		 */
		public function setRevision($revision);

		/**
		 * Возвращает редакцию системы
		 * @return string
		 */
		public function getEdition();

		/**
		 * Возвращает timestamp последнего обнвовления
		 * @return int
		 */
		public function getUpdateTime();

		/**
		 * Возвращает статус автоматического обновления
		 * @return string
		 */
		public function getStatus();
	}