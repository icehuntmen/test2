<?php

	namespace UmiCms\Classes\Components\Seo;

	use UmiCms\System\Registry\iPart;
	use UmiCms\System\Interfaces\iYandexTokenInjector;

	/**
	 * Интерфейс реестра модуля "SEO"
	 * @package UmiCms\Classes\Components\Seo;
	 */
	interface iRegistry extends iPart, iYandexTokenInjector {}