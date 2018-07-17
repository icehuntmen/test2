<?php
	require_once CURRENT_WORKING_DIR . '/libs/config.php';

	use UmiCms\Service;

	$config = mainConfiguration::getInstance();
	$buffer = Service::Response()
		->getCurrentBuffer();

	if ($config->get('seo', 'index-redirect') && mb_strpos(trim($_SERVER['REQUEST_URI'], ' /'), 'index.php') === 0) {
		$buffer->redirect('/');
	}

	// don't use getRequest('p') for this
	if (isset($_GET['p']) && $_GET['p'] === '0' && !getRequest('xmlMode') && !getRequest('jsonMode')) {
		$urlInfo = parse_url($_SERVER['REQUEST_URI']);
		$vars = [];
		parse_str($urlInfo['query'], $vars);
		unset($vars['p']);
		$validUrl = $urlInfo['path'];
		if (count($vars)) {
			$validUrl .= '?' . http_build_query($vars);
		}
		$buffer->redirect($validUrl);
	}

	$auth = Service::Auth();

	try {
		$auth->loginByEnvironment();
	} catch (UmiCms\System\Auth\AuthenticationException $e) {
		$buffer->clear();
		$buffer->status('401 Unauthorized');
		$buffer->setHeader('WWW-Authenticate', 'Basic realm="UMI.CMS"');
		$buffer->push('HTTP Authenticate failed');
		$buffer->end();
	}

	checkMobileApplication();
	$session = Service::Session();

	$referer = preg_replace('/^(http(s)?:\/\/)?(www\.)?/', '', getServer('HTTP_REFERER'));
	$host = preg_replace('/^(http(s)?:\/\/)?(www\.)?/', '', getServer('HTTP_HOST'));

	if (mb_strpos($referer, $host) !== 0) {
		$session->set('http_referer', getServer('HTTP_REFERER'));
		$session->set('http_target', getServer('REQUEST_URI'));
	}

	if (!$session->get('http_target')) {
		$session->set('http_target', getServer('REQUEST_URI'));
	}

	//Parse [stub] ini section
	if ($config->get('stub', 'enabled')) {
		if(is_array($ips = $config->get('stub', 'filter.ip'))) {
			$enabled = !in_array(getServer('REMOTE_ADDR'), $ips);
		}
		else {
			$enabled = true;
		}

		if ($enabled) {
			$stubFilePath = $config->includeParam('system.stub');
			if (is_file($stubFilePath)) {
				require $stubFilePath;
				$buffer->end();
			}
			else {
				throw new coreException("Stub file \"{$stubFilePath}\" not found");
			}
		}
	}

	if ($config->get('kernel', 'matches-enabled')) {
		try {
			$matches = new matches();
			$matches->setCurrentURI(getRequest('path'));
			$matches->execute();
		} catch (Exception $ignored) {}

		unset($matches);
	}

	$cmsController = cmsController::getInstance();
	cmsController::doSomething();
	$cmsController->calculateRefererUri();
	$cmsController->analyzePath();

	$request = Service::Request();
	$currentDomain = Service::DomainDetector()->detect();

	if ($request->host() != $currentDomain->getHost()) {
		$requestDomain = Service::DomainCollection()
			->getDomainByHost($request->host());
		handleRequestFromMirror($currentDomain, $requestDomain);
	}

	$eventPoint = new umiEventPoint('systemPrepare');
	$eventPoint->setMode('before');
	$eventPoint->call();

	$staticCache = Service::StaticCache();
	$cachedContent = $staticCache->load();

	$eventPoint->setMode('after');
	$eventPoint->call();


	if (is_string($cachedContent)) {
		$buffer->contentType('text/html');
		$buffer->charset('utf-8');
		$buffer->push($cachedContent);
	} elseif ($request->isXml()) {
		$buffer->contentType('text/xml');
		// flush XML
		$dom = new DOMDocument('1.0', 'utf-8');
		$rootNode = $dom->createElement('result');
		$dom->appendChild($rootNode);
		$rootNode->setAttribute('xmlns:xlink', 'http://www.w3.org/TR/xlink');

		// принудительный режим xslt для получения глобальных переменных
		def_module::isXSLTResultMode(true);

		$globalVars = $cmsController->getGlobalVariables();
		$translator = new xmlTranslator($dom);
		$translator->translateToXml($rootNode, $globalVars);
		$buffer->push($dom->saveXML());
		$buffer->option('generation-time', true);
	} elseif ($request->isJson()) {
		$buffer->contentType('text/javascript');

		// принудительный режим xslt для получения глобальных переменных
		def_module::isXSLTResultMode(true);
		$globalVars = $cmsController->getGlobalVariables();

		$translator = new jsonTranslator;
		$result = $translator->translateToJson($globalVars);

		$buffer->push($result);
	} else {
		$globalVars = $cmsController->getGlobalVariables();
		$currentTemplateEngine = $cmsController->getCurrentTemplater();
		// enable callstack
		if ($request->isStreamCallStack()) {
			$currentTemplateEngine::setEnabledCallStack(!$config->get('debug', 'callstack.disabled'));
		}

		$templatesSource = $currentTemplateEngine->getTemplatesSource();
		/** @noinspection PhpMethodParametersCountMismatchInspection */
		list($commonTemplate) = $currentTemplateEngine::getTemplates($templatesSource, 'common');

		if ($cmsController->getCurrentElementId()) {
			$currentTemplateEngine->setScope($cmsController->getCurrentElementId());
		}
		$result = $currentTemplateEngine->parse($globalVars, $commonTemplate);

		if ($request->isNotAdmin()) {
			$result = $currentTemplateEngine->cleanup($result);
		}

		$buffer->push($result);
		$buffer->option('generation-time', true);

		// flush streams calls
		if ($request->isStreamCallStack()) {
			$buffer->contentType('text/xml');
			$buffer->clear();
			$buffer->push($currentTemplateEngine->getCallStackXML());
			$buffer->end();
		}

		$staticCache->save($buffer->content());
	}

	if ($request->isNotAdmin() && Service::Registry()->get('//modules/stat/collect') && $statistics = $cmsController->getModule('stat') ) {
		if ($statistics instanceof stat && $statistics->enabled ) {
			$statistics->pushStat();
		}
	}

	$buffer->end();

	/**
	 * Обрабатывает запрос с зеркала домена.
	 * В зависимости от настроек:
	 * @link http://dev.docs.umi-cms.ru/nastrojka_sistemy/dostupnye_sekcii/sekciya_seo/#sel=29:1,29:3
	 *
	 * Совершает одно из следующих действий:
	 *
	 * 1) Перенаправляет с зеркала на текущий домен;
	 * 2) Прерывает выполнение скрипта, если запрошено неизвестное зеркало;
	 * 3) Добавляет неизвестное зеркало в список зеркал текущего домена;
	 * 4) Ничего не делает
	 *
	 * @param iDomain $currentDomain текущий домен
	 * @param iDomain|bool $requestDomain запрошенный домен
	 * @throws coreException
	 */
	function handleRequestFromMirror(iDomain $currentDomain, $requestDomain) {
		if (isCronCliMode()) {
			return;
		}

		$config = mainConfiguration::getInstance();
		$primaryDomainRedirect = $config->get('seo', 'primary-domain-redirect');
		$requestUnknownDomain = !$requestDomain instanceof iDomain;
		$buffer = Service::Response()
			->getCurrentBuffer();
		$request = Service::Request();

		if ($primaryDomainRedirect == 1) {
			$uri = $currentDomain->getUrl() . $request->uri();
			$buffer->redirect($uri);
		}

		if ($primaryDomainRedirect == 2 && $requestUnknownDomain) {
			$buffer->status(500);
			$buffer->push(file_get_contents(CURRENT_WORKING_DIR . '/errors/invalid_domain.html'));
			$buffer->end();
		}

		if ($primaryDomainRedirect == 3 && $requestUnknownDomain) {
			$host = $request->host();
			$currentDomain->addMirror($host);
		}
	}
