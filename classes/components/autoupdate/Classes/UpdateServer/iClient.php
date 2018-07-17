<?php

	namespace UmiCms\Classes\Components\AutoUpdate\UpdateServer;

	use UmiCms\System\Request\Http\iRequest;
	use UmiCms\Classes\Components\AutoUpdate\iRegistry;
	use UmiCms\System\Registry\iSettings;
	use UmiCms\Classes\System\Entities\Date\iFactory;
	use UmiCms\System\Cache\iEngineFactory;

	/**
	 * Интерфейс клиента сервера обновлений
	 * @package UmiCms\Classes\Components\AutoUpdate\UpdateServer
	 */
	interface iClient {

		/**
		 * Конструктор
		 * @param iRequest $request http запрос
		 * @param iRegistry $registry реестр модуля "Автообновления"
		 * @param iSettings $settings реестр общих настроек системы
		 * @param iFactory $dateFactory фабрика дат
		 * @param iEngineFactory $engineFactory фабрика хранилищ кеша
		 */
		public function __construct(
			iRequest $request, iRegistry $registry, iSettings $settings, iFactory $dateFactory,
			iEngineFactory $engineFactory
		);

		/**
		 * Возвращает номер последней ревизии
		 * @return int
		 * @throws \RuntimeException
		 */
		public function getLastRevision();

		/**
		 * Возвращает дату окончания поддержки
		 * @return \iUmiDate
		 * @throws \RuntimeException
		 */
		public function getSupportEndTime();


		/**
		 * Возвращает список модулей, доступных для установки
		 * @return array
		 *
		 * [
		 *      'news' => 'Новости',
		 * ]
		 *
		 * @throws \RuntimeException
		 */
		public function getAvailableModuleList();

		/**
		 * Возвращает список расширений, доступных для установки
		 * @return array
		 *
		 * [
		 *      'cpunumpages' => 'ЧПУ numpages',
		 * ]
		 *
		 * @throws \RuntimeException
		 */
		public function getAvailableExtensionList();


		/**
		 * Возвращает список модулей, которые не должны быть установлены на текущей системе
		 * @return array
		 *
		 * [
		 *      'news'
		 * ]
		 *
		 * @throws \RuntimeException
		 */
		public function getIllegalModuleList();

		/**
		 * Возвращает список расширений, которые не должны быть установлены на текущей системе
		 * @return array
		 *
		 * [
		 *      'cpunumpages'
		 * ]
		 *
		 * @throws \RuntimeException
		 */
		public function getIllegalExtensionList();

		/**
		 * Возвращает список файлов компонента
		 * @param string $name имя компонента
		 * @return array
		 *
		 * [
		 *      '7aabc04173bb8edf45a000cc9e6f0bf8' => './classes/modules/faq/events.php'
		 * ]
		 *
		 * @throws \RuntimeException
		 */
		public function getComponentFileList($name);
	}