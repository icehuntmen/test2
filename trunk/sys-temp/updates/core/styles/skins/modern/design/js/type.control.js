/** Контрол редактирования типа данных */
var modernTypeController = (function(jQuery) {
	var $ = jQuery || {},
			typeId = null,
			editableGroupsModel = {},
			restrictionLoaded = false,
			typesList = [],
			guidesList = [],
			restrictionsList = [],
			groupsContainer,    // Линк на контейнер со списком групп
			self = this;

	this.fields = [];
	this.groups = [];

	/**
	 * Возвращает заголовок для окна редактирования поля/группы
	 * @param {String} title заголовок поля
	 * @param {String} name имя поля
	 * @returns {string}
	 */
	var getEditWindowTitle = function(title, name) {
		return [
			title, '[', name, ']'
		].join('');
	};

	/**
	 * Инициализирует элементы формы всплывающего окна
	 * @param {Element|jQuery|String} scope элемент окна
	 */
	var initElements = function(scope) {
		var nameInput = $("#newname", scope);
		var titleInput = $("#newtitle", scope);

		$('.checkbox input:checked', scope).parent().addClass('checked');
		$('.checkbox', scope).click(function() {
			$(this).toggleClass('checked');
		});

		titleInput.focus(function(event) {
			$(this).select();

			if (!nameInput.val().length) {
				$(event.currentTarget).bind('keyup', {nameField: nameInput}, universalTitleConvertCallback);
			}
		}).blur(function(event) {
			$(event.currentTarget).unbind('keyup', universalTitleConvertCallback);
		});

		titleInput.focus();
	};

	/**
	 * Отображает сообщение-уведомление
	 * @param {String} message текст сообщения
	 * @param {String} title заголовок сообщения
	 */
	var showMessage = function(message, title) {
		title = title || 'UMI.CMS';
		message = typeof message == 'string' ? message : '';

		if (!jQuery.jGrowl) {
			return;
		}

		jQuery.jGrowl(message, {header: title});
	};

	var isWysiwygLoaded = false;

	/** Инициализирует поля типа WYSIWYG */
	var initWysiwyg = function() {
		if (!isWysiwygLoaded) {
			uAdmin.reg = {};
			$('head').append('<script type="text/javascript" src="/js/cms/wysiwyg/wysiwyg.js" />');
			uAdmin('settings', {
				min_height: 100
			}, 'wysiwyg');
			uAdmin('type', 'tinymce47', 'wysiwyg');
			uAdmin.load(uAdmin);
		}

		if (!uAdmin.wysiwyg) {
			return;
		}

		isWysiwygLoaded = true;
		uAdmin.wysiwyg.init();
	};

	/**
	 * Возвращает элемент, в котором выводятся данные поля
	 * @param {Number} fieldId ID поля
	 * @param {String} elementType тип данных
	 * @returns {*|jQuery|HTMLElement}
	 */
	var getFieldElement = function(fieldId, elementType) {
		var prefix = '#headf';
		var elementId = prefix + fieldId + elementType;
		return $(elementId, groupsContainer);
	};

	/**
	 * Применяет изменения к верстке при изменении данных поля
	 * @param {JSON} response ответ от сервера при успешном изменении данных поля
	 */
	var applyFieldChanges = function(response) {
		var data = response['data'] || {};

		if (!data.field || !data.field.id) {
			return;
		}
		var field = data.field;
		var fieldId = field.id;
		var title = field['title'];
		var name = field['name'];
		var typeName = field['type']['name'];
		var required = !!field['required'] ? ' *' : '';

		var fieldElement = $('li[umifieldid=' + fieldId + ']', groupsContainer);
		var invisibleClass = 'finvisible';

		if (fieldElement.length) {
			fieldElement.removeClass(invisibleClass);

			if (!field['visible']) {
				fieldElement.addClass(invisibleClass);
			}
		}

		var titleElement = getFieldElement(fieldId, 'title');
		titleElement.text(title + required);
		titleElement.attr('title', title + required);

		var nameElement = getFieldElement(fieldId, 'name');
		nameElement.text('[' + name + ']');
		nameElement.attr('title', name);

		var typeNameElement = getFieldElement(fieldId, 'type');
		typeNameElement.text('(' + typeName + ')');
		typeNameElement.attr('title', typeName);
	};

	/**
	 * Применяет изменения к верстке при изменении данных группы полей
	 * @param {JSON} response ответ от сервера при успешном изменении данных группы
	 */
	var applyGroupChanges = function(response) {
		var data = response['data'] || {};

		if (!data.group || !data.group.id) {
			return;
		}
		var group = data.group;
		var groupId = group.id;
		var groupElement = $('div[umigroupid=' + groupId + ']', groupsContainer);

		if (!groupElement.length) {
			return;
		}

		var titleId = '#headg' + groupId + 'title';
		var titleElement = $(titleId, groupsContainer);
		titleElement.text(getEditWindowTitle(group['title'], group['name']));
	};

	/**
	 * Переназначает обработчик события нажатия по элементам
	 * @param {Element|jQuery|String} elements DOM-элементы
	 * @param {Function} handler обработчик события нажатия
	 */
	var rebindClickHandler = function(elements, handler) {
		var $elements = $(elements);

		if (!$elements.length) {
			return;
		}

		$elements.unbind('click');
		$elements.bind('click', handler);
	};

	/**
	 * Вовзвращает содержимое поля типа WYSIWYG
	 * @param {Element|jQuery|String} field исходный элемент поля (например, textarea)
	 * @returns {*}
	 */
	var getWysiwygContent = function(field) {
		if (typeof tinyMCE == 'undefined' || !tinyMCE) {
			return null;
		}

		field = $(field);

		if (!field.length) {
			return null;
		}

		var fieldId = field.attr('id');
		return tinyMCE.get(fieldId) ? tinyMCE.get(fieldId).getContent() : null;
	};

	/**
	 * Возвращает данные сущности
	 * @param {Number} entityId ID сущности
	 * @param {String} entityName имя сущности
	 * @param {Array} entityContainer массив, в котором хранятся данные сущностей
	 * @returns {*}
	 */
	var getEntityData = function(entityId, entityName, entityContainer) {
		return entityContainer[entityId] || processEntityData(function(jsonPath, data) {
					var entitiesList = jsonPath({
						path: '$..' + entityName + '[?(@.id=="' + entityId + '")]',
						json: data
					});

					return entitiesList.length > 0 ? entitiesList[0] : null;
				});
	};

	/**
	 * Возвращает данные поля
	 * @param {Number} fieldId ID поля
	 * @returns {*}
	 */
	var getFieldData = function(fieldId) {
		return getEntityData(fieldId, 'field', self.fields);
	};

	/**
	 * Сохраняет данные сущности
	 * @param {JSON} data данные от сервера при сохранении сущности
	 * @param {String} entityName имя сущности
	 * @param {Array} entityContainer массив, в который нужно сохранить данные сущности
	 */
	var saveEntityData = function(data, entityName, entityContainer) {
		data = data || {};
		if (!data['data'] || !data['data'][entityName]) {
			return;
		}

		var entity = data['data'][entityName]
		entityContainer[entity.id] = entity;
	};

	/**
	 * Сохраняет данные поля
	 * @param {JSON} json данные от сервера при сохранении поля
	 */
	var saveFieldData = function(json) {
		saveEntityData(json, 'field', self.fields);
	};

	/**
	 * Возвращает данные группы полей
	 * @param {Number} groupId ID группы полей
	 * @returns {*}
	 */
	var getGroupData = function(groupId) {
		var groupInfo = getEntityData(groupId, 'group', self.groups);

		if (groupInfo['tip']) {
			groupInfo['tip'] = groupInfo['tip'].replace(/\\"/gi, '"');
			groupInfo['tip'] = groupInfo['tip'].replace(/\\n/gi, '<br />');
		}

		return groupInfo;
	};

	/**
	 * Сохраняет данные группы полей
	 * @param {JSON} json данные от сервера при сохранении группы полей
	 */
	var saveGroupData = function(json) {
		saveEntityData(json, 'group', self.groups);
	};

	/**
	 * Сохранение изменений или добавление группы параметров
	 * @param id ид группы
	 * @param options параметры группы в формате { 'data[%param name%]' : %value%, ... }
	 */
	var saveGroup = function(id, options) {
		var param = options;
		param['csrf'] = csrfProtection.getToken();

		if (id == 'new') {
			$.post("/admin/data/type_group_add/" + modernCurentType + "/do/.json?noredirect=true",
					param,
					function(response) {
						showMessage(getLabel('js-group-creating-success'), getLabel('js-group-creating-title'));
						saveGroupData(response);
						if (response.data.group !== undefined) {
							addGroupToContainer(response.data.group);
							actor.initGroupsSorting();
						}
					},
					'json');
		} else {
			$.post("/admin/data/type_group_edit/" + id + "/" + modernCurentType + "/do/.json?noredirect=true",
					param,
					function(data) {
						applyGroupChanges(data);
						saveGroupData(data);
						showMessage(getLabel('js-group-updating-success'), getLabel('js-group-updating-title'));
					},
					'json'
			);
		}
	};

	/**
	 * Сохраняет изменение существующего поля или добавляет новое поле
	 * @param {String|Integer} id идентификатор существующего поля или ключевое слово "new"
	 * @param {Array} fieldProperties параметры поля
	 * @param {Integer} groupId идентификатор группы поля
	 * @param {Function} success callback успешного добавления или изменения поля
	 */
	var saveField = function(id, fieldProperties, groupId, success) {
		success = (typeof success === 'function') ? success : function() {};
		var requestData = fieldProperties;
		requestData['csrf'] = csrfProtection.getToken();

		if (id == 'new') {
			checkSameFieldExists(requestData, groupId, success);
			return;
		}

		$.post(
			"/admin/data/type_field_edit/" + id + "/" + typeId + "/do/.json?noredirect=true",
			requestData,
			function(data) {
				applyFieldChanges(data);
				saveFieldData(data);
				showMessage(getLabel('js-field-updating-success'), getLabel('js-field-updating-title'));
				success();
			},
			"json"
		).error(function(rq, status, err) {
			showMessage(getLabel('js-error-occurred') + ' <br />"' + err + '".', getLabel('js-error-header'));
		});
	};

	/**
	 * Отправляет запрос на проверку данных поля на предмет того, что среди связанных типов данных
	 * уже есть подходящее поле.
	 * Если похожих полей нет - запускает создание поле, иначе предлагает показывает окно с выбором операции
	 * (прикрепить найденное поле или все же создать новое).
	 * @param {Array} requestData параметры поля и запроса
	 * @param {Integer} groupId идентификатор группы поля
	 * @param {Function} success callback успешного добавления поля (путем создания и прикрепления)
	 */
	var checkSameFieldExists = function(requestData, groupId, success) {
		$.post(
			"/admin/data/getSameFieldFromRelatedTypes/" + typeId + "/.json",
			requestData,
			function (response) {
				if (typeof response.data.fieldId === 'object') {
					return createField(requestData, groupId, success);
				}

				var foundFieldId = response.data.fieldId;
				var message = response.data.message;

				openDialog('', getLabel('js-label-found-similar-field'), {
					html: message,
					width: 360,
					cancelButton: true,
					confirmText: getLabel('js-label-attach-field'),
					cancelText: getLabel('js-label-create-new-field'),
					customClass: 'modalUp',
					confirmCallback: function(popupName) {
						attachField(foundFieldId, groupId, success);
						closeDialog(popupName);
					},
					cancelCallback: function (popupName) {
						createField(requestData, groupId, success);
						closeDialog(popupName);
					}
				});
			},
			'json'
		);
	};

	/**
	 * Отправляет запрос на создание нового поля.
	 * При успешном создании запускает callback.
	 * @param {Array} requestData параметры поля и запроса
	 * @param {Integer} groupId идентификатор группы поля
	 * @param {Function} success callback успешного добавления или изменения поля
	 */
	var createField = function(requestData, groupId, success) {
		// Не прерывать добавление поля в дочерние типы данных, если встретился дочерний тип без группы для поля
		requestData['ignoreChildGroup'] = true;
		$.post(
			"/admin/data/type_field_add/" + groupId + "/" + typeId + "/do/.json?noredirect=true",
			requestData,
			function(data) {
				if (data.data && data.data.error === undefined) {
					saveFieldData(data);
					showMessage(getLabel('js-field-creating-success'), getLabel('js-field-creating-title'));
					success();
					drawFieldInsideGroup(groupId, data.data.field);
				} else {
					var errorText = getLabel('js-error-message') + ' "' + data.data.error + '".';
					var errorTitle = getLabel('js-field-creating-error');
					showMessage(errorText, errorTitle);
				}
			},
			'json'
		);
	};

	/**
	 * Отравляет запрос на прикрепление поля к заданной группе в текущем типе.
	 * При успешном создании запускает callback.
	 * @param {Integer} fieldId идентификатор прикрепляемого поля
	 * @param {Integer} groupId идентификатор группы поля
	 * @param {Function} success callback успешного добавления или изменения поля
	 */
	var attachField = function (fieldId, groupId, success) {
		$.post(
			"/admin/data/attachField/" + typeId + "/" + groupId + "/" + fieldId + "/.json",
			{
				'csrf' : csrfProtection.getToken()
			},
			function(data) {
				if (data.data && data.data.error === undefined) {
					saveFieldData(data);
					showMessage(getLabel('js-field-attaching-success'), getLabel('js-field-attaching-title'));
					success();
					drawFieldInsideGroup(groupId, data.data.field);
				} else {
					var errorText = getLabel('js-error-message') + ' "' + data.data.error + '".';
					var errorTitle = getLabel('js-field-attaching-error');
					showMessage(errorText, errorTitle);
				}
			},
			'json'
		);
	};

	/** Обработчик события редактирования поля */
	var editFieldHandler = function() {
		var fieldId = $(this).attr('data');
		var fieldInfo = getFieldData(fieldId);

		if (!fieldInfo) {
			throw 'Can\'t find data for field #' + fieldId;
		}

		var windowTitle = getEditWindowTitle(fieldInfo['title'], fieldInfo['name']);
		var restriction = fieldInfo['restriction'] || {};

		openDialog('', windowTitle, {
			html: getFieldForm({
				id: fieldId,
				title: fieldInfo['title'] || '',
				typeId: fieldInfo['field-type-id'] || '',
				name: fieldInfo['name'] || '',
				tip: fieldInfo['tip'] || '',
				visible: !!fieldInfo['visible'],
				indexable: !!fieldInfo['indexable'],
				required: !!fieldInfo['required'],
				filterable: !!fieldInfo['filterable'],
				isImportant: !!fieldInfo['important']
			}),
			width: 360,
			cancelButton: true,
			confirmText: getLabel('js-save-button'),
			cancelText: getLabel('js-cancel'),
			customClass: 'modalUp',
			confirmCallback: function(popupName, scope) {
				var params = {
					'data[title]': $('#newtitle', scope).val(),
					'data[name]': $('#newname', scope).val(),
					'data[tip]': $('#newtip', scope).val(),
					'data[field_type_id]': $('#' + fieldId + 'type', scope).val(),
					'data[restriction_id]': $('#' + fieldId + 'restriction', scope).val(),
					'data[guide_id]': $('#' + fieldId + 'guide', scope).val()
				};
				if ($('#newvisible', scope).is(':checked')) {
					params['data[is_visible]'] = 1;
				}
				if ($('#newindexable', scope).is(':checked')) {
					params['data[in_search]'] = 1;
				}
				if ($('#newrequired', scope).is(':checked')) {
					params['data[is_required]'] = 1;
				}
				if ($('#newfilterable', scope).is(':checked')) {
					params['data[in_filter]'] = 1;
				}

				if ($('#newIsImportant', scope).is(':checked')) {
					params['data[is_important]'] = 1;
				}

				closeDialog(popupName);
				saveField(fieldId, params);
			},
			openCallback: function(scope) {

				initFieldForm({
							id: fieldId,
							typeId: fieldInfo['field-type-id'],
							restrictionId: restriction.id,
							guideId: fieldInfo['guide-id']
						}, scope
				);

				initElements(scope);
			}
		});
	};

	/** Обработчик события редактирования группы полей */
	var editGroupHandler = function() {
		var id = $(this).attr('data');
		var groupInfo = getGroupData(id);
		var title = getEditWindowTitle(groupInfo['title'], groupInfo['name']);

		openDialog('', title, {
			html: getGroupForm(groupInfo),
			cancelButton: true,
			zIndex: 999,
			width: 700,
			confirmText: getLabel('js-save-button'),
			cancelText: getLabel('js-cancel'),
			openCallback: function(scope) {
				initWysiwyg();
				initElements(scope);
			},
			confirmCallback: function(popupName, scope) {
				var tip = getWysiwygContent($('.tip', scope));
				var params = {
					'data[title]': $('#newtitle').val(),
					'data[name]': $('#newname').val(),
					'data[tip]': tip,
					'data[is_visible]': $('#newvisible').is(':checked') ? 1 : 0
				};

				saveGroup(id, params);
				closeDialog(popupName);
			}
		});
	};

	/** Обработчик события удаления группы полей */
	var removeGroupHandler = function(event) {
		var id = $(event.target).parent().attr('data');
		removeGroup(id);
	};

	/**
	 * Рисует интерфейс редактирования поля в заданной группе полей
	 * @param {Integer} groupId идентификатор группы полей
	 * @param {Array} fieldProperties данные поля
	 */
	var drawFieldInsideGroup = function(groupId, fieldProperties) {
		var typeId = (fieldProperties.type ? fieldProperties.type.id : '');
		var typeName = (fieldProperties.type ? fieldProperties.type.name : '');

		var str = [
			'<li class="f_container' + (fieldProperties.visible == 'visible' ? '' : ' finvisible') + '" umifieldid="' + fieldProperties.id + '">',
			'<div class="row">',
			'<div class="view col-md-12">',
			'<span id="headf' + fieldProperties.id + 'title" class="col-md-3 field-title" style="overflow:hidden; ">' + fieldProperties.title,
			(fieldProperties.required == "required" ? " *" : ""),
			'</span>',
			'<span id="headf' + fieldProperties.id + 'name" class="col-md-3 field-name" style="overflow:hidden; ">[' + fieldProperties.name + ']</span>',
			'<span id="headf' + fieldProperties.id + 'type" class="col-md-4 field-type" style="overflow:hidden; ">(' + typeName + ')</span>',
			'<span id="f' + fieldProperties.id + 'save" class="col-md-2" style="display:none;"></span>',
			'<span id="f' + fieldProperties.id + 'control" class="pull-right">',
			'<a class="fedit" data="' + fieldProperties.id + '" title="' + getLabel('label-edit') + '"><i class="small-ico i-edit"></i></a>',
			'<a class="fremove" data="' + fieldProperties.id + '" title="' + getLabel('label-delete') + '"><i class="small-ico i-remove"></i></a>',
			'</span>',
			'</div>',
			'<div class="edit col-md-12" style="display: none;">',
			'<form>',
			'<div class="row">',
			'<div class="col-md-6">',
			'<div class="title-edit" >' + getLabel("js-type-edit-title") + '</div>',
			'<input type="text" class="default" id="' + fieldProperties.id + 'title" name="data[title]" value="' + fieldProperties.title + '" />',
			'</div>',
			'<div class="col-md-6">',
			'<div class="title-edit">' + getLabel("js-type-edit-name") + '</div>',
			'<input type="text" class="default" id="' + fieldProperties.id + 'name" name="data[name]" value="' + fieldProperties.name + '" />',
			'</div>',
			'</div>',
			'<div class="row">',
			'<div class="col-md-6">',
			'<div class="title-edit" >' + getLabel("js-type-edit-tip") + '</div>',
			'<input type="text" class="default" id="tip" name="data[tip]" value="' + fieldProperties.tip + '" />',
			'</div>',
			'<div class="col-md-6">',
			'<div class="title-edit">' + getLabel("js-type-edit-type") + '</div>',
			'<select id="' + fieldProperties.id + 'type" name="data[field_type_id]">',
			'<option value="' + typeId + '">' + typeName + '</option>',
			'</select>',
			'</div>',
			'</div>',
			'<div class="row">',
			'<div class="col-md-6">',
			'<div class="title-edit">' + getLabel("js-type-edit-restriction") + '</div>',
			'<select id="' + fieldProperties.id + 'restriction" name="data[restriction_id]">',
			'</select>',
			'</div>',
			'<div class="col-md-6" id="' + fieldProperties.id + 'guideCont" style="display:none;">',
			'<div class="title-edit">' + getLabel("js-type-edit-guide") + '</div>',
			'<select id="' + fieldProperties.id + 'guide" name="data[guide_id]"></select>',
			'</div>',
			'</div>',
			'<div class="row">',
			'<div class="col-md-6">',
			'<div class="checkbox-wrapper">',
			'<div class="checkbox ' + (fieldProperties.visible == 'visible' ? 'checked' : '') + '">',
			'<input type="hidden" name="data[is_visible]" value="1" ' + (fieldProperties.visible == 'visible' ? 'checked' : '') + ' />',
			'<input type="checkbox" id="' + fieldProperties.id + 'visible" name="data[is_visible]" value="1" class="checkbox" ' + (fieldProperties.visible == "visible" ? "checked" : "") + ' >',
			'</div>',
			'<span>' + getLabel("js-type-edit-visible") + '</span>',
			'</div>',
			'</div>',
			'<div class="col-md-6">',
			'<div class="checkbox-wrapper">',
			'<div class="checkbox ' + (fieldProperties.indexable == "indexable" ? "checked" : "") + '">',
			'<input type="hidden" name="data[in_search]" value="1" ' + (fieldProperties.indexable == "indexable" ? "checked" : "") + '/>',
			'<input type="checkbox" id="' + fieldProperties.id + 'indexable" name="data[in_search]" value="1" class="checkbox" ' + (fieldProperties.indexable == "indexable" ? "checked" : "") + ' />',
			'</div>',
			'<span>' + getLabel("js-type-edit-indexable") + '</span>',
			'</div>',
			'</div>',
			'</div>',
			'<div class="row">',
			'<div class="col-md-6">',
			'<div class="checkbox-wrapper">',
			'<div class="checkbox ' + (fieldProperties.required == "required" ? "checked" : "") + '">',
			'<input type="checkbox" id="' + fieldProperties.id + 'required" name="data[is_required]" value="1" class="checkbox" ' + (fieldProperties.required == "required" ? "checked" : "") + '/>',
			'</div>',
			'<span>' + getLabel("js-type-edit-required") + '</span>',
			'</div>',
			'</div>',
			'<div class="col-md-6">',
			'<div class="checkbox-wrapper">',
			'<div class="checkbox ' + (fieldProperties.filterable == "filterable " ? "checked" : "") + '">',
			'<input type="checkbox" id="' + fieldProperties.id + 'filterable" name="data[in_filter]" value="1" class="checkbox" ' + (fieldProperties.filterable == "filterable " ? "checked" : "") + ' />',
			'</div>',
			'<span>' + getLabel("js-type-edit-filterable") + '</span>',
			'</div>',
			'</div>',
			'</div>',
			'<div class="row" style="padding-bottom: 10px;">',
			'<div class="pull-right buttons" style="">',
			'<input type="button" data="' + fieldProperties.id + '" value="' + getLabel("js-data-edit-field") + '" class="fsave btn color-blue"/>',
			'<input type="button"" data="' + fieldProperties.id + '" value="' + getLabel("js-trash-confirm-cancel") + '" class="fcancel btn color-blue"/>',
			'</div>',
			'</div>',
			'</form>',
			'</div>',
			'</div>',
			'</li>'
		].join('');

		str = $(str);
		str.find('.field-title').attr('title', fieldProperties.title);
		str.find('.field-name').attr('title', fieldProperties.name);
		str.find('.field-type').attr('title', typeName);

		str.find('a.fremove').bind('click', function(e) {
			var id = $(e.target).parent().attr('data');
			removeField(id);
		});

		rebindClickHandler(str.find('a.fedit'), editFieldHandler);

		$('ul[umigroupid=' + groupId + ']').append(str);
	};

	var addGroupToContainer = function(_options) {
		if (_options.id === null) return false;

		editableGroupsModel[_options.id] = {
			id: _options.id,
			title: _options.title,
			tip: _options.tip,
			name: _options.name,
			visible: true
		};

		var gid = 'g' + _options.id;
		var groupContainer =
				$("<div class=\"fg_container\">\
					<div class=\"fg_container_header\">\
						<span id='head" + gid + "title' class='left'>" + _options.title + " [" + _options.name + "]</span>\
						<span id='" + gid + "control'>\
                            <a class=\"gedit\"  data='" + _options.id + "' title='" + getLabel("js-type-edit-edit") + "' ><i class=\"small-ico i-edit\"></i></a>\
                            <a class=\"gremove\"  data='" + _options.id + "' title='" + getLabel("js-type-edit-remove") + "'><i class=\"small-ico i-remove\"></i></a>\
                        </span>\
                        <span id='" + gid + "save' style='display:none;'>\
							" + getLabel("js-type-edit-saving") + "...\
					    </span>\
					</div>\
					<div class=\"fg_container_body content\">\
                        <ul class=\"fg_container\">\
                        <div class='buttons'><a data='" + _options.id + "' class='fadd btn color-blue'>" + getLabel("js-type-edit-add_field") + "</a></div>\
                        </ul>\
                    </div>\
           </div>");
		if (_options.locked) {
			groupContainer.addClass('locked');
		}
		if (!_options.visible) {
			groupContainer.addClass('finvisible');
		}
		$("ul", groupContainer).addBack().attr("umigroupid", _options.id);

		groupsContainer.append(groupContainer);
		rebindClickHandler($('a.gedit'), editGroupHandler);
		rebindClickHandler($('a.gremove'), removeGroupHandler);
		rebindClickHandler($('a.fadd'), function(e) {
			var id = $(e.target).attr('data');
			addField(id);
		});
	};

	var removeGroup = function(id) {
		openDialog(getLabel('js-group-deleting-confirm'), getLabel('js-group-deleting-title'), {
			cancelButton: true,
			confirmText: getLabel('js-delete'),
			cancelText: getLabel('js-cancel'),
			confirmCallback: function(popupName) {
				closeDialog(popupName);
				$.get("/admin/data/json_delete_group/" + id + "/" + modernCurentType + "/", function() {
					showMessage(getLabel('js-group-deleting-success'));
					$('div[umigroupid=' + id + ']').remove();
				});
			}
		});
	};

	var removeField = function(id) {
		openDialog(getLabel('js-field-deleting-confirm'), getLabel('js-field-deleting-title'), {
			cancelButton: true,
			confirmText: getLabel('js-delete'),
			cancelText: getLabel('js-cancel'),
			confirmCallback: function(popupName) {
				groupsContainer.find('li[umifieldid=' + id + ']').remove();
				$.get("/admin/data/json_delete_field/" + id + "/" + modernCurentType + "/", function() {
					closeDialog(popupName);
				});
			}
		});
	};

	/**
	 * Рисуем окно с формой добавления поля
	 * @param gid
	 */
	var addField = function(gid) {
		var stringFieldTypeId = 3;

		openDialog('', getLabel("js-type-edit-new_field"), {
			html: getFieldForm({
				id: 'new',
				title: getLabel("js-type-edit-new_field"),
				typeId: stringFieldTypeId,
				typeName: '',
				name: '',
				tip: ''
			}),
			width: 360,
			cancelButton: true,
			confirmText: getLabel('js-add-button'),
			cancelText: getLabel('js-cancel'),
			customClass: 'modalUp',
			confirmCallback: function(popupName, scope) {
				var params = {
					'data[title]': $('#newtitle', scope).val(),
					'data[name]': $('#newname', scope).val(),
					'data[tip]': $('#newtip', scope).val(),
					'data[field_type_id]': $('#newtype', scope).val(),
					'data[restriction_id]': $('#newrestriction', scope).val(),
					'data[guide_id]': $('#newguide', scope).val()
				};
				if ($('#newvisible', scope).is(':checked')) {
					params['data[is_visible]'] = 1;
				}
				if ($('#newindexable', scope).is(':checked')) {
					params['data[in_search]'] = 1;
				}
				if ($('#newrequired', scope).is(':checked')) {
					params['data[is_required]'] = 1;
				}
				if ($('#newfilterable', scope).is(':checked')) {
					params['data[in_filter]'] = 1;
				}

				if ($('#newIsImportant', scope).is(':checked')) {
					params['data[is_important]'] = 1;
				}

				saveField('new', params, gid);
				closeDialog(popupName);
			},
			openCallback: function(scope) {
				initFieldForm({
							id: 'new',
							title: getLabel("js-type-edit-new_field"),
							typeId: 'new',
							typeName: '',
							name: '',
							tip: ''
						}, scope
				);

				initElements(scope);
			}
		});
	};

	var getFieldForm = function(options) {
		var str = [
			'<div class="group-block">',
			'<div class="title-edit" >' + getLabel("js-type-edit-title") + '</div>',
			'<input type="text" class="default" id="newtitle" name="data[title]" value="' + options.title + '" />',
			'</div>',
			'<div class="group-block">',
			'<div class="title-edit" >' + getLabel("js-type-edit-name") + '</div>',
			'<input type="text" class="default" id="newname" name="data[name]" value="' + options.name + '" />',
			'</div>',
			'<div class="group-block">',
			'<div class="title-edit" >' + getLabel("js-type-edit-tip") + '</div>',
			'<input type="text" class="default" id="newtip" name="data[tip]" value="' + options.tip + '" />',
			'</div>',
			'<div class="group-block">',
			'<div class="title-edit" >' + getLabel("js-type-edit-type") + '</div>',
			'<select id="' + options.id + 'type" name="data[field_type_id]">',
			'</select>',
			'</div>',
			'<div class="group-block">',
			'<div class="title-edit" >' + getLabel("js-type-edit-restriction") + '</div>',
			'<select id="' + options.id + 'restriction" name="data[restriction_id]">',
			'</select>',
			'</div>',
			'<div style="display: none;margin-bottom: 10px" id="' + options.id + 'guideCont">',
			'<div class="title-edit" >' + getLabel("js-type-edit-guide") + '</div>',
			'<select id="' + options.id + 'guide" name="data[guide]">',
			'</select>',
			'</div>',
			'<div class="option-block">',
			'<label><div class="checkbox ' + (options.visible ? 'checked' : '') + '">',
			'<input type="checkbox" id="newvisible" name="data[is_visible]" value="1" class="checkbox" ',
			(options.visible ? ' checked="" ' : ''),
			'/>',
			'</div>',
			'<span class="field-option-title">' + getLabel("js-type-edit-visible") + '</span></label>',
			'</div>',
			'<div class="option-block">',
			'<label><div class="checkbox ' + (options.indexable ? 'checked' : '') + '">',
			'<input type="checkbox" id="newindexable" name="data[in_search]" value="1" class="checkbox" ',
			(options.indexable ? ' checked="" ' : ''),
			'/>',
			'</div>',
			'<span class="field-option-title">' + getLabel("js-type-edit-indexable") + '</span></label>',
			'</div>',
			'<div class="option-block">',
			'<label><div class="checkbox ' + (options.required ? 'checked' : '') + '">',
			'<input type="checkbox" id="newrequired" name="data[is_required]" value="1" class="checkbox" ',
			(options.required ? ' checked="" ' : ''),
			'/>',
			'</div>',
			'<span class="field-option-title">' + getLabel("js-type-edit-required") + '</span></label>',
			'</div>',
			'<div class="option-block">',
			'<label><div class="checkbox ' + (options.filterable ? 'checked' : '') + '">',
			'<input type="checkbox" id="newfilterable" name="data[in_filter]" value="1" class="checkbox" ',
			(options.filterable ? ' checked="" ' : ''),
			'/>',
			'</div>',
			'<span class="field-option-title">' + getLabel("js-type-edit-filterable") + '</span></label>',
			'</div>',
			'<div class="option-block">',
			'<label><div class="checkbox ' + (options.isImportant ? 'checked' : '') + '">',
			'<input type="checkbox" id="newIsImportant" name="data[is_important]" value="1" class="checkbox" ',
			(options.isImportant ? ' checked="" ' : ''),
			'/>',
			'</div>',
			'<span class="field-option-title">' + getLabel("js-type-edit-important") + '</span></label>',
			'</div>',
		].join('');

		str = $(str);

		return str;
	};

	var initFieldForm = function(options, scope) {
		$('#newtype', scope).add('#' + options.id + 'type', scope).change(function() {
					var value = this.value;
					var typeO = jQuery.grep(typesList, function(o) {
						return o.id == value;
					});
					var guideFieldSelector = "#" + options.id + "guideCont";
					if (typeO.length && (typeO[0].dataType == "relation" || typeO[0].dataType == "optioned")) {
						jQuery(guideFieldSelector, scope).show("normal", function() {
							loadGuidesInfo(options.id, scope, options['guideId']);
						});
					} else {
						jQuery(guideFieldSelector, scope).hide();
					}
					loadRestrictionsInfo(options.id, scope, options['restrictionId']);
				}
		);
		loadTypesInfo(options.id, scope, options.typeId);
	};

	var loadGuidesInfo = function(id, context, selectedGuideId) {
		var select = jQuery("#" + id + "guide", context);
		var selected = false;
		select.attr("disabled", true);
		if (guidesList.length) {
			var options = "<option value=''></option>";
			for (var i = 0; i < guidesList.length; i++) {
				selected = guidesList[i].id == selectedGuideId;
				options += "<option " + (selected ? 'selected' : '') + " value='" + guidesList[i].id + "' >" + guidesList[i].name + "</option>";
			}
			select.html(options);
			select.attr("disabled", false);
		} else {
			$.post("/udata/system/publicGuidesList/.json", {}, function(data) {
						var items = data.items.item,
								keys = Object.keys(items);
						for (var i = 0; i < keys.length; i++) {
							var itm = items[keys[i]];
							guidesList[guidesList.length] = {
								id: itm.id,
								name: itm.name
							};
						}
						loadGuidesInfo(id, context, selectedGuideId);
					}
					, 'json');
		}
	};

	var loadRestrictionsInfo = function(id, context, selectedId) {
		var select = jQuery("#" + id + "restriction", context);
		var typeId = jQuery("#" + id + "type", context).val();
		select.attr("disabled", true);

		if (!restrictionLoaded) {
			$.post("/udata/data/getRestrictionsList/.json", {}, function(data) {
						var items = data.items.item,
								keys = Object.keys(items);
						for (var i = 0; i < keys.length; i++) {
							var typeId = items[keys[i]]["field-type-id"];
							if (!restrictionsList[typeId]) restrictionsList[typeId] = [];
							restrictionsList[typeId].push(
									{
										id: items[keys[i]].id,
										name: items[keys[i]].name,
										title: items[keys[i]].title
									});
						}
						restrictionLoaded = true;
						loadRestrictionsInfo(id, context, selectedId);
					}
					, 'json');
		} else {
			var options = '<option value="0" selected> </option>';
			var selected = false;
			if (restrictionsList[typeId]) {
				for (var i = 0; i < restrictionsList[typeId].length; i++) {
					selected = restrictionsList[typeId][i].id == selectedId;
					options += "<option " + (selected ? 'selected' : '') + " value='" + restrictionsList[typeId][i].id + "'>" + restrictionsList[typeId][i].title + "</option>";
				}
			}

			select.html(options);
			if (restrictionsList[typeId] && restrictionsList[typeId].length)
				select.attr("disabled", false);
		}

	};

	var loadTypesInfo = function(id, context, typeId) {
		var select = jQuery("#" + id + "type", context);
		var defaultFieldType = 'string';
		var isNew = isNaN(parseInt(typeId));

		if (typesList.length) {
			if (select.get(0).options.length > 1) return;
			var options = '';
			var value = select.prop('value');
			var selected = '';
			for (var i = 0; i < typesList.length; i++) {
				if ((isNew && typesList[i]['dataType'] == defaultFieldType) || typesList[i].id == typeId) {
					selected = 'selected';
				}

				options += "<option data-type=" + typesList[i]['dataType'] + " value='" + typesList[i].id + "' " + selected + ">" + typesList[i].name + "</option>";
				selected = '';
			}
			select.html(options);
			select.attr("disabled", false);
			select.change();
		} else {

			select.attr("disabled", true);
			jQuery.post("/udata/system/fieldTypesList/.json", {}, function(data) {
				var items = data.items.item;
				var sortedItems = _.sortBy(items, 'name');
				var keys = Object.keys(sortedItems);
				var itm;

				for (var i = 0; i < keys.length; i++) {
					itm = sortedItems[keys[i]];
					typesList.push({
						id: itm.id,
						name: itm.name,
						dataType: itm['data-type'],
						multiple: itm['is-multiple'] != null
					});
				}
				loadTypesInfo(id, context, typeId);
			}, 'json');
		}
	};

	var transliterateTitle = function(title) {
		return transliterateRu(title).replace(/\s+/g, "_").replace(/[^A-z0-9_]+/g, "").toLowerCase();
	};

	/**
	 * Колебек длятранслитерации титульников в имя
	 * @param event
	 */
	var universalTitleConvertCallback = function(event) {
		event.data.nameField.val(transliterateTitle(event.currentTarget.value));
	};

	var getGroupForm = function(option) {
		var tipFieldId = 'newtip_' + Math.round(Math.random() * 100000);

		var str = [
			'<div>',
			'<div class="group-block">',
			'<div class="title-edit">' + getLabel("js-type-edit-title") + '</div>',
			'<input type="text" class="default" id="newtitle" value="' + option.title + '">',
			'</div>',
			'<div class="group-block">',
			'<div class="title-edit">' + getLabel("js-type-edit-name") + '</div>',
			'<input type="text" class="default" id="newname" value="' + option.name + '">',
			'</div>',
			'<div class="group-block">',
			'<div class="title-edit">' + getLabel("js-type-edit-tip") + '</div>',
			'<textarea class="default wysiwyg tip" id="' + tipFieldId + '">' + (option.tip || '') + '</textarea>',
			'</div>',
			'<div class="group-block">',
			'<label><div class="checkbox ' + (option.visible ? 'checked' : '') + '">',
			'<input type="checkbox" id="newvisible" name="data[in_search]" value="1" class="checkbox" ',
			(option.visible ? ' checked="" ' : ''),
			'/>',
			'</div>',
			'<span>' + getLabel("js-type-edit-visible") + '</span></label>',
			'</div>',
			'</div>'].join('');

		str = $(str);

		return str;
	};

	var actor = {

		/** Инициализация объекта. Цепляем всякие интересные события. */
		init: function(id, gmodel) {
			getJSONPathScript();
			var that = this;
			editableGroupsModel = gmodel;
			typeId = id;
			//контейнер групп
			groupsContainer = $('div#groupsContainer > .row:nth-child(2) > div');


			this.initGroupsSorting();

			groupsContainer.sortable({
				items: "div.fg_container:not(:first)",
				update: function(e, ui) {
					var groupId = ui.item.attr("umiGroupId");
					var nextGroupId = ui.item.next("div.fg_container").attr("umiGroupId") || "false";
					jQuery.get("/admin/data/json_move_group_after/" + groupId + "/" + nextGroupId + "/" + typeId + "/");
				}
			});

			//Цепляем события добавления группы
			$('a.add_group').bind('click', function(event) {
				event.preventDefault();
				openDialog('', getLabel("js-type-edit-new_group-title"), {
					html: getGroupForm({
						id: 'new',
						title: getLabel("js-type-edit-new_group"),
						name: '',
						tip: '',
						visible: true
					}),
					width: 750,
					zIndex: 999,
					cancelButton: true,
					confirmText: getLabel('js-add-button'),
					cancelText: getLabel('js-cancel'),
					confirmCallback: function(popupName, scope) {
						var tip = getWysiwygContent($('.tip', scope));

						var params = {
							'data[title]': $('#newtitle').val(),
							'data[name]': $('#newname').val(),
							'data[tip]': tip,
							'data[is_visible]': $('#newvisible').is(':checked') ? 1 : 0
						};

						saveGroup('new', params);
						closeDialog(popupName);
					},
					openCallback: function(scope) {
						initWysiwyg();
						initElements(scope);
					}
				});
			});

			$('a.gedit').bind('click', editGroupHandler);
			$('a.fedit').bind('click', editFieldHandler);
			$('a.gremove').bind('click', removeGroupHandler);

			$('a.fremove').bind('click', function(e) {
				var id = $(e.target).parent().attr('data');
				removeField(id);
			});

			$('a.fadd').bind('click', function(e) {
				var id = $(e.target).attr('data');
				addField(id);
			});

			$('input[name="data[title]"]').focus(function(event) {
				var nameInput = $(event.currentTarget).parent().parent().find('input[name="data[name]"]');
				$(this).bind('keyup', {nameField: nameInput}, function(e) {
					universalTitleConvertCallback(e);
				});

			}).blur(function(event) {
				$(event.currentTarget).unbind('keyup');
			});

		},

		/**
		 * Выполняет инициализацию сортировки
		 * (возможность drag&drop полей внутри группы полей и между ними) полей групп
		 */
		initGroupsSorting: function() {
			var self = this;
			$(".fg_container", groupsContainer).sortable({
				connectWith: "ul.fg_container",
				dropOnEmpty: true,
				items: "li",
				placeholder: "ui-sortable-field-placeholder",
				remove: self.onSort,
				stop: self.onSort
			});
		},

		/**
		 * Обработчик события drag&drop полей
		 * @param {Object} event
		 * @param {Object} ui
		 * @link http://api.jqueryui.com/sortable/
		 * @returns {boolean}
		 */
		onSort: function(event, ui) {
			if (!ui.item || !ui.item.parent() || !ui.item.parent().attr("umiGroupId")) {
				return false;
			}

			var destContainer = ui.item.parent().parent().parent();

			if (destContainer.hasClass('locked')) {
				return false;
			}

			var fieldId = ui.item.attr("umiFieldId");
			var nextFieldId = ui.item.next("li").attr("umiFieldId");
			var isLast = (nextFieldId != undefined) ? "false" : ui.item.parent().attr("umiGroupId");

			jQuery.get("/admin/data/json_move_field_after/" + fieldId + "/" + nextFieldId + "/" + isLast + "/" + typeId + "/");
		}

	};

	return actor;

})(jQuery);
