/**
 * Модуль, содержащий конструкторы контролов и некоторые функции для модуля "Каталог"
 * @type {{ElementMovingControl, CopyCreatingControl, getControl, addControl, openCategoriesWindow, moveItem}}
 */
var CatalogModule = (function($) {
    "use strict";

    /**
     * Инициализирует контрол
     * @param {Object} context объект контекста выполнения
     * @param {String} id идентификатор контейнера для контрола
     * @param {String} module текущий модуль
     * @param {Object} options объект срдержащий опции для контрола
     * @param {String} hierarchyType строка вида: 'Модуль::Метод',
     * обозначающая тип выводимых объектов в дереве
     */
    var initControl = function(context, id, module, options, hierarchyType) {
        hierarchyType = hierarchyType || '';
        var self = context;
        var hierarchyTypeString = hierarchyType.split('::').join('-');
        var controlOptions = {
            idPrefix : '',
            treeURL: '/styles/skins/modern/design/js/common/parents.html',
            inputWidth: '100%',
            popupTitle: getLabel('js-choose-category')
        };

        var construct = function() {
            var userOptions = $.extend(options, controlOptions);
            self.symlinkControl = new symlinkControl(id, module, hierarchyTypeString, userOptions, hierarchyType);
            self.id = id;
            self.container = self.symlinkControl.container;
            self.itemsContainer = $('.items-list', self.container).get(0);
			$('li i', self.itemsContainer).each(function() {
				var deleteIcon = $(this);
				var page = deleteIcon.parent();
				var pageId = page.attr('umi:id');
				bindDeletePage(deleteIcon, pageId, page);
			});
        };

        construct();
    };

    /**
     * Возвращает ID текущей страницы
     * @returns {*}
     */
    var getCurrentId = function() {
        return (uAdmin.data && uAdmin.data.page ? uAdmin.data.page.id : window.page_id);
    };

    /**
     * Создает элемент со ссылками на родительские страницы (хлебные крошки)
     * @param {JSON} data исходные данные страницы
     * @returns {Element}
     */
    var createPathLinks = function (data) {
        var parentsList = data.parents.item;
        var parent;
        var linksContainer = document.createElement('span');
        linksContainer.className = 'paths';

        for (var i in parentsList) {
            if (!parentsList.hasOwnProperty(i)) {
                continue;
            }

            parent = parentsList[i];
            linksContainer.appendChild(createParentLink(parent));
        }

        linksContainer.appendChild(createPathElement(data));
        return linksContainer;
    };

    /**
     * Создает ссылку на родительскую страницу
     * @param {JSON} data исходные данные родительской страницы
     * @returns {Element}
     */
    var createParentLink = function createParentLink(data) {
        var link = document.createElement('a');

        link.href = "/admin/" + data.module + "/" + data.method + "/";
        link.className = "tree_link";
        link.target = "_blank";
        link.title = data.url;
        link.appendChild(document.createTextNode(data.name));

        $(link).bind('click', function() {
            return treeLink(data.settingsKey, data.treeLink);
        });

        return link;
    };

    /**
     * Создает текстовый элемент, содержащий название страницы
     * @param {JSON} data исходные данные страницы
     * @returns {Element}
     */
    var createPathElement = function(data) {
        var pathElement = document.createElement('span');
        pathElement.title = data.url;
        pathElement.appendChild(document.createTextNode(data.name));
        return pathElement;
    };

    /**
     * Перемещает страницу в другой раздел
     * @param {Number} parentId ID нового родителя
     * @param {Function} callback вызывается при успешном перемещении
     * @param {Number} element ID перемещаемой страницы
     */
    var moveItem = function(parentId, callback, element) {
        var elementId = element || getCurrentId(),
            selectedList = (Control.HandleItem !== null) ? Control.HandleItem.control.selectedList : [],
            data;
        callback = typeof callback == 'function' ? callback : function() {};

        if (!elementId || !parentId || elementId == parentId) {
            return;
        }

        data = {
            element: elementId,
            rel: parentId,
            return_copies : true
        };

        if (_.keys(selectedList).length>0){
            data['selected_list'] = [];
            _.each(selectedList, function (item) {
                data['selected_list'].push(item.id);
            });
        }

        $.ajax({
            url : "/admin/content/tree_move_element.json",
            type : "get",
            dataType : "json",
            data : data,
            success : function(response) {
                callback(response);
            }
        });
    };

    /**
     * Контрол для осуществления перемещения элементов
     * @param {String} id идентификатор контейнера
     * @param {String} module целевой модуль
     * @param {Object} options опции контрола
     * @param {String} hierarchyType строка вида: 'Модуль::Метод',
     * обозначающая тип выводимых объектов в дереве
     * @constructor
     */
    function ElementMovingControl (id, module, options, hierarchyType) {
        initControl(this, id, module, options, hierarchyType);
    }

    /**
     * Перемещает элемент в другой раздел
     * @param {Number} parentId ID нового раздела
     */
    ElementMovingControl.prototype.moveItem = function(parentId) {
        var self = this;
        moveItem(parentId, function(response) {
            if (!response.data || !response.data.page) {
                return;
            }

            var movedElementData = response.data.page.copies.copy[0];
            var pathElement = createPathLinks(movedElementData);
            var listElement = $('li', self.itemsContainer).eq(0);
            listElement.html('');
            listElement.append(pathElement);
        }, null);
    };

    /**
     * Возвращает идентификатор контрола
     * @returns {*}
     */
    ElementMovingControl.prototype.getId = CopyCreatingControl.prototype.getId = function() {
        return this.id;
    };

    /**
     * Контрол для создания виртуальных копий
     * @param {String} id идентификатор контейнера
     * @param {String} module целевой модуль
     * @param {Object} options опции контрола
     * @param {String} hierarchyType строка вида: 'Модуль::Метод',
     * обозначающая тип выводимых объектов в дереве
     * @constructor
     */
    function CopyCreatingControl(id, module, options, hierarchyType) {
        initControl(this, id, module, options, hierarchyType);
    }

    /**
     * Создает виртуальную копию страницы в разделе с ID = parentId
     * @param {Number} parentId ID раздела, в котором будет создана виртуальная копия
     */
    CopyCreatingControl.prototype.addCopy = function(parentId) {
        var elementId = getCurrentId();
        var self = this;

        if (!elementId || !parentId || elementId == parentId) {
            return;
        }

        $.ajax({
            url : "/admin/content/tree_copy_element.json",
            type : "get",
            dataType : "json",
            data : {
                element : elementId,
                rel : parentId,
                copyAll : 1,
                return_copies : 1,
                clone_mode : 0
            },
            success : function(response) {
                if (!response.data || !response.data.page) {
                    return;
                }

                var copyData = response.data.page.copies.copy[0];
                var pathElement = createPathLinks(copyData);

				var deleteIcon = $(document.createElement('i'));
				deleteIcon.attr('class', 'small-ico i-remove virtual-copy-delete');

                var listElement = $(document.createElement('li'));
                listElement.attr('umi:id', copyData.id);

				bindDeletePage(deleteIcon, copyData.id, listElement);

                if (copyData.basetype) {
                    listElement.attr('umi:module', copyData.basetype.module);
                    listElement.attr('umi:method', copyData.basetype.method);
                }

				listElement.append(deleteIcon);
                listElement.append(pathElement);
                $(self.itemsContainer).append(listElement);
            }
        });
    };

    /** @var [] controlsList хранит список добавленных контролов**/
    var controlsList = [];

	/**
	 * Устанавливает обработчик клика кнопки, который
	 * удаляет заданную страницу
	 * @param {Object} button jquery объект, которому назначается обработчик
	 * @param {String} pageId идентификатор удаляемой страницы
	 * @param {Object} page jquery объект, который нужно удалить вместе со страницей
	 */
	function bindDeletePage(button, pageId, page) {
		button.on("click", function() {
			$.ajax({
				url : "/admin/content/tree_delete_element.xml?csrf=" + csrfProtection.getToken(),
				type : "get",
				dataType : "xml",
				data : {
					element : pageId,
					childs : 1,
					allow : true
				},
				context : this,
				success : function(){
					page.remove();
				}
			});
		});
	}

    /**
     * Возвращает контрол по его идентификатору
     * @param {String} id идентификатор контрола
     * @returns {*}
     */
    function getControl(id) {
        return controlsList[id];
    }

    /**
     * Добавляет контрол в список
     * @param {Object} control объект контрола
     * @returns {*}
     */
    function addControl(control) {
        return controlsList[control.getId()] = control;
    }

    /**
     * Открывает окно с выбором категорий
     * @param {TableItem|TreeItem} handleItem выбранный элемент, для которого нужно произвести
     * какие-либо действия
     */
    function openCategoriesWindow(handleItem) {
        var popupName = 'SiteTree';
        var popupTitle = getLabel('js-choose-category');
        var treeBaseURL = '/styles/skins/modern/design/js/common/parents.html';
        var module = 'catalog';
        var typeString = '&hierarchy_types=catalog-category';
        var rootId = '';

        jQuery.openPopupLayer({
            name   : popupName,
            title  : popupTitle,
            width  : '100%',
            height : 335,
            url    : treeBaseURL + "?id=" + (handleItem.id) +  (module ? "&module=" + module : "" ) +
            '&name=' + popupName +
            typeString + (window.lang_id ? "&lang_id=" + window.lang_id : "") +
            (rootId ? "&root_id=" + rootId : "") + '&mode=tree'
        });
    }


    return {
        ElementMovingControl: ElementMovingControl,
        CopyCreatingControl : CopyCreatingControl,
        getControl: getControl,
        addControl: addControl,
        openCategoriesWindow: openCategoriesWindow,
        moveItem: moveItem
    };

})(jQuery);

