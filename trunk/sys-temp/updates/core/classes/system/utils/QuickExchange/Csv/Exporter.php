<?php
	namespace UmiCms\Classes\System\Utils\QuickExchange\Csv;

	use UmiCms\Classes\System\Utils\QuickExchange\Source\iDetector as SourceDetector;
	use UmiCms\System\Request\iFacade as iRequest;

	/**
	 * Класс экспортера
	 * @package UmiCms\Classes\System\Utils\QuickExchange\Csv
	 */
	class Exporter implements iExporter {

		/** @var SourceDetector $sourceDetector определитель источника */
		private $sourceDetector;

		/** @var iRequest $request фасад запроса */
		private $request;

		/** @inheritdoc */
		public function __construct(SourceDetector $sourceDetector, iRequest $request) {
			$this->sourceDetector = $sourceDetector;
			$this->request = $request;
		}

		/** @inheritdoc */
		public function export(\selector $query, $encoding) {
			$exporter = $this->createExporter($query, $encoding);
			$entityList = $exporter->isStarted() ? [] : $query->result();
			$exporter->export($entityList, []);
			return $exporter->getIsCompleted();
		}

		/**
		 * Создает экспортер данных
		 * @param \selector $query выборка сущностей, которые будут экспортированы
		 * @param string $encoding кодировка csv файла (windows-1251 / utf-8)
		 * @return \QuickCsvPageExporter|\QuickCsvObjectExporter
		 */
		private function createExporter(\selector $query, $encoding) {
			/** @todo: произвести рефакторинг umiExporter, передать фабрику в зависимость этому классу */
			$exporter = \umiExporter::get($this->getExporterPrefix($query));
			$sourceName = $this->getSourceDetector()
				->detectForExport();
			/** @var \QuickCsvPageExporter|\QuickCsvObjectExporter $exporter */
			$exporter->setSourceName($sourceName);
			$exporter->setFileName($sourceName);
			$exporter->setFieldNameWhiteList($this->getUsedFieldList());
			$exporter->setEncoding($encoding);
			return $exporter;
		}

		/**
		 * Возвращает префикс класса экспортера
		 * @param \selector $query выборка сущностей, которые будут экспортированы
		 * @return string
		 */
		private function getExporterPrefix(\selector $query) {
			return ($query->__get('mode') == 'pages') ? 'QuickCsvPage' : 'QuickCsvObject';
		}

		/**
		 * Возвращает список используемых полей
		 * @return array
		 */
		private function getUsedFieldList() {
			return (array) $this->getRequest()
				->Get()
				->get('used-fields');
		}

		/**
		 * Возвращает определителя источника
		 * @return SourceDetector
		 */
		private function getSourceDetector() {
			return $this->sourceDetector;
		}

		/**
		 * Возвращает фасад запроса
		 * @return iRequest
		 */
		private function getRequest() {
			return $this->request;
		}
	}
