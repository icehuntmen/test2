<?php
	namespace UmiCms\System\Import\UmiDump\Demolisher;
	use UmiCms\System\Import\UmiDump\Demolisher;

	/**
	 * Абстрактный класс удаления элементов файловой системы.
	 * @package UmiCms\System\Import\UmiDump\Demolisher
	 */
	abstract class FileSystem extends Demolisher implements iFileSystem {

		/** @var string $rootPath абсолютный путь до корневой директории системы */
		private $rootPath;

		/** @inheritdoc */
		public function setRootPath($path) {
			$this->rootPath = $path;
			return $this;
		}

		/**
		 * Возвращает абсолютный путь до корневой директории системы
		 * @return string
		 */
		protected function getRootPath() {
			return rtrim($this->rootPath ?: CURRENT_WORKING_DIR, '/');
		}

		/**
		 * Формирует абсолютный путь до файла
		 * @param string $localPath путь до файла, относительно корня
		 * @return string
		 */
		protected function buildAbsolutePath($localPath) {
			return $this->getRootPath() . '/' . ltrim($localPath, '/');
		}
	}
