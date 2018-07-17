<?php

	use UmiCms\Service;

	require_once CURRENT_WORKING_DIR . '/libs/config.php';

	$url = getRequest('url');
	$host = getServer('HTTP_HOST') ? str_replace('www.', '', getServer('HTTP_HOST')) : false;
	$referer = getServer('HTTP_REFERER') ? parse_url(getServer('HTTP_REFERER')) : false;

	$refererHost = false;

	if ($referer && isset($referer['host'])) {
		$refererHost = $referer['host'];
	}

	$buffer = Service::Response()
		->getCurrentBuffer();
	$buffer->contentType('text/plain');
	$buffer->charset('utf-8');

	if (!$url || !$refererHost || !$host || mb_strpos($refererHost, $host) === false) {
		$buffer->status(404);
		$buffer->end();
	}

	$buffer->redirect($url);
