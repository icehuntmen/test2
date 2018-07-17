<?php
	abstract class __branching_config extends baseModuleAdmin {
		
		public function branching() {
			$params = Array();

			$this->setDataType('settings');
			$this->setActionType('view');
			
			$data = $this->prepareData($params, 'settings');

			$this->setData($data);
			return $this->doData();
		}
		
		public function getDatabaseReport() {
			$tablesCount = 0;
			$optimizeTablesCount = 0;
			
			$maxItemsPerType = 3500;
			$minItemsPerType = round($maxItemsPerType / 2);
			
			foreach(umiBranch::getDatabaseStatus() as $info) {
				$tablesCount++;
				
				$isBranched = $info['isBranched'];
				$size = $info['count'];
				
				if(!$isBranched && ($size > $maxItemsPerType)) {
					$optimizeTablesCount++;
				}
				
				if($isBranched && ($size < $minItemsPerType)) {
					$optimizeTablesCount++;
				}
			}
			
			return ($tablesCount > 0) ? round($optimizeTablesCount * 100 / $tablesCount) : 0;
		}
		
		public function reviewDatabase() {
			$buffer = \UmiCms\Service::Response()
				->getCurrentBuffer();
			$buffer->contentType('text/javascript');
			$buffer->charset('utf-8');
		
			$maxItemsPerType = 3500;
			$minItemsPerType = round($maxItemsPerType / 2);
			
			$status = umiBranch::getDatabaseStatus();
			foreach($status as $item) {
				if($item['isBranched'] == false) {
					if($item['count'] > $maxItemsPerType) {
						$hierarchyTypeId = $item['id'];
						self::branchTable($hierarchyTypeId);
					}
				} else {
					if($item['count'] < $minItemsPerType) {
						$hierarchyTypeId = $item['id'];
						self::mergeTable($hierarchyTypeId);
					}
				}
			}
			$buffer->push("\nwindow.location = window.location;\n");
			$buffer->end();
		}
		
		protected static function branchTable($hierarchyTypeId = null) {
			if ($hierarchyTypeId === null) {
				$hierarchyTypeId = getRequest('param0');
			}

			$params = [
				'hierarchy-type-id' => $hierarchyTypeId
			];

			\UmiCms\Service::ManifestFactory()
				->create('BranchContentTable', $params)
				->execute();
		}
		
		protected static function mergeTable($hierarchyTypeId = null) {
			if ($hierarchyTypeId === null) {
				$hierarchyTypeId = getRequest('param0');
			}

			$params = [
				'hierarchy-type-id' => $hierarchyTypeId
			];

			\UmiCms\Service::ManifestFactory()
				->create('MergeContentTable', $params)
				->execute();
		}
	};
?>