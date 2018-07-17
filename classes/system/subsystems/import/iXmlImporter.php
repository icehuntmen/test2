<?php
	interface iXmlImporter {

		public function __construct($sourceName = false);
		public function loadXmlString($xmlString);
		public function loadXmlFile($path);
		public function loadXmlDocument(DOMDocument $doc);
		public function setDestinationElement($element);
		public function execute();

		/**
		 * Запускает удаление
		 * @throws publicException
		 */
		public function demolish();

		/**
		 * Включает отправку событий
		 * @return $this
		 */
		public function enableEvents();

		/**
		 * Выключает отправку событий
		 * @return $this
		 */
		public function disableEvents();
	}
