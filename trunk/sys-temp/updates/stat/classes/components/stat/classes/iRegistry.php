<?php

	namespace UmiCms\Classes\Components\Stat;

	use UmiCms\System\Registry\iPart;
	use UmiCms\System\Interfaces\iYandexTokenInjector;

	/**
	 * Интерфейс реестра модуля "Статистика"
	 * @package UmiCms\Classes\Components\Stat;
	 */
	interface iRegistry extends iPart, iYandexTokenInjector {}