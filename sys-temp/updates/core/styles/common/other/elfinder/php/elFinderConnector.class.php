<?php

	use UmiCms\Service;

	/**
 * Default elFinder connector
 *
 * @author Dmitry (dio) Levashov
 **/
class elFinderConnector {
	/**
	 * elFinder instance
	 *
	 * @var elFinder
	 **/
	protected $elFinder;

	/**
	 * Options
	 *
	 * @var aray
	 **/
	protected $options = [];

	/**
	 * undocumented class variable
	 *
	 * @var string
	 **/
	protected $header = 'Content-Type: text/html' /*'Content-Type: application/json'*/;

	/**
	 * Constructor
	 *
	 * @author Dmitry (dio) Levashov
	 **/
	public function __construct($elFinder) {
		$this->elFinder = $elFinder;
	}

	/**
	 * Execute elFinder command and output result
	 *
	 * @author Dmitry (dio) Levashov
	 **/
	public function run() {
		$isPost = $_SERVER['REQUEST_METHOD'] == 'POST';
		$src    = $_SERVER['REQUEST_METHOD'] == 'POST' ? $_POST : $_GET;
		$cmd    = isset($src['cmd']) ? $src['cmd'] : '';
		$args   = [];

		if (!function_exists('json_encode')) {
			$error = $this->elFinder->error(elFinder::ERROR_CONF, elFinder::ERROR_CONF_NO_JSON);
			$this->output(['error' => '{"error":["'.implode('","', $error).'"]}', 'raw' => true]);
		}

		if (!$this->elFinder->loaded()) {
			$this->output(['error' => $this->elFinder->error(elFinder::ERROR_CONF, elFinder::ERROR_CONF_NO_VOL)]);
		}

		// telepat_mode: on
		if (!$cmd && $isPost) {
			$this->output(['added' => [], 'warning' => $this->elFinder->error(elFinder::ERROR_UPLOAD_COMMON, elFinder::ERROR_UPLOAD_FILES_SIZE), 'header' => 'Content-Type: text/html']);
		}
		// telepat_mode: off

		if (!$this->elFinder->commandExists($cmd)) {
			$this->output(['error' => $this->elFinder->error(elFinder::ERROR_UNKNOWN_CMD)]);
		}

		// collect required arguments to exec command
		foreach ($this->elFinder->commandArgsList($cmd) as $name => $req) {
			$arg = $name == 'FILES'
				? $_FILES
				: (isset($src[$name]) ? $src[$name] : '');

			if (!is_array($arg)) {
				$arg = trim($arg);
			}
			if ($req && empty($arg)) {
				$this->output(['error' => $this->elFinder->error(elFinder::ERROR_INV_PARAMS, $cmd)]);
			}
			$args[$name] = $arg;
		}

		$args['debug'] = isset($src['debug']) ? (bool) $src['debug'] : false;

		$this->output($this->elFinder->exec($cmd, $args));
	}

	/**
	 * Output json
	 *
	 * @param  array  data to output
	 * @author Dmitry (dio) Levashov
	 **/
	protected function output(array $data) {
		$headerList = isset($data['header']) ? $data['header'] : 'Content-Type: text/html; charset=utf-8';
		unset($data['header']);

		$buffer = Service::Response()
			->getCurrentBuffer();
		$buffer->clear();
		@ob_end_clean();

		if ($headerList) {
			$headerList = (array) $headerList;

			foreach ($headerList as $header) {
				list($name, $value) = explode(':', $header);
				$buffer->setHeader(trim($name), trim($value));
			}
		}

		if (isset($data['pointer'])) {
			rewind($data['pointer']);
			fpassthru($data['pointer']);

			if (!empty($data['volume'])) {
				$data['volume']->close($data['pointer'], $data['info']['hash']);
			}

			$buffer->end();
		}

		if (!empty($data['raw']) && !empty($data['error'])) {
			$buffer->push($data['error']);
		} else {
			$buffer->push(json_encode($data));
		}

		$buffer->end();
	}

}
