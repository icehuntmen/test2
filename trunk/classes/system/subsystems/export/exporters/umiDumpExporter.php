<?php

	/**
	 * @deprecated
	 * @see umiDump20Exporter
	 * Тип экспорта в формате umiDump
	 */
	class umiDumpExporter extends umiExporter {

		/** @inheritdoc */
		public function export($exportList, $ignoreList) {
			$sel = new selector('pages');

			if (is_array($exportList) && umiCount($exportList)) {
				foreach ($exportList as $stem) {
					$sel->where('hierarchy')->page($stem->getId())->level(100);
				}
			} else {
				$sel->where('hierarchy')->page(0)->level(100);
			}

			$elements = array_merge($sel->result(), $exportList);
			$elements = array_diff($elements, $ignoreList);

			return $this->getUmiDump($elements);
		}

		/**
		 * @param $branches
		 * @param bool|string $source_name
		 * @return string
		 */
		protected function getUmiDump($branches, $source_name = false) {
			if (!$source_name) {
				$source_name = $this->getSourceName();
			}

			$exporter = new xmlExporter($source_name);
			$exporter->addBranches($branches);
			$exporter->setIgnoreRelations();
			$result = $exporter->execute();

			return $result->saveXML();
		}
	}
