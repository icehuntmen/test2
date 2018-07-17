<?php
	namespace UmiCms\System\Import\UmiDump;
	/**
	 * Интерфейс класса удаления группы однородных данных.
	 * @package UmiCms\System\Import\UmiDump
	 */
	interface iDemolisher {

		/**
		 * Запускает удаление
		 * @param \DOMXPath $parser парсер документа в формате umiDump
		 * @return string[]
		 */
		public function run(\DOMXPath $parser);
	}