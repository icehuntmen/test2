<?php

	namespace UmiCms\System\Auth\AuthenticationRules;

	use UmiCms\System\Auth\PasswordHash\iAlgorithm;
	use UmiCms\System\Selector\iFactory as SelectorFactory;

	/**
	 * Класс фабрики правил аутентификации
	 * @package UmiCms\System\Auth\AuthenticationRules
	 */
	class Factory implements iFactory {

		/** iAlgorithm $hashAlgorithm алгоритм хеширования паролей */
		private $hashAlgorithm;

		/** SelectorFactory $selectorFactory фабрика селекторов */
		private $selectorFactory;

		/** @inheritdoc */
		public function __construct(iAlgorithm $algorithm, SelectorFactory $selectorFactory) {
			$this->hashAlgorithm = $algorithm;
			$this->selectorFactory = $selectorFactory;
		}

		/** @inheritdoc */
		public function createByLoginAndPassword($login, $password) {
			$hashAlgorithm = $this->getHashAlgorithm();
			$queryBuilder = $this->getQueryBuilder();
			return new LoginAndPassword($login, $password, $hashAlgorithm, $queryBuilder);
		}

		/** @inheritdoc */
		public function createByActivationCode($activationCode) {
			$queryBuilder = $this->getQueryBuilder();
			return new ActivationCode($activationCode, $queryBuilder);
		}

		/** @inheritdoc */
		public function createByLoginAndProvider($login, $provider) {
			$queryBuilder = $this->getQueryBuilder();
			return new LoginAndProvider($login, $provider, $queryBuilder);
		}

		/** @inheritdoc */
		public function createByUserId($userId) {
			$queryBuilder = $this->getQueryBuilder();
			return new UserId($userId, $queryBuilder);
		}

		/** @inheritdoc */
		public function createByFakeUser($userId) {
			$queryBuilder = $this->getQueryBuilder();
			return new FakeUser($userId, $queryBuilder);
		}

		/**
		 * Возвращает алгоритм хеширования паролей
		 * @return iAlgorithm
		 */
		private function getHashAlgorithm() {
			return $this->hashAlgorithm;
		}

		/**
		 * Возвращает конструктор запросов к бд
		 * @return SelectorFactory
		 */
		private function getQueryBuilder() {
			return $this->selectorFactory;
		}
	}
