<?php

	use \UmiCms\Service;

	abstract class __autoupdate extends baseModuleAdmin {

		public function versions() {
			$regedit = regedit::getInstance();
			$systemEdition = $regedit->getVal("//modules/autoupdate/system_edition");
			$systemEditionStatus = "%autoupdate_edition_" . $systemEdition . "%";
			
			if($systemEdition == "commerce_trial" && $_SERVER['HTTP_HOST'] != 'localhost' && $_SERVER['HTTP_HOST'] != 'subdomain.localhost' && $_SERVER['SERVER_ADDR'] != '127.0.0.1') {
				$daysLeft = $regedit->getDaysLeft();
				$systemEditionStatus .= " ({$daysLeft} " . getLabel('label-days-left') . ")";
			}

			$systemEditionStatus = def_module::parseTPLMacroses($systemEditionStatus);

			$params = Array(
				"autoupdate" => Array(
					"status:system-edition"		=> NULL,
					"status:last-updated"		=> NULL,
					"status:system-version"		=> NULL,
					"status:system-build"		=> NULL,
					"status:db-driver"			=> NULL,
					"boolean:disabled"			=> NULL,
				)
			);


			$params['autoupdate']['status:system-version'] = $regedit->getVal("//modules/autoupdate/system_version");
			$params['autoupdate']['status:system-build'] = $regedit->getVal("//modules/autoupdate/system_build");
			$params['autoupdate']['status:system-edition'] = $systemEditionStatus;
			$params['autoupdate']['status:last-updated'] = date("Y-m-d H:i:s", $regedit->getVal("//modules/autoupdate/last_updated"));
			$params['autoupdate']['status:db-driver'] = iConfiguration::MYSQL_DB_DRIVER;



			$autoupdates_disabled = false;
			if(defined("CURRENT_VERSION_LINE")) {
				if(isDemoMode() || in_array(CURRENT_VERSION_LINE, array("start"))) {
					$autoupdates_disabled = true;
				}
			}

			$params['autoupdate']['boolean:disabled'] = (int) $autoupdates_disabled;
			
			$domainsCollection = domainsCollection::getInstance();
			$host = Service::Request()->host();

			if (!$domainsCollection->isDefaultDomain($host)) {
				$params['autoupdate']['check:disabled-by-host'] = $domainsCollection->getDefaultDomain()->getHost();
			}

			$this->setDataType("settings");
			$this->setActionType("view");

			$data = $this->prepareData($params, "settings");

			$this->setData($data);
			return $this->doData();
		}

	}
?>