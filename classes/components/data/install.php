<?php
	/** Установщик модуля */

	/** @var array $INFO реестр модуля */
	$INFO = [];
	$INFO['name'] = 'data';
	$INFO['config'] = '1';
	$INFO['default_method'] = 'empty';
	$INFO['default_method_admin'] = 'types';

	/** @var array $COMPONENTS файлы модуля */
	$COMPONENTS = [];
	$COMPONENTS[] = './classes/components/data/admin.php';
	$COMPONENTS[] = './classes/components/data/class.php';
	$COMPONENTS[] = './classes/components/data/customAdmin.php';
	$COMPONENTS[] = './classes/components/data/customMacros.php';
	$COMPONENTS[] = './classes/components/data/feeds.php';
	$COMPONENTS[] = './classes/components/data/fileManager.php';
	$COMPONENTS[] = './classes/components/data/forms.php';
	$COMPONENTS[] = './classes/components/data/handlers.php';
	$COMPONENTS[] = './classes/components/data/i18n.en.php';
	$COMPONENTS[] = './classes/components/data/i18n.php';
	$COMPONENTS[] = './classes/components/data/includes.php';
	$COMPONENTS[] = './classes/components/data/install.php';
	$COMPONENTS[] = './classes/components/data/lang.en.php';
	$COMPONENTS[] = './classes/components/data/lang.php';
	$COMPONENTS[] = './classes/components/data/macros.php';
	$COMPONENTS[] = './classes/components/data/permissions.php';

