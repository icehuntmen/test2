<?php
	/**
	 * Пример конфигурации для пакера, не вносите сюда изменения.
	 *
	 * Обязательный ключи:
	 *
	 * 1) package — имя пакета, может содержать только латинские символы в нижнем регистре и символы нижнего подчёркивания.
	 * Имя пакета должно быть уникальным, рекомендуем добавлять к нему префикс с названием вашей организации;
	 *
	 * 2) destination — директория, в которую будет помещено решение;
	 *
	 * Необязательные ключи:
	 *
	 * 1) directories — список упаковываемых директорий (будут добавлены все файлы рекурсивно)*;
	 *
	 * 2) files — отдельные файлы, подлежащие упаковке*;
	 *
	 * 3) registry — ключи реестра, подлежащие экспорту (обязательно необходимо указать при экспорте модуля);
	 *
	 * 4) types — идентификаторы объектных типов данных, подлежащих экспорту. В экспорт попадут страницы или объекты,
	 * относящиеся к заданным типам.
	 *
	 * 5) fieldTypes — идентификаторы типов полей, подлежащих экспорту;
	 *
	 * 6) objects — идентификаторы объектов, подлежащих экспорту;
	 *
	 * 7) branchesStructure — идентификаторы корневых страниц, подлежащих экспорту.
	 * В экспорт попадут они и все их дочерние страницы;
	 *
	 * 8) langs — идентификаторы экспортируемых языков системы;
	 *
	 * 9) templates — идентификаторы экспортируемых шаблонов системы (файлы или директории шаблонов нужно указать явно
	 * в секции files или directories соответственно);
	 *
	 * 10) savedRelations - обозначения данных, которые так же нужно экспортировать при определенных действиях:
	 *
	 *     10.1) files - значения полей типов 'file', 'swf_file' и 'img_file' в виде файлов при экспорте страниц;
	 *     10.2) langs - языки при экспорте страниц;
	 *     10.3) domains - домены при экспорте страниц;
	 *     10.4) templates - шаблоны при экспорте страниц;
	 *     10.5) objects - объекты при экспорте страниц;
	 *     10.6) fields_relations - значения полей типа 'relation', 'optioned' и 'symlink' при экспорте страниц/объектов;
	 *     10.7) restrictions - ограничения значений полей при экспорте объектных типов данных;
	 *     10.8) permissions - права на страницы и модули при экспорте страниц и объектов, соответственно;
	 *     10.9) hierarchy - иерархические связи при экспорте страниц;
	 *     10.10) guides - объектные типы данных, их содержимое и связи справочника с полем при экспорте объектного типа
	 * с полям типа "relation",
	 *
	 * *Примечание: одно из полей "directories" или "files" должно быть обязательно заполнено.
	 *
	 *  Пример команды:
	 *
	 *  php libs/packer/run.php libs/packer/config.sample.php
	 *
	 *  Результат будет в директории /libs/packer/out/packer/.
	 *  Результатом является tar архив.
	 */
	return [
		'package' => 'Packer',
		'destination' => './libs/packer/out/packer/',
		'directories' => [
			'./libs/packer/class'
		],
		'files' => [
			'./libs/packer/config.sample.php',
			'./libs/packer/run.php',
		],
		'registry' => [
            'blogs20' => [
                'path' => 'modules/blogs20',
                'recursive' => true
            ]
        ],
		'types' => [
			8
		],
		'fieldTypes' => [
			20
		],
		'objects' => [
			4
		],
		'branchesStructure' => [
			3
		],
		'langs' => [
			1
		],
		'templates' => [
			1
		],
		'savedRelations' => [
			'fields_relations', 'files', 'hierarchy', 'permissions', 'guides'
		]
	];
