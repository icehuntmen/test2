<?php

	use \UmiCms\Service;

	/** Класс функционала административной панели */
	class Social_networksAdmin {

		use baseModuleAdmin;
		/** @var social_network $module */
		public $module;

		/**
		 * Возвращает настройки приложений социальных сетей.
		 * Если передан ключевой параметр $_REQUEST['param0'] = do,
		 * то сохраняет настройки.
		 * @param social_network[] $networkList приложения социальные сетей
		 * @throws coreException|publicAdminException
		 */
		public function network_settings($networkList) {
			$mode = getRequest('param0');

			if (empty($networkList)) {
				throw new publicAdminException(getLabel('error-label-empty-network-list'));
			}

			$firstNetwork = $networkList[0];

			$this->setHeaderLabel(getLabel('header-social_networks-settings') . $firstNetwork->getName());
			$this->setDataType('form');
			$this->setActionType('modify');

			if ($mode == 'do') {

				foreach ($networkList as $network) {
					$this->saveEditedObjectData([
						'object' => $network->getObject(),
						'type' => $network->getCodeName()
					]);
				}

				$this->chooseRedirect($this->module->pre_lang . '/admin/social_networks/' . $firstNetwork->getCodeName() . '/');
			}

			$objectList = [];
			$domainsCollection = Service::DomainCollection();

			foreach ($networkList as $network) {

				$object = $this->prepareData(
					[
						'object' => $network->getObject(),
						'type' => $network->getCodeName()
					],
					'object'
				);

				$domain = $domainsCollection->getDomain($network->getDomainId());

				if (!$domain instanceof iDomain) {
					continue;
				}

				$object = $object['object'];
				$object['@domain'] = $domain->getHost();
				$object['@template-id'] = $network->getTemplateId();
				$objectList[] = $object;
			}

			$this->setData([
				'nodes:object' => $objectList
			]);
			$this->doData();
		}
	}