<?php

	use UmiCms\Service;

	/** Класс функционала административной панели */
	class AutoupdateAdmin{

		use baseModuleAdmin;
		/** @var autoupdate $module */
		public $module;

		/**
		 * Возвращает информацию о состоянии обновлений системы
		 * @throws coreException
		 */
		public function versions() {
			$module = $this->module;
			$systemEditionStatus = '%autoupdate_edition_' . $module->getEdition() . '%';

			if ($module->isTrial() && !Service::Request()->isLocalHost()) {
				$daysLeft = Service::Registry()->getDaysLeft();
				$systemEditionStatus .= " ({$daysLeft} " . getLabel('label-days-left') . ')';
			}

			$systemEditionStatus = autoupdate::parseTPLMacroses($systemEditionStatus);

			$params = [
				'autoupdate' => [
					'status:system-edition'	=> $systemEditionStatus,
					'status:last-updated' => date('Y-m-d H:i:s', $module->getUpdateTime()),
					'status:system-version' => $module->getVersion(),
					'status:system-build' => $module->getRevision(),
					'status:db-driver' => iConfiguration::MYSQL_DB_DRIVER,
					'boolean:disabled' => false
				]
			];

			if (defined('CURRENT_VERSION_LINE')) {
				$isStartEdition =  in_array(CURRENT_VERSION_LINE, ['start']);

				if (isDemoMode() || $isStartEdition) {
					$params['autoupdate']['boolean:disabled'] = true;
				}
			}

			$domainCollection = Service::DomainCollection();
			$host = Service::Request()->host();

			if (!$domainCollection->isDefaultDomain($host)) {
				$params['autoupdate']['check:disabled-by-host'] = $domainCollection->getDefaultDomain()
					->getHost();
			}

			$this->setDataType('settings');
			$this->setActionType('view');
			$data = $this->prepareData($params, 'settings');
			$this->setData($data);
			$this->doData();
		}

		/** Возвращает данные для вкладки "Целостность" */
		public function integrity() {
			$this->setDataType('settings');
			$this->setActionType('view');
			$data = $this->module->getIntegrityState();
			$this->setData($data);
			$this->doData();
		}
	}
