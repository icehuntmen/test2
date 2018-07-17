<?php
	namespace UmiCms\Manifest\Catalog;
	/**
	 * Команда переиндексации фильтров
	 * @package UmiCms\Manifest\Catalog
	 */
	class FilterIndexingAction extends \Action {

		/** @inheritdoc */
		public function execute() {
			$indexedCategories = $this->getIndexedCategories();
			$umiHierarchy = \umiHierarchy::getInstance();

			/* @var \iUmiHierarchyElement $indexedCategory */
			foreach ($indexedCategories as $indexedCategory) {

				try {
					$this->reIndexCategory($indexedCategory);
				} catch (\noObjectsFoundForIndexingException $exception) {
					//nothing
				}

				$categoryId = $indexedCategory->getId();
				$umiHierarchy->unloadElement($categoryId);
			}

			return $this;
		}

		/** @inheritdoc */
		public function rollback() {
			return $this;
		}

		/**
		 * Возвращает категории разделов каталога, нуждающиеся в переиндексации
		 * @return array
		 */
		private function getIndexedCategories() {
			$categories = new \selector('pages');
			$categories->types('hierarchy-type')->name('catalog', 'category');
			$categories->where('index_choose')->equals(true);
			return $categories->result();
		}

		/**
		 * Переиндексирует фильтры раздела каталога
		 * @param \iUmiHierarchyElement $category объект раздела каталога
		 * @return $this
		 */
		private function reIndexCategory(\iUmiHierarchyElement $category) {
			$level = (int) $category->getValue('index_level');
			$parentId = $category->getId();
			$catalogObjectHierarchyTypeId = $this->getCatalogObjectHierarchyTypeId();

			$indexGenerator = new \FilterIndexGenerator($catalogObjectHierarchyTypeId, 'pages');
			$indexGenerator->setHierarchyCondition($parentId, $level);
			$indexGenerator->run();

			$category->setValue('index_source', $parentId);
			$category->setValue('index_date', new \umiDate());
			$category->setValue('index_state', 100);
			$category->commit();

			$this->markChildren($parentId, $level);
			return $this;
		}

		/**
		 * Возвращает идентификатор иерархического типа данных объектов каталога,
		 * или false, если не удалось получить тип.
		 * @return bool|int
		 */
		private function getCatalogObjectHierarchyTypeId() {
			$umiHierarchyTypes = \umiHierarchyTypesCollection::getInstance();
			$umiHierarchyType = $umiHierarchyTypes->getTypeByName('catalog', 'object');

			if (!$umiHierarchyType instanceof \umiHierarchyType) {
				return false;
			}

			return $umiHierarchyType->getId();
		}

		/**
		 * Указывает у дочерних разделов каталога источник индекса фильтров
		 * @param int $parentId ид родительского раздела
		 * @param int $level уровень вложенности дочерних разделов
		 * @return $this
		 */
		private function markChildren($parentId, $level) {
			$childrenCategories = new \selector('pages');
			$childrenCategories->types('hierarchy-type')->name('catalog', 'category');
			$childrenCategories->where('hierarchy')->page($parentId)->childs($level);
			$childrenCategories->order('id');
			$childrenCategories = $childrenCategories->result();

			if (umiCount($childrenCategories) == 0) {
				return $this;
			}

			$umiHierarchy = \umiHierarchy::getInstance();
			/* @var \iUmiHierarchyElement $childrenCategory */
			foreach ($childrenCategories as $childrenCategory) {
				$childrenCategory->setValue('index_source', $parentId);
				$childrenCategory->commit();
				$umiHierarchy->unloadElement($childrenCategory->getId());
			}

			return $this;
		}
	}
