<?php
	namespace UmiCms\System\Import\UmiDump;
	/**
	 * Абстрактный класс удаления группы однородных данных.
	 * @package UmiCms\System\Import\UmiDump\Demolisher
	 */
	abstract class Demolisher implements iDemolisher {

		/** @var \DOMXPath $parser парсер документа в формате umiDump */
		private $parser;

		/** @var string[] $log журнал удаления */
		private $log = [];

		/** @inheritdoc */
		public function run(\DOMXPath $parser) {
			$this->setParser($parser);
			$this->execute();
			$log = $this->getLog();
			$this->clearLog();
			return $log;
		}

		/**
		 * Запускает обработку umiDump и удаление данных
		 */
		abstract protected function execute();

		/**
		 * Устанавливает парсер документа в формате umiDump
		 * @param \DOMXPath $parser парсер документа в формате umiDump
		 * @return iDemolisher
		 */
		protected function setParser(\DOMXPath $parser) {
			$this->parser = $parser;
			return $this;
		}

		/**
		 * Выполняет xpath запрос к документу в формате umiDump
		 * @param string $xpath xpath запрос
		 * @param \DOMElement|null $context контекст запроса
		 * @return \DOMNodeList
		 */
		protected function parse($xpath, $context = null) {
			return $this->getParser()
				->evaluate($xpath, $context);
		}

		/**
		 * Возвращает парсер документа в формате umiDump
		 * @return \DOMXPath
		 */
		protected function getParser() {
			return $this->parser;
		}

		/**
		 * Возвращает список значений узлов документа
		 * @param string $xpath xpath запрос списка узлов
		 * @param \DOMElement|null $context контекст запроса
		 * @return string[]
		 */
		protected function getNodeValueList($xpath, $context = null) {
			$result = [];

			/** @var \DOMAttr $attribute */
			foreach ($this->parse($xpath, $context) as $attribute) {
				$result[] = (string) $attribute->nodeValue;
			}

			return $result;
		}

		/**
		 * Возвращает список значений дочерних узлов, сгруппированный по id родителя
		 * @param array $result массив, в который требуется добавить результат
		 * @param string $parentXpath xpath запрос списка родительких элементов
		 * @param string $parentIdKey имя идентифицирующего атрибута родительского узла
		 * @param string $childrenXpath xpath запрос списка дочерних узлов
		 * @return array
		 *
		 * [
		 *      'parent id' => [
		 *          'child value'
		 *      ]
		 * ]
		 */
		protected function getNodeValueTree(array $result, $parentXpath, $parentIdKey, $childrenXpath) {
			/** @var \DOMElement $parent */
			foreach ($this->parse($parentXpath) as $parent) {
				$parentId = $parent->getAttribute($parentIdKey);

				if (!$parentId) {
					continue;
				}

				$childrenValueList = $this->getNodeValueList($childrenXpath, $parent);

				if (!isset($result[$parentId])) {
					$result[$parentId] = $childrenValueList;
					continue;
				}

				foreach ($childrenValueList as $childValue) {
					if (!in_array($childValue, $result[$parentId])) {
						$result[$parentId][] = $childValue;
					}
				}
			}

			return $result;
		}

		/**
		 * Помещает сообщение в журнал
		 * @param string $message сообщение
		 * @return iDemolisher
		 */
		protected function pushLog($message) {
			$this->log[] = $message;
			return $this;
		}

		/**
		 * Очищает журнал
		 * @return iDemolisher
		 */
		private function clearLog() {
			$this->log = [];
			return $this;
		}

		/**
		 * Возвращает журнал
		 * @return string[]
		 */
		private function getLog() {
			return $this->log;
		}
	}
