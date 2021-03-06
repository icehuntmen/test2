<?php

$i18n = Array(
	"header-config-main"		=> "Основные настройки",
	"group-globals"			=> "Название сайта",
	"option-admin_email"		=> "E-mail администратора",
	"option-keycode"		=> "Доменный ключ",
	"option-chache_browser"		=> "Разрешить браузерам кэшировать страницы",
	"option-disable_url_autocorrection"	=> "Отключить автокоррекцию адресов",
	"option-disable_captcha"	=> "Отключить CAPTCHA",
	"option-show_macros_onerror" => "Показывать текст макроса при ошибке",
	"option-site_name"		=> "Название сайта",
	"option-ip_blacklist"	=> "Список IP-адресов, которым недоступен сайт",
	"option-session_lifetime"	=> "Таймаут неактивности пользователя",
	"option-busy_quota_files_and_images"	=> "Занятое дисковое пространство (суммарно для каталогов /files/ и /images/, Мб)",
	"option-busy_quota_files_and_images_percent"	=> "Процент занятого дискового пространства (суммарно для каталогов /files/ и /images/, Мб)",
	"option-busy_quota_uploads"	=> "Занятое дисковое пространство для директории /sys-temp/uploads/, Мб",
	"option-quota_uploads"	=> "Ограничение дискового пространства для директории /sys-temp/uploads/, Мб",
	"option-quota_files_and_images"	=> "Ограничение дискового пространства (суммарно для каталогов /files/ и /images/, Мб)",
	"option-timezones"				=> "Часовой пояс",
	"option-modules"				=> "Модуль администратора по умолчанию",

	"header-config-mails"		=> "Настройки исходящих писем",
	"option-email_from"		=> "E-mail отправителя",
	"option-fio_from"		=> "Имя отправителя",

	"header-config-social_networks"	=> "Social Networks",
	"header-config-memcached"	=> "Настройки подключения к memcached",
	"group-memcached"		=> "Memcached",
	"option-host"			=> "Хост",
	"option-port"			=> "Порт",
	"option-is_enabled"		=> "Использовать memcached",
	"option-status"			=> "Статус",
	"config-memcache-no-connection"	=> "Нет подключения",
	"config-memcache-disabled"	=> "Отключено",
	"config-memcache-used"		=> "Используется",

	"label-modules-list"		=> "Список установленных модулей",

	"label-install-path"		=> "Путь до инсталляционного файла",
	"label-install"			=> "Установить",
	"label-langs-list"		=> "Список языков",
	"label-lang-prefix"		=> "Префикс",
	"header-config-langs"		=> "Языки",

	"header-config-domains"		=> "Настройка доменов",
	"label-domain-address"		=> "Адрес домена",
	"label-domain-mirror-address" => "Адрес зеркала домена",
	"label-domain-lang"		=> "Язык по умолчанию",
	"label-mirrows"			=> "Свойства",

	"header-config-domain_del"	=> "Удаление домена",
	"error-can-not-delete-default-domain" => "Удаление основного домена запрещено.",

	"tabs-config-main"		=> "Глобальные",
	"tabs-config-modules"		=> "Модули",
	"tabs-config-langs"		=> "Языки",
	"tabs-config-domains"		=> "Домены",
	"tabs-config-memcached"		=> "Memcached",
	"tabs-config-mails"		=> "Почта",
	"tabs-config-watermark" => "Водяной знак",
	"tabs-config-security" => "Безопасность",

	"header-config-modules"		=> "Модули",
	"header-config-domain_mirrows"	=> "Свойства домена",
	"header-config-lang_del" => "Удаление языка",
	"option-upload_max_filesize" => "Максимальный размер загружаемого файла в PHP (Мб)",
	"option-max_img_filesize"	=> "Максимальный размер загружаемой фотографии (Мб)",
	"header-config-del_module" => "Удаление модуля",
	"header-config-security" => "Безопасность",

	"group-static"				=> "Настройки статического кэширования",
	"option-enabled"			=> "Включено",
	"option-expire"				=> "Время хранения кэша (обычно не имеет значения)",
	"cache-static-short"		=> "Короткий кэш, не более 10 минут",
	"cache-static-normal"		=> "Нормальный кэш, не более суток (рекомендуется)",
	"cache-static-long"			=> "Длинный кэш, не более месяца",
	"option-lock_duration"		=> "Продолжительность блокировки страницы (с)",
	"option-ga-id"				=> "Google Analytics Id",
	"option-expiration_control"	=> "Разрешить контроль актуальности контента",

	"header-config-branching"	=> "Оптимизация базы данных",
	"label-optimize-db"			=> "Оптимизировать",
	"header-config-add_module_do"	=> "Установка модуля",

	"label-watermark" => "Водяной знак",
	"header-config-watermark" => "Настройки водяного знака",
	"option-image" => "Накладываемое изображение",
	"option-scale" => "Относительный размер (например, 80)",
	"option-alpha" => "Прозрачность (100 — непрозрачный)",
	"option-valign" => "Вертикальное положение",
	"option-halign" => "Горизонтальное положение",
	"watermark-valign-center" => "Центр",
	"watermark-valign-top" => "Вверху",
	"watermark-valign-bottom" => "Внизу",
	"watermark-halign-right" => "Справа",
	"watermark-halign-left" => "Слева",
	"watermark-halign-center" => "Центр",

	"header-config-cache" => "Кэширование",
	"tabs-config-cache" => "Производительность",
	"group-engine" => "Обычное кэширование",
	"group-test" => "Тестирование",
	"group-security-audit" => "Аудит безопасности системы",
	"option-current-engine" => "Используемый кеширующий механизм",
	"option-cache-status" => "Статус кеширования",
	"cache-engine-on" => "Работает",
	"cache-engine-off" => "Не работает",
	"option-cache-size" => "Размер кеша",
	"cache-size-off" => "Невозможно определить",

	"option-engines" => "Список доступных кэширующих механизмов",

	"cache-engine-apc" => "APC",
	"cache-engine-eaccelerator" => "eAccelerator",
	"cache-engine-xcache" => "XCache",
	"cache-engine-memcache" => "Memcache (TCP/IP, автоопределение)",
	"cache-engine-shm" => "Shared memory (shm)",
	"cache-engine-fs" => "Файловая система",
	"cache-engine-database" => "База данных",
	"cache-engine-none" => "Недоступно",
	"cache-engine-redis" => "Redis",

	"group-streamscache" => "Кэширование макросов и протоколов для XSLT и PHP шаблонизаторов",
	"option-cache-enabled" => "Включено",
	"option-cache-lifetime" => "Время жизни кэша (в секундах)",

	"option-reset" => "Сбросить кэш",
	"group-seo" => "Настройки SEO",
	"group-additional" => "Дополнительные настройки",
	"option-seo-title" => "Префикс для TITLE",
	"option-seo-default-title" => "TITLE (по умолчанию)",
	"option-seo-keywords" => "Keywords (по умолчанию)",
	"option-seo-description" => "Description (по умолчанию)",

	"option-allow-alt-name-with-module-collision" => "Разрешить совпадение адреса страницы с названием модуля",
	"cache-engine-none"		=> "Не выбрано",
	"group-branching"		=> "Оптимизация базы данных",
	"option-branch"			=> "Оптимизировать БД",

	"js-config-optimize-db-header"     => "Оптимизация базы данных",
	"js-config-optimize-db-content"    => "<p>Перестраивается база данных для более оптимальной работы.<br />Это может занять некоторое время.</p>",

	"event-systemModifyElement-title" => "Отредактирована страница",
	"event-systemModifyElement-content" => "В страницу \"<a href=\"%page-link%\">%page-name%</a>\" внесены изменения",

	"event-systemCreateElement-title" => "Создана страница",
	"event-systemCreateElement-content" => "Создана новая страница \"<a href=\"%page-link%\">%page-name%</a>\"",

	"event-systemSwitchElementActivity-title" => "Изменена активность",
	"event-systemSwitchElementActivity-content" => "Изменена активность страницы \"<a href=\"%page-link%\">%page-name%</a>\"",

	"event-systemDeleteElement-title" => "Удалена страница",
	"event-systemDeleteElement-content" => "Удалена страница \"<a href=\"%page-link%\">%page-name%</a>\"",

	"event-systemMoveElement-title" => "Перемещена страница",
	"event-systemMoveElement-content" => "Перемещена страница \"<a href=\"%page-link%\">%page-name%</a>\"",

	"event-systemModifyObject-title" => "Отредактирован объект",
	"event-systemModifyObject-content" => "Отредактирован объект \"%object-name%\" типа \"%object-type%\"",

	'option-disable_too_many_childs_notification' => 'Отключить уведомление о большом количестве дочерних документов в структуре',

	'js-check-security'					=> 'Проверить безопасность',
	'js-index-security-fine'			=> 'Тест пройден',
	'js-index-security-problem'			=> 'Тест провален',
	'js-index-security-no'				=> 'Тестирование не проводилось',
	'option-security-UFS'				=> 'Протокол UFS закрыт',
	'option-security-UObject'			=> 'Протокол UObject закрыт',
	'option-security-DBLogin'			=> 'Подключение к БД не под root-ом',
	'option-security-DBPassword'		=> 'Пароль для БД не пустой',
	'option-security-ConfigIniAccess'	=> 'Доступ к файлу конфигурации закрыт',
	'option-security-FoldersAccess'		=> 'Доступ к системным папкам закрыт',
	'option-security-PhpDisabledForFiles' => 'Выполнение php скриптов в /files/',
	'option-security-PhpDelConnector' => 'Доступ к файлу php_for_del_connector.php',
	'js-sitemap-ok' => 'OK',
	'js-sitemap-ajax-error' => 'Возникла ошибка при получении данных от сервера.',
	'js-sitemap-updating-complete' => 'Обновление Sitemap.xml завершено успешно.',
	'js-label-stop-and-close' => 'Остановить и закрыть',
	'group-mails' => 'Настройки почты',
	'group-watermark' => 'Настройки водяного знака',
	'js-current-watermark' => 'Для предпросмотра актуального водяного знака сохраните изменения',
	"label-extensions-list" => "Список установленных расширений",
	"js-label-component-installed" => "Компонент установлен",
	"js-label-component-install" => "Установка компонента ",
	"tabs-config-extensions" => "Расширения",
	"header-config-extensions" => "Расширения",
	'extension-list-available-for-installing' => 'Список расширений, доступных для установки',
	'all-available-extensions-installed' => 'Все доступные расширения установлены',
	'module-list-available-for-installing' => 'Список модулей, доступных для установки',
	'all-available-modules-installed' => 'Все доступные модули установлены',
	'js-label-stop-in-demo' => 'В демонстрационном режиме эта функция недоступна',
	'error-label-available-module-list' => 'Не удалось получить список из-за ошибки:',
	'group-browser' => 'Браузерный кеш',
	'option-current-browser-cache-engine' => "Выбранный способ кеширования",
	'option-browser-cache-engine' => "Список доступных способов кеширования",
	'None-browser-cache' => 'Кеш браузера отключен',
	'LastModified-browser-cache' => 'Заголовок "Last-Modified"',
	'EntityTag-browser-cache' => 'Заголовок "ETag"',
	'Expires-browser-cache' => 'Заголовок "Expires"',
);
?>
