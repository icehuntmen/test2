<?php

namespace UmiCms\Classes\System\Utils\I18n;

/** Загрузчик языковых констант. */
class I18nFilesLoader implements iI18nFilesLoader {

	/** @var array языковые константы */
	private $langConstants = [];

	/** @var array список модулей, константы для которых нужно загрузить */
	private $moduleList;

	/** @var string языковой префикс */
	private $langPrefix;

	/**
	 * Конструктор
	 * @param array $moduleList список модулей, константы для которых нужно загрузить
	 * @param string $langPrefix языковой префикс констант
	 */
	public function __construct(array $moduleList, $langPrefix) {
		$this->moduleList = $moduleList;
		$this->langPrefix = $langPrefix;
	}

	/**
	 * Загружает языковые константы для шаблонов сайта:
	 *   - Системные константы в общей директории модулей из файла lang.<prefix>.php или lang.php
	 *   - Константы модулей из файла lang.<prefix>.php или lang.php
	 *   - Константы расширений модулей из файлов формата lang.*.<prefix>.php (любое количество файлов)
	 *
	 * 	 * @return array загруженные константы
	 */
	public function loadLangConstants() {
		foreach ($this->moduleList as $moduleName) {
			$this->loadModuleLangConstants($moduleName);
		}

		$this->loadDefaultLangConstants();
		return $this->langConstants;
	}

	/**
	 * Загружает языковые константы из файлов вида lang.*.php для отдельного модуля.
	 * @param string $moduleName название модуля
	 */
	private function loadModuleLangConstants($moduleName) {
		$this->loadModuleDefaultLangConstants($moduleName);
		$this->loadModuleLangConstantsWithPrefix($moduleName);
		$this->loadModuleExtensionLangConstants($moduleName);
	}

	/**
	 * Загружает языковые константы по умолчанию из файлов вида lang.*.php для отдельного модуля.
	 * @param string $moduleName название модуля
	 */
	private function loadModuleDefaultLangConstants($moduleName) {
		$path = SYS_MODULES_PATH . $moduleName . '/lang.php';
		$this->loadLangConstantsFromFile($path, $moduleName);
	}

	/**
	 * Загружает языковые константы из файла вида lang.*.php.
	 * Переменные $LANG_EXPORT и $C_LANG наполняются языковыми константами из файла.
	 *
	 * @param string $path путь до файла с константами
	 * @param string $moduleName название модуля, если файл принадлежит модулю
	 */
	private function loadLangConstantsFromFile($path, $moduleName = '') {
		if (!file_exists($path)) {
			return;
		}

		$C_LANG = [];
		$LANG_EXPORT = [];

		/** @noinspection PhpIncludeInspection */
		require $path;

		if (isset($LANG_EXPORT) && is_array($LANG_EXPORT)) {
			foreach ($LANG_EXPORT as $key => $value) {
				$this->langConstants[$key] = $value;
			}
		}

		if ($moduleName === '') {
			return;
		}

		if (isset($C_LANG) && is_array($C_LANG)) {
			foreach ($C_LANG as $key => $value) {
				$this->langConstants[$moduleName][$key] = $value;
			}
		}
	}

	/**
	 * Загружает языковые константы для текущего языка из файлов вида lang.*.php для отдельного модуля.
	 * @param string $moduleName название модуля
	 */
	private function loadModuleLangConstantsWithPrefix($moduleName) {
		$path = SYS_MODULES_PATH . $moduleName . '/lang.' . $this->langPrefix . '.php';
		$this->loadLangConstantsFromFile($path, $moduleName);
	}

	/**
	 * Загружает языковые константы для шаблонов сайта из файлов вида lang.*.php. из расширения.
	 * @param string $moduleName название модуля
	 */
	public function loadModuleExtensionLangConstants($moduleName) {
		$pattern = SYS_MODULES_PATH . $moduleName . '/ext/lang.*.' . $this->langPrefix . '.php';
		$pathList = glob($pattern);

		if (!is_array($pathList)) {
			return;
		}

		foreach ($pathList as $path) {
			$this->loadLangConstantsFromFile($path, $moduleName);
		}
	}

	/** Загружает языковые константы по умолчанию из файлов вида lang.*.php. */
	private function loadDefaultLangConstants() {
		$path = SYS_MODULES_PATH . 'lang.' . $this->langPrefix . '.php';

		if (!file_exists($path)) {
			$path = SYS_MODULES_PATH . 'lang.php';
		}

		$this->loadLangConstantsFromFile($path);
	}
}
