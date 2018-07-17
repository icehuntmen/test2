<?php

	namespace UmiCms\System\Registry;

	/**
	 * Интерфейс реестра общих настроек системы
	 * @package UmiCms\System\Registry
	 */
	interface iSettings extends iPart {

		/**
		 * Возвращает доменный лицензионный ключ
		 * @return string
		 */
		public function getLicense();
	}