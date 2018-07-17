<?php

	namespace UmiCms\System\Auth\AuthenticationRules;

	use UmiCms\System\Auth\PasswordHash\iAlgorithm;
	use UmiCms\System\Auth\PasswordHash\WrongAlgorithmException;
	use UmiCms\System\Selector\iFactory as SelectorFactory;

	/**
	 * Класс правила аутентификации пользователя по логину и паролю
	 * @package UmiCms\System\Auth\AuthenticationRules
	 */
	class LoginAndPassword extends Rule {

		/** @var string $login логин */
		private $login;

		/** @var string $password пароль  */
		private $password;

		/**
		 * Конструктор
		 * @param string $login логин
		 * @param string $password пароль
		 * @param iAlgorithm $algorithm алгоритм хеширования паролей
		 * @param SelectorFactory $selectorFactory фабрика селекторов
		 */
		public function __construct($login, $password, iAlgorithm $algorithm, SelectorFactory $selectorFactory) {
			$this->login = $login;
			$this->password = $password;
			$this->hashAlgorithm = $algorithm;
			$this->selectorFactory = $selectorFactory;
		}

		/** @inheritdoc */
		public function validate() {
			$login = $this->getLogin();
			$password = $this->getPassword();
			$passwordList = $this->getPasswordList($password);

			try {
				$queryBuilder = $this->getQueryBuilder();
				$queryBuilder->option('or-mode')->fields('login', 'e-mail');
				$queryBuilder->where('login')->equals($login);
				$queryBuilder->where('e-mail')->equals($login);
				$queryBuilder->where('password')->equals($passwordList);
				$queryBuilder->where('is_activated')->equals(true);
				$queryBuilder->limit(0, 1);
				$queryResultSet = $queryBuilder->result();
			} catch (\Exception $e) {
				return false;
			}

			if (umiCount($queryResultSet) === 0) {
				return false;
			}

			$queryResultItem = array_shift($queryResultSet);
			return (int) $queryResultItem['id'];
		}

		/**
		 * Возвращает список хешей пароля
		 * @param string $rawPassword пароль в явном виде
		 * @return array
		 * @throws WrongAlgorithmException
		 */
		private function getPasswordList($rawPassword) {
			$algorithm = $this->getHashAlgorithm();

			return [
				$algorithm::hash($rawPassword, $algorithm::MD5),
				$algorithm::hash($rawPassword, $algorithm::SHA256)
			];
		}

		/**
		 * Возвращает алгоритм хеширования паролей
		 * @return iAlgorithm
		 */
		private function getHashAlgorithm() {
			return $this->hashAlgorithm;
		}

		/**
		 * Возвращает логин
		 * @return string
		 */
		private function getLogin() {
			return (string) $this->login;
		}

		/**
		 * Возвращает пароль
		 * @return string
		 */
		private function getPassword() {
			return (string) $this->password;
		}
	}
