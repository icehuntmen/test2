<?php
	require_once '../libs/config.php';

	use UmiCms\Service;

	$registry = Service::Registry();

	if ($registry->checkSelfKeycode()) {
		exit();
	}

	if (is_file(SYS_TEMP_PATH . '/runtime-cache/registry')) {
		unlink(SYS_TEMP_PATH . '/runtime-cache/registry');
	}

	if (is_file(SYS_TEMP_PATH . '/runtime-cache/trash')) {
		unlink(SYS_TEMP_PATH . '/runtime-cache/trash');
	}

	$ip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : str_replace("\\", '', $_SERVER['DOCUMENT_ROOT']);
	$domain = getServer('HTTP_HOST');
	$keycode = getRequest('keycode');
	$do = getRequest('do');
	$domain_keycode = getRequest('domain_keycode');
	$license_codename = getRequest('license_codename');

	if ($do == 'load') {
		header('Content-type: text/xml; charset=utf-8');
		$url = 'aHR0cDovL2luc3RhbGwudW1pLWNtcy5ydS9maWxlcy90ZXN0aG9zdC5waHA=';
		$result = umiRemoteFileGetter::get(base64_decode($url), dirname(__FILE__) . '/testhost.php');
		$content = '<?xml version="1.0" encoding="utf-8"?>';

		if ($result->getSize() == 0) {
			$content .= '<result><error>Не удается загрузить тесты хостинга.</error></result>';
		} else {
			$content .= '<result>ok</result>';
		}

		echo $content;
		exit();
	}

	if ($do == 'test') {
		header('Content-type: text/xml; charset=utf-8');
		require dirname(__FILE__) . '/testhost.php';
		$tests = new testHost();
		$conInfo = ConnectionPool::getInstance()->getConnection()->getConnectionInfo();
		$host = $conInfo['host'];
		$host .= mb_strlen(trim($conInfo['port'])) ? (':' . $conInfo['port']) : '';
		$tests->setConnect($host, $conInfo['user'], $conInfo['password'], $conInfo['dbname']);
		$result = $tests->getResults();
		$content = '<?xml version="1.0" encoding="utf-8"?>';
		$content .= '<result>';

		if (count($result) > 0) {
			foreach ($result as $error) {
				$error_url = 'https://errors.umi-cms.ru/upage://' . $error[0] . '/';
				$error_xml = simplexml_load_string(umiRemoteFileGetter::get($error_url));
				$error_msg = $error_xml->xpath('//property[@name = "short_description"]/value');
				$content .= '<error code="' . $error[0] . '" critical="' . $error[1] . '"><![CDATA[' . ((string) $error_msg[0]) . ']]></error>';
			}
		} else {
			$content .= '<message>ok</message>';
		}

		$content .= '</result>';

		echo $content;
		exit();
	}

	if (($domain_keycode === null || $license_codename === null) && $keycode !== null) {
		// Проверка лицензионного ключа
		$params = [
			'ip' => $ip,
			'domain' => $domain,
			'keycode' => $keycode,
		];
		$url = 'aHR0cDovL3Vkb2QudW1paG9zdC5ydS91ZGF0YTovL2N1c3RvbS9wcmltYXJ5Q2hlY2tDb2RlLw==';
		$url = base64_decode($url) . base64_encode(serialize($params)) . '/';
		$result = umiRemoteFileGetter::get(
			$url, false, false, false, false, false, 30
		);

		header('Content-type: text/xml; charset=utf-8');
		echo $result;
		exit();
	}

	if (mb_strlen(str_replace('-', '', $domain_keycode)) != 33) {
		exit();
	}

	if (!$license_codename) {
		exit();
	}

	$pro = ['commerce', 'business', 'corporate', 'commerce_enc', 'business_enc', 'corporate_enc', 'gov'];
	$internalCodeName = in_array($license_codename, $pro) ? 'pro' : $license_codename;
	$checkKey = umiTemplater::getSomething($internalCodeName, $domain);

	if ($checkKey != mb_substr($domain_keycode, 12)) {
		exit();
	}

	$domainCollection = Service::DomainCollection();
	$defaultDomain = $domainCollection
		->getDefaultDomain();

	try {
		$defaultDomain->setHost($domain);
		$defaultDomain->commit();
	} catch (databaseException $exception) {
		if ($exception->getCode() == IConnection::DUPLICATE_KEY_ERROR_CODE) {
			$currentDomainId = $domainCollection->getDomainId($domain);
			$domainCollection->setDefaultDomain($currentDomainId);
		} else {
			throw $exception;
		}
	}

	$registry->set('//settings/keycode', $domain_keycode);
	$registry->set('//settings/system_edition', $license_codename);
	$registry->set('//modules/autoupdate/system_edition', $license_codename);

	/** @var autoupdate|AutoUpdateService $moduleAutoUpdates */
	$moduleAutoUpdates = cmsController::getInstance()->getModule('autoupdate');
	$isAutoUpdateModuleCorrect = ($moduleAutoUpdates instanceof def_module);

	if ($isAutoUpdateModuleCorrect) {
		$moduleAutoUpdates->resetSupportTimeCache();

		if ($moduleAutoUpdates->isMethodExists('deleteIllegalComponents')) {
			$moduleAutoUpdates->deleteIllegalComponents();
		} elseif ($moduleAutoUpdates->isMethodExists('deleteIllegalModules')) {
			$moduleAutoUpdates->deleteIllegalModules();
		}
	}

	$oldServicePath = SYS_MODULES_PATH . 'autoupdate/ch_m.php';

	if (is_file($oldServicePath)) {
		include $oldServicePath;
		ch_remove_m_garbage();
	}
