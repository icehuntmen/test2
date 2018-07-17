<?php
	namespace UmiCms\System\Import\UmiDump\Demolisher\Type;
	use UmiCms\Classes\System\Entities\File\iFactory as FileFactory;
	use UmiCms\System\Import\UmiDump\Demolisher\FileSystem;

	/**
	 * Класс удаления файлов
	 * @package UmiCms\System\Import\UmiDump\Demolisher\Part
	 */
	class File extends FileSystem {

		/** @var FileFactory $fileFactory фабрика файлов */
		private $fileFactory;

		/**
		 * Конструктор
		 * @param FileFactory $fileFactory фабрика файлов
		 */
		public function __construct(FileFactory $fileFactory) {
			$this->fileFactory = $fileFactory;
		}

		/** @inheritdoc */
		protected function execute() {
			$fileFactory = $this->getFileFactory();

			foreach ($this->getFilePathList() as $path) {
				$path = $this->buildAbsolutePath($path);
				$file = $fileFactory->create($path);

				if (!$file->isExists()) {
					$this->pushLog(sprintf('File "%s" not exists', $path));
					continue;
				}

				$file->delete();
				$this->pushLog(sprintf('File "%s" was deleted', $path));
			}
		}

		/**
		 * Возвращает список относительных путей удаляемых файлов
		 * @return string[]
		 */
		private function getFilePathList() {
			return $this->getNodeValueList('/umidump/files/file');
		}

		/**
		 * Возвращает фабрику файлов
		 * @return FileFactory
		 */
		private function getFileFactory() {
			return $this->fileFactory;
		}
	}
