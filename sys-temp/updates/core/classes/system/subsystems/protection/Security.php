<?php

	namespace UmiCms\System\Protection;
	use UmiCms\Service;

	/**
	 * Отвечает за выполнение проверок безопасности разных типов.
	 * Facade. Singleton.
	 * Class Security
	 * @example
	 * try {
	 * 		UmiCms\System\Protection\Security::getInstance()->checkCsrf();
	 * 		// Проверка безопасности пройдена успешно
	 * } catch (\UmiCms\System\Protection\CsrfException $e) {
	 * 		// Проверка безопасности провалена
	 * }
	 * @package UmiCms\System\Protection
	 */
	class Security {
		/** @var null|Security единственный экземпляр класса */
		private static $instance;

		private $csrfProtection;

		/**
		 * Возвращает единственный экземпляр класса
		 * @return Security
		 */
		public static function getInstance() {
			if (self::$instance !== null) {
				return self::getInstance();
			}

			return new self();
		}

		/**
		 * @private Singleton
		 * Security constructor.
		 */
		private function __construct() {}

		/** @private Singleton */
		private function __clone() {}

		/**
		 * Выполняет проверку безопасности на наличие CSRF-атаки
		 * @return bool true если проверка безопасности пройдена успешно
		 * @throws \UmiCms\System\Protection\CsrfException если проверка безопасности провалена
		 */
		public function checkCsrf() {
			if (!\mainConfiguration::getInstance()->get('kernel', 'csrf_protection')) {
				return true;
			}

			try {
				$this->checkOrigin();
				$this->checkReferrer();
			} catch (\InvalidArgumentException $e) {
				/* Заголовки не были переданы */
			} catch (\Exception $e) {
				throw new CsrfException($e->getMessage());
			}

			$this->getCsrfProtection()
				->checkTokenMatch(getRequest('csrf'));

			return true;
		}

		/**
		 * Выполняет проверку безопасности на валидность заголовка Origin
		 * @return bool true если проверка безопасности пройдена успешно
		 * @throws \UmiCms\System\Protection\OriginException если проверка безопасности провалена
		 */
		public function checkOrigin() {
			try {
				return $this->getCsrfProtection()
					->checkOriginCorrect(getServer('HTTP_ORIGIN'));
			} catch(CsrfException $e) {
				throw new OriginException(getLabel('error-no-domain-permissions'));
			}
		}

		/**
		 * Выполняет проверку безопасности на валидность заголовка Referer
		 * @return bool true если проверка безопасности пройдена успешно
		 * @throws \UmiCms\System\Protection\ReferrerException если проверка безопасности провалена
		 */
		public function checkReferrer() {
			try {
				return $this->getCsrfProtection()
					->checkReferrerCorrect(getServer('HTTP_REFERER'));
			} catch(CsrfException $e) {
				throw new ReferrerException(getLabel('error-no-domain-permissions'));
			}
		}

		/**
		 * Инициализирует и возвращает объект защиты от CSRF-атак
		 * @return CsrfProtection
		 */
		private function getCsrfProtection() {
			if ($this->csrfProtection instanceof CsrfProtection) {
				return $this->csrfProtection;
			}

			return $this->loadCsrfProtection();
		}

		/**
		 * Загружает и инициализирует объект защиты от CSRF
		 * @return CsrfProtection
		 */
		private function loadCsrfProtection() {
			$csrfProtection = Service::CsrfProtection();
			$this->csrfProtection = $csrfProtection;
			return $this->csrfProtection;
		}
	}
