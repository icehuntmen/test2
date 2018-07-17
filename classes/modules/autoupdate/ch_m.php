<?php
	function ch_get_version_line() {
		$regedit = regedit::getInstance();
		return (string) $regedit->getVal("//modules/autoupdate/system_edition");
	}

	function get_file($url) {
		return umiRemoteFileGetter::get($url);
	}

	function ch_get_illegal_modules() {
		$regedit = regedit::getInstance();

		$info = array(
			'type'     => 'get-modules-list',
			'revision' => $regedit->getVal("//modules/autoupdate/system_build"),
			'key'      => $regedit->getVal("//settings/keycode"),
			'host'     => getServer('HTTP_HOST'),
			'ip'       => getServer('SERVER_ADDR')
		);

		$url = base64_decode('aHR0cDovL3Vkb2QudW1paG9zdC5ydS91cGRhdGVzZXJ2ZXIv') . "?" . http_build_query($info, '', '&');

		try {
			$result = get_file($url);
		} catch (umiRemoteFileGetterException $e) {
			throw new coreException("Не удалось загрузить список неподдерживаемых модулей");
		}

		$xml = new DOMDocument();
		$xml->loadXML($result);

		$xpath = new DOMXPath($xml);
		$no_active = $xpath->query("//module[not(@active)]");

		$illegal_modules = array();
		foreach ($no_active as $module) {
			$illegal_modules[] = $module->getAttribute("name");
		}

		unset($regedit, $info, $url, $result, $xml, $xpath, $no_active, $module);
		return $illegal_modules;
	}

	function ch_remove_m_garbage() {
		$modules = ch_get_illegal_modules();
		foreach ($modules as $module) {
			ch_remove_illegal_module($module);
		}
	}

	function ch_remove_illegal_module($module_name) {
		if (!trim($module_name, " \r\n\t\/")) {
			return;
		}

		$regedit = regedit::getInstance();
		$regedit->delVar("//modules/{$module_name}");
	}
?>
