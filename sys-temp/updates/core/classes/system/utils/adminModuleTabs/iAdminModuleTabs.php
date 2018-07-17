<?php
/**
	* Класс для управления табами в админке модулей.
	* Необходим для динамического изменния количества табов в модулях.
	* Должен быть доступен для подключаемых библиотек модуля при инициализации
	* При инициализации класса создается 2 экземпляра: 'common' и 'config', которые
	* содержат модифицируемый список табов для админки модуля и для конфигурации модуля.
	* После инициализации из шаблона вызывается макрос, который в зависимости от текущей страницы выбирает
	* необходимый экземпляр класса и выводит список табов для отрисовки.
*/
	interface iAdminModuleTabs {
		/**
			* Конструктор, создает класс определенного типа.
			* @param string $type = 'common' тип содержимого. Либо 'common', либо 'type'.
		*/
		public function __construct($type = 'common');
		
		/**
			* Добавить новый таб для метода $methodName
			* @param string $methodName название метода класса-модуля
			* @param array $aliases = NULL список методов-алиасов, при котором данный таб будет считаться активным (помимо $methodName)
			* @return bool результат операции
		*/
		public function add($methodName, $aliases = NULL);
		
		/**
			* Получить список алиасов для таба $methodName
			* @param string $methodName название метода класса-модуля
			* @return array|bool массив алиасов, либо false в случае ошибки
		*/
		public function get($methodName);
		
		/**
			* Получить основной метод таба по методу-алиасу, либо по его основному методу
			* @param string $methodOrAlias
			* @return string $methodName, либо false в случае ошибки
		*/

		public function getTabNameByAlias($methodOrAlias);

		/**
			* Удалить таб метода $methodName из списка табов
			* @param string $methodName название метода класса-модуля
			* @return bool результат операции
		*/
		public function remove($methodName);
		
		/**
			* Получить список всех табов
			* @return array массив, список всех табов
		*/
		public function getAll();
	}


