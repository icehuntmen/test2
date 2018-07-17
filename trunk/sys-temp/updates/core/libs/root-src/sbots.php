<?php

	use UmiCms\Service;

	require_once CURRENT_WORKING_DIR . '/libs/config.php';

	$buffer = Service::Response()
		->getCurrentBuffer();
	$buffer->contentType('text/plain');
	$buffer->charset('utf-8');

	$cmsController = cmsController::getInstance();
	$config = mainConfiguration::getInstance();
	$domain = Service::DomainDetector()->detect();

	$disallowedPages = new selector('pages');
	$disallowedPages->where('robots_deny')->equals(1);
	$disallowedPages->where('lang')->isnotnull();

	$rules = '';

	foreach ($disallowedPages as $page) {
		$rules .= 'Disallow: ' . $page->link . PHP_EOL;
	}

	$rules .= <<<RULES
Disallow: /admin
Disallow: /index.php
Disallow: /emarket/addToCompare
Disallow: /emarket/basket
Disallow: /go-out.php
Disallow: /cron.php
Disallow: /filemonitor.php
Disallow: /search
RULES;

	$crawlDelay = $config->get('seo', 'crawl-delay');
	$primaryWww = (bool) $config->get('seo', 'primary-www');
	$host = $domain->getHost();
	$host = preg_replace('/^www./', '', $host);

	if ($primaryWww) {
		$host = 'www.' . $host;
	}

	$host = $domain->getProtocol() . "://{$host}";
	$customPath = CURRENT_WORKING_DIR . '/robots/' . $domain->getId() . '.robots.txt';

	if (file_exists($customPath)) {
		$customRobots = file_get_contents($customPath);

		if ($customRobots !== '') {
			$needleList = [
				'%disallow_umi_pages%',
				'%host%',
				'%crawl_delay%'
			];

			$replacementList = [
				$rules,
				$host,
				$crawlDelay
			];

			$customRobots = str_replace($needleList, $replacementList, $customRobots);
			$buffer->push($customRobots);
			$buffer->end();
		}
	}

	$rules = 'Disallow: /?' . PHP_EOL . $rules . PHP_EOL . PHP_EOL;

	$buffer->push('User-Agent: Googlebot' . PHP_EOL);
	$buffer->push($rules);
	$buffer->push('User-Agent: Yandex' . PHP_EOL);
	$buffer->push($rules);
	$buffer->push('User-Agent: *' . PHP_EOL);
	$buffer->push($rules);
	$buffer->push("Host: {$host}" . PHP_EOL);
	$buffer->push("Sitemap: {$host}/sitemap.xml" . PHP_EOL);
	$buffer->push("Crawl-delay: {$crawlDelay}" . PHP_EOL);
	$buffer->end();
