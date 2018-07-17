<?php
	namespace UmiCms\Classes\System\Entities\File;
	/**
	 * Фабрика файлов
	 * @package UmiCms\Classes\System\Entities\File
	 */
	class Factory implements iFactory {

		/** @inheritdoc */
		public function create($path) {
			$file = $this->createSecure($path);
			$file->setIgnoreSecurity();
			return $file->refresh();
		}

		/** @inheritdoc */
		public function createSecure($path) {
			return new \umiFile($path);
		}
	}