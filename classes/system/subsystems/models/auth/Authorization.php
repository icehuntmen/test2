<?php

	namespace UmiCms\System\Auth;

	use UmiCms\System\Protection;
	use UmiCms\System\Cookies;
	use UmiCms\System\Session\iSession;

	/**
	 * Класс авторизации
	 * @package UmiCms\System\Auth
	 */
	class Authorization implements iAuthorization {

		/** @var int|null $authorizedUserId идентификатор авторизованного пользователя */
		private $authorizedUserId;

		/** @var iSession $session контейнер сессии */
		private $session;

		/** @var Protection\CsrfProtection $tokenGenerator генератор csrf токенов */
		private $tokenGenerator;

		/** @var \iPermissionsCollection $permissionsCollection коллекция прав доступа */
		private $permissionsCollection;

		/** @var Cookies\iCookieJar $cookieJar класс для работы с куками */
		private $cookieJar;

		/** @var \iUmiObjectsCollection коллекция объектов */
		private $umiObjects;

		/** @var \iConfiguration конфиг */
		private $configuration;

		/** @inheritdoc */
		public function __construct(
			iSession $session,
			Protection\CsrfProtection $tokenGenerator,
			\iPermissionsCollection $permissionsCollection,
			Cookies\iCookieJar $cookieJar,
			\iUmiObjectsCollection $umiObjectsCollection,
			\iConfiguration $configuration
		) {
			$this->session = $session;
			$this->tokenGenerator = $tokenGenerator;
			$this->permissionsCollection = $permissionsCollection;
			$this->cookieJar = $cookieJar;
			$this->umiObjects = $umiObjectsCollection;
			$this->configuration = $configuration;
		}

		/** @inheritdoc */
		public function authorize($userId) {
			$this->authorizeStateless($userId);
			$this->startSession($userId);
			return $this;
		}

		/** @inheritdoc */
		public function authorizeStateless($userId) {
			$this->validateUserId($userId);
			$this->setAuthorizedUserId($userId);
			return $this;
		}

		/** @inheritdoc */
		public function getAuthorizedUserId() {
			return $this->authorizedUserId;
		}

		/** @inheritdoc */
		public function deAuthorize() {
			$this->deAuthorizeStateless();
			$this->stopSession();
			return $this;
		}

		/** @inheritdoc */
		public function deAuthorizeStateless() {
			$this->setAuthorizedUserId(null);
			return $this;
		}

		/** @inheritdoc */
		public function authorizeUsingFixedSessionId($userId) {
			$this->authorizeStateless($userId);
			$this->startSession($userId, false);
			return $this;
		}

		/** @inheritdoc */
		public function authorizeFakeUser($userId) {
			$this->validateUserId($userId);

			$previousUserId = $this->getAuthorizedUserId();
			$this->deAuthorize();

			$session = $this->getSession();
			$user = $this->umiObjects->getObject($userId);

			switch ($user->getTypeGUID()) {
				case 'users-user': {
					$this->authorize($userId);
					$session->del('customer-id');
					break;
				}
				case 'emarket-customer': {
					$session->set('customer-id', $userId);
					break;
				}
			}

			$this->savePreviousUserId($previousUserId);
			return $this;
		}

		/**
		 * Сохраняет идентификатор предыдущего пользователя
		 * @param int $userId идентификатор предыдущего пользователя
		 */
		private function savePreviousUserId($userId) {
			$session = $this->getSession();
			$session->set('fake-user', true);
			$session->set('old_user_id', $userId);
		}

		/** @inheritdoc */
		public function authorizeUsingPreviousUserId($previousUserId) {
			$this->validateUserId($previousUserId);
			$this->deAuthorize();
			$this->authorize($previousUserId);
			$this->getSession()->del('customer-id');
			return $this;
		}

		/**
		 * Начинает сессию пользователя, неявно отправляет авторизационные куки.
		 * @param int $userId идентификатор пользователя
		 * @param bool $makeNewId необходимо ли назначить сессии новый идентификатор
		 */
		private function startSession($userId, $makeNewId = true) {
			$session = $this->getSession();

			if ($makeNewId) {
				$session->changeId();
			}

			$token = $this->getTokenGenerator()
				->generateToken();

			$userIsSv = $this->getPermissionsCollection()
				->isSv($userId);

			$session->set('user_id', $userId);
			$session->set('csrf_token', $token);
			$session->set('user_is_sv', $userIsSv);
			$session->startActiveTime();
		}

		/** Останавливает сессию пользователя */
		private function stopSession() {
			$session = $this->getSession();
			$cookieJar = $this->getCookieJar();

			$sessionName = $session->getName();
			$cookieJar->remove($sessionName);
			$session->clear();
		}

		/**
		 * Валидирует идентификатор пользователя
		 * @param int $userId идентификатор пользователя
		 * @throws AuthorizationException
		 */
		private function validateUserId($userId) {
			if (!is_int($userId) || $userId <= 0) {
				throw new AuthorizationException('Wrong user id given, integer > 0 expected');
			}
		}

		/**
		 * Устанавливает идентификатор авторизованного пользователя
		 * @param int $userId идентификатор пользователя
		 * @return $this
		 */
		private function setAuthorizedUserId($userId) {
			$this->authorizedUserId = $userId;
			return $this;
		}

		/**
		 * Возвращает контейнер сессии
		 * @return iSession
		 */
		private function getSession() {
			return $this->session;
		}

		/**
		 * Возвращает генератор csrf токенов
		 * @return Protection\CsrfProtection
		 */
		private function getTokenGenerator() {
			return $this->tokenGenerator;
		}

		/**
		 * Возвращает коллекцию прав доступа
		 * @return \iPermissionsCollection
		 */
		private function getPermissionsCollection() {
			return $this->permissionsCollection;
		}

		/**
		 * Возвращает класс для работы с куками
		 * @return Cookies\iCookieJar
		 */
		private function getCookieJar() {
			return $this->cookieJar;
		}
	}
