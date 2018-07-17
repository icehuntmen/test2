<?php
	namespace UmiCms\Classes\System\Entities\File;
	/**
	 * Интерфейс фабрики файлов
	 * @package UmiCms\Classes\System\Entities\File
	 */
	interface iFactory {

		/**
		 * Создает файл
		 * @param string $path путь до файла
		 * @return \iUmiFile
		 */
		public function create($path);

		/**
		 * Создает безопасный (не php) файл
		 * @param string $path путь до файла
		 * @return \iUmiFile
		 */
		public function createSecure($path);
	}