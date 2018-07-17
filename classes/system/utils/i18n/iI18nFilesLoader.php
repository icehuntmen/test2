<?php

namespace UmiCms\Classes\System\Utils\I18n;

/** Интерфейс загрузчика языковых констант */
interface iI18nFilesLoader {

	/**
	 * Загружает языковые константы из файлов вида lang.*.php и возвращает их
	 * @return array
	 *
	 * [
	 *
	 *     'key' => %value%,
	 *     ...
	 *     'moduleName' => [
	 *         'key' => %value%,
	 *         ...
	 *     ],
	 *     ...
	 * ]
	 *
	 */
	public function loadLangConstants();
}
