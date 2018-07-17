"use strict";
/**
 * Функционал отдельной установки компонентов
 * @constructor
 */
function ComponentInstaller() {
	/** @type {String} путь установщика */
	this.installerUri = '/smu/installer.php';
	/** @type {String} идентификатор диалогового окна */
	this.dialogName = 'install-component';
	/** @type {String} селектор контейнера текста диалогового окна */
	this.dialogTextSelector = '.popupText';
	/** @type {String} селектор контейнера флага "используется последняя версия системы" */
	this.isLastVersionSelector = 'div[data-is-last-version]';
	/** @type {String} селектор кнопок, которые запускают установку компонентов */
	this.buttonsSelector = 'a[data-component]';
	/** @type {String} название заключительного шага из списка шагов установщика */
	this.finalStep = 'cleanup';
	/** @type {Number} DIALOG_WINDOW_WIDTH ширина диалоговых окон */
	this.DIALOG_WINDOW_WIDTH = 350;
	/** @type {Number} MILLISECONDS_TO_WAIT_REQUEST время ожидания завершения предыдущего шага установки */
	this.MILLISECONDS_TO_WAIT_REQUEST = 100;
	/** @type {Number} MILLISECONDS_TO_CLOSE_DIALOG время ожидания перед закрытием окна с прогрессом установки */
	this.MILLISECONDS_TO_CLOSE_DIALOG = 1500;

	/** @type {Array} список шагов установщика для инициализации */
	this.initSteps = [
		'download-service-package',
		'extract-service-package',
		 this.finalStep
	];
	/** @type {Array} список шагов установщика для установки компонента */
	this.installSteps = [
		'check-user',
		'get-update-instructions',
		'download-component',
		'extract-component',
		'install-component',
		'execute-component-manifest',
		this.finalStep
	];
	/** @type {Array} список идентификаторов отложенных запусков выполнения шагов установщика */
	this.stepTimeoutIdList = [];
	/** @type {Boolean} флаг блокировки выполнения шага установщика */
	this.lock = false;

	/** Конструктор */
	this.construct = function() {
		if (!this.isDemoMode()) {
			this.init();
		}

		this.bindButtons();
	};

	/** Запускает инициализацию установщика */
	this.init = function() {
		var that = this;

		$.each(this.initSteps, function(index) {
			that.waitRequest(that.initSteps[index], null);
		});
	};

	/**
	 * Определяет работает ли система в демонстрационном режиме
	 * @returns {Boolean}
	 */
	this.isDemoMode = function() {
		return uAdmin.data && !!uAdmin.data['demo'];
	};

	/** Прикрепляет обработчик нажатия к кнопкам запуска установки компонентов */
	this.bindButtons = function() {
		var that = this;

		this.getButtons().on('click', function(){
			if (that.isDemoMode()) {
				return that.showDemoModeNotify();
			}

			var params = {
				'component' : $(this).data('component'),
				'is_extension' : Number($(this).data('extension') == '1')
			};

			that.handleButton(params);
		});
	};

	/** Показывает уведомление, что действие запрещено в демонстрационном режиме */
	this.showDemoModeNotify = function(){
		$.jGrowl(getLabel('js-label-stop-in-demo'), {
			'header': 'UMI.CMS',
			'life': 10000
		});
	};

	/**
	 * Возвращает кнопки запуска установки компонентов
	 * @returns {jQuery|HTMLElement}
	 */
	this.getButtons = function() {
		return $(this.buttonsSelector);
	};

	/**
	 * Обработчик нажатия на кнопку установки компонента
	 * @param {Object} params параметры устанавливаемого компонента
	 */
	this.handleButton = function(params) {
		this.unbindButtons();
		this.startInstall(params);
		this.bindButtons();
	};

	/**
	 * Запускает установку компонента
	 * @param {Object} params параметры устанавливаемого компонента
	 */
	this.startInstall = function(params) {
		var that = this;

		if (!this.isLastVersion()) {
			return this.showDisclaimer(function(){
				that.install(params);
			});
		}

		that.install(params);
	};

	/**
	 * Показывает предупреждение, что установлена не последняя версия
	 * @param {Function} callback обработчик нажатия кнопки "Игнорировать"
	 */
	this.showDisclaimer = function(callback) {
		openDialog('', getLabel('js-label-warning'), {
			name: 'disclaimer',
			width: this.DIALOG_WINDOW_WIDTH,
			html: getLabel('js-label-not-last-version-warning-text'),
			confirmText: getLabel('js-label-continue'),
			cancelButton: true,
			confirmCallback: function() {
				closeDialog('disclaimer');
				callback();
			}
		});
	};

	/**
	 * Устанавливает компонент
	 * @param {Object} params параметры устанавливаемого компонента
	 */
	this.install = function(params) {
		this.openDialog(getLabel('js-label-component-install') + params['component']);
		var that = this;

		$.each(this.installSteps, function(index) {
			that.waitRequest(that.installSteps[index], params);
		});
	};

	/**
	 * Ожидает завершения предыдущего шага установки и запускает заданный шаг
	 * @param {String} step название шага, который требуется запустить
	 * @param {Object} params параметры устанавливаемого компонента
	 */
	this.waitRequest = function(step, params) {
		var that = this;

		var timeoutId = setTimeout(function() {
			if (that.lock === true) {
				return that.waitRequest(step, params);
			}

			that.lock = true;
			that.sendRequest(step, params);
		}, this.MILLISECONDS_TO_WAIT_REQUEST);

		this.stepTimeoutIdList.push(timeoutId);
	};

	/**
	 * Отправляет запрос к установщику
	 * @param {String} step название шага, который требуется запустить
	 * @param {Object} params параметры устанавливаемого компонента
	 */
	this.sendRequest = function(step, params) {
		var that = this;
		var request = {
			step: step,
			guiUpdate: 'true',
			manifest_config_name: 'install'
		};

		request = $.extend(request, params);

		$.post(this.installerUri, request, function(response) {
			that.handleResponse(step, params, response);
		}).fail(function() {
			that.endInstall(getLabel('js-label-installation-unavailable'));
		});
	};

	/**
	 * Обрабатывает ответ установщика
	 * @param {String} step название шага, который был выполнен
	 * @param {Object} params параметры устанавливаемого компонента
	 * @param {Object} response ответ установщика
	 */
	this.handleResponse = function(step, params, response) {
		var state = $('install', response).attr('state');
		var isError = typeof state === 'undefined';

		if (isError) {
			return this.endInstall($('error', response).attr('message'));
		}

		var $messages = $('message', response);
		var that = this;

		$.each($messages, function(index) {
			that.pushToDialog($($messages[index]).text());
		});

		if (state !== 'done') {
			return this.sendRequest(step, params);
		}

		this.lock = false;

		if (this.isFinalStep(step)) {
			this.endInstall(getLabel('js-label-component-installed'));
		}
	};

	/**
	 * Завершает установку
	 * @param {String} message сообщение
	 */
	this.endInstall = function(message) {
		this.clearTimeout();
		this.lock = false;
		this.pushToDialog(message);
		this.closeDialog();
	};

	/** Удаляет все отложенные выполнения шагов установщика */
	this.clearTimeout = function() {
		var that = this;

		$.each(this.stepTimeoutIdList, function(index) {
			clearTimeout(that.stepTimeoutIdList[index]);
		});
	};

	/**
	 * Определяет является ли заданный шаг финальным
	 * @param {String} step название проверяемого шага
	 * @returns {Boolean}
	 */
	this.isFinalStep = function(step) {
		return step === this.finalStep;
	};

	/**
	 * Определяет используется ли последняя версия системы
	 * @returns {Boolean}
	 */
	this.isLastVersion = function() {
		return $(this.isLastVersionSelector).data('is-last-version') === 1;
	};

	/**
	 * Помещает сообщение в диалоговое окно
	 * @param {String} message сообщение
	 */
	this.pushToDialog = function(message) {
		var dialog = this.getDialog();

		if (!dialog) {
			return;
		}

		$(this.dialogTextSelector, dialog.id).html(message);
	};

	/**
	 * Открывает диалоговое окно
	 * @param {String} title заголовок и первоначальный контент
	 */
	this.openDialog = function (title){
		openDialog('', title, {
			name: this.dialogName,
			width: this.DIALOG_WINDOW_WIDTH,
			html: title,
			stdButtons: false,
			closeButton: false
		});
	};

	/** Закрывает диалоговое окно */
	this.closeDialog = function() {
		var that = this;

		if (this.getDialog()) {
			setTimeout(function(){
				closeDialog(that.dialogName);
				window.location.reload();
			}, this.MILLISECONDS_TO_CLOSE_DIALOG);
		}
	};

	/**
	 * Возвращает диалоговое окно
	 * @returns {*}
	 */
	this.getDialog = function() {
		return getPopupByName(this.dialogName);
	};

	/** Открепляет обработчик нажатия от кнопок запуска установки компонентов */
	this.unbindButtons = function() {
		this.getButtons().off('click');
	};
}

$(function() {
	var installer = new ComponentInstaller();
	installer.construct();
});