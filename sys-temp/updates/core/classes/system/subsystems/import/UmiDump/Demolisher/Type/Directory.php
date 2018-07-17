<?php
	namespace UmiCms\System\Import\UmiDump\Demolisher\Type;
	use UmiCms\Classes\System\Entities\Directory\iFactory as DirectoryFactory;
	use UmiCms\System\Import\UmiDump\Demolisher\FileSystem;

	/**
	 * Класс удаления директорий
	 * @package UmiCms\System\Import\UmiDump\Demolisher\Part
	 */
	class Directory extends FileSystem {

		/** @var DirectoryFactory $directoryFactory фабрика директорий */
		private $directoryFactory;

		/**
		 * Конструктор
		 * @param DirectoryFactory $directoryFactory фабрика директорий
		 */
		public function __construct(DirectoryFactory $directoryFactory) {
			$this->directoryFactory = $directoryFactory;
		}

		/** @inheritdoc */
		protected function execute() {
			$directoryFactory = $this->getDirectoryFactory();

			foreach ($this->getDirectoryPathList() as $path) {
				$path = $this->buildAbsolutePath($path);
				$directory = $directoryFactory->create($path);

				if (!$directory->isExists()) {
					$this->pushLog(sprintf('Directory "%s" not exists', $path));
					continue;
				}

				$directory->delete();
				$this->pushLog(sprintf('Directory "%s" was deleted', $path));
			}
		}

		/**
		 * Возвращает список относительных путей удаляемых директорий
		 * @return string[]
		 */
		private function getDirectoryPathList() {
			return $this->getNodeValueList('/umidump/directories/directory');
		}

		/**
		 * Возвращает фабрику директорий
		 * @return DirectoryFactory
		 */
		private function getDirectoryFactory() {
			return $this->directoryFactory;
		}
	}
