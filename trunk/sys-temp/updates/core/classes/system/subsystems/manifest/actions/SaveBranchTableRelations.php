<?php
	/** Команда сохранения состояния разбиения таблиц свойств объектов по иерархическому типу */
	class SaveBranchTableRelationsAction extends Action {

		/** @inheritdoc */
		public function execute() {
			$filePath = CURRENT_WORKING_DIR . '/cache/branchedTablesRelations.rel';
			
			if (is_file($filePath)) {
				unlink($filePath);
			}
			
			umiBranch::saveBranchedTablesRelations();
		}

		/** @inheritdoc */
		public function rollback() {
			$filePath = CURRENT_WORKING_DIR . '/cache/branchedTablesRelations.rel';

			if (is_file($filePath)) {
				unlink($filePath);
			}
		}
	}
