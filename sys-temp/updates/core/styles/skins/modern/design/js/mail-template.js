(function($) {
    var defaultWrapperSymbol = '%';

    'use strict';

    /** Выполняется, когда все элементы DOM готовы */
    $(function() {
        initFieldsElements();
    });

    /** Инициализирует элементы вставки идентификаторов полей в соответствующие тестовые поля */
    function initFieldsElements() {
        $('.mail-template li a').unbind('click').bind('click', function(e) {
            e.preventDefault();
            var fieldValue = getInsertingField($(this).data('value'));
            var $textBox = getRelatedTextBox(this);

            if (!$textBox.length) {
                return;
            }

            switch ($textBox.prop('tagName').toLowerCase()) {
                case 'input':
                    var caretPosition = $textBox.get(0).selectionStart;
                    $textBox.val(insertSubString(fieldValue, $textBox.val(), caretPosition));
                    $textBox.focus();
                    setCaretPosition($textBox.get(0), caretPosition + fieldValue.length);
                    break;
                case 'textarea':
                    if (!window['tinyMCE']) {
                        return;
                    }

                    insertField($(this), getRelatedEditor($textBox));
                    break;

                //no default
            }
        });
    }

    /** Вставляет идентификатор поля в контент редактора */
    function insertField($field, editor) {
        insertText($field.data('value'), editor);
    }

    /** Вставляет строку в позицию курсора редактора */
    function insertText(text, editor, wrapperSymbol) {
        if (editor && typeof editor.execCommand != 'undefined') {
            editor.execCommand('mceInsertContent', true, getInsertingField(text, wrapperSymbol));
        }
    }

    /**
     * Возвращает строковое представление поля для вставки
     * @param {String} fieldName имя поля
     * @param {String|undefined} wrapperSymbol символ, обрамляющий имя поля
     * @returns {string}
     */
    function getInsertingField(fieldName, wrapperSymbol) {
        wrapperSymbol = typeof wrapperSymbol == 'string' ? wrapperSymbol : defaultWrapperSymbol;

        return [wrapperSymbol, fieldName, wrapperSymbol].join('');
    }

    /**
     * Вставляет подстроку в строку в указанной позиции
     * @param {String} subString вставляемая строка
     * @param {String} string строка, в которую будет вставлена под строка
     * @param {Number} position позиция вставки подстроки
     * @returns {string} новая строка с вставленной подстрокой
     */
    function insertSubString(subString, string, position) {
        return [string.slice(0, position), subString, string.slice(position)].join('');
    }

    /**
     * Возвращает связанное тестовое поле со ссылкой
     * @param {HTMLElement} link элемент ссылки
     * @returns {*}
     */
    function getRelatedTextBox(link) {
        return $(link).closest('.mail-template').find('input, textarea').eq(0);
    }

    /**
     * Возвращает редактор, связанный с текстовым полем
     * @param textBox элемент текстового поля
     * @returns {*}
     */
    function getRelatedEditor(textBox) {
        return tinyMCE.get($(textBox).attr('id'));
    }

    /**
     * Устанавливает позицию курсора в текстовом поле
     * @param {HTMLElement} textBox элемент тестового поля
     * @param {Number} caretPos новая позиция курсора
     */
   function setCaretPosition(textBox, caretPos) {
        if(textBox != null) {
            if(textBox.createTextRange) {
                var range = textBox.createTextRange();
                range.move('character', caretPos);
                range.select();
            }
            else {
                if(textBox.selectionStart) {
                    textBox.focus();
                    textBox.setSelectionRange(caretPos, caretPos);
                }
                else
                    textBox.focus();
            }
        }
    }

})(jQuery);